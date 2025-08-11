<?php
	// Menghubungkan dengan database
	include('koneksi.php');

	// Fungsi untuk mengirimkan respons JSON
	function sendResponse($status, $message, $data = null) {
		header('Content-Type: application/json');
		echo json_encode([
			'status' => $status,
			'message' => $message,
			'data' => $data
		], JSON_UNESCAPED_UNICODE);
		exit;
	}

	// **1. Mengambil data berdasarkan ID Fingerprint atau UID RFID**
	if (isset($_GET['ID']) || isset($_GET['uid'])) {
		$input = isset($_GET['ID']) ? $_GET['ID'] : $_GET['uid'];

		if (empty($input)) {
			sendResponse('error', 'ID atau UID tidak boleh kosong');
		}

		$query = isset($_GET['ID']) ? "SELECT nama, nip FROM dosen WHERE id_fingerprint = ?" : "SELECT nama, nip FROM dosen WHERE uid_rfid = ?";
		$stmt = mysqli_prepare($conn, $query);
		mysqli_stmt_bind_param($stmt, "s", $input);
		mysqli_stmt_execute($stmt);
		$result = mysqli_stmt_get_result($stmt);

		if ($row = mysqli_fetch_assoc($result)) {
			sendResponse('success', 'Data ditemukan', $row);
		} else {
			sendResponse('error', 'Data tidak ditemukan');
		}
	}

	// **2. Menangani hapus data dosen (pakai redirect)**
	if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['hapus'])) {
		$id_dosen = intval($_POST['hapus']);
		$stmt = $conn->prepare("DELETE FROM dosen WHERE id_dosen = ?");
		$stmt->bind_param("i", $id_dosen);
		
		if ($stmt->execute()) {
			header("Location: dosen.php?hapus=success");
			exit;
		} else {
			header("Location: dosen.php?hapus=error");
			exit;
		}
		$stmt->close();
	}

	// **3. Menangani update data dosen**
	if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update'])) {
		$id_dosen = intval($_POST['id_dosen']);
		$nama = htmlspecialchars($_POST['nama']);
		$nip = htmlspecialchars($_POST['nip']);
		$id_fingerprint = htmlspecialchars($_POST['id_fingerprint']);
		$uid_rfid = htmlspecialchars($_POST['uid_rfid']);

		$stmt = $conn->prepare("UPDATE dosen SET nama=?, nip=?, id_fingerprint=?, uid_rfid=? WHERE id_dosen=?");
		$stmt->bind_param("ssssi", $nama, $nip, $id_fingerprint, $uid_rfid, $id_dosen);
		
		if ($stmt->execute()) {
			// **Perbaikan: Redirect ke halaman dosen.php agar tampilan normal**
			header("Location: dosen.php?update=success");
			exit;
		} else {
			header("Location: dosen.php?update=error");
			exit;
		}
		$stmt->close();
	}

	// **4. Mengambil semua data dosen**
	$sql_dosen = "SELECT * FROM dosen ORDER BY nama ASC";
	$result_dosen = $conn->query($sql_dosen);
	$data_dosen = $result_dosen->fetch_all(MYSQLI_ASSOC);

	// Jika diminta format JSON, kirim sebagai JSON
	if (isset($_GET['format']) && $_GET['format'] == 'json') {
		sendResponse('success', 'Data dosen ditemukan', $data_dosen);
	}
	$conn->close();
?>


