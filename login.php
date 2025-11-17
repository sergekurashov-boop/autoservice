<?php
session_start();
require_once 'includes/db.php';

// Если пользователь уже авторизован, перенаправляем на главную
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

// 🔐 Инициализация CSRF защиты
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// 🔐 ЗАЩИТА ОТ БРУТФОРСА
$max_attempts = 5;
$lockout_time = 900; // 15 минут
$client_ip = $_SERVER['REMOTE_ADDR'];

$login_attempts = $_SESSION['login_attempts'] ?? 0;
$lockout_until = $_SESSION['lockout_until'] ?? 0;

// Проверяем блокировку
if ($lockout_until > time()) {
    $remaining_time = ceil(($lockout_until - time()) / 60);
    $error = "Слишком много попыток входа. Попробуйте через {$remaining_time} минут.";
} elseif ($lockout_until > 0 && time() >= $lockout_until) {
    unset($_SESSION['login_attempts']);
    unset($_SESSION['lockout_until']);
    $login_attempts = 0;
}

// Обработка демо-входа (без защиты от брутфорса)
if (isset($_POST['demo_login'])) {
    $_SESSION['demo_mode'] = true;
    $_SESSION['user_id'] = 0;
    $_SESSION['username'] = 'demo_user';
    $_SESSION['user_role'] = 'demo_admin';
    $_SESSION['full_name'] = 'Demo Пользователь';
    
    // Обновляем CSRF токен
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    
    // ЛОГИРОВАНИЕ: Вход в демо-режим
    $logger->log('demo_login', 'auth');
    
    header('Location: index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    
    // 🔐 ПРОВЕРКА CSRF ТОКЕНА
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = "Ошибка безопасности. Обновите страницу.";
        error_log("CSRF token mismatch for IP: {$client_ip}");
        
        // ЛОГИРОВАНИЕ: Ошибка CSRF
        $logger->log('csrf_error', 'auth');
    } else {
        if (empty($username) || empty($password)) {
            $error = 'Введите имя пользователя и пароль';
        } else {
            // Ищем пользователя в базе
            $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch();
            
            if ($user) {
                $password_valid = false;
                $needs_migration = false;
                
                // Проверяем пароль разными способами
                if (password_verify($password, $user['password'])) {
                    // Пароль уже в современном формате
                    $password_valid = true;
                } elseif (md5($password) === $user['password']) {
                    // Пароль в старом MD5 формате - нужна миграция
                    $password_valid = true;
                    $needs_migration = true;
                }
                
                if ($password_valid) {
                    if ($user['is_active']) {
                        // Мигрируем пароль на безопасный хэш если нужно
                        if ($needs_migration) {
                            $new_hash = password_hash($password, PASSWORD_DEFAULT);
                            $update_stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                            $update_stmt->execute([$new_hash, $user['id']]);
                            
                            // ЛОГИРОВАНИЕ: Миграция пароля
                            $logger->log('password_migrated', 'auth', $user['id']);
                        }
                        
                        // Успешный вход
                        $_SESSION['user_id'] = $user['id'];
                        $_SESSION['username'] = $user['username'];
                        $_SESSION['user_role'] = $user['role'];
                        $_SESSION['full_name'] = $user['full_name'];
                        
                        // Сбрасываем защиту от брутфорса
                        unset($_SESSION['login_attempts']);
                        unset($_SESSION['lockout_until']);
                        
                        // Обновляем CSRF токен после успешного входа
                        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                        
                        error_log("Успешный вход: {$username} с IP: {$client_ip}");
                        
                        // ЛОГИРОВАНИЕ: Успешный вход
                     //   $logger->logLogin(true);
                        
                        // Перенаправляем на главную
                        header('Location: index.php');
                        exit;
                    } else {
                        $error = 'Ваш аккаунт еще не активирован администратором';
                        
                        // ЛОГИРОВАНИЕ: Попытка входа в неактивный аккаунт
                        $logger->log('login_inactive_account', 'auth', $user['id']);
                    }
                } else {
                    // ❌ Неудачная попытка входа
                    $login_attempts++;
                    $_SESSION['login_attempts'] = $login_attempts;
                    
                    error_log("Неудачная попытка входа: {$username} с IP: {$client_ip}. Попытка: {$login_attempts}");
                    
                    // ЛОГИРОВАНИЕ: Неудачная попытка входа
                    $logger->logLogin(false);
                    
                    if ($login_attempts >= $max_attempts) {
                        $_SESSION['lockout_until'] = time() + $lockout_time;
                        $error = "Слишком много неудачных попыток. Доступ заблокирован на 15 минут.";
                        
                        // ЛОГИРОВАНИЕ: Блокировка аккаунта
                        $logger->log('account_locked', 'auth');
                    } else {
                        $remaining_attempts = $max_attempts - $login_attempts;
                        $error = "Неверное имя пользователя или пароль. Осталось попыток: {$remaining_attempts}";
                    }
                    
                    // Задержка для защиты от брутфорса
                    sleep(min($login_attempts, 3));
                }
            } else {
                // ❌ Пользователь не найден
                $login_attempts++;
                $_SESSION['login_attempts'] = $login_attempts;
                
                error_log("Пользователь не найден: {$username} с IP: {$client_ip}. Попытка: {$login_attempts}");
                
                // ЛОГИРОВАНИЕ: Попытка входа несуществующего пользователя
                $logger->log('login_user_not_found', 'auth');
                
                if ($login_attempts >= $max_attempts) {
                    $_SESSION['lockout_until'] = time() + $lockout_time;
                    $error = "Слишком много неудачных попыток. Доступ заблокирован на 15 минут.";
                    
                    // ЛОГИРОВАНИЕ: Блокировка по IP
                    $logger->log('ip_locked', 'auth');
                } else {
                    $remaining_attempts = $max_attempts - $login_attempts;
                    $error = "Неверное имя пользователя или пароль. Осталось попыток: {$remaining_attempts}";
                }
                
                // Задержка для защиты от брутфорса
                sleep(min($login_attempts, 3));
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Вход - Автосервис</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
    .login-container {
        min-height: 100vh;
        background: linear-gradient(135deg, #FFFFE0 0%, #FFF8DC 50%, #FFFAF0 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 2rem;
    }

    .login-card {
        background: #fffef5;
        border: 2px solid #d4c49e;
        border-radius: 0;
        padding: 3rem;
        width: 100%;
        max-width: 500px; /* Увеличили с 420px до 500px */
        box-shadow: 0 8px 32px rgba(92, 74, 0, 0.15);
        position: relative;
    }

    .login-header {
        text-align: center;
        margin-bottom: 2.5rem;
    }

    .login-icon {
        font-size: 3rem;
        margin-bottom: 1rem;
        display: block;
    }

    .login-title {
        color: #8b6914;
        font-size: 1.8rem;
        font-weight: 600;
        margin-bottom: 0.5rem;
    }

    .login-subtitle {
        color: #8b6914;
        font-size: 1rem;
        opacity: 0.8;
    }

    .form-group {
        margin-bottom: 1.5rem;
    }

    .form-label {
        display: block;
        color: #8b6914;
        font-weight: 500;
        margin-bottom: 0.5rem;
        font-size: 0.95rem;
    }

    .form-input {
        width: 100%;
        padding: 0.875rem 1rem;
        border: 1px solid #d4c49e;
        background: #fffdf5;
        color: #5c4a00;
        font-size: 1rem;
        border-radius: 0;
        transition: all 0.3s ease;
    }

    .form-input:focus {
        outline: none;
        border-color: #8b6914;
        background: #fffef5;
        box-shadow: 0 0 0 2px rgba(139, 105, 20, 0.1);
    }

    .form-input::placeholder {
        color: #b0a47c;
    }

    .login-btn {
        width: 100%;
        padding: 1rem;
        background: #8b6914;
        color: white;
        border: none;
        font-size: 1.1rem;
        font-weight: 600;
        cursor: pointer;
        border-radius: 0;
        transition: all 0.3s ease;
        margin-top: 1rem;
    }

    .login-btn:hover {
        background: #7a5a10;
        transform: translateY(-1px);
    }

    .demo-btn {
        width: 100%;
        padding: 1rem;
        background: #28a745;
        color: white;
        border: none;
        font-size: 1.1rem;
        font-weight: 600;
        cursor: pointer;
        border-radius: 0;
        transition: all 0.3s ease;
        margin-top: 0.5rem;
    }

    .demo-btn:hover {
        background: #218838;
        transform: translateY(-1px);
    }

    .login-footer {
        text-align: center;
        margin-top: 2rem;
        padding-top: 1.5rem;
        border-top: 1px solid #e6d8a8;
    }

    .register-link {
        color: #8b6914;
        text-decoration: none;
        font-weight: 500;
        transition: color 0.3s ease;
    }

    .register-link:hover {
        color: #5c4a00;
        text-decoration: underline;
    }

    .error-message {
        background: #f8d7da;
        color: #721c24;
        padding: 0.875rem 1rem;
        border: 1px solid #f5c6cb;
        margin-bottom: 1.5rem;
        border-radius: 0;
        text-align: center;
        font-weight: 500;
    }

    .success-message {
        background: #d4edda;
        color: #155724;
        padding: 0.875rem 1rem;
        border: 1px solid #c3e6cb;
        margin-bottom: 1.5rem;
        border-radius: 0;
        text-align: center;
        font-weight: 500;
    }

    .demo-info {
        background: #e7f3ff;
        border: 1px solid #b3d9ff;
        color: #004085;
        padding: 1rem;
        margin: 1.5rem 0;
        border-radius: 0;
        text-align: center;
    }

    .security-info {
        background: #fff3cd;
        border: 1px solid #ffeaa7;
        color: #856404;
        padding: 0.75rem;
        margin: 1rem 0;
        border-radius: 0;
        text-align: center;
        font-size: 0.85rem;
    }

    .demo-warning {
        background: #fff3cd;
        border: 1px solid #ffeaa7;
        color: #856404;
        padding: 0.75rem;
        margin: 1rem 0;
        border-radius: 0;
        text-align: center;
        font-size: 0.8rem;
    }

    /* Анимации */
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .login-card {
        animation: fadeIn 0.6s ease-out;
    }

    /* Адаптивность */
    @media (max-width: 480px) {
        .login-container {
            padding: 1rem;
        }
        
        .login-card {
            padding: 2rem 1.5rem;
        }
        
        .login-title {
            font-size: 1.5rem;
        }
    }
</style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <span class="login-icon">🔐</span>
                <h1 class="login-title">Вход в систему</h1>
                <p class="login-subtitle">Автосервис - профессиональное управление</p>
				<p><small>ВСЕ ДАННЫЕ В СИСТЕМЕ ЯВЛЯЮТСЯ ТЕСТОВЫМИ И НОСЯТ ИСКЛЮЧИТЕЛЬНО ДЕМОНСТРАЦИОННЫЙ ХАРАКТЕР. ЛЮБЫЕ СОВПАДЕНИЯ С РЕАЛЬНЫМИ ЛИЦАМИ ИЛИ ОРГАНИЗАЦИЯМИ СЛУЧАЙНЫ.</small></p>
            </div>

            <?php if ($error): ?>
                <div class="error-message"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <?php if (isset($_GET['registered']) && $_GET['registered'] == 'true'): ?>
                <div class="success-message">Регистрация успешна! Теперь вы можете войти в систему.</div>
            <?php endif; ?>

            <!-- Информация о безопасности -->
            <?php if ($login_attempts > 0): ?>
                <div class="security-info">
                    🔒 Неудачных попыток: <?= $login_attempts ?>/<?= $max_attempts ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                
                <div class="form-group">
                    <label for="username" class="form-label">👤 Имя пользователя</label>
                    <input type="text" class="form-input" id="username" name="username" placeholder="Введите ваше имя пользователя" 
                           value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="password" class="form-label">🔑 Пароль</label>
                    <input type="password" class="form-input" id="password" name="password" placeholder="Введите ваш пароль" required>
                </div>
                
                <button type="submit" name="login" class="login-btn" 
                        <?= ($lockout_until > time()) ? 'disabled' : '' ?>>
                    🚀 Войти в систему
                </button>
            </form>

            <!-- Блок демо-режима -->
            <div class="demo-info">
                <strong>🚀 Хотите просто посмотреть систему?</strong>
                <p style="margin: 8px 0 0 0; font-size: 0.9em;">
                    Демо-режим позволяет тестировать все функции без сохранения данных
                </p>
				<p><small>🛡️ <strong>ДЕМО-РЕЖИМ:</strong> Все данные являются тестовыми. Изменения не сохраняются в базе данных. Совпадения с реальными лицами случайны.</small></p>
            </div>

            <form method="POST" action="">
                <button type="submit" name="demo_login" class="demo-btn">
                    🎮 Войти в демо-режим
                </button>
            </form>
            
            <div class="login-footer">
                <p>Нет аккаунта? <a href="register.php" class="register-link">Создайте новый аккаунт</a></p>
            </div>
        </div>
    </div>

    <script>
        // Плавное появление элементов
        document.addEventListener('DOMContentLoaded', function() {
            const inputs = document.querySelectorAll('.form-input');
            inputs.forEach((input, index) => {
                input.style.opacity = '0';
                input.style.transform = 'translateY(10px)';
                
                setTimeout(() => {
                    input.style.transition = 'all 0.4s ease';
                    input.style.opacity = '1';
                    input.style.transform = 'translateY(0)';
                }, 100 * index);
            });
        });
    </script>
</body>
</html>