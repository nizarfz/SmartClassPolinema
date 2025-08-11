<?php
header('Content-Type: application/json');
// Koneksi ke database
include('koneksi.php');
$data = "";

// Pastikan koneksi berhasil
if (!$conn) {
    die("Koneksi gagal: " . mysqli_connect_error());
}

// Set zona waktu agar waktu sesuai dengan lokal
date_default_timezone_set('Asia/Jakarta');

// Ambil hari dalam format yang sesuai dengan database
$hari_ini = date('l'); // Contoh: Sunday
$hari_indo = [
    'Sunday' => 'Minggu',
    'Monday' => 'Senin',
    'Tuesday' => 'Selasa',
    'Wednesday' => 'Rabu',
    'Thursday' => 'Kamis',
    'Friday' => 'Jumat',
    'Saturday' => 'Sabtu'
];
$hari_sekarang = $hari_indo[$hari_ini] ?? $hari_ini;

$waktu_sekarang = date("H:i:s");

if(isset($_GET["last"])){
    $query = "SELECT * FROM absensi WHERE DATE(tanggal) = CURDATE() ORDER BY id DESC LIMIT 1";
    $result = $conn->query($query);
    while ($row = $result->fetch_assoc()) {
        $mahasiswa_last = $row['nama'];
        $mahasiswa_status = $row['keterangan'];
    }
    $query = "SELECT * FROM absensi_dosen WHERE DATE(tanggal) = CURDATE() ORDER BY id DESC LIMIT 1";
    $result = $conn->query($query);
    while ($row = $result->fetch_assoc()) {
        $dosen_last = $row['nama'];
        $dosen_status = $row['keterangan'];
    }
    $data = [
        "mahasiswa" => ("Mahasiswa " . $mahasiswa_last . " " . $mahasiswa_status),
        "mhsket" => $mahasiswa_status,
        "dosen" => ("Dosen " . $dosen_last . " " . $dosen_status)
    ];
}

if(isset($_GET["jumlah"])){
    $query = "SELECT * FROM mahasiswa";
    $result = $conn->query($query);
    $jumlahmahasiswa = $result->num_rows;
    $query = "SELECT * FROM dosen";
    $result = $conn->query($query);
    $jumlahdosen = $result->num_rows;
    $query = "SELECT * FROM absensi WHERE DATE(tanggal) = CURDATE()";
    $result = $conn->query($query);
    $mahasiswa_jumlahhadir = $result->num_rows;
    $query = "SELECT * FROM absensi_dosen WHERE DATE(tanggal) = CURDATE()";
    $result = $conn->query($query);
    $dosen_jumlahhadir = $result->num_rows;
    $query = "SELECT * FROM jadwal";
    $result = $conn->query($query);
    $jumlahmatkul = $result->num_rows;
    $query = "SELECT * FROM jadwal
          WHERE hari = '$hari_sekarang' 
          AND '$waktu_sekarang' BETWEEN waktu_mulai AND waktu_selesai";
    $result = $conn->query($query);
    $matkulberlangsung = $result->num_rows;
    $data = [
        "mahasiswa" => $jumlahmahasiswa,
        "mahasiswa_hadir" => $mahasiswa_jumlahhadir,
        "dosen" => $jumlahdosen,
        "dosen_hadir" => $dosen_jumlahhadir,
        "matkul" => $jumlahmatkul,
        "matkulberlangsung" => $matkulberlangsung
    ];
}

if (isset($_GET["today"])) {
    // Query mahasiswa attendance
    $query1 = "SELECT nama, mata_kuliah, suhu, 'masuk' AS keterangan, waktu_masuk AS waktu
FROM absensi
WHERE DATE(tanggal) = CURDATE() AND waktu_masuk IS NOT NULL

UNION ALL

SELECT nama, mata_kuliah, suhu, 'pulang' AS keterangan, waktu_pulang AS waktu
FROM absensi
WHERE DATE(tanggal) = CURDATE() AND waktu_pulang IS NOT NULL

ORDER BY waktu DESC
LIMIT 4;
";

$query = "SELECT nama, mata_kuliah, suhu, keterangan, waktu_masuk AS waktu
FROM absensi
WHERE DATE(tanggal) = CURDATE()

ORDER BY waktu DESC
LIMIT 4;
";

$result = $conn->query($query);

$mahasiswa_jumlahhadir = $result->num_rows;
$mahasiswa_data = [];

while ($row = $result->fetch_assoc()) {
    $mahasiswa_data[] = [
        'nama' => $row['nama'],
        'matkul' => $row['mata_kuliah'],
        'suhu' => $row['suhu'],
        'status' => $row['keterangan']
    ];
}

    // Query dosen attendance for today
    $query = "SELECT nama, mata_kuliah, suhu, 'masuk' AS keterangan, waktu_masuk AS waktu
FROM absensi_dosen
WHERE DATE(tanggal) = CURDATE() AND waktu_masuk IS NOT NULL
ORDER BY waktu_masuk DESC
LIMIT 4;
";
    $result = $conn->query($query);

    $dosen_jumlahhadir = $result->num_rows;
    $dosen_data = [];
    while ($row = $result->fetch_assoc()) {
    $dosen_data[] = [
        'nama' => $row['nama'],
        'matkul' => $row['mata_kuliah'],
        'suhu' => $row['suhu'],
        'status' => $row['keterangan']
    ];
}

    // Build the data array
    $data = [
        "mahasiswa" => [
            "hadir" => $mahasiswa_jumlahhadir,
            "data" => $mahasiswa_data
        ],
        "dosen" => [
            "hadir" => $dosen_jumlahhadir,
            "data" => $dosen_data
        ]
    ];
}

// Step 3: Convert the array to JSON and output it
echo json_encode($data);

$conn->close();
?>