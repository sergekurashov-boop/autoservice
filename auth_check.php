<?php
function requireAuth() {
    if (!isset($_SESSION['user_id'])) {
        // Для AJAX запросов возвращаем JSON ошибку вместо перенаправления
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
            header('Content-Type: application/json');
            http_response_code(401);
            echo json_encode(['error' => 'Требуется авторизация']);
            exit;
        } else {
            header("Location: login.php");
            exit;
        }
    }
}

function requireAnyRole($allowed_roles) {
    requireAuth(); // Сначала проверяем авторизацию
    
    if (!in_array($_SESSION['user_role'], $allowed_roles)) {
        // Для AJAX запросов возвращаем JSON ошибку
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
            header('Content-Type: application/json');
            http_response_code(403);
            echo json_encode(['error' => 'Доступ запрещен']);
            exit;
        } else {
            http_response_code(403);
            die("Доступ запрещен");
        }
    }
}
?>