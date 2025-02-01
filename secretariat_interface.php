<?php
session_start();

if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'secretariat') {
    header("Location: login.php?message=Please log in as secretariat to access this page");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Secretariat Interface</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f5f5dc;
            color: #333;
            margin: 0;
            padding: 0;
            height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }
        .header-container {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            background-color: #800020;
            color: white;
            padding: 10px 20px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }
        .header-container .header {
            font-size: 2rem;
            font-weight: bold;
        }
        .container {
            text-align: center;
            background: rgba(255, 255, 255, 0.9);
            padding: 20px 40px;
            border-radius: 15px;
            box-shadow: 0 8px 15px rgba(0, 0, 0, 0.2);
        }
        button {
            font-size: 1rem;
            padding: 10px 20px;
            background-color: #007BFF;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
        button:hover {
            background-color: #0056b3;
        }
        /* Modal styling */
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
        }
        .modal-content {
            background: white;
            color: black;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            box-shadow: 0 8px 15px rgba(0, 0, 0, 0.3);
        }
        .modal-content input[type="file"] {
            margin: 10px 0;
        }
        .modal-content button {
            margin: 10px;
        }
    </style>
</head>
<body>
    <div class="header-container">
        <div class="header">Secretariat</div>
    </div>
    <div class="container">
        <button onclick="openModal()">Import Users</button>
        <button onclick="location.href='thesis_list.php'">Thesis List</button>
        <button onclick="location.href='logout.php'">Logout</button>
    </div>

    <!-- Modal -->
    <div class="modal" id="userModal">
        <div class="modal-content">
            <h2>Import Users</h2>
            <form action="import_users.php" method="POST" enctype="multipart/form-data">
                <label for="jsonFile">Upload JSON File:</label>
                <input type="file" id="jsonFile" name="jsonFile" accept=".json, .php" required>
                <div>
                    <button type="submit">Import</button>
                    <button type="button" onclick="closeModal()">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Open modal
        function openModal() {
            document.getElementById('userModal').style.display = 'flex';
        }
        // Close modal
        function closeModal() {
            document.getElementById('userModal').style.display = 'none';
        }
    </script>
</body>
</html>

