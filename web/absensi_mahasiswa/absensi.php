<?php
// Include koneksi database
include('koneksi.php');

date_default_timezone_set("Asia/Jakarta");
$conn->query("SET time_zone = '+07:00'");


session_start();

// Cek apakah ada permintaan penghapusan data
if (isset($_GET['delete_id'])) {
    $delete_id = $_GET['delete_id'];

    if (is_numeric($delete_id)) {
        $query_hapus = $conn->prepare("DELETE FROM absensi WHERE id = ?");
        $query_hapus->bind_param("i", $delete_id);
        if ($query_hapus->execute()) {
            echo "<script>alert('Data absensi berhasil dihapus!'); window.location.href='absensi.php';</script>";
        } else {
            echo "<script>alert('Gagal menghapus data absensi.'); window.location.href='absensi.php';</script>";
        }
        $query_hapus->close();
    } else {
        echo "<script>alert('ID absensi tidak valid.'); window.location.href='absensi.php';</script>";
    }
}

// Ambil parameter dari URL
$nama = isset($_GET['nama']) ? urldecode(trim($_GET['nama'])) : null;
$nim = isset($_GET['nim']) ? trim($_GET['nim']) : null;
$suhu = isset($_GET['suhu']) ? floatval($_GET['suhu']) : null;
$kelas = isset($_GET['kelas']) ? trim($_GET['kelas']) : null;
$mata_kuliah = isset($_GET['mata_kuliah']) ? trim($_GET['mata_kuliah']) : null;
$waktu = isset($_GET['waktu']) ? urldecode(trim($_GET['waktu'])) : null;
$tanggal_filter = isset($_GET['tanggal']) ? $_GET['tanggal'] : '';

// Query dasar
$query_base = "SELECT * FROM absensi WHERE 1=1";
$filter_sql = ""; 
$params = [];
$types = "";

// Tambahkan filter berdasarkan kelas
if (!empty($_GET['kelas'])) {
    $query_base .= " AND kelas = ?";
    $params[] = $_GET['kelas'];
    $types .= "s";
}

// Tambahkan filter berdasarkan mata kuliah
if (!empty($_GET['mata_kuliah'])) {
    $query_base .= " AND mata_kuliah = ?";
    $params[] = $_GET['mata_kuliah'];
    $types .= "s";
}

// Tambahkan filter berdasarkan tanggal
if (!empty($_GET['tanggal'])) {
    $query_base .= " AND DATE(waktu_masuk) = ?";
    $params[] = $_GET['tanggal'];
    $types .= "s";
}

// Tambahkan ORDER BY
$query_base .= " ORDER BY waktu_masuk DESC";

// Eksekusi query dengan prepared statement
$stmt = $conn->prepare($query_base);

// Pastikan hanya bind param jika ada parameter
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

// Eksekusi statement
$stmt->execute();

// Ambil hasil
$result = $stmt->get_result();



