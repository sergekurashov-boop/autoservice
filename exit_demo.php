<?php
session_start();

// Выход из демо-режима
if (isset($_SESSION['demo_mode'])) {
    unset($_SESSION['demo_mode']);
    unset($_SESSION['user_id']);
    unset($_SESSION['user_name']); 
    unset($_SESSION['user_role']);
    session_destroy();
}

header('Location: login.php');
exit;
?>