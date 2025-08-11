<?php
include 'koneksi.php'; // Pastikan koneksi ke database
date_default_timezone_set('Asia/Jakarta');

$pesan = "";

// Ambil data mahasiswa untuk dropdown
$mahasiswa_query = "SELECT * FROM mahasiswa";
$mahasiswa_result = mysqli_query($conn, $mahasiswa_query);

// Ambil data mata kuliah berdasarkan kelas dari tabel jadwal
$jadwal_query = "SELECT kelas, mata_kuliah FROM jadwal";
$jadwal_result = mysqli_query($conn, $jadwal_query);

if (!$jadwal_result) {
    die("Error mengambil data jadwal: " . mysqli_error($conn));
}

// Ambil tanggal hari ini otomatis
$tanggal_hari_ini = date('Y-m-d');

// Proses Absensi Manual
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nim = $_POST['nim'] ?? "";
    $nama = $_POST['nama'] ?? "";
    $mata_kuliah = $_POST['mata_kuliah'] ?? "";
    $kelas = $_POST['kelas'] ?? "";
    $waktu_masuk = $_POST['waktu_masuk'] ?? "";
    $keterangan = $_POST['keterangan'] ?? "Hadir";

    // Gabungkan tanggal dengan waktu masuk
if ($keterangan == "Hadir") {
    $waktu_masuk_lengkap = "$tanggal_hari_ini $waktu_masuk";
} else {
    // Beri default atau NULL
    $waktu_masuk_lengkap = "$tanggal_hari_ini 00:00:00";
}


    if (!empty($nim) && !empty($nama) && !empty($mata_kuliah) && !empty($kelas) && !empty($keterangan)) {
        // Cek apakah mahasiswa sudah absen untuk mata kuliah yang sama hari ini
        $cek_query = "SELECT * FROM absensi WHERE nim=? AND mata_kuliah=? AND tanggal=?";
        $stmt = $conn->prepare($cek_query);
        $stmt->bind_param("sss", $nim, $mata_kuliah, $tanggal_hari_ini);
        $stmt->execute();
        $cek_result = $stmt->get_result();

        if ($cek_result->num_rows > 0) {
            $pesan = "Mahasiswa dengan NIM $nim sudah melakukan absensi untuk mata kuliah $mata_kuliah hari ini!";
        } else {
            // Gunakan prepared statement untuk menghindari SQL Injection
            $query = "INSERT INTO absensi (nim, nama, mata_kuliah, kelas, waktu_masuk, tanggal, keterangan) 
                      VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("sssssss", $nim, $nama, $mata_kuliah, $kelas, $waktu_masuk_lengkap, $tanggal_hari_ini, $keterangan);

            if ($stmt->execute()) {
                $pesan = "Absensi berhasil disimpan.";
            } else {
                $pesan = "Error: " . $stmt->error;
            }
        }
        $stmt->close();
    } else {
        $pesan = "Harap isi semua data!";
    }
}

