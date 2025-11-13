<?php
// auth_check.php

// Начинаем сессию если еще не начата
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Функция для проверки, авторизован ли пользователь
function requireAuth() {
    if (!isset($_SESSION['user_id'])) {
        header('Location: /autoservice/login.php');
        exit;
    }
}

// Функция для проверки роли пользователя
function requireRole($requiredRole) {
    requireAuth();
    
    if ($_SESSION['user_role'] !== $requiredRole) {
        header('Location: /autoservice/unauthorized.php');
        exit;
    }
}

// Функция для проверки нескольких ролей
function requireAnyRole($allowedRoles) {
    requireAuth();
    
    if (!in_array($_SESSION['user_role'], $allowedRoles)) {
        header('Location: /autoservice/unauthorized.php');
        exit;
    }
}

// Функция для получения информации о текущем пользователе
function getCurrentUser() {
    global $conn; // Исправлено: было $pdo, стало $conn
    
    if (!isset($_SESSION['user_id'])) {
        return null;
    }
    
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}