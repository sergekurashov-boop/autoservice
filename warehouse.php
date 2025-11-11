<?php
// Включаем отображение ошибок для отладки
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Запускаем сессию ДО любого вывода
session_start();

// 1. Подключение конфигурации и проверка авторизации
require 'includes/db.php';
require_once 'config.php';

// 3. Функции для работы со складом и категориями
function getCategories($pdo) {
    return $pdo->query("SELECT id, name FROM warehouse_categories ORDER BY name")->fetchAll();
}

function getManufacturers($pdo) {
    return $pdo->query("SELECT id, name FROM warehouse_manufacturers ORDER BY name")->fetchAll();
}

function getItem($pdo, $id) {
    $stmt = $pdo->prepare("SELECT * FROM warehouse_items WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

function saveItem($pdo, $data, $id = null) {
    if ($id) {
        // Редактирование существующей записи
        $stmt = $pdo->prepare("
            UPDATE warehouse_items SET
                name = ?,
                description = ?,
                category_id = ?,
                manufacturer_id = ?,
                price = ?,
                quantity = ?,
                min_quantity = ?,
                location = ?,
                updated_at = NOW()
            WHERE id = ?
        ");
        $params = [
            $data['name'],
            $data['description'],
            $data['category_id'],
            $data['manufacturer_id'],
            $data['price'],
            $data['quantity'],
            $data['min_quantity'],
            $data['location'],
            $id
        ];
    } else {
        // Добавление новой записи
        $sku = 'ITM-' . strtoupper(substr(uniqid(), -6));
        $stmt = $pdo->prepare("
            INSERT INTO warehouse_items 
            (sku, name, description, category_id, manufacturer_id, price, quantity, min_quantity, location)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $params = [
            $sku,
            $data['name'],
            $data['description'],
            $data['category_id'],
            $data['manufacturer_id'],
            $data['price'],
            $data['quantity'],
            $data['min_quantity'],
            $data['location']
        ];
    }
    
    return $stmt->execute($params);
}

// Функции для работы с категориями
function saveCategory($pdo, $name, $id = null) {
    if ($id) {
        $stmt = $pdo->prepare("UPDATE warehouse_categories SET name = ? WHERE id = ?");
        return $stmt->execute([$name, $id]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO warehouse_categories (name) VALUES (?)");
        return $stmt->execute([$name]);
    }
}

function deleteCategory($pdo, $id) {
    // Проверяем, используются ли категории
    $check = $pdo->prepare("SELECT COUNT(*) FROM warehouse_items WHERE category_id = ?");
    $check->execute([$id]);
    $count = $check->fetchColumn();
    
    if ($count > 0) {
        return false; // Категория используется, удалить нельзя
    }
    
    $stmt = $pdo->prepare("DELETE FROM warehouse_categories WHERE id = ?");
    return $stmt->execute([$id]);
}

// 4. Обработка действий
$action = $_GET['action'] ?? 'list';
$message = $_GET['message'] ?? null;
$error = $_GET['error'] ?? null;

// Получаем общие данные
$categories = getCategories($pdo);
$manufacturers = getManufacturers($pdo);

// Включаем шапку с навбаром (ОДИН раз)
define('ACCESS', true);

// Проверяем существование файла шаблона перед подключением
if (file_exists('templates/header.php')) {
    include 'templates/header.php';
} else {
    die('Файл шаблона header.php не найден');
}

switch ($action) {
    // Управление категориями
    case 'category_add':
    case 'category_edit':
        // Обработка формы категории
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $name = trim($_POST['name']);
            $id = $_POST['id'] ?? null;
            
            if (empty($name)) {
                $error = "Название категории не может быть пустым";
            } else {
                try {
                    saveCategory($pdo, $name, $id);
                    header('Location: warehouse.php?action=categories&message=Категория сохранена');
                    exit;
                } catch (PDOException $e) {
                    $error = "Ошибка сохранения: " . $e->getMessage();
                }
            }
        }
        
        // Получаем данные для редактирования
        $category = ['id' => null, 'name' => ''];
        if ($action === 'category_edit' && isset($_GET['id'])) {
            $stmt = $pdo->prepare("SELECT * FROM warehouse_categories WHERE id = ?");
            $stmt->execute([(int)$_GET['id']]);
            $category = $stmt->fetch();
            
            if (!$category) {
                header('Location: warehouse.php?action=categories&error=Категория не найдена');
                exit;
            }
        }
        
        // Подключаем форму категории
        if (file_exists('templates/category_form.php')) {
            include 'templates/category_form.php';
        } else {
            echo 'Файл шаблона category_form.php не найден';
        }
        break;
        
    case 'category_delete':
        if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
            header('Location: warehouse.php?action=categories&error=Недостаточно прав');
            exit;
        }
        
        if (isset($_GET['id'])) {
            try {
                $success = deleteCategory($pdo, (int)$_GET['id']);
                
                if ($success) {
                    header('Location: warehouse.php?action=categories&message=Категория удалена');
                } else {
                    header('Location: warehouse.php?action=categories&error=Невозможно удалить категорию, так как она используется');
                }
                exit;
            } catch (PDOException $e) {
                header('Location: warehouse.php?action=categories&error=Ошибка при удалении');
                exit;
            }
        }
        break;
        
    case 'categories':
        // Список категорий
        $categories = getCategories($pdo);
        if (file_exists('templates/categories_list.php')) {
            include 'templates/categories_list.php';
        } else {
            echo 'Файл шаблона categories_list.php не найден';
        }
        break;
        
    // Остальные действия (items)
    case 'add':
    case 'edit':
        // Обработка формы
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $data = [
                    'name' => $_POST['name'],
                    'description' => $_POST['description'],
                    'category_id' => $_POST['category_id'],
                    'manufacturer_id' => $_POST['manufacturer_id'],
                    'price' => (float)$_POST['price'],
                    'quantity' => (int)$_POST['quantity'],
                    'min_quantity' => (int)$_POST['min_quantity'],
                    'location' => $_POST['location']
                ];
                
                $id = isset($_POST['id']) ? (int)$_POST['id'] : null;
                saveItem($pdo, $data, $id);
                
                header('Location: warehouse.php?action=list&message=' . 
                      ($id ? 'Запчасть обновлена' : 'Запчасть добавлена'));
                exit;
            } catch (PDOException $e) {
                $error = "Ошибка сохранения: " . $e->getMessage();
            }
        }
        
        // Получаем данные для редактирования
        $item = [];
        if ($action === 'edit' && isset($_GET['id'])) {
            $item = getItem($pdo, (int)$_GET['id']);
            if (!$item) {
                header('Location: warehouse.php?error=Запчасть не найдена');
                exit;
            }
        }
        
        // Подключаем форму
        if (file_exists('templates/warehouse_form.php')) {
            include 'templates/warehouse_form.php';
        } else {
            echo 'Файл шаблона warehouse_form.php не найден';
        }
        break;
        
    case 'delete':
        if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
            header('Location: warehouse.php?error=Недостаточно прав');
            exit;
        }
        
        if (isset($_GET['id'])) {
            try {
                $pdo->prepare("DELETE FROM warehouse_items WHERE id = ?")
                   ->execute([(int)$_GET['id']]);
                header('Location: warehouse.php?action=list&message=Запчасть удалена');
                exit;
            } catch (PDOException $e) {
                header('Location: warehouse.php?error=Ошибка при удалении');
                exit;
            }
        }
        break;
        
    case 'export':
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=warehouse_export_' . date('Y-m-d') . '.csv');
        
        $output = fopen('php://output', 'w');
        fputcsv($output, [
            'Артикул', 'Название', 
            'Цена', 'Количество', 'Мин.запас', 'Место'
        ], ';');
        
        $items = $pdo->query("
            SELECT wi.sku, wi.name, wc.name AS category, wm.name AS manufacturer,
                   wi.price, wi.quantity, wi.min_quantity, wi.location
            FROM warehouse_items wi
            
        ")->fetchAll();
        
        foreach ($items as $item) {
            fputcsv($output, $item, ';');
        }
        
        fclose($output);
        exit;
        
    case 'list':
    default:
        // Пагинация
        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = 20;
        $offset = ($page - 1) * $perPage;
        
        // Фильтры
        $where = [];
        $params = [];
        
        if (!empty($_GET['category_id'])) {
            $where[] = "wi.category_id = ?";
            $params[] = (int)$_GET['category_id'];
        }
        
        if (!empty($_GET['search'])) {
            $where[] = "(wi.name LIKE ? OR wi.sku LIKE ?)";
            $params[] = '%' . $_GET['search'] . '%';
            $params[] = '%' . $_GET['search'] . '%';
        }
        
        // Основной запрос
        $sql = "
            SELECT wi.*, wc.name AS category_name, wm.name AS manufacturer_name
            FROM warehouse_items wi
            LEFT JOIN warehouse_categories wc ON wi.category_id = wc.id
            LEFT JOIN warehouse_manufacturers wm ON wi.manufacturer_id = wm.id
        ";
        
        if (!empty($where)) {
            $sql .= " WHERE " . implode(" AND ", $where);
        }
        
        $sql .= " ORDER BY wi.name LIMIT $perPage OFFSET $offset";
        
        // Получение данных
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $items = $stmt->fetchAll();
        
        // Подсчет общего количества
        $countSql = "SELECT COUNT(*) FROM warehouse_items wi";
        if (!empty($where)) {
            $countSql .= " WHERE " . implode(" AND ", $where);
        }
        
        $totalItems = $pdo->query($countSql)->fetchColumn();
        $totalPages = ceil($totalItems / $perPage);
        
        // Подключаем список
        if (file_exists('templates/warehouse_list.php')) {
            include 'templates/warehouse_list.php';
        } else {
            echo 'Файл шаблона warehouse_list.php не найден';
        }
        break;
}

// Включаем подвал
if (file_exists('templates/footer.php')) {
    include 'templates/footer.php';
} else {
    echo 'Файл шаблона footer.php не найден';
}