<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Dosen</title>
    
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    
    <!-- FontAwesome untuk ikon -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
	
    <style>
        body {
            font-family: Arial, sans-serif;
        }

        /* Navbar Styling */
        .navbar {
            background-color: #007bff; /* Warna biru */
            padding: 15px;
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 70px; /* Jarak antar item navbar */
        }

        .navbar a {
            color: #fff;
            text-decoration: none;
            font-size: 18px;
            display: flex;
            align-items: center;
            gap: 8px; /* Jarak antara ikon dan teks */
            font-weight: bold;
        }

        .navbar a:hover {
            color: #d1d1d1;
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

        /* Styling untuk Tabel */
        .table {
            margin-top: 5px;
            background-color: #fff; /* Warna dasar putih */
        }

        .table th {
            color:black !important; /* Teks header putih */
            text-align: center;
        }

        .table td {
            color:black !important; /* Warna teks isi tabel tetap hitam */
            text-align: center;
            vertical-align: middle;
        }

        /* Tombol Aksi */
        .btn {
            padding: 5px 10px;
            font-size: 14px;
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
			position: relative;
			padding: 10px;
			width: 100%;
			bottom: 0;
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
		/* Saat sidebar terbuka */
		.sidebar.active {
			left: 0;
		}
		/* Saat sidebar terbuka, konten bergeser */
		.sidebar.active ~ .content {
			margin-left: 170px;
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
			margin-left: 5px; /* Kurangi margin kiri */
			margin-top: 20px; /* Sesuaikan dengan tinggi header */
			padding: 30px; /* Kurangi padding agar tidak terlalu jauh */
			transition: margin-left 0.3s;
			width: 100%;
			text-align: center;
		}


		.content.shifted {
			margin-left: 250px;
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
		
		.modal-content {
			transition: all 0.3s ease-in-out;
		}
		
		.modal-header i {
			font-size: 1.2rem;
		}
		
		#alertBox {
			position: fixed;
			top: 70px; /* agak bawah dari header */
			right: 20px;
			z-index: 1050; /* lebih tinggi dari modal dan sidebar */
			width: auto;
			max-width: 300px;
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
	
	<?php if (isset($_GET['update'])): ?>
		<div id="alertBox" class="alert 
			<?= ($_GET['update'] == 'success') ? 'alert-success' : 'alert-danger'; ?> 
			alert-dismissible fade show" role="alert" 
			style="position: fixed; top: 70px; right: 20px; z-index: 1050; max-width: 300px;">
			<strong><?= ($_GET['update'] == 'success') ? 'Berhasil!' : 'Gagal!'; ?></strong> 
			<?= ($_GET['update'] == 'success') ? 'Data dosen telah diperbarui.' : 'Terjadi kesalahan saat memperbarui data.'; ?>
			<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
		</div>
	<script>
		setTimeout(() => {
			const alertBox = document.getElementById("alertBox");
			if (alertBox) {
				alertBox.style.transition = "opacity 0.5s ease";
				alertBox.style.opacity = "0";
				setTimeout(() => alertBox.style.display = "none", 500);
			}
		}, 5000);  // ubah 2000 jadi 5000 agar hilang setelah 5 detik
	</script>
	<?php endif; ?>
	<?php if (isset($_GET['hapus'])): ?>
			<div id="alertBox" class="alert 
				<?= ($_GET['hapus'] == 'success') ? 'alert-success' : 'alert-danger'; ?> 
				alert-dismissible fade show" role="alert" style="position: fixed; top: 70px; right: 20px; z-index: 1050; max-width: 300px;">
				<strong><?= ($_GET['hapus'] == 'success') ? 'Berhasil!' : 'Gagal!'; ?></strong> 
				<?= ($_GET['hapus'] == 'success') ? 'Data dosen telah dihapus.' : 'Terjadi kesalahan saat menghapus data.'; ?>
				<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
			</div>
		<script>
			setTimeout(() => {
				const alertBox = document.getElementById('alertBox');
				if(alertBox){
					alertBox.style.transition = 'opacity 0.5s ease';
					alertBox.style.opacity = '0';
					setTimeout(() => alertBox.remove(), 500);
				}
			}, 5000);
		</script>
	<?php endif; ?>

	<div class="content">
    <h2 class="text-center my-4">Data Dosen</h2>
    <div class="table-responsive">
        <table class="table table-bordered table-hover mx-auto">

            <tr>
                <th>ID</th>
                <th>Nama</th>
                <th>NIP/NIDN</th>
                <th>ID Fingerprint</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
             <?php if (!empty($data_dosen)): ?>
				<?php $no = 1; // Mulai nomor dari 1 ?>
					<?php foreach ($data_dosen as $dosen): ?>
                <tr>
					<td><?= $no++; ?></td> <!-- Gunakan nomor urut dinamis -->                    
                    <td style="text-align: left;"><?= htmlspecialchars($dosen['nama']); ?></td>
                    <td><?= htmlspecialchars($dosen['nip']); ?></td>
                    <td><?= htmlspecialchars($dosen['id_fingerprint']); ?></td>
                    <td>
                        <button class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#editModal<?= $dosen['id_dosen']; ?>">
                            <i class="fas fa-edit"></i> Edit
                        </button>
                        <form action="dosen.php" method="POST" style="display:inline-block;">
                            <input type="hidden" name="hapus" value="<?= htmlspecialchars($dosen['id_dosen']); ?>">
                            <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Yakin ingin menghapus?')">
                                <i class="fas fa-trash"></i> Hapus
                            </button>
                        </form>
                    </td>
                </tr>

                <!-- Modal Edit -->
				<div class="modal fade" id="editModal<?= $dosen['id_dosen']; ?>" tabindex="-1" aria-labelledby="editModalLabel<?= $dosen['id_dosen']; ?>" aria-hidden="true">
					<div class="modal-dialog modal-dialog-centered">
						<div class="modal-content shadow rounded-3 border-0">
							<div class="modal-header bg-primary text-white">
								<h5 class="modal-title" id="editModalLabel<?= $dosen['id_dosen']; ?>">
									<i class="fas fa-user-edit me-2"></i>Edit Data Dosen
								</h5>
								<button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Tutup"></button>
							</div>
							<div class="modal-body px-4">
								<form action="dosen.php" method="POST">
									<input type="hidden" name="id_dosen" value="<?= htmlspecialchars($dosen['id_dosen']); ?>">

									<div class="form-floating mb-3">
										<input type="text" class="form-control" name="nama" id="nama<?= $dosen['id_dosen']; ?>" value="<?= htmlspecialchars($dosen['nama']); ?>" required>
										<label for="nama<?= $dosen['id_dosen']; ?>">Nama</label>
									</div>

									<div class="form-floating mb-3">
										<input type="text" class="form-control" name="nip" id="nip<?= $dosen['id_dosen']; ?>" value="<?= htmlspecialchars($dosen['nip']); ?>" required>
										<label for="nip<?= $dosen['id_dosen']; ?>">NIP/NIDN</label>
									</div>

									<div class="form-floating mb-3">
										<input type="text" class="form-control" name="id_fingerprint" id="fp<?= $dosen['id_dosen']; ?>" value="<?= htmlspecialchars($dosen['id_fingerprint']); ?>">
										<label for="fp<?= $dosen['id_dosen']; ?>">ID Fingerprint</label>
									</div>
									<?php
									$uid = $dosen['uid_rfid'];
									$display_uid = substr($uid, 0, 2) . str_repeat('*', max(0, strlen($uid) - 2));
									?>

									<div class="form-floating mb-3">
										<input type="text" class="form-control" id="rfid_display<?= $dosen['id_dosen']; ?>" value="<?= htmlspecialchars($display_uid); ?>" oninput="unmaskUID(this, 'uid_rfid_hidden<?= $dosen['id_dosen']; ?>')">
										<input type="hidden" name="uid_rfid" id="uid_rfid_hidden<?= $dosen['id_dosen']; ?>" value="<?= htmlspecialchars($uid); ?>">

										<label for="rfid<?= $dosen['id_dosen']; ?>">UID RFID</label>
									</div>
									<script>
									function unmaskUID(displayInput, hiddenInputId) {
										const raw = displayInput.value.replace(/\*/g, '');
										document.getElementById(hiddenInputId).value = raw;

										// Apply masking again (2 huruf depan saja yang terlihat)
										if (raw.length > 2) {
											displayInput.value = raw.substring(0, 2) + '*'.repeat(raw.length - 2);
										} else {
											displayInput.value = raw;
										}
									}
									</script>

									<div class="text-end">
										<button type="submit" name="update" class="btn btn-success">
											<i class="fas fa-save me-1"></i>Simpan Perubahan
										</button>
									</div>
								</form>
							</div>
						</div>
					</div>
				</div>

                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="6" class="text-center">Tidak ada data dosen.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
	<footer>
        &copy; <?php echo date("Y"); ?> Smart Presence D4 Teknik Elektronika. All rights reserved.
    </footer>
	<!-- Bootstrap JavaScript -->
	<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
