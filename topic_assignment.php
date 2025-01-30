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

$error_message = "";
$success_message = "";
$students = [];
$already_assigned_message = "";

// Fetch available theses created by the professor
$query = "SELECT id_diplomatikis, topic FROM diplomatiki WHERE prof_num = ? AND stud_num IS NULL";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $professor_id);
$stmt->execute();
$result = $stmt->get_result();

$theses = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $theses[] = $row;
    }
}
$stmt->close();

// Handle search and assignment
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $search_input = $_POST['search_input'] ?? '';
    $thesis_id = $_POST['thesis_id'] ?? '';

    // If a student and thesis are selected for assignment
    if ($thesis_id && isset($_POST['student_number'])) {
        $student_number = $_POST['student_number'];

        // Update diplomatiki table
        $assign_query = "UPDATE diplomatiki SET stud_num = ? WHERE id_diplomatikis = ?";
        $stmt = $conn->prepare($assign_query);
        $stmt->bind_param("ii", $student_number, $thesis_id);

        if ($stmt->execute()) {
            $success_message = "Thesis assigned successfully!";
            $students = []; // Clear students to prevent re-rendering
        } else {
            $error_message = "Failed to assign thesis: " . $stmt->error;
        }

        $stmt->close();
    } else {
        // Search for students
        $student_query = "SELECT student_number, name, surname FROM students WHERE student_number = ? OR CONCAT(name, ' ', surname) LIKE ?";
        $stmt = $conn->prepare($student_query);
        $like_input = '%' . $search_input . '%';
        $stmt->bind_param("is", $search_input, $like_input);
        $stmt->execute();
        $students_result = $stmt->get_result();

        if ($students_result) {
            while ($row = $students_result->fetch_assoc()) {
                // Check if the student already has an assigned thesis
                $assigned_check_query = "SELECT id_diplomatikis FROM diplomatiki WHERE stud_num = ?";
                $check_stmt = $conn->prepare($assigned_check_query);
                $check_stmt->bind_param("i", $row['student_number']);
                $check_stmt->execute();
                $check_result = $check_stmt->get_result();

                if ($check_result->num_rows > 0) {
                    // Student already has an assigned thesis
                    $already_assigned_message = "Student " . htmlspecialchars($row['name'] . ' ' . $row['surname']) . " has already been assigned a thesis.";
                } else {
                    // Add student to the list if not assigned
                    $students[] = $row;
                }

                $check_stmt->close();
            }
        }
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Topic Assignment</title>
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
            max-width: 800px;
            background: rgba(255, 255, 255, 0.9);
            padding: 20px 30px;
            border-radius: 15px;
            box-shadow: 0 8px 15px rgba(0, 0, 0, 0.2);
            text-align: center;
        }
        h2 {
            color: #800020;
        }
        form {
            margin: 20px 0;
        }
        input, select {
            padding: 8px;
            margin: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
            width: calc(100% - 22px);
            max-width: 500px;
        }
        button {
            margin-top: 10px;
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
        .success {
            background-color: #d4edda;
            color: #155724;
            padding: 10px;
            border-radius: 5px;
            margin-top: 10px;
        }
        .error, .already-assigned {
            background-color: #f8d7da;
            color: #721c24;
            padding: 10px;
            border-radius: 5px;
            margin-top: 10px;
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
        ul {
            list-style-type: none;
            padding: 0;
        }
        li {
            margin: 5px 0;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Assign Thesis</h2>

        <!-- Display success or error messages -->
        <?php if ($success_message): ?>
            <div class="success"><?php echo $success_message; ?></div>
        <?php elseif ($error_message): ?>
            <div class="error"><?php echo $error_message; ?></div>
        <?php elseif ($already_assigned_message): ?>
            <div class="already-assigned"><?php echo $already_assigned_message; ?></div>
        <?php endif; ?>

        <!-- Search Form -->
        <form method="POST" action="topic_assignment.php">
            <label for="search_input">Search Student (by Number or Full Name):</label>
            <input type="text" id="search_input" name="search_input" placeholder="Enter student number or name" required>
            <button type="submit">Search</button>
        </form>

        <!-- Display Search Results -->
        <?php if (!empty($students)): ?>
            <h3>Search Results:</h3>
            <ul>
                <?php foreach ($students as $student): ?>
                    <li>
                        <?php echo htmlspecialchars($student['name'] . ' ' . $student['surname']) . " (Number: " . $student['student_number'] . ")"; ?>
                        <form method="POST" style="margin-top: 10px;">
                            <input type="hidden" name="student_number" value="<?php echo $student['student_number']; ?>">
                            <label for="thesis_id">Select Thesis:</label>
                            <select name="thesis_id" required>
                                <option value="">-- Choose Thesis --</option>
                                <?php foreach ($theses as $thesis): ?>
                                    <option value="<?php echo $thesis['id_diplomatikis']; ?>">
                                        <?php echo htmlspecialchars($thesis['topic']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <button type="submit">Assign Thesis</button>
                        </form>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($success_message)): ?>
            <p>No students found.</p>
        <?php endif; ?>

        <button class="back-button" onclick="location.href='professor_interface.php'">Back</button>
    </div>
</body>
</html>