// Jika data absensi baru akan disimpan
if ($nama && $nim && $suhu !== null && $kelas && $waktu) {
    $hari_ini = date('l');
    $hari_db = [
        'Monday' => 'Senin', 'Tuesday' => 'Selasa', 'Wednesday' => 'Rabu',
        'Thursday' => 'Kamis', 'Friday' => 'Jumat', 'Saturday' => 'Sabtu', 'Sunday' => 'Minggu'
    ];
    $hari_ini = $hari_db[$hari_ini] ?? $hari_ini;
    $waktu_sekarang = date('H:i:s', strtotime($waktu));

    // Cari mata kuliah aktif
    $query_jadwal = $conn->prepare("SELECT mata_kuliah, waktu_mulai FROM jadwal WHERE kelas = ? AND hari = ? AND TIME(?) BETWEEN waktu_mulai AND waktu_selesai");
    $query_jadwal->bind_param("sss", $kelas, $hari_ini, $waktu_sekarang);
    $query_jadwal->execute();
    $result_jadwal = $query_jadwal->get_result();

    $mata_kuliah_aktif = [];
    while ($row_jadwal = $result_jadwal->fetch_assoc()) {
        $mata_kuliah_aktif[] = $row_jadwal['mata_kuliah'];
        $waktu_mulai = $row_jadwal['waktu_mulai'];
    }
    $query_jadwal->close(); // TUTUP STATEMENT

    if (empty($mata_kuliah_aktif)) {
        echo json_encode(["status" => "error", "message" => "Tidak ada mata kuliah aktif"]);
        exit();
    }
	
	// Ambil tanggal hari ini
	$tanggal_hari_ini = date("Y-m-d");

	// Cek apakah dosen sudah absen hari ini untuk mata kuliah dan kelas yang sama
	$cek_dosen_sudah_absen = false;
	$stmt_cek_dosen = null;
	$stmt_ditutup = false; // tambahkan flag

	foreach ($mata_kuliah_aktif as $mk) {
		$sql_cek_dosen = "SELECT * FROM absensi_dosen 
						  WHERE mata_kuliah = ? AND kelas = ? AND tanggal = ?";
		$stmt_cek_dosen = $conn->prepare($sql_cek_dosen);
		$stmt_cek_dosen->bind_param("sss", $mk, $kelas, $tanggal_hari_ini);
		$stmt_cek_dosen->execute();
		$result_cek_dosen = $stmt_cek_dosen->get_result();

		if ($result_cek_dosen->num_rows > 0) {
			$cek_dosen_sudah_absen = true;
			break;
		}

		$stmt_cek_dosen->close();
		$stmt_ditutup = true; // tandai bahwa sudah ditutup
	}

	// Tutup statement jika belum ditutup di dalam loop
	if (!$stmt_ditutup && $stmt_cek_dosen) {
		$stmt_cek_dosen->close();
	}

	if (!$cek_dosen_sudah_absen) {
		echo json_encode([
			"status" => "error",
			"message" => "Absensi dosen belum dilakukan hari ini untuk kelas $kelas. Mahasiswa tidak dapat absen."
		]);
		exit();
	}


    // Validasi data mahasiswa
    $cek_mahasiswa = $conn->prepare("SELECT * FROM mahasiswa WHERE nama = ? AND nim = ?");
    $cek_mahasiswa->bind_param("ss", $nama, $nim);
    $cek_mahasiswa->execute();
    $result_mahasiswa = $cek_mahasiswa->get_result();

    if ($result_mahasiswa->num_rows > 0) {
        $waktu_mulai_timestamp = strtotime($waktu_mulai);
        $waktu_sekarang_timestamp = strtotime($waktu_sekarang);
        $selisih_waktu = ($waktu_sekarang_timestamp - $waktu_mulai_timestamp) / 60;

        $keterangan = ($selisih_waktu <= 30) ? "Hadir" : "Terlambat";
    } else {
        echo json_encode(["status" => "error", "message" => "Mahasiswa tidak terdaftar"]);
        exit();
    }
    $cek_mahasiswa->close(); // TUTUP STATEMENT

    $absensi_berhasil = [];
    foreach ($mata_kuliah_aktif as $mata_kuliah) {
        // Cek apakah mahasiswa sudah absen untuk mata kuliah ini hari ini
        $cek_absensi = $conn->prepare("SELECT * FROM absensi WHERE nim = ? AND mata_kuliah = ? AND DATE(waktu_masuk) = CURDATE()");
        $cek_absensi->bind_param("ss", $nim, $mata_kuliah);
        $cek_absensi->execute();
        $result_absensi = $cek_absensi->get_result();

        if ($result_absensi->num_rows > 0) {
            $update_absensi = $conn->prepare("UPDATE absensi SET waktu_pulang = NOW(), keterangan = 'Pulang' WHERE nim = ? AND mata_kuliah = ?");
            $update_absensi->bind_param("ss", $nim, $mata_kuliah);
            $update_absensi->execute();
            $update_absensi->close();
            $absensi_berhasil[] = ["mata_kuliah" => $mata_kuliah, "status" => "pulang"];
        } else {
            $stmt = $conn->prepare("INSERT INTO absensi (nama, nim, mata_kuliah, suhu, kelas, waktu_masuk, tanggal, keterangan) VALUES (?, ?, ?, ?, ?, NOW(), CURDATE(), ?)");
            $stmt->bind_param("sssdss", $nama, $nim, $mata_kuliah, $suhu, $kelas, $keterangan);
            $stmt->execute();
            $stmt->close();
            $absensi_berhasil[] = ["mata_kuliah" => $mata_kuliah, "status" => "masuk"];
        }
        $cek_absensi->close();
    }

    echo json_encode(["status" => "success", "message" => "Absensi berhasil", "absensi" => $absensi_berhasil]);
    exit();
}

