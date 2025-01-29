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
$professor_id = $_SESSION['user_id'];
$note_column = '';

// Determine which note column to update based on the professor's role
$query = "SELECT supervisor, num_prof2, num_prof3 FROM commitee WHERE id_diplomatikis = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $thesis_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    if ($row['supervisor'] == $professor_id) {
        $note_column = 'note1';
    } elseif ($row['num_prof2'] == $professor_id) {
        $note_column = 'note2';
    } elseif ($row['num_prof3'] == $professor_id) {
        $note_column = 'note3';
    } else {
        die("You are not a member of the committee for this thesis.");
    }
} else {
    die("Committee not found for this thesis.");
}

$stmt->close();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $note = $_POST['note'];
    $update_query = "UPDATE commitee SET $note_column = ? WHERE id_diplomatikis = ?";
    $update_stmt = $conn->prepare($update_query);
    $update_stmt->bind_param("si", $note, $thesis_id);
    if ($update_stmt->execute()) {
        $success_message = "Note updated successfully.";
    } else {
        $error_message = "Failed to update the note: " . $update_stmt->error;
    }
    $update_stmt->close();
}

// Fetch existing note
$fetch_note_query = "SELECT $note_column FROM commitee WHERE id_diplomatikis = ?";
$fetch_note_stmt = $conn->prepare($fetch_note_query);
$fetch_note_stmt->bind_param("i", $thesis_id);
$fetch_note_stmt->execute();
$note_result = $fetch_note_stmt->get_result();
$current_note = "";
if ($note_result && $note_result->num_rows > 0) {
    $note_row = $note_result->fetch_assoc();
    $current_note = htmlspecialchars($note_row[$note_column]);
}
$fetch_note_stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thesis Notes</title>
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
        textarea {
            padding: 8px;
            margin-top: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
            resize: vertical;
            min-height: 100px;
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
        <h2>Thesis Notes</h2>

        <!-- Display success or error messages -->
        <?php if (isset($success_message)): ?>
            <div class="success"><?php echo $success_message; ?></div>
        <?php elseif (isset($error_message)): ?>
            <div class="error"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <form action="thesis_notes.php?id=<?php echo $thesis_id; ?>" method="POST">
            <textarea name="note" placeholder="Enter your note here"><?php echo $current_note; ?></textarea>
            <button type="submit">Save Note</button>
        </form>
        <button class="back-button" onclick="location.href='thesis_details.php?id=<?php echo $thesis_id; ?>'">Back</button>
    </div>
</body>
</html>