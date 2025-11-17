<div class="container mt-4">
    <h2>Управление категориями</h2>
    
    <?php if ($message): ?>
        <div class="alert alert-success"><?= $message ?></div>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <div class="alert alert-danger"><?= $error ?></div>
    <?php endif; ?>
    
    <div class="d-flex justify-content-between mb-3">
        <a href="warehouse.php?action=category_add" class="btn btn-success">
            <i class="bi bi-plus-circle"></i> Добавить категорию
        </a>
    </div>
    
    <?php if (empty($categories)): ?>
        <div class="alert alert-info">Нет доступных категорий</div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Название</th>
                        <th>Действия</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($categories as $category): ?>
                    <tr>
                        <td><?= $category['id'] ?></td>
                        <td><?= htmlspecialchars($category['name']) ?></td>
                        <td>
                            <a href="warehouse.php?action=category_edit&id=<?= $category['id'] ?>" 
                               class="btn btn-sm btn-warning">
                                <i class="bi bi-pencil"></i> Редактировать
                            </a>
                            <a href="warehouse.php?action=category_delete&id=<?= $category['id'] ?>" 
                               class="btn btn-sm btn-danger"
                               onclick="return confirm('Вы уверены, что хотите удалить эту категорию?')">
                                <i class="bi bi-trash"></i> Удалить
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>