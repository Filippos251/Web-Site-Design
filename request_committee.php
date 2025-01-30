<?php
session_start();

if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'student') {
    header("Location: login.php?message=Please log in as a student to access this page");
    exit();
}

require 'db.php';

// Get the logged-in user's ID
$user_id = $_SESSION['user_id'];

// Fetch the student's student_number, thesis ID, and supervising professor
$student_query = "SELECT students.student_number, diplomatiki.id_diplomatikis, diplomatiki.prof_num 
                  FROM students 
                  JOIN diplomatiki ON students.student_number = diplomatiki.stud_num 
                  WHERE students.id = '$user_id'";
$student_result = $conn->query($student_query);

if ($student_result && $student_result->num_rows > 0) {
    $student = $student_result->fetch_assoc();
    $student_number = $student['student_number'];
    $id_diplomatikis = $student['id_diplomatikis'];
    $supervising_professor = $student['prof_num']; // Get the supervising professor's ID
} else {
    die("No thesis found for the logged-in student.");
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['professor_ids'])) {
    $professor_ids = $_POST['professor_ids'];
    $current_date = date("Y-m-d H:i:s"); // Current date and time

    foreach ($professor_ids as $prof_id) {
        $insert_query = "INSERT INTO commitee_app (stud_num, id_diplomatikis, prof_num_app, invitation_date) 
                         VALUES ('$student_number', '$id_diplomatikis', '$prof_id', '$current_date')";
        $conn->query($insert_query);
    }
    $success_message = "Your requests were successfully submitted!";
}

// Fetch all professors, excluding already requested ones and the supervising professor
$professors_query = "
    SELECT p.id, p.name, p.surname
    FROM professors p
    WHERE p.id NOT IN (
        SELECT prof_num_app
        FROM commitee_app
        WHERE stud_num = '$student_number'
          AND id_diplomatikis = '$id_diplomatikis'
    )
    AND p.id != '$supervising_professor'"; // Exclude the supervising professor
$professors_result = $conn->query($professors_query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Request Committee</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f5f5dc;
            margin: 0;
            padding: 20px;
            text-align: center;
        }
        .form-container {
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
        }
        .professor-list {
            max-height: 300px;
            overflow-y: auto;
            margin-top: 10px;
            border: 1px solid #ddd;
            padding: 10px;
            border-radius: 5px;
            background-color: #f9f9f9;
        }
        .professor-item {
            margin-bottom: 10px;
        }
        button {
            padding: 10px 20px;
            background-color: #007BFF;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s ease;
            margin-top: 20px;
        }
        button:hover {
            background-color: #0056b3;
        }
        .back-button {
            background-color: #6c757d;
        }
        .back-button:hover {
            background-color: #5a6268;
        }
        .success-message {
            color: #006400;
            font-size: 1.2rem;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <h1>Request Committee</h1>
    <?php if (isset($success_message)): ?>
        <p class="success-message"><?php echo $success_message; ?></p>
        <button class="back-button" onclick="location.href='student_interface.php'">Back to Interface</button>
    <?php else: ?>
        <div class="form-container">
            <form method="POST">
                <h2>Select Professors</h2>
                <div class="professor-list">
                    <?php if ($professors_result && $professors_result->num_rows > 0): ?>
                        <?php while ($professor = $professors_result->fetch_assoc()): ?>
                            <div class="professor-item">
                                <label>
                                    <input type="checkbox" name="professor_ids[]" value="<?php echo $professor['id']; ?>">
                                    <?php echo htmlspecialchars($professor['name'] . ' ' . $professor['surname']); ?>
                                </label>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <p>No professors available for request.</p>
                    <?php endif; ?>
                </div>
                <button type="submit">Submit Request</button>
            </form>
        </div>
    <?php endif; ?>
</body>
</html>
