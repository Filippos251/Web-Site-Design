<?php
session_start();

// Ensure the user is logged in as a professor and is a supervisor
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'professor') {
    header("Location: login.php?message=Please log in as a professor to access this page");
    exit();
}

require 'db.php';

$thesis_id = intval($_GET['id']);

// Check if the user is the supervisor of the thesis
$professor_id = $_SESSION['user_id'];
$query = "
    SELECT d.state, c.supervisor
    FROM diplomatiki d
    JOIN commitee c ON d.id_diplomatikis = c.id_diplomatikis
    JOIN support_files sf ON d.id_diplomatikis = sf.id_diplomatikis
    WHERE d.id_diplomatikis = ? AND c.supervisor = ? AND sf.path_of_files IS NOT NULL";
$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $thesis_id, $professor_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("You do not have permission to access this page or the conditions are not met.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['announcement_text'])) {
    $announcement_text = $_POST['announcement_text'];
    
    $update_query = "UPDATE diplomatiki SET announcement_text = ? WHERE id_diplomatikis = ?";
    $update_stmt = $conn->prepare($update_query);
    $update_stmt->bind_param("si", $announcement_text, $thesis_id);

    if ($update_stmt->execute()) {
        $success_message = "Announcement text successfully submitted!";
    } else {
        $error_message = "Failed to submit the announcement text.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Submit Announcement Text</title>
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
        button:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>
    <h1>Submit Announcement Text</h1>
    <div class="container">
        <?php if (!empty($success_message)): ?>
            <p style="color: green;"><?php echo $success_message; ?></p>
        <?php elseif (!empty($error_message)): ?>
            <p style="color: red;"><?php echo $error_message; ?></p>
        <?php endif; ?>
        <form method="POST">
            <label for="announcement_text">Announcement Text:</label><br><br>
            <textarea name="announcement_text" id="announcement_text" rows="5" cols="40" required></textarea><br><br>
            <button type="submit">Submit</button>
        </form>
        <br>
        <button onclick="location.href='thesis_details.php?id=<?php echo $thesis_id; ?>'">Back</button>
    </div>
</body>
</html>