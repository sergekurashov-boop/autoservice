<?php
$password = "SuperAdmin123!";
$hash_from_db = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';

echo "Пароль: " . $password . "<br>";
echo "Хэш из базы: " . $hash_from_db . "<br>";

if (password_verify($password, $hash_from_db)) {
    echo "✅ Пароль СОВПАДАЕТ!";
} else {
    echo "❌ Пароль НЕ СОВПАДАЕТ!";
    
    // Создаем правильный хэш
    $new_hash = password_hash($password, PASSWORD_DEFAULT);
    echo "<br>Правильный хэш: " . $new_hash;
}
?>