// Format data mata kuliah berdasarkan kelas
$mata_kuliah_data = [];
while ($row = mysqli_fetch_assoc($jadwal_result)) {
    $kelas = $row['kelas'];
    $mata_kuliah = $row['mata_kuliah'];
    $mata_kuliah_data[$kelas][] = $mata_kuliah;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Absensi Manual</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0/dist/css/select2.min.css" rel="stylesheet">
	
    <style>
		/* ======== RESET DAN STRUKTUR DASAR ======== */
		body {
			display: flex;
			flex-direction: column;
			margin: 0;
			font-family: Arial, sans-serif;
			padding-top: 30px;
			min-height: 100vh; 
			background-color: #f8f9fa;
		}

		/* ======== HEADER ======== */
		.header {
			display: flex;
			align-items: center;
			justify-content: space-between;
			background-color: #007bff;
			padding: 10px 20px;
			color: white;
			width: 100%;
			height: 60px;
			position: fixed;
			top: 0;
			left: 0;
			z-index: 1000;
		}

		/* ======== SIDEBAR ======== */
		.sidebar {
			width: 220px;
			height: calc(100vh - 60px);
			background: #007bff;
			padding-top: 20px;
			position: fixed;
			left: -220px;
			top: 60px;
			transition: left 0.3s ease-in-out;
			overflow-y: auto;
			box-shadow: 2px 0 5px rgba(0, 0, 0, 0.1);
			z-index: 999;
		}

		.sidebar a {
			display: block;
			padding: 12px 20px;
			color: white;
			text-decoration: none;
			font-size: 16px;
			border-bottom: 1px solid rgba(255, 255, 255, 0.2);
		}

		.sidebar a:hover {
			background: #f0f0f0;
			color: #007bff;
		}

		/* Sidebar Aktif */
		.sidebar.active {
			left: 0;
		}

		/* ======== TOMBOL MENU ======== */
		.menu-btn {
			font-size: 24px;
			cursor: pointer;
			background: none;
			border: none;
			color: white;
			padding: 10px;
			z-index: 1000;
		}

		/* ======== KONTEN UTAMA ======== */
		.main-content {
			display: flex;
			justify-content: center;
			align-items: center;
			min-height: calc(100vh - 60px);
			margin-left: 0;
			transition: margin-left 0.3s ease;
			padding: 20px;
		}

		/* Jika sidebar aktif, konten utama ikut bergeser */
		.sidebar.active ~ .main-content {
			margin-left: 220px;
		}

		/* ======== FORM (AGAR TENGAH & RAPI) ======== */
		.table-form {
			width: 80%;
			max-width: 600px;
			background: #ffffff;
			border-radius: 8px;
			box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.1);
			padding: 20px;
			text-align: left;
			margin: auto;
		}

		/* Header dan Cell */
		.table-form th, 
		.table-form td {
			padding: 12px 15px;
			border: 1px solid #ddd;
			text-align: left;
		}

		/* Header Styling */
		.table-form th {
			background-color: #007bff;
			color: white;
			text-transform: uppercase;
		}

		/* Warna Baris Genap */
		.table-form tr:nth-child(even) {
			background-color: #f9f9f9;
		}

		/* Hover Efek */
		.table-form tr:hover {
			background-color: #f1f1f1;
		}

		/* Input dan Select di Dalam Tabel */
		.table-form input, 
		.table-form select {
			width: 100%;
			padding: 10px;
			border: 1px solid #ccc;
			border-radius: 5px;
			font-size: 14px;
		}

		/* Tombol dalam Tabel */
		.table-form button {
			background: #007bff;
			color: white;
			border: none;
			padding: 10px 15px;
			border-radius: 4px;
			cursor: pointer;
			font-size: 14px;
			transition: background 0.3s ease;
		}

		.table-form button:hover {
			background: #0056b3;
		}
		
		.card-header h3 {
			font-weight: bold;
			color: white;
			text-shadow: 
				-2px -2px 0px black,  
				 2px -2px 0px black,  
				-2px  2px 0px black,  
				 2px  2px 0px black,  
				 0px  0px 5px rgba(0, 0, 0, 0.6); /* Glow lebih halus */
			letter-spacing: 1px;
			font-size: 22px;
		}

		/* ======== FOOTER ======== */
		.footer {
		padding: 10px;
			width: 100%;
			position: relative;
			bottom: 0;;
			text-align: center;
			padding: 10px;
			transition: margin-left 0.3s ease-in-out;
		}

		/* Jika sidebar terbuka, geser footer juga */
		.sidebar.active ~ .footer,
		.footer.shifted {
			margin-left: 220px; /* Sesuaikan dengan lebar sidebar */
		}

		/* ======== RESPONSIF UNTUK MOBILE ======== */
		@media screen and (max-width: 768px) {
			/* Sidebar lebih kecil */
			.sidebar {
				width: 180px;
				left: -180px;
			}

			.sidebar.active {
				left: 0;
			}

			/* Konten utama lebih fleksibel */
			.main-content {
				margin-left: 0;
				padding: 15px;
			}

			.sidebar.active ~ .main-content {
				margin-left: 200px;
			}

			/* Tabel lebih fleksibel */
			.table-form {
				width: 95%;
				font-size: 14px;
			}

			.table-form th,
			.table-form td {
				padding: 10px;
			}

			.table-form input,
			.table-form select {
				font-size: 12px;
				padding: 8px;
			}

			.table-form button {
				font-size: 12px;
				padding: 8px 12px;
			}

			/* Menu tombol lebih kecil */
			.menu-btn {
				font-size: 18px;
			}
		}

		/* ======== TEKS KHUSUS ======== */
		.smart {
			color: white;
			font-size: 15px;
			font-weight: bold;
			text-shadow: 1px 1px 3px black;
		}

		.absence {
			color: black;
			font-size: 20px;
			font-weight: bold;
			text-shadow: 1px 1px 3px white;
		}
    </style>
	
