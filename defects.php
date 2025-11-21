<?php
require 'includes/db.php';
session_start();
define('ACCESS', true);

// Получаем список дефектных ведомостей
$status_filter = $_GET['status'] ?? '';
$where = '';
if ($status_filter && in_array($status_filter, ['draft', 'approved', 'rejected'])) {
    $where = "WHERE d.status = '$status_filter'";
}

$defects = $pdo->query("
    SELECT d.*, 
           c.name as client_name, 
           car.model as car_model,
           car.license_plate as car_plate,
           e.name as master_name,
           (SELECT COUNT(*) FROM defect_items di WHERE di.defect_id = d.id) as items_count
    FROM defects d 
    LEFT JOIN clients c ON d.client_id = c.id 
    LEFT JOIN cars car ON d.car_id = car.id 
    LEFT JOIN employees e ON d.master_id = e.id 
    $where
    ORDER BY d.created_at DESC
")->fetchAll();

include 'templates/header.php';
?>

    <div class="content-container">
        <!-- Заголовок -->
        <div class="header-compact">
            <h1 class="page-title-compact">📋 ДЕФЕКТНЫЕ ВЕДОМОСТИ</h1>
            <div class="header-actions-compact">
                <a href="create_order.php" class="action-btn-compact primary">
                    <span class="action-icon">➕</span>
                    <span class="action-label">Новый заказ</span>
                </a>
                <a href="defect_create.php" class="action-btn-compact">
                    <span class="action-icon">📝</span>
                    <span class="action-label">Новая ведомость</span>
                </a>
            </div>
        </div>

        <!-- Фильтры -->
        <div class="card-1c" style="margin-bottom: 1.5rem;">
            <div class="card-header-1c">
                <h5>🔍 ФИЛЬТРЫ</h5>
            </div>
            <div style="padding: 1rem 1.5rem;">
                <div class="filter-tabs">
                    <a href="defects.php" class="filter-tab <?= !$status_filter ? 'active' : '' ?>">
                        Все (<?= count($defects) ?>)
                    </a>
                    <a href="defects.php?status=draft" class="filter-tab <?= $status_filter === 'draft' ? 'active' : '' ?>">
                        📝 Черновики
                    </a>
                    <a href="defects.php?status=approved" class="filter-tab <?= $status_filter === 'approved' ? 'active' : '' ?>">
                        ✅ Утвержденные
                    </a>
                    <a href="defects.php?status=rejected" class="filter-tab <?= $status_filter === 'rejected' ? 'active' : '' ?>">
                        ❌ Отклоненные
                    </a>
                </div>
            </div>
        </div>

        <!-- Список ведомостей -->
        <div class="card-1c">
            <div class="card-header-1c">
                <h5>📄 СПИСОК ВЕДОМОСТЕЙ</h5>
            </div>
            <div class="orders-table-container">
                <?php if (empty($defects)): ?>
                <div style="text-align: center; padding: 3rem; color: #8b6914;">
                    <div style="font-size: 3rem; margin-bottom: 1rem;">📋</div>
                    <h3>Нет дефектных ведомостей</h3>
                    <p>Создайте первую дефектную ведомость</p>
                    <a href="create_order.php" class="btn-1c primary" style="margin-top: 1rem;">
                        ➕ Создать заказ с ведомостью
                    </a>
                </div>
                <?php else: ?>
                <table class="orders-table-enhanced">
                    <thead>
                        <tr>
                            <th class="col-id">№ Ведомости</th>
                            <th class="col-client">Клиент</th>
                            <th class="col-car">Автомобиль</th>
                            <th class="col-master">Мастер</th>
                            <th class="col-status">Статус</th>
                            <th class="col-amount">Сумма</th>
                            <th class="col-items">Позиций</th>
                            <th class="col-date">Дата</th>
                            <th class="col-actions">Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($defects as $defect): ?>
                        <tr class="order-row">
                            <td class="order-id">
                                <a href="defect_view.php?id=<?= $defect['id'] ?>" class="order-link">
                                    <?= htmlspecialchars($defect['defect_number'] ?? 'DEF-'.$defect['id']) ?>
                                </a>
                                <?php if ($defect['order_id']): ?>
                                <div class="order-ref" style="font-size: 0.8rem; color: #8b6914;">
                                    Заказ #<?= $defect['order_id'] ?>
                                </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="client-name"><?= htmlspecialchars($defect['client_name']) ?></div>
                            </td>
                            <td>
                                <div class="car-main"><?= htmlspecialchars($defect['car_model']) ?></div>
                                <?php if (!empty($defect['car_plate'])): ?>
                                <div class="car-vin"><?= htmlspecialchars($defect['car_plate']) ?></div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="master-name"><?= htmlspecialchars($defect['master_name'] ?? 'Не назначен') ?></div>
                            </td>
                            <td>
                                <span class="status-badge-enhanced <?= $defect['status'] ?>">
                                    <?= $defect['status'] === 'draft' ? '📝 Черновик' : 
                                       ($defect['status'] === 'approved' ? '✅ Утверждено' : '❌ Отклонено') ?>
                                </span>
                            </td>
                            <td class="order-amount">
                                <div class="amount-main"><?= number_format($defect['grand_total'], 2, ',', ' ') ?> ₽</div>
                            </td>
                            <td style="text-align: center;">
                                <span class="items-count"><?= $defect['items_count'] ?></span>
                            </td>
                            <td>
                                <div class="date-main"><?= date('d.m.Y', strtotime($defect['created_at'])) ?></div>
                                <div class="date-time"><?= date('H:i', strtotime($defect['created_at'])) ?></div>
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
                    </tbody>
                </table>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include 'templates/footer.php'; ?>