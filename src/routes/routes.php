<?php

require 'vendor/router/Router.php';
include 'src/db/conn.php';

$router = new Router();
$conn = new Conn();

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
    if (!Router::found($_POST, 'name', 'email', 'password','gender','phone')) {
        return Router::response(400);
    }
    $_POST['password'] = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $stmt = $conn->prepare('INSERT INTO user(name,email,password,gender,phone) VALUES(?, ?, ?, ?, ?)');
    $stmt->bind_param('sssis', $_POST['name'], $_POST['email'], $_POST['password'],$_POST['gender'],$_POST['phone']);
    $result = $stmt->execute();
    if ($result) {
        return Router::response(201, ['id' => $conn->insert_id]);
    } else if ($stmt->errno == 1062) {
        return Router::response(409);
    } else {
        return Router::response(500);
    }
});

$router->run();