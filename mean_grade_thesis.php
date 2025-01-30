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

// Fetch mean grade for topics
$sql = "SELECT 
            AVG(p.grade) AS mean_grade
        FROM 
            praktiko p
        JOIN 
            commitee c ON p.id_diplomatikis = c.id_diplomatikis
        WHERE 
            c.supervisor = ? OR c.num_prof2 = ? OR c.num_prof3 = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("iii", $professor_id, $professor_id, $professor_id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$mean_grade = $row['mean_grade'];

$stmt->close();
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mean Grade of Thesis</title>
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
        <h2>Mean Grade of Thesis</h2>
        <div>
            <canvas id="meanGradeChart"></canvas>
            <p>Mean Grade: <?php echo round($mean_grade, 2); ?></p>
        </div>
        <button class="back-button" onclick="location.href='statistics.php'">Back</button>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        const ctx = document.getElementById('meanGradeChart').getContext('2d');
        const meanGradeChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: ['Mean Grade'],
                datasets: [{
                    label: 'Grade',
                    data: [<?php echo round($mean_grade, 2); ?>],
                    backgroundColor: ['rgba(0, 123, 255, 0.5)'],
                    borderColor: ['rgba(0, 123, 255, 1)'],
                    borderWidth: 1
                }]
            },
            options: {
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    </script>
</body>
</html>