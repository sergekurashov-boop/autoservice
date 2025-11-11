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

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $password_confirm = trim($_POST['password_confirm']);
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);

    // 🔐 ПРОВЕРКА CSRF ТОКЕНА
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = "Ошибка безопасности. Обновите страницу.";
    } else {
        // Валидация данных
        if (empty($username) || empty($password) || empty($password_confirm) || empty($full_name)) {
            $error = 'Все обязательные поля должны быть заполнены';
        } elseif (strlen($username) < 3) {
            $error = 'Имя пользователя должно содержать минимум 3 символа';
        } elseif (strlen($password) < 6) {
            $error = 'Пароль должен содержать минимум 6 символов';
        } elseif ($password !== $password_confirm) {
            $error = 'Пароли не совпадают';
        } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
            $error = 'Имя пользователя может содержать только латинские буквы, цифры и подчеркивания';
        } else {
            try {
                // Проверяем, не занят ли username
                $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
                $stmt->execute([$username]);
                
                if ($stmt->fetch()) {
                    $error = 'Это имя пользователя уже занято';
                } else {
                    // Проверяем email, если указан
                    if (!empty($email)) {
                        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
                        $stmt->execute([$email]);
                        if ($stmt->fetch()) {
                            $error = 'Этот email уже используется';
                        }
                    }

                    if (empty($error)) {
                        // Хэшируем пароль
                        $password_hash = password_hash($password, PASSWORD_DEFAULT);
                        
                        // Создаем пользователя (по умолчанию неактивен, требует активации админом)
                        $stmt = $pdo->prepare("INSERT INTO users (username, password, full_name, email, role, is_active, created_at) 
                                              VALUES (?, ?, ?, ?, 'user', 0, NOW())");
                        $stmt->execute([$username, $password_hash, $full_name, $email]);
                        
                        $success = 'Регистрация успешна! Ваш аккаунт ожидает активации администратором.';
                        
                        // Логируем регистрацию
                        error_log("Новая регистрация: {$username} ({$full_name})");
                    }
                }
            } catch (Exception $e) {
                $error = "Ошибка базы данных: " . $e->getMessage();
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
    <title>Регистрация - Автосервис</title>
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
            max-width: 500px;
            box-shadow: 0 8px 32px rgba(92, 74, 0, 0.15);
        }

        .login-header {
            text-align: center;
            margin-bottom: 2rem;
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

        .info-message {
            background: #e7f3ff;
            color: #004085;
            padding: 1rem;
            margin: 1.5rem 0;
            border-radius: 0;
            text-align: center;
            border: 1px solid #b3d9ff;
        }

        .password-requirements {
            font-size: 0.8rem;
            color: #666;
            margin-top: 0.5rem;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .login-card {
            animation: fadeIn 0.6s ease-out;
        }

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
                <span class="login-icon">👤</span>
                <h1 class="login-title">Регистрация</h1>
                <p class="login-subtitle">Создание нового аккаунта</p>
            </div>

            <?php if ($error): ?>
                <div class="error-message"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="success-message"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>

            <div class="info-message">
                <strong>ℹ️ После регистрации аккаунт требует активации администратором</strong>
            </div>

            <form method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                
                <div class="form-group">
                    <label for="full_name" class="form-label">👤 ФИО *</label>
                    <input type="text" class="form-input" id="full_name" name="full_name" 
                           value="<?= htmlspecialchars($_POST['full_name'] ?? '') ?>" 
                           placeholder="Введите ваше полное имя" required>
                </div>

                <div class="form-group">
                    <label for="username" class="form-label">🔑 Имя пользователя *</label>
                    <input type="text" class="form-input" id="username" name="username" 
                           value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" 
                           placeholder="Только латинские буквы и цифры" required>
                    <div class="password-requirements">Минимум 3 символа, только a-z, 0-9, _</div>
                </div>
                
                <div class="form-group">
                    <label for="email" class="form-label">📧 Email</label>
                    <input type="email" class="form-input" id="email" name="email" 
                           value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" 
                           placeholder="example@domain.com">
                </div>
                
                <div class="form-group">
                    <label for="password" class="form-label">🔒 Пароль *</label>
                    <input type="password" class="form-input" id="password" name="password" 
                           placeholder="Минимум 6 символов" required>
                    <div class="password-requirements">Минимум 6 символов</div>
                </div>

                <div class="form-group">
                    <label for="password_confirm" class="form-label">🔒 Подтверждение пароля *</label>
                    <input type="password" class="form-input" id="password_confirm" name="password_confirm" 
                           placeholder="Повторите пароль" required>
                </div>
                
                <button type="submit" name="register" class="login-btn">📝 Зарегистрироваться</button>
            </form>
            
            <div class="login-footer">
                <p>Уже есть аккаунт? <a href="login.php" class="register-link">Войдите в систему</a></p>
            </div>

            <div style="margin-top: 2rem; padding-top: 1rem; border-top: 1px solid #e6d8a8;">
                <p style="font-size: 0.75rem; color: #666; text-align: center;">
                    <strong>ℹ️ ВНИМАНИЕ:</strong> Все данные в системе являются тестовыми и носят исключительно демонстрационный характер. Любые совпадения с реальными лицами или организациями случайны.
                </p>
            </div>
        </div>
    </div>

    <script>
        // Валидация пароля в реальном времени
        document.addEventListener('DOMContentLoaded', function() {
            const password = document.getElementById('password');
            const confirm = document.getElementById('password_confirm');
            
            function validatePassword() {
                if (password.value !== confirm.value) {
                    confirm.style.borderColor = '#dc3545';
                } else {
                    confirm.style.borderColor = '#28a745';
                }
            }
            
            password.addEventListener('input', validatePassword);
            confirm.addEventListener('input', validatePassword);
        });
    </script>
</body>
</html>