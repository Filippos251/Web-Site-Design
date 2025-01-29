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
$success_message = "";
$error_message = "";

// Fetch thesis details
$query = "
    SELECT d.topic, d.state, d.active_date, c.supervisor
    FROM diplomatiki d
    LEFT JOIN commitee c ON d.id_diplomatikis = c.id_diplomatikis
    WHERE d.id_diplomatikis = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $thesis_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result && $result->num_rows > 0) {
    $thesis = $result->fetch_assoc();
} else {
    die("Thesis not found.");
}

// Ensure the logged-in professor is the supervisor
if ($thesis['supervisor'] != $professor_id) {
    die("You do not have permission to cancel this thesis.");
}

// Check if the thesis can be canceled
$can_cancel = false;
if ($thesis['state'] === 'under assignment') {
    $can_cancel = true;
} elseif ($thesis['state'] === 'active' && isset($thesis['active_date'])) {
    $active_date = new DateTime($thesis['active_date']);
    $current_date = new DateTime();
    $interval = $current_date->diff($active_date);
    if ($interval->y >= 2) {
        $can_cancel = true;
    }
}

// Handle cancellation form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_cancel'])) {
    if ($can_cancel) {
        $protocol_number = $_POST['protocol_number'];
        $meeting_date = $_POST['meeting_date'];
        $reasoning = $_POST['reasoning'];

        $conn->begin_transaction();

        try {
            // Insert meeting details into general_meetings
            $insert_meeting_sql = "INSERT INTO general_meetings (protocol_number, general_meeting_date, reasoning) VALUES (?, ?, ?)";
            $insert_meeting_stmt = $conn->prepare($insert_meeting_sql);
            $insert_meeting_stmt->bind_param("iss", $protocol_number, $meeting_date, $reasoning);
            $insert_meeting_stmt->execute();

            // Delete invitations from commitee_app
            $delete_invitations_sql = "DELETE FROM commitee_app WHERE id_diplomatikis = ?";
            $delete_invitations_stmt = $conn->prepare($delete_invitations_sql);
            $delete_invitations_stmt->bind_param("i", $thesis_id);
            $delete_invitations_stmt->execute();

            // Clear committee members (except supervisor)
            $clear_committee_sql = "UPDATE commitee SET num_prof2 = NULL, num_prof3 = NULL WHERE id_diplomatikis = ?";
            $clear_committee_stmt = $conn->prepare($clear_committee_sql);
            $clear_committee_stmt->bind_param("i", $thesis_id);
            $clear_committee_stmt->execute();

            // Remove student assignment from diplomatiki
            $clear_student_sql = "UPDATE diplomatiki SET stud_num = NULL WHERE id_diplomatikis = ?";
            $clear_student_stmt = $conn->prepare($clear_student_sql);
            $clear_student_stmt->bind_param("i", $thesis_id);
            $clear_student_stmt->execute();

            $conn->commit();
            $success_message = "The assignment has been successfully canceled and recorded.";
            header("Location: thesis_details.php?id=$thesis_id&message=cancel_success");
            exit();
        } catch (Exception $e) {
            $conn->rollback();
            $error_message = "Failed to cancel the assignment: " . $e->getMessage();
        }
    } else {
        $error_message = "You do not have permission to cancel this thesis.";
    }
}

$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cancel Thesis Assignment</title>
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
            max-width: 600px;
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
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        input, textarea {
            width: 80%;
            padding: 8px;
            margin-top: 5px;
            border: 1px solid #ccc;
            border-radius: 4px;
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
        .back-button {
            margin-top: 20px;
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
        .message {
            margin-top: 10px;
            padding: 10px;
            border-radius: 5px;
        }
        .success {
            background-color: #d4edda;
            color: #155724;
        }
        .error {
            background-color: #f8d7da;
            color: #721c24;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Cancel Thesis Assignment</h2>
        <p><strong>Thesis Topic:</strong> <?php echo htmlspecialchars($thesis['topic']); ?></p>

        <?php if ($success_message): ?>
            <p class="message success"><?php echo $success_message; ?></p>
        <?php elseif ($error_message): ?>
            <p class="message error"><?php echo $error_message; ?></p>
        <?php else: ?>
            <form method="POST">
                <label>Protocol Number:</label>
                <input type="number" name="protocol_number" required>

                <label>General Meeting Date:</label>
                <input type="date" name="meeting_date" required>

                <label>Reasoning:</label>
                <textarea name="reasoning" rows="4" required></textarea>

                <button type="submit" name="confirm_cancel">Confirm Cancellation</button>
            </form>
        <?php endif; ?>

        <button class="back-button" onclick="location.href='thesis_details.php?id=<?php echo $thesis_id; ?>'">Back</button>
    </div>
</body>
</html>
