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

// Fetch committee details and grades
$query = "
    SELECT c.supervisor, c.num_prof2, c.num_prof3, c.supervisor_grade, c.grade2, c.grade3,
           p1.name AS supervisor_name, p1.surname AS supervisor_surname,
           p2.name AS prof2_name, p2.surname AS prof2_surname,
           p3.name AS prof3_name, p3.surname AS prof3_surname
    FROM commitee c
    LEFT JOIN professors p1 ON c.supervisor = p1.id
    LEFT JOIN professors p2 ON c.num_prof2 = p2.id
    LEFT JOIN professors p3 ON c.num_prof3 = p3.id
    WHERE c.id_diplomatikis = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $thesis_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result && $result->num_rows > 0) {
    $committee = $result->fetch_assoc();
} else {
    die("Committee details not found for the specified thesis.");
}

$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registered Grades</title>
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
            max-width: 600px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            padding: 10px;
            border: 1px solid #ddd;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
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
    <h1>Registered Grades</h1>
    <div class="container">
        <table>
            <tr>
                <th>Name</th>
                <th>Role</th>
                <th>Grade</th>
            </tr>
            <tr>
                <td><?php echo htmlspecialchars($committee['supervisor_name'] . ' ' . $committee['supervisor_surname']); ?></td>
                <td>Supervisor</td>
                <td><?php echo htmlspecialchars($committee['supervisor_grade']); ?></td>
            </tr>
            <tr>
                <td><?php echo htmlspecialchars($committee['prof2_name'] . ' ' . $committee['prof2_surname']); ?></td>
                <td>Committee Member</td>
                <td><?php echo htmlspecialchars($committee['grade2']); ?></td>
            </tr>
            <tr>
                <td><?php echo htmlspecialchars($committee['prof3_name'] . ' ' . $committee['prof3_surname']); ?></td>
                <td>Committee Member</td>
                <td><?php echo htmlspecialchars($committee['grade3']); ?></td>
            </tr>
        </table>
        <br>
        <button onclick="location.href='thesis_details.php?id=<?php echo $thesis_id; ?>'">Back</button>
    </div>
</body>
</html>