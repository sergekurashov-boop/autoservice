<?php
require 'includes/db.php';
require_once 'config.php';
session_start();

// Проверка прав администратора
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

// Основные функции
function getKbCategories($conn, $parent_id = 0) {
    $stmt = $conn->prepare("SELECT * FROM kb_categories WHERE parent_id = ? ORDER BY sort_order, title");
    $stmt->bind_param("i", $parent_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

function getKbCategory($conn, $id) {
    $stmt = $conn->prepare("SELECT * FROM kb_categories WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

function saveKbCategory($conn, $data) {
    $stmt = $conn->prepare("
        INSERT INTO kb_categories (title, description, parent_id, sort_order, is_active)
        VALUES (?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
        title = VALUES(title),
        description = VALUES(description),
        parent_id = VALUES(parent_id),
        sort_order = VALUES(sort_order),
        is_active = VALUES(is_active)
    ");
    
    $stmt->bind_param(
        "ssiii", 
        $data['title'],
        $data['description'],
        $data['parent_id'],
        $data['sort_order'],
        $data['is_active']
    );
    
    return $stmt->execute();
}

// Аналогичные функции для статей и вложений...

// Обработка действий
$action = $_GET['action'] ?? 'dashboard';
$message = '';
$error = '';

// Включаем шапку
define('ACCESS', true);
include 'templates/header.php';

// Главный контент админки
echo '<div class="container-fluid mt-4">';
echo '<div class="row">';

// Сайдбар меню
include 'templates/kb_admin_sidebar.php';

// Основной контент
echo '<div class="col-md-9">';

switch ($action) {
    case 'categories':
        include 'admin/kb_categories.php';
        break;
    case 'articles':
        include 'admin/kb_articles.php';
        break;
    case 'add_article':
        include 'admin/kb_article_form.php';
        break;
    case 'analytics':
        include 'admin/kb_analytics.php';
        break;
    default:
        include 'admin/kb_dashboard.php';
        break;
}

echo '</div>'; // Закрываем col-md-9
echo '</div>'; // Закрываем row
echo '</div>'; // Закрываем container-fluid

include 'templates/footer.php';