<?php
session_start();

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}
// Koneksi ke database
include('koneksi.php');

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

// Query untuk mengambil semua mata kuliah yang sedang berlangsung
$query = "SELECT * FROM jadwal 
          WHERE hari = '$hari_sekarang' 
          AND '$waktu_sekarang' BETWEEN waktu_mulai AND waktu_selesai
          ORDER BY kelas ASC"; // Urutkan berdasarkan kelas

$result = mysqli_query($conn, $query);

// Kelompokkan data berdasarkan kelas
$jadwal_kelas = [];
while ($row = mysqli_fetch_assoc($result)) {
    $jadwal_kelas[$row['kelas']][] = $row;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Absensi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
	<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;700&display=swap" rel="stylesheet">
    <style>
		/* Reset */
		* {
			margin: 0;
			padding: 0;
			box-sizing: border-box;
			font-family: 'Arial', sans-serif;
		}
		
		/* Navbar */
		.navbar {
			position: fixed;
			top: 0;
			left: 0;
			width: 100%;
			background: #007bff;
			color: white;
			padding: 10px 20px;
			display: flex;
			align-items: center;
			justify-content: space-between;
			box-shadow: 0px 2px 5px rgba(0, 0, 0, 0.2);
			z-index: 1000;
		}

		.navbar h1 {
			font-size: 25px;
			font-weight: bold;
			text-transform: uppercase;
			letter-spacing: 1px;
		}

		.navbar h1 span {
			font-weight: normal;
			color: #fff;
		}
		.text-white {
			color: white !important;
			font-size: 20px;
			font-weight: bold;
			text-shadow: 1px 1px 3px black;
		}

		.text-dark {
			color: black !important;
			font-size: 25px;
			font-weight: bold;
			text-shadow: 1px 1px 3px white;
		}

		/* Tombol Toggle Sidebar */
		.menu-toggle {
			background: transparent;
			border: none;
			color: white;
			font-size: 24px;
			cursor: pointer;
			transition: color 0.3s;
		}

		.menu-toggle:hover {
			color: #f8f9fa;
		}

		/* Sidebar */
		.sidebar {
            width: 200px;
            height: 100vh;
            position: fixed;
            top: 0;
            left: -200px; /* Default disembunyikan */
            background: #007bff;
            color: white;
            padding-top: 60px;
            transition: left 0.3s ease-in-out;
            box-shadow: 2px 0 5px rgba(0, 0, 0, 0.2);
            overflow-y: auto;
        }

        .sidebar.show {
            left: 0;
        }
		.sidebar-hidden {
			transform: translateX(-100%);
		}

		/* Styling menu sidebar */
		.sidebar ul {
			list-style: none;
			padding-left: 0;
		}

		.sidebar ul li {
			padding: 10px 20px;
		}

		.sidebar ul li a {
			text-decoration: none;
			color: white;
			font-size: 16px;
			display: flex;
			align-items: center;
			gap: 10px;
			transition: background 0.3s;
			padding: 10px;
			border-radius: 5px;
		}

		.sidebar ul li a:hover {
			background: #495057;
		}

		/* Main content */
		.main-content {
            margin-left: 0;
            padding: 70px 20px 20px;
            min-height: 100vh;
            background: #f1f1f1;
            transition: margin-left 0.3s;
        }

        .sidebar.show + .main-content {
            margin-left: 200px;
        }
		/* Jika sidebar disembunyikan */
		.sidebar-hidden + .main-content {
			margin-left: 0;
		}
		
		/* Gaya untuk Selamat Datang */
		.welcome-title {
			font-family: 'Poppins', sans-serif;
			font-size: 36px;
			font-weight: 700;
			color: #222; /* Warna lebih elegan */
			text-align: center;
			margin-top: 15px;
			text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.2);
		}

		/* Gaya untuk Mata Kuliah Sedang Berlangsung */
		.course-title {
			font-family: 'Poppins', sans-serif;
			font-size: 28px;
			font-weight: 600;
			color: #0056b3; /* Warna biru modern */
			text-align: center;
			margin: 20px auto; /* Pusatkan dengan margin auto */
			padding: 10px 20px;
			border-radius: 8px;
			background: linear-gradient(45deg, #e3f2fd, #bbdefb); /* Efek gradasi */
			box-shadow: 0px 4px 6px rgba(0, 0, 0, 0.1);
			width: fit-content; /* Lebar menyesuaikan isi */
		}

		.custom-card {
			border-radius: 15px; /* Membuat sudut lebih membulat */
			box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1); /* Efek bayangan */
			padding: 20px;
			text-align: center;
			transition: transform 0.3s ease-in-out;
		}
		.custom-card:hover {
			transform: scale(1.05); /* Animasi saat hover */
		}
		.custom-title {
			font-size: 18px;
			font-weight: bold;
			color: #007bff; /* Warna biru khas Bootstrap */
		}
		.custom-number {
			font-size: 30px;
			font-weight: bold;
			color: #333;
		}
		.filter-select {
			width: 100%;
			padding: 8px;
			border-radius: 10px;
			border: 1px solid #ddd;
		}
		@media (max-width: 768px) {
			.custom-card {
				margin-bottom: 15px;
			}
		}

		/* Gaya untuk Tabel */
		.table {
			width: 80%; /* Sesuaikan agar tidak terlalu lebar */
			border-collapse: collapse;
			background: #fff;
			box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.1);
			border-radius: 10px;
			overflow: hidden;
		}

		/* Gaya untuk Header Tabel */
		.table th {
			background: #007bff;
			color: white;
			padding: 10px;
			text-align: center;
		}

		/* Gaya untuk Isi Tabel */
		.table td {
			padding: 10px;
			text-align: center;
			border-bottom: 1px solid #ddd;
		}
		
		/* Menjadikan Container Mata Kuliah Berada di Tengah */
		.course-container {
			display: flex;
			flex-direction: column;
			align-items: center;
			width: 100%;
		}

		/* Menyesuaikan Card Mata Kuliah */
		.course-container .card {
			width: 80%;
			margin: auto;
			box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.1);
			border-radius: 10px;
		}

		/* Mengatur Tabel Mata Kuliah agar Lebih Rapi */
		.course-container .table {
			width: 100%;
			text-align: center;
			border-collapse: collapse;
		}

		.course-container .table th {
			background: #007bff;
			color: white;
			padding: 12px;
			text-align: center; 
		}

		.course-container .table td {
			padding: 10px;
			border-bottom: 1px solid #ddd;
		}

		/* Menyesuaikan Header Mata Kuliah */
		.course-title {
			text-align: center;
			font-size: 24px;
			font-weight: bold;
			color: #333;
			padding: 15px;
		}

		/* Responsif */
		@media (min-width: 768px) {
            .sidebar {
                left: 0; /* Pastikan sidebar terlihat di layar besar */
            }
            .main-content {
                margin-left: 200px;
            }
        }
		.filter-select {
			width: 80%;
			padding: 8px;
			border-radius: 8px;
			border: 1px solid #ddd;
			font-size: 16px;
		}

		.card-total-mahasiswa,
		.card-total-dosen,
		.card-total-absensi {
			width: 100%; 
			height: 120px; /* Pastikan tinggi semua card sama */
			text-align: center;
			padding: 20px;
			border-radius: 10px;
			font-weight: bold;
			display: flex;
			align-items: center;
			justify-content: center;
		}

		.card-total-mahasiswa {
			background-color: #4caf50; /* Kuning */
			color: #000;
		}

		.card-total-dosen {
			background-color: #ffcc00; /* Hijau */
			color: #000;
		}

		.card-total-absensi {
			background-color: #f44336; /* Merah */
			color: #000;
		}

    </style>
