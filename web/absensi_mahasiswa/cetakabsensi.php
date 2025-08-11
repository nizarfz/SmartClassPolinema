<?php
ob_start(); // Buffer output agar tidak bocor sebelum PDF dibuat

require_once 'dompdf/autoload.inc.php';
include 'koneksi.php';

use Dompdf\Dompdf;

date_default_timezone_set('Asia/Jakarta');

// Ambil logo dan ubah jadi base64
$path = 'logokampus.jpg';
$type = pathinfo($path, PATHINFO_EXTENSION);
$data = file_get_contents($path);
$base64 = 'data:image/' . $type . ';base64,' . base64_encode($data);

// Ambil parameter dari URL
$kelas = $_GET['kelas'] ?? '';
$mata_kuliah = $_GET['mata_kuliah'] ?? '';
$tanggal = $_GET['tanggal'] ?? '';
$tanggalFormatted = date('d-m-Y', strtotime($tanggal));

// Validasi input
if (empty($kelas) || empty($mata_kuliah) || empty($tanggal)) {
    die("Parameter kelas, mata kuliah, dan tanggal harus diisi.");
}

// Ambil data absensi dari database
$sql = "SELECT * FROM absensi WHERE kelas = ? AND mata_kuliah = ? AND DATE(tanggal) = ? ORDER BY nama ASC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("sss", $kelas, $mata_kuliah, $tanggal);
$stmt->execute();
$result = $stmt->get_result();

// Cek jika tidak ada data
if ($result->num_rows === 0) {
    die("Data absensi tidak ditemukan untuk tanggal tersebut.");
}

// Ambil satu data pertama untuk ambil hari aktual
$firstRow = $result->fetch_assoc();
$tanggalAktual = $firstRow['tanggal'];

// Konversi hari ke Bahasa Indonesia
$hari_indo = [
    'Sunday' => 'Minggu',
    'Monday' => 'Senin',
    'Tuesday' => 'Selasa',
    'Wednesday' => 'Rabu',
    'Thursday' => 'Kamis',
    'Friday' => 'Jumat',
    'Saturday' => 'Sabtu'
];
$hariInggris = date('l', strtotime($tanggalAktual));
$namaHari = $hari_indo[$hariInggris] ?? '-';

// Kembalikan pointer ke awal data karena sudah di-fetch satu kali
$result->data_seek(0);

// Ambil nama dosen dari jadwal
$dosen = '';
$stmt_dosen = $conn->prepare("SELECT nama_dosen FROM jadwal WHERE mata_kuliah = ? AND kelas = ?");
$stmt_dosen->bind_param("ss", $mata_kuliah, $kelas);
$stmt_dosen->execute();
$result_dosen = $stmt_dosen->get_result();
if ($row_dosen = $result_dosen->fetch_assoc()) {
    $dosen = $row_dosen['nama_dosen'];
}

$html = <<<HTML
<style>
    body {
        font-family: "Times New Roman", serif;
        font-size: 12px;
    }
    table {
        border-collapse: collapse;
        width: 100%;
        margin-bottom: 5px; /* Mengurangi jarak bawah tabel */
    }
    th, td {
        border: 1px solid #000;
        padding: 4px 6px; /* Mengurangi padding dari 6px menjadi 4px di atas dan bawah */
        text-align: center;
        word-break: break-word;
        white-space: normal;
    }
    .no-border {
        border: none;
    }
    .align-left {
        text-align: left;
        vertical-align: top;
        padding: 2px 4px; /* Mengurangi padding dalam elemen align-left */
    }
    .logo {
        text-align: center;
    }
    .label {
        font-weight: bold;
        white-space: nowrap;
    }
    .bold-header td {
        font-weight: bold;
    }
	.bold-header td {
		padding-top: 2px;
		padding-bottom: 2px;
	}
	.text-left {
	  text-align: left;
	}

</style>

