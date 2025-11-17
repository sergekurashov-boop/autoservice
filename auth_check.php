<?php
function requireAnyRole($allowed_roles) {
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit;
    }
    
    if (!in_array($_SESSION['user_role'], $allowed_roles)) {
        http_response_code(403);
        die("Доступ запрещен");
    }
}
?>