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

// Retrieve filters from the query parameters
$filter_role = $_GET['filter_role'] ?? 'supervisor'; // Default to supervisor
$filter_state = $_GET['filter_state'] ?? 'all'; // Default to show all states

$theses = [];

// Build the query based on the selected filters
$query = "
    SELECT d.id_diplomatikis, d.topic, d.state
    FROM diplomatiki d
    INNER JOIN commitee c ON d.id_diplomatikis = c.id_diplomatikis
    WHERE 1=1
";

if ($filter_role === 'supervisor') {
    $query .= " AND c.supervisor = ?";
} elseif ($filter_role === 'committee') {
    $query .= " AND (c.num_prof2 = ? OR c.num_prof3 = ?)";
}

if ($filter_state !== 'all') {
    $query .= " AND d.state = ?";
}

// Prepare the statement
$stmt = $conn->prepare($query);

if ($filter_role === 'supervisor') {
    if ($filter_state === 'all') {
        $stmt->bind_param("i", $professor_id);
    } else {
        $stmt->bind_param("is", $professor_id, $filter_state);
    }
} elseif ($filter_role === 'committee') {
    if ($filter_state === 'all') {
        $stmt->bind_param("ii", $professor_id, $professor_id);
    } else {
        $stmt->bind_param("iis", $professor_id, $professor_id, $filter_state);
    }
}

$stmt->execute();
$result = $stmt->get_result();

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $theses[] = $row;
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
    <title>Thesis List</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f5f5dc;
            color: #333;
            margin: 0;
            padding: 0;
            display: flex;
            flex-direction: column;
            align-items: center;
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
        form {
            margin-bottom: 20px;
            text-align: center;
        }
        select, button {
            padding: 10px;
            margin: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
            cursor: pointer;
        }
        ul {
            list-style-type: none;
            padding: 0;
        }
        li {
            padding: 10px;
            background-color: #fff;
            margin: 5px 0;
            border-radius: 5px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            cursor: pointer;
        }
        li:hover {
            background-color: #f8f9fa;
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
    </style>
</head>
<body>
    <div class="container">
        <h2>Thesis List</h2>

        <!-- Filter Form -->
        <form method="GET" action="thesis_list2.php">
            <label for="filter_role">Filter by Role:</label>
            <select name="filter_role" id="filter_role" onchange="this.form.submit()">
                <option value="supervisor" <?php echo $filter_role === 'supervisor' ? 'selected' : ''; ?>>As Supervisor</option>
                <option value="committee" <?php echo $filter_role === 'committee' ? 'selected' : ''; ?>>As Committee Member</option>
            </select>

            <label for="filter_state">Filter by State:</label>
            <select name="filter_state" id="filter_state" onchange="this.form.submit()">
                <option value="all" <?php echo $filter_state === 'all' ? 'selected' : ''; ?>>All States</option>
                <option value="active" <?php echo $filter_state === 'active' ? 'selected' : ''; ?>>Active</option>
                <option value="under assignment" <?php echo $filter_state === 'under assignment' ? 'selected' : ''; ?>>Under Assignment</option>
                <option value="under examination" <?php echo $filter_state === 'under examination' ? 'selected' : ''; ?>>Under Examination</option>
                <option value="finished" <?php echo $filter_state === 'finished' ? 'selected' : ''; ?>>Finished</option>
                <option value="canceled" <?php echo $filter_state === 'canceled' ? 'selected' : ''; ?>>Canceled</option>
            </select>
        </form>

        <!-- Thesis List -->
        <?php if (!empty($theses)): ?>
            <ul>
                <?php foreach ($theses as $thesis): ?>
                    <li onclick="location.href='thesis_details.php?id=<?php echo $thesis['id_diplomatikis']; ?>'">
                        <strong>Thesis ID:</strong> <?php echo htmlspecialchars($thesis['id_diplomatikis']); ?><br>
                        <strong>Topic:</strong> <?php echo htmlspecialchars($thesis['topic']); ?><br>
                        <strong>State:</strong> <?php echo htmlspecialchars($thesis['state']); ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p>No theses found for the selected filter.</p>
        <?php endif; ?>

        <!-- Back Button -->
        <button class="back-button" onclick="location.href='professor_interface.php'">Back</button>
    </div>
</body>
</html>
