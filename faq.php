<?php
require 'includes/db.php';

// Получаем активные вопросы
$sql = "SELECT * FROM faq WHERE is_active = 1 ORDER BY sort_order, id";
$result = $conn->query($sql);
$faq_items = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];

define('ACCESS', true);
include 'templates/header.php';
?>

<div class="container my-4">
    <h2 class="mb-4">Часто задаваемые вопросы</h2>
    
    <?php if (empty($faq_items)): ?>
        <div class="alert alert-info">
            В настоящее время нет активных вопросов.
        </div>
    <?php else: ?>
        <div class="accordion" id="faqAccordion">
            <?php foreach ($faq_items as $index => $item): ?>
            <div class="accordion-item">
                <h3 class="accordion-header" id="heading<?= $index ?>">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" 
                            data-bs-target="#collapse<?= $index ?>" aria-expanded="false" 
                            aria-controls="collapse<?= $index ?>">
                        <?= htmlspecialchars($item['question']) ?>
                    </button>
                </h3>
                <div id="collapse<?= $index ?>" class="accordion-collapse collapse" 
                     aria-labelledby="heading<?= $index ?>" data-bs-parent="#faqAccordion">
                    <div class="accordion-body">
                        <?= nl2br(htmlspecialchars($item['answer'])) ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php include 'templates/footer.php'; ?>