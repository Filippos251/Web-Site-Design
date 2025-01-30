<?php
session_start();

if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'student') {
    header("Location: login.php?message=Please log in as a student to access this page");
    exit();
}

require 'db.php';

// Assuming the logged-in user's ID is stored in the session
$user_id = $_SESSION['user_id'];

// Fetch the student's name and surname from the database
$query = "SELECT name, surname FROM students WHERE id = '$user_id'";
$result = $conn->query($query);

if ($result && $result->num_rows > 0) {
    $student = $result->fetch_assoc();
    $full_name = htmlspecialchars($student['name'] . ' ' . $student['surname']);
} else {
    $full_name = "Student"; // Fallback if the name cannot be retrieved
}

// Check the thesis state
$thesis_query = "
    SELECT id_diplomatikis, state 
    FROM diplomatiki 
    WHERE stud_num = (
        SELECT student_number 
        FROM students 
        WHERE id = '$user_id'
    )";
$thesis_result = $conn->query($thesis_query);

$can_request_committee = false;
$manage_thesis = false;
$id_diplomatikis = null;

if ($thesis_result && $thesis_result->num_rows > 0) {
    $thesis = $thesis_result->fetch_assoc();
    $id_diplomatikis = $thesis['id_diplomatikis'];
    if ($thesis['state'] === 'under assignment') {
        $can_request_committee = true;
    }
    if ($thesis['state'] === 'under examination') {
        $manage_thesis = true;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Interface</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f5f5dc; /* Subtle beige color */
            color: #333; /* Darker text for better contrast */
            margin: 0;
            padding: 0;
            height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: flex-start;
            align-items: center;
        }
        .header-container {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            background-color: #006400; /* Dark green color */
            color: white;
            padding: 10px 20px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }
        .header-container .header {
            font-size: 2rem;
            font-weight: bold;
        }
        .container {
            margin-top: 80px;
            text-align: center;
            background: rgba(255, 255, 255, 0.9);
            padding: 20px 40px;
            border-radius: 15px;
            box-shadow: 0 8px 15px rgba(0, 0, 0, 0.2);
            width: 90%;
            max-width: 600px;
        }
        button {
            font-size: 1rem;
            padding: 10px 20px;
            background-color: #007BFF;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s ease;
            margin: 10px 0;
        }
        button:hover {
            background-color: #0056b3;
        }
        .link-button {
            display: inline-block;
            font-size: 1rem;
            padding: 10px 20px;
            background-color: #006400;
            color: white;
            border-radius: 5px;
            text-decoration: none;
            transition: background-color 0.3s ease;
        }
        .link-button:hover {
            background-color: #004d00;
        }
    </style>
</head>
<body>
    <div class="header-container">
        <div class="header">Student</div>
    </div>
    <div class="container">
        <h2>Welcome, <?php echo $full_name; ?></h2>
        <p>Select an action below:</p>
        <button onclick="location.href='edit_profile.php'">Edit Profile</button>
        <button onclick="location.href='my_thesis.php'">My Thesis</button>
        <?php if ($can_request_committee): ?>
            <button onclick="location.href='request_committee.php?id_diplomatikis=<?php echo $id_diplomatikis; ?>'">Request Committee</button>
        <?php endif; ?>
        <?php if ($manage_thesis): ?>
            <button onclick="location.href='manage_thesis.php?id_diplomatikis=<?php echo $id_diplomatikis; ?>'">Manage Thesis</button>
        <?php endif; ?>
        <a href="logout.php" class="link-button">Logout</a>
    </div>
</body>
</html>
