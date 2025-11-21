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
    <style>
        /* Стили для кнопки помощи в хедере */
        .help-dropdown {
            position: relative;
            display: inline-block;
        }
        
        .help-btn {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            background: #8b6914;
            border: 1px solid #e6d8a8;
            color: #FFFFFF;
            text-decoration: none;
            border-radius: 0;
            font-weight: 500;
            transition: all 0.2s ease;
            cursor: pointer;
        }
        
        .help-btn:hover {
            background: #f5e8b0;
            text-decoration: none;
            color: #5c4a00;
        }
        
        .help-dropdown-content {
            display: none;
            position: absolute;
            top: 100%;
            right: 0;
            background: #fffef5;
            border: 1px solid #e6d8a8;
            min-width: 250px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            z-index: 1000;
        }
        
        .help-dropdown:hover .help-dropdown-content {
            display: block;
        }
        
        .help-dropdown-content a {
            display: block;
            padding: 0.75rem 1rem;
            color: #5c4a00;
            text-decoration: none;
            border-bottom: 1px solid #f5f0d8;
            transition: background 0.2s ease;
        }
        
        .help-dropdown-content a:hover {
            background: #f5e8b0;
            text-decoration: none;
        }
        
        .help-dropdown-content a:last-child {
            border-bottom: none;
        }
        
        /* Стили для кнопочного интерфейса */
        .btn-group-1c {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }
        
        .btn-1c {
            padding: 0.75rem 1.5rem;
            background: #fffef5;
            border: 1px solid #e6d8a8;
            color: #5c4a00;
            text-decoration: none;
            border-radius: 0;
            font-weight: 500;
            transition: all 0.2s ease;
            cursor: pointer;
            text-align: center;
        }
        
        .btn-1c:hover {
            background: #f5e8b0;
            transform: translateY(-1px);
            text-decoration: none;
            color: #5c4a00;
        }
        
        .btn-1c.active {
            background: #8b6914;
            color: white;
            border-color: #7a5a10;
        }
        
        .btn-1c.primary {
            background: #8b6914;
            color: white;
            border-color: #7a5a10;
        }
        
        .btn-1c.primary:hover {
            background: #7a5a10;
            color: white;
        }
    </style>
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
                <img src="images/ck50negativ.jpg" width="40" height="40" alt="Логотип" class="logo">
                <h4>Система управления автосервисом 🛠️  BMS</h4>
                <div class="nav-right">
                    <!-- Кнопка помощи -->
                    <div class="help-dropdown">
                        <a href="javascript:void(0)" class="help-btn">
                            <span>❓</span>
                            <span>Помощь</span>
                        </a>
                        <div class="help-dropdown-content">
                            <a href="help.php">
                                <span>📖</span>
                                Помощь по программе
                            </a>
                            <a href="help_quickstart.php">
                                <span>🚀</span>
                                Быстрый старт
                            </a>
                            <a href="help_orders.php">
                                <span>📋</span>
                                Работа с заказами
                            </a>
                            <a href="help_warehouse.php">
                                <span>🏭</span>
                                Управление складом
                            </a>
                            <a href="help_reports.php">
                                <span>📈</span>
                                Формирование отчетов
                            </a>
                            <a href="help_troubleshooting.php">
                                <span>🔧</span>
                                Решение проблем
                            </a>
                        </div>
                    </div>
                    
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