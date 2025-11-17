<?php
<?php
// test_dadata.php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$token = "6e6340d0b8edb61cbab2be71cd7cba0d27d03a02";
$secret = "a7f6a4fb0e27664fb11243ac1cd03003b5d3e397";

// Остальной код...

$url = "https://cleaner.dadata.ru/api/v1/clean/vehicle";
$data = json_encode(["с911се78"]);

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json',
    'Authorization: Token ' . $token,
    'X-Secret: ' . $secret
]);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curl_error = curl_error($ch);
curl_close($ch);

echo "<h2>Результат запроса к Dadata:</h2>";
echo "<p>HTTP код: $http_code</p>";
echo "<p>Ошибка CURL: " . ($curl_error ? $curl_error : 'нет') . "</p>";
echo "<p>Ответ: <pre>" . htmlspecialchars($response) . "</pre></p>";

if ($http_code == 200) {
    $result = json_decode($response, true);
    echo "<p>Декодированный результат: <pre>";
    print_r($result);
    echo "</pre></p>";
}
?>