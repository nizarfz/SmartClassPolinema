<?php
// Informasi koneksi database
$servername = "localhost";  // Biasanya 'localhost' jika database ada di mesin yang sama
$username = "smartclass";         // Username default untuk MySQL di lokal adalah 'root'
$password = "elektroloss";             // Kosongkan jika tidak ada password untuk MySQL (default untuk XAMPP)
$dbname = "smartabsence";        // Nama database Anda

// Membuat koneksi ke MySQL dengan exception handling
try {
    // Membuat koneksi menggunakan MySQLi dengan OOP (Object-Oriented Programming)
    $conn = new mysqli($servername, $username, $password, $dbname);
    
    // Cek apakah koneksi berhasil
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
} catch (Exception $e) {
    // Menampilkan pesan kesalahan jika koneksi gagal
    die("Koneksi Gagal: " . $e->getMessage());
}

// Setelah koneksi berhasil, Anda bisa melanjutkan dengan operasi database lainnya
?>
