<?php
require '../includes/db.php';

// Параметры запроса
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 10;
$offset = ($page - 1) * $perPage;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'name';
$order = isset($_GET['order']) ? $_GET['order'] : 'asc';

// Валидация параметров сортировки
$allowedSorts = ['id', 'name', 'phone'];
$sort = in_array($sort, $allowedSorts) ? $sort : 'name';
$order = in_array(strtolower($order), ['asc', 'desc']) ? $order : 'asc';

// Формирование SQL-запроса
$sql = "SELECT * FROM clients";
$params = [];
$types = '';

if (!empty($search)) {
    $sql .= " WHERE name LIKE ? OR phone LIKE ?";
    $searchTerm = "%$search%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $types .= 'ss';
}

$sql .= " ORDER BY $sort $order LIMIT ? OFFSET ?";
$params[] = $perPage;
$params[] = $offset;
$types .= 'ii';

// Подготовка и выполнение запроса
$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$clients = $result->fetch_all(MYSQLI_ASSOC);

// Получение общего количества записей
$countSql = "SELECT COUNT(*) as total FROM clients";
if (!empty($search)) {
    $countSql .= " WHERE name LIKE ? OR phone LIKE ?";
}
$countStmt = $conn->prepare($countSql);
if (!empty($search)) {
    $countStmt->bind_param('ss', $searchTerm, $searchTerm);
}
$countStmt->execute();
$totalResult = $countStmt->get_result()->fetch_assoc();
$total = $totalResult['total'];
$totalPages = ceil($total / $perPage);

// Формирование ответа
header('Content-Type: application/json');
echo json_encode([
    'clients' => $clients,
    'pagination' => [
        'currentPage' => $page,
        'totalPages' => $totalPages,
        'totalItems' => $total
    ]
]);
?>