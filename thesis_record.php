<?php
session_start();

// Ensure the user is logged in as a student
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'student') {
    header("Location: login.php?message=Please log in as a student to access this page");
    exit();
}

require 'db.php';

// Get the logged-in student's thesis ID
$user_id = $_SESSION['user_id'];
$query = "SELECT d.id_diplomatikis 
          FROM diplomatiki d 
          JOIN students s ON d.stud_num = s.student_number 
          WHERE s.id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("No thesis found for the logged-in student.");
}

$thesis = $result->fetch_assoc();
$thesis_id = $thesis['id_diplomatikis'];

// Fetch full thesis details
$query = "
    SELECT d.topic, d.description, DATE(d.active_date) AS active_date, DATE(d.finished_date) AS finished_date, 
           s.name AS student_name, s.surname AS student_surname, s.student_number,
           c.supervisor, c.num_prof2, c.num_prof3,
           sf.examination_date, p.grade, p.link AS nimertis_link
    FROM diplomatiki d
    LEFT JOIN students s ON d.stud_num = s.student_number
    LEFT JOIN commitee c ON d.id_diplomatikis = c.id_diplomatikis
    LEFT JOIN support_files sf ON d.id_diplomatikis = sf.id_diplomatikis
    LEFT JOIN praktiko p ON d.id_diplomatikis = p.id_diplomatikis
    WHERE d.id_diplomatikis = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $thesis_id);
$stmt->execute();
$result = $stmt->get_result();
$thesis_details = $result->fetch_assoc();

// Fetch committee member names
$committee_members = [];
$professor_roles = ['Supervisor' => 'supervisor', 'Committee Member 2' => 'num_prof2', 'Committee Member 3' => 'num_prof3'];

foreach ($professor_roles as $role => $column) {
    if (!is_null($thesis_details[$column])) {
        $prof_query = "SELECT name, surname FROM professors WHERE id = ?";
        $stmt = $conn->prepare($prof_query);
        $stmt->bind_param("i", $thesis_details[$column]);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $committee_members[$role] = $row['name'] . ' ' . $row['surname'];
        }
    }
}

// Handle form submission for adding the NIMERTIS link
// Handle form submission for adding/updating the NIMERTIS link
$success_message = "";
$error_message = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nimertis_link'])) {
    $new_nimertis_link = trim($_POST['nimertis_link']);

    if (!empty($new_nimertis_link)) {
        // Update the existing row in the praktiko table for this thesis ID
        $update_query = "UPDATE praktiko SET link = ? WHERE id_diplomatikis = ?";
        $stmt = $conn->prepare($update_query);
        $stmt->bind_param("si", $new_nimertis_link, $thesis_id);
        
        if ($stmt->execute() && $stmt->affected_rows > 0) {
            $thesis_details['nimertis_link'] = $new_nimertis_link;
            $success_message = "NIMERTIS link successfully updated!";
        } else {
            $error_message = "Failed to update the NIMERTIS link. Ensure a record exists for this thesis.";
        }
    } else {
        $error_message = "Please enter a valid link.";
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
    <title>Thesis Record</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f5f5dc;
            color: #333;
            margin: 0;
            padding: 20px;
            text-align: center;
        }
        .container {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 8px 15px rgba(0, 0, 0, 0.2);
            display: inline-block;
            text-align: left;
            max-width: 700px;
        }
        h2 {
            color: #800020;
            text-align: center;
            font-size: 1.8rem;
        }
        .message {
            padding: 10px;
            border-radius: 5px;
            margin-top: 10px;
            text-align: center;
        }
        .success {
            background-color: #d4edda;
            color: #155724;
        }
        .error {
            background-color: #f8d7da;
            color: #721c24;
        }
        input[type="text"] {
            width: 100%;
            padding: 8px;
            margin-top: 5px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }
        button {
            margin-top: 15px;
            padding: 10px;
            background-color: #007BFF;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            width: 100%;
            font-size: 1rem;
        }
        button:hover {
            background-color: #0056b3;
        }
        .back-button {
            background-color: #800020;
        }
        .back-button:hover {
            background-color: #660017;
        }
        .info-box {
            background-color: #fff;
            padding: 15px;
            border-radius: 8px;
            margin: 10px 0;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        ul {
            padding: 0;
            list-style: none;
        }
        ul li {
            margin: 5px 0;
        }
        .nimertis-box {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-top: 20px;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Thesis Record</h2>

        <?php if (!empty($success_message)): ?>
            <div class="message success"><?php echo $success_message; ?></div>
        <?php elseif (!empty($error_message)): ?>
            <div class="message error"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <div class="info-box">
            <p><strong>Student:</strong> <?php echo htmlspecialchars($thesis_details['student_name'] . ' ' . $thesis_details['student_surname']); ?> (<?php echo htmlspecialchars($thesis_details['student_number']); ?>)</p>
            <p><strong>Topic:</strong> <?php echo htmlspecialchars($thesis_details['topic']); ?></p>
            <p><strong>Description:</strong> <?php echo htmlspecialchars($thesis_details['description']); ?></p>
            <p><strong>Start Date:</strong> <?php echo htmlspecialchars($thesis_details['active_date']); ?></p>
            <p><strong>Finished Date:</strong> <?php echo htmlspecialchars($thesis_details['finished_date']); ?></p>
            <p><strong>Presentation Date:</strong> <?php echo htmlspecialchars($thesis_details['examination_date']); ?></p>
        </div>

        <div class="info-box">
            <h3>Committee Members</h3>
            <ul>
                <?php foreach ($committee_members as $role => $name): ?>
                    <li><strong><?php echo htmlspecialchars($role); ?>:</strong> <?php echo htmlspecialchars($name); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>

        <div class="nimertis-box">
    <p><strong>NIMERTIS Link:</strong> 
        <?php if (empty($thesis_details['nimertis_link'])): ?>
            <form method="POST">
                <input type="text" name="nimertis_link" required placeholder="Insert NIMERTIS link here">
                <button type="submit">Submit</button>
            </form>
        <?php else: ?>
            <a href="<?php echo htmlspecialchars($thesis_details['nimertis_link']); ?>" target="_blank">
                <?php echo htmlspecialchars($thesis_details['nimertis_link']); ?>
            </a>
        <?php endif; ?>
    </p>
</div>


        <button class="back-button" onclick="location.href='student_interface.php'">Back</button>
    </div>
</body>
</html>
