<?php
require '../db_connect.php';

header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['resetAll']) && $_POST['resetAll'] === 'true') {
    // Update all students' remaining sit-ins to 30
    $updateStmt = $conn->prepare("UPDATE student SET remaining_sitin = 30");

    if ($updateStmt->execute()) {
        echo json_encode([
            "success" => true,
            "message" => "All students' remaining sit-ins have been reset to 30."
        ]);
    } else {
        echo json_encode([
            "success" => false,
            "message" => "Error resetting sit-ins: " . $updateStmt->error
        ]);
    }

    $updateStmt->close();
    exit();
}
?>
