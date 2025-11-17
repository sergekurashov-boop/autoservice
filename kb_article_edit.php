<?php
require '../includes/db.php';
require '../includes/auth.php';

// Проверка авторизации
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$article_id = $_GET['id'] ?? 0;
$article = [];

if ($article_id) {
    $result = $conn->query("SELECT * FROM kb_articles WHERE id = $article_id");
    $article = $result->fetch_assoc();
}

// Обработка сохранения
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $conn->real_escape_string($_POST['title']);
    $content = $conn->real_escape_string($_POST['content']);
    $category_id = (int)$_POST['category_id'];
    
    if ($article_id) {
        $conn->query("UPDATE kb_articles SET title='$title', content='$content', category_id=$category_id WHERE id=$article_id");
    } else {
        $conn->query("INSERT INTO kb_articles (title, content, category_id) VALUES ('$title', '$content', $category_id)");
        $article_id = $conn->insert_id;
    }
    
    header("Location: kb_article_edit.php?id=$article_id&saved=1");
    exit;
}

$categories = $conn->query("SELECT id, title FROM kb_categories");
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title><?= $article_id ? 'Редактирование' : 'Создание' ?> статьи</title>
    <link rel="stylesheet" href="/autoservice/assets/css/bootstrap.min.css">
    <script src="/autoservice/assets/js/tinymce/tinymce.min.js"></script>
    <style>
        body { background-color: #f8f9fa; }
        .editor-container { border: 1px solid #ddd; border-radius: 5px; padding: 10px; background: white; }
    </style>
</head>
<body>
    <div class="container py-4">
        <h1 class="mb-4"><?= $article_id ? 'Редактирование статьи' : 'Создание новой статьи' ?></h1>
        
        <?php if (isset($_GET['saved'])): ?>
        <div class="alert alert-success">Статья успешно сохранена!</div>
        <?php endif; ?>
        
        <form method="post">
            <div class="mb-3">
                <label class="form-label">Заголовок</label>
                <input type="text" name="title" class="form-control" 
                       value="<?= htmlspecialchars($article['title'] ?? '') ?>" required>
            </div>
            
            <div class="mb-3">
                <label class="form-label">Категория</label>
                <select name="category_id" class="form-select" required>
                    <?php while ($cat = $categories->fetch_assoc()): ?>
                    <option value="<?= $cat['id'] ?>" 
                        <?= ($article['category_id'] ?? 0) == $cat['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($cat['title']) ?>
                    </option>
                    <?php endwhile; ?>
                </select>
            </div>
            
            <div class="mb-3">
                <label class="form-label">Содержание</label>
                <textarea id="kb-editor" name="content"><?= $article['content'] ?? '' ?></textarea>
            </div>
            
            <div class="d-flex justify-content-between">
                <a href="dashboard.php" class="btn btn-secondary">Назад</a>
                <button type="submit" class="btn btn-primary">Сохранить</button>
            </div>
        </form>
    </div>

    <script>
    tinymce.init({
        selector: '#kb-editor',
        height: 500,
        plugins: 'preview searchreplace autolink save directionality visualblocks visualchars image link media table charmap lists wordcount',
        toolbar: 'undo redo | bold italic underline | alignleft aligncenter alignright | bullist numlist outdent indent | image media | preview save',
        relative_urls: false,
        remove_script_host: false,
        document_base_url: '/autoservice/'
    });
    </script>
</body>
</html>