<?php
require '../db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['sitin_id'])) {
    $sitin_id = $_POST['sitin_id'];

    // Fetch the sit-in record using sitin_id
    $stmt = $conn->prepare("SELECT c.sitin_id, s.student_idno, 
                                   CONCAT(u.first_name, ' ', u.middle_name, ' ', u.last_name) AS full_name, 
                                   c.sitin_purpose, c.lab_room, 
                                   c.start_time, NOW() AS end_time
                            FROM current_sitin c
                            JOIN student s ON c.student_idno = s.student_idno
                            JOIN user u ON s.user_id = u.user_id
                            WHERE c.sitin_id = ?");
    $stmt->bind_param("s", $sitin_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $end_time = $row['end_time'];
        $start_time = $row['start_time'];
        $duration = round((strtotime($end_time) - strtotime($start_time)) / 60);

        // Insert into sitin_history with sitin_id
        $insertStmt = $conn->prepare("INSERT INTO sitin_history 
                                      (sitin_id, student_idno, sitin_purpose, lab_room, start_time, end_time, duration) 
                                      VALUES (?, ?, ?, ?, ?, ?, ?)");
        $insertStmt->bind_param("ssssssi", 
            $row['sitin_id'], 
            $row['student_idno'], 
            $row['sitin_purpose'], 
            $row['lab_room'], 
            $start_time, 
            $end_time, 
            $duration
        );
        $insertStmt->execute();

        // Delete from current_sitin AFTER inserting into history
        $deleteStmt = $conn->prepare("DELETE FROM current_sitin WHERE sitin_id = ?");
        $deleteStmt->bind_param("s", $sitin_id);
        $deleteStmt->execute();

        echo json_encode([
            "success" => true,
            "message" => "Student checked out successfully!",
            "sitin_id" => $row['sitin_id'],
            "student_idno" => $row['student_idno'],
            "full_name" => $row['full_name'],
            "sitin_purpose" => $row['sitin_purpose'],
            "lab_room" => $row['lab_room'],
            "start_time" => $start_time,
            "end_time" => $end_time,
            "duration" => $duration
        ]);
    } else {
        echo json_encode(["success" => false, "message" => "Sit-in record not found."]);
    }

    $stmt->close();
    exit();
}
?>
