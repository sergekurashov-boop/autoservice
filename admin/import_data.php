<?php
// admin/import_data.php
require_once '../Includes/auth.php';
require_once '../Includes/db_connect.php';
require_once '../Includes/functions.php';

// Проверка прав администратора
if (!isAdmin()) {
    header('HTTP/1.1 403 Forbidden');
    echo 'Доступ запрещен. Требуются права администратора.';
    exit;
}

$log = [];
$importType = '';
$importedCount = 0;
$errorCount = 0;

// Обработка формы импорта
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['import_file']) && isset($_POST['import_type'])) {
    $importType = $_POST['import_type'];
    $file = $_FILES['import_file'];
    
    // Валидация файла
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $log[] = "Ошибка загрузки файла: " . $file['error'];
    } else {
        // Проверка типа файла - только CSV
        $fileExt = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if ($fileExt !== 'csv') {
            $log[] = "Недопустимый формат файла. Разрешены только CSV файлы.";
        } else {
            // Обработка файла в зависимости от типа
            try {
                switch ($importType) {
                    case 'clients':
                        list($imported, $errors) = importClients($file['tmp_name']);
                        $importedCount = $imported;
                        $errorCount = $errors;
                        break;
                    case 'cars':
                        list($imported, $errors) = importCars($file['tmp_name']);
                        $importedCount = $imported;
                        $errorCount = $errors;
                        break;
                    case 'services':
                        list($imported, $errors) = importServices($file['tmp_name']);
                        $importedCount = $imported;
                        $errorCount = $errors;
                        break;
                    case 'mechanics':
                        list($imported, $errors) = importMechanics($file['tmp_name']);
                        $importedCount = $imported;
                        $errorCount = $errors;
                        break;
                    case 'orders':
                        list($imported, $errors) = importOrders($file['tmp_name']);
                        $importedCount = $imported;
                        $errorCount = $errors;
                        break;
                    default:
                        $log[] = "Неизвестный тип импорта: " . htmlspecialchars($importType);
                        break;
                }
                
                $log[] = "Импорт завершен. Успешно: $importedCount, Ошибок: $errorCount";
                
            } catch (Exception $e) {
                $log[] = "Ошибка при импорте: " . $e->getMessage();
            }
        }
    }
}

/**
 * Импорт клиентов из CSV файла
 */
function importClients($filePath) {
    global $pdo;
    $imported = 0;
    $errors = 0;
    
    $data = readCSVFile($filePath);
    
    // Начинаем транзакцию для целостности данных
    $pdo->beginTransaction();
    
    try {
        $stmt = $pdo->prepare("INSERT INTO clients (name, phone, email, address, created_at) 
                              VALUES (?, ?, ?, ?, NOW()) 
                              ON DUPLICATE KEY UPDATE 
                              name = VALUES(name), phone = VALUES(phone), address = VALUES(address)");
        
        foreach ($data as $row) {
            // Пропускаем пустые строки
            if (empty(array_filter($row))) continue;
            
            // Валидация данных
            if (empty($row[0]) || empty($row[2])) {
                $errors++;
                $log[] = "Пропущена запись: отсутствует имя или email";
                continue;
            }
            
            // Подготовка данных
            $name = trim($row[0]);
            $phone = trim($row[1] ?? '');
            $email = filter_var(trim($row[2]), FILTER_SANITIZE_EMAIL);
            $address = trim($row[3] ?? '');
            
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors++;
                $log[] = "Неверный email: $email";
                continue;
            }
            
            // Выполняем запрос
            if ($stmt->execute([$name, $phone, $email, $address])) {
                $imported++;
            } else {
                $errors++;
                $log[] = "Ошибка БД для: $email";
            }
        }
        
        $pdo->commit();
        return [$imported, $errors];
        
    } catch (Exception $e) {
        $pdo->rollBack();
        throw new Exception("Ошибка импорта клиентов: " . $e->getMessage());
    }
}

/**
 * Импорт автомобилей из CSV файла
 */
