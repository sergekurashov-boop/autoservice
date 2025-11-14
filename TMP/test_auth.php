<?php
// test_auth.php
require_once 'includes/db.php';

// Тестовые данные
$test_user = 'admin';
$test_pass = 'TPZk3915';

$stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
$stmt->execute([$test_user]);
$user = $stmt->fetch();

if ($user) {
    echo "User found: " . $user['username'] . "<br>";
    echo "Password hash: " . $user['password'] . "<br>";
    echo "Password verify: " . (password_verify($test_pass, $user['password']) ? 'TRUE' : 'FALSE');
} else {
    echo "User not found!";
}
?>