<script>
    // Data mahasiswa dikelompokkan berdasarkan kelas
    var mahasiswaByKelas = <?php 
        $kelasMahasiswa = [];
        $result = mysqli_query($conn, "SELECT * FROM mahasiswa");
        while ($row = mysqli_fetch_assoc($result)) {
            $kelasMahasiswa[$row['kelas']][] = ['nim' => $row['nim'], 'nama' => $row['nama']];
        }
        echo json_encode($kelasMahasiswa);
    ?>;

    // Data lengkap mahasiswa (untuk ambil nama dan kelas berdasarkan NIM)
    var mahasiswaData = <?php 
        $data = [];
        $result = mysqli_query($conn, "SELECT * FROM mahasiswa");
        while ($row = mysqli_fetch_assoc($result)) {
            $data[$row['nim']] = ['nama' => $row['nama'], 'kelas' => $row['kelas']];
        }
        echo json_encode($data);
    ?>;

    var semuaMataKuliah = <?php echo json_encode($mata_kuliah_data); ?>;

    function updateNIMs() {
        var selectedKelas = document.getElementById("kelas").value;
        var nimSelect = document.getElementById("nim");

        nimSelect.innerHTML = "<option value=''>Pilih NIM</option>";

        if (selectedKelas in mahasiswaByKelas) {
            mahasiswaByKelas[selectedKelas].forEach(function (mhs) {
                var option = document.createElement("option");
                option.value = mhs.nim;
                option.text = mhs.nim + " - " + mhs.nama;
                nimSelect.appendChild(option);
            });
        }

        // Reset nama dan mata kuliah
        document.getElementById("nama").value = "";
        updateMataKuliah(selectedKelas);
    }

    function updateMahasiswa() {
        var selectedNIM = document.getElementById("nim").value;
        if (selectedNIM in mahasiswaData) {
            document.getElementById("nama").value = mahasiswaData[selectedNIM]['nama'];
        } else {
            document.getElementById("nama").value = "";
        }
    }

    function updateMataKuliah(kelas) {
        var mataKuliahSelect = document.getElementById("mata_kuliah");
        mataKuliahSelect.innerHTML = "<option value=''>Pilih Mata Kuliah</option>";

        if (kelas in semuaMataKuliah) {
            semuaMataKuliah[kelas].forEach(function(mk) {
                var option = document.createElement("option");
                option.value = mk;
                option.text = mk;
                mataKuliahSelect.appendChild(option);
            });
        }
    }

    function setWaktuSekarang() {
        var now = new Date();
        var jam = now.getHours().toString().padStart(2, "0");
        var menit = now.getMinutes().toString().padStart(2, "0");
        document.getElementById("waktu_masuk").value = jam + ":" + menit;
    }
</script>

