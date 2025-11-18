<?php
require 'includes/db.php';
session_start();
define('ACCESS', true);
include 'templates/header.php';
?>

<div class="main-content-1c">
    <div class="content-container">
        <!-- Заголовок -->
        <div class="header-compact">
            <h1 class="page-title-compact">🎯 УПРАВЛЕНИЕ РЕМОНТАМИ</h1>
        </div>

        <!-- Статистика -->
        <div class="row-1c">
            <div class="stat-card">
                <div class="stat-icon">📋</div>
                <div class="stat-content">
                    <h3>12</h3>
                    <p>Дефектных ведомостей</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">🔧</div>
                <div class="stat-content">
                    <h3>8</h3>
                    <p>Заданий в ремзону</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">⏱️</div>
                <div class="stat-content">
                    <h3>3</h3>
                    <p>В работе</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">✅</div>
                <div class="stat-content">
                    <h3>5</h3>
                    <p>Завершено</p>
                </div>
            </div>
        </div>

        <!-- Быстрые действия -->
        <div class="row-1c">
            <!-- Дефектные ведомости -->
            <div class="card-1c">
                <div class="card-header-1c">
                    <h5>📋 ДЕФЕКТНЫЕ ВЕДОМОСТИ</h5>
                </div>
                <div class="quick-actions-grid">
                    <a href="defects.php" class="quick-action">
                        <span class="action-icon">📄</span>
                        <span class="action-text">Все ведомости</span>
                    </a>
                    <a href="defect_create.php" class="quick-action">
                        <span class="action-icon">➕</span>
                        <span class="action-text">Новая ведомость</span>
                    </a>
                    <a href="defects.php?status=draft" class="quick-action">
                        <span class="action-icon">📝</span>
                        <span class="action-text">Черновики</span>
                    </a>
                    <a href="defects.php?status=approved" class="quick-action">
                        <span class="action-icon">✅</span>
                        <span class="action-text">Согласованные</span>
                    </a>
                </div>
            </div>

            <!-- Задания в ремзону -->
            <div class="card-1c">
                <div class="card-header-1c">
                    <h5>🔧 ЗАДАНИЯ В РЕМЗОНУ</h5>
                </div>
                <div class="quick-actions-grid">
                    <a href="repair_tasks.php" class="quick-action">
                        <span class="action-icon">📋</span>
                        <span class="action-text">Все задания</span>
                    </a>
                    <a href="repair_tasks.php?status=assigned" class="quick-action">
                        <span class="action-icon">🆕</span>
                        <span class="action-text">Новые</span>
                    </a>
                    <a href="repair_tasks.php?status=in_progress" class="quick-action">
                        <span class="action-icon">⚡</span>
                        <span class="action-text">В работе</span>
                    </a>
                    <a href="repair_tasks.php?status=completed" class="quick-action">
                        <span class="action-icon">✅</span>
                        <span class="action-text">Завершенные</span>
                    </a>
                </div>
            </div>
        </div>

        <!-- Последние ведомости -->
        <div class="card-1c">
            <div class="card-header-1c">
                <h5>🕐 ПОСЛЕДНИЕ ДЕФЕКТНЫЕ ВЕДОМОСТИ</h5>
            </div>
            <div class="orders-table-container">
                <table class="orders-table-enhanced">
                    <thead>
                        <tr>
                            <th class="col-id">№</th>
                            <th class="col-client">Клиент</th>
                            <th class="col-car">Автомобиль</th>
                            <th class="col-status">Статус</th>
                            <th class="col-amount">Сумма</th>
                            <th class="col-actions">Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $recent_defects = $pdo->query("
                            SELECT d.*, c.name as client_name, car.model as car_model 
                            FROM defects d 
                            LEFT JOIN clients c ON d.client_id = c.id 
                            LEFT JOIN cars car ON d.car_id = car.id 
                            ORDER BY d.created_at DESC LIMIT 5
                        ")->fetchAll();
                        
                        foreach ($recent_defects as $defect): 
                        ?>
                        <tr class="order-row">
                            <td class="order-id">
                                <a href="defect_view.php?id=<?= $defect['id'] ?>" class="order-link">
                                    <?= htmlspecialchars($defect['defect_number'] ?? 'DEF-'.$defect['id']) ?>
                                </a>
                            </td>
                            <td>
                                <div class="client-name"><?= htmlspecialchars($defect['client_name']) ?></div>
                            </td>
                            <td>
                                <div class="car-main"><?= htmlspecialchars($defect['car_model']) ?></div>
                            </td>
                            <td>
                                <span class="status-badge-enhanced <?= $defect['status'] ?>">
                                    <?= $defect['status'] === 'draft' ? 'Черновик' : 
                                       ($defect['status'] === 'approved' ? 'Утверждено' : 'Отклонено') ?>
                                </span>
                            </td>
                            <td class="order-amount">
                                <div class="amount-main"><?= number_format($defect['grand_total'], 2, ',', ' ') ?></div>
                            </td>
                            <td class="order-actions">
                                <div class="action-buttons">
                                    <a href="defect_view.php?id=<?= $defect['id'] ?>" class="action-btn view" title="Просмотр">👁️</a>
                                    <a href="defect_edit.php?id=<?= $defect['id'] ?>" class="action-btn edit" title="Редактировать">✏️</a>
                                    <a href="repair_task_create.php?defect_id=<?= $defect['id'] ?>" class="action-btn print" title="Задание в ремзону">🔧</a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include 'templates/footer.php'; ?>