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

// Fetch theses under assignment supervised by the professor
$thesis_query = "
    SELECT d.id_diplomatikis, d.topic, d.description, d.state, s.name AS student_name, s.surname AS student_surname, s.student_number
    FROM diplomatiki d
    LEFT JOIN students s ON d.stud_num = s.student_number
    WHERE d.prof_num = ? AND d.state = 'under assignment'
";
$stmt = $conn->prepare($thesis_query);
$stmt->bind_param("i", $professor_id);
$stmt->execute();
$theses = $stmt->get_result();

// Handle cancellation of a thesis assignment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_thesis_id'])) {
    $thesis_id = intval($_POST['cancel_thesis_id']);

    // Delete committee invitations and assignments
    $delete_committee_sql = "DELETE FROM commitee_app WHERE id_diplomatikis = '$thesis_id'";
    $delete_committee_stmt = $conn->prepare($delete_committee_sql);
    $delete_committee_stmt->bind_param("i", $thesis_id);
    $delete_committee_stmt->execute();

    $delete_committee_sql = "DELETE FROM commitee WHERE id_diplomatikis = '$thesis_id'";
    $delete_committee_stmt = $conn->prepare($delete_committee_sql);
    $delete_committee_stmt->bind_param("i", $thesis_id);
    $delete_committee_stmt->execute();

    // Set the thesis as unassigned
    $update_thesis_sql = "UPDATE diplomatiki SET stud_num = NULL, state = 'under assignment' WHERE id_diplomatikis = ?";
    $update_thesis_stmt = $conn->prepare($update_thesis_sql);
    $update_thesis_stmt->bind_param("i", $thesis_id);

    if ($update_thesis_stmt->execute()) {
        $success_message = "Thesis assignment canceled successfully.";
    } else {
        $error_message = "Failed to cancel the thesis assignment.";
    }

    $delete_committee_stmt->close();
    $update_thesis_stmt->close();
}

$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Thesis</title>
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
        }
        .thesis-item {
            margin-bottom: 20px;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 8px;
            background-color: #f9f9f9;
        }
        .button-group {
            margin-top: 10px;
        }
        button {
            padding: 10px 15px;
            background-color: #007BFF;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s ease;
            margin-right: 10px;
        }
        button:hover {
            background-color: #0056b3;
        }
        .cancel-button {
            background-color: #dc3545;
        }
        .cancel-button:hover {
            background-color: #c82333;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Manage Thesis</h2>

        <?php if (!empty($success_message)): ?>
            <p style="color: green;"><?php echo $success_message; ?></p>
        <?php elseif (!empty($error_message)): ?>
            <p style="color: red;"><?php echo $error_message; ?></p>
        <?php endif; ?>

        <?php if ($theses->num_rows > 0): ?>
            <?php while ($thesis = $theses->fetch_assoc()): ?>
                <div class="thesis-item">
                    <h4><?php echo htmlspecialchars($thesis['topic']); ?></h4>
                    <p><?php echo htmlspecialchars($thesis['description']); ?></p>
                    <p><strong>Student:</strong> <?php echo htmlspecialchars($thesis['student_name'] . ' ' . $thesis['student_surname']); ?> (<?php echo htmlspecialchars($thesis['student_number']); ?>)</p>

                    <!-- Fetch committee details -->
                    <ul>
                        <?php
                        $committee_query = "SELECT ca.prof_num_app, p.name, p.surname, ca.invitation_date, ca.acceptance_date, ca.rejection_date
                                            FROM commitee_app ca
                                            JOIN professors p ON ca.prof_num_app = p.id
                                            WHERE ca.id_diplomatikis = '$thesis_id'";
                        $committee_stmt = $conn->prepare($committee_query);
                        $committee_stmt->bind_param("i", $thesis['id_diplomatikis']);
                        $committee_stmt->execute();
                        $committee_result = $committee_stmt->get_result();

                        if ($committee_result->num_rows > 0):
                            while ($committee_member = $committee_result->fetch_assoc()):
                        ?>
                            <li>
                                <strong><?php echo htmlspecialchars($committee_member['name'] . ' ' . $committee_member['surname']); ?></strong>
                                - Invited on: <?php echo htmlspecialchars($committee_member['invitation_date']); ?>
                                <?php if ($committee_member['acceptance_date']): ?>
                                    - Accepted on: <?php echo htmlspecialchars($committee_member['acceptance_date']); ?>
                                <?php elseif ($committee_member['rejection_date']): ?>
                                    - Rejected on: <?php echo htmlspecialchars($committee_member['rejection_date']); ?>
                                <?php endif; ?>
                            </li>
                        <?php
                            endwhile;
                        endif;

                        $committee_stmt->close();
                        ?>
                    </ul>

                    <div class="button-group">
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="cancel_thesis_id" value="<?php echo $thesis['id_diplomatikis']; ?>">
                            <button type="submit" class="cancel-button">Cancel Assignment</button>
                        </form>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p>No theses under assignment found.</p>
        <?php endif; ?>
    </div>
</body>
</html>
