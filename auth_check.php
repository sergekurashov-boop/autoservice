<?php
// auth_check.php

// Используем существующий файл подключения к БД
require_once 'includes/db.php';

// Функция для проверки, авторизован ли пользователь
function requireAuth() {
    if (!isset($_SESSION['user_id'])) {
        header('Location: login.php');
        exit;
    }
}

// Функция для проверки роли пользователя
function requireRole($requiredRole) {
    requireAuth();
    
    if ($_SESSION['user_role'] !== $requiredRole) {
        http_response_code(403);
        die('Доступ запрещен. Недостаточно прав.');
    }
}

// Функция для проверки нескольких ролей
function requireAnyRole($allowedRoles) {
    requireAuth();
    
    if (!in_array($_SESSION['user_role'], $allowedRoles)) {
        http_response_code(403);
        die('Доступ запрещен. Недостаточно прав.');
    }
}

// Функция для получения информации о текущем пользователе
function getCurrentUser() {
    global $pdo;
    
    if (!isset($_SESSION['user_id'])) {
        return null;
    }
    
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch();
}