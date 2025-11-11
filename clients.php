<?php
session_start();
require 'includes/db.php';
require_once 'auth_check.php';
requireAnyRole(['admin', 'manager', 'mechanic']);
include 'templates/header.php';
// Обработка поиска клиентов (AJAX)
if (isset($_GET['search'])) {
    header('Content-Type: application/json');
    
    $search = trim($_GET['search']);
    $clients = [];
    
    if (!empty($search) && strlen($search) >= 2) {
        try {
            $stmt = $conn->prepare("
                SELECT id, name, phone, client_type, company_name, contact_person 
                FROM clients 
                WHERE name LIKE ? OR phone LIKE ? OR company_name LIKE ? OR contact_person LIKE ?
                ORDER BY name 
                LIMIT 10
            ");
            $searchParam = "%$search%";
            $stmt->bind_param("ssss", $searchParam, $searchParam, $searchParam, $searchParam);
            $stmt->execute();
            $result = $stmt->get_result();
            $clients = $result->fetch_all(MYSQLI_ASSOC);
        } catch (Exception $e) {
            error_log("Search error: " . $e->getMessage());
        }
    }
    
    echo json_encode($clients, JSON_UNESCAPED_UNICODE);
    exit;
}

// Обработка добавления клиента
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_client'])) {
    $name = trim($_POST['name']);
    $phone = trim($_POST['phone']);
    $client_type = 'individual'; // По умолчанию физ. лицо
    
    $errors = [];
    
    // Валидация
    if (empty($name)) {
        $errors[] = "ФИО обязательно для заполнения";
    } elseif (strlen($name) < 2) {
        $errors[] = "ФИО должно содержать минимум 2 символа";
    }
    
    if (empty($phone)) {
        $errors[] = "Телефон обязателен для заполнения";
    } elseif (strlen(preg_replace('/[^0-9]/', '', $phone)) < 10) {
        $errors[] = "Телефон должен содержать минимум 10 цифр";
    }
    
    if (empty($errors)) {
        try {
            // Проверяем, нет ли уже клиента с таким телефоном
            $checkStmt = $conn->prepare("SELECT id FROM clients WHERE phone = ?");
            $checkStmt->bind_param("s", $phone);
            $checkStmt->execute();
            $checkResult = $checkStmt->get_result();
            
            if ($checkResult->num_rows > 0) {
                $_SESSION['error'] = "Клиент с таким телефоном уже существует";
            } else {
                // Добавляем нового клиента
                $stmt = $conn->prepare("INSERT INTO clients (name, phone, client_type) VALUES (?, ?, ?)");
                $stmt->bind_param("sss", $name, $phone, $client_type);
                
                if ($stmt->execute()) {
                    $_SESSION['success'] = "Клиент успешно добавлен!";
                    header("Location: clients.php");
                    exit;
                } else {
                    $_SESSION['error'] = "Ошибка при добавлении клиента: " . $conn->error;
                }
            }
        } catch (Exception $e) {
            $_SESSION['error'] = "Ошибка базы данных: " . $e->getMessage();
        }
    } else {
        $_SESSION['error'] = implode("<br>", $errors);
    }
}

