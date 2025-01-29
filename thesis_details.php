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

// Handle cancel assignment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_assignment'])) {
    // Verify the professor is the supervisor
    $check_supervisor_sql = "SELECT state FROM diplomatiki WHERE id_diplomatikis = ? AND state = 'under assignment'";
    $check_stmt = $conn->prepare($check_supervisor_sql);
    $check_stmt->bind_param("i", $thesis_id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();

    if ($result && $result->num_rows > 0) {
        // Proceed with cancelation
        $conn->begin_transaction(); // Start transaction

        try {
            // Delete invitations from commitee_app
            $delete_invitations_sql = "DELETE FROM commitee_app WHERE id_diplomatikis = ?";
            $delete_invitations_stmt = $conn->prepare($delete_invitations_sql);
            $delete_invitations_stmt->bind_param("i", $thesis_id);
            $delete_invitations_stmt->execute();

            // Clear committee members from commitee
            $clear_committee_sql = "UPDATE commitee SET supervisor = NULL, num_prof2 = NULL, num_prof3 = NULL WHERE id_diplomatikis = ?";
            $clear_committee_stmt = $conn->prepare($clear_committee_sql);
            $clear_committee_stmt->bind_param("i", $thesis_id);
            $clear_committee_stmt->execute();

            // Remove the student assignment from the diplomatiki table
            $clear_student_sql = "UPDATE diplomatiki SET stud_num = NULL WHERE id_diplomatikis = ?";
            $clear_student_stmt = $conn->prepare($clear_student_sql);
            $clear_student_stmt->bind_param("i", $thesis_id);
            $clear_student_stmt->execute();

            $conn->commit(); // Commit the transaction
            $success_message = "The assignment has been successfully canceled.";
        } catch (Exception $e) {
            $conn->rollback(); // Rollback the transaction in case of error
            $error_message = "Failed to cancel the assignment: " . $e->getMessage();
        }
    } else {
        $error_message = "You can only cancel assignments for theses under assignment where you are the supervisor.";
    }

    $check_stmt->close();
}

// Fetch thesis details
$query = "
    SELECT d.topic, d.description, d.state, s.name AS student_name, s.surname AS student_surname, s.student_number,
           c.supervisor, c.num_prof2, c.num_prof3
    FROM diplomatiki d
    LEFT JOIN students s ON d.stud_num = s.student_number
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

// Fetch professor names for the committee
$committee_members = [];
if ($thesis['supervisor']) {
    $supervisor_query = "SELECT name, surname FROM professors WHERE id = ?";
    $stmt = $conn->prepare($supervisor_query);
    $stmt->bind_param("i", $thesis['supervisor']);
    $stmt->execute();
    $result = $stmt->get_result();
    $supervisor = $result->fetch_assoc();
    $committee_members['Supervisor'] = $supervisor['name'] . ' ' . $supervisor['surname'];
}
if ($thesis['num_prof2']) {
    $prof2_query = "SELECT name, surname FROM professors WHERE id = ?";
    $stmt = $conn->prepare($prof2_query);
    $stmt->bind_param("i", $thesis['num_prof2']);
    $stmt->execute();
    $result = $stmt->get_result();
    $prof2 = $result->fetch_assoc();
    $committee_members['Committee Member 2'] = $prof2['name'] . ' ' . $prof2['surname'];
}
if ($thesis['num_prof3']) {
    $prof3_query = "SELECT name, surname FROM professors WHERE id = ?";
    $stmt = $conn->prepare($prof3_query);
    $stmt->bind_param("i", $thesis['num_prof3']);
    $stmt->execute();
    $result = $stmt->get_result();
    $prof3 = $result->fetch_assoc();
    $committee_members['Committee Member 3'] = $prof3['name'] . ' ' . $prof3['surname'];
}

