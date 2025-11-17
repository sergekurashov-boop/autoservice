<?php
require_once __DIR__.'/../config.php';
require_once __DIR__.'/../functions.php';

// Проверка авторизации администратора
checkAdminSession();

// Подключение к БД
$db = connectDB();

// Обработка действий
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'save') {
        $id = $_POST['id'] ?? 0;
        $question = cleanInput($_POST['question']);
        $answer = cleanInput($_POST['answer'], true); // Разрешить HTML
        $order = (int)($_POST['order'] ?? 0);
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        
        if ($id > 0) {
            $stmt = $db->prepare("UPDATE faq SET question=?, answer=?, sort_order=?, is_active=? WHERE id=?");
            $stmt->bind_param("ssiii", $question, $answer, $order, $is_active, $id);
        } else {
            $stmt = $db->prepare("INSERT INTO faq (question, answer, sort_order, is_active) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssii", $question, $answer, $order, $is_active);
        }
        $stmt->execute();
    }
    elseif ($action === 'delete') {
        $id = (int)$_POST['id'];
        $db->query("DELETE FROM faq WHERE id = $id");
    }
}

// Получение списка вопросов
$faq_items = $db->query("SELECT * FROM faq ORDER BY sort_order, id")->fetch_all(MYSQLI_ASSOC);

include('../templates/header.php');
?>

<div class="container mt-4">
    <h2>Управление FAQ</h2>
    
    <!-- Форма добавления/редактирования -->
    <form method="POST" class="mb-4">
        <input type="hidden" name="action" value="save">
        <input type="hidden" name="id" id="edit_id" value="0">
        
        <div class="form-group">
            <label>Вопрос:</label>
            <input type="text" name="question" id="edit_question" class="form-control" required>
        </div>
        
        <div class="form-group">
            <label>Ответ:</label>
            <textarea name="answer" id="edit_answer" class="form-control" rows="4" required></textarea>
        </div>
        
        <div class="row">
            <div class="col-md-3">
                <div class="form-group">
                    <label>Порядок:</label>
                    <input type="number" name="order" id="edit_order" class="form-control" value="0">
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-check mt-4">
                    <input type="checkbox" name="is_active" id="edit_active" class="form-check-input" checked>
                    <label class="form-check-label">Активен</label>
                </div>
            </div>
        </div>
        
        <button type="submit" class="btn btn-primary">Сохранить</button>
        <button type="button" class="btn btn-secondary" onclick="resetForm()">Сбросить</button>
    </form>

    <!-- Список вопросов -->
    <div class="list-group">
        <?php foreach ($faq_items as $item): ?>
        <div class="list-group-item <?= $item['is_active'] ? '' : 'list-group-item-secondary' ?>">
            <div class="d-flex justify-content-between">
                <h5><?= htmlspecialchars($item['question']) ?></h5>
                <div>
                    <button class="btn btn-sm btn-outline-primary" 
                            onclick="editItem(<?= $item['id'] ?>, '<?= js_escape($item['question']) ?>', `<?= js_escape($item['answer']) ?>`, <?= $item['sort_order'] ?>, <?= $item['is_active'] ?>)">
                        <i class="bi bi-pencil"></i>
                    </button>
                    <form method="POST" style="display:inline">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?= $item['id'] ?>">
                        <button type="submit" class="btn btn-sm btn-outline-danger">
                            <i class="bi bi-trash"></i>
                        </button>
                    </form>
                </div>
            </div>
            <div><?= $item['answer'] ?></div>
            <small class="text-muted">Порядок: <?= $item['sort_order'] ?></small>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<script>
function editItem(id, question, answer, order, isActive) {
    document.getElementById('edit_id').value = id;
    document.getElementById('edit_question').value = question;
    document.getElementById('edit_answer').value = answer;
    document.getElementById('edit_order').value = order;
    document.getElementById('edit_active').checked = isActive;
    window.scrollTo(0, 0);
}

function resetForm() {
    document.getElementById('edit_id').value = 0;
    document.getElementById('edit_question').value = '';
    document.getElementById('edit_answer').value = '';
    document.getElementById('edit_order').value = 0;
    document.getElementById('edit_active').checked = true;
}
</script>

