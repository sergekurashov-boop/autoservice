<?php
// header.php
// Определяем базовый URL
$base_url = 'http://' . $_SERVER['HTTP_HOST'] . '/autoservice';
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?? 'Autoservice' ?></title>
    
    <!-- Подключение CSS файлов -->
    <link rel="stylesheet" href="assets/css/sidebar.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <!-- Подключаем сайдбар  -->
    <?php include 'templates/sidebar.php'; ?>
    
    <!-- Основной контент -->
    <div class="main-content-1c" id="mainContent">
        <!-- Навбар -->
        <nav class="top-navbar">
            <div class="container-fluid">
                <button class="mobile-toggle" type="button" id="sidebarToggle">
                    ☰
                </button>
              <img src="images/ck50negativ.jpg" width="40" height="40" alt="Логотип" class="logo"><h4>Система управления автосервисом 🛠️  BMS</h4>
                <div class="nav-right">
                    <div class="search-container">
                        <form class="search-form" action="search.php" method="get">
                            <input type="search" name="q" placeholder="Поиск..." aria-label="Поиск">
                            <button type="submit">Найти</button>
                        </form>
                    </div>
                    <div class="user-dropdown">
                        <?php if (isset($_SESSION['user_id'])): ?>
                            <div class="user-menu">
                                <div class="user-avatar">
                                    <?= substr($_SESSION['full_name'] ?? 'U', 0, 1) ?>
                                </div>
                                <span class="user-info">
                                    <?= htmlspecialchars($_SESSION['full_name'] ?? 'Пользователь') ?>
                                    <small><?= $_SESSION['user_role'] ?? 'Гость' ?></small>
                                </span>
                            </div>
                            <div class="dropdown-content">
                                <a href="profile.php">👤 Профиль</a>
                                <?php if ($_SESSION['user_role'] === 'admin'): ?>
                                    <a href="user_management.php">👥 Управление пользователями</a>
                                <?php endif; ?>
                                <div class="divider"></div>
                                <a href="logout.php">🚪 Выход</a>
                            </div>
                        <?php else: ?>
                            <a href="login.php" class="login-btn">Войти</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Основной контент страницы -->
        <div class="content-container">