</head>
<body class="bg-light">
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
		<a href="absensimanual.php"><i class="fas fa-file-signature"></i> Presensi Manual</a>
        <a href="absensi.php"><i class="fas fa-calendar-check"></i> Data Presensi</a>
        <a href="absensi_dosen.php"><i class="fas fa-chalkboard-teacher"></i> Data Presensi Dosen</a>
        <a href="jadwal.php"><i class="fas fa-calendar-alt"></i> Jadwal</a>
    </div>
	
	 <script>
		function toggleSidebar() {
			let sidebar = document.getElementById("sidebar");
			let content = document.getElementById("content");
			let footer = document.getElementById("footer"); // Ambil elemen footer

			sidebar.classList.toggle("active");
			content.classList.toggle("shifted");
			footer.classList.toggle("shifted"); // Tambahkan class untuk footer
		}

		document.addEventListener("click", function(event) {
			let sidebar = document.getElementById("sidebar");
			let menuBtn = document.querySelector(".menu-btn");
			let footer = document.getElementById("footer");

			if (!sidebar.contains(event.target) && !menuBtn.contains(event.target)) {
				sidebar.classList.remove("active");
				document.getElementById("content").classList.remove("shifted");
				footer.classList.remove("shifted"); // Kembalikan posisi footer
			}
		});
    </script>

	<!-- Main Content -->
	<div class="main-content">
		<div class="container mt-5">
			<div class="card shadow-lg">
				<div class="card-header bg-primary text-white text-center">
					<h3><i class="fas fa-edit"></i> Form Presensi Manual</h3>
				</div>
				<div class="card-body">
					<?php if (!empty($pesan)) { echo "<div class='alert alert-info'>$pesan</div>"; } ?>

	<form action="" method="POST">

		<div class="mb-3">
			<label for="kelas" class="form-label">Kelas:</label>
			<select id="kelas" name="kelas" class="form-select" onchange="updateNIMs()" required>
				<option value="">Pilih Kelas</option>
				<?php
				$kelas_result = mysqli_query($conn, "SELECT DISTINCT kelas FROM mahasiswa");
				while ($row = mysqli_fetch_assoc($kelas_result)) {
					echo "<option value='" . $row['kelas'] . "'>" . $row['kelas'] . "</option>";
				}
				?>
			</select>
		</div>

		<div class="mb-3">
			<label for="nim" class="form-label">NIM:</label>
			<select id="nim" name="nim" class="form-select" onchange="updateMahasiswa()" required>
				<option value="">Pilih NIM</option>
			</select>
		</div>

		<div class="mb-3">
			<label for="nama" class="form-label">Nama:</label>
			<input type="text" id="nama" name="nama" class="form-control" readonly required>
		</div>

		<div class="mb-3">
			<label for="mata_kuliah" class="form-label">Mata Kuliah:</label>
			<select id="mata_kuliah" name="mata_kuliah" class="form-select" required>
				<option value="">Pilih Mata Kuliah</option>
			</select>
		</div>

		<div class="mb-3" id="waktuMasukDiv">
			<label for="waktu_masuk" class="form-label">Waktu Presensi:</label>
			<input type="text" id="waktu_masuk" name="waktu_masuk" class="form-control">
		</div>

		<div class="mb-3">
			<label for="keterangan" class="form-label">Keterangan:</label>
			<select id="keterangan" name="keterangan" class="form-select" required>
				<option value="Hadir">Hadir</option>
				<option value="Sakit">Sakit</option>
				<option value="Izin">Izin</option>
				<option value="Alpa">Alpa</option>
			</select>
		</div>

		<button type="submit" class="btn btn-success w-100">Submit Presensi</button>
	</form>


	<script>
		document.addEventListener("DOMContentLoaded", function () {
			const keteranganSelect = document.getElementById("keterangan");
			const waktuMasukInput = document.getElementById("waktu_masuk");
			const waktuMasukDiv = document.getElementById("waktuMasukDiv");

			function updateWaktuMasuk() {
				const keterangan = keteranganSelect.value;

				if (keterangan === "Hadir") {
					const now = new Date();
					const jam = now.getHours().toString().padStart(2, "0");
					const menit = now.getMinutes().toString().padStart(2, "0");
					waktuMasukInput.value = jam + ":" + menit;
					waktuMasukInput.readOnly = false;
					waktuMasukDiv.style.display = "block";
				} else {
					waktuMasukInput.value = "";
					waktuMasukInput.readOnly = true;
					waktuMasukDiv.style.display = "none";
				}
			}

			keteranganSelect.addEventListener("change", updateWaktuMasuk);

			// Jalankan saat halaman dimuat
			updateWaktuMasuk();
		});
	</script>

	<footer id="footer" class="footer">
        &copy; <?php echo date("Y"); ?> Smart Presence D4 Teknik Elektronika. All rights reserved.
    </footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0/dist/js/select2.min.js"></script>
<script>$('.select2').select2(); setWaktuSekarang();</script>

</body>
</html>
