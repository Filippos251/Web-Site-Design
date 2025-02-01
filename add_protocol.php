<?php
require 'db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id_diplomatikis = $_POST['id_diplomatikis'];
    $protocol_number = $_POST['protocol_number'];
    $general_meeting_date = date('Y-m-d');

    $sql = "INSERT INTO general_meetings (id_diplomatikis, protocol_number, general_meeting_date) 
            VALUES ('$id_diplomatikis', '$protocol_number', '$general_meeting_date')";

    if ($conn->query($sql) === TRUE) {
        header("Location: thesis_list.php?message=Protocol Number Added Successfully");
    } else {
        echo "Error: " . $conn->error;
    }
}
?>