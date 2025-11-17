<?php
// permissions.php

// Права доступа для разных ролей
$permissions = [
    'admin' => [
        'manage_users',
        'manage_orders',
        'manage_clients',
        'manage_cars',
        'manage_services',
        'manage_mechanics',
        'manage_parts',
        'view_reports',
        'manage_booking',
        'manage_content'
    ],
    'manager' => [
        'manage_orders',
        'manage_clients',
        'manage_cars',
        'manage_services',
        'view_reports',
        'manage_booking'
    ],
    'mechanic' => [
        'view_orders',
        'update_order_status',
        'view_clients',
        'view_cars',
        'view_services'
    ],
    'reception' => [
        'manage_booking',
        'view_clients',
        'view_cars',
        'create_orders'
    ]
];

// Функция для проверки прав
function hasPermission($permission) {
    global $permissions;
    
    if (!isset($_SESSION['user_role'])) {
        return false;
    }
    
    $role = $_SESSION['user_role'];
    return in_array($permission, $permissions[$role]);
}

// Функция для проверки прав с выводом ошибки
function requirePermission($permission) {
    if (!hasPermission($permission)) {
        http_response_code(403);
        die('Доступ запрещен. Недостаточно прав.');
    }
}