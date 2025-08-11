<?php
include('koneksi.php'); // Koneksi ke database

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['username'])) {
    // Ambil username dan password baru yang dimasukkan oleh pengguna
    $username = $_POST['username'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Cek apakah password dan konfirmasi password cocok
    if ($password === $confirm_password) {
        // Hash password baru sebelum disimpan
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Cek apakah username ada di database
        $stmt = $conn->prepare("SELECT * FROM login WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            // Jika username ditemukan, update password di database
            $stmt = $conn->prepare("UPDATE login SET password = ? WHERE username = ?");
            $stmt->bind_param("ss", $hashed_password, $username);
            $stmt->execute();

            echo "<p class='success-message'>Password berhasil direset. Silakan login dengan password baru.</p>";
        } else {
            echo "<p class='error-message'>Username tidak ditemukan.</p>";
        }
    } else {
        echo "<p class='error-message'>Password dan konfirmasi password tidak cocok.</p>";
    }
} else {
    // Form untuk input username dan password baru
    ?>
    <!DOCTYPE html>
    <html lang="id">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Reset Password</title>
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

            .container {
                background-color: #fff;
                padding: 30px;
                border-radius: 8px;
                box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
                width: 100%;
                max-width: 400px;
            }

            h1 {
                text-align: center;
                color: #007bff;
                font-size: 24px;
                margin-bottom: 20px;
            }

            label {
                font-size: 14px;
                color: #333;
                margin-bottom: 8px;
                display: block;
            }

            input[type="text"], input[type="password"] {
                width: 90%;
                padding: 10px;
                margin: 10px 0 20px 0;
                border: 1px solid #ccc;
                border-radius: 4px;
                font-size: 14px;
            }

            button {
                width: 90%;
                padding: 12px;
                background-color: #007bff;
                color: #fff;
                border: none;
                border-radius: 4px;
                font-size: 16px;
                cursor: pointer;
            }

            button:hover {
                background-color: #007bff;
            }

            .error-message {
                color: #ff4d4d;
                font-size: 14px;
                text-align: center;
            }

            .success-message {
                color: #4CAF50;
                font-size: 14px;
                text-align: center;
            }

            .form-group {
                margin-bottom: 20px;
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
        </style>
    </head>
    <body>
        <div class="container">
            <h1>Reset Password</h1>
            <form action="reset_password.php" method="POST">
                <div class="form-group">
                    <label for="username">Masukkan Username</label>
                    <input type="text" id="username" name="username" required>
                </div>

                <div class="form-group">
                    <label for="password">Masukkan Password Baru</label>
                    <input type="password" id="password" name="password" required>
                </div>

                <div class="form-group">
                    <label for="confirm_password">Konfirmasi Password Baru</label>
                    <input type="password" id="confirm_password" name="confirm_password" required>
                </div>

                <button type="submit">Reset Password</button>
            </form>
        </div>
		<footer class="footer">
			&copy; <?php echo date("Y"); ?> Smart Absence D4 Teknik Elektronika. All rights reserved.
		</footer>
    </body>
    </html>
    <?php
}
?>
