<?php
class DaDataVehicle {
    private $api_key;
    private $secret_key;
    
    public function __construct($api_key, $secret_key = '') {
        $this->api_key = $api_key;
        $this->secret_key = $secret_key;
    }
    
    public function cleanVehicle($input) {
        $url = "https://cleaner.dadata.ru/api/v1/clean/vehicle";
        $data = json_encode([$input]);
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Content-Type: application/json",
            "Authorization: Token " . $this->api_key,
            "X-Secret: " . $this->secret_key
        ]);
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        return json_decode($response, true);
    }
    
    public function getCarByLicensePlate($license_plate) {
        return $this->cleanVehicle($license_plate);
    }
    
    public function getCarByVIN($vin) {
        return $this->cleanVehicle($vin);
    }
}

// Инициализация (вставьте ваш API ключ)
$dadata_vehicle = new DaDataVehicle("ваш_api_ключ_здесь");
?>