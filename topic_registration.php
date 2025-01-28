<?php
session_start();

// Ensure the user is logged in as a professor
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'professor') {
    header("Location: login.php?message=Please log in as a professor to access this page");
    exit();
}

require 'db.php';

// Get the professor's ID from the session
$professor_id = $_SESSION['user_id'];

$success_message = "";
$error_message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $topic = $_POST['topic'] ?? '';
    $description = $_POST['description'] ?? '';
    $pdf_path = null;

    // Handle PDF upload
    if (isset($_FILES['pdf']) && $_FILES['pdf']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = 'uploads/';
        $file_name = basename($_FILES['pdf']['name']);
        $pdf_path = $upload_dir . uniqid() . '_' . $file_name;

        // Move the uploaded file
        if (!move_uploaded_file($_FILES['pdf']['tmp_name'], $pdf_path)) {
            $error_message = "Failed to upload the PDF file.";
        }
    }

    // Insert the thesis into the `diplomatiki` table
    $sql = "INSERT INTO diplomatiki (topic, description, pdf, prof_num, state) VALUES (?, ?, ?, ?, 'under assignment')";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssi", $topic, $description, $pdf_path, $professor_id);

    if ($stmt->execute()) {
        // Get the ID of the newly inserted thesis
        $id_diplomatikis = $stmt->insert_id;

        // Insert into the `commitee` table
        $commitee_sql = "INSERT INTO commitee (id_diplomatikis, supervisor) VALUES (?, ?)";
        $commitee_stmt = $conn->prepare($commitee_sql);
        $commitee_stmt->bind_param("ii", $id_diplomatikis, $professor_id);

        if ($commitee_stmt->execute()) {
            $success_message = "Thesis registered successfully.";
        } else {
            $error_message = "Thesis added, but failed to update the committee: " . $commitee_stmt->error;
        }

        $commitee_stmt->close();
    } else {
        $error_message = "Failed to register thesis: " . $stmt->error;
    }

    $stmt->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Topic Registration</title>
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
            max-width: 500px;
            background: rgba(255, 255, 255, 0.9);
            padding: 20px 30px;
            border-radius: 15px;
            box-shadow: 0 8px 15px rgba(0, 0, 0, 0.2);
        }
        h2 {
            text-align: center;
            color: #800020;
        }
        form {
            display: flex;
            flex-direction: column;
        }
        label {
            margin-top: 10px;
        }
        input, textarea {
            padding: 8px;
            margin-top: 5px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }
        button {
            margin-top: 20px;
            padding: 10px;
            background-color: #007BFF;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        button:hover {
            background-color: #0056b3;
        }
        .success {
            background-color: #d4edda;
            color: #155724;
            padding: 10px;
            border-radius: 5px;
            margin-top: 10px;
        }
        .error {
            background-color: #f8d7da;
            color: #721c24;
            padding: 10px;
            border-radius: 5px;
            margin-top: 10px;
        }
        .back-button {
            margin-top: 10px;
            padding: 8px 15px;
            background-color: #800020;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        .back-button:hover {
            background-color: #660017;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Topic Registration</h2>

        <!-- Display success or error messages -->
        <?php if ($success_message): ?>
            <div class="success"><?php echo $success_message; ?></div>
        <?php elseif ($error_message): ?>
            <div class="error"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <form action="topic_registration.php" method="POST" enctype="multipart/form-data">
            <label for="topic">Topic:</label>
            <input type="text" id="topic" name="topic" required>

            <label for="description">Description:</label>
            <textarea id="description" name="description" rows="4" required></textarea>

            <label for="pdf">Upload PDF (optional):</label>
            <input type="file" id="pdf" name="pdf" accept=".pdf">

            <button type="submit">Register Topic</button>
        </form>
        <button class="back-button" onclick="location.href='professor_interface.php'">Back</button>
    </div>
</body>
</html>


