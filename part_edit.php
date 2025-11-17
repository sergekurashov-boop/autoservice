<?php
// В начало каждого edit-файла
//error_log(date('Y-m-d H:i:s') . " - Редактирование записи ID: $id\n", 3, "logs/edits.log");
require 'includes/db.php';
session_start();
require_once 'includes/navbar.php';

if (!isset($_GET['id'])) {
    header("Location: parts.php");
    exit;
}

$part_id = $_GET['id'];
$part = $conn->query("SELECT * FROM parts WHERE id = $part_id")->fetch_assoc();

if (!$part) {
    header("Location: parts.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_part'])) {
    $name = $_POST['name'];
    $part_number = $_POST['part_number'];
    $quantity = $_POST['quantity'];
    $price = $_POST['price'];
    
    $stmt = $conn->prepare("UPDATE parts SET name = ?, part_number = ?, quantity = ?, price = ? WHERE id = ?");
    $stmt->bind_param("ssidi", $name, $part_number, $quantity, $price, $part_id);
    
    if ($stmt->execute()) {
        $success = "Запчасть обновлена!";
        $part = $conn->query("SELECT * FROM parts WHERE id = $part_id")->fetch_assoc();
    } else {
        $error = "Ошибка: " . $stmt->error;
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Редактирование запчасти</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
     
    
    <div class="container mt-4">
        <h1>Редактирование запчасти #<?= $part_id ?></h1>
        
        <?php if(isset($success)): ?>
            <div class="alert alert-success"><?= $success ?></div>
        <?php endif; ?>
        
        <?php if(isset($error)): ?>
            <div class="alert alert-danger"><?= $error ?></div>
        <?php endif; ?>
        
        <form method="post">
            <div class="mb-3">
                <label class="form-label">Название:</label>
                <input type="text" name="name" class="form-control" 
                       value="<?= htmlspecialchars($part['name']) ?>" required>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Артикул:</label>
                        <input type="text" name="part_number" class="form-control" 
                               value="<?= htmlspecialchars($part['part_number']) ?>" required>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="mb-3">
                        <label class="form-label">Количество:</label>
                        <input type="number" name="quantity" class="form-control" 
                               value="<?= $part['quantity'] ?>" min="0" required>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="mb-3">
                        <label class="form-label">Цена (руб):</label>
                        <input type="number" name="price" class="form-control" 
                               value="<?= $part['price'] ?>" step="0.01" min="0" required>
                    </div>
                </div>
            </div>
            
            <button type="submit" name="update_part" class="btn btn-primary">Сохранить</button>
            <a href="parts.php" class="btn btn-secondary">Отмена</a>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>