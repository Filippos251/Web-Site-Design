<?php
session_start();

if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'student') {
    header("Location: login.php?message=Please log in as a student to access this page");
    exit();
}

require 'db.php';

// Get the logged-in user's ID
$user_id = $_SESSION['user_id'];

// Retrieve the student's student_number
$student_query = "SELECT student_number FROM students WHERE id = '$user_id'";
$student_result = $conn->query($student_query);

if ($student_result && $student_result->num_rows > 0) {
    $student = $student_result->fetch_assoc();
    $student_number = $student['student_number'];

    // Retrieve the thesis information for the student
    $thesis_query = "SELECT id_diplomatikis, topic, description, state FROM diplomatiki WHERE stud_num = '$student_number'";
    $thesis_result = $conn->query($thesis_query);

    if ($thesis_result && $thesis_result->num_rows > 0) {
        $thesis = $thesis_result->fetch_assoc();

        // Get the committee members for the thesis
        $id_diplomatikis = $thesis['id_diplomatikis'];
        $committee_query = "
            SELECT 
                (SELECT CONCAT(name, ' ', surname) FROM professors WHERE id = c.num_prof1) AS prof1,
                (SELECT CONCAT(name, ' ', surname) FROM professors WHERE id = c.num_prof2) AS prof2,
                (SELECT CONCAT(name, ' ', surname) FROM professors WHERE id = c.num_prof3) AS prof3
            FROM commitee c
            WHERE c.id_diplomatikis = '$id_diplomatikis'
        ";
        $committee_result = $conn->query($committee_query);

        $committee_members = [];
        if ($committee_result && $committee_result->num_rows > 0) {
            $row = $committee_result->fetch_assoc();
            if ($row['prof1']) $committee_members[] = htmlspecialchars($row['prof1']);
            if ($row['prof2']) $committee_members[] = htmlspecialchars($row['prof2']);
            if ($row['prof3']) $committee_members[] = htmlspecialchars($row['prof3']);
        } else {
            $committee_members[] = "No committee members found.";
        }
    } else {
        $error_message = "No thesis found for your student number.";
    }
} else {
    $error_message = "Student number not found.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Thesis</title>
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
            max-width: 400px; /* Narrower width */
            margin-bottom: 20px; /* Space for the back button */
        }
        h2 {
            color: #800020;
        }
        p {
            margin: 10px 0;
        }
        ul {
            padding-left: 20px;
            margin: 10px 0;
        }
        .back-button-container {
            display: flex;
            justify-content: center;
            margin-top: 20px;
        }
        .back-button {
            padding: 10px 20px;
            background-color: #007BFF;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
        .back-button:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>
    <h1>My Thesis</h1>
    <?php if (isset($thesis)): ?>
        <div class="container">
            <h2>Topic: <?php echo htmlspecialchars($thesis['topic']); ?></h2>
            <p><strong>Description:</strong> <?php echo htmlspecialchars($thesis['description']); ?></p>
            <p><strong>State:</strong> <?php echo htmlspecialchars($thesis['state']); ?></p>
            <h3>Committee Members</h3>
            <ul>
                <?php foreach ($committee_members as $member): ?>
                    <li><?php echo $member; ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php elseif (isset($error_message)): ?>
        <p><?php echo htmlspecialchars($error_message); ?></p>
    <?php endif; ?>
    <div class="back-button-container">
        <button class="back-button" onclick="location.href='student_interface.php'">Back</button>
    </div>
</body>
</html>


