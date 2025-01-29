<?php
session_start();

// Ensure the user is logged in as a professor
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'professor') {
    header("Location: login.php?message=Please log in as a professor to access this page");
    exit();
}

require 'db.php';
// Assuming the logged-in user's ID is stored in the session
$user_id = $_SESSION['user_id'];

// Fetch the professor's name and surname from the database
$query = "SELECT name, surname FROM professors WHERE id = '$user_id'";
$result = $conn->query($query);

if ($result && $result->num_rows > 0) {
    $professor = $result->fetch_assoc();
    $full_name = htmlspecialchars($professor['name'] . ' ' . $professor['surname']);
} else {
    $full_name = "Professor"; // Fallback if no name is found
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Professor Interface</title>
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
            background-color: #800020; /* Bordeaux color */
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
            margin: 10px 10px;
        }
        button:hover {
            background-color: #0056b3;
        }
        .link-button {
            display: inline-block;
            font-size: 1rem;
            padding: 10px 20px;
            background-color: #800020; /* Bordeaux color */
            color: white;
            border-radius: 5px;
            text-decoration: none;
            transition: background-color 0.3s ease;
        }
        .link-button:hover {
            background-color: #660017;
        }
    </style>
</head>
<body>
    <div class="header-container">
        <div class="header">Professor Interface</div>
    </div>
    <div class="container">
        <h2>Welcome, <?php echo $full_name; ?></h2>
        <p>Select an action below:</p>
        <button onclick="location.href='topic_assignment.php'">Topic Assignment</button>
        <button onclick="location.href='topics_created.php'">Topics Created</button>
        <button onclick="location.href='topic_registration.php'">Topic Registration</button>
        <button onclick="location.href='thesis_list2.php'">Thesis List</button> <!-- Thesis List Button -->
		<button onclick="location.href='committee_invitation.php'">Committee Invitations</button> <!-- Committee Invitations -->
        <button onclick="location.href='statistics.php'">Statistics</button> <!-- Statistics -->
        <br><br>
        <a href="logout.php" class="link-button">Logout</a>
    </div>
</body>
</html>

