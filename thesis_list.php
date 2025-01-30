<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'secretariat') {
    header("Location: login.php?message=Please log in as secretariat to access this page");
    exit();
}

// Fetch theses
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
            background-color: #f5f5dc;
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
            color: #800020;
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
            padding: 15px;
            border-radius: 10px;
            text-align: center;
            max-width: 500px;
            width: 90%;
            box-shadow: 0 8px 15px rgba(0, 0, 0, 0.3);
        }
        .close-btn {
            background-color: #800020;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
        }
        .close-btn:hover {
            background-color: #660017;
        }
        .add-protocol-btn {
            background-color: #007BFF;
            color: white;
            border: none;
            padding: 8px 15px;
            margin-top: 10px;
            cursor: pointer;
            border-radius: 5px;
        }
        .add-protocol-btn:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>
    <div class="thesis-list">
        <h2>Thesis List</h2>
        <?php if ($result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()): ?>
                <div class="thesis-item" onclick="showDescription('<?php echo addslashes($row['id_diplomatikis']); ?>', '<?php echo addslashes($row['topic']); ?>', '<?php echo addslashes($row['description']); ?>', '<?php echo addslashes($row['state']); ?>')">
                    <h3>Thesis <?php echo $row['id_diplomatikis']; ?></h3>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p>No theses found with the specified states.</p>
        <?php endif; ?>
    </div>

    <!-- Modal for Thesis Description -->
    <div class="modal" id="descriptionModal">
        <div class="modal-content">
            <h3 id="modalTopic"></h3>
            <p id="modalDescription"></p>
            <p><strong>State:</strong> <span id="modalState"></span></p>
            <button id="protocolButton" class="add-protocol-btn" onclick="openProtocolModal()" style="display: none;">Add Protocol Number</button>
            <button id="cancelAssignationButton" class="add-protocol-btn" onclick="openCancelAssignationModal()" style="display: none;">Cancel Assignation</button>
            <div style="text-align: center; margin-top: 15px;">
                <button class="close-btn" onclick="closeModal()">Close</button>
            </div>
        </div>
    </div>

    <!-- Modal for Adding Protocol Number -->
    <div class="modal" id="protocolModal">
        <div class="modal-content">
            <h3>Add Protocol Number</h3>
            <form action="add_protocol.php" method="POST">
                <input type="hidden" id="protocolThesisId" name="id_diplomatikis">
                <label for="protocol_number">Protocol Number:</label>
                <input type="number" id="protocol_number" name="protocol_number" required>
                <button type="submit">Submit</button>
            </form>
            <div style="text-align: center; margin-top: 15px;">
                <button class="close-btn" onclick="closeProtocolModal()">Close</button>
            </div>
        </div>
    </div>

    <!-- Modal for Canceling Assignation -->
    <div class="modal" id="cancelAssignationModal">
        <div class="modal-content">
            <h3>Cancel Assignation</h3>
            <form action="cancel_assignation.php" method="POST">
                <input type="hidden" id="cancelThesisId" name="id_diplomatikis">
                <label for="protocol_number">Protocol Number:</label>
                <input type="number" id="cancel_protocol_number" name="protocol_number" required>
                <label for="meeting_date">Meeting Date:</label>
                <input type="date" id="meeting_date" name="meeting_date" required>
                <button type="submit">Submit</button>
            </form>
            <div style="text-align: center; margin-top: 15px;">
                <button class="close-btn" onclick="closeCancelAssignationModal()">Close</button>
            </div>
        </div>
    </div>

    <script>
        function showDescription(id, topic, description, state) {
            document.getElementById('modalTopic').textContent = topic;
            document.getElementById('modalDescription').textContent = description;
            document.getElementById('modalState').textContent = state.charAt(0).toUpperCase() + state.slice(1);
            document.getElementById('descriptionModal').style.display = 'flex';

            if (state === 'active') {
                document.getElementById('protocolButton').style.display = 'block';
                document.getElementById('cancelAssignationButton').style.display = 'block';
                document.getElementById('protocolThesisId').value = id;
                document.getElementById('cancelThesisId').value = id;
            } else {
                document.getElementById('protocolButton').style.display = 'none';
                document.getElementById('cancelAssignationButton').style.display = 'none';
            }
        }

        function openProtocolModal() {
            document.getElementById('protocolModal').style.display = 'flex';
        }

        function closeProtocolModal() {
            document.getElementById('protocolModal').style.display = 'none';
        }

        function openCancelAssignationModal() {
            document.getElementById('cancelAssignationModal').style.display = 'flex';
        }

        function closeCancelAssignationModal() {
            document.getElementById('cancelAssignationModal').style.display = 'none';
        }

        function closeModal() {
            document.getElementById('descriptionModal').style.display = 'none';
        }
    </script>
</body>
</html>