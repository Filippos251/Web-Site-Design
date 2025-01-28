<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'secretariat') {
    header("Location: login.php?message=Please log in as secretariat to access this page");
    exit();
}

require 'db.php';

// Fetch thesis entries with the required states
$sql = "SELECT id_diplomatikis, topic, description, state FROM diplomatiki WHERE state IN ('active', 'under examination')";
$result = $conn->query($sql);
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
            background-color: #f5f5dc; /* Subtle beige color */
            color: #333;
            margin: 0;
            padding: 20px;
        }
        .thesis-list {
            text-align: center;
        }
        .thesis-item {
            background-color: #fff;
            margin: 10px auto;
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            width: 90%;
            max-width: 600px;
            cursor: pointer;
            transition: transform 0.2s ease;
        }
        .thesis-item:hover {
            transform: scale(1.02);
        }
        .thesis-item h3 {
            margin: 0;
            color: #800020; /* Bordeaux color */
        }
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }
        .modal-content {
            background: white;
            color: black;
            padding: 15px;
            border-radius: 10px;
            text-align: left;
            max-width: 500px;
            width: 90%;
            box-shadow: 0 8px 15px rgba(0, 0, 0, 0.3);
            font-size: 0.9rem; /* Smaller text */
            line-height: 1.5;
        }
        .modal-content h3 {
            margin-top: 0;
            font-size: 1.2rem; /* Smaller heading */
        }
        .close-btn {
            background-color: #800020;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            float: right;
        }
        .close-btn:hover {
            background-color: #660017;
        }
    </style>
</head>
<body>
    <div class="thesis-list">
        <h2>Thesis List</h2>
        <?php if ($result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()): ?>
                <div class="thesis-item" onclick="showDescription('<?php echo addslashes($row['topic']); ?>', '<?php echo addslashes($row['description']); ?>', '<?php echo addslashes($row['state']); ?>')">
                    <h3>Thesis <?php echo $row['id_diplomatikis']; ?></h3>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p>No theses found with the specified states.</p>
        <?php endif; ?>
    </div>

    <!-- Modal -->
    <div class="modal" id="descriptionModal">
        <div class="modal-content">
            <button class="close-btn" onclick="closeModal()">Close</button>
            <h3 id="modalTopic"></h3>
            <p id="modalDescription"></p>
            <p><strong>State:</strong> <span id="modalState"></span></p>
        </div>
    </div>

    <script>
        function showDescription(topic, description, state) {
            document.getElementById('modalTopic').textContent = topic;
            document.getElementById('modalDescription').textContent = description;
            document.getElementById('modalState').textContent = state.charAt(0).toUpperCase() + state.slice(1);
            document.getElementById('descriptionModal').style.display = 'flex';
        }

        function closeModal() {
            document.getElementById('descriptionModal').style.display = 'none';
        }
    </script>
</body>
</html>

