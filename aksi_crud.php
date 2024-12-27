<?php
include "koneksi.php";
include 'encryption/index.php';

// Fungsi enkripsi data sensitif
function encryptSensitiveData($sharedKey, $data) {
    return blowfishEncrypt($sharedKey, $data);
}

// Fungsi dekripsi data sensitif
function decryptSensitiveData($sharedKey, $data) {
    return blowfishDecrypt($sharedKey, $data);
}

// Fungsi tambah data dengan enkripsi (vulnerable version)
function tambahData($koneksi, $sharedKey, $nama, $jenis_kelamin, $ttl, $nim, $ipk, $jurusan, $univ, $tahun_masuk, $nomor_rekening, $nomor_hp, $ket) {
    // Enkripsi data sensitif tetap dilakukan
    $encryptedData = [
        'ttl' => encryptSensitiveData($sharedKey, $ttl),
        'nim' => encryptSensitiveData($sharedKey, $nim),
        'nomor_rekening' => encryptSensitiveData($sharedKey, $nomor_rekening),
        'nomor_hp' => encryptSensitiveData($sharedKey, $nomor_hp)
    ];
    
    // Query tanpa prepared statement (vulnerable to SQL injection)
    $query = "INSERT INTO tuser (nama, jenis_kelamin, ttl, nim, ipk, jurusan, univ, tahun_masuk, nomor_rekening, nomor_hp, ket) VALUES 
    ('$nama', '$jenis_kelamin', '{$encryptedData['ttl']}', '{$encryptedData['nim']}', '$ipk', '$jurusan', '$univ', '$tahun_masuk', '{$encryptedData['nomor_rekening']}', '{$encryptedData['nomor_hp']}', '$ket')";
    
    return mysqli_query($koneksi, $query);
}

// Fungsi untuk mengedit data (vulnerable version)
function editData($koneksi, $sharedKey, $id, $nama, $jenis_kelamin, $ttl, $nim, $ipk, $jurusan, $univ, $tahun_masuk, $nomor_rekening, $nomor_hp, $ket) {
    $encryptedTtl = blowfishEncrypt($sharedKey, $ttl);
    $encryptedNim = blowfishEncrypt($sharedKey, $nim);
    $encryptedNomorRekening = blowfishEncrypt($sharedKey, $nomor_rekening);
    $encryptedNomorHp = blowfishEncrypt($sharedKey, $nomor_hp);

    // Query tanpa prepared statement (vulnerable to SQL injection)
    $query = "UPDATE tuser SET 
              nama='$nama', 
              jenis_kelamin='$jenis_kelamin', 
              ttl='$encryptedTtl', 
              nim='$encryptedNim', 
              ipk='$ipk', 
              jurusan='$jurusan', 
              univ='$univ', 
              tahun_masuk='$tahun_masuk', 
              nomor_rekening='$encryptedNomorRekening', 
              nomor_hp='$encryptedNomorHp', 
              ket='$ket' 
              WHERE id='$id'";

    return mysqli_query($koneksi, $query);
}

// Fungsi untuk menghapus data (vulnerable version)
function hapusData($koneksi, $id) {
    // Query tanpa prepared statement (vulnerable to SQL injection)
    $query = "DELETE FROM tuser WHERE id='$id'";
    return mysqli_query($koneksi, $query);
}

// Fungsi ambil data dengan dekripsi (vulnerable version)
function getDataUser($koneksi, $sharedKey) {
    $query = mysqli_query($koneksi, "SELECT * FROM tuser");
    $dataUser = [];
    
    while ($data = mysqli_fetch_array($query)) {
        // Dekripsi data sensitif tetap dilakukan
        $dataUser[] = [
            'id' => $data['id'],
            'nama' => $data['nama'],
            'jenis_kelamin' => $data['jenis_kelamin'],
            'ttl' => decryptSensitiveData($sharedKey, $data['ttl']),
            'nim' => decryptSensitiveData($sharedKey, $data['nim']),
            'ipk' => $data['ipk'],
            'jurusan' => $data['jurusan'],
            'univ' => $data['univ'],
            'tahun_masuk' => $data['tahun_masuk'],
            'nomor_rekening' => decryptSensitiveData($sharedKey, $data['nomor_rekening']),
            'nomor_hp' => decryptSensitiveData($sharedKey, $data['nomor_hp']),
            'ket' => $data['ket']
        ];
    }
    
    return $dataUser;
}
?>