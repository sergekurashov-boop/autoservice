<?php
class DadataAPI {
    private $api_key;
    private $secret_key;
    private $base_url = "https://cleaner.dadata.ru/api/v1/clean/";
    
    public function __construct($api_key, $secret_key) {
        $this->api_key = $api_key;
        $this->secret_key = $secret_key;
    }
    
    public function cleanVehicle($license_plate) {
        $url = $this->base_url . "vehicle";
        
        $data = json_encode([$license_plate]);
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Token ' . $this->api_key,
            'X-Secret: ' . $this->secret_key
        ]);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($http_code == 200) {
            return json_decode($response, true);
        } else {
            error_log("Dadata API error: HTTP $http_code - $response");
            return null;
        }
    }
    
    public function suggestAddress($query) {
        $url = "https://suggestions.dadata.ru/suggestions/api/4_1/rs/suggest/address";
        
        $data = json_encode(["query" => $query, "count" => 5]);
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Token ' . $this->api_key
        ]);
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        return json_decode($response, true);
    }
}
?>