<?php
$page_title = "📖 Помощь по программе";
$page_description = "Центр помощи и документации системы";

$help_content = '
<div class="help-section">
    <h2>🎯 Быстрый доступ к разделам помощи</h2>
    <p>Выберите нужный раздел для получения подробной информации:</p>
</div>

<div class="help-cards-grid">
    <!-- Карточка быстрого старта -->
    <a href="help_quickstart.php" class="help-card">
        <div class="help-card-icon">🚀</div>
        <div class="help-card-content">
            <h3>Быстрый старт</h3>
            <p>Начало работы с системой, основные настройки</p>
        </div>
        <div class="help-card-arrow">→</div>
    </a>

    <!-- Карточка работы с заказами -->
    <a href="help_orders.php" class="help-card">
        <div class="help-card-icon">📋</div>
        <div class="help-card-content">
            <h3>Работа с заказами</h3>
            <p>Создание, редактирование, статусы заказов</p>
        </div>
        <div class="help-card-arrow">→</div>
    </a>

    <!-- Карточка управления складом -->
    <a href="help_warehouse.php" class="help-card">
        <div class="help-card-icon">🏭</div>
        <div class="help-card-content">
            <h3>Управление складом</h3>
            <p>Учет запчастей, приход/расход, остатки</p>
        </div>
        <div class="help-card-arrow">→</div>
    </a>

    <!-- Карточка отчетов -->
    <a href="help_reports.php" class="help-card">
        <div class="help-card-icon">📈</div>
        <div class="help-card-content">
            <h3>Формирование отчетов</h3>
            <p>Аналитика, статистика, финансовые отчеты</p>
        </div>
        <div class="help-card-arrow">→</div>
    </a>

    <!-- Карточка решения проблем -->
    <a href="help_troubleshooting.php" class="help-card">
        <div class="help-card-icon">🔧</div>
        <div class="help-card-content">
            <h3>Решение проблем</h3>
            <p>FAQ, устранение неполадок, поддержка</p>
        </div>
        <div class="help-card-arrow">→</div>
    </a>

    <!-- Карточка администрирования -->
    <a href="admin.php" class="help-card">
        <div class="help-card-icon">⚙️</div>
        <div class="help-card-content">
            <h3>Администрирование</h3>
            <p>Настройки системы, пользователи, резервные копии</p>
        </div>
        <div class="help-card-arrow">→</div>
    </a>
</div>

<div class="help-quick-links">
    <h3>🔗 Полезные ссылки</h3>
    <div class="quick-links-grid">
        <a href="create_order.php" class="quick-link">
            <span class="quick-link-icon">➕</span>
            <span>Создать заказ</span>
        </a>
        <a href="orders.php" class="quick-link">
            <span class="quick-link-icon">📝</span>
            <span>Список заказов</span>
        </a>
        <a href="warehouse.php" class="quick-link">
            <span class="quick-link-icon">📦</span>
            <span>Склад запчастей</span>
        </a>
        <a href="reports.php" class="quick-link">
            <span class="quick-link-icon">📊</span>
            <span>Отчеты</span>
        </a>
    </div>
</div>

<div class="help-search">
    <h3>🔍 Поиск по помощи</h3>
    <div class="search-box">
        <input type="text" placeholder="Введите вопрос или ключевое слово..." class="search-input">
        <button class="search-btn">Найти</button>
    </div>
</div>
';

$related_links = '
    <a href="help_quickstart.php">🚀 Быстрый старт</a>
    <a href="help_troubleshooting.php">🔧 Решение проблем</a>
';

include 'help_base.php';
?>