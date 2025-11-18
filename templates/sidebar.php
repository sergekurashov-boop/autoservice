<!-- Сайдбар в стиле 1С -->
<div class="sidebar-1c" id="mainSidebar">
    <button class="sidebar-toggle-1c" id="sidebarToggle">‹</button>
    
    <div class="sidebar-header-1c">
        <h5>🛠️ <span>AUTOSERVICE</span></h5>
        <div class="sidebar-subtitle">Управление автосервисом</div>
    </div>
    
    <nav class="sidebar-nav-1c">
        <!-- Главное меню -->
        <a href="index.php" class="sidebar-item-1c <?= basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : '' ?>">
            <span class="sidebar-icon-1c">📊</span>
            <span class="menu-text">Главная</span>
        </a>
        
        <!-- 🔹 АККОРДЕОН ЗАКАЗЫ -->
        <div class="accordion-1c">
            <div class="accordion-header-1c" data-accordion="orders">
                <span class="sidebar-icon-1c">📋</span>
                <span class="menu-text">Заказы</span>
                <span class="accordion-icon-1c">▼</span>
            </div>
            <div class="accordion-content-1c" id="orders-menu">
                <a href="orders.php" class="sidebar-subitem-1c <?= basename($_SERVER['PHP_SELF']) == 'orders.php' ? 'active' : '' ?>">
                    📝 Все заказы
                </a>
                <a href="create_order.php" class="sidebar-subitem-1c <?= basename($_SERVER['PHP_SELF']) == 'create_order.php' ? 'active' : '' ?>">
                    ➕ Новый заказ
                </a>
                <a href="orders.php?status=active" class="sidebar-subitem-1c">
                    🔧 В работе
                </a>
                <a href="orders.php?status=completed" class="sidebar-subitem-1c">
                    ✅ Выполненные
                </a>
				 <a href="booking.php?status=completed" class="sidebar-subitem-1c">
                📅Запись на обслуживание
        </a>
                <!-- Будет добавляться позже -->
                <a href="inspection.php" class="sidebar-subitem-1c <?= basename($_SERVER['PHP_SELF']) == 'inspection.php' ? 'active' : '' ?>" style="display: none;">
                    🔍 Осмотр авто
                </a>
            </div>
        </div>

        <!-- 🔹 АККОРДЕОН ШИНОМОНТАЖ (ОБНОВЛЕННАЯ ИКОНКА) -->
        <div class="accordion-1c">
            <div class="accordion-header-1c" data-accordion="tire">
                <span class="sidebar-icon-1c">🚗</span>
                <span class="menu-text">Шиномонтаж</span>
                <span class="accordion-icon-1c">▼</span>
            </div>
            <div class="accordion-content-1c" id="tire-menu">
                <a href="tire_orders.php" class="sidebar-subitem-1c <?= basename($_SERVER['PHP_SELF']) == 'tire_orders.php' ? 'active' : '' ?>">
                    📋 Заказы шиномонтажа
                </a>
                <a href="tire_create.php" class="sidebar-subitem-1c <?= basename($_SERVER['PHP_SELF']) == 'tire_create.php' ? 'active' : '' ?>">
                    ➕ Новый заказ-наряд
                </a>
                <a href="tire_stats.php" class="sidebar-subitem-1c <?= basename($_SERVER['PHP_SELF']) == 'tire_stats.php' ? 'active' : '' ?>">
                    📊 Статистика шиномонтажа
                </a>
            </div>
        </div>

        <!-- Аккордеон Управление (ОБНОВЛЕННЫЙ) -->
        <div class="accordion-1c">
            <div class="accordion-header-1c" data-accordion="management">
                <span class="sidebar-icon-1c">⚙️</span>
                <span class="menu-text">Номенклатура</span>
                <span class="accordion-icon-1c">▼</span>
            </div>
            <div class="accordion-content-1c" id="management-menu">
                <a href="clients.php" class="sidebar-subitem-1c <?= basename($_SERVER['PHP_SELF']) == 'clients.php' ? 'active' : '' ?>">
                    👥 Клиенты
                </a>
                <a href="cars.php" class="sidebar-subitem-1c <?= basename($_SERVER['PHP_SELF']) == 'cars.php' ? 'active' : '' ?>">
                    🚗 Транспорт
                </a>
                <a href="services.php" class="sidebar-subitem-1c <?= basename($_SERVER['PHP_SELF']) == 'services.php' ? 'active' : '' ?>">
                    🔧 Услуги
                </a>
                <!-- ОБНОВЛЕННАЯ ССЫЛКА: вместо mechanics.php -> staff_management.php -->
                <a href="staff_management.php" class="sidebar-subitem-1c <?= basename($_SERVER['PHP_SELF']) == 'staff_management.php' ? 'active' : '' ?>">
                    👥 Персонал
                </a>
                <a href="parts.php" class="sidebar-subitem-1c <?= basename($_SERVER['PHP_SELF']) == 'parts.php' ? 'active' : '' ?>">
                    🔩 Запчасти
                </a>
                <a href="tasks.php" class="sidebar-subitem-1c <?= basename($_SERVER['PHP_SELF']) == 'tasks.php' ? 'active' : '' ?>">
                    ⏰ Задачи
                </a>
            </div>
        </div>
        
        <!-- Одиночные пункты -->
       
        
        <a href="warehouse.php" class="sidebar-item-1c <?= basename($_SERVER['PHP_SELF']) == 'warehouse.php' ? 'active' : '' ?>">
            <span class="sidebar-icon-1c">🏭</span>
            <span class="menu-text">Склад запчастей</span>
        </a>
        
        <!-- Аккордеон Контент -->
        <div class="accordion-1c">
            <div class="accordion-header-1c" data-accordion="content">
                <span class="sidebar-icon-1c">📁</span>
                <span class="menu-text">Контент</span>
                <span class="accordion-icon-1c">▼</span>
            </div>
            <div class="accordion-content-1c" id="content-menu">
                <a href="faq.php" class="sidebar-subitem-1c <?= basename($_SERVER['PHP_SELF']) == 'faq.php' ? 'active' : '' ?>">
                    ❓ Управление FAQ
                </a>
                <a href="admin_faq.php" class="sidebar-subitem-1c <?= basename($_SERVER['PHP_SELF']) == 'admin_faq.php' ? 'active' : '' ?>">
                    💬 FAQ
                </a>
                <a href="knowbase.php" class="sidebar-subitem-1c <?= basename($_SERVER['PHP_SELF']) == 'knowbase.php' ? 'active' : '' ?>">
                    📚 База знаний
                </a>
                <a href="https://www.carmans.net/" target="_blank" class="sidebar-subitem-1c">
                    📖 Мануалы
                </a>
            </div>
        </div>
        
        <!-- Отчеты -->
        <a href="reports.php" class="sidebar-item-1c <?= basename($_SERVER['PHP_SELF']) == 'reports.php' ? 'active' : '' ?>">
            <span class="sidebar-icon-1c">📈</span>
            <span class="menu-text">Отчеты</span>
        </a>

        <!-- 🔹 АККОРДЕОН ПОМОЩЬ -->
        <div class="accordion-1c">
            <div class="accordion-header-1c" data-accordion="help">
                <span class="sidebar-icon-1c">❓</span>
                <span class="menu-text">Помощь</span>
                <span class="accordion-icon-1c">▼</span>
            </div>
            <div class="accordion-content-1c" id="help-menu">
                <a href="help.php" class="sidebar-subitem-1c <?= basename($_SERVER['PHP_SELF']) == 'help.php' ? 'active' : '' ?>">
                    📖 Помощь по программе
                </a>
                <a href="help_quickstart.php" class="sidebar-subitem-1c <?= basename($_SERVER['PHP_SELF']) == 'help_quickstart.php' ? 'active' : '' ?>">
                    🚀 Быстрый старт
                </a>
                <a href="help_orders.php" class="sidebar-subitem-1c <?= basename($_SERVER['PHP_SELF']) == 'help_orders.php' ? 'active' : '' ?>">
                    📋 Работа с заказами
                </a>
                <a href="help_warehouse.php" class="sidebar-subitem-1c <?= basename($_SERVER['PHP_SELF']) == 'help_warehouse.php' ? 'active' : '' ?>">
                    🏭 Управление складом
                </a>
                <a href="help_reports.php" class="sidebar-subitem-1c <?= basename($_SERVER['PHP_SELF']) == 'help_reports.php' ? 'active' : '' ?>">
                    📈 Формирование отчетов
                </a>
                <a href="help_troubleshooting.php" class="sidebar-subitem-1c <?= basename($_SERVER['PHP_SELF']) == 'help_troubleshooting.php' ? 'active' : '' ?>">
                    🔧 Решение проблем
                </a>
            </div>
        </div>

        <!-- 🔹 АДМИНИСТРИРОВАНИЕ (только для админов) -->
       <?php 
