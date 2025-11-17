<?php
// navbar.php
?>
<!-- Навбар -->
<nav class="navbar top-navbar navbar-expand-lg sticky-top">
    <div class="container-fluid">
        <button class="btn btn-link d-lg-none" type="button" id="sidebarToggle">
            <i class="bi bi-list" style="color: var(--primary);"></i>
        </button>
        <img src="images/ck50negativ.jpg" width="40" height="40" alt="Логотип" class="rounded-circle">
        <div class="ms-auto d-flex align-items-center">
            <div class="input-group ms-3" style="max-width:600px;">
                <!-- Поиск по всем сущностям -->
                <form class="d-flex" action="search.php" method="get">
                    <input class="form-control me-2" type="search" name="q" placeholder="Поиск..." aria-label="Поиск">
                    <button class="btn btn-primary" type="submit">Найти</button>
                </form>
            </div>
            <!-- Блок пользователя -->
            <div class="dropdown ms-3">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="#" class="d-flex align-items-center text-decoration-none dropdown-toggle" 
                       id="userDropdown" data-bs-toggle="dropdown">
                        <div class="avatar-placeholder rounded-circle d-flex align-items-center justify-content-center me-2" 
                             style="width: 32px; height: 32px; background-color: var(--primary-light); color: var(--primary); font-weight: bold;">
                            <?= substr($_SESSION['full_name'] ?? 'U', 0, 1) ?>
                        </div>
                        <span style="color: var(--text-dark);">
                            <?= htmlspecialchars($_SESSION['full_name'] ?? 'Пользователь') ?>
                            <small class="d-block text-muted"><?= $_SESSION['user_role'] ?? 'Гость' ?></small>
                        </span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="profile.php">
                            <i class="bi bi-person-fill me-2"></i> Профиль</a></li>
                        <?php if ($_SESSION['user_role'] === 'admin'): ?>
                            <li><a class="dropdown-item" href="user_management.php">
                                <i class="bi bi-people-fill me-2"></i> Управление пользователями</a></li>
                        <?php endif; ?>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="logout.php">
                            <i class="bi bi-box-arrow-right me-2"></i> Выход</a></li>
                    </ul>
                <?php else: ?>
                    <a href="login.php" class="btn btn-outline-primary btn-sm">Войти</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>