</head>
<body>

<!-- Navbar -->
<nav class="navbar">
    <button class="menu-toggle"><i class="fas fa-bars"></i></button>
    <h1><span class="text-white">SMART</span> <span class="text-dark">PRESENCE</span></h1>
</nav>

<!-- Sidebar -->
<nav class="sidebar">
    <ul>
        <li><a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a></li>
        <li><a href="tambah.php"><i class="fas fa-user-plus"></i> Tambah Data</a></li>
        <li><a href="mahasiswa.php"><i class="fas fa-users"></i> Data Mahasiswa</a></li>
        <li><a href="dosen.php"><i class="fas fa-chalkboard-teacher"></i> Data Dosen</a></li>
		<li><a href="absensimanual.php"><i class="fas fa-file-signature"></i> Presensi Manual</a></li>
        <li><a href="absensi.php"><i class="fas fa-check-circle"></i> Data Presensi</a></li>
        <li><a href="absensi_dosen.php"><i class="fas fa-chalkboard"></i> Data Presensi Dosen</a></li>
        <li><a href="jadwal.php"><i class="fas fa-calendar-alt"></i> Jadwal</a></li>
		<li><a href="logout.php" onclick="return confirm('Yakin ingin logout?')"> <i class="fas fa-sign-out-alt"></i> Logout</a></li>
    </ul>
