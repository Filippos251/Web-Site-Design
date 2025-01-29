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
$professor_id = $_SESSION['user_id']; // Get the logged-in professor's ID
$success_message = "";
$error_message = "";

// Fetch thesis details to ensure the user is the supervisor
$query = "
    SELECT d.state, d.active_date, c.supervisor
    FROM diplomatiki d
    LEFT JOIN commitee c ON d.id_diplomatikis = c.id_diplomatikis
    WHERE d.id_diplomatikis = ? AND c.supervisor = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $thesis_id, $professor_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result && $result->num_rows > 0) {
    $thesis = $result->fetch_assoc();
    // Check if the assignment can be cancelled (active and more than 2 years old)
    $can_cancel = false;
    if ($thesis['state'] === 'active' && isset($thesis['active_date'])) {
        $active_date = new DateTime($thesis['active_date']);
        $current_date = new DateTime();
        $interval = $current_date->diff($active_date);
        if ($interval->y >= 2) {
            $can_cancel = true;
        }
    }
} else {
    die("You are not authorized to cancel this assignment or the thesis details are invalid.");
}

$stmt->close();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $protocol_number = isset($_POST['protocol_number']) ? $_POST['protocol_number'] : '';
    $general_meeting_date = isset($_POST['general_meeting_date']) ? $_POST['general_meeting_date'] : '';
    $reasoning = isset($_POST['reasoning']) ? $_POST['reasoning'] : '';

    // Validate inputs
    if (empty($protocol_number) || empty($general_meeting_date)) {
        $error_message = "Please provide both protocol number and date of General Meeting.";
    } else {
        // Proceed with cancelation
        $conn->begin_transaction(); // Start transaction

        try {
            // Insert protocol number and date of General Meeting into the general_mettings table
            $insert_protocol_sql = "INSERT INTO general_meetings (protocol_number, general_meeting_date, reasoning) VALUES (?, ?, ?)";
            $insert_protocol_stmt = $conn->prepare($insert_protocol_sql);
            $insert_protocol_stmt->bind_param("iss", $protocol_number, $general_meeting_date, $reasoning);
            $insert_protocol_stmt->execute();

            // Clear committee members from commitee
            $clear_committee_sql = "UPDATE commitee SET num_prof2 = NULL, num_prof3 = NULL WHERE id_diplomatikis = ?";
            $clear_committee_stmt = $conn->prepare($clear_committee_sql);
            $clear_committee_stmt->bind_param("i", $thesis_id);
            $clear_committee_stmt->execute();

            // Remove the student assignment from the diplomatiki table and update state to 'cancelled'
            $clear_student_sql = "UPDATE diplomatiki SET stud_num = NULL, state = 'cancelled' WHERE id_diplomatikis = ?";
            $clear_student_stmt = $conn->prepare($clear_student_sql);
            $clear_student_stmt->bind_param("i", $thesis_id);
            $clear_student_stmt->execute();

            $conn->commit(); // Commit the transaction
            $success_message = "The assignment has been successfully canceled.";
        } catch (Exception $e) {
            $conn->rollback(); // Rollback the transaction in case of error
            $error_message = "Failed to cancel the assignment: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cancel Assignment</title>
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
            background-color: #dc3545;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        button:hover {
            background-color: #c82333;
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
        <h2>Cancel Assignment</h2>

        <!-- Display success or error messages -->
        <?php if ($success_message): ?>
            <div class="success"><?php echo $success_message; ?></div>
        <?php elseif ($error_message): ?>
            <div class="error"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <form action="cancel_assignment_interface.php?id=<?php echo $thesis_id; ?>" method="POST">
            <label for="protocol_number">Protocol Number:</label>
            <input type="number" id="protocol_number" name="protocol_number" required>

            <label for="general_meeting_date">Date of General Meeting:</label>
            <input type="date" id="general_meeting_date" name="general_meeting_date" required>

            <label for="reasoning">Reasoning:</label>
            <textarea id="reasoning" name="reasoning"></textarea>

            <button type="submit">Submit</button>
        </form>
    </div>
</body>
</html>
