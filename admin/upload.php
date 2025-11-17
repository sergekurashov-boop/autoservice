<?php
// Проверка авторизации
session_start();


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = htmlspecialchars($_POST['title']);
    $category = $_POST['category'];
    
    // Генерация уникального имени файла
    $filename = uniqid() . '_' . str_replace(' ', '_', basename($_FILES['pdf']['name']));
    $target_file = __DIR__ . '/../knowledge_base/pdf/' . $filename;
    
    // Сохранение файла
    if (move_uploaded_file($_FILES['pdf']['tmp_name'], $target_file)) {
        // Запись в БД
        $stmt = $pdo->prepare("INSERT INTO pdf_documents (title, file_path, category) VALUES (?, ?, ?)");
        $stmt->execute([$title, "knowledge_base/pdf/$filename", $category]);
        echo "✅ Файл загружен!";
    } else {
        echo "❌ Ошибка загрузки файла";
    }
}
?>

<form method="post" enctype="multipart/form-data">
    <input type="text" name="title" placeholder="Название документа" required>
    <select name="category" required>
        <option value="Двигатель">Двигатель</option>
        <option value="Трансмиссия">Трансмиссия</option>
        <!-- ... -->
    </select>
    <input type="file" name="pdf" accept=".pdf" required>
    <button>Загрузить</button>
</form>
