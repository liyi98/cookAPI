
<?php

function isEmailExists($email) {
    $stmt = $conn->prepare('SELECT * FROM user WHERE email = ?');
}