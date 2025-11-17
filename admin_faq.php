<?php
require 'includes/db.php';
session_start();


// Обработка действий
$action = $_GET['action'] ?? 'list';
$message = '';
$error = '';

// Функции для работы с FAQ
function getFaqItems($conn) {
    return $conn->query("SELECT * FROM faq ORDER BY sort_order, id")->fetch_all(MYSQLI_ASSOC);
}

function getFaqItem($conn, $id) {
    $stmt = $conn->prepare("SELECT * FROM faq WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

function saveFaqItem($conn, $data) {
    if (empty($data['question']) || empty($data['answer'])) {
        return "Вопрос и ответ не могут быть пустыми";
    }
    
    if (isset($data['id'])) {
        // Редактирование
        $stmt = $conn->prepare("
            UPDATE faq SET 
                question = ?, 
                answer = ?, 
                sort_order = ?, 
                is_active = ? 
            WHERE id = ?
        ");
        $stmt->bind_param(
            "ssiii", 
            $data['question'], 
            $data['answer'], 
            $data['sort_order'], 
            $data['is_active'], 
            $data['id']
        );
    } else {
        // Добавление
        $stmt = $conn->prepare("
            INSERT INTO faq (question, answer, sort_order, is_active) 
            VALUES (?, ?, ?, ?)
        ");
        $stmt->bind_param(
            "ssii", 
            $data['question'], 
            $data['answer'], 
            $data['sort_order'], 
            $data['is_active']
        );
    }
    
    if (!$stmt->execute()) {
        return "Ошибка сохранения: " . $conn->error;
    }
    
    return true;
}

function deleteFaqItem($conn, $id) {
    $stmt = $conn->prepare("DELETE FROM faq WHERE id = ?");
    $stmt->bind_param("i", $id);
    return $stmt->execute();
}

// Обработка POST-запросов
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'question' => trim($_POST['question']),
        'answer' => trim($_POST['answer']),
        'sort_order' => (int)$_POST['sort_order'],
        'is_active' => isset($_POST['is_active']) ? 1 : 0
    ];
    
    if (!empty($_POST['id'])) {
        $data['id'] = (int)$_POST['id'];
    }
    
    $result = saveFaqItem($conn, $data);
    
    if ($result === true) {
        $message = isset($data['id']) ? "Вопрос обновлен!" : "Вопрос добавлен!";
    } else {
        $error = $result;
    }
}

// Обработка удаления
if (isset($_GET['delete'])) {
    if (deleteFaqItem($conn, (int)$_GET['delete'])) {
        $message = "Вопрос удален!";
    } else {
        $error = "Ошибка при удалении вопроса";
    }
}

// Включаем шапку
define('ACCESS', true);
include 'templates/header.php';
?>

<div class="container mt-4">
    <h2>Управление FAQ</h2>
    
    <?php if ($message): ?>
        <div class="alert alert-success"><?= $message ?></div>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <div class="alert alert-danger"><?= $error ?></div>
    <?php endif; ?>
    
    <div class="d-flex justify-content-between mb-3">
        <a href="admin_faq.php" class="btn btn-primary">
            <i class="bi bi-list"></i> Список вопросов
        </a>
        <a href="admin_faq.php?action=add" class="btn btn-success">
            <i class="bi bi-plus-circle"></i> Добавить вопрос
        </a>
    </div>
    
    <?php if ($action === 'add' || $action === 'edit'): ?>
        <!-- Форма добавления/редактирования -->
        <?php
        $item = ['id' => '', 'question' => '', 'answer' => '', 'sort_order' => 0, 'is_active' => 1];
        if ($action === 'edit' && isset($_GET['id'])) {
            $item = getFaqItem($conn, (int)$_GET['id']);
            if (!$item) {
                echo '<div class="alert alert-danger">Вопрос не найден!</div>';
                include 'templates/footer.php';
                exit;
            }
        }
        ?>
        
        <div class="card">
            <div class="card-header">
                <?= $action === 'add' ? 'Добавление нового вопроса' : 'Редактирование вопроса' ?>
            </div>
            <div class="card-body">
                <form method="post">
                    <input type="hidden" name="id" value="<?= $item['id'] ?>">
                    
                    <div class="mb-3">
                        <label class="form-label">Вопрос:</label>
                        <input type="text" name="question" class="form-control" 
                               value="<?= htmlspecialchars($item['question']) ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Ответ:</label>
                        <textarea name="answer" class="form-control" rows="5" required><?= 
                            htmlspecialchars($item['answer']) 
                        ?></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Порядок сортировки:</label>
                            <input type="number" name="sort_order" class="form-control" 
                                   value="<?= $item['sort_order'] ?>" min="0">
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <div class="form-check form-switch mt-4">
                                <input class="form-check-input" type="checkbox" name="is_active" 
                                       id="is_active" value="1" <?= $item['is_active'] ? 'checked' : '' ?>>
                                <label class="form-check-label" for="is_active">Активен</label>
                            </div>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save"></i> Сохранить
                    </button>
                    <a href="admin_faq.php" class="btn btn-secondary">Отмена</a>
                </form>
            </div>
        </div>
    
    <?php else: ?>
        <!-- Список вопросов -->
        <?php $faq_items = getFaqItems($conn); ?>
        
        <div class="card">
            <div class="card-header">Список вопросов</div>
            <div class="card-body">
                <?php if (empty($faq_items)): ?>
                    <div class="alert alert-info">Нет добавленных вопросов</div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Вопрос</th>
                                    <th>Порядок</th>
                                    <th>Статус</th>
                                    <th>Действия</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($faq_items as $item): ?>
                                <tr>
                                    <td><?= $item['id'] ?></td>
                                    <td><?= htmlspecialchars(mb_substr($item['question'], 0, 50)) ?>...</td>
                                    <td><?= $item['sort_order'] ?></td>
                                    <td>
                                        <span class="badge bg-<?= $item['is_active'] ? 'success' : 'secondary' ?>">
                                            <?= $item['is_active'] ? 'Активен' : 'Скрыт' ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="admin_faq.php?action=edit&id=<?= $item['id'] ?>" 
                                           class="btn btn-sm btn-warning" title="Редактировать">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <a href="admin_faq.php?delete=<?= $item['id'] ?>" 
                                           class="btn btn-sm btn-danger" title="Удалить"
                                           onclick="return confirm('Удалить этот вопрос?')">
                                            <i class="bi bi-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include 'templates/footer.php'; ?>