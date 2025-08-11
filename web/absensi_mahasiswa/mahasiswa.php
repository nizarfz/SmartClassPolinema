<?php
include('koneksi.php');

// Fungsi untuk mendapatkan ID Sidik Jari terakhir
if (isset($_GET['action']) && $_GET['action'] == 'get_last_finger_id') {
    $query = "SELECT MAX(id_fingerprint) AS last_id FROM mahasiswa";
    $result = mysqli_query($conn, $query);

    if ($result) {
        $row = mysqli_fetch_assoc($result);
        $lastID = $row['last_id'] ?? 0; // Jika tidak ada data, gunakan 0
        echo json_encode(array('status' => 'success', 'last_id' => $lastID));
    } else {
        echo json_encode(array('status' => 'error', 'message' => 'Query gagal'));
    }
    exit;
}

// Fungsi untuk mendapatkan data mahasiswa berdasarkan ID Sidik Jari atau UID RFID
if (isset($_GET['ID']) || isset($_GET['uid'])) {
    $input = isset($_GET['ID']) ? $_GET['ID'] : $_GET['uid'];

    if (empty($input)) {
        echo json_encode(array('status' => 'error', 'message' => 'ID atau UID kosong'));
        exit;
    }

    // Periksa apakah input adalah ID atau UID dan jalankan query yang sesuai
    if (isset($_GET['ID'])) {
        $query = "SELECT nama, nim, kelas FROM mahasiswa WHERE id_fingerprint = ?";
    } else {
        $query = "SELECT nama, nim, kelas FROM mahasiswa WHERE uid_rfid = ?";
    }

    $stmt = mysqli_prepare($conn, $query);

    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "s", $input);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if ($row = mysqli_fetch_assoc($result)) {
            echo json_encode(array('status' => 'success', 'nama' => $row['nama'], 'nim' => $row['nim'], 'kelas' => $row['kelas']));
        } else {
            echo json_encode(array('status' => 'error', 'message' => 'Data tidak ditemukan'));
        }
    } else {
        echo json_encode(array('status' => 'error', 'message' => 'Query database gagal'));
    }
    exit;
}

// Halaman utama: Data Mahasiswa
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'delete') {
    $id_mahasiswa = $_POST['id_mahasiswa'];

    // Pastikan ID mahasiswa valid
    if (!empty($id_mahasiswa)) {
        $query = "DELETE FROM mahasiswa WHERE id_mahasiswa = ?";
        $stmt = mysqli_prepare($conn, $query);

        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "i", $id_mahasiswa);
            if (mysqli_stmt_execute($stmt)) {
                echo "<script>alert('Data berhasil dihapus'); window.location='mahasiswa.php';</script>";
            } else {
                echo "<script>alert('Gagal menghapus data');</script>";
            }
        } else {
            echo "<script>alert('Query gagal');</script>";
        }
    } else {
        echo "<script>alert('ID mahasiswa tidak valid');</script>";
    }
}

// Menangani filter
$filter_kelas = isset($_GET['kelas']) ? $_GET['kelas'] : '';

// Modifikasi query untuk menyertakan filter hanya berdasarkan kelas
$query = "SELECT * FROM mahasiswa WHERE 1";  // Kondisi 1 untuk memastikan query berjalan

if ($filter_kelas) {
    $query .= " AND kelas = ?";
}
$query .= " ORDER BY kelas ASC, nama ASC";

$stmt = mysqli_prepare($conn, $query);

