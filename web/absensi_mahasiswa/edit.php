<?php
include('koneksi.php');

// Cek apakah ID mahasiswa dikirimkan
if (!isset($_GET['id_mahasiswa'])) {
    echo "<script>alert('ID tidak ditemukan!'); window.location.href = 'mahasiswa.php';</script>";
    exit;
}

$id_mahasiswa = $_GET['id_mahasiswa'];

// Ambil data mahasiswa berdasarkan ID
$query = "SELECT * FROM mahasiswa WHERE id_mahasiswa = ?";
$stmt = mysqli_prepare($conn, $query);
if ($stmt) {
    mysqli_stmt_bind_param($stmt, "i", $id_mahasiswa);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    
    if (!$row) {
        echo "<script>alert('Data tidak ditemukan!'); window.location.href = 'mahasiswa.php';</script>";
        exit;
    }
} else {
    echo "<script>alert('Terjadi kesalahan saat mengambil data!');</script>";
    exit;
}

function formatUID($uid) {
    return substr($uid, 0, 2) . str_repeat('*', max(0, strlen($uid) - 2));
}
$uid_asli = $row['uid_rfid'];
$display_uid = formatUID($uid_asli);

// Jika form disubmit
if (isset($_POST['submit'])) {
    $nama = htmlspecialchars($_POST['nama']);
    $nim_nip = htmlspecialchars($_POST['nim_nip']);
    $role = $_POST['role'];  
    $id_fingerprint = htmlspecialchars($_POST['id_fingerprint']);
    $kelas = isset($_POST['kelas']) ? htmlspecialchars($_POST['kelas']) : NULL; 
	
	$input_uid = htmlspecialchars($_POST['uid_rfid']);
    $uid_asli_post = htmlspecialchars($_POST['uid_rfid_asli']);

    // Cek apakah UID diinput mengandung bintang *, jika ya berarti tidak dirubah, pakai UID asli
    if (strpos($input_uid, '*') !== false) {
        $uid_rfid = $uid_asli_post;
    } else {
        $uid_rfid = $input_uid;
    }
	
    if ($role == "Dosen") {
        // **Pindahkan ke tabel `dosen`**
        $query_insert_dosen = "INSERT INTO dosen (nama, nip, id_fingerprint, uid_rfid) VALUES (?, ?, ?, ?)";
        $stmt_insert = mysqli_prepare($conn, $query_insert_dosen);
        mysqli_stmt_bind_param($stmt_insert, "ssss", $nama, $nim_nip, $id_fingerprint, $uid_rfid);
        $insert_success = mysqli_stmt_execute($stmt_insert);

        if ($insert_success) {
            // **Hapus dari tabel `mahasiswa` karena sudah dipindahkan**
            $query_delete = "DELETE FROM mahasiswa WHERE id_mahasiswa = ?";
            $stmt_delete = mysqli_prepare($conn, $query_delete);
            mysqli_stmt_bind_param($stmt_delete, "i", $id_mahasiswa);
            mysqli_stmt_execute($stmt_delete);
            
            echo "<script>alert('Data berhasil dipindahkan ke dosen!'); window.location.href = 'dosen.php';</script>";
            exit;
        } else {
            echo "<script>alert('Gagal memindahkan ke dosen!');</script>";
        }
    } else {
        // **Update tabel `mahasiswa` jika tetap mahasiswa**
        $query_update = "UPDATE mahasiswa SET nama = ?, nim = ?, kelas = ?, id_fingerprint = ?, uid_rfid = ? WHERE id_mahasiswa = ?";
        $stmt_update = mysqli_prepare($conn, $query_update);
        mysqli_stmt_bind_param($stmt_update, "sssssi", $nama, $nim_nip, $kelas, $id_fingerprint, $uid_rfid, $id_mahasiswa);
        $update_success = mysqli_stmt_execute($stmt_update);

        if ($update_success) {
            echo "<script>alert('Data mahasiswa berhasil diperbarui!'); window.location.href = 'mahasiswa.php';</script>";
            exit;
        } else {
            echo "<script>alert('Gagal memperbarui data mahasiswa!');</script>";
        }
    }
}
?>


