<?php
// autoservice/employee_edit.php
require 'includes/db.php';
session_start();

// Проверка авторизации
if (!isset($_SESSION['user_id'])) {
    $_SESSION['error'] = "❌ Требуется авторизация";
    header("Location: login.php");
    exit;
}

define('ACCESS', true);

// МРОТ 2025
define('MIN_SALARY', 22440);

$id = (int)$_GET['id'] ?? 0;

if ($id === 0) {
    $_SESSION['error'] = "❌ Неверный идентификатор сотрудника";
    header("Location: salaries.php");
    exit;
}

// Получаем данные сотрудника
$stmt = $conn->prepare("SELECT * FROM employees WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$employee = $result->fetch_assoc();

if (!$employee) {
    $_SESSION['error'] = "❌ Сотрудник не найден";
    header("Location: salaries.php");
    exit;
}

// Обработка сохранения изменений
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_employee'])) {
    $name = trim($_POST['name'] ?? '');
    $position = trim($_POST['position'] ?? '');
    $salary_type = $_POST['salary_type'] ?? 'fixed';
    $base_rate = floatval(str_replace(',', '.', $_POST['base_rate'] ?? 0));
    $percentage_rate = floatval(str_replace(',', '.', $_POST['percentage_rate'] ?? 0));
    $sales_percentage = floatval(str_replace(',', '.', $_POST['sales_percentage'] ?? 0));
    $active = isset($_POST['active']) ? 1 : 0;

    // Валидация
    $errors = [];
    
    if (empty($name) || strlen($name) < 2) {
        $errors[] = "ФИО должно содержать минимум 2 символа";
    }
    
    if (empty($position) || strlen($position) < 2) {
        $errors[] = "Должность должна содержать минимум 2 символа";
    }
    
    if ($base_rate < MIN_SALARY) {
        $errors[] = "Базовая ставка не может быть меньше МРОТ (" . number_format(MIN_SALARY, 0, '.', ' ') . " ₽)";
    }

    if ($base_rate > 1000000) {
        $errors[] = "Базовая ставка не может превышать 1 000 000 ₽";
    }

    if ($percentage_rate < 0 || $percentage_rate > 100) {
        $errors[] = "Процент от работ должен быть от 0 до 100";
    }

    if ($sales_percentage < 0 || $sales_percentage > 100) {
        $errors[] = "Процент от продаж должен быть от 0 до 100";
    }

    if (empty($errors)) {
        $stmt = $conn->prepare("
            UPDATE employees 
            SET name = ?, position = ?, salary_type = ?, base_rate = ?, 
                percentage_rate = ?, sales_percentage = ?, active = ?, updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->bind_param("sssdddii", $name, $position, $salary_type, $base_rate, 
                         $percentage_rate, $sales_percentage, $active, $id);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = "✅ Данные сотрудника обновлены";
            header("Location: salaries.php");
            exit;
        } else {
            error_log("Ошибка обновления сотрудника: " . $conn->error);
            $_SESSION['error'] = "❌ Ошибка при обновлении данных";
        }
    } else {
        $_SESSION['error'] = implode("<br>", $errors);
    }
    
    // Обновляем данные для отображения
    $employee['name'] = $name;
    $employee['position'] = $position;
    $employee['salary_type'] = $salary_type;
    $employee['base_rate'] = $base_rate;
    $employee['percentage_rate'] = $percentage_rate;
    $employee['sales_percentage'] = $sales_percentage;
    $employee['active'] = $active;
}

include 'templates/header.php';
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Редактирование сотрудника - Автосервис</title>
    <link rel="stylesheet" href="assets/css/services.css?v=<?= time() ?>">
    <style>
        .salary-type-options {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin: 15px 0;
        }
        .salary-option {
            border: 2px solid #e6d8a8;
            border-radius: 8px;
            padding: 15px;
            cursor: pointer;
            transition: all 0.3s ease;
            background: white;
        }
        .salary-option:hover {
            border-color: #8b6914;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .salary-option.selected {
            border-color: #28a745;
            background: #f8fff9;
            box-shadow: 0 4px 12px rgba(40, 167, 69, 0.2);
        }
        .rate-fields {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-top: 20px;
            border-left: 4px solid #007bff;
        }
        .field-hidden {
            display: none !important;
        }
        .mrot-hint {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 4px;
            padding: 8px 12px;
            margin-top: 5px;
            font-size: 0.875em;
        }
        
        @media (max-width: 768px) {
            .salary-type-options {
                grid-template-columns: 1fr;
            }
            .rate-fields .row {
                flex-direction: column;
            }
        }
    </style>