if ($stmt) {
    if ($filter_kelas) {
        mysqli_stmt_bind_param($stmt, "s", $filter_kelas);
    }
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
} else {
    die("Query gagal: " . mysqli_error($conn));
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Mahasiswa</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            font-family: Arial, sans-serif;
        }

        h1 {
            text-align: center;
            margin-top: 20px;
            color: #007bff; /* Warna biru untuk judul Data Mahasiswa */
        }

        .filter-container {
            text-align: center;
            margin: 20px 0;
        }

        .filter-container select {
            width: 200px;
            margin-right: 10px;
            padding: 5px;
            font-size: 16px;
        }

        .filter-container button {
            padding: 5px 20px;
            background-color: #007bff;
            color: white;
            border: none;
            cursor: pointer;
            font-size: 16px;
            border-radius: 5px;
        }

        .filter-container button:hover {
            background-color: #0056b3;
        }

        table {
            width: 100%;
            margin-top: 20px;
            border-collapse: collapse;
        }

        table th, table td {
            padding: 12px;
            text-align: center;
            border: 1px solid #ddd;
        }

        table th {
            color:black !important;
            font-weight: bold;
        }

        table tr:hover {
            background-color: #f1f1f1; /* Efek hover pada baris */
        }

        .action-buttons {
            display: flex;
            justify-content: center;
            gap: 10px;
        }

        .action-buttons a, .action-buttons button {
            padding: 8px 16px;
            border: none;
            cursor: pointer;
            border-radius: 5px;
            font-size: 16px;
        }

        .action-buttons .edit {
            background-color: #007bff; /* Biru untuk edit */
			color: white;
			text-decoration: none; /* Menghapus garis bawah pada link */
			padding: 8px 16px;
			border: none;
			cursor: pointer;
			border-radius: 5px;
        }

        .action-buttons .delete {
            background-color: #dc3545; /* Merah untuk hapus */
            color: white;
        }

        .action-buttons a:hover, .action-buttons button:hover {
            opacity: 0.8;
        }
				
		html, body {
			height: 100%;
			margin: 0;
			display: flex;
			flex-direction: column;
		}

		.container {
			flex: 1; /* Membuat konten utama memenuhi ruang yang tersedia */
		}

		footer {
			text-align: center;
			padding: 10px;
			width: 100%;
			bottom: 0;
		}
		/* Optional: Tampilan form filter */
		.input-group {
			box-shadow: 0 4px 8px rgba(0,0,0,0.05);
			border-radius: 8px;
			overflow: hidden;
		}

		.input-group-text {
			background-color: #007bff;
			border: none;
			font-weight: bold;
		}

		select.form-control {
			border: none;
		}

		.btn-primary {
			border-radius: 0;
		}

		@media (max-width: 576px) {
			.input-group {
				flex-direction: column;
				gap: 10px;
				padding: 10px;
			}

			.input-group-text,
			.btn,
			select {
				width: 100%;
				border-radius: 6px !important;
			}
		}
		
		body, html {
			font-family: Arial, sans-serif;
			background-color: #f8f9fa;
			display: flex;
			flex-direction: column;
			min-height: 100vh;
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
		
		/* Jika sidebar aktif, geser konten */
		.sidebar.active ~ .container {
			margin-left: 100px;
		}

		/* Konten utama */
		.content {
			flex: 1;
			margin-left: 10px; /* Kurangi margin kiri */
			margin-top: 10px; /* Sesuaikan dengan tinggi header */
			padding: 10px; /* Kurangi padding agar tidak terlalu jauh */
			transition: margin-left 0.3s;
			width: 100%;
			text-align: center;
			position: fixed;
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

		/* Header */
		.header {
			display: flex;
			align-items: center;
			justify-content: space-between; /* Memberikan ruang antara ikon menu dan judul */
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

		/* Tombol Menu */
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
			z-index: 1000;
		}
		
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
			let content = document.querySelector(".container");

			sidebar.classList.toggle("active");

			// Jika sidebar aktif, tambahkan margin kiri, jika tidak, kembalikan ke 0
			if (sidebar.classList.contains("active")) {
				content.style.marginLeft = "180px";
			} else {
				content.style.marginLeft = "auto";
				content.style.marginRight = "auto";
				content.style.textAlign = "center"; // Untuk memastikan tabel tetap di tengah
			}
		}

		// Tutup sidebar jika pengguna mengklik di luar sidebar
		document.addEventListener("click", function(event) {
			let sidebar = document.getElementById("sidebar");
			let menuBtn = document.querySelector(".menu-btn");
			let content = document.querySelector(".container");

			if (!sidebar.contains(event.target) && !menuBtn.contains(event.target)) {
				sidebar.classList.remove("active");
				content.style.marginLeft = "auto";
				content.style.marginRight = "auto";
				content.style.textAlign = "center"; // Untuk memastikan tabel tetap di tengah
			}
		});
	</script>
	
    <div class="container mt-5">
        <h2 class="mb-4 text-center text-primary">DATA MAHASISWA</h1>

<!-- Filter Section -->
<form method="GET" action="mahasiswa.php" class="mb-4">
    <div class="input-group mx-auto" style="max-width: 400px;">
        <label class="input-group-text" for="kelas"><i class="fas fa-school"></i></label>
        <select name="kelas" id="kelas" class="form-control" onchange="this.form.submit()">
            <option value="">Pilih Kelas</option>
            <?php 
            $kelas_list = ['1A', '1B', '2A', '2B', '3A', '3B', '4A', '4B'];
            foreach ($kelas_list as $k) {
                $selected = (isset($_GET['kelas']) && $_GET['kelas'] == $k) ? 'selected' : '';
                echo "<option value='$k' $selected>$k</option>";
            }
            ?>
        </select>
        <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Filter</button>
    </div>
</form>


    <!-- Table of Students -->
    <div class="container">
        <table>
            <thead>
                <tr>
                    <th>No</th>
                    <th>Nama</th>
                    <th>NIM</th>
                    <th>Kelas</th>
                    <th>ID Fingerprint</th>
                    <th>Opsi</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $no = 1;
                while ($row = mysqli_fetch_assoc($result)) { 
                ?>
                    <tr>
                        <td><?php echo $no++; ?></td>
                        <td style="text-align: left;"><?php echo htmlspecialchars($row['nama']); ?></td>
                        <td><?php echo htmlspecialchars($row['nim']); ?></td>
                        <td><?php echo htmlspecialchars($row['kelas']); ?></td>
                        <td><?php echo htmlspecialchars($row['id_fingerprint']); ?></td>
						<td class="action-buttons">
							<a href="edit.php?id_mahasiswa=<?php echo isset($row['id_mahasiswa']) ? htmlspecialchars($row['id_mahasiswa']) : ''; ?>" class="edit">
								<i class="fas fa-edit"></i> Edit
							</a>
							<form method="POST" action="mahasiswa.php" style="display:inline;">
								<input type="hidden" name="action" value="delete">
								<input type="hidden" name="id_mahasiswa" value="<?php echo isset($row['id_mahasiswa']) ? htmlspecialchars($row['id_mahasiswa']) : ''; ?>">
								<button type="submit" onclick="return confirm('Apakah Anda yakin ingin menghapus data ini?')" class="delete">
									<i class="fas fa-trash-alt"></i> Hapus
								</button>
							</form>
						</td>

                    </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
    <footer>
        &copy; <?php echo date("Y"); ?> Smart Presence D4 Teknik Elektronika. All rights reserved.
    </footer>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
</body>
</html>
