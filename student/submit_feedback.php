<?php
session_start();
require '../db_connect.php';

// Only output JSON if it's an API request
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    $foulWords = ['atay', 'yawa', 'ass', 'shit', 'fuck', 'damn'];
    // Don't output JSON here - just set the variable
    // Remove or comment out this line:
    // echo json_encode(["foul_words_list" => $foulWords]);
    exit();
}

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["success" => false, "message" => "Unauthorized access. Please log in."]);
    exit();
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $history_id = $_POST['history_id'];
    $feedback = trim($_POST['feedback']);

    if (empty($feedback)) {
        echo json_encode(["success" => false, "message" => "Feedback cannot be empty."]);
        exit();
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

    // Insert feedback into database (allows all feedback)
    $sql = "UPDATE sitin_history SET feedback_desc = ? WHERE history_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $feedback, $history_id);

    if ($stmt->execute()) {
        // Return both success and foul words detection result
        $hasFoulWords = false;
        $lowerFeedback = strtolower($feedback);
        foreach ($foulWords as $word) {
            if (strpos($lowerFeedback, strtolower($word)) !== false) {
                $hasFoulWords = true;
                break;
            }
        }
        
        echo json_encode([
            "success" => true,
            "message" => "Feedback submitted successfully.",
            "has_foul_words" => $hasFoulWords,
            "foul_words_list" => $foulWords // Optional: Send to frontend
        ]);
    } else {
        echo json_encode(["success" => false, "message" => "Error submitting feedback."]);
    }

    $stmt->close();
    $conn->close();
    exit();
}

// If not POST request, return foul words list for frontend detection
echo json_encode([
    "foul_words_list" => $foulWords
]);
?>