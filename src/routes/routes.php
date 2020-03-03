<?php

require 'vendor/router/Router.php';
include 'src/db/conn.php';

$router = new Router();
$conn = new Conn();

function authUser($conn) {
    $stmt = $conn->prepare('SELECT * FROM user WHERE token = ?');
    $token = Router::getBearerToken();
    $stmt->bind_param('s', $token);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $user;
}

$router->route('GET', '/', function() {
    return 'Hello World';
});

$router->route('GET', '/user', function() use ($conn) {
    $result = $conn->query('SELECT * FROM user');
    $users = [];
    while ($user = $result->fetch_assoc()) {
        $users[] = $user;
    }
    if (Router::getBearerToken() == 'liyi980804') {
        return Router::response(200, $users);
    } else {
        return Router::response(403);
    }

});

$router->route('GET', '/user/[i:id]', function($id) use ($conn) {
    $stmt = $conn->prepare('SELECT * FROM user WHERE id = ?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    if ($user) {
        return Router::response(200, $user);
    } else {
        return Router::response(204);
    }
});

$router->route('POST', '/user', function() use ($conn) {
    if (!Router::found($_POST, 'name', 'email', 'password')) {
        return Router::response(400);
    }
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $token = bin2hex(random_bytes(16));
    $stmt = $conn->prepare('INSERT INTO user(name,email,password,token) VALUES(?, ?, ?, ?)');
    $stmt->bind_param('ssss', $_POST['name'], $_POST['email'], $password, $token);
    $result = $stmt->execute();
    if ($result) {
        return Router::response(200, ['token' => $token]);
    } else if ($stmt->errno == 1062) {
        return Router::response(409);
    } else {
        return Router::response(500);
    }
});

$router->route('POST', '/login', function () use ($conn) {
    if (!Router::found($_POST, 'email', 'password')) {
        return Router::response(400);
    }
    $stmt = $conn->prepare('SELECT * FROM user WHERE email = ?');
    $stmt->bind_param('s', $_POST['email']);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    if ($user && password_verify($_POST['password'], $user['password'])) {
        return Router::response(200, [
            'token' => $user['token']
        ]);
    }
    return Router::response(401 );
});

$router->route('GET', '/user/me', function () use ($conn) {
    $user = authUser($conn);
    if ($user) {
        unset($user['password']);
        return Router::response(200, $user);
    }
    return Router::response(403);
});

$router->route('GET', '/recipe', function() use ($conn) {
    $result = $conn->query('SELECT r.id, r.description, r.category, r.title, r.cover_image, r.tips, u.name, r.user_id FROM recipe r inner join user u on r.user_id = u.id');
    $recipes = [];
    while ($recipe = $result->fetch_assoc()) {
        $recipes[] = $recipe;
    }
    return Router::response(200, $recipes);
});

