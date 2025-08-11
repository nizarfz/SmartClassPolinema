<?php
include 'koneksi.php';

$message = "";

if (isset($_POST['submit'])) {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    if ($username == "" || $password == "") {
        $message = "<p class='error'>Username dan password wajib diisi!</p>";
    } else {
        // Cek apakah username sudah digunakan
        $check = $conn->prepare("SELECT * FROM login WHERE username = ?");
        $check->bind_param("s", $username);
        $check->execute();
        $result = $check->get_result();

        if ($result->num_rows > 0) {
            $message = "<p class='error'>Username sudah digunakan. Gunakan username lain.</p>";
        } else {
            // Hash password dan simpan ke database
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $query = "INSERT INTO login (username, password) VALUES (?, ?)";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "ss", $username, $hashedPassword);

            if (mysqli_stmt_execute($stmt)) {
                $message = "<p class='success'>User berhasil ditambahkan!</p>";
            } else {
                $message = "<p class='error'>Gagal: " . mysqli_error($conn) . "</p>";
            }
        }
    }
}
?>


<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah User Baru</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(to right, #56ccf2, #2f80ed);
            display: flex;
            justify-content: center;
            align-items: center;
            flex-direction: column;
			min-height: 100vh;
			font-family: 'Poppins', sans-serif;
            margin: 0;
        }
		.page-wrapper {
			display: flex;
			flex-direction: column;
			flex: 1;
			justify-content: center;
			align-items: center;
		}

		.footer {
			text-align: center;
			color: white;
			font-size: 14px;
			padding: 15px;
		}
		
        .form-container {
            background: #fff;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 400px;
        }
        h2 {
            text-align: center;
            margin-bottom: 25px;
            color: #333;
        }
        label {
            font-weight: 500;
            display: block;
            margin-top: 15px;
        }
        input[type="text"],
        input[type="password"] {
            width: 100%;
            padding: 12px;
            border: 1px solid #ccc;
            border-radius: 8px;
            margin-top: 5px;
            font-size: 14px;
        }
        input[type="submit"] {
            width: 100%;
            background-color: #2f80ed;
            color: white;
            border: none;
            padding: 12px;
            margin-top: 20px;
            border-radius: 8px;
            font-size: 16px;
            cursor: pointer;
        }
        input[type="submit"]:hover {
            background-color: #1c60c5;
        }
        .success {
            background: #d4edda;
            color: #155724;
            padding: 10px;
            border-radius: 5px;
            margin-top: 15px;
            text-align: center;
        }
        .error {
            background: #f8d7da;
            color: #721c24;
            padding: 10px;
            border-radius: 5px;
            margin-top: 15px;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="form-container">
        <h2>Tambah User Baru</h2>
        <?php echo $message; ?>
        <form method="POST">
            <label for="username">Username:</label>
            <input type="text" id="username" name="username" required>

            <label for="password">Password:</label>
            <input type="password" id="password" name="password" required>

            <input type="submit" name="submit" value="Tambah">
        </form>
    </div>
		<footer class="footer">
			&copy; <?php echo date("Y"); ?> Smart Absence D4 Teknik Elektronika. All rights reserved.
		</footer>
</body>
</html>
