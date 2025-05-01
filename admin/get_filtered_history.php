<?php
require '../db_connect.php';

header('Content-Type: application/json');

// Get filter parameters
$labFilter = $_POST['lab_room'] ?? 'all';
$purposeFilter = $_POST['sitin_purpose'] ?? 'all';
$timePeriod = $_POST['time_period'] ?? 'all';
$startDate = $_POST['start_date'] ?? null;
$endDate = $_POST['end_date'] ?? null;

// Base query
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
        WHERE 1=1";

// Apply filters
if ($labFilter !== 'all') {
    $sql .= " AND h.lab_room = '" . $conn->real_escape_string($labFilter) . "'";
}

if ($purposeFilter !== 'all') {
    $sql .= " AND h.sitin_purpose = '" . $conn->real_escape_string($purposeFilter) . "'";
}

// Apply time filters
if ($timePeriod !== 'all') {
    $now = date('Y-m-d H:i:s');
    
    switch ($timePeriod) {
        case 'today':
            $sql .= " AND DATE(h.start_time) = CURDATE()";
            break;
        case 'week':
            $sql .= " AND YEARWEEK(h.start_time, 1) = YEARWEEK(CURDATE(), 1)";
            break;
        case 'month':
            $sql .= " AND MONTH(h.start_time) = MONTH(CURDATE()) AND YEAR(h.start_time) = YEAR(CURDATE())";
            break;
        case 'custom':
            if ($startDate && $endDate) {
                $sql .= " AND DATE(h.start_time) BETWEEN '" . $conn->real_escape_string($startDate) . "' 
                        AND '" . $conn->real_escape_string($endDate) . "'";
            }
            break;
    }
}

$sql .= " ORDER BY h.end_time DESC";

$result = $conn->query($sql);

if ($result->num_rows > 0) {
    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
    echo json_encode(["success" => true, "data" => $data]);
} else {
    echo json_encode(["success" => false, "message" => "No records found with these filters"]);
}

$conn->close();
?>