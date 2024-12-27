<?php
// ==== Implementasi Diffie-Hellman dan Blowfish ==== //

// Fungsi eksponensial modular untuk Diffie-Hellman
function modExp($base, $exp, $mod) {
    $result = 1;
    $base = $base % $mod;
    
    // Optimized exponential calculation
    while ($exp > 0) {
        if ($exp % 2 == 1) {
            $result = ($result * $base) % $mod;
        }
        $exp = floor($exp / 2);
        $base = ($base * $base) % $mod;
    }
    return $result;
}

// Implementasi enkripsi Blowfish dengan CBC mode
function blowfishEncrypt($key, $data) {
    // Generate IV acak untuk setiap enkripsi
    $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('BF-CBC'));
    
    // Enkripsi data dengan Blowfish-CBC
    $encrypted = openssl_encrypt(
        $data,
        'BF-CBC',
        $key,
        OPENSSL_RAW_DATA,
        $iv
    );
    
    // Gabungkan IV dan data terenkripsi, encode ke Base64
    return base64_encode($iv . $encrypted);
}

// Implementasi dekripsi Blowfish
function blowfishDecrypt($key, $data) {
    // Decode dari Base64
    $data = base64_decode($data);
    
    // Ekstrak IV dan data terenkripsi
    $ivLength = openssl_cipher_iv_length('BF-CBC');
    $iv = substr($data, 0, $ivLength);
    $encrypted = substr($data, $ivLength);
    
    // Dekripsi data
    return openssl_decrypt(
        $encrypted,
        'BF-CBC',
        $key,
        OPENSSL_RAW_DATA,
        $iv
    );
}

// ==== Konfigurasi dan Pembentukan Kunci ==== //
$p = 23;  // Gunakan bilangan prima yang lebih besar untuk keamanan produksi
$g = 5;   // Generator
$privateAlice = 6;  // Kunci privat harus random dan aman
$privateBob = 15;

// Hitung public keys
$publicAlice = modExp($g, $privateAlice, $p);
$publicBob = modExp($g, $privateBob, $p);

// Hitung shared secret
$sharedKeyAlice = modExp($publicBob, $privateAlice, $p);
$sharedKeyBob = modExp($publicAlice, $privateBob, $p);

// Verifikasi shared secret sama
if ($sharedKeyAlice !== $sharedKeyBob) {
    throw new Exception("Kunci rahasia tidak cocok");
}

// Hash shared key untuk penggunaan dengan Blowfish
$sharedKey = hash('sha256', $sharedKeyAlice, true);
?>