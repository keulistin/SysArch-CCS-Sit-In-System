<?php
require '../db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['student_idno'])) {
    $student_idno = $_POST['student_idno'];

    // Update remaining_sitin to 0
    $stmt = $conn->prepare("UPDATE student SET remaining_sitin = 30 WHERE student_idno = ?");
    $stmt->bind_param("s", $student_idno);
    
    if ($stmt->execute()) {
        echo json_encode(["success" => true, "message" => "Sit-in sessions reset successfully."]);
    } else {
        echo json_encode(["success" => false, "message" => "Failed to reset sit-in sessions."]);
    }

    $stmt->close();
    exit();
}

echo json_encode(["success" => false, "message" => "Invalid request."]);
?>
