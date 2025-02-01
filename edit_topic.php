<?php
session_start();

// Ensure the user is logged in as a professor
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'professor') {
    header("Location: login.php?message=Please log in as a professor to access this page");
    exit();
}

require 'db.php';

// Get the thesis ID from the URL
$thesis_id = $_GET['id'] ?? null;

if (!$thesis_id) {
    header("Location: topics_created.php?message=Invalid thesis ID");
    exit();
}

// Fetch thesis details
$query = "SELECT topic, description, pdf FROM diplomatiki WHERE id_diplomatikis = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $thesis_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: topics_created.php?message=Thesis not found");
    exit();
}

$thesis = $result->fetch_assoc();
$stmt->close();

$error_message = "";
$success_message = "";

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $topic = $_POST['topic'] ?? '';
    $description = $_POST['description'] ?? '';
    $remove_pdf = isset($_POST['remove_pdf']); // Check if the remove checkbox is ticked
    $pdf_path = $thesis['pdf']; // Default to the existing PDF path

    // Handle PDF upload if a new file is uploaded
    if (!$remove_pdf && isset($_FILES['pdf']) && $_FILES['pdf']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = 'uploads/';
        $file_name = basename($_FILES['pdf']['name']);
        $pdf_path = $upload_dir . uniqid() . '_' . $file_name;

        // Move the uploaded file
        if (!move_uploaded_file($_FILES['pdf']['tmp_name'], $pdf_path)) {
            $error_message = "Failed to upload the new PDF file.";
        }
    }

    // Remove the existing PDF if requested
    if ($remove_pdf) {
        if (file_exists($thesis['pdf'])) {
            unlink($thesis['pdf']); // Delete the file from the server
        }
        $pdf_path = null; // Set PDF path to null
    }

    // Update the thesis
    if (empty($error_message)) {
        $update_query = "UPDATE diplomatiki SET topic = ?, description = ?, pdf = ? WHERE id_diplomatikis = ?";
        $stmt = $conn->prepare($update_query);
        $stmt->bind_param("sssi", $topic, $description, $pdf_path, $thesis_id);

        if ($stmt->execute()) {
            $success_message = "Thesis updated successfully.";
            $thesis['pdf'] = $pdf_path; // Update the PDF path in the current data
        } else {
            $error_message = "Failed to update thesis: " . $stmt->error;
        }

        $stmt->close();
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Thesis</title>
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
            height: 100vh;
        }
        .container {
            width: 90%;
            max-width: 400px;
            background: rgba(255, 255, 255, 0.9);
            padding: 20px;
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
        .back-button {
            margin-top: 10px;
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
    </style>
</head>
<body>
    <div class="container">
        <h2>Edit Thesis</h2>
        <?php if ($success_message): ?>
            <div class="success"><?php echo $success_message; ?></div>
        <?php elseif ($error_message): ?>
            <div class="error"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <form action="edit_topic.php?id=<?php echo $thesis_id; ?>" method="POST" enctype="multipart/form-data">
            <label for="topic">Topic:</label>
            <input type="text" id="topic" name="topic" value="<?php echo htmlspecialchars($thesis['topic']); ?>" required>

            <label for="description">Description:</label>
            <textarea id="description" name="description" rows="4" required><?php echo htmlspecialchars($thesis['description']); ?></textarea>

            <?php if ($thesis['pdf']): ?>
                <p>A PDF file has already been uploaded.</p>
                <p><a href="<?php echo htmlspecialchars($thesis['pdf']); ?>" target="_blank">View PDF</a></p>
                <label>
                    <input type="checkbox" name="remove_pdf"> Remove the existing PDF
                </label>
            <?php else: ?>
                <p>No PDF file uploaded yet.</p>
            <?php endif; ?>

            <label for="pdf">Upload New PDF:</label>
            <input type="file" id="pdf" name="pdf" accept=".pdf">

            <button type="submit">Update</button>
        </form>
        <button class="back-button" onclick="location.href='topics_created.php'">Back</button>
    </div>
</body>
</html>
