<?php
session_start();
require '../db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $history_id = $_POST['history_id'] ?? null;
    $feedback = $_POST['feedback'] ?? null;

    if (!$history_id || !$feedback) {
        echo json_encode(["success" => false, "message" => "Invalid input."]);
        exit();
    }

    // Update sit-in history feedback based on history_id
    $sql = "UPDATE sitin_history SET feedback_desc = ? WHERE history_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $feedback, $history_id);

    if ($stmt->execute()) {
        echo json_encode(["success" => true, "message" => "Feedback submitted successfully!"]);
    } else {
        echo json_encode(["success" => false, "message" => "Error submitting feedback."]);
    }

    $stmt->close();
    $conn->close();
}
?>
