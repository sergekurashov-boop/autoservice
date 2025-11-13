<?php
session_start();
require 'includes/db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Получаем JSON данные
$input = json_decode(file_get_contents('php://input'), true);

// Проверка CSRF токена
if (!isset($input['csrf_token']) || $input['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'CSRF token validation failed']);
    exit;
}

if (!isset($input['order_id']) || !isset($input['part_id']) || !isset($input['new_status'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit;
}

$order_id = (int)$input['order_id'];
$part_id = (int)$input['part_id'];
$new_status = $input['new_status'];
$allowed_statuses = ['reserved', 'issued', 'used', 'returned'];

if (!in_array($new_status, $allowed_statuses)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid status']);
    exit;
}

try {
    $conn->begin_transaction();
    
    // Получаем текущие данные о запчасти
    $stmt = $conn->prepare("
        SELECT op.*, p.name as part_name, op.source_type, op.quantity, op.warehouse_item_id,
               w.current_stock, w.name as warehouse_name
        FROM order_parts op 
        LEFT JOIN parts p ON op.part_id = p.id 
        LEFT JOIN warehouse_items w ON op.warehouse_item_id = w.id
        WHERE op.order_id = ? AND op.part_id = ?
    ");
    $stmt->bind_param("ii", $order_id, $part_id);
    $stmt->execute();
    $part = $stmt->get_result()->fetch_assoc();
    
    if (!$part) {
        throw new Exception("Part not found");
    }
    
    $old_status = $part['issue_status'];
    
    // Обновляем статус
    $stmt = $conn->prepare("UPDATE order_parts SET issue_status = ? WHERE order_id = ? AND part_id = ?");
    $stmt->bind_param("sii", $new_status, $order_id, $part_id);
    $stmt->execute();
    
    // Если запчасть со склада, обновляем остатки
    if ($part['source_type'] == 'service_warehouse' && $part['warehouse_item_id']) {
        $stock_result = updateWarehouseStock($part, $old_status, $new_status, $conn);
    }
    
    // Логируем изменение
    $stmt = $conn->prepare("
        INSERT INTO part_status_log (order_id, part_id, old_status, new_status, changed_by, changed_at, notes) 
        VALUES (?, ?, ?, ?, ?, NOW(), ?)
    ");
    $user_id = $_SESSION['user_id'] ?? 0;
    
    // Формируем заметку для лога
    $notes = "";
    if ($part['source_type'] == 'service_warehouse' && $part['warehouse_item_id']) {
        $notes = "Склад: " . ($part['warehouse_name'] ?? 'N/A') . ". ";
        if (isset($stock_result['action'])) {
            $notes .= "Действие: " . $stock_result['action'] . ". ";
        }
        if (isset($stock_result['old_stock'])) {
            $notes .= "Остатки: " . $stock_result['old_stock'] . " → " . $stock_result['new_stock'];
        }
    }
    
    $stmt->bind_param("iissis", $order_id, $part_id, $old_status, $new_status, $user_id, $notes);
    $stmt->execute();
    
    $conn->commit();
    
    echo json_encode([
        'success' => true, 
        'message' => 'Status updated successfully',
        'new_status' => $new_status,
        'old_status' => $old_status,
        'stock_updated' => isset($stock_result),
        'stock_info' => $stock_result ?? null
    ]);
    
} catch (Exception $e) {
    $conn->rollback();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}

function updateWarehouseStock($part, $old_status, $new_status, $conn) {
    $quantity = $part['quantity'];
    $warehouse_item_id = $part['warehouse_item_id'];
    $result = [];
    
    // Получаем текущие остатки
    $stmt = $conn->prepare("SELECT current_stock FROM warehouse_items WHERE id = ?");
    $stmt->bind_param("i", $warehouse_item_id);
    $stmt->execute();
    $stock_result = $stmt->get_result()->fetch_assoc();
    $old_stock = $stock_result['current_stock'];
    
    $result['old_stock'] = $old_stock;
    $result['warehouse_item_id'] = $warehouse_item_id;
    
    // Логика изменения остатков
    if ($old_status == 'reserved' && $new_status == 'issued') {
        // При выдаче уменьшаем остатки
        $new_stock = $old_stock - $quantity;
        $stmt = $conn->prepare("UPDATE warehouse_items SET current_stock = ? WHERE id = ?");
        $stmt->bind_param("ii", $new_stock, $warehouse_item_id);
        $stmt->execute();
        $result['action'] = 'Списание при выдаче';
        $result['new_stock'] = $new_stock;
    }
    elseif ($old_status == 'issued' && $new_status == 'used') {
        // При использовании - остатки уже списаны при выдаче, ничего не делаем
        $result['action'] = 'Подтверждение использования';
        $result['new_stock'] = $old_stock;
    }
    elseif ($old_status == 'issued' && $new_status == 'returned') {
        // При возврате увеличиваем остатки
        $new_stock = $old_stock + $quantity;
        $stmt = $conn->prepare("UPDATE warehouse_items SET current_stock = ? WHERE id = ?");
        $stmt->bind_param("ii", $new_stock, $warehouse_item_id);
        $stmt->execute();
        $result['action'] = 'Возврат на склад';
        $result['new_stock'] = $new_stock;
    }
    elseif ($old_status == 'reserved' && $new_status == 'returned') {
        // Отмена резерва - ничего не меняем в остатках
        $result['action'] = 'Отмена резерва';
        $result['new_stock'] = $old_stock;
    }
    elseif ($old_status == 'returned' && $new_status == 'issued') {
        // Повторная выдача после возврата - уменьшаем остатки
        $new_stock = $old_stock - $quantity;
        $stmt = $conn->prepare("UPDATE warehouse_items SET current_stock = ? WHERE id = ?");
        $stmt->bind_param("ii", $new_stock, $warehouse_item_id);
        $stmt->execute();
        $result['action'] = 'Повторная выдача';
        $result['new_stock'] = $new_stock;
    }
    elseif ($old_status == 'used' && $new_status == 'returned') {
        // Возврат использованной запчасти (брак и т.д.)
        $new_stock = $old_stock + $quantity;
        $stmt = $conn->prepare("UPDATE warehouse_items SET current_stock = ? WHERE id = ?");
        $stmt->bind_param("ii", $new_stock, $warehouse_item_id);
        $stmt->execute();
        $result['action'] = 'Возврат использованной запчасти';
        $result['new_stock'] = $new_stock;
    }
    else {
        $result['action'] = 'Без изменений остатков';
        $result['new_stock'] = $old_stock;
    }
    
    return $result;
}
?>