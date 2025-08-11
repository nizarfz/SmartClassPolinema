<?php
// Koneksi ke database
include('koneksi.php');

// Jika permintaan berasal dari ESP32 (API JSON)
if (isset($_GET['json']) && (isset($_GET['kelas']) || isset($_GET['nama_dosen']))) {
    if (isset($_GET['kelas'])) {
        $kelas = mysqli_real_escape_string($conn, $_GET['kelas']);
        $query = "SELECT * FROM jadwal WHERE kelas = '$kelas' ORDER BY hari, waktu_mulai";
    } elseif (isset($_GET['nama_dosen'])) {
        $nama_dosen = mysqli_real_escape_string($conn, $_GET['nama_dosen']);
        $query = "SELECT * FROM jadwal WHERE nama_dosen = '$nama_dosen' ORDER BY hari, waktu_mulai";
    }

    $result = mysqli_query($conn, $query);
    $jadwal_data = [];

    if (mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $jadwal_data[] = $row;
        }
        header('Content-Type: application/json');
        echo json_encode(['status' => 'success', 'message' => 'Data ditemukan', 'data' => $jadwal_data]);
    } else {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'Tidak ada mata kuliah aktif']);
    }
    exit();
}


// Jika permintaan dari browser (menampilkan jadwal di halaman)
if (isset($_GET['kelas'])) {
    $kelas = mysqli_real_escape_string($conn, $_GET['kelas']);
    $query = "SELECT * FROM jadwal WHERE kelas = '$kelas' ORDER BY hari, waktu_mulai LIMIT 1";
    $result = mysqli_query($conn, $query);
}

// Ambil data dosen dari tabel dosen
$query_dosen = "SELECT nama FROM dosen";
$result_dosen = mysqli_query($conn, $query_dosen);

// Proses penghapusan jadwal
if (isset($_GET['hapus_id'])) {
    $hapus_id = mysqli_real_escape_string($conn, $_GET['hapus_id']);
    $query_hapus = "DELETE FROM jadwal WHERE id = '$hapus_id'";
    if (mysqli_query($conn, $query_hapus)) {
        echo "<script>alert('Jadwal berhasil dihapus!'); window.location.href='jadwal.php';</script>";
    } else {
        echo "<script>alert('Gagal menghapus jadwal.');</script>";
    }
}

// Proses tambah atau edit jadwal
if (isset($_POST['submit'])) {
    $mata_kuliah = mysqli_real_escape_string($conn, $_POST['mata_kuliah']);
    $hari = mysqli_real_escape_string($conn, $_POST['hari']);
    $waktu_mulai = mysqli_real_escape_string($conn, $_POST['waktu_mulai']);
    $waktu_selesai = mysqli_real_escape_string($conn, $_POST['waktu_selesai']);
    $kelas = mysqli_real_escape_string($conn, $_POST['kelas']);
	$nama_dosen = mysqli_real_escape_string($conn, $_POST['nama_dosen']);


    if (!empty($_POST['edit_id'])) {
        $edit_id = mysqli_real_escape_string($conn, $_POST['edit_id']);
        $query_edit = "UPDATE jadwal SET mata_kuliah = '$mata_kuliah', hari = '$hari', waktu_mulai = '$waktu_mulai', waktu_selesai = '$waktu_selesai', kelas = '$kelas' , nama_dosen = '$nama_dosen' WHERE id = '$edit_id'";
        if (mysqli_query($conn, $query_edit)) {
            echo "<script>alert('Jadwal berhasil diperbarui!'); window.location.href='jadwal.php';</script>";
        } else {
            echo "<script>alert('Gagal memperbarui jadwal.');</script>";
        }
    } else {
        $query_tambah = "INSERT INTO jadwal (mata_kuliah, hari, waktu_mulai, waktu_selesai, kelas, nama_dosen) VALUES ('$mata_kuliah', '$hari', '$waktu_mulai', '$waktu_selesai', '$kelas', '$nama_dosen')";
        if (mysqli_query($conn, $query_tambah)) {
            echo "<script>alert('Jadwal berhasil ditambahkan!'); window.location.href='jadwal.php';</script>";
        } else {
            echo "<script>alert('Gagal menambahkan jadwal.');</script>";
        }
    }
}