</head>
<body class="services-container">
   
    
    <div class="container mt-4">
        <div class="header-compact">
            <h1 class="page-title-compact">✏️ Редактирование сотрудника</h1>
            <div class="header-actions-compact">
                <a href="salaries.php" class="action-btn-compact">
                    <span class="action-icon">←</span>
                    <span class="action-label">Назад к зарплатам</span>
                </a>
            </div>
        </div>
        
        <!-- Вывод сообщений -->
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert-enhanced alert-danger"><?= $_SESSION['error'] ?></div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <!-- Форма редактирования -->
        <div class="enhanced-card">
            <div class="enhanced-card-header">📝 Данные сотрудника</div>
            <div class="card-body">
                <form method="post" id="employeeForm">
                    <!-- Основная информация -->
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">👤 ФИО сотрудника *</label>
                                <input type="text" name="name" class="form-control" 
                                       value="<?= htmlspecialchars($employee['name']) ?>" 
                                       required minlength="2" maxlength="100">
                                <div class="form-text">Минимум 2 символа</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">💼 Должность *</label>
                                <input type="text" name="position" class="form-control" 
                                       value="<?= htmlspecialchars($employee['position']) ?>" 
                                       required minlength="2" maxlength="100">
                                <div class="form-text">Минимум 2 символа</div>
                            </div>
                        </div>
                    </div>

                    <!-- Тип оплаты -->
                    <div class="mb-3">
                        <label class="form-label">💰 Система оплаты</label>
                        <div class="salary-type-options">
                            <div class="salary-option <?= $employee['salary_type'] === 'percentage' ? 'selected' : '' ?>" 
                                 onclick="selectSalaryType('percentage')">
                                <input type="radio" name="salary_type" value="percentage" 
                                       <?= $employee['salary_type'] === 'percentage' ? 'checked' : '' ?> 
                                       style="display: none;">
                                <div class="form-check">
                                    <h6>🔧 Процент от работ</h6>
                                    <small class="text-muted">МРОТ + процент от выполненных работ</small>
                                </div>
                            </div>
                            
                            <div class="salary-option <?= $employee['salary_type'] === 'sales' ? 'selected' : '' ?>" 
                                 onclick="selectSalaryType('sales')">
                                <input type="radio" name="salary_type" value="sales" 
                                       <?= $employee['salary_type'] === 'sales' ? 'checked' : '' ?> 
                                       style="display: none;">
                                <div class="form-check">
                                    <h6>🛍️ Продажи запчастей</h6>
                                    <small class="text-muted">МРОТ + процент от продаж запчастей</small>
                                </div>
                            </div>
                            
                            <div class="salary-option <?= $employee['salary_type'] === 'fixed' ? 'selected' : '' ?>" 
                                 onclick="selectSalaryType('fixed')">
                                <input type="radio" name="salary_type" value="fixed" 
                                       <?= $employee['salary_type'] === 'fixed' ? 'checked' : '' ?> 
                                       style="display: none;">
                                <div class="form-check">
                                    <h6>🏢 Фиксированная ЗП</h6>
                                    <small class="text-muted">Постоянный оклад</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Поля ставок -->
                    <div class="rate-fields">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">📊 Базовая ставка (₽) *</label>
                                    <input type="number" step="0.01" name="base_rate" class="form-control" 
                                           value="<?= number_format($employee['base_rate'], 2, '.', '') ?>" 
                                           required min="<?= MIN_SALARY ?>" max="1000000">
                                    <div class="mrot-hint">
                                        💡 <strong>МРОТ в 2025 году:</strong> <?= number_format(MIN_SALARY, 0, '.', ' ') ?> ₽
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3" id="percentageRateField">
                                    <label class="form-label">📈 Процент от работ (%)</label>
                                    <input type="number" step="0.01" name="percentage_rate" class="form-control" 
                                           value="<?= number_format($employee['percentage_rate'], 2, '.', '') ?>" 
                                           min="0" max="100">
                                    <div class="form-text">Для типа "Процент от работ"</div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3" id="salesPercentageField">
                                    <label class="form-label">🛒 Процент от продаж (%)</label>
                                    <input type="number" step="0.01" name="sales_percentage" class="form-control" 
                                           value="<?= number_format($employee['sales_percentage'], 2, '.', '') ?>" 
                                           min="0" max="100">
                                    <div class="form-text">Для типа "Продажи запчастей"</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Статус -->
                    <div class="mb-3">
                        <div class="form-check">
                            <input type="checkbox" name="active" class="form-check-input" 
                                   id="active" <?= $employee['active'] ? 'checked' : '' ?>>
                            <label class="form-check-label" for="active">
                                ✅ Активный сотрудник
                            </label>
                        </div>
                    </div>

                    <!-- История изменений -->
                    <div class="mt-4">
                        <details>
                            <summary>📊 История изменений</summary>
                            <div class="mt-2 p-3 bg-light rounded">
                                <small><strong>Создан:</strong> <?= date('d.m.Y H:i', strtotime($employee['created_at'])) ?></small><br>
                                <small><strong>Последнее изменение:</strong> 
                                    <?= !empty($employee['updated_at']) && $employee['updated_at'] != '0000-00-00 00:00:00' 
                                        ? date('d.m.Y H:i', strtotime($employee['updated_at'])) 
                                        : 'не изменялся' ?>
                                </small>
                            </div>
                        </details>
                    </div>

                    <div class="form-actions">
                        <button type="submit" name="update_employee" class="btn-1c-primary">
                            💾 Сохранить изменения
                        </button>
                        <a href="salaries.php" class="btn-1c-outline">❌ Отмена</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
    function selectSalaryType(type) {
        // Убираем выделение со всех options
        document.querySelectorAll('.salary-option').forEach(option => {
            option.classList.remove('selected');
        });
        
        // Выделяем выбранный option
        event.currentTarget.classList.add('selected');
        
        // Устанавливаем значение radio
        document.querySelector(`input[value="${type}"]`).checked = true;
        
        // Обновляем видимость полей
        updateRateFieldsVisibility(type);
    }
    
    function updateRateFieldsVisibility(type) {
        const percentageField = document.getElementById('percentageRateField');
        const salesField = document.getElementById('salesPercentageField');
        
        // Сбрасываем все поля к видимым
        percentageField.classList.remove('field-hidden');
        salesField.classList.remove('field-hidden');
        
        // Скрываем поля в зависимости от типа оплаты
        switch(type) {
            case 'percentage':
                salesField.classList.add('field-hidden');
                break;
            case 'sales':
                percentageField.classList.add('field-hidden');
                break;
            case 'fixed':
                percentageField.classList.add('field-hidden');
                salesField.classList.add('field-hidden');
                break;
        }
    }
    
    // Инициализация при загрузке
    document.addEventListener('DOMContentLoaded', function() {
        const currentType = document.querySelector('input[name="salary_type"]:checked').value;
        updateRateFieldsVisibility(currentType);
        
        // Подтверждение при отправке формы
        document.getElementById('employeeForm').addEventListener('submit', function(e) {
            const baseRate = parseFloat(document.querySelector('input[name="base_rate"]').value);
            const minSalary = <?= MIN_SALARY ?>;
            
            if (baseRate < minSalary) {
                if (!confirm('⚠️ Базовая ставка ниже МРОТ (<?= number_format(MIN_SALARY, 0, '.', ' ') ?> ₽). Продолжить сохранение?')) {
                    e.preventDefault();
                    return false;
                }
            }
            
            const percentageRate = parseFloat(document.querySelector('input[name="percentage_rate"]').value);
            const salesPercentage = parseFloat(document.querySelector('input[name="sales_percentage"]').value);
            
            if (percentageRate > 50) {
                if (!confirm('⚠️ Вы установили высокий процент от работ (>50%). Продолжить?')) {
                    e.preventDefault();
                    return false;
                }
            }
            
            if (salesPercentage > 30) {
                if (!confirm('⚠️ Вы установили высокий процент от продаж (>30%). Продолжить?')) {
                    e.preventDefault();
                    return false;
                }
            }
            
            return true;
        });
    });
    </script>

    <?php include 'templates/footer.php'; ?>
</body>
</html>