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

$success_message = "";
$error_message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_diplomatikis = $_POST['id_diplomatikis'];
    $action = $_POST['action'];

    if ($action === 'accept') {
        // Check if there's space in the committee
        $check_sql = "SELECT num_prof2, num_prof3 FROM commitee WHERE id_diplomatikis = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("i", $id_diplomatikis);
        $check_stmt->execute();
        $result = $check_stmt->get_result();

        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            if (is_null($row['num_prof2'])) {
                $update_sql = "UPDATE commitee SET num_prof2 = ? WHERE id_diplomatikis = ?";
                $update_stmt = $conn->prepare($update_sql);
                $update_stmt->bind_param("ii", $professor_id, $id_diplomatikis);
                if ($update_stmt->execute()) {
                    $success_message = "Invitation accepted successfully.";
                } else {
                    $error_message = "Failed to accept the invitation: " . $update_stmt->error;
                }
            } elseif (is_null($row['num_prof3'])) {
                $update_sql = "UPDATE commitee SET num_prof3 = ? WHERE id_diplomatikis = ?";
                $update_stmt = $conn->prepare($update_sql);
                $update_stmt->bind_param("ii", $professor_id, $id_diplomatikis);
                if ($update_stmt->execute()) {
                    $success_message = "Invitation accepted successfully.";
                } else {
                    $error_message = "Failed to accept the invitation: " . $update_stmt->error;
                }
            } else {
                $error_message = "Committee is complete.";
            }

            // After updating, check if both num_prof2 and num_prof3 are not NULL
            $check_committee_sql = "SELECT num_prof2, num_prof3 FROM commitee WHERE id_diplomatikis = ?";
            $check_committee_stmt = $conn->prepare($check_committee_sql);
            $check_committee_stmt->bind_param("i", $id_diplomatikis);
            $check_committee_stmt->execute();
            $committee_result = $check_committee_stmt->get_result();

            if ($committee_result && $committee_result->num_rows > 0) {
                $committee_row = $committee_result->fetch_assoc();
                if (!is_null($committee_row['num_prof2']) && !is_null($committee_row['num_prof3'])) {
                    // Update the state of the diplomatiki table to 'active'
                    $update_diplomatiki_sql = "UPDATE diplomatiki SET state = 'active', active_date = NOW() WHERE id_diplomatikis = ?";
                    $update_diplomatiki_stmt = $conn->prepare($update_diplomatiki_sql);
                    $update_diplomatiki_stmt->bind_param("i", $id_diplomatikis);
                    if ($update_diplomatiki_stmt->execute()) {
                        $success_message .= " Committee is now active.";
                    } else {
                        $error_message = "Failed to update the state of the topic: " . $update_diplomatiki_stmt->error;
                    }
                }
            }

            $check_committee_stmt->close();
        }

        $check_stmt->close();
    } elseif ($action === 'reject') {
        // Handle rejection (e.g., delete the invitation or mark it as rejected)
        $delete_sql = "DELETE FROM commitee_app WHERE id_diplomatikis = ? AND prof_num_app = ?";
        $delete_stmt = $conn->prepare($delete_sql);
        $delete_stmt->bind_param("ii", $id_diplomatikis, $professor_id);
        if ($delete_stmt->execute()) {
            $success_message = "Invitation rejected successfully.";
        } else {
            $error_message = "Failed to reject the invitation: " . $delete_stmt->error;
        }
        $delete_stmt->close();
    }
}

// Fetch committee invitations for the professor
$sql = "SELECT ca.id_diplomatikis, d.topic, d.description FROM commitee_app ca JOIN diplomatiki d ON ca.id_diplomatikis = d.id_diplomatikis WHERE ca.prof_num_app = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $professor_id);
$stmt->execute();
$invitations = $stmt->get_result();
$stmt->close();
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Committee Invitations</title>
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
        <h2>Committee Invitations</h2>

        <!-- Display success or error messages -->
        <?php if ($success_message): ?>
            <div class="success"><?php echo $success_message; ?></div>
        <?php elseif ($error_message): ?>
            <div class="error"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <form action="committee_invitation.php" method="POST">
            <?php if ($invitations->num_rows > 0): ?>
                <?php while ($invitation = $invitations->fetch_assoc()): ?>
                    <div>
                        <h4>Topic: <?php echo htmlspecialchars($invitation['topic']); ?></h4>
                        <p>Description: <?php echo htmlspecialchars($invitation['description']); ?></p>
                        <input type="hidden" name="id_diplomatikis" value="<?php echo $invitation['id_diplomatikis']; ?>">
                        <button type="submit" name="action" value="accept">Accept</button>
                        <button type="submit" name="action" value="reject">Reject</button>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p>No invitations found.</p>
            <?php endif; ?>
        </form>
        <button class="back-button" onclick="location.href='professor_interface.php'">Back</button>
    </div>
</body>
</html>