function importCars($filePath) {
    global $pdo;
    $imported = 0;
    $errors = 0;
    
    $data = readCSVFile($filePath);
    
    $pdo->beginTransaction();
    
    try {
        $stmt = $pdo->prepare("INSERT INTO cars (client_id, brand, model, year, vin, license_plate, created_at) 
                              VALUES (?, ?, ?, ?, ?, ?, NOW()) 
                              ON DUPLICATE KEY UPDATE 
                              brand = VALUES(brand), model = VALUES(model), year = VALUES(year), 
                              vin = VALUES(vin), license_plate = VALUES(license_plate)");
        
        foreach ($data as $row) {
            if (empty(array_filter($row))) continue;
            
            // Получаем ID клиента по email
            $clientEmail = filter_var(trim($row[0]), FILTER_SANITIZE_EMAIL);
            $clientId = getClientIdByEmail($clientEmail);
            
            if (!$clientId) {
                $errors++;
                $log[] = "Клиент не найден: $clientEmail";
                continue;
            }
            
            $brand = trim($row[1]);
            $model = trim($row[2]);
            $year = is_numeric($row[3]) ? (int)$row[3] : null;
            $vin = trim($row[4] ?? '');
            $licensePlate = trim($row[5] ?? '');
            
            if (empty($brand) || empty($model)) {
                $errors++;
                $log[] = "Пропущена запись: отсутствует марка или модель";
                continue;
            }
            
            if ($stmt->execute([$clientId, $brand, $model, $year, $vin, $licensePlate])) {
                $imported++;
            } else {
                $errors++;
                $log[] = "Ошибка БД для: $brand $model";
            }
        }
        
        $pdo->commit();
        return [$imported, $errors];
        
    } catch (Exception $e) {
        $pdo->rollBack();
        throw new Exception("Ошибка импорта автомобилей: " . $e->getMessage());
    }
}

/**
 * Импорт услуг из CSV файла
 */
function importServices($filePath) {
    global $pdo;
    $imported = 0;
    $errors = 0;
    
    $data = readCSVFile($filePath);
    
    $pdo->beginTransaction();
    
    try {
        $stmt = $pdo->prepare("INSERT INTO services (name, description, price, duration, category, created_at) 
                              VALUES (?, ?, ?, ?, ?, NOW()) 
                              ON DUPLICATE KEY UPDATE 
                              description = VALUES(description), price = VALUES(price), 
                              duration = VALUES(duration), category = VALUES(category)");
        
        foreach ($data as $row) {
            if (empty(array_filter($row))) continue;
            
            $name = trim($row[0]);
            $description = trim($row[1] ?? '');
            $price = is_numeric($row[2]) ? (float)$row[2] : 0;
            $duration = is_numeric($row[3]) ? (int)$row[3] : 60;
            $category = trim($row[4] ?? '');
            
            if (empty($name)) {
                $errors++;
                $log[] = "Пропущена запись: отсутствует название услуги";
                continue;
            }
            
            if ($stmt->execute([$name, $description, $price, $duration, $category])) {
                $imported++;
            } else {
                $errors++;
                $log[] = "Ошибка БД для: $name";
            }
        }
        
        $pdo->commit();
        return [$imported, $errors];
        
    } catch (Exception $e) {
        $pdo->rollBack();
        throw new Exception("Ошибка импорта услуг: " . $e->getMessage());
    }
}

/**
 * Чтение CSV файла
 */
function readCSVFile($filePath) {
    $data = [];
    
    // Чтение CSV файла
    if (($handle = fopen($filePath, "r")) !== FALSE) {
        // Пропускаем заголовок (первую строку)
        fgetcsv($handle, 10000, ",");
        
        while (($row = fgetcsv($handle, 10000, ",")) !== FALSE) {
            $data[] = $row;
        }
        fclose($handle);
    }
    
    return $data;
}

/**
 * Вспомогательные функции для получения ID по различным полям
 */
function getClientIdByEmail($email) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT id FROM clients WHERE email = ?");
    $stmt->execute([$email]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result ? $result['id'] : null;
}

// Остальные вспомогательные функции (getCarIdByVin, getServiceIdByName и т.д.) остаются без изменений
// ...

?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Импорт данных - Autoservice</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .container {
            max-width: 800px;
            margin: 30px auto;
            padding: 20px;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #2c3e50;
            margin-bottom: 20px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        select, input[type="file"] {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
        }
        button {
            background: #3498db;
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
        }
        button:hover {
            background: #2980b9;
        }
        .log {
            margin-top: 20px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 4px;
            border-left: 4px solid #3498db;
        }
        .success {
            color: #27ae60;
            font-weight: bold;
        }
        .error {
            color: #e74c3c;
        }
        .info {
            margin-top: 20px;
            padding: 15px;
            background: #e1f5fe;
            border-radius: 4px;
        }
        .template-download {
            margin-top: 15px;
        }
    </style>
</head>
<body>
    <?php include '../templates/header.php'; ?>
    
    <div class="container">
        <h1>Импорт данных из CSV</h1>
        
        <div class="info">
            <h3>Поддерживаемые форматы: CSV</h3>
            <p>Перед импортом убедитесь, что ваш CSV файл имеет правильную структуру:</p>
            <ul>
                <li><strong>Клиенты</strong>: имя, телефон, email, адрес</li>
                <li><strong>Автомобили</strong>: email клиента, марка, модель, год, VIN, номер</li>
                <li><strong>Услуги</strong>: название, описание, цена, длительность, категория</li>
            </ul>
            
            <div class="template-download">
                <p>Скачать шаблоны CSV:</p>
                <a href="?template=clients" download="clients_template.csv">Шаблон для клиентов</a> |
                <a href="?template=cars" download="cars_template.csv">Шаблон для автомобилей</a> |
                <a href="?template=services" download="services_template.csv">Шаблон для услуг</a>
            </div>
        </div>
        
        <form method="post" enctype="multipart/form-data">
            <div class="form-group">
                <label for="import_type">Тип данных для импорта:</label>
                <select id="import_type" name="import_type" required>
                    <option value="">-- Выберите тип --</option>
                    <option value="clients" <?= $importType === 'clients' ? 'selected' : '' ?>>Клиенты</option>
                    <option value="cars" <?= $importType === 'cars' ? 'selected' : '' ?>>Автомобили</option>
                    <option value="services" <?= $importType === 'services' ? 'selected' : '' ?>>Услуги</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="import_file">CSV файл для импорта:</label>
                <input type="file" id="import_file" name="import_file" accept=".csv" required>
            </div>
            
            <button type="submit">Загрузить и импортировать</button>
        </form>
        
        <?php if (!empty($log)): ?>
        <div class="log">
            <h3>Результат импорта:</h3>
            <?php if ($importedCount > 0): ?>
                <p class="success">Успешно импортировано записей: <?= $importedCount ?></p>
            <?php endif; ?>
            <?php if ($errorCount > 0): ?>
                <p class="error">Ошибок при импорте: <?= $errorCount ?></p>
            <?php endif; ?>
            <pre><?= implode("\n", $log) ?></pre>
        </div>
        <?php endif; ?>
    </div>
    
    <?php include '../templates/footer.php'; ?>
</body>
</html>