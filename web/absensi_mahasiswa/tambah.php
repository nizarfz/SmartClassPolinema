<?php
include 'koneksi.php'; // Hubungkan ke database

// Aktifkan debugging untuk melihat error
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Tangkap data dari ESP32
$nama = isset($_GET['nama']) ? trim($_GET['nama']) : ''; 
$id_fingerprint = isset($_GET['id_fingerprint']) ? trim($_GET['id_fingerprint']) : ''; 
$uid_rfid = isset($_GET['uid_rfid']) ? trim($_GET['uid_rfid']) : ''; 
$nim = isset($_GET['nim']) ? trim($_GET['nim']) : '000000'; // Default jika tidak ada NIM
$kelas = isset($_GET['kelas']) ? trim($_GET['kelas']) : 'Tidak Diketahui'; // Default jika tidak ada kelas

// Pastikan semua data yang diperlukan sudah ada
if (!empty($nama) && !empty($id_fingerprint) && !empty($uid_rfid)) {
    // Gunakan prepared statement untuk keamanan
    $stmt = $conn->prepare("INSERT INTO mahasiswa (nama, nim, kelas, id_fingerprint, uid_rfid) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssss", $nama, $nim, $kelas, $id_fingerprint, $uid_rfid);

    if ($stmt->execute()) {
        echo "Data ESP32 berhasil disimpan!";
    } else {
        echo "Gagal menyimpan data ESP32: " . htmlspecialchars($stmt->error);
    }
    $stmt->close();
    exit();
}

// Tangkap data dari POST (pendaftaran manual)
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nama_manual = isset($_POST['nama']) ? trim($_POST['nama']) : ''; 
    $nim_manual = isset($_POST['nim']) ? trim($_POST['nim']) : ''; 
    $kelas_manual = isset($_POST['kelas']) ? trim($_POST['kelas']) : ''; 
    $nip_manual = isset($_POST['nip']) ? trim($_POST['nip']) : ''; 
    $tipe_manual = isset($_POST['tipe']) ? trim($_POST['tipe']) : ''; 
    $id_fingerprint_manual = isset($_POST['id_fingerprint']) ? trim($_POST['id_fingerprint']) : ''; 
    $uid_rfid_manual = isset($_POST['uid_rfid']) ? trim($_POST['uid_rfid']) : ''; 

    if (!empty($nama_manual) && !empty($id_fingerprint_manual) && !empty($uid_rfid_manual) && !empty($tipe_manual)) {
        if ($tipe_manual == "mahasiswa" && !empty($nim_manual) && !empty($kelas_manual)) {
            $stmt = $conn->prepare("INSERT INTO mahasiswa (nama, nim, kelas, id_fingerprint, uid_rfid) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssss", $nama_manual, $nim_manual, $kelas_manual, $id_fingerprint_manual, $uid_rfid_manual);
            $redirect = "mahasiswa.php"; 
        } elseif ($tipe_manual == "dosen" && !empty($nip_manual)) {
            $stmt = $conn->prepare("INSERT INTO dosen (nama, nip, id_fingerprint, uid_rfid) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $nama_manual, $nip_manual, $id_fingerprint_manual, $uid_rfid_manual);
            $redirect = "dosen.php"; 
        } else {
            echo "<div class='alert alert-danger'>Semua data harus diisi dengan benar!</div>";
            exit();
        }

        if ($stmt->execute()) {
            echo "<script>alert('Data berhasil ditambahkan!'); window.location='$redirect';</script>";
        } else {
            echo "<div class='alert alert-danger'>Terjadi kesalahan: " . htmlspecialchars($stmt->error) . "</div>";
        }
        $stmt->close();
    } else {
        echo "<div class='alert alert-danger'>Semua data harus diisi!</div>";
    }
}
?>


<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Data</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <script>
        function toggleFields() {
            var tipe = document.getElementById("tipe").value;
            if (tipe === "mahasiswa") {
                document.getElementById("mahasiswaFields").style.display = "block";
                document.getElementById("dosenFields").style.display = "none";
            } else {
                document.getElementById("mahasiswaFields").style.display = "none";
                document.getElementById("dosenFields").style.display = "block";
            }
        }
        window.onload = toggleFields; // Pastikan tampilan sudah sesuai saat halaman dimuat
    </script>
