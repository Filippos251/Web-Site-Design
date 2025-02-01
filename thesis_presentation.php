<?php
session_start();

// Ensure the user is logged in as a student
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'student') {
    header("Location: login.php?message=Please log in as a student to access this page");
    exit();
}

require 'db.php';

// Get the logged-in student's ID
$user_id = $_SESSION['user_id'];

// Fetch the student's thesis ID
$query = "SELECT id_diplomatikis FROM students 
          JOIN diplomatiki ON students.student_number = diplomatiki.stud_num 
          WHERE students.id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $student = $result->fetch_assoc();
    $id_diplomatikis = $student['id_diplomatikis'];
} else {
    die("No thesis found for the logged-in student.");
}

$stmt->close();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $examination_date = $_POST['examination_date'];
    $presentation = $_POST['presentation'];
    $room = ($presentation === 'live') ? $_POST['room'] : NULL;
    $remote_room = ($presentation === 'remote') ? $_POST['remote_room'] : NULL;

    // Update the support_files table
    $update_query = "UPDATE support_files 
                     SET examination_date = ?, presentation = ?, room = ?, remote_room = ? 
                     WHERE id_diplomatikis = ?";
    $stmt = $conn->prepare($update_query);
    $stmt->bind_param("ssssi", $examination_date, $presentation, $room, $remote_room, $id_diplomatikis);

    if ($stmt->execute()) {
        $success_message = "Presentation details updated successfully!";
    } else {
        $error_message = "Error updating presentation details.";
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
    <title>Thesis Presentation</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f5f5dc;
            margin: 0;
            padding: 20px;
            text-align: center;
        }
        .container {
            background: rgba(255, 255, 255, 0.9);
            padding: 20px 30px;
            border-radius: 15px;
            box-shadow: 0 8px 15px rgba(0, 0, 0, 0.2);
            display: inline-block;
            text-align: left;
            max-width: 500px;
        }
        h2 {
            color: #800020;
            text-align: center;
        }
        label {
            display: block;
            margin-top: 10px;
            font-weight: bold;
        }
        input, select {
            width: 100%;
            padding: 8px;
            margin-top: 5px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }
        .hidden {
            display: none;
        }
        .submit-btn {
            margin-top: 20px;
            padding: 10px;
            background-color: #007BFF;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        .submit-btn:hover {
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
            display: block;
            width: 100%;
            text-align: center;
        }
        .back-button:hover {
            background-color: #660017;
        }
    </style>
    <script>
        function toggleFields() {
            var presentationType = document.getElementById("presentation").value;
            document.getElementById("live-fields").style.display = (presentationType === "live") ? "block" : "none";
            document.getElementById("remote-fields").style.display = (presentationType === "remote") ? "block" : "none";
        }
    </script>
</head>
<body>

<div class="container">
    <h2>Thesis Presentation</h2>

    <?php if (isset($success_message)): ?>
        <p style="color: green;"><?php echo $success_message; ?></p>
    <?php elseif (isset($error_message)): ?>
        <p style="color: red;"><?php echo $error_message; ?></p>
    <?php endif; ?>

    <form method="POST">
        <label for="examination_date">Presentation Date & Time:</label>
        <input type="datetime-local" id="examination_date" name="examination_date" required>

        <label for="presentation">Presentation Type:</label>
        <select id="presentation" name="presentation" onchange="toggleFields()" required>
            <option value="UNKNOWN" selected disabled>Select Presentation Type</option>
            <option value="live">Live</option>
            <option value="remote">Remote</option>
        </select>

        <div id="live-fields" class="hidden">
            <label for="room">Room Number:</label>
            <input type="text" id="room" name="room">
        </div>

        <div id="remote-fields" class="hidden">
            <label for="remote_room">Remote Room Link:</label>
            <input type="text" id="remote_room" name="remote_room">
        </div>

        <button type="submit" class="submit-btn">Submit</button>
        <button type="button" class="back-button" onclick="location.href='manage_thesis.php'">Back</button>
    </form>
</div>

</body>
</html>
