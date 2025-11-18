<?php
require 'includes/db.php';
session_start();
define('ACCESS', true);


$defect_id = $_GET['id'] ?? 0;

// Получаем данные дефектной ведомости (исправленный запрос для employees)
$stmt = $pdo->prepare("
    SELECT d.*, 
           c.name as client_name, c.phone as client_phone,
           car.model as car_model, car.vin as car_vin, car.license_plate as car_plate, car.year as car_year,
           e.name as master_name  -- исправлено на таблицу employees
    FROM defects d
    LEFT JOIN clients c ON d.client_id = c.id
    LEFT JOIN cars car ON d.car_id = car.id
    LEFT JOIN employees e ON d.master_id = e.id  -- исправлено на employees
    WHERE d.id = ?
");
$stmt->execute([$defect_id]);
$defect = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$defect) {
    echo "<div class='main-content-1c'><div class='content-container'>";
    echo "<div class='card-1c' style='text-align: center; padding: 2rem;'>";
    echo "<h3>Дефектная ведомость не найдена</h3>";
    echo "<p>Ведомость с ID $defect_id не существует.</p>";
    echo "<a href='defects.php' class='action-btn-compact'>← Вернуться к списку</a>";
    echo "</div></div></div>";
    include 'templates/footer.php';
    exit;
}