</nav>

    <!-- Konten Utama -->
    <div class="main-content">
        <header>
            <h1 class="welcome-title">Selamat Datang di Sistem <span style="color:#007bff;">SMART PRESENCE</span></h1>
        </header>
    <div class="row">
	
	<!-- Total Mahasiswa per Kelas atau Semua -->
	<div class="col-md-4">
		<div class="card card-total-mahasiswa">
			<div class="card-body text-center">
				<h5><i class="fas fa-users"></i> Total Mahasiswa</h5>

				<form method="GET">
					<select name="kelas" class="filter-select" onchange="this.form.submit()">
						<option value="">Semua Kelas</option>
						<?php
						$kelasQuery = "SELECT DISTINCT kelas FROM mahasiswa ORDER BY kelas";
						$kelasResult = mysqli_query($conn, $kelasQuery);
						while ($kelasRow = mysqli_fetch_assoc($kelasResult)) {
							$selected = (isset($_GET['kelas']) && $_GET['kelas'] == $kelasRow['kelas']) ? 'selected' : '';
							echo "<option value='{$kelasRow['kelas']}' $selected>{$kelasRow['kelas']}</option>";
						}
						?>
					</select>
				</form>

				<?php
				// Jika kelas dipilih, filter berdasarkan kelas
				if (isset($_GET['kelas']) && !empty($_GET['kelas'])) {
					$kelas = mysqli_real_escape_string($conn, $_GET['kelas']);
					$query = "SELECT COUNT(*) as total FROM mahasiswa WHERE kelas = '$kelas'";
					$label = "Kelas " . htmlspecialchars($kelas);
				} else {
					// Jika tidak, tampilkan total semua mahasiswa
					$query = "SELECT COUNT(*) as total FROM mahasiswa";
					$label = "Semua Kelas";
				}

				$result = mysqli_query($conn, $query);
				$data = mysqli_fetch_assoc($result);
				echo "<h3>" . number_format($data['total']) . " Mahasiswa</h3>";
				?>
			</div>
		</div>
	</div>

            <!-- Total Dosen -->
            <div class="col-md-4">
                <div class="card card-total-dosen">
                    <div class="card-body text-center">
                        <h5><i class="fas fa-chalkboard-teacher"></i> Total Dosen</h5>
                        <?php
                        $query = "SELECT COUNT(*) as total FROM dosen";
                        $result = mysqli_query($conn, $query);
                        $data = mysqli_fetch_assoc($result);
                        echo "<h3>" . number_format($data['total']) . "</h3>";
                        ?>
                    </div>
                </div>
            </div>

		<!-- Total Absensi Hari Ini -->
		<div class="col-md-4">
			<div class="card card-total-absensi">
            <div class="card-body text-center">
					<h5><i class="fas fa-check-circle"></i> Total Presensi Hari Ini</h5>
					<?php
					$query = "SELECT COUNT(*) as total FROM absensi WHERE tanggal = CURDATE()";
					$result = mysqli_query($conn, $query);
					$data = mysqli_fetch_assoc($result);
					echo "<h3>" . number_format($data['total']) . "</h3>";
					?>
				</div>
			</div>
		</div>

      <!-- Mata Kuliah Sedang Berlangsung -->
		<div class="container-fluid mt-4">
			<h2 class="course-title text-center">📚 Mata Kuliah Sedang Berlangsung</h2>

			<?php if (!empty($jadwal_kelas)): ?>
				<?php foreach ($jadwal_kelas as $kelas => $jadwal): ?>
					<div class="card mt-3">
						<div class="card-header bg-primary text-white text-center">
							<h5 class="mb-0">Kelas <?= htmlspecialchars($kelas) ?></h5>
						</div>
						<div class="card-body">
							<div class="table-responsive">
								<table class="table table-bordered table-hover text-center">
									<thead class="table-primary">
										<tr>
											<th>Mata Kuliah</th>
											<th>Hari</th>
											<th>Waktu Mulai</th>
											<th>Waktu Selesai</th>
											<th>Kelas</th>
											<th>Nama Dosen</th>
										</tr>
									</thead>
									<tbody>
										<?php foreach ($jadwal as $data): ?>
											<tr>
												<td><?= htmlspecialchars($data['mata_kuliah']) ?></td>
												<td><?= htmlspecialchars($data['hari']) ?></td>
												<td><?= htmlspecialchars($data['waktu_mulai']) ?></td>
												<td><?= htmlspecialchars($data['waktu_selesai']) ?></td>
												<td><?= htmlspecialchars($data['kelas']) ?></td>
												<td><?= htmlspecialchars($data['nama_dosen']) ?></td>
											</tr>
										<?php endforeach; ?>
									</tbody>
								</table>
							</div>
						</div>
					</div>
				<?php endforeach; ?>
			<?php else: ?>
				<div class="alert alert-warning text-center">
					<strong>Tidak ada mata kuliah yang sedang berlangsung saat ini.</strong>
				</div>
			<?php endif; ?>
		</div>

	<!-- Data Absensi Hari Ini -->
	<div class="card mt-4">
		<div class="card-header bg-primary text-white text-center">
			<h5><i class="fas fa-table"></i> Data Presensi Hari Ini</h5>
		</div>
		<div class="card-body">

			<!-- Dropdown Filter Kelas -->
			<form method="GET">
				<div class="form-group">
					<label for="kelas">Pilih Kelas:</label>
					<select name="kelas" class="form-control" onchange="this.form.submit()">
						<option value="">Semua Kelas</option>
						 <?php
						// Urutkan kelas secara logis (1A, 1B, ..., 4B)
						$kelasQuery = "SELECT DISTINCT kelas FROM absensi ORDER BY 
									   CAST(SUBSTRING(kelas, 1, 1) AS UNSIGNED), 
									   SUBSTRING(kelas, 2, 1)";
						$kelasResult = mysqli_query($conn, $kelasQuery);
						while ($kelasRow = mysqli_fetch_assoc($kelasResult)) {
							$selected = (isset($_GET['kelas']) && $_GET['kelas'] == $kelasRow['kelas']) ? 'selected' : '';
							echo "<option value='{$kelasRow['kelas']}' $selected>{$kelasRow['kelas']}</option>";
						}
						?>
					</select>
				</div>
			</form>

		<!-- Tabel Data Absensi -->
		<div class="table-responsive">
			<table class="table table-bordered table-striped text-center">
                <thead class="table-primary">
					<tr>
						<th>No</th>
						<th>Nama</th>
						<th>Kelas</th>
						<th>Mata Kuliah</th>
						<th>Suhu (&deg;C)</th>
						<th>Kondisi Tubuh</th>
						<th>Keterangan</th>							
					</tr>
				</thead>
				<tbody>
					<?php
					// Ambil filter kelas dari dropdown
					$kelasFilter = isset($_GET['kelas']) ? mysqli_real_escape_string($conn, $_GET['kelas']) : '';

					// Query untuk data absensi hari ini dengan filter kelas
					$query = "SELECT * FROM absensi WHERE tanggal = CURDATE()";
					if (!empty($kelasFilter)) {
						$query .= " AND kelas = '$kelasFilter'";
					}
					$query .= " ORDER BY 
                                CAST(SUBSTRING(kelas, 1, 1) AS UNSIGNED), 
                                SUBSTRING(kelas, 2, 1), 
                                nama ASC";
					
					$result = mysqli_query($conn, $query);
					$no = 1;					
					while ($row = mysqli_fetch_assoc($result)) {
						$suhu = $row['suhu'];
						if (!is_null($suhu)) {
							$kondisi_suhu = ($suhu > 37.5) 
								? "<span style='color:red; font-weight:bold;'>Suhu Tinggi</span>" 
								: "<span style='color:green; font-weight:bold;'>Sehat</span>";
						} else {
							$kondisi_suhu = "<span style='color:gray; font-weight:bold;'>-</span>";
						}
						echo "<tr>";
						echo "<td>{$no}</td>";
						echo "<td>{$row['nama']}</td>";
						echo "<td>{$row['kelas']}</td>";
						echo "<td>{$row['mata_kuliah']}</td>";
						echo "<td>" . (!is_null($row['suhu']) ? number_format(floor($row['suhu'] * 10) / 10, 1) . " &deg;C" : "-") . "</td>";
						echo "<td>" . $kondisi_suhu . "</td>";
						echo "<td>{$row['keterangan']}</td>";
						echo "</tr>";
						$no++;
					}
					?>
				</tbody>
			</table>
			</div>
		</div>
	</div>
	
	<footer>
        &copy; <?php echo date("Y"); ?> Smart Presence D4 Teknik Elektronika. All rights reserved.
    </footer>
	
	<script>
		document.addEventListener("DOMContentLoaded", function () {
			const menuToggle = document.querySelector(".menu-toggle");
			const sidebar = document.querySelector(".sidebar");
			const mainContent = document.querySelector(".main-content");

			menuToggle.addEventListener("click", function () {
				sidebar.classList.toggle("show");
				mainContent.classList.toggle("expanded");
			});
		});
	</script>

	<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
