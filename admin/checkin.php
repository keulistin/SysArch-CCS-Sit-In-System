<?php
require_once "../db_connect.php";
header("Content-Type: application/json");

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

$response = ["success" => false, "message" => ""];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $student_idno = $_POST['student_idno'] ?? '';
    $full_name = $_POST['full_name'] ?? ''; 
    $sitin_purpose = $_POST['sitin_purpose'] ?? '';
    $lab_room = $_POST['lab_room'] ?? '';
    $start_time = date("H:i:s");
    $sitin_date = date("Y-m-d");

    // Validate required fields
    if (empty($student_idno) || empty($full_name) || empty($sitin_purpose) || empty($lab_room)) {
        $response["message"] = "All fields are required.";
        echo json_encode($response);
        exit;
    }

    // Debug: Log received values
    error_log("Check-in Data: Student ID: $student_idno, Name: $full_name, Purpose: $sitin_purpose, Lab: $lab_room");

    // Check database connection
    if (!$conn) {
        $response["message"] = "Database connection failed.";
        echo json_encode($response);
        exit;
    }

    // Check lab status first
    $statusStmt = $conn->prepare("SELECT status FROM lab_schedules WHERE lab_room = ?");
    if (!$statusStmt) {
        $response["message"] = "Database statement error: " . $conn->error;
        echo json_encode($response);
        exit;
    }

    $statusStmt->bind_param("s", $lab_room);
    $statusStmt->execute();
    $statusResult = $statusStmt->get_result();

    if ($statusResult->num_rows > 0) {
        $lab = $statusResult->fetch_assoc();
        if ($lab['status'] !== 'open') {
            // Lab is not open
            $response["message"] = "The selected lab is currently closed.";
            echo json_encode($response);
            $statusStmt->close();
            $conn->close();
            exit;
        }
    } else {
        $response["message"] = "Lab not found.";
        echo json_encode($response);
        $statusStmt->close();
        $conn->close();
        exit;
    }
    $statusStmt->close();

    // Proceed to insert since lab is open
    $stmt = $conn->prepare("INSERT INTO current_sitin (student_idno, full_name, sitin_purpose, lab_room, sitin_date) 
    VALUES (?, ?, ?, ?, ?)");

    if (!$stmt) {
        $response["message"] = "Database statement error: " . $conn->error;
        echo json_encode($response);
        $conn->close();
        exit;
    }

    $stmt->bind_param("sssss", $student_idno, $full_name, $sitin_purpose, $lab_room, $sitin_date);

    if ($stmt->execute()) {
        $response["success"] = true;
        $response["message"] = "Check-in successful.";
        $response["sitin_id"] = $stmt->insert_id;
    } else {
        $response["message"] = "Student currently checked in, check out student first.";
        error_log($stmt->error);
    }

    $stmt->close();
    $conn->close();
} else {
    $response["message"] = "Invalid request method.";
}

echo json_encode($response);
?>
