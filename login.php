<?php
session_start(); // Start the session to manage user login state

$error_message = ""; // Initialize error message


require_once "db.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $password = $_POST['password'];

    
    $query = "SELECT * FROM users WHERE email = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();

        // Check if the password matches
        if ($password === $row['password']) {
            // Store user information in the session
            $_SESSION['user_type'] = $row['role']; // Updated to use 'role'
            $_SESSION['email'] = $row['email'];

            // Redirect to the appropriate interface
            if ($row['role'] === "student") {
                $_SESSION['user_id'] = $row['id']; // Replace 'id' with the actual column name for the student's ID
    $_SESSION['user_type'] = 'student';
    header("Location: student_interface.php");
    exit();
            } elseif ($row['role'] === "professor") {
                header("Location: professor_interface.php");
            } elseif ($row['role'] === "secretariat") {
                header("Location: secretariat_interface.php");
            }
            exit();
        } else {
            $error_message = "Invalid email or password.";
        }
    } else {
        $error_message = "Invalid email or password.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <style>
        body {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            font-family: Arial, sans-serif;
            background-color: #f5f5dc; /* Subtle beige color */
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
            text-align: center;
        }
        .header-container .header {
            font-size: 1.5rem;
            font-weight: bold;
        }
        .login-container {
            text-align: center;
            background: #ffffff;
            padding: 20px 30px;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            max-width: 400px;
            width: 100%;
        }
        .login-container h1 {
            margin-bottom: 20px;
            font-size: 24px;
        }
        .error-message {
            color: red;
            margin-bottom: 15px;
            font-size: 14px;
        }
        form label {
            display: block;
            margin-bottom: 5px;
            text-align: left;
        }
        form input {
            width: 100%;
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }
        form button {
            width: 100%;
            padding: 10px;
            background-color: #007BFF;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
        }
        form button:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>
    <!-- Top Layer -->
    <div class="header-container">
        <div class="header">University of Patras</div>
    </div>

    <!-- Login Form -->
    <div class="login-container">
        <h1>Login</h1>
        <!-- Display error message -->
        <?php if (!empty($error_message)): ?>
            <div class="error-message"><?php echo $error_message; ?></div>
        <?php endif; ?>
        <form action="login.php" method="POST">
            <label for="email">Email:</label>
            <input type="email" id="email" name="email" required>
            <label for="password">Password:</label>
            <input type="password" id="password" name="password" required>
            <button type="submit">Login</button>
        </form>
    </div>
</body>
</html>
