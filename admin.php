<?php
session_start();
require 'includes/db.php';
require_once 'auth_check.php';
requireAnyRole(['admin']);

// Создаем таблицу для реквизитов компании если её нет
$conn->query("
    CREATE TABLE IF NOT EXISTS company_details (
        id INT PRIMARY KEY AUTO_INCREMENT,
        company_name VARCHAR(255) NOT NULL,
        legal_name VARCHAR(255),
        inn VARCHAR(20),
        kpp VARCHAR(20),
        ogrn VARCHAR(20),
        legal_address TEXT,
        actual_address TEXT,
        phone VARCHAR(50),
        email VARCHAR(100),
        website VARCHAR(255),
        bank_name VARCHAR(255),
        bank_account VARCHAR(50),
        corr_account VARCHAR(50),
        bic VARCHAR(20),
        director_name VARCHAR(255),
        accountant_name VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )
");

// Обработка сохранения системных настроек и реквизитов
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Сохранение основных настроек
    if (isset($_POST['save_settings'])) {
        $errors = [];
        
        // Получаем и валидируем данные
        $company_name = trim($_POST['company_name'] ?? '');
        $company_phone = trim($_POST['company_phone'] ?? '');
        $company_email = trim($_POST['company_email'] ?? '');
        $company_address = trim($_POST['company_address'] ?? '');
        $default_tax_rate = floatval($_POST['default_tax_rate'] ?? 0);
        $currency = trim($_POST['currency'] ?? '₽');
        
        // Валидация
        if (empty($company_name)) {
            $errors[] = "Название компании обязательно";
        }
        
        if (!empty($company_email) && !filter_var($company_email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Некорректный email адрес";
        }
        
        if ($default_tax_rate < 0 || $default_tax_rate > 100) {
            $errors[] = "Налоговая ставка должна быть от 0 до 100%";
        }
        
        if (empty($errors)) {
            try {
                // Сохраняем настройки в таблицу system_settings
                $stmt = $conn->prepare("
                    INSERT INTO system_settings (setting_key, setting_value) 
                    VALUES 
                    ('company_name', ?),
                    ('company_phone', ?),
                    ('company_email', ?),
                    ('company_address', ?),
                    ('default_tax_rate', ?),
                    ('currency', ?)
                    ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)
                ");
                
                $stmt->bind_param(
                    "ssssds", 
                    $company_name, 
                    $company_phone, 
                    $company_email, 
                    $company_address, 
                    $default_tax_rate, 
                    $currency
                );
                
                if ($stmt->execute()) {
                    $_SESSION['success'] = "✅ Настройки системы успешно сохранены!";
                } else {
                    $_SESSION['error'] = "❌ Ошибка при сохранении настроек: " . $conn->error;
                }
            } catch (Exception $e) {
                $_SESSION['error'] = "❌ Ошибка базы данных: " . $e->getMessage();
            }
        } else {
            $_SESSION['error'] = implode("<br>", $errors);
        }
    }
    
    // Сохранение реквизитов компании
    if (isset($_POST['save_company_details'])) {
        try {
            $stmt = $conn->prepare("
                INSERT INTO company_details (
                    company_name, legal_name, inn, kpp, ogrn, legal_address, actual_address,
                    phone, email, website, bank_name, bank_account, corr_account, bic,
                    director_name, accountant_name
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                    company_name = VALUES(company_name),
                    legal_name = VALUES(legal_name),
                    inn = VALUES(inn),
                    kpp = VALUES(kpp),
                    ogrn = VALUES(ogrn),
                    legal_address = VALUES(legal_address),
                    actual_address = VALUES(actual_address),
                    phone = VALUES(phone),
                    email = VALUES(email),
                    website = VALUES(website),
                    bank_name = VALUES(bank_name),
                    bank_account = VALUES(bank_account),
                    corr_account = VALUES(corr_account),
                    bic = VALUES(bic),
                    director_name = VALUES(director_name),
                    accountant_name = VALUES(accountant_name)
            ");
            
            $stmt->bind_param(
                "ssssssssssssssss",
                $_POST['company_name'],
                $_POST['legal_name'],
                $_POST['inn'],
                $_POST['kpp'],
                $_POST['ogrn'],
                $_POST['legal_address'],
                $_POST['actual_address'],
                $_POST['phone'],
                $_POST['email'],
                $_POST['website'],
                $_POST['bank_name'],
                $_POST['bank_account'],
                $_POST['corr_account'],
                $_POST['bic'],
                $_POST['director_name'],
                $_POST['accountant_name']
            );
            
            if ($stmt->execute()) {
                $_SESSION['success'] = "✅ Реквизиты компании успешно сохранены!";
            } else {
                $_SESSION['error'] = "❌ Ошибка при сохранении реквизитов: " . $conn->error;
            }
        } catch (Exception $e) {
            $_SESSION['error'] = "❌ Ошибка базы данных: " . $e->getMessage();
        }
    }
}

// Обработка добавления пользователя
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_user'])) {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $full_name = trim($_POST['full_name'] ?? '');
    $role = trim($_POST['role'] ?? 'mechanic');
    $phone = trim($_POST['phone'] ?? '');
    
    $errors = [];
    
    // Валидация
    if (empty($username) || strlen($username) < 3) {
        $errors[] = "Логин должен содержать минимум 3 символа";
    }
    
    if (empty($password) || strlen($password) < 6) {
        $errors[] = "Пароль должен содержать минимум 6 символов";
    }
    
    if (empty($full_name) || strlen($full_name) < 2) {
        $errors[] = "ФИО должно содержать минимум 2 символа";
    }
    
    if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Некорректный email адрес";
    }
    
    if (empty($errors)) {
        try {
            // Проверяем, нет ли уже пользователя с таким логином
            $checkStmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
            $checkStmt->bind_param("s", $username);
            $checkStmt->execute();
            $checkResult = $checkStmt->get_result();
            
            if ($checkResult->num_rows > 0) {
                $_SESSION['error'] = "❌ Пользователь с таким логином уже существует";
            } else {
                // Добавляем нового пользователя
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("INSERT INTO users (username, password, email, full_name, role, phone) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("ssssss", $username, $hashed_password, $email, $full_name, $role, $phone);
                
                if ($stmt->execute()) {
                    $_SESSION['success'] = "✅ Пользователь успешно добавлен!";
                    header("Location: admin.php");
                    exit;
                } else {
                    $_SESSION['error'] = "❌ Ошибка при добавлении пользователя: " . $conn->error;
                }
            }
        } catch (Exception $e) {
            $_SESSION['error'] = "❌ Ошибка базы данных: " . $e->getMessage();
        }
    } else {
        $_SESSION['error'] = implode("<br>", $errors);
    }
}

// Обработка удаления пользователя
if (isset($_GET['delete_user'])) {
    $user_id = intval($_GET['delete_user']);
    
    if ($user_id > 0 && $user_id != $_SESSION['user_id']) {
        try {
            $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
            $stmt->bind_param("i", $user_id);
            
            if ($stmt->execute()) {
                $_SESSION['success'] = "✅ Пользователь удален!";
            } else {
                $_SESSION['error'] = "❌ Ошибка при удалении пользователя";
            }
        } catch (Exception $e) {
            $_SESSION['error'] = "❌ Ошибка базы данных: " . $e->getMessage();
        }
    } else {
        $_SESSION['error'] = "❌ Нельзя удалить собственный аккаунт";
    }
    
    header("Location: admin.php");
    exit;
}

// Получаем текущие настройки системы
$system_settings = [];
try {
    $result = $conn->query("SELECT setting_key, setting_value FROM system_settings");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $system_settings[$row['setting_key']] = $row['setting_value'];
        }
    }
} catch (Exception $e) {
    error_log("Error fetching system settings: " . $e->getMessage());
}

