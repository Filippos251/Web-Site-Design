<?php
session_start();

if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'secretariat') {
    header("Location: login.php?message=Please log in as secretariat to access this page");
    exit();
}

require 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['jsonFile'])) {
    $file = $_FILES['jsonFile'];

    // Ensure the uploads directory exists
    if (!is_dir('uploads')) {
        mkdir('uploads', 0777, true);
    }

    $filePath = 'uploads/' . basename($file['name']);

    // Move the uploaded file
    if (move_uploaded_file($file['tmp_name'], $filePath)) {
        // Read and decode the JSON file
        $jsonContent = file_get_contents($filePath);
        $data = json_decode($jsonContent, true);

        if ($data === null) {
            echo "Invalid JSON format.";
        } else {
            $successCount = 0;
            $errorCount = 0;

            // Process students
            if (isset($data['students'])) {
                foreach ($data['students'] as $student) {
                    $id = mysqli_real_escape_string($conn, $student['id']);
                    $name = mysqli_real_escape_string($conn, $student['name']);
                    $surname = mysqli_real_escape_string($conn, $student['surname']);
                    $student_number = mysqli_real_escape_string($conn, $student['student_number']);
                    $street = mysqli_real_escape_string($conn, $student['street']);
                    $number = mysqli_real_escape_string($conn, $student['number']);
                    $city = mysqli_real_escape_string($conn, $student['city']);
                    $postcode = mysqli_real_escape_string($conn, $student['postcode']);
                    $father_name = mysqli_real_escape_string($conn, $student['father_name']);
                    $landline_telephone = mysqli_real_escape_string($conn, $student['landline_telephone']);
                    $mobile_telephone = mysqli_real_escape_string($conn, $student['mobile_telephone']);
                    $email = mysqli_real_escape_string($conn, $student['email']);

                    $query = "INSERT INTO students (id, name, surname, student_number, street, number, city, postcode, father_name, landline_telephone, mobile_telephone, email) 
                              VALUES ('$id', '$name', '$surname', '$student_number', '$street', '$number', '$city', '$postcode', '$father_name', '$landline_telephone', '$mobile_telephone', '$email')";

                    if (mysqli_query($conn, $query)) {
                        $successCount++;
                    } else {
                        $errorCount++;
                    }
                }
            }

            // Process professors
            if (isset($data['professors'])) {
                foreach ($data['professors'] as $professor) {
                    $id = mysqli_real_escape_string($conn, $professor['id']);
                    $name = mysqli_real_escape_string($conn, $professor['name']);
                    $surname = mysqli_real_escape_string($conn, $professor['surname']);
                    $email = mysqli_real_escape_string($conn, $professor['email']);
                    $topic = mysqli_real_escape_string($conn, $professor['topic']);
                    $landline = mysqli_real_escape_string($conn, $professor['landline']);
                    $mobile = mysqli_real_escape_string($conn, $professor['mobile']);
                    $department = mysqli_real_escape_string($conn, $professor['department']);
                    $university = mysqli_real_escape_string($conn, $professor['university']);

                    $query = "INSERT INTO professors (id, name, surname, email, topic, landline, mobile, department, university) 
                              VALUES ('$id', '$name', '$surname', '$email', '$topic', '$landline', '$mobile', '$department', '$university')";

                    if (mysqli_query($conn, $query)) {
                        $successCount++;
                    } else {
                        $errorCount++;
                    }
                }
            }

            echo "Import completed: $successCount rows inserted successfully, $errorCount errors occurred.";
        }

        // Remove the file after processing
        unlink($filePath);
    } else {
        echo "Failed to move the uploaded file.";
    }
} else {
    echo "No file uploaded.";
}
?>

<!-- Redirect back to Secretariat Interface -->
<p>You will be redirected back to the Secretariat Interface in 3 seconds...</p>
<script>
    setTimeout(function() {
        window.location.href = 'secretariat_interface.php';
    }, 3000);
</script>