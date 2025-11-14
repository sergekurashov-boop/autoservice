<?php
define('ACCESS', true);

require_once 'includes/db.php';
require_once 'includes/functions.php';
require_once 'auth_check.php';

$page_title = "Заказы шиномонтажа";
include 'templates/header.php';
?>

<div class="main-content">
    <div class="container">
        <div class="page-header">
            <h1>1F6DE  Заказы шиномонтажа</h1>
            <div class="header-actions">
                <a href="tire_create.php" class="btn btn-primary">➕ Новый заказ</a>
            </div>
        </div>

        <!-- Переключатель вида -->
        <div class="view-switcher" style="margin-bottom: 20px; padding: 10px; background: #f0f0f0; border: 1px solid #ccc;">
            <span style="margin-right: 10px; font-weight: bold;">Вид:</span>
            <button type="button" class="btn-view active" data-view="table">📊 Таблица</button>
            <button type="button" class="btn-view" data-view="cards">🃏 Карточки</button>
        </div>

        <!-- Вид таблицы -->
        <div class="table-view">
            <div class="table-container">
                <table class="data-table" style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="background: #e0e0e0;">
                            <th style="padding: 8px; border: 1px solid #999; text-align: left;">ID</th>
                            <th style="padding: 8px; border: 1px solid #999; text-align: left;">Клиент</th>
                            <th style="padding: 8px; border: 1px solid #999; text-align: left;">Автомобиль</th>
                            <th style="padding: 8px; border: 1px solid #999; text-align: left;">Статус</th>
                            <th style="padding: 8px; border: 1px solid #999; text-align: left;">Дата</th>
                            <th style="padding: 8px; border: 1px solid #999; text-align: left;">Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        try {
                            $sql = "SELECT t.*, c.name as client_name, c.phone,
                                           car.make, car.model, car.year, car.license_plate
                                    FROM tire_orders t
                                    LEFT JOIN clients c ON t.client_id = c.id
                                    LEFT JOIN cars car ON t.car_id = car.id
                                    ORDER BY t.created_at DESC";
                            
                            $stmt = $pdo->query($sql);
                            $orders = $stmt->fetchAll();
                            
                            if (count($orders) > 0) {
                                foreach ($orders as $order) {
                                    $status_icons = [
                                        'draft' => '📝',
                                        'active' => '🔧', 
                                        'completed' => '✅',
                                        'cancelled' => '❌'
                                    ];
                                    $status_icon = $status_icons[$order['status']] ?? '📄';
                                    ?>
                                    <tr style="background: white;">
                                        <td style="padding: 8px; border: 1px solid #ccc;">#<?= $order['id'] ?></td>
                                        <td style="padding: 8px; border: 1px solid #ccc;">
                                            <strong><?= htmlspecialchars($order['client_name']) ?></strong><br>
                                            <span style="color: #666; font-size: 12px;"><?= $order['phone'] ?? '' ?></span>
                                        </td>
                                        <td style="padding: 8px; border: 1px solid #ccc;">
                                            <?= htmlspecialchars($order['make']) ?> <?= htmlspecialchars($order['model']) ?>
                                            <?php if (!empty($order['year'])): ?>
                                                (<?= $order['year'] ?>)
                                            <?php endif; ?>
                                            <br>
                                            <span style="color: #666; font-size: 12px;"><?= $order['license_plate'] ?></span>
                                        </td>
                                        <td style="padding: 8px; border: 1px solid #ccc;">
                                            <?= $status_icon ?> <?= $order['status'] ?>
                                        </td>
                                        <td style="padding: 8px; border: 1px solid #ccc;">
                                            <?= date('d.m.Y H:i', strtotime($order['created_at'])) ?>
                                        </td>
                                        <td style="padding: 8px; border: 1px solid #ccc;">
                                            <a href="tire_edit.php?id=<?= $order['id'] ?>" class="btn-action" title="Редактировать">✏️</a>
                                            <a href="tire_print_pdf.php?id=<?= $order['id'] ?>" class="btn-action" title="Печать"target="_blank">🖨️</a>
                                        </td>
                                    </tr>
                                    <?php
                                }
                            } else {
                                ?>
                                <tr>
                                    <td colspan="6" style="padding: 40px; text-align: center; border: 1px solid #ccc; background: white;">
                                        📭<br>
                                        <strong>Заказы не найдены</strong><br>
                                        <span style="color: #666;">Создайте первый заказ шиномонтажа</span>
                                    </td>
                                </tr>
                                <?php
                            }
                        } catch (PDOException $e) {
                            ?>
                            <tr>
                                <td colspan="6" style="padding: 20px; text-align: center; border: 1px solid #ccc; background: white; color: red;">
                                    ⚠️<br>
                                    <strong>Ошибка загрузки данных</strong><br>
                                    <?= $e->getMessage() ?>
                                </td>
                            </tr>
                            <?php
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Вид карточек -->
        <div class="card-view" style="display: none;">
            <div class="cards-container">
                <?php
                if (isset($orders) && count($orders) > 0) {
                    foreach ($orders as $order) {
                        $status_icons = [
                            'draft' => '📝 Черновик',
                            'active' => '🔧 В работе', 
                            'completed' => '✅ Выполнен',
                            'cancelled' => '❌ Отменен'
                        ];
                        $status_display = $status_icons[$order['status']] ?? '📄 ' . $order['status'];
                        ?>
                        <div class="order-card" style="border: 1px solid #ccc; background: white; margin-bottom: 15px; padding: 15px;">
                            <div class="card-header" style="border-bottom: 1px solid #eee; padding-bottom: 10px; margin-bottom: 10px;">
                                <div style="display: flex; justify-content: between; align-items: center;">
                                    <strong style="font-size: 16px;">🛞 Заказ #<?= $order['id'] ?></strong>
                                    <span style="background: #f0f0f0; padding: 4px 8px; border: 1px solid #ccc; font-size: 12px;">
                                        <?= $status_display ?>
                                    </span>
                                </div>
                            </div>
                            
                            <div class="card-body">
                                <div style="margin-bottom: 10px;">
                                    <div style="font-weight: bold; color: #666; font-size: 12px;">👤 КЛИЕНТ</div>
                                    <div style="font-size: 14px;">
                                        <strong><?= htmlspecialchars($order['client_name']) ?></strong>
                                        <?php if (!empty($order['phone'])): ?>
                                            <br><span style="color: #666;">📞 <?= $order['phone'] ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <div style="margin-bottom: 10px;">
                                    <div style="font-weight: bold; color: #666; font-size: 12px;">🚗 АВТОМОБИЛЬ</div>
                                    <div style="font-size: 14px;">
                                        <?= htmlspecialchars($order['make']) ?> <?= htmlspecialchars($order['model']) ?>
                                        <?php if (!empty($order['year'])): ?>
                                            (<?= $order['year'] ?>)
                                        <?php endif; ?>
                                        <br>
                                        <span style="color: #666;">🔢 <?= $order['license_plate'] ?></span>
                                    </div>
                                </div>
                                
                                <div style="margin-bottom: 10px;">
                                    <div style="font-weight: bold; color: #666; font-size: 12px;">📅 ДАТА СОЗДАНИЯ</div>
                                    <div style="font-size: 14px;"><?= date('d.m.Y H:i', strtotime($order['created_at'])) ?></div>
                                </div>
                            </div>
                            
                            <div class="card-footer" style="border-top: 1px solid #eee; padding-top: 10px; margin-top: 10px;">
                                <div style="display: flex; gap: 10px;">
                                    <a href="tire_edit.php?id=<?= $order['id'] ?>" class="btn" style="flex: 1; text-align: center; padding: 8px; background: #e0e0e0; border: 1px solid #ccc; text-decoration: none; color: black;">
                                        ✏️ Редактировать
                                    </a>
                                    <a href="tire_print.php?id=<?= $order['id'] ?>" class="btn" style="flex: 1; text-align: center; padding: 8px; background: #e0e0e0; border: 1px solid #ccc; text-decoration: none; color: black;">
                                        🖨️ Печать
                                    </a>
                                </div>
                            </div>
                        </div>
                        <?php
                    }
                } else {
                    ?>
                    <div style="text-align: center; padding: 40px; background: white; border: 1px solid #ccc;">
                        📭<br>
                        <strong style="font-size: 18px; display: block; margin: 10px 0;">Заказы не найдены</strong>
                        <span style="color: #666; display: block; margin-bottom: 20px;">Создайте первый заказ шиномонтажа</span>
                        <a href="tire_create.php" class="btn" style="padding: 10px 20px; background: #4CAF50; color: white; text-decoration: none; border: none;">
                            ➕ Новый заказ
                        </a>
                    </div>
                    <?php
                }
                ?>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Переключение между таблицей и карточками
    const viewButtons = document.querySelectorAll('.btn-view');
    const tableView = document.querySelector('.table-view');
    const cardView = document.querySelector('.card-view');
    
    viewButtons.forEach(button => {
        button.addEventListener('click', function() {
            // Убираем активный класс у всех кнопок
            viewButtons.forEach(btn => btn.classList.remove('active'));
            // Добавляем активный класс текущей кнопке
            this.classList.add('active');
            
            // Показываем соответствующий вид
            const viewType = this.getAttribute('data-view');
            if (viewType === 'table') {
                tableView.style.display = 'block';
                cardView.style.display = 'none';
            } else {
                tableView.style.display = 'none';
                cardView.style.display = 'block';
            }
        });
    });
});
</script>

<style>
.btn-view {
    padding: 5px 15px;
    background: white;
    border: 1px solid #ccc;
    cursor: pointer;
    margin-right: 5px;
}
.btn-view.active {
    background: #0078d7;
    color: white;
    border-color: #0078d7;
}
.btn-action {
    margin: 0 2px;
    text-decoration: none;
    padding: 2px 5px;
    background: #f0f0f0;
    border: 1px solid #ccc;
}
.btn-action:hover {
    background: #e0e0e0;
}
.order-card {
    box-shadow: 2px 2px 5px rgba(0,0,0,0.1);
}
.order-card:hover {
    box-shadow: 2px 2px 8px rgba(0,0,0,0.2);
}
</style>

<?php include 'templates/footer.php'; ?>