<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Data Pengguna</title>
    <link rel="stylesheet" href="style.css">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
            margin: 0;
            padding: 0;
        }

        /* Navbar */
        .navbar {
            background-color: #007BFF;
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: white;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .navbar h2 {
            margin: 0;
            font-size: 20px;
        }

        .navbar a {
            color: white;
            text-decoration: none;
            padding: 8px 15px;
            background-color: rgba(255, 255, 255, 0.2);
            border-radius: 5px;
        }

        .navbar a:hover {
            background-color: rgba(255, 255, 255, 0.3);
        }

        /* Container */
        .container {
            width: 100%;
            max-width: 600px;
            background: #fff;
            padding: 20px 10px;
            border-radius: 12px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            margin: 50px auto; /* Atur margin agar lebih ke bawah */
        }

        h1 {
            text-align: center;
            font-size: 24px;
            color: #007BFF;
            margin-bottom: 10px;
        }

        label {
            font-weight: bold;
            display: block;
            margin-top: 15px;
            color: #555;
        }

        input, select {
            width: 100%;
            padding: 12px;
            margin-top: 8px;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-size: 16px;
            box-sizing: border-box;
        }

        .form-group {
            margin-bottom: 10px;
        }
		
		form {
			margin-top: -10px;
		}

        .button-group {
            display: flex;
            justify-content: space-between;
            gap: 10px;
            margin-top: 25px;
        }

        button, .cancel-button {
            padding: 12px 20px;
            font-size: 16px;
            border-radius: 6px;
            cursor: pointer;
            width: 48%;
            text-align: center;
        }

        button {
            background-color: #007BFF;
            color: white;
            border: none;
        }

        button:hover {
            background-color: #0056b3;
        }

        .cancel-button {
            background-color: #dc3545;
            color: white;
            text-decoration: none;
            border: none;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .cancel-button:hover {
            background-color: #b52b3a;
        }

        #kelas-container {
            display: none;
        }

        /* Responsive */
        @media (max-width: 600px) {
            .container {
                padding: 20px;
            }

            h1 {
                font-size: 22px;
                margin-bottom: 25px;
            }

            .navbar h2 {
                font-size: 18px;
            }

            .button-group {
                flex-direction: column;
            }

            button, .cancel-button {
                width: 100%;
                margin: 5px 0;
            }
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

	<!-- Navbar -->
    <div class="navbar">
        <h2>
			<span class="smart">SMART</span> 
			<span class="absence">ABSENCE</span>
		</h2>
    </div>

    <div class="container">
        <h1>Edit Data</h1>
        <form action="edit.php?id_mahasiswa=<?php echo $row['id_mahasiswa']; ?>" method="POST">
            <div class="form-group">
                <label for="nama">Nama:</label>
                <input type="text" id="nama" name="nama" value="<?php echo htmlspecialchars($row['nama']); ?>" required>
            </div>

            <div class="form-group">
                <label for="nim_nip">NIM/NIP:</label>
                <input type="text" id="nim_nidn" name="nim_nip" value="<?php echo htmlspecialchars($row['nim']); ?>" required>
            </div>

            <div class="form-group">
                <label for="role">Status:</label>
                <select name="role" id="role" required>
                    <option value="Mahasiswa" <?php echo ($row['role'] == 'Mahasiswa') ? 'selected' : ''; ?>>Mahasiswa</option>
                    <option value="Dosen" <?php echo ($row['role'] == 'Dosen') ? 'selected' : ''; ?>>Dosen</option>
                </select>
            </div>

			<div class="form-group" id="kelas-container">
				<label for="kelas">Kelas:</label>
				<select id="kelas" name="kelas">
					<option value="">-- Pilih Kelas --</option>
					<option value="1A" <?php echo ($row['kelas'] == '1A') ? 'selected' : ''; ?>>1A</option>
					<option value="1B" <?php echo ($row['kelas'] == '1B') ? 'selected' : ''; ?>>1B</option>
					<option value="2A" <?php echo ($row['kelas'] == '2A') ? 'selected' : ''; ?>>2A</option>
					<option value="2B" <?php echo ($row['kelas'] == '2B') ? 'selected' : ''; ?>>2B</option>
					<option value="3A" <?php echo ($row['kelas'] == '3A') ? 'selected' : ''; ?>>3A</option>
					<option value="3B" <?php echo ($row['kelas'] == '3B') ? 'selected' : ''; ?>>3B</option>
					<option value="4A" <?php echo ($row['kelas'] == '4A') ? 'selected' : ''; ?>>4A</option>
					<option value="4B" <?php echo ($row['kelas'] == '4B') ? 'selected' : ''; ?>>4B</option>
				</select>
			</div>

            <div class="form-group">
                <label for="id_fingerprint">ID Sidik Jari:</label>
                <input type="text" id="id_fingerprint" name="id_fingerprint" value="<?php echo htmlspecialchars($row['id_fingerprint']); ?>" required>
            </div>

			<?php
			$uid = $row['uid_rfid'];
			$display_uid = substr($uid, 0, 2) . str_repeat('*', max(0, strlen($uid) - 2));
			?>
			<script>
			document.getElementById('uid_rfid').addEventListener('focus', function(){
				this.value = '<?php echo $uid; ?>';
			});
			</script>

			<div class="form-group">
				<label for="uid_rfid">UID RFID:</label>
				<input type="text" id="uid_rfid" name="uid_rfid" value="<?php echo htmlspecialchars($display_uid); ?>" required>
				<input type="hidden" name="uid_rfid_asli" value="<?php echo htmlspecialchars($uid); ?>">
			</div>
            <div class="button-group">
                <button type="submit" name="submit">Simpan Perubahan</button>
                <a href="<?php echo ($row['role'] == 'Dosen') ? 'dosen.php' : 'mahasiswa.php'; ?>" class="cancel-button">Batal</a>
            </div>
        </form>
    </div>

    
    <script>
        // Tampilkan atau sembunyikan kolom kelas berdasarkan pilihan role
        document.getElementById('role').addEventListener('change', function () {
            let kelasContainer = document.getElementById('kelas-container');
            if (this.value === "Dosen") {
                kelasContainer.style.display = "none";
            } else {
                kelasContainer.style.display = "block";
            }
        });

        // Jalankan saat halaman pertama kali dimuat
        window.onload = function () {
            let role = document.getElementById('role').value;
            let kelasContainer = document.getElementById('kelas-container');
            if (role === "Dosen") {
                kelasContainer.style.display = "none";
            } else {
                kelasContainer.style.display = "block";
            }
        };
    </script>
</body>
</html>
