<?php
// Include koneksi database
include('koneksi.php');

date_default_timezone_set('Asia/Jakarta');

// Mulai sesi untuk menyimpan status absensi
session_start();

// Cek apakah ada permintaan penghapusan data
if (isset($_GET['delete_id'])) {
    $delete_id = $_GET['delete_id'];

    if (is_numeric($delete_id)) {
        $query_hapus = $conn->prepare("DELETE FROM absensi_dosen WHERE id = ?");
        $query_hapus->bind_param("i", $delete_id);
        if ($query_hapus->execute()) {
            echo "<script>alert('Data absensi dosen berhasil dihapus!'); window.location.href='absensi_dosen.php';</script>";
        } else {
            echo "<script>alert('Gagal menghapus data absensi dosen.'); window.location.href='absensi_dosen.php';</script>";
        }
    } else {
        echo "<script>alert('ID absensi tidak valid.'); window.location.href='absensi_dosen.php';</script>";
    }
}

// Ambil parameter filter tanggal dari URL
$tanggal_filter = isset($_GET['tanggal']) ? $_GET['tanggal'] : '';

// Query untuk mengambil data absensi dosen
$query_absensi_dosen = "SELECT * FROM absensi_dosen WHERE 1=1";
$params = [];
$types = "";

if (!empty($tanggal_filter)) {
    $query_absensi_dosen .= " AND tanggal = ?";
    $params[] = $tanggal_filter;
    $types .= "s";
}

$query_absensi_dosen .= " ORDER BY tanggal DESC, waktu_masuk DESC";
$stmt = $conn->prepare($query_absensi_dosen);

// Bind parameter jika ada filter
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result_absensi_dosen = $stmt->get_result();