<!-- HEADER TABLE -->
<table class="bold-header" style="width: 100%;">
    <tr>
        <td class="no-border logo" rowspan="5" style="width: 90px;">
            <img src="$base64" width="90">
        </td>
        <td class="no-border align-left" style="width: 50%;">
            <span class="label">POLITEKNIK NEGERI MALANG</span>
        </td>
        <td class="no-border align-left" style="width: 50%;">
            <span class="label">Data Presensi Mahasiswa</span>
        </td>
    </tr>
    <tr>
        <td class="no-border align-left">
            PROGRAM STUDI DI LUAR KAMPUS UTAMA
        </td>
        <td class="no-border align-left">
            <span style="display: inline-block; width: 80px; vertical-align: top;">Kelas</span>
			<span style="vertical-align: top;">: {$kelas}</span>
        </td>
    </tr>
	<tr>
		<td class="no-border align-left">
			<span style="display: inline-block; width: 130px; vertical-align: top;">JURUSAN</span>
			<span style="vertical-align: top;">: TEKNIK ELEKTRO</span>
		</td>
		<td class="no-border align-left">
			<span style="display: inline-block; width: 80px; vertical-align: top;">Mata Kuliah</span>
			<span style="vertical-align: top;">: {$mata_kuliah}</span>
		</td>
	</tr>
    <tr>
        <td class="no-border align-left">
            <span style="display: inline-block; width: 130px; vertical-align: top;">PROGRAM STUDI</span>
			<span style="vertical-align: top;">: D-IV Teknik Elektronika (Kampus Kediri)</span>
        </td>
        <td class="no-border align-left">
            <span style="display: inline-block; width: 80px; vertical-align: top;">Hari</span>
			<span style="vertical-align: top;">: {$namaHari}</span>
        </td>
    </tr>
    <tr>
        <td class="no-border align-left">
            <span style="display: inline-block; width: 130px; vertical-align: top;">Nama Dosen</span>
			<span style="vertical-align: top;">: {$dosen}</span>
        </td>
        <td class="no-border align-left">
            <span style="display: inline-block; width: 80px; vertical-align: top;">Tanggal</span>
			<span style="vertical-align: top;">: {$tanggalFormatted}</span>
        </td>
    </tr>
</table>



<!-- TABEL DATA -->
<table>
    <thead>
        <tr>
            <th>No</th>
            <th>Nama</th>
            <th>NIM</th>
            <th>Mata Kuliah</th>
            <th>Suhu (&deg;C)</th>
            <th>Kondisi</th>
            <th>Kelas</th>
            <th>Tanggal</th>
            <th>Masuk</th>
            <th>Pulang</th>
            <th>Keterangan</th>
        </tr>
    </thead>
    <tbody>
HTML;

// Tambahkan baris data dari database
$no = 1;
while ($row = $result->fetch_assoc()) {
    $suhu = $row['suhu'];
    $kondisi = '-';
    if (!is_null($suhu)) {
        $kondisi = ($suhu > 37.5) ? 'Suhu Tinggi' : 'Sehat';
    }

    $html .= '<tr>
        <td>' . $no++ . '</td>
        <td class="text-left">' . htmlspecialchars($row['nama']) . '</td>
        <td>' . htmlspecialchars($row['nim']) . '</td>
        <td>' . htmlspecialchars($row['mata_kuliah']) . '</td>
        <td>' . (!is_null($suhu) ? number_format(floor($suhu * 10) / 10, 1) : '-') . '</td>
        <td>' . $kondisi . '</td>
        <td>' . htmlspecialchars($row['kelas']) . '</td>
        <td>' . date('d-m-Y', strtotime($row['tanggal'])) . '</td>
        <td>' . date('H:i:s', strtotime($row['waktu_masuk'])) . '</td>
        <td>' . (!empty($row['waktu_pulang']) ? date('H:i:s', strtotime($row['waktu_pulang'])) : '-') . '</td>
        <td>' . htmlspecialchars($row['keterangan']) . '</td>
    </tr>';
}

// Tutup tabel
$html .= '</tbody></table>';

// Buat dan render PDF
$dompdf = new Dompdf();
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'landscape');
$dompdf->render();

ob_end_clean(); // Bersihkan output sebelum kirim PDF
$dompdf->stream("Absensi_{$kelas}_" . date('d-m-Y', strtotime($tanggal)) . ".pdf", ["Attachment" => true]);

$conn->close();
?>