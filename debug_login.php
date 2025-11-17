<?php
session_start();
require_once 'includes/db.php';

echo "<h3>DEBUG LOGIN</h3>";

// Демо-вход
if (isset($_POST['demo_login'])) {
    $_SESSION['demo_mode'] = true;
    $_SESSION['user_id'] = 999;
    $_SESSION['username'] = 'demo';
    $_SESSION['user_role'] = 'admin';
    
    echo "Demo session set!<br>";
    echo "Session data: ";
    print_r($_SESSION);
    echo "<br>";
    
    echo "<a href='index.php'>Go to index.php</a>";
    exit;
}

// Обычный вход
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['username'])) {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    
    echo "Trying to login: $username<br>";
    
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    
    if ($user) {
        echo "User found: " . $user['username'] . "<br>";
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['user_role'] = $user['role'];
            
            echo "Login successful!<br>";
            echo "Session data: ";
            print_r($_SESSION);
            echo "<br>";
            
            echo "<a href='index.php'>Go to index.php</a>";
            exit;
        } else {
            echo "Password incorrect<br>";
        }
    } else {
        echo "User not found<br>";
    }
}
?>

<form method="POST">
    <input type="text" name="username" placeholder="Username" required>
    <input type="password" name="password" placeholder="Password" required>
    <button type="submit">Login</button>
</form>

<form method="POST">
    <button type="submit" name="demo_login">Demo Mode</button>
</form>