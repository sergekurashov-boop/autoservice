<?php
define('ACCESS', true);

require_once 'includes/db.php';
require_once 'includes/functions.php';
require_once 'auth_check.php';

$page_title = "Статистика шиномонтажа";
include 'templates/header.php';

// Получаем статистику
try {
    // Общая статистика
    $total_orders = $pdo->query("SELECT COUNT(*) as count FROM tire_orders")->fetch()['count'];
    $completed_orders = $pdo->query("SELECT COUNT(*) as count FROM tire_orders WHERE status = 'completed'")->fetch()['count'];
    $active_orders = $pdo->query("SELECT COUNT(*) as count FROM tire_orders WHERE status = 'active'")->fetch()['count'];
    
    // Статистика по услугам
    $services_stats = $pdo->query("
        SELECT services, COUNT(*) as count 
        FROM tire_orders 
        WHERE services IS NOT NULL AND services != ''
        GROUP BY services
        ORDER BY count DESC
    ")->fetchAll();
    
    // Статистика по месяцам
    $monthly_stats = $pdo->query("
        SELECT 
            DATE_FORMAT(created_at, '%Y-%m') as month,
            COUNT(*) as order_count,
            SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_count
        FROM tire_orders 
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
        GROUP BY DATE_FORMAT(created_at, '%Y-%m')
        ORDER BY month DESC
    ")->fetchAll();
    
    // Популярные размеры шин
    $tire_sizes = $pdo->query("
        SELECT 
            tire_data->>'$.fl_size' as size,
            COUNT(*) as count
        FROM tire_orders 
        WHERE tire_data->>'$.fl_size' IS NOT NULL AND tire_data->>'$.fl_size' != ''
        GROUP BY tire_data->>'$.fl_size'
        ORDER BY count DESC
        LIMIT 10
    ")->fetchAll();

} catch (PDOException $e) {
    $error = "Ошибка загрузки статистики: " . $e->getMessage();
}
?>

<div class="main-content">
    <div class="container">
        <div class="page-header">
            <h1>📊 Статистика шиномонтажа</h1>
            <div class="header-actions">
                <a href="tire_orders.php" class="btn btn-secondary">📋 К заказам</a>
                <a href="tire_create.php" class="btn btn-primary">➕ Новый заказ</a>
            </div>
        </div>

        <?php if (isset($error)): ?>
            <div style="background: #ffebee; color: #c62828; padding: 15px; border: 1px solid #ffcdd2; margin-bottom: 20px;">
                <?= $error ?>
            </div>
        <?php endif; ?>

        <!-- Основная статистика -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 30px;">
            <div style="background: white; padding: 20px; border: 1px solid #ccc; text-align: center;">
                <div style="font-size: 2em; font-weight: bold; color: #0078d7;"><?= $total_orders ?></div>
                <div style="color: #666;">📋 Всего заказов</div>
            </div>
            <div style="background: white; padding: 20px; border: 1px solid #ccc; text-align: center;">
                <div style="font-size: 2em; font-weight: bold; color: #28a745;"><?= $completed_orders ?></div>
                <div style="color: #666;">✅ Выполнено</div>
            </div>
            <div style="background: white; padding: 20px; border: 1px solid #ccc; text-align: center;">
                <div style="font-size: 2em; font-weight: bold; color: #ffc107;"><?= $active_orders ?></div>
                <div style="color: #666;">🔧 В работе</div>
            </div>
            <div style="background: white; padding: 20px; border: 1px solid #ccc; text-align: center;">
                <div style="font-size: 2em; font-weight: bold; color: #6f42c1;">
                    <?= $total_orders > 0 ? round(($completed_orders / $total_orders) * 100, 1) : 0 ?>%
                </div>
                <div style="color: #666;">📈 Эффективность</div>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
            <!-- Статистика по услугам -->
            <div style="background: white; padding: 20px; border: 1px solid #ccc;">
                <h3 style="margin-top: 0; border-bottom: 1px solid #eee; padding-bottom: 10px;">🔧 Популярные услуги</h3>
                <?php if (!empty($services_stats)): ?>
                    <div style="max-height: 300px; overflow-y: auto;">
                        <?php foreach ($services_stats as $stat): ?>
                            <?php
                            $services = explode(',', $stat['services']);
                            $service_names = [
                                'mounting' => 'Монтаж/демонтаж',
                                'balancing' => 'Балансировка',
                                'alignment' => 'Развал-схождение',
                                'repair' => 'Ремонт шин',
                                'seasonal' => 'Сезонная замена'
                            ];
                            ?>
                            <div style="display: flex; justify-content: between; align-items: center; padding: 8px 0; border-bottom: 1px solid #f0f0f0;">
                                <div>
                                    <?php foreach ($services as $service): ?>
                                        <span style="background: #e9ecef; padding: 2px 6px; border-radius: 3px; font-size: 12px; margin-right: 5px;">
                                            <?= $service_names[$service] ?? $service ?>
                                        </span>
                                    <?php endforeach; ?>
                                </div>
                                <div style="font-weight: bold; color: #0078d7;"><?= $stat['count'] ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div style="text-align: center; color: #666; padding: 20px;">
                        📭 Нет данных по услугам
                    </div>
                <?php endif; ?>
            </div>

            <!-- Популярные размеры шин -->
            <div style="background: white; padding: 20px; border: 1px solid #ccc;">
                <h3 style="margin-top: 0; border-bottom: 1px solid #eee; padding-bottom: 10px;">🛞 Популярные размеры шин</h3>
                <?php if (!empty($tire_sizes)): ?>
                    <div style="max-height: 300px; overflow-y: auto;">
                        <?php foreach ($tire_sizes as $size): ?>
                            <?php if (!empty($size['size'])): ?>
                                <div style="display: flex; justify-content: between; align-items: center; padding: 8px 0; border-bottom: 1px solid #f0f0f0;">
                                    <div style="font-weight: 500;"><?= htmlspecialchars($size['size']) ?></div>
                                    <div style="background: #0078d7; color: white; padding: 2px 8px; border-radius: 10px; font-size: 12px;">
                                        <?= $size['count'] ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div style="text-align: center; color: #666; padding: 20px;">
                        📭 Нет данных по размерам шин
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Статистика по месяцам -->
        <div style="background: white; padding: 20px; border: 1px solid #ccc; margin-top: 20px;">
            <h3 style="margin-top: 0; border-bottom: 1px solid #eee; padding-bottom: 10px;">📅 Статистика по месяцам</h3>
            <?php if (!empty($monthly_stats)): ?>
                <div style="overflow-x: auto;">
                    <table style="width: 100%; border-collapse: collapse;">
                        <thead>
                            <tr style="background: #f8f9fa;">
                                <th style="padding: 10px; border: 1px solid #ddd; text-align: left;">Месяц</th>
                                <th style="padding: 10px; border: 1px solid #ddd; text-align: center;">Всего заказов</th>
                                <th style="padding: 10px; border: 1px solid #ddd; text-align: center;">Выполнено</th>
                                <th style="padding: 10px; border: 1px solid #ddd; text-align: center;">Эффективность</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($monthly_stats as $stat): ?>
                                <tr>
                                    <td style="padding: 10px; border: 1px solid #ddd;">
                                        <?= date('F Y', strtotime($stat['month'] . '-01')) ?>
                                    </td>
                                    <td style="padding: 10px; border: 1px solid #ddd; text-align: center; font-weight: bold;">
                                        <?= $stat['order_count'] ?>
                                    </td>
                                    <td style="padding: 10px; border: 1px solid #ddd; text-align: center; color: #28a745;">
                                        <?= $stat['completed_count'] ?>
                                    </td>
                                    <td style="padding: 10px; border: 1px solid #ddd; text-align: center;">
                                        <?php 
                                        $efficiency = $stat['order_count'] > 0 ? round(($stat['completed_count'] / $stat['order_count']) * 100, 1) : 0;
                                        $color = $efficiency >= 80 ? '#28a745' : ($efficiency >= 60 ? '#ffc107' : '#dc3545');
                                        ?>
                                        <span style="color: <?= $color ?>; font-weight: bold;">
                                            <?= $efficiency ?>%
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div style="text-align: center; color: #666; padding: 20px;">
                    📭 Нет данных за последние месяцы
                </div>
            <?php endif; ?>
        </div>

        <!-- Быстрые действия -->
        <div style="background: #f8f9fa; padding: 20px; border: 1px solid #ccc; margin-top: 20px;">
            <h3 style="margin-top: 0;">⚡ Быстрые действия</h3>
            <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                <a href="tire_create.php" class="btn" style="padding: 10px 15px; background: #0078d7; color: white; text-decoration: none;">
                    ➕ Создать заказ
                </a>
                <a href="tire_orders.php?status=active" class="btn" style="padding: 10px 15px; background: #ffc107; color: black; text-decoration: none;">
                    🔧 Активные заказы
                </a>
                <a href="tire_orders.php?status=completed" class="btn" style="padding: 10px 15px; background: #28a745; color: white; text-decoration: none;">
                    ✅ Выполненные заказы
                </a>
                <button onclick="window.print()" class="btn" style="padding: 10px 15px; background: #6f42c1; color: white; border: none; cursor: pointer;">
                    🖨️ Печать отчета
                </button>
            </div>
        </div>
    </div>
</div>

<style>
@media print {
    .header-actions, .btn { display: none !important; }
}
</style>

<?php include 'templates/footer.php'; ?>