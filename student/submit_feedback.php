<?php
session_start();
require '../db_connect.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["success" => false, "message" => "Unauthorized access. Please log in."]);
    exit();
}

// Define foul words list
$foulWords = ['atay', 'yawa', 'ass', 'shit', 'fuck', 'damn'];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $history_id = $_POST['history_id'];
    $feedback = trim($_POST['feedback']);

    if (empty($feedback)) {
        echo json_encode(["success" => false, "message" => "Feedback cannot be empty."]);
        exit();
    }

    // Check for foul words
    $containsFoulWord = false;
    foreach ($foulWords as $word) {
        if (stripos($feedback, $word) !== false) {
            $containsFoulWord = true;
            break;
        }
    }

    // Check if feedback already exists
    $sql_check = "SELECT feedback_desc FROM sitin_history WHERE history_id = ?";
    $stmt_check = $conn->prepare($sql_check);
    $stmt_check->bind_param("s", $history_id);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();
    $existing_feedback = $result_check->fetch_assoc();
    $stmt_check->close();

    if (!empty($existing_feedback['feedback_desc'])) {
        echo json_encode(["success" => false, "message" => "Feedback already submitted."]);
        exit();
    }

    // Insert feedback into database
    $sql = "UPDATE sitin_history SET feedback_desc = ? WHERE history_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $feedback, $history_id);

    if ($stmt->execute()) {
        echo json_encode([
            "success" => true, 
            "message" => "Feedback submitted successfully.",
            "hasFoulWord" => $containsFoulWord
        ]);
    } else {
        echo json_encode(["success" => false, "message" => "Error submitting feedback."]);
    }

    $stmt->close();
    $conn->close();
}
?>