// Fetch committee invitations if the thesis is under assignment
$invitations = [];
if ($thesis['state'] === 'under assignment') {
    $invitation_query = "
        SELECT p.name, p.surname, ca.invitation_date, ca.acceptance_date, ca.rejection_date
        FROM commitee_app ca
        JOIN professors p ON ca.prof_num_app = p.id
        WHERE ca.id_diplomatikis = ?";
    $stmt = $conn->prepare($invitation_query);
    $stmt->bind_param("i", $thesis_id);
    $stmt->execute();
    $invitation_result = $stmt->get_result();

    while ($row = $invitation_result->fetch_assoc()) {
        $invitations[] = $row;
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
    <title>Thesis Details</title>
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
            margin-top: 20px;
        }
        h2 {
            color: #800020;
            text-align: center;
        }
        p {
            margin: 10px 0;
        }
        .back-button, .cancel-button {
            margin-top: 20px;
            padding: 10px;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        .back-button {
            background-color: #800020;
        }
        .back-button:hover {
            background-color: #660017;
        }
        .cancel-button {
            background-color: #dc3545;
        }
        .cancel-button:hover {
            background-color: #c82333;
        }
        .committee {
            margin-top: 20px;
        }
        .committee h3 {
            color: #800020;
        }
        .invitation table {
            width: 100%;
            border-collapse: collapse;
        }
        .invitation th, .invitation td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        .invitation th {
            background-color: #800020;
            color: white;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Thesis Details</h2>

        <?php if ($success_message): ?>
            <p style="color: green;"><?php echo $success_message; ?></p>
        <?php elseif ($error_message): ?>
            <p style="color: red;"><?php echo $error_message; ?></p>
        <?php endif; ?>

        <p><strong>Thesis ID:</strong> <?php echo htmlspecialchars($thesis_id); ?></p>
        <p><strong>Topic:</strong> <?php echo htmlspecialchars($thesis['topic']); ?></p>
        <p><strong>Description:</strong> <?php echo htmlspecialchars($thesis['description']); ?></p>
        <p><strong>State:</strong> <?php echo htmlspecialchars($thesis['state']); ?></p>

        <?php if ($thesis['student_name'] && $thesis['student_surname']): ?>
            <p><strong>Student:</strong> <?php echo htmlspecialchars($thesis['student_name'] . ' ' . $thesis['student_surname']); ?> (<?php echo htmlspecialchars($thesis['student_number']); ?>)</p>
            
            <?php if ($thesis['state'] === 'under assignment' && $thesis['supervisor'] == $professor_id): ?>
                <form method="POST">
                    <button type="submit" name="cancel_assignment" class="cancel-button">Cancel Assignment</button>
                </form>
            <?php endif; ?>
        <?php else: ?>
            <p><strong>Student:</strong> Not assigned yet.</p>
        <?php endif; ?>

        <div class="committee">
            <h3>Committee Members:</h3>
            <?php if (!empty($committee_members)): ?>
                <ul>
                    <?php foreach ($committee_members as $role => $name): ?>
                        <li><strong><?php echo htmlspecialchars($role); ?>:</strong> <?php echo htmlspecialchars($name); ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p>No committee members assigned yet.</p>
            <?php endif; ?>
        </div>

        <?php if ($thesis['state'] === 'under assignment'): ?>
            <div class="invitation">
                <h3>Invited Professors:</h3>
                <?php if (!empty($invitations)): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Professor</th>
                                <th>Invitation Date</th>
                                <th>Acceptance Date</th>
                                <th>Rejection Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($invitations as $invitation): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($invitation['name'] . ' ' . $invitation['surname']); ?></td>
                                    <td><?php echo htmlspecialchars($invitation['invitation_date']); ?></td>
                                    <td><?php echo htmlspecialchars($invitation['acceptance_date'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($invitation['rejection_date'] ?? 'N/A'); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p>No professors have been invited yet.</p>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <button class="back-button" onclick="location.href='thesis_list2.php'">Back</button>
    </div>
</body>
</html>