<?php
require 'includes/db.php';
session_start();
define('ACCESS', true);

// Получаем реальную статистику
$stats = [
    'total_defects' => $pdo->query("SELECT COUNT(*) FROM defects")->fetchColumn(),
    'total_tasks' => $pdo->query("SELECT COUNT(*) FROM repair_tasks")->fetchColumn(),
    'in_progress' => $pdo->query("SELECT COUNT(*) FROM repair_tasks WHERE status = 'in_progress'")->fetchColumn(),
    'completed' => $pdo->query("SELECT COUNT(*) FROM repair_tasks WHERE status = 'completed'")->fetchColumn()
];

// Получаем последние дефектные ведомости
$recent_defects = $pdo->query("
    SELECT d.*, o.id as order_id, c.name as client_name, 
           o.car_model, o.car_vin, o.problem_description,
           COALESCE(d.grand_total, 0) as total_amount
    FROM defects d 
    LEFT JOIN orders o ON d.order_id = o.id 
    LEFT JOIN clients c ON d.client_id = c.id 
    ORDER BY d.created_at DESC LIMIT 5
")->fetchAll();
?>

<?php include 'templates/header.php'; ?>

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
                    <h3><?= $stats['total_defects'] ?></h3>
                    <p>Дефектных ведомостей</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">🔧</div>
                <div class="stat-content">
                    <h3><?= $stats['total_tasks'] ?></h3>
                    <p>Заданий в ремзону</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">⏱️</div>
                <div class="stat-content">
                    <h3><?= $stats['in_progress'] ?></h3>
                    <p>В работе</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">✅</div>
                <div class="stat-content">
                    <h3><?= $stats['completed'] ?></h3>
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
                    <a href="create_order.php" class="quick-action">
                        <span class="action-icon">➕</span>
                        <span class="action-text">Новый заказ</span>
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
                    <a href="repair_task_create.php" class="quick-action">
                        <span class="action-icon">🆕</span>
                        <span class="action-text">Новое задание</span>
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
                        <?php if (empty($recent_defects)): ?>
                        <tr>
                            <td colspan="6" style="text-align: center; padding: 2rem; color: #666;">
                                📝 Нет дефектных ведомостей. <a href="create_order.php">Создайте первый заказ</a>
                            </td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($recent_defects as $defect): ?>
                            <tr class="order-row">
                                <td class="order-id">
                                    <a href="defect_view.php?id=<?= $defect['id'] ?>" class="order-link">
                                        <?= htmlspecialchars($defect['defect_number'] ?? 'DEF-'.$defect['id']) ?>
                                    </a>
                                </td>
                                <td>
                                    <div class="client-name"><?= htmlspecialchars($defect['client_name'] ?? 'Без клиента') ?></div>
                                </td>
                                <td>
                                    <div class="car-main"><?= htmlspecialchars($defect['car_model'] ?? 'Не указан') ?></div>
                                    <?php if (!empty($defect['car_vin'])): ?>
                                    <div class="car-vin">VIN: <?= htmlspecialchars($defect['car_vin']) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="status-badge-enhanced <?= $defect['status'] ?>">
                                        <?= $defect['status'] === 'draft' ? 'Черновик' : 
                                           ($defect['status'] === 'approved' ? 'Утверждено' : 
                                           ($defect['status'] === 'in_repair' ? 'В ремонте' : 'Новый')) ?>
                                    </span>
                                </td>
                                <td class="order-amount">
                                    <div class="amount-main"><?= number_format($defect['total_amount'], 2, ',', ' ') ?> ₽</div>
                                </td>
                                <td class="order-actions">
                                    <div class="action-buttons">
                                        <a href="defect_view.php?id=<?= $defect['id'] ?>" class="action-btn view" title="Просмотр">👁️</a>
                                        <a href="defect_edit.php?id=<?= $defect['id'] ?>" class="action-btn edit" title="Редактировать">✏️</a>
                                        <?php if ($defect['status'] === 'approved'): ?>
                                        <a href="repair_task_create.php?defect_id=<?= $defect['id'] ?>" class="action-btn print" title="Задание в ремзону">🔧</a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include 'templates/footer.php'; ?>