// Получаем позиции ведомости
$items_stmt = $pdo->prepare("
    SELECT * FROM defect_items WHERE defect_id = ? ORDER BY type, sort_order
");
$items_stmt->execute([$defect_id]);
$items = $items_stmt->fetchAll(PDO::FETCH_ASSOC);

// Если мастер не указан
if (!$defect['master_name']) {
    $defect['master_name'] = 'Мастер не указан';
}
?>
<?php include 'templates/header.php';?>
    <div class="container">
        <!-- Компактный заголовок -->
        <div class="header-compact">
            <h1 class="page-title-compact">ПРЕДВАРИТЕЛЬНАЯ ДЕФЕКТНАЯ ВЕДОМОСТЬ</h1>
            <div class="header-actions-compact">
                <a href="defect_edit.php?id=<?= $defect_id ?>" class="action-btn-compact">
                    <span class="action-icon">📝</span>
                    <span class="action-label">Редактировать</span>
                </a>
                <a href="defect_print.php?id=<?= $defect_id ?>" class="action-btn-compact primary" target="_blank">
                    <span class="action-icon">🖨️</span>
                    <span class="action-label">Печать</span>
                </a>
                <a href="defects.php" class="action-btn-compact">
                    <span class="action-icon">←</span>
                    <span class="action-label">Назад</span>
                </a>
            </div>
        </div>

        <!-- Основная информация -->
        <div class="row-1c">
            <div class="card-1c compact-card">
                <div class="card-header-1c compact-header">
                    <h5>📋 ОСНОВНАЯ ИНФОРМАЦИЯ</h5>
                </div>
                <div class="compact-content">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                        <div>
                            <p><strong>Номер ведомости:</strong> 
                                <?= htmlspecialchars($defect['defect_number'] ?? 'DEF-' . $defect_id) ?>
                            </p>
                            <p><strong>Дата создания:</strong> 
                                <?= date('d.m.Y H:i', strtotime($defect['created_at'])) ?>
                            </p>
                            <p><strong>Мастер-приёмщик:</strong> 
                                <?= htmlspecialchars($defect['master_name']) ?>
                            </p>
                        </div>
                        <div>
                            <p><strong>Статус:</strong> 
                                <span class="status-badge-enhanced <?= $defect['status'] ?>">
                                    <?= $defect['status'] === 'draft' ? '📝 Черновик' : 
                                       ($defect['status'] === 'approved' ? '✅ Утверждено' : '❌ Отклонено') ?>
                                </span>
                            </p>
                            <p><strong>Согласовано клиентом:</strong> 
                                <?= $defect['client_agreed'] ? '✅ Да' : '❌ Нет' ?>
                            </p>
                            <p><strong>Безопасность разъяснена:</strong> 
                                <?= $defect['safety_explained'] ? '✅ Да' : '❌ Нет' ?>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Информация о клиенте и автомобиле -->
        <div class="row-1c">
            <div class="card-1c compact-card">
                <div class="card-header-1c compact-header">
                    <h5>👤 ДАННЫЕ КЛИЕНТА</h5>
                </div>
                <div class="compact-content">
                    <p><strong>ФИО:</strong> <?= htmlspecialchars($defect['client_name'] ?? 'Не указан') ?></p>
                    <p><strong>Телефон:</strong> <?= htmlspecialchars($defect['client_phone'] ?? 'Не указан') ?></p>
                </div>
            </div>

            <div class="card-1c compact-card">
                <div class="card-header-1c compact-header">
                    <h5>🚗 ДАННЫЕ АВТОМОБИЛЯ</h5>
                </div>
                <div class="compact-content">
                    <p><strong>Марка/Модель:</strong> <?= htmlspecialchars($defect['car_model'] ?? 'Не указан') ?></p>
                    <p><strong>VIN:</strong> <?= htmlspecialchars($defect['car_vin'] ?? 'Не указан') ?></p>
                    <p><strong>Гос. номер:</strong> <?= htmlspecialchars($defect['car_plate'] ?? 'Не указан') ?></p>
                    <p><strong>Год выпуска:</strong> <?= htmlspecialchars($defect['car_year'] ?? 'Не указан') ?></p>
                </div>
            </div>
        </div>

        <!-- Работы и услуги -->
        <div class="card-1c">
            <div class="card-header-1c">
                <h5>🔧 НЕОБХОДИМЫЕ РАБОТЫ И УСЛУГИ</h5>
            </div>
            <div class="orders-table-container">
                <table class="orders-table-enhanced">
                    <thead>
                        <tr>
                            <th class="col-id">#</th>
                            <th class="col-desc">Наименование работ и услуг</th>
                            <th class="col-status">Кол-во</th>
                            <th class="col-amount">Цена, руб.</th>
                            <th class="col-amount">Сумма, руб.</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $services_total = 0;
                        $service_count = 0;
                        $has_services = false;
                        
                        foreach ($items as $index => $item): 
                            if ($item['type'] === 'service'): 
                                $has_services = true;
                                $service_count++;
                                $services_total += $item['total'];
                        ?>
                        <tr class="order-row">
                            <td class="order-id"><?= $service_count ?></td>
                            <td class="order-desc">
                                <div class="desc-text"><?= htmlspecialchars($item['name']) ?></div>
                                <?php if (!empty($item['notes'])): ?>
                                <div style="font-size: 0.8rem; color: #8b6914; margin-top: 0.2rem;">
                                    📝 <?= htmlspecialchars($item['notes']) ?>
                                </div>
                                <?php endif; ?>
                            </td>
                            <td><?= $item['quantity'] ?> <?= $item['unit'] ?></td>
                            <td class="order-amount">
                                <div class="amount-main"><?= number_format($item['price'], 2, ',', ' ') ?></div>
                            </td>
                            <td class="order-amount">
                                <div class="amount-main"><?= number_format($item['total'], 2, ',', ' ') ?></div>
                            </td>
                        </tr>
                        <?php endif; endforeach; ?>
                        
                        <?php if (!$has_services): ?>
                        <tr>
                            <td colspan="5" style="text-align: center; padding: 2rem; color: #8b6914;">
                                📋 Нет добавленных работ
                            </td>
                        </tr>
                        <?php endif; ?>
                        
                        <!-- Итог по работам -->
                        <tr style="background: #fff8dc;">
                            <td colspan="3"><strong>Итого стоимость необходимых работ и услуг:</strong></td>
                            <td class="order-amount" colspan="2">
                                <div class="amount-main"><?= number_format($services_total, 2, ',', ' ') ?></div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Запчасти и материалы -->
        <div class="card-1c">
            <div class="card-header-1c">
                <h5>⚙️ НЕОБХОДИМЫЕ ДЛЯ РЕМОНТА ЗАПАСНЫЕ ЧАСТИ И МАТЕРИАЛЫ</h5>
            </div>
            <div class="orders-table-container">
                <table class="orders-table-enhanced">
                    <thead>
                        <tr>
                            <th class="col-id">#</th>
                            <th class="col-desc">Наименование товара</th>
                            <th class="col-status">Производитель</th>
                            <th class="col-status">Кол-во</th>
                            <th class="col-amount">Цена, руб.</th>
                            <th class="col-amount">Сумма, руб.</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $parts_total = 0;
                        $part_count = 0;
                        $has_parts = false;
                        
                        foreach ($items as $index => $item): 
                            if ($item['type'] === 'part'): 
                                $has_parts = true;
                                $part_count++;
                                $parts_total += $item['total'];
                        ?>
                        <tr class="order-row">
                            <td class="order-id"><?= $part_count ?></td>
                            <td class="order-desc">
                                <div class="desc-text"><?= htmlspecialchars($item['name']) ?></div>
                                <?php if (!empty($item['notes'])): ?>
                                <div style="font-size: 0.8rem; color: #8b6914; margin-top: 0.2rem;">
                                    📝 <?= htmlspecialchars($item['notes']) ?>
                                </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!empty($item['manufacturer'])): ?>
                                <span style="background: #fff8dc; padding: 0.2rem 0.4rem; border-radius: 3px; font-size: 0.8rem;">
                                    <?= htmlspecialchars($item['manufacturer']) ?>
                                </span>
                                <?php else: ?>
                                <span style="color: #8b6914; font-size: 0.8rem;">Не указан</span>
                                <?php endif; ?>
                            </td>
                            <td><?= $item['quantity'] ?> <?= $item['unit'] ?></td>
                            <td class="order-amount">
                                <div class="amount-main"><?= number_format($item['price'], 2, ',', ' ') ?></div>
                            </td>
                            <td class="order-amount">
                                <div class="amount-main"><?= number_format($item['total'], 2, ',', ' ') ?></div>
                            </td>
                        </tr>
                        <?php endif; endforeach; ?>
                        
                        <?php if (!$has_parts): ?>
                        <tr>
                            <td colspan="6" style="text-align: center; padding: 2rem; color: #8b6914;">
                                ⚙️ Нет добавленных запчастей
                            </td>
                        </tr>
                        <?php endif; ?>
                        
                        <!-- Итог по запчастям -->
                        <tr style="background: #fff8dc;">
                            <td colspan="4"><strong>Итого стоимость необходимых запасных частей и материалов:</strong></td>
                            <td class="order-amount" colspan="2">
                                <div class="amount-main"><?= number_format($parts_total, 2, ',', ' ') ?></div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Общая сумма -->
        <div class="card-1c">
            <div class="card-header-1c">
                <h5>💰 ОБЩАЯ СУММА РЕМОНТА</h5>
            </div>
            <div style="padding: 20px; text-align: center;">
                <div style="background: #fff8dc; padding: 20px; border: 2px solid #e6d8a8;">
                    <h2 style="color: #5c4a00; margin-bottom: 10px; font-size: 1.8rem;">
                        ИТОГО К ОПЛАТЕ: <?= number_format($services_total + $parts_total, 2, ',', ' ') ?> руб.
                    </h2>
                    <p style="color: #8b6914; font-size: 1rem; font-weight: 500;">
                        <?= num2str($services_total + $parts_total) ?>
                    </p>
                </div>
            </div>
        </div>

        <!-- Согласование -->
        <div class="card-1c">
            <div class="card-header-1c">
                <h5>📝 СОГЛАСОВАНИЕ РАБОТ</h5>
            </div>
            <div style="padding: 20px;">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div>
                        <h4 style="color: #5c4a00; margin-bottom: 15px;">✅ Подтверждения</h4>
                        <div style="margin-bottom: 15px;">
                            <label style="display: flex; align-items: center; gap: 10px; font-weight: 500;">
                                <input type="checkbox" <?= $defect['safety_explained'] ? 'checked' : '' ?> disabled>
                                Техника безопасности разъяснена
                            </label>
                        </div>
                        <div>
                            <label style="display: flex; align-items: center; gap: 10px; font-weight: 500;">
                                <input type="checkbox" <?= $defect['client_agreed'] ? 'checked' : '' ?> disabled>
                                Согласен с перечнем работ и стоимостью
                            </label>
                        </div>
                    </div>
                    
                    <div>
                        <h4 style="color: #5c4a00; margin-bottom: 15px;">📅 Даты</h4>
                        <p><strong>Создано:</strong> <?= date('d.m.Y H:i', strtotime($defect['created_at'])) ?></p>
                        <?php if ($defect['client_agreed'] && $defect['updated_at']): ?>
                        <p><strong>Согласовано:</strong> <?= date('d.m.Y H:i', strtotime($defect['updated_at'])) ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Примечания -->
        <?php if (!empty($defect['notes'])): ?>
        <div class="card-1c">
            <div class="card-header-1c">
                <h5>📋 ПРИМЕЧАНИЯ ИСПОЛНИТЕЛЯ</h5>
            </div>
            <div style="padding: 20px;">
                <div style="background: #fff8dc; padding: 15px; border-left: 4px solid #8b6914;">
                    <p style="color: #5c4a00; line-height: 1.5; margin: 0;"><?= nl2br(htmlspecialchars($defect['notes'])) ?></p>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php 
// Функция для преобразования числа в строку (прописью)
function num2str($num) {
    // Упрощенная версия
    $whole = floor($num);
    $fraction = round(($num - $whole) * 100);
    
    $rubles = $whole . ' ' . getNoun($whole, 'рубль', 'рубля', 'рублей');
    $kopecks = $fraction . ' ' . getNoun($fraction, 'копейка', 'копейки', 'копеек');
    
    return "$rubles $kopecks";
}

function getNoun($number, $one, $two, $five) {
    $n = abs($number) % 100;
    if ($n > 10 && $n < 20) return $five;
    $n = $n % 10;
    if ($n === 1) return $one;
    if ($n >= 2 && $n <= 4) return $two;
    return $five;
}

include 'templates/footer.php'; 
?>