$router->route('GET', '/recipe/me', function() use ($conn) {

    $user = authUser($conn);
    if (!$user) return Router::response(403);
    $stmt = $conn->prepare('SELECT r.id, r.description, r.category, r.title, r.cover_image, r.tips, u.name, r.user_id FROM recipe r inner join user u on r.user_id = u.id where user_id = ?');
    $stmt->bind_param('i', $user['id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $recipes = [];
    while($recipe = $result->fetch_assoc()){
        $recipes[] = $recipe;

    }
    return Router::response(200, $recipes);
});
$router->route('GET', '/recipe/[i:id]/ingredient', function($id) use ($conn) {
    if (!$id) {
        return Router::response(400);
    }
    $stmt = $conn->prepare('SELECT * FROM ingredient where recipe_id = ?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $ingredients = [];
    while($ingredient = $result->fetch_assoc()){
        $ingredients[] = $ingredient;

    }
    return Router::response(200, $ingredients);
});

$router->route('GET', '/recipe/[i:id]/instruction', function($id) use ($conn) {
    if (!$id) {
        return Router::response(400);
    }
    $stmt = $conn->prepare('SELECT * FROM instruction where recipe_id = ?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $instructions = [];
    while($instruction = $result->fetch_assoc()){
        $instructions[] = $instruction;

    }
    return Router::response(200, $instructions);
});

//upload image problem
$router->route('POST', '/recipe', function() use ($conn) {
    $user = authUser($conn);
    if (!$user) return Router::response(403);
    if (
        !Router::found($_POST, 'title', 'description', 'tips', 'category', 'ingredients', 'instructions') ||
        !Router::found($_FILES, 'cover_image')) {
        return Router::response(400);
    }
    $dest = "uploads/" . bin2hex(random_bytes(6)) . $_FILES['cover_image']['name'];
    move_uploaded_file($_FILES['cover_image']['tmp_name'], $dest);
    $stmt = $conn->prepare('INSERT INTO recipe(user_id, title, description, cover_image, tips, category) VALUES(?, ?, ?, ?, ?, ?)');
    $stmt->bind_param('issssi', $user['id'], $_POST['title'],  $_POST['description'], $dest, $_POST['tips'], $_POST['category']);
    $result = $stmt->execute();
    if (!$result) {
        return Router::response(500);
    }
    $recipe_id =  $conn->insert_id;
    foreach ($_POST['ingredients'] as $ingredient) {
        $ingredient = json_decode($ingredient, true);
        $stmt = $conn->prepare('INSERT INTO ingredient(recipe_id, name, amount, unit) VALUES(?, ?, ?, ?)');
        $stmt->bind_param('isds', $recipe_id, $ingredient['name'], $ingredient['amount'], $ingredient['unit']);
        $stmt->execute();
    }
    foreach ($_POST['instructions'] as $instruction) {
        $instruction = json_decode($instruction, true);
        $stmt = $conn->prepare('INSERT INTO instruction(recipe_id, description, step) VALUES(?, ?, ?)');
        $stmt->bind_param('isi', $recipe_id, $instruction['description'], $instruction['step']);
        $stmt->execute();
    }
    foreach ($_FILES as $key => $image) {
        $stmt = $conn->prepare('UPDATE instruction SET media = ? WHERE step = ? AND recipe_id = ?');
        $dest = "uploads/" . bin2hex(random_bytes(6)) . $image['name'];
        move_uploaded_file($image['tmp_name'], $dest);
        $stmt->bind_param('sii', $dest, $key, $recipe_id);
        $stmt->execute();
    }
    return Router::response(201, ['id' => $recipe_id]);
});

$router->route('POST', '/album', function() use ($conn) {
    $user = authUser($conn);
    if (!$user) return Router::response(403);
    if (!Router::found($_POST, 'name')) {
        return Router::response(400);
    }

    $stmt = $conn->prepare('INSERT INTO album(user_id, name) VALUES(?, ?)');
    $stmt->bind_param('is', $user['id'], $_POST['name']);
    $result = $stmt->execute();

    if ($result) {
        return Router::response(201, ['id' => $conn->insert_id ]);
    } else {
        return Router::response(500);
    }
});
$router->route('POST', '/album/[i:id]/item', function($id) use ($conn) {
    if (!Router::found($_POST, 'recipe_id')) {
        return Router::response(400);
    }

    $stmt = $conn->prepare('INSERT INTO album_recipe(album_id, recipe_id) VALUES(?, ?)');
    $stmt->bind_param('ii', $id, $_POST['recipe_id']);
    $result = $stmt->execute();

    if ($result) {
        return Router::response(201);
    }  else {
        return Router::response(500);
    }
});
//Get Album
$router->route('GET', '/album', function() use ($conn) {
    $user = authUser($conn);
    if (!$user) return Router::response(403);
    $stmt = $conn->prepare('SELECT * FROM album where user_id = ?');
    $stmt->bind_param('i', $user['id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $albums = [];
    while($album = $result->fetch_assoc()){
        $albums[] = $album;
    }
    return Router::response(200, $albums);
});
//Get Specific Album

$router->route('GET', '/album/[i:id]/item', function($id) use ($conn) {
    if (!$id) {
        return Router::response(400);
    }
    $user = authUser($conn);
    if (!$user) return Router::response(403);
    $stmt = $conn->prepare('SELECT recipe.cover_image, recipe.title, user.name FROM album inner join album_recipe ar on album.id = ar.album_id inner join recipe on ar.recipe_id = recipe.id inner join user on recipe.user_id = user.id where album_id = ? and album.user_id = ?');
    $stmt->bind_param('ii', $id, $user['id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $recipes = [];
    while($recipe = $result->fetch_assoc()){
        $recipes[] = $recipe;
    }
    return Router::response(200, $recipes);
});
//route do not how to put
$router->route('POST', '/recipe/ratings', function() use ($conn) {
    if (!Router::found($_POST, 'recipe_id', 'user_id', 'ratings')) {
        return Router::response(400);
    }

    $stmt = $conn->prepare('INSERT INTO ratings(recipe_id, user_id, ratings) VALUES(?, ?)');
    $stmt->bind_param('iii', $_POST['recipe_id'], $_POST['user_id'], $_POST['ratings']);
    $result = $stmt->execute();

    if ($result) {
        return Router::response(201);
    } else if ($stmt->errno == 1062) {
        return Router::response(409);
    } else {
        return Router::response(500);
    }
});

$router->route('GET', '/category', function() use ($conn) {
    $result = $conn->query('SELECT * FROM category');
    $categories = [];
    while ($category = $result->fetch_assoc()) {
        $categories[] = $category;
    }
    return Router::response(200, $categories);
});

$router->route('GET', '/allergy', function() use ($conn) {
    $result = $conn->query('SELECT * FROM allergies_categories');
    $allergies_categories = [];
    while ($allergies_category = $result->fetch_assoc()) {
        $allergies_categories[] = $allergies_category;
    }
    return Router::response(200, $allergies_categories);
});
$router->run();