// Получаем реквизиты компании
$company_details = [];
try {
    $result = $conn->query("SELECT * FROM company_details ORDER BY id DESC LIMIT 1");
    if ($result && $result->num_rows > 0) {
        $company_details = $result->fetch_assoc();
    }
} catch (Exception $e) {
    error_log("Error fetching company details: " . $e->getMessage());
}

// Получаем список всех пользователей
$all_users = [];
try {
    $result = $conn->query("
        SELECT id, username, email, full_name, role, is_active, created_at
        FROM users 
        ORDER BY role, username ASC
    ");
    if ($result) {
        $all_users = $result->fetch_all(MYSQLI_ASSOC);
    }
} catch (Exception $e) {
    error_log("Error fetching users: " . $e->getMessage());
}

// Статистика системы
$stats = [];
try {
    // Количество клиентов
    $result = $conn->query("SELECT COUNT(*) as count FROM clients");
    $stats['total_clients'] = $result ? $result->fetch_assoc()['count'] : 0;
    
    // Количество автомобилей
    $result = $conn->query("SELECT COUNT(*) as count FROM cars");
    $stats['total_cars'] = $result ? $result->fetch_assoc()['count'] : 0;
    
    // Количество заказов
    $result = $conn->query("SELECT COUNT(*) as count FROM orders WHERE status != 'completed'");
    $stats['active_orders'] = $result ? $result->fetch_assoc()['count'] : 0;
    
    // Количество завершенных заказов за месяц
    $result = $conn->query("SELECT COUNT(*) as count FROM orders WHERE status = 'completed' AND MONTH(created_at) = MONTH(CURRENT_DATE())");
    $stats['completed_this_month'] = $result ? $result->fetch_assoc()['count'] : 0;
    
} catch (Exception $e) {
    error_log("Error fetching stats: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>⚙️ Панель управления - Autoservice</title> 
	    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .admin-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
            border-left: 4px solid #007bff;
        }
        
        .stat-card.success { border-left-color: #28a745; }
        .stat-card.warning { border-left-color: #ffc107; }
        .stat-card.danger { border-left-color: #dc3545; }
        
        .stat-number {
            font-size: 2.5em;
            font-weight: bold;
            color: #333;
            margin-bottom: 5px;
        }
        
        .stat-label {
            color: #666;
            font-size: 0.9em;
        }
        
        .toggle-buttons {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        
        .toggle-btn {
            padding: 12px 20px;
            background: #f8f9fa;
            border: 2px solid #dee2e6;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: 500;
        }
        
        .toggle-btn.active {
            background: #007bff;
            color: white;
            border-color: #007bff;
        }
        
        .hidden-section {
            display: none;
        }
        
        .hidden-section.active {
            display: block;
        }
        
        .card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            overflow: hidden;
        }
        
        .card-header {
            background: #f8f9fa;
            padding: 15px 20px;
            border-bottom: 1px solid #dee2e6;
            font-weight: 600;
            font-size: 1.1em;
        }
        
        .card-body {
            padding: 20px;
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        }
        
        .form-grid-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        
        @media (max-width: 768px) {
            .form-grid-2 {
                grid-template-columns: 1fr;
            }
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
            color: #333;
        }
        
        .form-control {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
        }
        
        textarea.form-control {
            min-height: 80px;
            resize: vertical;
        }
        
        .section-title {
            font-size: 1.2em;
            font-weight: 600;
            margin: 25px 0 15px 0;
            padding-bottom: 10px;
            border-bottom: 2px solid #007bff;
            color: #333;
        }
        
        .table-responsive {
            overflow-x: auto;
        }
        
        .table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .table th,
        .table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #dee2e6;
        }
        
        .table th {
            background: #f8f9fa;
            font-weight: 600;
        }
        
        .badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.8em;
            font-weight: 500;
        }
        
        .badge-admin { background: #dc3545; color: white; }
        .badge-manager { background: #ffc107; color: black; }
        .badge-mechanic { background: #17a2b8; color: white; }
        .badge-reception { background: #6f42c1; color: white; }
        
        .action-buttons {
            display: flex;
            gap: 5px;
        }
        
        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            font-size: 14px;
            text-align: center;
            transition: all 0.3s ease;
        }
        
        .btn-primary { background: #007bff; color: white; }
        .btn-success { background: #28a745; color: white; }
        .btn-danger { background: #dc3545; color: white; }
        .btn-warning { background: #ffc107; color: black; }
        .btn-secondary { background: #6c757d; color: white; }
        
        .btn:hover {
            opacity: 0.9;
            transform: translateY(-1px);
        }
        
        .alert {
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
        }
        
        .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        
        .status-active { color: #28a745; }
        .status-inactive { color: #dc3545; }
    </style>
</head>
<body>
    <div class="admin-container">
        <h1 class="page-title">⚙️ Административная панель Autoservice</h1> 
          <div class="subtitle"><a href="index.php"><h3>🏠 ПАНЕЛЬ УПРАВЛЕНИЯ НАЗАД, НА ГЛАВНУЮ</h3></a></div>
            </div>
           
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-error"><?= $_SESSION['error'] ?></div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success"><?= $_SESSION['success'] ?></div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <!-- Статистика системы -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?= $stats['total_clients'] ?? 0 ?></div>
                <div class="stat-label">👥 Всего клиентов</div>
            </div>
            <div class="stat-card success">
                <div class="stat-number"><?= $stats['total_cars'] ?? 0 ?></div>
                <div class="stat-label">🚗 Автомобилей в базе</div>
            </div>
            <div class="stat-card warning">
                <div class="stat-number"><?= $stats['active_orders'] ?? 0 ?></div>
                <div class="stat-label">🔧 Активных заказов</div>
            </div>
            <div class="stat-card danger">
                <div class="stat-number"><?= $stats['completed_this_month'] ?? 0 ?></div>
                <div class="stat-label">✅ Завершено в этом месяце</div>
            </div>
        </div>

        <div class="toggle-buttons">
            <div class="toggle-btn active" data-target="settings-section">🏢 Основные настройки</div>
            <div class="toggle-btn" data-target="company-section">🏛️ Реквизиты компании</div>
            <div class="toggle-btn" data-target="users-section">👥 Управление пользователями</div>
            <div class="toggle-btn" data-target="system-section">📊 Системная информация</div>
        </div>

        <!-- Основные настройки -->
        <div id="settings-section" class="hidden-section active">
            <div class="card">
                <div class="card-header">🏢 Основные настройки системы</div>
                <div class="card-body">
                    <form method="post">
                        <div class="form-grid">
                            <div class="form-group">
                                <label class="form-label">🏢 Название компании *</label>
                                <input type="text" name="company_name" class="form-control" 
                                       value="<?= htmlspecialchars($system_settings['company_name'] ?? '') ?>" 
                                       placeholder="ООО 'Автосервис'" required>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">📞 Телефон компании</label>
                                <input type="text" name="company_phone" class="form-control" 
                                       value="<?= htmlspecialchars($system_settings['company_phone'] ?? '') ?>" 
                                       placeholder="+7 (999) 123-45-67">
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">📧 Email компании</label>
                                <input type="email" name="company_email" class="form-control" 
                                       value="<?= htmlspecialchars($system_settings['company_email'] ?? '') ?>" 
                                       placeholder="info@autoservice.ru">
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">💼 Валюта</label>
                                <select name="currency" class="form-control">
                                    <option value="₽" <?= ($system_settings['currency'] ?? '₽') === '₽' ? 'selected' : '' ?>>Рубли (₽)</option>
                                    <option value="$" <?= ($system_settings['currency'] ?? '') === '$' ? 'selected' : '' ?>>Доллары ($)</option>
                                    <option value="€" <?= ($system_settings['currency'] ?? '') === '€' ? 'selected' : '' ?>>Евро (€)</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">📊 Налоговая ставка (%)</label>
                                <input type="number" name="default_tax_rate" class="form-control" 
                                       value="<?= htmlspecialchars($system_settings['default_tax_rate'] ?? '20') ?>" 
                                       min="0" max="100" step="0.1">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">📍 Адрес компании</label>
                            <textarea name="company_address" class="form-control" rows="3" 
                                      placeholder="г. Москва, ул. Ленина, д. 1"><?= htmlspecialchars($system_settings['company_address'] ?? '') ?></textarea>
                        </div>
                        
                        <button type="submit" name="save_settings" class="btn btn-success">
                            💾 Сохранить настройки
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Реквизиты компании -->
        <div id="company-section" class="hidden-section">
            <div class="card">
                <div class="card-header">🏛️ Реквизиты компании для печатных форм</div>
                <div class="card-body">
                    <form method="post">
                        <input type="hidden" name="save_company_details" value="1">
                        
                        <div class="section-title">📋 Основная информация</div>
                        <div class="form-grid-2">
                            <div class="form-group">
                                <label class="form-label">🏢 Торговое название *</label>
                                <input type="text" name="company_name" class="form-control" 
                                       value="<?= htmlspecialchars($company_details['company_name'] ?? '') ?>" 
                                       placeholder="Автосервис 'Профи'" required>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">⚖️ Юридическое название</label>
                                <input type="text" name="legal_name" class="form-control" 
                                       value="<?= htmlspecialchars($company_details['legal_name'] ?? '') ?>" 
                                       placeholder="ООО 'Автосервис Профи'">
                            </div>
                        </div>
                        
                        <div class="form-grid-2">
                            <div class="form-group">
                                <label class="form-label">🔢 ИНН</label>
                                <input type="text" name="inn" class="form-control" 
                                       value="<?= htmlspecialchars($company_details['inn'] ?? '') ?>" 
                                       placeholder="1234567890" maxlength="12">
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">🔢 КПП</label>
                                <input type="text" name="kpp" class="form-control" 
                                       value="<?= htmlspecialchars($company_details['kpp'] ?? '') ?>" 
                                       placeholder="123456789" maxlength="9">
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">🔢 ОГРН</label>
                                <input type="text" name="ogrn" class="form-control" 
                                       value="<?= htmlspecialchars($company_details['ogrn'] ?? '') ?>" 
                                       placeholder="1234567890123" maxlength="13">
                            </div>
                        </div>
                        
                        <div class="section-title">📍 Адреса</div>
                        <div class="form-grid-2">
                            <div class="form-group">
                                <label class="form-label">⚖️ Юридический адрес</label>
                                <textarea name="legal_address" class="form-control" rows="3" 
                                          placeholder="г. Москва, ул. Ленина, д. 1"><?= htmlspecialchars($company_details['legal_address'] ?? '') ?></textarea>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">🏢 Фактический адрес</label>
                                <textarea name="actual_address" class="form-control" rows="3" 
                                          placeholder="г. Москва, ул. Ленина, д. 1"><?= htmlspecialchars($company_details['actual_address'] ?? '') ?></textarea>
                            </div>
                        </div>
                        
                        <div class="section-title">🏦 Банковские реквизиты</div>
                        <div class="form-grid-2">
                            <div class="form-group">
                                <label class="form-label">🏦 Название банка</label>
                                <input type="text" name="bank_name" class="form-control" 
                                       value="<?= htmlspecialchars($company_details['bank_name'] ?? '') ?>" 
                                       placeholder="ПАО 'Сбербанк'">
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">💰 Расчетный счет</label>
                                <input type="text" name="bank_account" class="form-control" 
                                       value="<?= htmlspecialchars($company_details['bank_account'] ?? '') ?>" 
                                       placeholder="40702810123456789012" maxlength="20">
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">💳 Корреспондентский счет</label>
                                <input type="text" name="corr_account" class="form-control" 
                                       value="<?= htmlspecialchars($company_details['corr_account'] ?? '') ?>" 
                                       placeholder="30101810234567890123" maxlength="20">
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">🔢 БИК банка</label>
                                <input type="text" name="bic" class="form-control" 
                                       value="<?= htmlspecialchars($company_details['bic'] ?? '') ?>" 
                                       placeholder="123456789" maxlength="9">
                            </div>
                        </div>
                        
                        <div class="section-title">👥 Руководство</div>
                        <div class="form-grid-2">
                            <div class="form-group">
                                <label class="form-label">👨‍💼 Директор</label>
                                <input type="text" name="director_name" class="form-control" 
                                       value="<?= htmlspecialchars($company_details['director_name'] ?? '') ?>" 
                                       placeholder="Иванов Иван Иванович">
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">👩‍💼 Главный бухгалтер</label>
                                <input type="text" name="accountant_name" class="form-control" 
                                       value="<?= htmlspecialchars($company_details['accountant_name'] ?? '') ?>" 
                                       placeholder="Петрова Мария Сергеевна">
                            </div>
                        </div>
                        
                        <div class="section-title">📞 Контакты</div>
                        <div class="form-grid-2">
                            <div class="form-group">
                                <label class="form-label">📞 Телефон</label>
                                <input type="text" name="phone" class="form-control" 
                                       value="<?= htmlspecialchars($company_details['phone'] ?? '') ?>" 
                                       placeholder="+7 (999) 123-45-67">
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">📧 Email</label>
                                <input type="email" name="email" class="form-control" 
                                       value="<?= htmlspecialchars($company_details['email'] ?? '') ?>" 
                                       placeholder="info@autoservice.ru">
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">🌐 Веб-сайт</label>
                                <input type="text" name="website" class="form-control" 
                                       value="<?= htmlspecialchars($company_details['website'] ?? '') ?>" 
                                       placeholder="https://autoservice.ru">
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-success">
                            💾 Сохранить реквизиты компании
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Управление пользователями -->
        <div id="users-section" class="hidden-section">
            <!-- Добавление пользователя -->
            <div class="card">
                <div class="card-header">➕ Добавить пользователя</div>
                <div class="card-body">
                    <form method="post">
                        <div class="form-grid">
                            <div class="form-group">
                                <label class="form-label">👤 Логин *</label>
                                <input type="text" name="username" class="form-control" 
                                       placeholder="user123" required minlength="3">
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">🔒 Пароль *</label>
                                <input type="password" name="password" class="form-control" 
                                       placeholder="Минимум 6 символов" required minlength="6">
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">📧 Email</label>
                                <input type="email" name="email" class="form-control" 
                                       placeholder="user@autoservice.ru">
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">📛 ФИО *</label>
                                <input type="text" name="full_name" class="form-control" 
                                       placeholder="Иванов Иван Иванович" required minlength="2">
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">📞 Телефон</label>
                                <input type="text" name="phone" class="form-control" 
                                       placeholder="+7 (999) 123-45-67">
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">🎭 Роль *</label>
                                <select name="role" class="form-control" required>
                                    <option value="mechanic">🔧 Механик</option>
                                    <option value="reception">💁‍♀️ Приёмщик</option>
                                    <option value="manager">💼 Менеджер</option>
                                    <option value="admin">👑 Администратор</option>
                                </select>
                            </div>
                        </div>
                        
                        <button type="submit" name="add_user" class="btn btn-success">
                            ✅ Добавить пользователя
                        </button>
                    </form>
                </div>
            </div>

            <!-- Список пользователей -->
            <div class="card">
                <div class="card-header">📋 Список пользователей (<?= count($all_users) ?>)</div>
                <div class="card-body">
                    <?php if (!empty($all_users)): ?>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Логин</th>
                                        <th>ФИО</th>
                                        <th>Email</th>
                                        <th>Телефон</th>
                                        <th>Роль</th>
                                        <th>Статус</th>
                                        <th>Последний вход</th>
                                        <th>Действия</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($all_users as $user): ?>
                                    <tr>
                                        <td><?= $user['id'] ?></td>
                                        <td>
                                            <strong><?= htmlspecialchars($user['username']) ?></strong>
                                            <?php if ($user['id'] == $_SESSION['user_id']): ?>
                                                <br><small class="status-active">(это вы)</small>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= htmlspecialchars($user['full_name']) ?></td>
                                        <td><?= htmlspecialchars($user['email'] ?? '—') ?></td>
                                        <td><?= htmlspecialchars($user['phone'] ?? '—') ?></td>
                                        <td>
                                            <?php if ($user['role'] === 'admin'): ?>
                                                <span class="badge badge-admin">👑 Админ</span>
                                            <?php elseif ($user['role'] === 'manager'): ?>
                                                <span class="badge badge-manager">💼 Менеджер</span>
                                            <?php elseif ($user['role'] === 'reception'): ?>
                                                <span class="badge badge-reception">💁‍♀️ Приёмщик</span>
                                            <?php else: ?>
                                                <span class="badge badge-mechanic">🔧 Механик</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($user['is_active']): ?>
                                                <span class="status-active">✅ Активен</span>
                                            <?php else: ?>
                                                <span class="status-inactive">❌ Неактивен</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= $user['last_login'] ? date('d.m.Y H:i', strtotime($user['last_login'])) : 'Никогда' ?></td>
                                        <td>
                                            <div class="action-buttons">
                                                <a href="user_edit.php?id=<?= $user['id'] ?>" class="btn btn-warning">✏️</a>
                                                <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                                    <a href="admin.php?delete_user=<?= $user['id'] ?>" class="btn btn-danger" 
                                                       onclick="return confirm('Удалить пользователя <?= htmlspecialchars($user['username']) ?>?')">🗑️</a>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div style="text-align: center; padding: 40px; color: #666;">
                            <div style="font-size: 3em; margin-bottom: 20px;">👥</div>
                            <h3>Нет пользователей</h3>
                            <p>Добавьте первого пользователя системы</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Системная информация -->
        <div id="system-section" class="hidden-section">
            <div class="card">
                <div class="card-header">📊 Системная информация</div>
                <div class="card-body">
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
                        <div>
                            <h4>🖥️ Сервер</h4>
                            <div style="background: #f8f9fa; padding: 15px; border-radius: 6px;">
                                <div><strong>PHP:</strong> <?= phpversion() ?></div>
                                <div><strong>Версия БД:</strong> <?= $conn->server_info ?></div>
                                <div><strong>ОС:</strong> <?= php_uname('s') ?> <?= php_uname('r') ?></div>
                                <div><strong>Память:</strong> <?= round(memory_get_usage(true) / 1024 / 1024, 2) ?> MB</div>
                                <div><strong>Время:</strong> <?= date('d.m.Y H:i:s') ?></div>
                            </div>
                        </div>
                        
                        <div>
                            <h4>📈 Статистика БД</h4>
                            <div style="background: #f8f9fa; padding: 15px; border-radius: 6px;">
                                <div><strong>Пользователи:</strong> <?= count($all_users) ?></div>
                                <div><strong>Клиенты:</strong> <?= $stats['total_clients'] ?? 0 ?></div>
                                <div><strong>Автомобили:</strong> <?= $stats['total_cars'] ?? 0 ?></div>
                                <div><strong>Заказы:</strong> <?= $stats['active_orders'] ?? 0 ?> активных</div>
                                <div><strong>Завершено:</strong> <?= $stats['completed_this_month'] ?? 0 ?> в этом месяце</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
    function switchToSection(sectionId) {
        // Скрываем все секции
        document.querySelectorAll('.hidden-section').forEach(section => {
            section.classList.remove('active');
        });
        
        // Показываем выбранную секцию
        document.getElementById(sectionId).classList.add('active');
        
        // Обновляем активные кнопки
        document.querySelectorAll('.toggle-btn').forEach(btn => {
            btn.classList.remove('active');
        });
        document.querySelector(`[data-target="${sectionId}"]`).classList.add('active');
    }
    
    // Добавляем обработчики для кнопок переключения
    document.querySelectorAll('.toggle-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            switchToSection(this.getAttribute('data-target'));
        });
    });
    </script>
</body>
</html>