$user_role = $_SESSION['user_role'] ?? 'user';
if ($user_role === 'admin'): 
?>
        <div class="accordion-1c">
            <div class="accordion-header-1c" data-accordion="admin">
                <span class="sidebar-icon-1c">🔐</span>
                <span class="menu-text">Администрирование</span>
                <span class="accordion-icon-1c">▼</span>
            </div>
            <div class="accordion-content-1c" id="admin-menu">
                <a href="admin.php" class="sidebar-subitem-1c <?= basename($_SERVER['PHP_SELF']) == 'admin.php' ? 'active' : '' ?>">
                    ⚙️ Настройки системы
                </a>
                    <!-- ОБНОВЛЕННЫЙ РАЗДЕЛ ЗАРПЛАТ -->
                <a href="salaries.php" class="sidebar-subitem-1c <?= basename($_SERVER['PHP_SELF']) == 'salaries.php' ? 'active' : '' ?>">
                    💰 Зарплаты
                </a>
                <a href="salary_calculate.php" class="sidebar-subitem-1c <?= basename($_SERVER['PHP_SELF']) == 'salary_calculate.php' ? 'active' : '' ?>">
                    🧮 Расчет ЗП
                </a>
				<a href="salary_report.php" class="sidebar-subitem-1c <?= basename($_SERVER['PHP_SELF']) == 'salary_reports.php' ? 'active' : '' ?>">
                    📊 Ведомость по ЗП
                </a>
                <a href="salary_reports.php" class="sidebar-subitem-1c <?= basename($_SERVER['PHP_SELF']) == 'salary_reports.php' ? 'active' : '' ?>">
                    📊 Отчеты по ЗП
                </a>
				<a href="system_logs.php" class="sidebar-subitem-1c <?= basename($_SERVER['PHP_SELF']) == 'system_logs.php' ? 'active' : '' ?>">
                    📊 Логи системы
                </a>
                <a href="backup.php" class="sidebar-subitem-1c <?= basename($_SERVER['PHP_SELF']) == 'backup.php' ? 'active' : '' ?>">
                    💾 Резервное копирование
                </a>
            </div>
        </div>
        <?php endif; ?>
    </nav>
    
    <!-- Статистика -->
    <div class="sidebar-stats-1c">
        <h6>📊 Статистика</h6>
        <div class="stat-item-1c">
            <small>Активные заказы:</small>
            <strong>12</strong>
        </div>
        <div class="stat-item-1c">
            <small>Клиенты:</small>
            <strong>156</strong>
        </div>
        <div class="stat-item-1c">
            <small>Выполнено сегодня:</small>
            <strong>8</strong>
        </div>
    </div>

    <!-- 🔹 СТАТУС СИСТЕМЫ И ПОЛЬЗОВАТЕЛЬ -->
    <div class="sidebar-footer-1c">
        <!-- Статус системы -->
        <div class="system-status-card">
            <div class="status-header">
                <div class="status-indicator online" title="Система активна"></div>
                <span class="status-text">Система онлайн</span>
            </div>
            <div class="status-details">
                <div class="status-item">
                    <span class="status-label">База данных:</span>
                    <span class="status-value success">✓ Активна</span>
                </div>
                <div class="status-item">
                    <span class="status-label">Память:</span>
                    <span class="status-value">64%</span>
                </div>
                <div class="status-item">
                    <span class="status-label">Время работы:</span>
                    <span class="status-value">12д 4ч</span>
                </div>
            </div>
        </div>

        <!-- Информация о пользователе -->
        <div class="user-card">
            <div class="user-avatar-large">
                <?= substr($_SESSION['full_name'] ?? 'U', 0, 1) ?>
            </div>
            <div class="user-details">
                <div class="user-name"><?= htmlspecialchars($_SESSION['full_name'] ?? 'Пользователь') ?></div>
                <div class="user-role-badge <?= ($_SESSION['user_role'] ?? 'user') === 'admin' ? 'admin' : 'user' ?>">
                    <?= ($_SESSION['user_role'] ?? 'user') === 'admin' ? '👑 Администратор' : '👤 Пользователь' ?>
                </div>
                <div class="user-stats">
                    <span class="stat">📊 24 заказа</span>
                    <span class="stat">⭐ 4.8</span>
                </div>
            </div>
        </div>

        <!-- Технология -->
        <div class="tech-card">
            <div class="tech-header">
                <span class="tech-icon">🚀</span>
                <span class="tech-title">Технология</span>
            </div>
            <a href="https://www.deepseek.com" target="_blank" class="tech-link">
                <span class="tech-name">DeepSeek R1</span>
                <span class="tech-version">v2.0</span>
            </a>
            <div class="tech-stats">
                <span class="tech-stat">⚡ Быстро</span>
                <span class="tech-stat">🔒 Надежно</span>
            </div>
        </div>
    </div>
