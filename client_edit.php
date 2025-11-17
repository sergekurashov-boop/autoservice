<?php
require 'includes/db.php';
session_start();
define('ACCESS', true);
include 'templates/header.php';

// Проверка авторизации
if (!isset($_SESSION['user_id']) && !isset($_SESSION['demo_mode'])) {
    header("Location: login.php");
    exit;
}

// Проверка ID клиента
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error'] = "Неверный ID клиента";
    header("Location: clients.php");
    exit;
}

$client_id = (int)$_GET['id'];

// Получение данных клиента
$stmt = $conn->prepare("SELECT * FROM clients WHERE id = ?");
$stmt->bind_param("i", $client_id);
$stmt->execute();
$client = $stmt->get_result()->fetch_assoc();

if (!$client) {
    $_SESSION['error'] = "Клиент не найден";
    header("Location: clients.php");
    exit;
}

// Обработка формы
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_client'])) {
    $name = trim($_POST['name']);
    $phone = trim($_POST['phone']);
    $email = trim($_POST['email']);

    // Валидация
    if (empty($name)) {
        $error = "Имя клиента обязательно для заполнения";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) && !empty($email)) {
        $error = "Некорректный email";
    } else {
        // Обновление данных
        $stmt = $conn->prepare("UPDATE clients SET name = ?, phone = ?, email = ? WHERE id = ?");
        $stmt->bind_param("sssi", $name, $phone, $email, $client_id);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = "Данные клиента обновлены";
            header("Location: clients.php");
            exit;
        } else {
            $error = "Ошибка базы данных: " . $stmt->error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Редактирование клиента</title>
    <style>
        .edit-container {
            background: #fff8dc;
            min-height: 100vh;
            padding: 20px 0;
        }
        .enhanced-card {
            background: white;
            border: 2px solid #8b6914;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(139, 105, 20, 0.1);
            margin-bottom: 24px;
            transition: all 0.3s ease;
            max-width: 600px;
            margin: 0 auto;
        }
        .enhanced-card:hover {
            box-shadow: 0 6px 20px rgba(139, 105, 20, 0.15);
            transform: translateY(-2px);
        }
        .enhanced-card-header {
            background: linear-gradient(135deg, #8b6914, #6b5200);
            color: white;
            border-radius: 10px 10px 0 0;
            padding: 16px 20px;
            font-weight: 600;
            font-size: 1.2em;
            text-align: center;
        }
        .card-body {
            padding: 30px;
        }
        .btn-1c-primary {
            background: linear-gradient(135deg, #8b6914, #6b5200);
            border: none;
            color: white;
            font-weight: 500;
            padding: 12px 24px;
            border-radius: 8px;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }
        .btn-1c-primary:hover {
            background: linear-gradient(135deg, #6b5200, #8b6914);
            color: white;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(139, 105, 20, 0.3);
        }
        .btn-1c-secondary {
            background: linear-gradient(135deg, #6c757d, #5a6268);
            border: none;
            color: white;
            font-weight: 500;
            padding: 12px 24px;
            border-radius: 8px;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }
        .btn-1c-secondary:hover {
            background: linear-gradient(135deg, #5a6268, #6c757d);
            color: white;
            transform: translateY(-1px);
        }
        .form-control {
            border: 2px solid #8b6914;
            border-radius: 6px;
            padding: 12px 16px;
            background: #fffdf5;
            transition: all 0.3s ease;
            font-size: 1rem;
        }
        .form-control:focus {
            border-color: #6b5200;
            box-shadow: 0 0 0 2px rgba(139, 105, 20, 0.25);
            background: #fffef5;
            outline: none;
        }
        .form-label {
            font-weight: 600;
            color: #6b5200;
            margin-bottom: 8px;
            display: block;
        }
        .alert-enhanced {
            border: none;
            border-radius: 10px;
            padding: 16px 20px;
            font-weight: 500;
            border-left: 4px solid;
            margin-bottom: 20px;
        }
        .alert-danger {
            background: #f8d7da;
            color: #721c24;
            border-left-color: #dc3545;
        }
        .page-title {
            color: #076cd9;
            font-size: 2rem;
            font-weight: 600;
            margin-bottom: 24px;
            text-align: center;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .action-buttons {
            display: flex;
            gap: 12px;
            justify-content: flex-end;
            margin-top: 30px;
            flex-wrap: wrap;
        }
        @media (max-width: 576px) {
            .action-buttons {
                flex-direction: column;
            }
            .btn-1c-primary, .btn-1c-secondary {
                width: 100%;
            }
            .enhanced-card {
                margin: 0 15px;
            }
            .card-body {
                padding: 20px;
            }
        }
        .form-icon {
            font-size: 1.2em;
            margin-right: 8px;
        }
    </style>
</head>
<body class="edit-container">
    <div class="container mt-4">
        <h1 class="page-title">✏️ Редактирование клиента</h1>
        
        <div class="enhanced-card">
            <div class="enhanced-card-header">
                👤 Редактирование данных клиента
            </div>
            <div class="card-body">
                <?php if(isset($error)): ?>
                    <div class="alert-enhanced alert-danger"><?= $error ?></div>
                <?php endif; ?>
                
                <form method="post">
                    <div class="form-group">
                        <label class="form-label">
                            <span class="form-icon">👤</span>ФИО:
                        </label>
                        <input type="text" class="form-control" name="name" 
                               value="<?= htmlspecialchars($client['name']) ?>" 
                               placeholder="Введите ФИО клиента" required>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">
                                    <span class="form-icon">📞</span>Телефон:
                                </label>
                                <input type="tel" class="form-control" name="phone" 
                                       value="<?= htmlspecialchars($client['phone']) ?>" 
                                       placeholder="+7 (XXX) XXX-XX-XX">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">
                                    <span class="form-icon">📧</span>Email:
                                </label>
                                <input type="email" class="form-control" name="email" 
                                       value="<?= htmlspecialchars($client['email']) ?>" 
                                       placeholder="email@example.com">
                            </div>
                        </div>
                    </div>
                    
                    <div class="action-buttons">
                        <a href="clients.php" class="btn-1c-secondary">
                            ↩️ Назад к списку
                        </a>
                        <button type="submit" name="update_client" class="btn-1c-primary">
                            ✅ Сохранить изменения
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Плавное появление формы
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('form');
            const inputs = document.querySelectorAll('.form-control');
            
            form.style.opacity = '0';
            form.style.transform = 'translateY(20px)';
            
            setTimeout(() => {
                form.style.transition = 'all 0.5s ease';
                form.style.opacity = '1';
                form.style.transform = 'translateY(0)';
            }, 100);
            
            inputs.forEach((input, index) => {
                input.style.opacity = '0';
                input.style.transform = 'translateX(-10px)';
                
                setTimeout(() => {
                    input.style.transition = 'all 0.4s ease';
                    input.style.opacity = '1';
                    input.style.transform = 'translateX(0)';
                }, 200 + (index * 50));
            });
        });
    </script>
</body>
</html>