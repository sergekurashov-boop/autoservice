<?php
require_once '../Includes/auth.php';
require_once '../Includes/db_connect.php';
require_once '../Includes/functions.php';

// Проверка прав администратора
if (!isAdmin()) {
    die('Access denied');
}

$log = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csv_file'])) {
    
    $file = $_FILES['csv_file']['tmp_name'];
    
    if (($handle = fopen($file, "r")) !== FALSE) {
        // Пропускаем заголовок (первую строку)
        fgetcsv($handle, 1000, ",");
        
        while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
            // Подготовка данных
            $clientData = [
                'name' => $data[0],
                'phone' => $data[1],
                'email' => $data[2],
                'car_model' => $data[3],
                'car_year' => $data[4],
                'vin' => $data[5]
            ];
            
            try {
                // Валидация данных
                if (!validateClientData($clientData)) {
                    $log[] = "Invalid data: " . implode(", ", $data);
                    continue;
                }
                
                // Проверка на дубликаты
                if (clientExists($pdo, $clientData['email'])) {
                    $log[] = "Client exists: " . $clientData['email'];
                    continue;
                }
                
                // Сохранение в БД
                $stmt = $pdo->prepare("INSERT INTO clients (name, phone, email, car_model, car_year, vin) 
                                      VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute(array_values($clientData));
                
                $log[] = "Imported: " . $clientData['email'];
                
            } catch (Exception $e) {
                $log[] = "Error: " . $e->getMessage();
            }
        }
        fclose($handle);
    }
}

// Функция валидации
function validateClientData($data) {
    return !empty($data['name']) && 
           !empty($data['phone']) && 
           filter_var($data['email'], FILTER_VALIDATE_EMAIL);
}

// Проверка существования клиента
function clientExists($pdo, $email) {
    $stmt = $pdo->prepare("SELECT id FROM clients WHERE email = ?");
    $stmt->execute([$email]);
    return $stmt->fetch();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Импорт клиентов</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="container">
        <h1>Импорт клиентов из CSV</h1>
        
        <form method="post" enctype="multipart/form-data">
            <input type="file" name="csv_file" accept=".csv" required>
            <button type="submit">Импортировать</button>
        </form>
        
        <?php if (!empty($log)): ?>
        <div class="log">
            <h3>Результат импорта:</h3>
            <pre><?= implode("\n", $log) ?></pre>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>