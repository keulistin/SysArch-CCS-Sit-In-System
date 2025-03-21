<?php
require '../db_connect.php';  // Ensure this connects to your database

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_idno = $_POST['student_idno'] ?? null;
    $purpose = $_POST['purpose'] ?? null;
    $lab = $_POST['lab'] ?? null;

    if (!$student_idno || !$purpose || !$lab) {
        echo json_encode(["success" => false, "message" => "Invalid input. Please fill all fields."]);
        exit();
    }

    // Check if the student is already checked in
    $check_stmt = $conn->prepare("SELECT * FROM current_sitin WHERE student_idno = ?");
    $check_stmt->bind_param("s", $student_idno);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    $check_stmt->close();

    if ($result->num_rows > 0) {
        echo json_encode(["success" => false, "message" => "This student is already checked in. Please check out first."]);
        exit();
    }

    // If not checked in, proceed with check-in
    $insert_stmt = $conn->prepare("INSERT INTO current_sitin (student_idno, sitin_purpose, lab_room, start_time) VALUES (?, ?, ?, NOW())");
    $insert_stmt->bind_param("sss", $student_idno, $purpose, $lab);
    
    if ($insert_stmt->execute()) {
        echo json_encode(["success" => true, "message" => "Student successfully checked in."]);
    } else {
        echo json_encode(["success" => false, "message" => "Error checking in student."]);
    }

    $insert_stmt->close();
    $conn->close();
}
?>