// Jika data absensi baru akan disimpan
if (isset($_GET['nama'], $_GET['nip'], $_GET['suhu'], $_GET['waktu'])) {
    $nama = trim($_GET['nama']);
    $nip = trim($_GET['nip']);
    $suhu = trim($_GET['suhu']);
    $waktu = trim($_GET['waktu']);

    // Ambil hari dan jam sekarang
    date_default_timezone_set('Asia/Jakarta'); 
    $hari_mapping = [
        "Sunday" => "Minggu",
        "Monday" => "Senin",
        "Tuesday" => "Selasa",
        "Wednesday" => "Rabu",
        "Thursday" => "Kamis",
        "Friday" => "Jumat",
        "Saturday" => "Sabtu"
    ];
    $hari_ini = $hari_mapping[date('l')]; 
    $jam_sekarang = date('H:i:s');
    $tanggal_hari_ini = date("Y-m-d");

    // Validasi suhu (harus berupa angka)
    if (!is_numeric($suhu)) {
        echo json_encode(["status" => "error", "message" => "Format suhu tidak valid"]);
        exit();
    }
    $suhu = floatval($suhu); 

    // Cek jadwal dosen berdasarkan hari dan waktu
    $query_jadwal = $conn->prepare("SELECT mata_kuliah, kelas FROM jadwal 
        WHERE nama_dosen = ? AND hari = ? AND waktu_mulai <= ? AND waktu_selesai >= ?");
    $query_jadwal->bind_param("ssss", $nama, $hari_ini, $jam_sekarang, $jam_sekarang);
    $query_jadwal->execute();
    $result_jadwal = $query_jadwal->get_result();

	if ($result_jadwal->num_rows === 0) {
		echo json_encode(["status" => "error", "message" => "Tidak ada mata kuliah yang sedang berlangsung"]);
		exit();
	}

	$jadwal = $result_jadwal->fetch_assoc();
	$mata_kuliah = $jadwal['mata_kuliah'];
	$kelas = $jadwal['kelas'];

    $keterangan = "Hadir";

    // Validasi dosen
    $cek_dosen = $conn->prepare("SELECT * FROM dosen WHERE nama = ? AND nip = ?");
    $cek_dosen->bind_param("ss", $nama, $nip);
    $cek_dosen->execute();
    $result_dosen = $cek_dosen->get_result();

    if ($result_dosen->num_rows > 0) {
        // Cek apakah sudah ada absensi untuk hari dan mata kuliah yang sama
        $cek_absensi = $conn->prepare("SELECT id FROM absensi_dosen 
            WHERE nama = ? AND nip = ? AND mata_kuliah = ? AND tanggal = ?");
        $cek_absensi->bind_param("ssss", $nama, $nip, $mata_kuliah, $tanggal_hari_ini);
        $cek_absensi->execute();
        $result_absensi = $cek_absensi->get_result();

        if ($result_absensi->num_rows > 0) {
            // Jika sudah ada absensi pada hari ini, tidak memasukkan data baru
            echo json_encode(["status" => "error", "message" => "Absensi sudah dilakukan untuk mata kuliah ini hari ini"]);
        } else {
            // Jika belum ada, insert absensi baru
           $stmt_insert = $conn->prepare("INSERT INTO absensi_dosen (nama, nip, suhu, waktu_masuk, mata_kuliah, kelas, keterangan, tanggal) 
                               VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
			$stmt_insert->bind_param("ssdsssss", $nama, $nip, $suhu, $waktu, $mata_kuliah, $kelas, $keterangan, $tanggal_hari_ini);

            $stmt_insert->execute();
            echo json_encode(["status" => "success", "message" => "Absensi dosen berhasil dilakukan"]);
        }
    } else {
        echo json_encode(["status" => "error", "message" => "Dosen tidak terdaftar"]);
    }
    exit();
}
?>


<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Absensi Dosen</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
    body, html {
        font-family: Arial, sans-serif;
        background-color: #f8f9fa;
        display: flex;
        flex-direction: column;
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

    /* Tombol Hamburger */
    .menu-btn {
        font-size: 24px;
        cursor: pointer;
        color: white;
        background: #007bff;
        border: none;
        padding: 10px 15px;
        position: fixed;
        left: 10px;
        top: 10px;
        border-radius: 5px;
        z-index: 1000;
    }

    /* Sidebar aktif */
    .sidebar.active {
        left: 0;
    }
	/* Saat sidebar aktif, footer ikut bergeser */
	.sidebar.active ~ .footer {
		left: 200px; /* Sesuaikan dengan lebar sidebar */
		width: calc(100% - 250px); /* Lebar menyesuaikan sisa ruang */
		transition: left 0.3s ease-in-out, width 0.3s ease-in-out;
	}


	/* Konten utama */
	.content {
		flex: 1;
		margin-left: 10px; /* Kurangi margin kiri */
		margin-top: 20px; /* Sesuaikan dengan tinggi header */
		padding: 10px; /* Kurangi padding agar tidak terlalu jauh */
		transition: margin-left 0.3s;
		width: 100%;
		text-align: center;
	}

    .content.shifted {
        margin-left: 190px;
    }

	/* Footer */
	.footer {
		background-color: transparent;
		text-align: center;
		padding: 10px;
		width: 100%;
		bottom: 0;
		left: 0;
		transition: left 0.3s ease-in-out, width 0.3s ease-in-out; /* Tambahkan transisi */
	}

	/* Footer bergeser saat sidebar aktif */
	.sidebar.active ~ .footer {
		left: 200px; /* Sesuaikan dengan lebar sidebar */
		width: calc(100% - 200px); /* Menyesuaikan dengan sisa ruang */
	}


	/* Header */
	.header {
		display: flex;
		align-items: center;
		position: fixed;
		justify-content: space-between; /* Memberikan ruang antara ikon menu dan judul */
		background-color: #007bff;
		padding: 0 20px;
		color: white;
		width: 100%;
		height: 50px;
		top: 0;
		left: 0;
		z-index: 1000;
	}

	/* Tombol Menu */
	.menu-btn {
		font-size: 20px;
		cursor: pointer;
		background: none;
		border: none;
		color: white;
		padding: 5px 10px; /* Tambahkan padding agar lebih rapi */
	}
	
	/* Judul utama */
	h2 {
		text-align: center;
		font-size: 32px; /* Perbesar sedikit agar lebih mencolok */
		font-weight: bold;
		color: #212529;
		margin-top: 15px;
		text-transform: uppercase; /* Semua huruf menjadi kapital */
		letter-spacing: 2px; /* Beri sedikit jarak antar huruf */
		text-shadow: 2px 2px 5px rgba(0, 0, 0, 0.3); /* Tambahkan bayangan untuk efek 3D */
		background: linear-gradient(90deg, #007bff, #0056b3); /* Efek gradasi warna biru */
		-webkit-background-clip: text; /* Terapkan efek gradasi hanya pada teks */
		-webkit-text-fill-color: transparent; /* Buat warna teks transparan agar gradasi terlihat */
	}

	.title {
		font-size: 20px;
		font-weight: bold;
		color: white;
		margin-left: auto; /* Membuatnya tetap di kanan */
		margin-right: 5px; /* Beri jarak dengan sisi kanan */
		text-transform: uppercase;
	}
	
	.text-left {
	  text-align: left;
	}

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

    <!-- Konten Utama -->
    <div class="content" id="content">
        <h2 class="text-center my-4">Data Presensi Dosen</h2>

        <form method="GET" action="absensi_dosen.php" class="mb-4">
            <div class="input-group" style="max-width: 400px; margin: 0 auto;">
                <label class="input-group-text" for="tanggal">Tanggal:</label>
                <input type="date" name="tanggal" id="tanggal" class="form-control" value="<?php echo $tanggal_filter; ?>">
                <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Filter</button>
            </div>
        </form>

        <table class="table table-striped table-bordered">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Nama</th>
                    <th>NIP/NIDN</th>
                    <th>Suhu (°C)</th>
                    <th>Mata Kuliah</th>
                    <th>Kelas</th>
                    <th>Tanggal</th>
                    <th>Waktu Masuk</th>
                    <th>Keterangan</th>
                    <th>Opsi</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $no = 1;
                while ($row = $result_absensi_dosen->fetch_assoc()) {
                    echo "<tr>";
                    echo "<td>{$no}</td>";
                    echo "<td style='text-align: left;'>{$row['nama']}</td>";
                    echo "<td>{$row['nip']}</td>";
                    echo "<td>" . number_format(floor($row['suhu'] * 10) / 10, 1) . " &deg;C</td>";
                    echo "<td>{$row['mata_kuliah']}</td>";
                    echo "<td>{$row['kelas']}</td>";
                    echo "<td>{$row['tanggal']}</td>";
                    echo "<td>{$row['waktu_masuk']}</td>";
                    echo "<td>Hadir</td>";
                    echo "<td><a href='?delete_id={$row['id']}' class='btn btn-danger btn-sm'><i class='fas fa-trash-alt'></i> Hapus</a></td>";
                    echo "</tr>";
                    $no++;
                }
                ?>
            </tbody>
        </table>
    </div>

	<!-- Footer -->
	<footer id="footer" class="footer">
		<p>&copy; 2025 Smart Presence D4 Teknik Elektronika. All rights reserved.</p>
	</footer>
	
   	<script>
        function toggleSidebar() {
		let sidebar = document.getElementById("sidebar");
		let content = document.getElementById("content");
		let footer = document.getElementById("footer"); // Ambil elemen footer

		sidebar.classList.toggle("active");
		content.classList.toggle("shifted");
		footer.classList.toggle("shifted"); // Tambahkan efek pergeseran pada footer
	}

	// Tutup sidebar saat klik di luar
	document.addEventListener("click", function(event) {
		let sidebar = document.getElementById("sidebar");
		let menuBtn = document.querySelector(".menu-btn");
		let footer = document.getElementById("footer"); // Ambil elemen footer
		let content = document.getElementById("content");

		if (!sidebar.contains(event.target) && !menuBtn.contains(event.target)) {
			sidebar.classList.remove("active");
			content.classList.remove("shifted");
			footer.classList.remove("shifted"); // Pastikan footer kembali ke posisi semula
		}
	});
    </script>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
</body>
</html>

<?php mysqli_close($conn); ?>