// Получаем список всех клиентов
$all_clients = [];
try {
    $result = $conn->query("
        SELECT id, name, phone, client_type, company_name, contact_person, contract_number, inn, kpp
        FROM clients 
        ORDER BY COALESCE(company_name, name) ASC
    ");
    if ($result) {
        $all_clients = $result->fetch_all(MYSQLI_ASSOC);
    }
} catch (Exception $e) {
    error_log("Error fetching clients: " . $e->getMessage());
}

$is_selection_mode = isset($_GET['select']);
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $is_selection_mode ? 'Выбор клиента' : 'Управление клиентами' ?></title>
    <link rel="stylesheet" href="assets/css/clients.css">
	<link rel="stylesheet" href="assets/css/style.css">
   
</head>
<body>
    <div class="clients-container">
        <h1 class="page-title">
            <?php if ($is_selection_mode): ?>
                👥 Выбор клиента
            <?php else: ?>
                👥 Управление клиентами
            <?php endif; ?>
        </h1>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-error"><?= $_SESSION['error'] ?></div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success"><?= $_SESSION['success'] ?></div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <div class="toggle-buttons">
            <div class="toggle-btn active" data-target="search-section">🔍 Поиск клиента</div>
            <div class="toggle-btn" data-target="add-section">➕ Добавить клиента</div>
            <div class="toggle-btn" data-target="list-section">📋 Все клиенты (<?= count($all_clients) ?>)</div>
        </div>

        <!-- Поиск клиента -->
        <div id="search-section" class="hidden-section active">
            <div class="card">
                <div class="card-header">🔍 Поиск клиента</div>
                <div class="card-body">
                    <div class="search-container">
                        <input type="text" id="clientSearch" class="form-control" 
                               placeholder="Введите ФИО, телефон или название компании..."
                               autocomplete="off">
                        <div id="searchResults" class="search-results"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Добавление клиента -->
        <div id="add-section" class="hidden-section">
            <div class="card">
                <div class="card-header">➕ Добавить нового клиента</div>
                <div class="card-body">
                    <form method="post" id="clientForm">
                        <div class="form-group">
                            <label class="form-label">👤 ФИО *</label>
                            <input type="text" name="name" class="form-control" 
                                   placeholder="Иванов Иван Иванович" required
                                   minlength="2" maxlength="100">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">📞 Телефон *</label>
                            <input type="text" name="phone" class="form-control" 
                                   placeholder="+7 (999) 123-45-67" required
                                   minlength="5" maxlength="20">
                        </div>
                        
                        <button type="submit" name="add_client" class="btn btn-primary">
                            ✅ Добавить клиента
                        </button>
                    </form>
                </div>
            </div>
        </div>
       
<!-- Список всех клиентов -->
<div id="list-section" class="hidden-section">
    <div class="enhanced-card">
        <div class="enhanced-card-header">
            📋 Все клиенты (<?= count($all_clients) ?>)
        </div>
        <div class="card-body">
            <?php if (!empty($all_clients)): ?>
                <div class="table-responsive">
                    <table class="table-enhanced">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Тип</th>
                                <th>Наименование</th>
                                <th>Телефон</th>
                                <th>Договор</th>
                                <th>Реквизиты</th>
                                <th style="width: 140px;">Действия</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($all_clients as $client): ?>
                            <tr>
                                <td><strong><?= $client['id'] ?></strong></td>
                                <td>
                                    <?php if ($client['client_type'] === 'legal'): ?>
                                        <span class="type-badge badge-legal">Юр.лицо</span>
                                    <?php else: ?>
                                        <span class="type-badge badge-individual">Физ.лицо</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($client['client_type'] === 'legal'): ?>
                                        <strong><?= htmlspecialchars($client['company_name']) ?></strong>
                                        <?php if (!empty($client['contact_person'])): ?>
                                            <br><small>Контакт: <?= htmlspecialchars($client['contact_person']) ?></small>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <strong><?= htmlspecialchars($client['name']) ?></strong>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($client['phone'] ?? '—') ?></td>
                                <td><?= !empty($client['contract_number']) ? htmlspecialchars($client['contract_number']) : '—' ?></td>
                                <td>
                                    <?php if (!empty($client['inn'])): ?>
                                        <div><small>ИНН: <?= htmlspecialchars($client['inn']) ?></small></div>
                                    <?php endif; ?>
                                    <?php if (!empty($client['kpp'])): ?>
                                        <div><small>КПП: <?= htmlspecialchars($client['kpp']) ?></small></div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="client_edit.php?id=<?= $client['id'] ?>" class="btn-1c-warning">✏️</a>
                                        <a href="cars.php?client_id=<?= $client['id'] ?>" class="btn-1c-primary">🚗</a>
                                        <?php if ($is_selection_mode): ?>
                                            <a href="create_order.php?client_id=<?= $client['id'] ?>" class="btn-1c-success">✅ Выбрать</a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <div class="empty-state-icon">📝</div>
                    <h3>Нет клиентов в базе данных</h3>
                    <p>Добавьте первого клиента чтобы начать работу</p>
                    <div class="mt-3">
                        <button type="button" class="btn-1c-primary" onclick="switchToSection('add-section')">
                            ➕ Добавить первого клиента
                        </button>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
    <script>
    // JavaScript код из предыдущего ответа остается без изменений
    function switchToSection(sectionId) {
        document.querySelectorAll('.hidden-section').forEach(section => {
            section.classList.remove('active');
        });
        document.getElementById(sectionId).classList.add('active');
        
        document.querySelectorAll('.toggle-btn').forEach(btn => {
            btn.classList.remove('active');
        });
        document.querySelector(`[data-target="${sectionId}"]`).classList.add('active');
    }
    
    document.querySelectorAll('.toggle-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            switchToSection(this.getAttribute('data-target'));
        });
    });
    
    const searchInput = document.getElementById('clientSearch');
    const searchResults = document.getElementById('searchResults');
    
    if (searchInput && searchResults) {
        let searchTimeout;
        
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            const searchTerm = this.value.trim();
            
            if (searchTerm.length < 2) {
                searchResults.style.display = 'none';
                return;
            }
            
            searchTimeout = setTimeout(() => {
                searchClients(searchTerm);
            }, 300);
        });
        
        document.addEventListener('click', function(e) {
            if (!searchInput.contains(e.target) && !searchResults.contains(e.target)) {
                searchResults.style.display = 'none';
            }
        });
    }
    
    function searchClients(searchTerm) {
        fetch(`clients.php?search=${encodeURIComponent(searchTerm)}`)
            .then(response => response.json())
            .then(clients => {
                displaySearchResults(clients);
            })
            .catch(error => {
                console.error('Search error:', error);
                searchResults.innerHTML = '<div class="search-result-item">Ошибка поиска</div>';
                searchResults.style.display = 'block';
            });
    }
    
    function displaySearchResults(clients) {
        searchResults.innerHTML = '';
        
        if (clients.length > 0) {
            clients.forEach(client => {
                const item = document.createElement('div');
                item.className = 'search-result-item';
                
                const clientName = client.client_type === 'legal' ? 
                    (client.company_name || '') : 
                    (client.name || '');
                const clientPhone = client.phone || '';
                const contactPerson = client.contact_person || '';
                
                item.innerHTML = `
                    <div class="client-info">
                        <strong>${escapeHtml(clientName)}</strong>
                        <div class="client-details">
                            ${escapeHtml(clientPhone)}
                            ${contactPerson ? '<br>Контакт: ' + escapeHtml(contactPerson) : ''}
                        </div>
                    </div>
                `;
                
                item.addEventListener('click', function() {
                    selectClient(client);
                });
                
                searchResults.appendChild(item);
            });
        } else {
            searchResults.innerHTML = '<div class="search-result-item">Клиенты не найдены</div>';
        }
        
        searchResults.style.display = 'block';
    }
    
    function selectClient(client) {
        searchInput.value = client.client_type === 'legal' ? 
            (client.company_name || '') : 
            (client.name || '');
        searchResults.style.display = 'none';
        
        <?php if ($is_selection_mode): ?>
            window.location.href = `create_order.php?client_id=${client.id}`;
        <?php endif; ?>
    }
    
    function escapeHtml(unsafe) {
        if (!unsafe) return '';
        return unsafe
            .toString()
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }
    </script>
	<script src="assets/js/clients.js?v=<?= time() ?>"></script>
    
    <?php include 'templates/footer.php'; ?>
</body>
</html>