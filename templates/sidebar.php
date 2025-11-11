<!-- Сайдбар в стиле 1С -->
<div class="sidebar-1c" id="mainSidebar">
    <button class="sidebar-toggle-1c" id="sidebarToggle">
        ‹
    </button>
    
    <div class="sidebar-header-1c">
        <h5>🛠️ <span>AUTOSERVICE</span></h5>
        <div class="sidebar-subtitle">Управление автосервисом</div>
    </div>
    
    <nav class="sidebar-nav-1c">
        <!-- Главное меню -->
        <a href="/autoservice/index.php" class="sidebar-item-1c <?= basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : '' ?>">
            <span class="sidebar-icon-1c">📊</span>
            <span class="menu-text">Главная</span>
        </a>
        
        <!-- Аккордеон Управление -->
        <div class="accordion-1c">
            <div class="accordion-header-1c" data-accordion="management">
                <span class="sidebar-icon-1c">⚙️</span>
                <span class="menu-text">Управление</span>
                <span class="accordion-icon-1c">▼</span>
            </div>
            <div class="accordion-content-1c" id="management-menu">
                <a href="/autoservice/orders.php" class="sidebar-subitem-1c <?= basename($_SERVER['PHP_SELF']) == 'orders.php' ? 'active' : '' ?>">
                    📋 Заказы
                </a>
                <a href="/autoservice/clients.php" class="sidebar-subitem-1c <?= basename($_SERVER['PHP_SELF']) == 'clients.php' ? 'active' : '' ?>">
                    👥 Клиенты
                </a>
                <a href="/autoservice/cars.php" class="sidebar-subitem-1c <?= basename($_SERVER['PHP_SELF']) == 'cars.php' ? 'active' : '' ?>">
                    🚗 Транспорт
                </a>
                <a href="/autoservice/services.php" class="sidebar-subitem-1c <?= basename($_SERVER['PHP_SELF']) == 'services.php' ? 'active' : '' ?>">
                    🔧 Услуги
                </a>
                <a href="/autoservice/mechanics.php" class="sidebar-subitem-1c <?= basename($_SERVER['PHP_SELF']) == 'mechanics.php' ? 'active' : '' ?>">
                    👨‍🔧 Мастера
                </a>
                <a href="/autoservice/parts.php" class="sidebar-subitem-1c <?= basename($_SERVER['PHP_SELF']) == 'parts.php' ? 'active' : '' ?>">
                    🔩 Запчасти
                </a>
                <a href="/autoservice/tasks.php" class="sidebar-subitem-1c <?= basename($_SERVER['PHP_SELF']) == 'tasks.php' ? 'active' : '' ?>">
                    ⏰ Задачи
                </a>
            </div>
        </div>
        
        <!-- Одиночные пункты -->
        <a href="/autoservice/booking.php" class="sidebar-item-1c <?= basename($_SERVER['PHP_SELF']) == 'booking.php' ? 'active' : '' ?>">
            <span class="sidebar-icon-1c">📅</span>
            <span class="menu-text">Запись на обслуживание</span>
        </a>
        
        <a href="/autoservice/warehouse.php" class="sidebar-item-1c <?= basename($_SERVER['PHP_SELF']) == 'warehouse.php' ? 'active' : '' ?>">
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
                <a href="/autoservice/faq.php" class="sidebar-subitem-1c <?= basename($_SERVER['PHP_SELF']) == 'faq.php' ? 'active' : '' ?>">
                    ❓ Управление FAQ
                </a>
                <a href="/autoservice/admin_faq.php" class="sidebar-subitem-1c <?= basename($_SERVER['PHP_SELF']) == 'admin_faq.php' ? 'active' : '' ?>">
                    💬 FAQ
                </a>
                <a href="/autoservice/knowbase.php" class="sidebar-subitem-1c <?= basename($_SERVER['PHP_SELF']) == 'knowbase.php' ? 'active' : '' ?>">
                    📚 База знаний
                </a>
                <a href="https://www.carmans.net/" target="_blank" class="sidebar-subitem-1c">
                    📖 Мануалы
                </a>
            </div>
        </div>
        
        <!-- Отчеты -->
        <a href="/autoservice/reports.php" class="sidebar-item-1c <?= basename($_SERVER['PHP_SELF']) == 'reports.php' ? 'active' : '' ?>">
            <span class="sidebar-icon-1c">📈</span>
            <span class="menu-text">Отчеты</span>
        </a>

        <!-- 🔹 АДМИНИСТРИРОВАНИЕ (только для админов) -->
        <?php if ($_SESSION['user_role'] === 'admin'): ?>
        <div class="accordion-1c">
            <div class="accordion-header-1c" data-accordion="admin">
                <span class="sidebar-icon-1c">⚙️</span>
                <span class="menu-text">Администрирование</span>
                <span class="accordion-icon-1c">▼</span>
            </div>
            <div class="accordion-content-1c" id="admin-menu">
                <a href="/autoservice/admin/user_management.php" class="sidebar-subitem-1c <?= basename($_SERVER['PHP_SELF']) == 'user_management.php' ? 'active' : '' ?>">
                    👨‍💼 Управление пользователями
                </a>
                <a href="/autoservice/admin/backup.php" class="sidebar-subitem-1c <?= basename($_SERVER['PHP_SELF']) == 'backup.php' ? 'active' : '' ?>">
                    💾 Резервные копии
                </a>
                <a href="/autoservice/admin/logs.php" class="sidebar-subitem-1c <?= basename($_SERVER['PHP_SELF']) == 'logs.php' ? 'active' : '' ?>">
                    📊 Логи системы
                </a>
                <a href="/autoservice/admin/settings.php" class="sidebar-subitem-1c <?= basename($_SERVER['PHP_SELF']) == 'settings.php' ? 'active' : '' ?>">
                    ⚙️ Настройки системы
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