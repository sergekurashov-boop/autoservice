<?php defined('ACCESS') 
?>

<div class="card hover-scale mb-4" style="opacity: 0; transform: translateY(20px); transition: opacity 0.5s, transform 0.5s;">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">
            <i class="bi bi-box-seam me-2"></i>
            <?= isset($item) ? 'Редактирование запчасти' : 'Добавление новой запчасти' ?>
        </h5>
        <a href="warehouse.php" class="btn btn-sm btn-outline-light">
            <i class="bi bi-arrow-left"></i> Назад
        </a>
    </div>
    
    <div class="card-body">
        <?php if (isset($error)): ?>
        <div class="alert alert-danger alert-dismissible fade show" style="background-color: rgba(0,0,0,0.3); border: 1px solid var(--gold-secondary);">
            <?= $error ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" style="filter: invert(1);"></button>
        </div>
        <?php endif; ?>
        
        <form method="post">
            <?php if (isset($item) && isset($item['id'])): ?>
            <input type="hidden" name="id" value="<?= $item['id'] ?>">
            <?php endif; ?>
            
            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label"><span style="color: #ffd700;">Название</span></label>
                    <input type="text" class="form-control bg-dark text-light border-secondary" 
                           name="name" value="<?= htmlspecialchars($item['name'] ?? '') ?>" >
                </div>
                <div class="col-md-6">
                    <label class="form-label"><span style="color: #ffd700;">Артикул</span></label>
                    <input type="text" class="form-control bg-dark text-light border-secondary" 
                           value="<?= htmlspecialchars($item['sku'] ?? 'ITM-'.strtoupper(substr(uniqid(), -6))) ?>" readonly>
                </div>
            </div>
            
            <div class="mb-3">
                <label class="form-label"><span style="color: #ffd700;">Описание</span></label>
                <textarea class="form-control bg-dark text-light border-secondary" 
                          name="description" rows="3"><?= htmlspecialchars($item['description'] ?? '') ?></textarea>
            </div>
            
            <div class="row mb-3">
                               
               
            </div>
            
            <div class="row mb-3">
                <div class="col-md-3">
                    <label class="form-label"><span style="color: #ffd700;">Цена* (₽)</span></label>
                    <input type="number" step="0.01" min="0" class="form-control bg-dark text-light border-secondary" 
                           name="price" value="<?= $item['price'] ?? '0' ?>" required>
                </div>
                
                <div class="col-md-3">
                    <label class="form-label"><span style="color: #ffd700;">Количество*</span></label>
                    <input type="number" min="0" class="form-control bg-dark text-light border-secondary" 
                           name="quantity" value="<?= $item['quantity'] ?? '0' ?>" required>
                </div>
                
                <div class="col-md-3">
                    <label class="form-label"><span style="color: #ffd700;">Мин. запас*</span></label>
                    <input type="number" min="1" class="form-control bg-dark text-light border-secondary" 
                           name="min_quantity" value="<?= $item['min_quantity'] ?? '5' ?>" required>
                </div>
                
                <div class="col-md-3">
                    <label class="form-label"><span style="color: #ffd701;">Место хранения</span></label>
                    <input type="text" class="form-control bg-dark text-light border-secondary" 
                           name="location" value="<?= htmlspecialchars($item['location'] ?? '') ?>">
                </div>
            </div>
            
            <!-- Исправленный блок: вывод только при редактировании существующей записи -->
            <?php if (isset($item) && isset($item['id'])): ?>
            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">Дата создания</label>
                    <input type="text" class="form-control bg-dark text-light border-secondary" 
                           value="<?= $item['created_at'] ?? 'Неизвестно' ?>" readonly>
                </div>
                
                <div class="col-md-6">
                    <label class="form-label">Дата обновления</label>
                    <input type="text" class="form-control bg-dark text-light border-secondary" 
                           value="<?= $item['updated_at'] ?? 'Не обновлялась' ?>" readonly>
                </div>
            </div>
            <?php endif; ?>
            
            <div class="d-grid">
                <button type="submit" class="btn btn-gold btn-lg">
                    <i class="bi bi-save me-2"></i>
                    <?= isset($item) ? 'Обновить запчасть' : 'Добавить запчасть' ?>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    // Анимация появления формы
    document.addEventListener('DOMContentLoaded', function() {
        const card = document.querySelector('.hover-scale');
        setTimeout(() => {
            card.style.opacity = '1';
            card.style.transform = 'translateY(0)';
        }, 100);
    });
    
    // Валидация числовых полей
    document.querySelectorAll('input[type="number"]').forEach(input => {
        input.addEventListener('input', function() {
            const min = this.min ? parseFloat(this.min) : -Infinity;
            const max = this.max ? parseFloat(this.max) : Infinity;
            const value = parseFloat(this.value) || 0;
            
            if (value < min) {
                this.value = min;
            } else if (value > max) {
                this.value = max;
            }
        });
    });
</script>