<?php
require 'db.php';

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["id_diplomatikis"])) {
    $id = $_POST["id_diplomatikis"];
    $date = date("Y-m-d");

    $sql = "UPDATE diplomatiki SET state = 'completed', finished_date = ? WHERE id_diplomatikis = ? AND state = 'under examination'";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $date, $id);

    if ($stmt->execute()) {
        echo "Success";
    } else {
        echo "Error";
    }
}
?>