</head>
<body>
	
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
		padding: 20px; /* Kurangi padding agar tidak terlalu jauh */
		transition: margin-left 0.3s;
		width: 100%;
		text-align: center;
	}

    .content.shifted {
        margin-left: 190px;
    }

    /* Styling untuk Judul Utama */
    .title {
        text-align: center;
        font-size: 36px;
        font-weight: bold;
        color: #007bff;
        margin-top: 10px;
        text-transform: uppercase;
    }

	/* Footer */
	footer {
	  width: 100%;
	  text-align: center;
	  padding: 15px 0;
	  font-size: 14px;
	  color: #333;
	  background-color: #f8f9fa;
	  margin-top: 30px;
	}

	h2 {
		text-align: center;
		font-size: 32px;
		font-weight: bold;
		color: #212529;
		margin-top: 50px; /* Tambahkan ini, misal 80px */
		text-transform: uppercase;
		letter-spacing: 2px;
		text-shadow: 2px 2px 5px rgba(0, 0, 0, 0.3);
		background: linear-gradient(90deg, #007bff, #0056b3);
		-webkit-background-clip: text;
		-webkit-text-fill-color: transparent;
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
	
	.form-container {
		width: 100%;
		max-width: 700px;
		margin: auto;
		padding: 20px;
		background: white;
		border-radius: 10px;
		box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.1);
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

	/* Judul */
	.title {
		font-size: 20px;
		font-weight: bold;
		color: white;
		margin-left: auto; /* Membuatnya tetap di kanan */
		margin-right: 5px; /* Beri jarak dengan sisi kanan */
		text-transform: uppercase;
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
	/* Membuat form tetap berada di tengah */
	.container {
		display: flex;
		flex-direction: column;
		justify-content: center;
		align-items: center;
		min-height: 80vh; /* Menyesuaikan agar tetap berada di tengah */
		text-align: left;
		margin-top: 80px;
	}

	/* Menyesuaikan lebar form agar tidak terlalu lebar */
	form {
		padding: 10px;
		background: white;
		border-radius: 10px;
		box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.1);
		text-align: left; 
	}
	/* Mengatur form agar memiliki lebar yang lebih kecil */
        .form-container {
            margin: auto; /* Membuat form berada di tengah */
            background: #f8f9fa; /* Warna background */
            border-radius: 10px; /* Membuat sudut membulat */
            box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.1); /* Efek bayangan */
        }

        /* Mengatur tombol agar berada di tengah */
        .btn-center {
            display: flex;
            justify-content: center;
        }

        /* Styling untuk input form */
        .form-control {
            border-radius: 8px; /* Membuat input lebih smooth */
            border: 1px solid #ccc;
            padding: 10px;
        }

        /* Styling untuk label */
        .form-label {
            font-weight: bold;
        }

        /* Styling untuk tombol */
        .btn-primary {
            background-color: #007bff;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
        }

        .btn-primary:hover {
            background-color: #0056b3;
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

    <!-- Form Tambah Data -->
			<div class="form-container">
				<form method="POST">
                    <h2 class="text-center mb-4">Tambah Data</h2>
                    <form method="POST">
                        <div class="mb-3">
                            <label for="nama" class="form-label">Nama:</label>
                            <input type="text" class="form-control" name="nama" id="nama" required>
                        </div>

                        <div class="mb-3">
                            <label for="tipe" class="form-label">Tipe:</label>
                            <select class="form-control" name="tipe" id="tipe" required onchange="toggleFields()">
                                <option value="mahasiswa">Mahasiswa</option>
                                <option value="dosen">Dosen</option>
                            </select>
                        </div>

                        <!-- Form khusus Mahasiswa -->
                        <div id="mahasiswaFields" style="display:none;">
                            <div class="mb-3">
                                <label for="nim" class="form-label">NIM:</label>
                                <input type="text" class="form-control" name="nim" id="nim">
                            </div>

                            <div class="mb-3">
                                <label for="kelas" class="form-label">Kelas:</label>
                                <select class="form-control" name="kelas" id="kelas">
                                    <option value="">-- Pilih Kelas --</option>
                                    <option value="1A">1A</option>
                                    <option value="1B">1B</option>
                                    <option value="2A">2A</option>
                                    <option value="2B">2B</option>
                                    <option value="3A">3A</option>
                                    <option value="3B">3B</option>
                                    <option value="4A">4A</option>
                                    <option value="4B">4B</option>
                                </select>
                            </div>
                        </div>

                        <!-- Form khusus Dosen -->
                        <div id="dosenFields" style="display:none;">
                            <div class="mb-3">
                                <label for="nip" class="form-label">NIP/NIDN:</label>
                                <input type="text" class="form-control" name="nip" id="nip">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="id_fingerprint" class="form-label">ID Sidik Jari:</label>
                            <input type="text" class="form-control" name="id_fingerprint" id="id_fingerprint" required>
                        </div>

                        <div class="mb-3">
                            <label for="uid_rfid" class="form-label">UID RFID:</label>
                            <input type="text" class="form-control" name="uid_rfid" id="uid_rfid" required>
                        </div>

                        <div class="text-center mt-3">
                            <button type="submit" class="btn btn-primary">Tambah Data</button>
                        </div>
                    </form>
                </div> <!-- Penutup Card -->			

    <!-- Footer di Luar Semua Container -->
    <footer class="footer">
        &copy; <?php echo date("Y"); ?> Smart Presence D4 Teknik Elektronika. All rights reserved.
    </footer>

    <!-- Script Sidebar -->
    <script>
        function toggleSidebar() {
            let sidebar = document.getElementById("sidebar");
            let content = document.querySelector(".container");

            sidebar.classList.toggle("active");

            if (sidebar.classList.contains("active")) {
                content.style.marginLeft = "180px";
            } else {
                content.style.marginLeft = "auto";
                content.style.marginRight = "auto";
                content.style.textAlign = "center";
            }
        }

        document.addEventListener("click", function(event) {
            let sidebar = document.getElementById("sidebar");
            let menuBtn = document.querySelector(".menu-btn");
            let content = document.querySelector(".container");

            if (!sidebar.contains(event.target) && !menuBtn.contains(event.target)) {
                sidebar.classList.remove("active");
                content.style.marginLeft = "auto";
                content.style.marginRight = "auto";
                content.style.textAlign = "center";
            }
        });

        // Tampilkan/Hide form Mahasiswa atau Dosen
        function toggleFields() {
            let tipe = document.getElementById("tipe").value;
            let mahasiswaFields = document.getElementById("mahasiswaFields");
            let dosenFields = document.getElementById("dosenFields");

            if (tipe === "mahasiswa") {
                mahasiswaFields.style.display = "block";
                dosenFields.style.display = "none";
            } else if (tipe === "dosen") {
                mahasiswaFields.style.display = "none";
                dosenFields.style.display = "block";
            } else {
                mahasiswaFields.style.display = "none";
                dosenFields.style.display = "none";
            }
        }
    </script>
</body>
