<?php
require 'includes/db.php';
session_start();
define('ACCESS', true);

$success = false;
$defect_id = null;
$defect_number = null;

// Обработка формы
if ($_POST) {
    try {
        // Создаем новую дефектную ведомость
        $stmt = $pdo->prepare("INSERT INTO defects (client_id, car_id, defect_number, master_id) VALUES (?, ?, ?, ?)");
        $defect_number = 'DEF-' . date('Y-m-d') . '-' . str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT);
        $stmt->execute([$_POST['client_id'], $_POST['car_id'], $defect_number, $_SESSION['user_id'] ?? 1]);
        $defect_id = $pdo->lastInsertId();
        $success = true;
        
    } catch (PDOException $e) {
        $error = "Ошибка при создании ведомости: " . $e->getMessage();
    }
}

// Получаем клиентов и автомобили для формы
$clients = $pdo->query("SELECT id, name, phone FROM clients WHERE active = 1")->fetchAll();
$cars = $pdo->query("SELECT id, model, vin, license_plate FROM cars WHERE active = 1")->fetchAll();
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Создание дефектной ведомости - АВТОСЕРВИС</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'templates/header.php';?>
<body class="cars-container">
            <!-- Компактный заголовок -->
            <div class="header-compact">
                <h1 class="page-title-compact">СОЗДАНИЕ ДЕФЕКТНОЙ ВЕДОМОСТИ</h1>
                <div class="header-actions-compact">
                    <a href="defects.php" class="action-btn-compact">
                        <span class="action-icon">←</span>
                        <span class="action-label">Назад</span>
                    </a>
                </div>
            </div>

            <?php if ($success): ?>
            <!-- Сообщение об успехе -->
            <div class="card-1c" style="background: #d4edda; border-color: #c3e6cb;">
                <div style="text-align: center; padding: 2rem;">
                    <div style="font-size: 3rem; margin-bottom: 1rem;">✅</div>
                    <h3 style="color: #155724; margin-bottom: 1rem;">Дефектная ведомость успешно создана!</h3>
                    <p style="color: #155724; margin-bottom: 2rem;">
                        <strong>Номер ведомости:</strong> <?= htmlspecialchars($defect_number) ?>
                    </p>
                    <div style="display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;">
                        <a href="defect_view.php?id=<?= $defect_id ?>" class="action-btn-compact primary">
                            <span class="action-icon">👁️</span>
                            <span class="action-label">Просмотреть</span>
                        </a>
                        <a href="defect_edit.php?id=<?= $defect_id ?>" class="action-btn-compact">
                            <span class="action-icon">✏️</span>
                            <span class="action-label">Редактировать</span>
                        </a>
                        <a href="defects.php" class="action-btn-compact">
                            <span class="action-icon">📋</span>
                            <span class="action-label">К списку ведомостей</span>
                        </a>
                        <a href="defect_create.php" class="action-btn-compact">
                            <span class="action-icon">➕</span>
                            <span class="action-label">Создать еще</span>
                        </a>
                    </div>
                </div>
            </div>
            <?php else: ?>

            <?php if (isset($error)): ?>
            <!-- Сообщение об ошибке -->
            <div class="card-1c" style="background: #f8d7da; border-color: #f5c6cb;">
                <div style="text-align: center; padding: 1rem;">
                    <div style="font-size: 2rem; margin-bottom: 0.5rem;">❌</div>
                    <p style="color: #721c24; margin: 0;"><?= htmlspecialchars($error) ?></p>
                </div>
            </div>
            <?php endif; ?>

            <!-- Форма создания -->
            <div class="card-1c">
                <div class="card-header-1c">
                    <h5>📝 ВЫБЕРИТЕ КЛИЕНТА И АВТОМОБИЛЬ</h5>
                </div>
                <div style="padding: 2rem;">
                    <form method="POST" id="createDefectForm">
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
                            <!-- Выбор клиента -->
                            <div>
                                <div class="form-group">
                                    <label for="client_id"><strong>👤 Клиент:</strong></label>
                                    <select name="client_id" id="client_id" required class="form-select" style="width: 100%; padding: 0.75rem; border: 1px solid #e6d8a8; background: #fffef5;">
                                        <option value="">-- Выберите клиента --</option>
                                        <?php foreach ($clients as $client): ?>
                                        <option value="<?= $client['id'] ?>">
                                            <?= htmlspecialchars($client['name']) ?> (<?= htmlspecialchars($client['phone']) ?>)
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <?php if (empty($clients)): ?>
                                <div style="background: #fff3cd; padding: 1rem; border: 1px solid #ffeaa7; margin-top: 1rem;">
                                    <p style="margin: 0; color: #856404;">
                                        <strong>ℹ️ Нет доступных клиентов</strong><br>
                                        <a href="client_create.php" style="color: #8b6914;">Создать нового клиента</a>
                                    </p>
                                </div>
                                <?php endif; ?>
                            </div>

                            <!-- Выбор автомобиля -->
                            <div>
                                <div class="form-group">
                                    <label for="car_id"><strong>🚗 Автомобиль:</strong></label>
                                    <select name="car_id" id="car_id" required class="form-select" style="width: 100%; padding: 0.75rem; border: 1px solid #e6d8a8; background: #fffef5;">
                                        <option value="">-- Выберите автомобиль --</option>
                                        <?php foreach ($cars as $car): ?>
                                                                                <option value="<?= $car['id'] ?>">
                                            <?= htmlspecialchars($car['model']) ?> (<?= htmlspecialchars($car['vin']) ?>)
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <?php if (empty($cars)): ?>
                                <div style="background: #fff3cd; padding: 1rem; border: 1px solid #ffeaa7; margin-top: 1rem;">
                                    <p style="margin: 0; color: #856404;">
                                        <strong>ℹ️ Нет доступных автомобилей</strong><br>
                                        <a href="car_create.php" style="color: #8b6914;">Добавить новый автомобиль</a>
                                    </p>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Кнопки -->
                        <div style="display: flex; gap: 1rem; justify-content: center; margin-top: 2rem; padding-top: 2rem; border-top: 1px solid #e6d8a8;">
                            <button type="submit" class="action-btn-compact primary" style="font-size: 1.1rem; padding: 1rem 2rem;">
                                <span class="action-icon">✅</span>
                                <span class="action-label">Создать ведомость</span>
                            </button>
                            <a href="defects.php" class="action-btn-compact" style="font-size: 1.1rem; padding: 1rem 2rem;">
                                <span class="action-icon">❌</span>
                                <span class="action-label">Отмена</span>
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Подсказки -->
            <div class="row-1c">
                <div class="card-1c compact-card">
                    <div class="card-header-1c compact-header">
                        <h5>💡 ПОДСКАЗКА</h5>
                    </div>
                    <div class="compact-content">
                        <p style="color: #8b6914; font-size: 0.9rem; margin: 0;">
                            После создания ведомости вы сможете добавить работы, запчасти и отправить на согласование клиенту.
                        </p>
                    </div>
                </div>
                
                <div class="card-1c compact-card">
                    <div class="card-header-1c compact-header">
                        <h5>⚡ БЫСТРЫЕ ДЕЙСТВИЯ</h5>
                    </div>
                    <div class="compact-content">
                        <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                            <a href="client_create.php" class="action-btn-compact small" style="justify-content: flex-start;">
                                <span class="action-icon">👤</span>
                                <span class="action-label">Новый клиент</span>
                            </a>
                            <a href="car_create.php" class="action-btn-compact small" style="justify-content: flex-start;">
                                <span class="action-icon">🚗</span>
                                <span class="action-label">Новый автомобиль</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <?php include 'templates/footer.php';?>

    <script>
        // Простая валидация формы
        document.getElementById('createDefectForm').addEventListener('submit', function(e) {
            const clientId = document.getElementById('client_id').value;
            const carId = document.getElementById('car_id').value;
            
            if (!clientId || !carId) {
                e.preventDefault();
                alert('Пожалуйста, выберите клиента и автомобиль');
                return false;
            }
        });
    </script>
</body>
</html>