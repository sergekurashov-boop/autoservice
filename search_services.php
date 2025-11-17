<?php
// search_services.php
session_start();
require 'includes/db.php';
require_once 'auth_check.php';
requireAuth();

header('Content-Type: application/json');

$searchTerm = $_GET['q'] ?? '';

if (empty($searchTerm)) {
    echo json_encode([]);
    exit;
}

try {
    // Поиск услуг по коду, названию или описанию
    $stmt = $pdo->prepare("
        SELECT id, name, code, price, '' as category, '' as description 
        FROM services 
        WHERE (code LIKE ? OR name LIKE ?)
        ORDER BY 
            CASE 
                WHEN code = ? THEN 1  -- Точное совпадение кода - высший приоритет
                WHEN code LIKE ? THEN 2 -- Частичное совпадение кода
                WHEN name LIKE ? THEN 3 -- Совпадение названия
                ELSE 4
            END,
            name
        LIMIT 20
    ");
    
    $searchPattern = "%{$searchTerm}%";
    $exactSearch = $searchTerm;
    
    $stmt->execute([
        $searchPattern,     // code LIKE %
        $searchPattern,     // name LIKE %  
        $exactSearch,       // code = точное совпадение
        $searchPattern,     // code LIKE %
        $searchPattern      // name LIKE %
    ]);
    
    $services = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($services);
    
} catch (PDOException $e) {
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>