<?php
$pdf_id = (int)$_GET['id'];
$stmt = $pdo->prepare("SELECT file_path FROM pdf_documents WHERE id = ?");
$stmt->execute([$pdf_id]);
$pdf = $stmt->fetch();

if ($pdf && file_exists($pdf['file_path'])) {
    // Отправка PDF в браузер
    header('Content-Type: application/pdf');
    header('Content-Disposition: inline; filename="document.pdf"');
    readfile($pdf['file_path']);
    exit;
} else {
    die("Документ не найден");
}