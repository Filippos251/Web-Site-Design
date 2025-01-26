<?php
session_start();

if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'student') {
    header("Location: login.php?message=Please log in as a student to access this page");
    exit();
}

if (!isset($_SESSION['user_id'])) {
    echo "Error: User ID not found in session.";
    exit();
}

require 'db.php';

$user_id = $_SESSION['user_id'];

// Fetch the student's profile data
$query = "SELECT * FROM students WHERE id = '$user_id'";
$result = mysqli_query($conn, $query);

if ($result && mysqli_num_rows($result) > 0) {
    $student = mysqli_fetch_assoc($result);
} else {
    echo "Student not found in the database.";
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $street = mysqli_real_escape_string($conn, $_POST['street']);
    $number = mysqli_real_escape_string($conn, $_POST['number']);
    $city = mysqli_real_escape_string($conn, $_POST['city']);
    $postcode = mysqli_real_escape_string($conn, $_POST['postcode']);
    $landline = mysqli_real_escape_string($conn, $_POST['landline']);
    $mobile = mysqli_real_escape_string($conn, $_POST['mobile']);
    $new_email = mysqli_real_escape_string($conn, $_POST['email']);

    // Update the students table
    $update_student_query = "UPDATE students 
                             SET street = '$street', number = '$number', city = '$city', postcode = '$postcode', 
                                 landline_telephone = '$landline', mobile_telephone = '$mobile', email = '$new_email'
                             WHERE id = '$user_id'";

    // Update the users table
    $update_user_query = "UPDATE users 
                          SET email = '$new_email'
                          WHERE id = '$user_id'";

    $success = mysqli_query($conn, $update_student_query) && mysqli_query($conn, $update_user_query);

    if ($success) {
        echo "<p>Profile and email updated successfully!</p>";
    } else {
        echo "<p>Error updating profile: " . mysqli_error($conn) . "</p>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f5f5dc;
            color: #333;
            margin: 0;
            padding: 0;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }
        .form-container {
            background: rgba(255, 255, 255, 0.9);
            padding: 20px 40px;
            border-radius: 15px;
            box-shadow: 0 8px 15px rgba(0, 0, 0, 0.2);
            width: 400px;
            text-align: center;
        }
        form {
            display: flex;
            flex-direction: column;
            margin-bottom: 20px;
        }
        label, input {
            margin-bottom: 10px;
        }
        input {
            padding: 8px;
            border-radius: 5px;
            border: 1px solid #ddd;
        }
        button {
            padding: 10px 20px;
            background-color: #007BFF;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s ease;
            margin-top: 10px;
        }
        button:hover {
            background-color: #0056b3;
        }
        .back-button {
            background-color: #6c757d;
            margin-top: 20px;
        }
        .back-button:hover {
            background-color: #5a6268;
        }
    </style>
</head>
<body>
    <div class="form-container">
        <h2>Edit Profile</h2>
        <form method="POST" action="">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($student['email']); ?>" required>

            <label for="street">Street</label>
            <input type="text" id="street" name="street" value="<?php echo htmlspecialchars($student['street']); ?>" required>

            <label for="number">Number</label>
            <input type="text" id="number" name="number" value="<?php echo htmlspecialchars($student['number']); ?>" required>

            <label for="city">City</label>
            <input type="text" id="city" name="city" value="<?php echo htmlspecialchars($student['city']); ?>" required>

            <label for="postcode">Postcode</label>
            <input type="text" id="postcode" name="postcode" value="<?php echo htmlspecialchars($student['postcode']); ?>" required>

            <label for="landline">Landline Telephone</label>
            <input type="text" id="landline" name="landline" value="<?php echo htmlspecialchars($student['landline_telephone']); ?>">

            <label for="mobile">Mobile Telephone</label>
            <input type="text" id="mobile" name="mobile" value="<?php echo htmlspecialchars($student['mobile_telephone']); ?>" required>

            <button type="submit">Update Profile</button>
        </form>
        <button class="back-button" onclick="window.location.href='student_interface.php';">Back</button>
    </div>
</body>
</html>
