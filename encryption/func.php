<?php
// ==== Implementasi Diffie-Hellman ==== //

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

// ==== Konfigurasi dan Pembentukan Kunci dengan Diffie-Hellman ==== //
$p = 23;  // Bilangan prima
$g = 5;   // Generator
$privateAlice = 6;  // Kunci privat Alice 
$privateBob = 15;   // Kunci privat Bob

// Hitung public keys
$publicAlice = modExp($g, $privateAlice, $p);
$publicBob = modExp($g, $privateBob, $p);

// Hitung shared secret
$sharedKeyAlice = modExp($publicBob, $privateAlice, $p);
$sharedKeyBob = modExp($publicAlice, $privateBob, $p);

// Verifikasi bahwa shared secret sama
if ($sharedKeyAlice !== $sharedKeyBob) {
    throw new Exception("Kunci rahasia tidak cocok");
}

// Hash shared key untuk penggunaan dengan Blowfish
$sharedKey = hash('sha256', $sharedKeyAlice, true); //hanya digunakan alice karna key yang dihasilkan dari shared key sudah pasti sama antara alice dan bob

// ==== Implementasi Blowfish ==== //

// Fungsi untuk enkripsi menggunakan Blowfish dengan CBC mode
function blowfishEncrypt($sharedKey, $data) {
    // Generate IV acak sepanjang 8 bytes (sesuai block size Blowfish)
    // IV diperlukan untuk mode CBC agar blok yang sama menghasilkan ciphertext berbeda
    $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('BF-CBC'));

    // Proses enkripsi:
    // 1. Data dipecah menjadi blok-blok 64-bit
    // 2. Setiap blok di-XOR dengan IV (blok pertama) atau ciphertext sebelumnya (blok berikutnya)
    // 3. Hasil XOR diproses menggunakan Feistel Network (16 putaran) yang sudah diimplementasi dalam openssl
    // 4. Feistel Network menggunakan subkeys yang dibuat dari $sharedKey
    $encrypted = openssl_encrypt(
        $data,      // Data yang akan dienkripsi
        'BF-CBC',   // Menggunakan Blowfish dengan mode CBC
        $sharedKey, // SharedKey dari Diffie-Hellman digunakan untuk membuat subkeys
        OPENSSL_RAW_DATA, // Output dalam bentuk raw binary
        $iv         // IV untuk mode CBC
    );

    // Gabungkan IV dan hasil enkripsi
    // IV perlu disertakan agar data bisa didekripsi
    return base64_encode($iv . $encrypted);
}

// Fungsi untuk dekripsi menggunakan Blowfish
function blowfishDecrypt($sharedKey, $data) {
    // Decode dari Base64 ke binary
    $data = base64_decode($data);

    // Ambil IV dari awal data (8 bytes pertama)
    $ivLength = openssl_cipher_iv_length('BF-CBC');
    $iv = substr($data, 0, $ivLength);
    // Ambil data terenkripsi setelah IV
    $encrypted = substr($data, $ivLength);

    // Proses dekripsi:
    // 1. Data didekripsi menggunakan Feistel Network dengan subkeys dari $sharedKey
    // 2. Hasil dekripsi di-XOR dengan IV atau ciphertext blok sebelumnya
    return openssl_decrypt(
        $encrypted, // Data terenkripsi
        'BF-CBC',   // Blowfish-CBC
        $sharedKey, // SharedKey yang sama dengan enkripsi
        OPENSSL_RAW_DATA,
        $iv         // IV yang sama dengan enkripsi
    );
}
?>
