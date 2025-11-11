<?php
function safe($value) {
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

function highlight_search($text, $search) {
    if (empty($search)) return safe($text);
    return preg_replace('/(' . preg_quote($search, '/') . ')/i', '<mark>$1</mark>', safe($text));
}

function format_date($date) {
    if (!$date) return 'Не указано';
    return date('d.m.Y', strtotime($date));
}

function get_status_badge($status) {
    $statuses = [
        'pending' => ['text' => 'Ожидание', 'class' => 'bg-secondary'],
        'confirmed' => ['text' => 'Подтвержден', 'class' => 'bg-primary'],
        'in_progress' => ['text' => 'В работе', 'class' => 'bg-warning'],
        'completed' => ['text' => 'Завершен', 'class' => 'bg-success'],
        'cancelled' => ['text' => 'Отменен', 'class' => 'bg-danger']
    ];
    $info = $statuses[$status] ?? ['text' => $status, 'class' => 'bg-dark'];
    return '<span class="badge ' . $info['class'] . '">' . $info['text'] . '</span>';
}
//--070725
function get_tasks($department = '', $mechanic = '', $status = '') {
    global $pdo;
    
    $sql = "SELECT t.*, c.make, c.model, cl.first_name, cl.last_name AS client_name
            FROM tasks t
            JOIN cars c ON t.car_id = c.id
            JOIN clients cl ON t.client_id = cl.id
            WHERE 1";
    
    $params = [];
    
    if (!empty($department)) {
        $sql .= " AND t.department = ?";
        $params[] = $department;
    }
    
    if (!empty($mechanic)) {
        $sql .= " AND t.mechanic_id = ?";
        $params[] = $mechanic;
    }
    
    if (!empty($status)) {
        $statuses = explode(',', $status);
        $placeholders = implode(',', array_fill(0, count($statuses), '?'));
        $sql .= " AND t.status IN ($placeholders)";
        $params = array_merge($params, $statuses);
    }
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function get_department_text($code) {
    $departments = [
        'engine' => 'Двигатель',
        'transmission' => 'Трансмиссия',
        'electrical' => 'Электрика',
        'body' => 'Кузов'
    ];
    
    return $departments[$code] ?? $code;
}

function get_priority_text($code) {
    $priorities = [
        'low' => 'Низкий',
        'medium' => 'Средний',
        'high' => 'Высокий',
        'urgent' => 'Срочный'
    ];
    
    return $priorities[$code] ?? $code;
}
// functions.php

// Очистка ввода
function cleanInput($data, $allow_html = false) {
    if ($allow_html) {
        // Разрешаем только безопасные HTML-теги
        $allowed_tags = '<a><strong><em><ul><ol><li><p><br><h3><h4>';
        $data = strip_tags($data, $allowed_tags);
    } else {
        $data = strip_tags($data);
    }
    return trim($data);
}

// Экранирование для JS
function js_escape($str) {
    return addslashes(htmlspecialchars($str, ENT_QUOTES));
}

// Проверка прав администратора
function checkAdminSession() {
    session_start();
    if (empty($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
        header('HTTP/1.1 403 Forbidden');
        exit('Доступ запрещен');
    }
}