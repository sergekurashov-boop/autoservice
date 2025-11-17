<div class="container mt-4">
    <h2><?= isset($category['id']) ? 'Редактирование категории' : 'Добавление новой категории' ?></h2>
    
    <?php if ($error): ?>
        <div class="alert alert-danger"><?= $error ?></div>
    <?php endif; ?>
    
    <form method="post">
        <input type="hidden" name="id" value="<?= $category['id'] ?? '' ?>">
        
        <div class="mb-3">
            <label class="form-label">Название категории:</label>
            <input type="text" name="name" class="form-control" 
                   value="<?= htmlspecialchars($category['name'] ?? '') ?>" required>
        </div>
        
        <button type="submit" class="btn btn-primary">Сохранить</button>
        <a href="warehouse.php?action=categories" class="btn btn-secondary">Отмена</a>
    </form>
</div>