</div>

<script>
// Активация пункта "Осмотр авто" когда открыта страница осмотра
document.addEventListener('DOMContentLoaded', function() {
    const currentPage = '<?= basename($_SERVER['PHP_SELF']) ?>';
    
    // Показываем пункт "Осмотр авто" если открыта страница осмотра
    if (currentPage === 'inspection.php') {
        const inspectionLink = document.querySelector('a[href="inspection.php"]');
        if (inspectionLink) {
            inspectionLink.style.display = 'block';
        }
    }
    
    // Автоматически раскрываем аккордеон Заказы если открыта связанная страница
    const orderPages = ['orders.php', 'create_order.php', 'inspection.php', 'order_edit.php'];
    if (orderPages.includes(currentPage)) {
        const ordersAccordion = document.querySelector('[data-accordion="orders"]');
        if (ordersAccordion) {
            ordersAccordion.click();
        }
    }
    
    // Автоматически раскрываем аккордеон Помощь если открыта страница помощи
    const helpPages = ['help.php', 'help_quickstart.php', 'help_orders.php', 'help_warehouse.php', 'help_reports.php', 'help_troubleshooting.php'];
    if (helpPages.includes(currentPage)) {
        const helpAccordion = document.querySelector('[data-accordion="help"]');
        if (helpAccordion) {
            helpAccordion.click();
        }
    }

    // Автоматически раскрываем аккордеон Зарплаты если открыта связанная страница
    const salaryPages = ['salaries.php', 'salary_calculate.php', 'salary_reports.php', 'employee_edit.php', 'staff_management.php'];
    if (salaryPages.includes(currentPage)) {
        const adminAccordion = document.querySelector('[data-accordion="admin"]');
        if (adminAccordion) {
            adminAccordion.click();
        }
    }
});
</script>