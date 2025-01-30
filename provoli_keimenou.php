<?php
session_start();

// Ensure the user is logged in as a professor
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'professor') {
    header("Location: login.php?message=Please log in as a professor to access this page");
    exit();
}

require 'db.php';

// Validate and get the thesis ID from the URL
if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("Invalid Thesis ID.");
}

$thesis_id = intval($_GET['id']);

// Fetch the text file path from the database
$query = "SELECT path_of_files FROM support_files WHERE id_diplomatikis = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $thesis_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result && $result->num_rows > 0) {
    $support_file = $result->fetch_assoc();
    $file_path = $support_file['path_of_files'];
    if (file_exists($file_path)) {
        $file_content = file_get_contents($file_path);
    } else {
        die("File not found.");
    }
} else {
    die("No file found for the specified thesis.");
}

$stmt->close();
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student's Text</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f5f5dc;
            color: #333;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            flex-direction: column;
            height: 100vh;
        }
        .container {
            width: 90%;
            max-width: 800px;
            background: rgba(255, 255, 255, 0.9);
            padding: 20px 30px;
            border-radius: 15px;
            box-shadow: 0 8px 15px rgba(0, 0, 0, 0.2);
            margin-top: 20px;
            overflow-y: auto;
            max-height: 80%;
        }
        .back-button {
            margin-top: 20px;
            padding: 10px;
            background-color: #800020;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        .back-button:hover {
            background-color: #660017;
        }
        pre {
            white-space: pre-wrap; /* Ensures long lines wrap */
            word-wrap: break-word; /* Ensures words break correctly */
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Student's Text</h2>
        <pre><?php echo htmlspecialchars($file_content); ?></pre>
        <button class="back-button" onclick="window.history.back()">Back</button>
    </div>
</body>
</html>