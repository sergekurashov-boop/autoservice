<?php
session_start();
require 'includes/db.php';
require_once 'auth_check.php';
requireAnyRole(['admin', 'manager', 'mechanic']);

// Создаем необходимые таблицы если их нет
$conn->query("
    CREATE TABLE IF NOT EXISTS warehouse_categories (
        id INT PRIMARY KEY AUTO_INCREMENT,
        name VARCHAR(255) NOT NULL UNIQUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )
");

$conn->query("
    CREATE TABLE IF NOT EXISTS warehouse_items (
        id INT PRIMARY KEY AUTO_INCREMENT,
        sku VARCHAR(50) UNIQUE,
        name VARCHAR(255) NOT NULL,
        description TEXT,
        category_id INT,
        price DECIMAL(10,2) DEFAULT 0,
        quantity INT DEFAULT 0,
        min_quantity INT DEFAULT 0,
        location VARCHAR(100),
        part_number VARCHAR(100),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (category_id) REFERENCES warehouse_categories(id) ON DELETE SET NULL
    )
");

// Добавляем базовые категории если их нет
$conn->query("
    INSERT IGNORE INTO warehouse_categories (name) VALUES 
    ('Двигатель'),
    ('Трансмиссия'),
    ('Тормозная система'),
    ('Подвеска'),
    ('Электрика'),
    ('Кузовные детали'),
    ('Фильтры'),
    ('Масла и жидкости')
");

// Обработка импорта данных
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['import_csv'])) {
    if (isset($_FILES['csv_file']) && $_FILES['csv_file']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['csv_file']['tmp_name'];
        $handle = fopen($file, 'r');
        
        // Пропускаем заголовок
        fgetcsv($handle, 1000, ';');
        
        $imported = 0;
        $errors = [];
        
        while (($data = fgetcsv($handle, 1000, ';')) !== FALSE) {
            if (count($data) >= 6) {
                $name = trim($data[0]);
                $category = trim($data[1]);
                $part_number = trim($data[2]);
                $price = (float)str_replace(',', '.', $data[3]);
                $quantity = (int)$data[4];
                $location = trim($data[5]);
                
                if (empty($name)) continue;
                
                // Получаем или создаем категорию
                $category_id = null;
                if (!empty($category)) {
                    $stmt = $conn->prepare("SELECT id FROM warehouse_categories WHERE name = ?");
                    $stmt->bind_param("s", $category);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    
                    if ($result->num_rows > 0) {
                        $category_id = $result->fetch_assoc()['id'];
                    } else {
                        $stmt = $conn->prepare("INSERT INTO warehouse_categories (name) VALUES (?)");
                        $stmt->bind_param("s", $category);
                        $stmt->execute();
                        $category_id = $stmt->insert_id;
                    }
                }
                
                // Генерируем SKU если нет артикула
                $sku = !empty($part_number) ? $part_number : 'ITM-' . strtoupper(uniqid());
                
                // Проверяем существование по SKU
                $check_stmt = $conn->prepare("SELECT id FROM warehouse_items WHERE sku = ?");
                $check_stmt->bind_param("s", $sku);
                $check_stmt->execute();
                
                if ($check_stmt->get_result()->num_rows > 0) {
                    // Обновляем существующую запись
                    $stmt = $conn->prepare("
                        UPDATE warehouse_items SET 
                        name = ?, category_id = ?, price = ?, quantity = ?, location = ?, updated_at = NOW()
                        WHERE sku = ?
                    ");
                    $stmt->bind_param("sidis", $name, $category_id, $price, $quantity, $location, $sku);
                } else {
                    // Добавляем новую запись
                    $stmt = $conn->prepare("
                        INSERT INTO warehouse_items 
                        (sku, name, category_id, part_number, price, quantity, location) 
                        VALUES (?, ?, ?, ?, ?, ?, ?)
                    ");
                    $stmt->bind_param("ssisdis", $sku, $name, $category_id, $part_number, $price, $quantity, $location);
                }
                
                if ($stmt->execute()) {
                    $imported++;
                } else {
                    $errors[] = "Ошибка при импорте: " . $name;
                }
            }
        }
        
        fclose($handle);
        
        if ($imported > 0) {
            $_SESSION['success'] = "Успешно импортировано $imported записей";
        }
        if (!empty($errors)) {
            $_SESSION['error'] = "Ошибки при импорте: " . implode(", ", array_slice($errors, 0, 5));
        }
        
        header("Location: warehouse.php");
        exit;
    } else {
        $_SESSION['error'] = "Ошибка загрузки файла";
        header("Location: warehouse.php");
        exit;
    }
}

// Обработка добавления/редактирования запчасти
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_item'])) {
    $id = $_POST['id'] ?? null;
    $name = trim($_POST['name']);
    $category_id = $_POST['category_id'] ?: null;
    $part_number = trim($_POST['part_number']);
    $price = (float)$_POST['price'];
    $quantity = (int)$_POST['quantity'];
    $min_quantity = (int)$_POST['min_quantity'];
    $location = trim($_POST['location']);
    $description = trim($_POST['description']);
    
    if (empty($name)) {
        $_SESSION['error'] = "Название запчасти обязательно";
    } else {
        try {
            if ($id) {
                // Редактирование
                $stmt = $conn->prepare("
                    UPDATE warehouse_items SET 
                    name = ?, category_id = ?, part_number = ?, price = ?, 
                    quantity = ?, min_quantity = ?, location = ?, description = ?
                    WHERE id = ?
                ");
                $stmt->bind_param("sisdiiisi", $name, $category_id, $part_number, $price, 
                                $quantity, $min_quantity, $location, $description, $id);
            } else {
                // Добавление
                $sku = !empty($part_number) ? $part_number : 'ITM-' . strtoupper(uniqid());
                $stmt = $conn->prepare("
                    INSERT INTO warehouse_items 
                    (sku, name, category_id, part_number, price, quantity, min_quantity, location, description) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->bind_param("ssisdiiis", $sku, $name, $category_id, $part_number, $price, 
                                $quantity, $min_quantity, $location, $description);
            }
            
            if ($stmt->execute()) {
                $_SESSION['success'] = $id ? "Запчасть обновлена" : "Запчасть добавлена";
            } else {
                $_SESSION['error'] = "Ошибка при сохранении";
            }
        } catch (Exception $e) {
            $_SESSION['error'] = "Ошибка базы данных: " . $e->getMessage();
        }
    }
    
    header("Location: warehouse.php");
    exit;
}

// Обработка удаления
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    
    if (in_array($_SESSION['user_role'], ['admin', 'manager'])) {
        try {
            $stmt = $conn->prepare("DELETE FROM warehouse_items WHERE id = ?");
            $stmt->bind_param("i", $id);
            
            if ($stmt->execute()) {
                $_SESSION['success'] = "Запчасть удалена";
            }
        } catch (Exception $e) {
            $_SESSION['error'] = "Ошибка при удалении";
        }
    } else {
        $_SESSION['error'] = "Недостаточно прав для удаления";
    }
    
    header("Location: warehouse.php");
    exit;
}

// Получаем категории
$categories = [];
$result = $conn->query("SELECT id, name FROM warehouse_categories ORDER BY name");
if ($result) {
    $categories = $result->fetch_all(MYSQLI_ASSOC);
}

// Получаем список запчастей с пагинацией
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;

// Поиск и фильтры
$search = $_GET['search'] ?? '';
$category_filter = $_GET['category'] ?? '';

$where_conditions = [];
$params = [];
$types = '';

if (!empty($search)) {
    $where_conditions[] = "(wi.name LIKE ? OR wi.sku LIKE ? OR wi.part_number LIKE ?)";
    $search_term = "%$search%";
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
    $types .= 'sss';
}

if (!empty($category_filter)) {
    $where_conditions[] = "wi.category_id = ?";
    $params[] = $category_filter;
    $types .= 'i';
}

$where_sql = '';
if (!empty($where_conditions)) {
    $where_sql = "WHERE " . implode(" AND ", $where_conditions);
}

// Общее количество
$count_sql = "SELECT COUNT(*) FROM warehouse_items wi $where_sql";
$count_stmt = $conn->prepare($count_sql);
if (!empty($params)) {
    $count_stmt->bind_param($types, ...$params);
}
$count_stmt->execute();
$total_items = $count_stmt->get_result()->fetch_array()[0];
$total_pages = ceil($total_items / $per_page);

// Данные для таблицы
$sql = "
    SELECT wi.*, wc.name as category_name 
    FROM warehouse_items wi 
    LEFT JOIN warehouse_categories wc ON wi.category_id = wc.id 
    $where_sql 
    ORDER BY wi.name 
    LIMIT $per_page OFFSET $offset
";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Запчасти с низким запасом для предупреждения
$low_stock = [];
$result = $conn->query("
    SELECT wi.*, wc.name as category_name 
    FROM warehouse_items wi 
    LEFT JOIN warehouse_categories wc ON wi.category_id = wc.id 
    WHERE wi.quantity <= wi.min_quantity AND wi.min_quantity > 0 
    ORDER BY wi.quantity ASC 
    LIMIT 10
");
if ($result) {
    $low_stock = $result->fetch_all(MYSQLI_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>📦 Склад запчастей - Autoservice</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .warehouse-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 25px;
        }
        
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
            border-left: 4px solid #007bff;
        }
        
        .stat-card.warning { border-left-color: #ffc107; }
        .stat-card.danger { border-left-color: #dc3545; }
        
        .stat-number {
            font-size: 2em;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .header-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .search-filters {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
            flex-wrap: wrap;
            align-items: center;
        }
        
        .search-filters input, .search-filters select {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
        }
        
        .table-responsive {
            overflow-x: auto;
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .table th, .table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #495057;
        }
        
        .low-stock {
            background: #fff3cd !important;
            color: #856404;
        }
        
        .out-of-stock {
            background: #f8d7da !important;
            color: #721c24;
        }
        
        .action-buttons {
            display: flex;
            gap: 5px;
        }
        
        .btn {
            padding: 6px 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            font-size: 12px;
        }
        
        .btn-primary { background: #007bff; color: white; }
        .btn-success { background: #28a745; color: white; }
        .btn-warning { background: #ffc107; color: black; }
        .btn-danger { background: #dc3545; color: white; }
        .btn-info { background: #17a2b8; color: white; }
        
        .pagination {
            display: flex;
            justify-content: center;
            gap: 5px;
            margin-top: 20px;
        }
        
        .page-link {
            padding: 8px 12px;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            text-decoration: none;
            color: #007bff;
        }
        
        .page-link.active {
            background: #007bff;
            color: white;
            border-color: #007bff;
        }
        
        .import-section {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        
        .alert-warning {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            color: #856404;
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <?php include 'templates/header.php'; ?>
    
    <div class="warehouse-container">
        <h1>📦 Склад запчастей</h1>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-error"><?= $_SESSION['error'] ?></div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success"><?= $_SESSION['success'] ?></div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <!-- Статистика -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?= $total_items ?></div>
                <div class="stat-label">Всего запчастей</div>
            </div>
            <div class="stat-card warning">
                <div class="stat-number"><?= count($low_stock) ?></div>
                <div class="stat-label">Низкий запас</div>
            </div>
            <div class="stat-card danger">
                <div class="stat-number">
                    <?= array_reduce($items, function($carry, $item) {
                        return $carry + ($item['quantity'] === 0 ? 1 : 0);
                    }, 0) ?>
                </div>
                <div class="stat-label">Нет в наличии</div>
            </div>
        </div>

        <!-- Кнопки действий -->
        <div class="header-actions">
            <div class="search-filters">
                <input type="text" id="search" placeholder="Поиск по названию или артикулу..." 
                       value="<?= htmlspecialchars($search) ?>" style="width: 300px;">
                <select id="category_filter">
                    <option value="">Все категории</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?= $category['id'] ?>" <?= $category_filter == $category['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($category['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button onclick="applyFilters()" class="btn btn-primary">🔍 Поиск</button>
                <button onclick="clearFilters()" class="btn btn-secondary">❌ Сбросить</button>
            </div>
            
            <div style="display: flex; gap: 10px;">
                <button onclick="showImport()" class="btn btn-success">📤 Импорт CSV</button>
                <a href="warehouse_export.php?format=csv" class="btn btn-info">📥 Экспорт CSV</a>
                <a href="warehouse_export.php?format=json" class="btn btn-info">📥 Экспорт JSON</a>
                <button onclick="showAddForm()" class="btn btn-primary">➕ Добавить запчасть</button>
            </div>
        </div>
<?php if ($_SESSION['user_role'] === 'admin'): ?>
<a href="full_export.php?format=json" class="btn btn-warning" 
   onclick="return confirm('Экспортировать ВСЮ систему? Это может занять время.')">
   🗃️ Полный экспорт системы
</a>
<?php endif; ?>
        <!-- Предупреждение о низком запасе -->
        <?php if (!empty($low_stock)): ?>
        <div class="alert-warning">
            <strong>⚠️ Внимание! Низкий запас:</strong>
            <?php 
            $low_stock_count = count($low_stock);
            $current = 0;
            foreach (array_slice($low_stock, 0, 5) as $item): 
                $current++;
            ?>
                <?= htmlspecialchars($item['name']) ?> (осталось: <?= $item['quantity'] ?>)<?= $current < min(5, $low_stock_count) ? ', ' : '' ?>
            <?php endforeach; ?>
            <?php if ($low_stock_count > 5): ?>... и еще <?= $low_stock_count - 5 ?><?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- Таблица запчастей -->
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Артикул</th>
                        <th>Название</th>
                        <th>Категория</th>
                        <th>Цена</th>
                        <th>Количество</th>
                        <th>Мин. запас</th>
                        <th>Место</th>
                        <th>Действия</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $item): ?>
                    <tr class="<?= $item['quantity'] == 0 ? 'out-of-stock' : ($item['quantity'] <= $item['min_quantity'] ? 'low-stock' : '') ?>">
                        <td><strong><?= htmlspecialchars($item['sku']) ?></strong></td>
                        <td>
                            <strong><?= htmlspecialchars($item['name']) ?></strong>
                            <?php if (!empty($item['part_number'])): ?>
                                <br><small>Арт: <?= htmlspecialchars($item['part_number']) ?></small>
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($item['category_name'] ?? '—') ?></td>
                        <td><?= number_format($item['price'], 2) ?> ₽</td>
                        <td>
                            <strong><?= $item['quantity'] ?></strong>
                            <?php if ($item['quantity'] <= $item['min_quantity'] && $item['min_quantity'] > 0): ?>
                                <br><small style="color: #dc3545;">⚠️ Мин: <?= $item['min_quantity'] ?></small>
                            <?php endif; ?>
                        </td>
                        <td><?= $item['min_quantity'] ?: '—' ?></td>
                        <td><?= htmlspecialchars($item['location'] ?? '—') ?></td>
                        <td>
                            <div class="action-buttons">
                                <button onclick="editItem(<?= $item['id'] ?>)" class="btn btn-warning">✏️</button>
                                <?php if (in_array($_SESSION['user_role'], ['admin', 'manager'])): ?>
                                    <a href="warehouse.php?delete=<?= $item['id'] ?>" class="btn btn-danger" 
                                       onclick="return confirm('Удалить запчасть <?= htmlspecialchars($item['name']) ?>?')">🗑️</a>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Пагинация -->
        <?php if ($total_pages > 1): ?>
        <div class="pagination">
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <a href="warehouse.php?page=<?= $i ?>&search=<?= urlencode($search) ?>&category=<?= $category_filter ?>" 
                   class="page-link <?= $i == $page ? 'active' : '' ?>"><?= $i ?></a>
            <?php endfor; ?>
        </div>
        <?php endif; ?>

        <!-- Форма импорта -->
        <div id="importModal" style="display: none;">
            <div class="import-section">
                <h3>📤 Импорт запчастей из CSV</h3>
                <p>Формат CSV: Название;Категория;Артикул;Цена;Количество;Местоположение</p>
                <form method="post" enctype="multipart/form-data">
                    <input type="file" name="csv_file" accept=".csv" required>
                    <button type="submit" name="import_csv" class="btn btn-success">📤 Импортировать</button>
                    <button type="button" onclick="hideImport()" class="btn btn-secondary">Отмена</button>
                </form>
            </div>
        </div>
    </div>

    <script>
    function applyFilters() {
        const search = document.getElementById('search').value;
        const category = document.getElementById('category_filter').value;
        const url = new URL(window.location);
        
        url.searchParams.set('search', search);
        url.searchParams.set('category', category);
        url.searchParams.set('page', '1');
        
        window.location.href = url.toString();
    }
    
    function clearFilters() {
        window.location.href = 'warehouse.php';
    }
    
    function showImport() {
        document.getElementById('importModal').style.display = 'block';
    }
    
    function hideImport() {
        document.getElementById('importModal').style.display = 'none';
    }
    
    function showAddForm() {
        window.location.href = 'warehouse_edit.php';
    }
    
    function editItem(id) {
        window.location.href = 'warehouse_edit.php?id=' + id;
    }
    
    // Автопоиск при вводе
    let searchTimeout;
    document.getElementById('search').addEventListener('input', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(applyFilters, 500);
    });
    </script>
    <?php include 'templates/footer.php'; ?>
</body>
</html>