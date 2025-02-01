<?php
require 'db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id_diplomatikis = $_POST['id_diplomatikis'];
    $protocol_number = $_POST['protocol_number'];
    $meeting_date = $_POST['meeting_date'];

    // Start transaction
    $conn->begin_transaction();

    try {
        // Update diplomatiki table
        $sql1 = "UPDATE diplomatiki SET stud_num = NULL, state = 'canceled' WHERE id_diplomatikis = ?";
        $stmt1 = $conn->prepare($sql1);
        $stmt1->bind_param("i", $id_diplomatikis);
        $stmt1->execute();

        // Update commitee table
        $sql2 = "UPDATE commitee SET num_prof2 = NULL, num_prof3 = NULL WHERE id_diplomatikis = ?";
        $stmt2 = $conn->prepare($sql2);
        $stmt2->bind_param("i", $id_diplomatikis);
        $stmt2->execute();

        // Update commitee_app table
        $sql3 = "UPDATE commitee_app SET stud_num = NULL, prof_num_app = NULL WHERE id_diplomatikis = ?";
        $stmt3 = $conn->prepare($sql3);
        $stmt3->bind_param("i", $id_diplomatikis);
        $stmt3->execute();

        // Insert into general_meetings table
        $sql4 = "INSERT INTO general_meetings (protocol_number, general_meeting_date, reasoning) VALUES (?, ?, 'upon agreement with student')";
        $stmt4 = $conn->prepare($sql4);
        $stmt4->bind_param("iss", $protocol_number, $meeting_date);
        $stmt4->execute();

        // Commit transaction
        $conn->commit();

        header("Location: thesis_list.php?message=Thesis assignation canceled successfully");
    } catch (Exception $e) {
        // Rollback transaction if any query fails
        $conn->rollback();
        echo "Error: " . $e->getMessage();
    }
}
?>