// Mendapatkan data kelas dan jadwal
$kelas = isset($_GET['kelas']) ? mysqli_real_escape_string($conn, $_GET['kelas']) : '';
$query = $kelas ? "SELECT * FROM jadwal WHERE kelas = '$kelas' ORDER BY 
    FIELD(kelas, '1A','1B','2A','2B','3A','3B','4A','4B'), FIELD(hari, 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'), waktu_mulai" : 
    "SELECT * FROM jadwal ORDER BY 
    FIELD(kelas, '1A','1B','2A','2B','3A','3B','4A','4B'), FIELD(hari, 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'), waktu_mulai";

$result = mysqli_query($conn, $query);



// Menangani edit data jadwal
$edit_row = null;
if (isset($_GET['edit_id'])) {
    $edit_id = mysqli_real_escape_string($conn, $_GET['edit_id']);
    $query_edit = "SELECT * FROM jadwal WHERE id = '$edit_id'";
    $edit_result = mysqli_query($conn, $query_edit);
    if (mysqli_num_rows($edit_result) > 0) {
        $edit_row = mysqli_fetch_assoc($edit_result);
    }
}

// Menentukan apakah form harus ditampilkan
$show_form = isset($_GET['edit_id']) || isset($_GET['tambah']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Jadwal Mata Kuliah</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .container {
            max-width: 1200px;
        }
        .navbar {
            background-color: #007bff; 
            padding: 10px;
        }
        .navbar a {
            color: white;
            padding: 10px 15px;
            text-decoration: none;
            font-weight: bold;
            font-size: 16px;
        }
        .navbar a:hover {
            background-color: #0056b3;
            border-radius: 5px;
        }
        .table-responsive {
            overflow-x: auto;
        }
        .table td, .table th {
            vertical-align: middle;
            text-align: center;
			padding: 12px 20px;
			border: 1px solid #ddd;
        }
        .table th {
			color:black !important;
			font-size: 18px;
			font-weight: bold;
        }
		.spacing {
			height: 5px;
		}
        .kelas-header {
            background-color: #6c757d;
            font-size: 18px;
            font-weight: bold;
            text-align: center;
            padding: 12px;
			color: white;
			margin-top: 20px;
        }
        .hari-header {
            background-color: #f1f1f1;
            color: white;
            font-size: 16px;
            font-weight: bold;
            text-align: center;
            padding: 10px;
			margin-top: 15px;
        }
		/* Styling untuk Filter Kelas */
		.input-group-text {
			background-color: #007bff;
			color: white;
			border: none;
			font-weight: bold;
		}

		.form-control {
			border: 1px solid #007bff;
			text-align: center;
		}

		.btn-primary {
			background-color: #007bff;
			border: none;
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
		#content {
			transition: margin-left 0.3s;
			padding: 20px;
		}

		#content.shifted {
			margin-left: 180px; /* Konten bergeser saat sidebar aktif */
		}
		
		/* Saat sidebar aktif, footer ikut bergeser */
		.sidebar.active ~ .footer {
			left: 10px; /* Sesuaikan dengan lebar sidebar */
			width: calc(100% - 250px); /* Lebar menyesuaikan sisa ruang */
			transition: left 0.3s ease-in-out, width 0.3s ease-in-out;
		}


		/* Konten utama */
		.content {
			flex: 1;
			margin-left: 10px; /* Kurangi margin kiri */
			margin-top: 10px; /* Sesuaikan dengan tinggi header */
			padding: 20px; /* Kurangi padding agar tidak terlalu jauh */
			transition: margin-left 0.3s;
			width: 100%;
			text-align: center;
		}
		
		h2 {
			text-align: center;
			font-size: 32px; /* Perbesar sedikit agar lebih mencolok */
			font-weight: bold;
			color: #212529;
			margin-top: 10px;
			text-transform: uppercase; /* Semua huruf menjadi kapital */
			letter-spacing: 2px; /* Beri sedikit jarak antar huruf */
			text-shadow: 2px 2px 5px rgba(0, 0, 0, 0.3); /* Tambahkan bayangan untuk efek 3D */
			background: linear-gradient(90deg, #007bff, #0056b3); /* Efek gradasi warna biru */
			-webkit-background-clip: text; /* Terapkan efek gradasi hanya pada teks */
			-webkit-text-fill-color: transparent; /* Buat warna teks transparan agar gradasi terlihat */
		}
		
		.content.shifted {
			margin-left: 200px;
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

		/* Footer hanya di bagian kanan */
		.footer {
			background-color: #f8f9fa;
			text-align: center;
			padding: 10px 20px;
			font-size: 14px;
			color: #333;
			right: 0;
			bottom: 0;
			width: auto; /* Sesuai isi kontennya */
			min-width: 200px; /* Opsional, atur lebar minimum */
			border-top-left-radius: 10px; /* Agar sudut kiri atas melengkung */
			box-shadow: -2px -2px 5px rgba(0, 0, 0, 0.1); /* Tambahkan bayangan agar terlihat melayang */
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
			height: 50px;
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

		/* Judul */
		.title {
			font-size: 20px;
			font-weight: bold;
			color: white;
			margin-left: auto; /* Membuatnya tetap di kanan */
			margin-right: 5px; /* Beri jarak dengan sisi kanan */
			text-transform: uppercase;
		}
		.container {
			margin-top: 50px;
			padding: 20px;
			background-color: #f8f9fa;
			border-radius: 10px;
			box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
		}

		h1.text-primary {
			font-size: 24px;
			color: #007bff;
			font-weight: bold;
			text-transform: uppercase;
			letter-spacing: 1.5px;
			margin-bottom: 20px;
			text-shadow: 1px 1px 3px rgba(0, 0, 0, 0.1);
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
	
	<div id="content">
    <div class="container mt-5">
        <h2 class="mb-4 text-center text-primary">Jadwal Mata Kuliah</h1>
		
	<!-- Form Filter Kelas -->
	<form method="GET" action="jadwal.php" class="mb-4">
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

        <a href="jadwal.php?tambah=1" class="btn btn-success mb-4"><i class="fas fa-plus-circle"></i> Tambah Jadwal</a>

        <?php if ($show_form): ?>
            <div class="card mt-4">
                <div class="card-header">
                    <?php echo isset($_GET['edit_id']) ? 'Edit Jadwal' : 'Tambah Jadwal'; ?>
                </div>
                <div class="card-body">
                    <form method="POST" action="jadwal.php">
                        <input type="hidden" name="edit_id" value="<?php echo isset($edit_row['id']) ? $edit_row['id'] : ''; ?>">
                        <div class="mb-3">
                            <label for="mata_kuliah" class="form-label">Mata Kuliah</label>
                            <input type="text" class="form-control" id="mata_kuliah" name="mata_kuliah" value="<?php echo isset($edit_row['mata_kuliah']) ? $edit_row['mata_kuliah'] : ''; ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="hari" class="form-label">Hari</label>
                            <select class="form-control" id="hari" name="hari" required>
                                <option value="">Pilih Hari</option>
                                <?php 
                                $hari_list = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'];
                                foreach ($hari_list as $hari_option) {
                                    $selected = (isset($edit_row['hari']) && $edit_row['hari'] == $hari_option) ? 'selected' : '';
                                    echo "<option value='$hari_option' $selected>$hari_option</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="waktu_mulai" class="form-label">Waktu Mulai</label>
                            <input type="time" class="form-control" id="waktu_mulai" name="waktu_mulai" value="<?php echo isset($edit_row['waktu_mulai']) ? $edit_row['waktu_mulai'] : ''; ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="waktu_selesai" class="form-label">Waktu Selesai</label>
                            <input type="time" class="form-control" id="waktu_selesai" name="waktu_selesai" value="<?php echo isset($edit_row['waktu_selesai']) ? $edit_row['waktu_selesai'] : ''; ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="kelas" class="form-label">Kelas</label>
                            <select class="form-control" id="kelas" name="kelas" required>
                                <option value="">Pilih Kelas</option>
                                <?php 
                                foreach ($kelas_list as $k) {
                                    $selected = (isset($edit_row['kelas']) && $edit_row['kelas'] == $k) ? 'selected' : '';
                                    echo "<option value='$k' $selected>$k</option>";
                                }
                                ?>
                            </select>
                       <div class="mb-3">
							<label for="nama_dosen" class="form-label">Nama Dosen</label>
							<select class="form-control" id="nama_dosen" name="nama_dosen" required>
								<option value="">-- Pilih Dosen --</option>
								<?php while ($dosen = mysqli_fetch_assoc($result_dosen)) : ?>
									<option value="<?php echo $dosen['nama']; ?>" 
										<?php echo (isset($edit_row['nama_dosen']) && $edit_row['nama_dosen'] == $dosen['nama']) ? 'selected' : ''; ?>>
										<?php echo $dosen['nama']; ?>
									</option>
								<?php endwhile; ?>
							</select>
						</div>
                        <button type="submit" name="submit" class="btn btn-primary">Simpan</button>
                        <a href="jadwal.php" class="btn btn-secondary">Batal</a>
                    </form>
                </div>
            </div>
        <?php endif; ?>

        <!-- Tabel jadwal -->
        <div class="table-responsive mt-4">
            <table class="table table-bordered">
                
                <tbody>
				
                    <?php 
                    $previous_class = '';
                    $previous_day = '';
                    while ($row = mysqli_fetch_assoc($result)): 
                        // Menampilkan pemisah kelas
                        if ($row['kelas'] != $previous_class) {
							echo "<tr><td colspan='7' class='spacing'></td></tr>";
                            echo "<tr><td colspan='7' class='text-center bg-light'><strong>Kelas {$row['kelas']}</strong></td></tr><thead>
                    <tr>
                        <th>Mata Kuliah</th>
                        <th>Hari</th>
                        <th>Waktu Mulai</th>
                        <th>Waktu Selesai</th>
                        <th>Kelas</th>
						<th>Nama Dosen</th>
                        <th>Aksi</th>
                    </tr>
                </thead>";
                            $previous_class = $row['kelas'];
                        }
                        // Menampilkan pemisah hari
                        if ($row['hari'] != $previous_day) {
                            echo "<tr><td colspan='7' class='text-white bg-success'><strong>{$row['hari']}</strong></td></tr>";
                            $previous_day = $row['hari'];
                        }
                    ?>
                    <tr>
                        <td><?php echo $row['mata_kuliah']; ?></td>
                        <td><?php echo $row['hari']; ?></td>
                        <td><?php echo $row['waktu_mulai']; ?></td>
                        <td><?php echo $row['waktu_selesai']; ?></td>
                        <td><?php echo $row['kelas']; ?></td>
						<td><?php echo $row['nama_dosen']; ?></td>
                        <td>
							<a href="jadwal.php?edit_id=<?php echo isset($row['id']) ? htmlspecialchars($row['id']) : ''; ?>" class="btn btn-warning btn-sm">
								<i class="fas fa-edit"></i> Edit
							</a>
							<a href="jadwal.php?hapus_id=<?php echo isset($row['id']) ? htmlspecialchars($row['id']) : ''; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Apakah Anda yakin ingin menghapus jadwal ini?');">
								<i class="fas fa-trash-alt"></i> Hapus
							</a>
						</td>

                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

    <footer class="text-center mt-5">
        <p>&copy; 2025 Smart Presence D4 Teknik Elektronika. All rights reserved.</p>
    </footer>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
</body>
</html>
