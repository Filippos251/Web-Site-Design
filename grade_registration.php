<?php
session_start();

// Ensure the user is logged in as a professor
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'professor') {
    header("Location: login.php?message=Please log in as a professor to access this page");
    exit();
}

require 'db.php';

$thesis_id = intval($_GET['id']);
$professor_id = $_SESSION['user_id'];

// Fetch the thesis details and check if the user is part of the committee
$query = "
    SELECT c.supervisor, c.num_prof2, c.num_prof3, c.grade_registration_state
    FROM commitee c
    JOIN diplomatiki d ON c.id_diplomatikis = d.id_diplomatikis
    WHERE c.id_diplomatikis = ? AND d.state = 'under examination' AND c.grade_registration_state = 'active' 
      AND (c.supervisor = ? OR c.num_prof2 = ? OR c.num_prof3 = ?)";
$stmt = $conn->prepare($query);
$stmt->bind_param("iiii", $thesis_id, $professor_id, $professor_id, $professor_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("You do not have permission to access this page or the conditions are not met.");
}

$committee = $result->fetch_assoc();
$role = ($committee['supervisor'] == $professor_id) ? 'supervisor' : (($committee['num_prof2'] == $professor_id) ? 'num_prof2' : 'num_prof3');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['grade'])) {
    $grade = intval($_POST['grade']);
    
    if ($grade < 0 || $grade > 10) {
        $error_message = "Grade must be between 0 and 10.";
    } else {
        $grade_column = ($role === 'supervisor') ? 'supervisor_grade' : (($role === 'num_prof2') ? 'grade2' : 'grade3');
        $update_query = "UPDATE commitee SET $grade_column = ? WHERE id_diplomatikis = ?";
        $update_stmt = $conn->prepare($update_query);
        $update_stmt->bind_param("ii", $grade, $thesis_id);

        if ($update_stmt->execute()) {
            if ($role === 'supervisor') {
                $praktiko_update_query = "UPDATE praktiko SET grade = ? WHERE id_diplomatikis = ?";
                $praktiko_stmt = $conn->prepare($praktiko_update_query);
                $praktiko_stmt->bind_param("ii", $grade, $thesis_id);
                $praktiko_stmt->execute();
            }
            $success_message = "Grade successfully submitted!";
        } else {
            $error_message = "Failed to submit the grade.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Grade Registration</title>
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
    <h1>Grade Registration</h1>
    <div class="container">
        <?php if (!empty($success_message)): ?>
            <p style="color: green;"><?php echo $success_message; ?></p>
        <?php elseif (!empty($error_message)): ?>
            <p style="color: red;"><?php echo $error_message; ?></p>
        <?php endif; ?>
        <form method="POST">
            <label for="grade">Grade (0-10):</label><br><br>
            <input type="number" name="grade" id="grade" min="0" max="10" required><br><br>
            <button type="submit">Submit</button>
        </form>
        <br>
        <button onclick="location.href='thesis_details.php?id=<?php echo $thesis_id; ?>'">Back</button>
    </div>
</body>
</html>