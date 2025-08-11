<?php
session_start();
include 'koneksi.php'; // Pastikan ada koneksi ke database

if (isset($_POST['login'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $query = "SELECT * FROM login WHERE username = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "s", $username);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if (mysqli_num_rows($result) > 0) {
        $data = mysqli_fetch_assoc($result);
        if (password_verify($password, $data['password'])) {
            $_SESSION['user'] = $data;

            if (isset($_POST['remember'])) {
                setcookie("username", $username, time() + (86400 * 30), "/");
            }

            header("Location: dashboard.php");
            exit;
        } else {
            echo "<script>alert('Password salah!');</script>";
        }
    } else {
        echo "<script>alert('Username tidak ditemukan!');</script>";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Polinema</title>
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

        .login-container {
            background: #fff;
            padding: 40px 30px;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
            text-align: center;
            width: 80%;
            max-width: 450px;
        }

        .logo {
            width: 90px;
            margin-bottom: 15px;
        }

        h2 {
            margin-bottom: 25px;
            color: #FF69B4;
            font-weight: 600;
			font-size: 14px;
        }

        input[type="text"],
        input[type="password"] {
            width: 90%;
            padding: 12px;
            margin: 10px 0 18px;
            border: 1px solid #ccc;
            border-radius: 8px;
            font-size: 14px;
            transition: border-color 0.3s;
        }

        input:focus {
            outline: none;
            border-color: #2f80ed;
        }

        .remember {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 18px;
            font-size: 14px;
        }

        .forgot-password {
            color: #2f80ed;
            text-decoration: none;
        }

        .forgot-password:hover {
            text-decoration: underline;
        }

        button {
            width: 100%;
            padding: 12px;
            background: #2f80ed;
            color: #fff;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            cursor: pointer;
            transition: background 0.3s;
        }

        button:hover {
            background: #1366d6;
        }

        @media (max-width: 480px) {
            .login-container {
                padding: 30px 20px;
            }
        }
		
    </style>
</head>
<body>
	<div class="page-wrapper">
		<div class="login-container">
			<img src="logo.png" alt="Logo" class="logo">
			<h2>Silakan Masukan Username dan Password</h2>
			<form method="POST">
				<input type="text" name="username" placeholder="Username"
					value="<?php echo isset($_COOKIE['username']) ? $_COOKIE['username'] : ''; ?>" required>
				<input type="password" name="password" placeholder="Password" required>

				<div class="remember">
					<label><input type="checkbox" name="remember"> Ingat Saya</label>
					<a href="reset_password.php" class="forgot-password">Lupa Password?</a>
				</div>

				<button type="submit" name="login">Login</button>
			</form>
		</div>
	 </div>
	<footer class="footer">
        &copy; <?php echo date("Y"); ?> Smart Presence D4 Teknik Elektronika. All rights reserved.
    </footer>
</body>
</html>
