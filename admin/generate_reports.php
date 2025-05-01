<?php
require '../db_connect.php';

header('Content-Type: application/json');

$sql = "SELECT h.history_id,
                s.student_idno, 
                CONCAT(u.first_name, ' ', u.middle_name, ' ', u.last_name) AS full_name, 
                s.course, 
                s.year_level, 
                u.email, 
                h.sitin_purpose, 
                h.lab_room, 
                h.start_time, 
                h.end_time
        FROM sitin_history h
        JOIN student s ON h.student_idno = s.student_idno
        JOIN user u ON s.user_id = u.user_id
        ORDER BY h.end_time DESC";

$result = $conn->query($sql);

if ($result->num_rows > 0) {
    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
    echo json_encode(['success' => true, 'data' => $data]);
} else {
    echo json_encode(['success' => false, 'message' => 'No sit-in history found']);
}

$conn->close();
?>