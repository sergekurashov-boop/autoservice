<?php
session_start();
require 'includes/db.php';
require_once 'auth_check.php';
requireAuth();

header('Content-Type: application/json');

if (!isset($_GET['license_plate'])) {
    echo json_encode(['error' => 'Госномер не указан']);
    exit;
}

$license_plate = trim($_GET['license_plate']);

if (empty($license_plate)) {
    echo json_encode(['error' => 'Госномер не может быть пустым']);
    exit;
}

// 🔧 ПРОСТАЯ РАБОЧАЯ ВЕРСИЯ ДЛЯ ТЕСТА
try {
    // Всегда возвращаем структуру для селектора
    echo json_encode([
        'license_plate' => $license_plate,
        'selection_required' => true,
        'step_by_step_selection' => true,
        'message' => 'Выберите модификацию автомобиля:',
        'selection_steps' => [
            'brand' => [
                'title' => 'Выберите марку',
                'options' => [
                    ['id' => 'vag', 'name' => 'Volkswagen Group', 'group' => true],
                    ['id' => 'vw', 'name' => 'Volkswagen', 'parent' => 'vag'],
                    ['id' => 'audi', 'name' => 'Audi', 'parent' => 'vag'],
                    ['id' => 'skoda', 'name' => 'Skoda', 'parent' => 'vag'],
                    ['id' => 'bmw', 'name' => 'BMW'],
                    ['id' => 'mb', 'name' => 'Mercedes-Benz'],
                    ['id' => 'ford', 'name' => 'Ford'],
                    ['id' => 'hyundai', 'name' => 'Hyundai'],
                    ['id' => 'kia', 'name' => 'Kia'],
                    ['id' => 'toyota', 'name' => 'Toyota']
                ]
            ]
        ]
    ]);
    
} catch (Exception $e) {
    echo json_encode(['error' => 'Ошибка системы: ' . $e->getMessage()]);
}
?>