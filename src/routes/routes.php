<?php

require 'vendor/router/Router.php';
include 'src/db/conn.php';

$router = new Router();
$conn = new Conn();

function authUser($conn) {
    $stmt = $conn->prepare('SELECT * FROM user WHERE token = ?');
    $stmt->bind_param('s', Router::getBearerToken());
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
        return Router::response(201, ['id' => $conn->insert_id]);
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
//upload image problem
$router->route('POST', '/recipe', function() use ($conn) {
    $user = authUser($conn);
    if (!$user) return Router::response(403);

    if (!Router::found($_POST, 'title', 'description', 'cover_image', 'tips', 'category', 'instructions', 'ingredients')) {
        return Router::response(400);
    }

    $stmt = $conn->prepare('INSERT INTO recipe(user_id, title, description, cover_image, tips, category) VALUES(?, ?, ?, ?, ?, ?)');
    $stmt->bind_param('issssi', $user['id'], $_POST['title'], $_POST['description'], $_POST['cover_image'], $_POST['tips'], $_POST['category']);
    $result = $stmt->execute();
    $recipe_id =  $conn->insert_id;
    foreach (json_decode($_POST['ingredients']) as $ingredient){
        $stmt = $conn->prepare('INSERT INTO ingredient(recipe_id, name, amount, unit) VALUES(?, ?, ?, ?)');
        $stmt->bind_param('isis', $recipe_id, $ingredient['name'], $ingredient['amount'], $ingredient['unit']);
        $result = $stmt->execute();
    }
    foreach (json_decode($_POST['instructions']) as $instruction) {
        $stmt = $conn->prepare('INSERT INTO instruction(recipe_id, media, description, step) VALUES(?, ?, ?, ?)');
        $stmt->bind_param('issi', $recipe_id, $instruction['media'], $instruction['description'], $instruction['step']);
        $result = $stmt->execute();
    }
    $stmt->close();
    if ($result) {
        return Router::response(201, ['id' => $conn->insert_id]);
    } else if ($stmt->errno == 1062) {
        return Router::response(409);
    } else {
        return Router::response(500);
    }
});

$router->route('POST', '/album', function() use ($conn) {
    if (!Router::found($_POST, 'user_id', 'name')) {
        return Router::response(400);
    }

    $stmt = $conn->prepare('INSERT INTO album(user_id, name) VALUES(?, ?)');
    $stmt->bind_param('is', $_POST['user_id'], $_POST['name']);
    $result = $stmt->execute();

    if ($result) {
        return Router::response(201);
    } else if ($stmt->errno == 1062) {
        return Router::response(409);
    } else {
        return Router::response(500);
    }
});
$router->route('POST', '/album/[i:id]/item', function($id) use ($conn) {
    if (!Router::found($_POST, 'recipe_id')) {
        return Router::response(400);
    }

    $stmt = $conn->prepare('INSERT INTO album_recipe(album_id, recipe_id) VALUES(?, ?)');
    $stmt->bind_param('ii', $_POST['album_id'], $_POST['recipe_id']);
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
    $album = $stmt->get_result()->fetch_assoc();
    if ($album) {
        return Router::response(201, $album);
    }  else {
        return Router::response(500);
    }
});
//Get Specific Album
$router->route('GET', '/album/[i:id]', function($id) use ($conn) {
    if (!$id) {
        return Router::response(400);
    }
    $user = authUser($conn);
    if (!$user) return Router::response(403);
    $stmt = $conn->prepare('SELECT recipe.cover_image, recipe.title, user.name FROM album inner join album_recipe ar on album.id = ar.album_id inner join recipe on ar.recipe_id = recipe.id inner join user on recipe.user_id = user.id where album_id = ? and album.user_id = ?');
    $stmt->bind_param('ii', $id, $user['id']);
    $stmt->execute();
    $recipe = $stmt->get_result()->fetch_assoc();
    if ($recipe) {
        return Router::response(201, $recipe);
    }  else {
        return Router::response(500);
    }
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
$router->run();