$query_kelas = $conn->query("SELECT DISTINCT kelas FROM jadwal");
$query_mata_kuliah = $conn->query("SELECT DISTINCT mata_kuliah FROM jadwal");

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Absensi Mahasiswa</title>
    <!-- Link Font Awesome untuk ikon -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
    body, html {
        font-family: Arial, sans-serif;
        background-color: #f8f9fa;
        display: flex;
        flex-direction: column;
    }

	/* Title Styling */
	h1 {
		text-align: center;
		margin: 30px 0;
		color: #007bff;
	}

	/* Judul utama (h2) */
	h2 {
		text-align: center;
		font-size: 32px;
		font-weight: bold;
		color: #212529;
		margin-top: 15px;
		text-transform: uppercase;
		letter-spacing: 2px;
		text-shadow: 2px 2px 5px rgba(0, 0, 0, 0.3);
		background: linear-gradient(90deg, #007bff, #0056b3);
		-webkit-background-clip: text;
		-webkit-text-fill-color: transparent;
	}

	/* SMART ABSENCE Styling */
	.smart {
		color: white;
		font-size: 15px;
		text-shadow: 1px 1px 3px black;
	}

	.absence {
		color: black;
		font-size: 20px;
		text-shadow: 1px 1px 3px white;
	}

	/* Filter Form Styling */
	form {
		display: flex;
		justify-content: center;
		gap: 20px;
		margin-bottom: 30px;
	}

	form label {
		font-size: 16px;
		font-weight: bold;
		margin-right: 10px;
	}

	form select, form input {
		padding: 10px;
		font-size: 16px;
		border-radius: 5px;
		border: 1px solid #ccc;
	}

	form button {
		background-color: #007bff;
		color: white;
		border: none;
		cursor: pointer;
		transition: background-color 0.3s;
		padding: 10px 20px;
		display: flex;
		align-items: center;
		gap: 10px;
		font-size: 16px;
		border-radius: 5px;
	}

	form button:hover {
		background-color: #0056b3;
	}

	/* ===================== TABLE STYLING ===================== */
	table {
		width: 95%;
		margin: 0 auto;
		border-collapse: collapse;
		box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
		border: 1px solid #ddd; /* Garis tepi tabel */
		border-radius: 8px;
		overflow: hidden;
		background-color: #fff;
	}

	thead {
		background-color: #f2f2f2;
		color: black;
		font-weight: 600;
	}

	th, td {
		padding: 12px 15px;
		text-align: center;
		border: 1px solid #ddd; /* Garis antar sel */
	}

	tr:nth-child(even) {
		background-color: #f9f9f9;
	}

	tr:hover {
		background-color: #f1f1f1;
	}

	td:nth-child(8) {
		white-space: nowrap;
		min-width: 120px;
	}
	
	table {
		font-size: 14px;
		font-family: 'Segoe UI', Arial, sans-serif;
	}
	th {
		font-size: 15px;
	}
	td {
		font-size: 14px;
	}

	/* Button Styling */
	button {
		background-color: #f44336;
		color: white;
		padding: 8px 15px;
		border: none;
		border-radius: 5px;
		cursor: pointer;
		transition: background-color 0.3s;
	}

	button:hover {
		background-color: #d32f2f;
	}

	/* Icon inside buttons */
	button i {
		margin-right: 5px;
	}

	/* Responsiveness for small screens */
	@media (max-width: 768px) {
		.navbar {
			flex-direction: column;
			align-items: center;
		}
		.navbar a {
			padding: 10px;
			margin-bottom: 10px;
		}
		form {
			flex-direction: column;
			align-items: center;
		}
		form input, form select {
			width: 100%;
			margin-bottom: 10px;
		}
	}

	/* Layout and container */
	.container {
		flex: 1; /* Konten utama memenuhi ruang yang tersedia */
	}

	footer {
		text-align: center;
		padding: 10px;
		width: 100%;
		bottom: 0;
	}

	/* Sidebar */
	.sidebar {
		width: 200px;
		background: #007bff;
		padding-top: 20px;
		position: fixed;
		left: -200px; /* Sidebar tersembunyi */
		transition: left 0.3s;
		bottom: 0;
		top: 50px; /* Sesuaikan agar tidak menutupi header */
		overflow-y: auto; /* Bisa di-scroll jika kontennya panjang */
		z-index: 999; /* Pastikan tidak tertutup elemen lain */
	}

    .sidebar a {
        display: block;
        padding: 15px;
        color: white;
        text-decoration: none;
        font-size: 18px;
    }

	.sidebar a:hover {
		background: rgba(255, 255, 255, 0.2); /* Efek hover lebih lembut */
	}


	/* Hamburger Menu Button */
	.menu-btn {
		font-size: 20px;
		cursor: pointer;
		background: none;
		border: none;
		color: white;
		padding: 5px 10px;
		position: fixed;
		left: 10px;
		top: 10px;
		border-radius: 5px;
		z-index: 1000;
	}

	/* Sidebar active state */
	.sidebar.active {
		left: 0;
	}

	.sidebar.active ~ .content {
		margin-left: 170px;
	}

	.sidebar.active ~ .footer {
		left: 10px;
		width: calc(100% - 200px);
		transition: left 0.3s ease-in-out, width 0.3s ease-in-out;
	}

	/* Main content */
	.content {
		flex: 1;
		margin-left: 5px;
		margin-top: 20px;
		padding: 30px;
		transition: margin-left 0.3s;
		width: 100%;
		text-align: center;
	}

	.content.shifted {
		margin-left: 250px;
	}

	/* Header */
	.header {
		display: flex;
		align-items: center;
		justify-content: space-between;
		background-color: #007bff;
		padding: 0px;
		color: white;
		width: 100%;
		height: 60px;
		position: fixed;
		top: 0;
		left: 0;
		z-index: 1000;
	}

	/* Title inside header */
	.title {
		font-size: 20px;
		font-weight: bold;
		color: white;
		margin-left: auto;
		margin-right: 5px;
		text-transform: uppercase;
	}

	/* PDF Button Styling */
	.btn-pdf {
		background: linear-gradient(135deg, #e60000, #b30000); 
		color: #fff !important;
		font-weight: bold;
		padding: 10px 20px;
		border-radius: 30px;
		border: none;
		transition: all 0.3s ease-in-out;
		box-shadow: 0 4px 10px rgba(0, 0, 0, 0.15);
		text-decoration: none;
		display: inline-block;
	}

	.btn-pdf:hover {
		background: linear-gradient(135deg, #b30000, #990000);
		transform: scale(1.05);
		box-shadow: 0 6px 15px rgba(0, 0, 0, 0.25);
		text-decoration: none;
	}

	.btn-pdf i {
		margin-right: 8px;
	}
	
	.text-left {
        text-align: left;
    }
    </style>
</head>
<body>

   <!-- Tombol Hamburger -->
	<div class="header">
		<button class="menu-btn" onclick="toggleSidebar()">
			<i class="fas fa-bars"></i>
		</button>
		<h1 class="title">
			<span class="smart">SMART</span> <span class="absence">PRESENCE</span>
		</h1>
	</div>

    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
        <a href="tambah.php"><i class="fas fa-user-plus"></i> Tambah Data</a>
        <a href="mahasiswa.php"><i class="fas fa-users"></i> Data Mahasiswa</a>
        <a href="dosen.php"><i class="fas fa-user-tie"></i> Data Dosen</a>
        <a href="absensi.php"><i class="fas fa-calendar-check"></i> Data Presensi</a>
        <a href="absensi_dosen.php"><i class="fas fa-chalkboard-teacher"></i> Data Presensi Dosen</a>
        <a href="jadwal.php"><i class="fas fa-calendar-alt"></i> Jadwal</a>
    </div>
	
	 <script>
        function toggleSidebar() {
            let sidebar = document.getElementById("sidebar");
            let content = document.getElementById("content");
            sidebar.classList.toggle("active");
            content.classList.toggle("shifted");
        }

        document.addEventListener("click", function(event) {
            let sidebar = document.getElementById("sidebar");
            let menuBtn = document.querySelector(".menu-btn");
            if (!sidebar.contains(event.target) && !menuBtn.contains(event.target)) {
                sidebar.classList.remove("active");
                document.getElementById("content").classList.remove("shifted");
            }
        });	
    </script>
	
	<div class="content">
		<h2 class="text-center my-4">Data Presensi Mahasiswa</h2>
	<div class="table-responsive">
    <!-- Filter Form -->
	<form id="filterForm" method="GET" action="absensi.php">
    <label for="kelas">Kelas:</label>
    <select name="kelas" id="kelas">
        <option value="">-- Pilih Kelas --</option>
        <?php while ($kelas_row = $query_kelas->fetch_assoc()) { ?>
            <option value="<?php echo $kelas_row['kelas']; ?>" 
                <?php echo (isset($_GET['kelas']) && $_GET['kelas'] == $kelas_row['kelas']) ? 'selected' : ''; ?>>
                <?php echo $kelas_row['kelas']; ?>
            </option>
        <?php } ?>
    </select>

    <label for="mata_kuliah">Mata Kuliah:</label>
    <select name="mata_kuliah" id="mata_kuliah">
        <option value="">-- Pilih Mata Kuliah --</option>
        <?php while ($mata_kuliah_row = $query_mata_kuliah->fetch_assoc()) { ?>
            <option value="<?php echo $mata_kuliah_row['mata_kuliah']; ?>" 
                <?php echo (isset($_GET['mata_kuliah']) && $_GET['mata_kuliah'] == $mata_kuliah_row['mata_kuliah']) ? 'selected' : ''; ?>>
                <?php echo $mata_kuliah_row['mata_kuliah']; ?>
            </option>
        <?php } ?>
    </select>

    <label for="tanggal">Tanggal:</label>
    <input type="date" name="tanggal" id="tanggal" value="<?php echo isset($_GET['tanggal']) ? $_GET['tanggal'] : ''; ?>">

    <button type="submit"><i class="fas fa-search"></i> Filter</button>
	<script>
	  // Ambil form dan semua elemen filter
	  const filterForm = document.getElementById('filterForm');
	  const fields = [
		document.getElementById('kelas'),
		document.getElementById('mata_kuliah'),
		document.getElementById('tanggal')
	  ];

	  // Pasang event listener onchange untuk setiap field
	  fields.forEach(field => {
		field.addEventListener('change', () => {
		  filterForm.submit();
		});
	  });
	</script>
		<!-- Jika pakai Font Awesome -->
	<a href="cetakabsensi.php?kelas=<?= $_GET['kelas'] ?? '' ?>&mata_kuliah=<?= $_GET['mata_kuliah'] ?? '' ?>&tanggal=<?= $_GET['tanggal'] ?? '' ?>"
	   class="btn-pdf">
	   <i class="fa fa-file-pdf"></i> Cetak ke PDF
	</a>

</form>

<table class="table table-striped table-bordered">
    <thead>
        <tr>
            <th>No</th>
            <th>Nama</th>
            <th>NIM</th>
            <th>Mata Kuliah</th>
            <th>Suhu (&deg;C)</th>
			<th>Kondisi Tubuh</th>
            <th>Kelas</th>
			<th>Tanggal</th>
            <th>Waktu Masuk</th>
            <th>Waktu Pulang</th>
            <th>Keterangan</th>
            <th>Aksi</th>
        </tr>
    </thead>
    <tbody>
        <?php
        $no = 1;
        while ($row = $result->fetch_assoc()) {
			$suhu = $row['suhu'];
			if (!is_null($suhu)) {
				$kondisi_suhu = ($suhu > 37.5) 
					? "<span style='color:red; font-weight:bold;'>Suhu Tinggi</span>" 
					: "<span style='color:green; font-weight:bold;'>Sehat</span>";
			} else {
				$kondisi_suhu = "<span style='color:gray; font-weight:bold;'>-</span>";
			}
            echo "<tr>";
            echo "<td>" . $no++ . "</td>";
			echo "<td class='text-left'>" . htmlspecialchars($row['nama']) . "</td>";
            echo "<td>" . htmlspecialchars($row['nim']) . "</td>";
            echo "<td>" . htmlspecialchars($row['mata_kuliah']) . "</td>";
            echo "<td>" . (!is_null($row['suhu']) ? number_format(floor($row['suhu'] * 10) / 10, 1) . " &deg;C" : "-") . "</td>";
			echo "<td>" . $kondisi_suhu . "</td>";
            echo "<td>" . htmlspecialchars($row['kelas']) . "</td>";
			echo "<td>" . date('d-m-Y', strtotime($row['waktu_masuk'])) . "</td>";
			if (isset($row['keterangan']) && in_array($row['keterangan'], ['Sakit', 'Izin', 'Alpa'])) {
				echo "<td>-</td>";
			} else {
				// Pastikan waktu_masuk valid sebelum diproses date()
				if (!empty($row['waktu_masuk']) && $row['waktu_masuk'] != "-" && strtotime($row['waktu_masuk']) !== false) {
					echo "<td>" . date('H:i:s', strtotime($row['waktu_masuk'])) . "</td>";
				} else {
					echo "<td>-</td>";
				}
			}
            echo "<td>" . ($row['waktu_pulang'] ? date('H:i:s', strtotime($row['waktu_pulang'])) : "-") . "</td>";
            echo "<td>" . htmlspecialchars($row['keterangan']) . "</td>";
            echo "<td>
                <a href='?delete_id=" . $row['id'] . "&kelas=" . urlencode($_GET['kelas'] ?? '') . "&mata_kuliah=" . urlencode($_GET['mata_kuliah'] ?? '') . "&tanggal=" . urlencode($_GET['tanggal'] ?? '') . "' 
                onclick='return confirm(\"Apakah Anda yakin ingin menghapus data ini?\")'>
                <button><i class='fas fa-trash-alt'></i> Hapus</button>
                </a>
            </td>";
            echo "</tr>";
        }
        ?>
    </tbody>
</table>

<footer>
    &copy; <?php echo date("Y"); ?> Smart Presence D4 Teknik Elektronika. All rights reserved.
</footer>

<script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>

<?php
$conn->close();
?>
