<?php
function validateINN($inn) {
    if (!preg_match('/^\d{10}$|^\d{12}$/', $inn)) {
        return false;
    }
    
    if (strlen($inn) === 10) {
        $coefficients = [2, 4, 10, 3, 5, 9, 4, 6, 8];
        $checksum = 0;
        for ($i = 0; $i < 9; $i++) {
            $checksum += $inn[$i] * $coefficients[$i];
        }
        $checksum = $checksum % 11 % 10;
        return $checksum == $inn[9];
    }
    
    if (strlen($inn) === 12) {
        $coefficients1 = [7, 2, 4, 10, 3, 5, 9, 4, 6, 8];
        $checksum1 = 0;
        for ($i = 0; $i < 10; $i++) {
            $checksum1 += $inn[$i] * $coefficients1[$i];
        }
        $checksum1 = $checksum1 % 11 % 10;
        
        $coefficients2 = [3, 7, 2, 4, 10, 3, 5, 9, 4, 6, 8];
        $checksum2 = 0;
        for ($i = 0; $i < 11; $i++) {
            $checksum2 += $inn[$i] * $coefficients2[$i];
        }
        $checksum2 = $checksum2 % 11 % 10;
        
        return $checksum1 == $inn[10] && $checksum2 == $inn[11];
    }
    
    return false;
}

function validateKPP($kpp) {
    if (!preg_match('/^\d{9}$/', $kpp)) {
        return false;
    }
    
    $taxCode = substr($kpp, 0, 4);
    return $taxCode !== '0000';
}
?>