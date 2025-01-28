<?php
session_start();

if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'student') {
    header("Location: login.php?message=Please log in as a student to access this page");
    exit();
}

require 'db.php';

$id_diplomatikis = $_GET['id_diplomatikis'] ?? null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $link = $_POST['link'] ?? null;

    // Handle file upload if present
    if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = 'uploads/';
        $file_name = basename($_FILES['file']['name']);
        $file_path = $upload_dir . uniqid() . '_' . $file_name;

        if (move_uploaded_file($_FILES['file']['tmp_name'], $file_path)) {
            $stmt = $conn->prepare("INSERT INTO support_files (id_diplomatikis, path_of_files, link) VALUES (?, ?, ?)");
            $stmt->bind_param("iss", $id_diplomatikis, $file_path, $link);

            if ($stmt->execute()) {
                $success_message = "Successfully uploaded!";
            } else {
                $error_message = "Failed to store file and link in the database.";
            }
        } else {
            $error_message = "Failed to upload the file.";
        }
    } elseif (!empty($link)) {
        // Insert only the link if no file is uploaded
        $stmt = $conn->prepare("INSERT INTO support_files (id_diplomatikis, link) VALUES (?, ?)");
        $stmt->bind_param("is", $id_diplomatikis, $link);

        if ($stmt->execute()) {
            $success_message = "Link successfully uploaded!";
        } else {
            $error_message = "Failed to store the link in the database.";
        }
    } else {
        $error_message = "No file or link provided.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Thesis</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f5f5dc;
            text-align: center;
            padding: 20px;
        }
        .container {
            background: rgba(255, 255, 255, 0.9);
            padding: 20px 30px;
            border-radius: 15px;
            box-shadow: 0 8px 15px rgba(0, 0, 0, 0.2);
            display: inline-block;
            text-align: left;
            max-width: 400px;
        }
        button {
            padding: 10px 20px;
            background-color: #007BFF;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <h1>Manage Thesis</h1>
    <div class="container">
        <?php if (!empty($success_message)): ?>
            <p style="color: green;"><?php echo $success_message; ?></p>
        <?php elseif (!empty($error_message)): ?>
            <p style="color: red;"><?php echo $error_message; ?></p>
        <?php endif; ?>
        <form method="POST" enctype="multipart/form-data">
            <label for="file">Upload Thesis documentation:</label><br><br>
            <input type="file" name="file" id="file" accept=".txt"><br><br>
            <label for="link">Additional files:</label><br><br>
            <input type="url" name="link" id="link" placeholder="https://example.com/file"><br><br>
            <button type="submit">Upload</button>
        </form>
        <br>
        <button onclick="location.href='student_interface.php'">Back</button>
    </div>
</body>
</html>
