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
            background-color: #f5f5dc; /* Subtle beige color */
            color: #333;
            margin: 0;
            padding: 0;
            height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        .header-container {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            background-color: #800020; /* Bordeaux color */
            color: white;
            padding: 10px 20px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }
        .header-container .header {
            font-size: 2rem;
            font-weight: bold;
        }
        .container {
            margin-top: 80px;
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
        .modal-content button {
            background-color: #4CAF50;
            margin: 10px;
        }
        .modal-content button:hover {
            background-color: #45a049;
        }
        .link-button {
            display: inline-block;
            font-size: 1rem;
            padding: 10px 20px;
            margin-top: 20px;
            background-color: #800020;
            color: white;
            border-radius: 5px;
            text-decoration: none;
            transition: background-color 0.3s ease;
        }
        .link-button:hover {
            background-color: #660017;
        }
    </style>
</head>
<body>
    <div class="header-container">
        <div class="header">Secretariat</div>
    </div>
    <div class="container">
        <button onclick="openModal()">Import User</button>
        <a href="thesis_list.php" class="link-button">Thesis List</a>
        <a href="logout.php" class="link-button">Logout</a>
    </div>

    <!-- Modal -->
    <div class="modal" id="userModal">
        <div class="modal-content">
            <h2>Select User Type</h2>
            <button onclick="location.href='import_student.php'">Student</button>
            <button onclick="location.href='import_professor.php'">Professor</button>
            <button onclick="closeModal()">Cancel</button>
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
