
<div class="card hover-scale mb-4" style="opacity: 0; transform: translateY(20px); transition: opacity 0.5s, transform 0.5s;">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="bi bi-box-seam me-2"></i>Склад запчастей</h5>
        <div>
            <a href="?action=export&type=csv" class="btn btn-outline-light btn-sm me-2">
                <i class="bi bi-file-earmark-excel me-1"></i> Экспорт
            </a>
            <a href="?action=add" class="btn btn-gold btn-sm">
                <i class="bi bi-plus-lg me-1"></i> Добавить
            </a>
        </div>
    </div>
    
    <div class="card-body">
        <?php if (!empty($message)): ?>
        <div class="alert alert-success alert-dismissible fade show" style="background-color: rgba(0,0,0,0.3); border: 1px solid var(--gold-secondary);">
            <?= htmlspecialchars($message) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" style="filter: invert(1);"></button>
        </div>
        <?php endif; ?>
        
        <!-- Фильтры -->
        <form method="get" class="row g-3 mb-4">
            <input type="hidden" name="action" value="list">
            
            <div class="col-md-4">
                <select name="category_id" class="form-select bg-dark text-light border-secondary">
                    <option value="">Все категории</option>
                    <?php foreach ($categories as $cat): ?>
                    <option value="<?= $cat['id'] ?>" 
                        <?= ($_GET['category_id'] ?? '') == $cat['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($cat['name']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="col-md-4">
                <input type="text" name="search" class="form-control bg-dark text-light border-secondary" 
                       placeholder="Поиск по названию или артикулу" value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
            </div>
            
            <div class="col-md-2">
                <button type="submit" class="btn btn-gold w-100">
                    <i class="bi bi-funnel me-1"></i> Фильтр
                </button>
            </div>
            
            <div class="col-md-2">
                <a href="?" class="btn btn-outline-light w-100">
                    <i class="bi bi-arrow-counterclockwise me-1"></i> Сброс
                </a>
            </div>
        </form>
        
        <!-- Таблица -->
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Артикул</th>
                        <th>Название</th>
                        <th>Категория</th>
                        <th class="text-end">Цена</th>
                        <th class="text-center">Остаток</th>
                        <th class="text-center">Действия</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($items)): ?>
                    <tr>
                        <td colspan="6" class="text-center py-4">Нет данных для отображения</td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($items as $item): ?>
                    <tr class="<?= $item['quantity'] < $item['min_quantity'] ? 'table-warning' : '' ?>"
                        style="background-color: rgba(0,0,0,0.2);">
                        <td><?= htmlspecialchars($item['sku']) ?></td>
                        <td><?= htmlspecialchars($item['name']) ?></td>
                        <td><?= htmlspecialchars($item['category_name'] ?? '-') ?></td>
                        <td class="text-end"><?= number_format($item['price'], 2) ?> ₽</td>
                        <td class="text-center <?= $item['quantity'] < $item['min_quantity'] ? 'text-warning fw-bold' : '' ?>">
                            <?= $item['quantity'] ?>
                        </td>
                        <td class="text-center">
                            <a href="?action=edit&id=<?= $item['id'] ?>" class="btn btn-sm btn-outline-warning me-1">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <a href="?action=delete&id=<?= $item['id'] ?>" class="btn btn-sm btn-outline-danger"
                               onclick="return confirm('Удалить запчасть <?= htmlspecialchars(addslashes($item['name'])) ?>?')">
                                <i class="bi bi-trash"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Пагинация -->
        <?php if ($totalPages > 1): ?>
        <nav aria-label="Page navigation">
            <ul class="pagination justify-content-center mt-4">
                <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                    <a class="page-link bg-dark border-secondary text-light" 
                       href="?action=list&page=<?= $page-1 ?>&<?= http_build_query($_GET) ?>">
                        <i class="bi bi-chevron-left"></i>
                    </a>
                </li>
                
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                    <a class="page-link <?= $i == $page ? 'bg-gold border-gold text-dark' : 'bg-dark border-secondary text-light' ?>" 
                       href="?action=list&page=<?= $i ?>&<?= http_build_query($_GET) ?>">
                        <?= $i ?>
                    </a>
                </li>
                <?php endfor; ?>
                
                <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                    <a class="page-link bg-dark border-secondary text-light" 
                       href="?action=list&page=<?= $page+1 ?>&<?= http_build_query($_GET) ?>">
                        <i class="bi bi-chevron-right"></i>
                    </a>
                </li>
            </ul>
        </nav>
        <?php endif; ?>
    </div>
</div>