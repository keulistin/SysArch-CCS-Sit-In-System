<?php
require '../db_connect.php';

header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['sitin_id'])) {
    $sitin_id = $_POST['sitin_id'];

    // Fetch the sit-in record using sitin_id from current_sitin table
    $stmt = $conn->prepare("SELECT sitin_id, student_idno, full_name, sitin_purpose, lab_room, start_time, sitin_date 
                            FROM current_sitin 
                            WHERE sitin_id = ?");
    $stmt->bind_param("s", $sitin_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();

        // Get current timestamp for end time (ensure both times are in the same format)
        $end_time = new DateTime("now", new DateTimeZone('Asia/Manila')); // Adjust for your timezone

        // Use the start time from the database and ensure it is a DateTime object with the correct timezone
        $start_time = new DateTime($row['start_time'], new DateTimeZone('Asia/Manila')); // Ensure timezone consistency
        $end_time_str = $end_time->format("Y-m-d H:i:s");

        // Calculate the duration in minutes
        $interval = $start_time->diff($end_time);
        $duration = ($interval->h * 60) + $interval->i; // Convert hours to minutes and add minutes

        // Insert into sitin_history (this is where we move the data from current_sitin to sitin_history)
        $insertStmt = $conn->prepare("INSERT INTO sitin_history 
                                      (sitin_id, student_idno, full_name, sitin_purpose, lab_room, start_time, end_time, duration, sitin_date) 
                                      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $insertStmt->bind_param("sssssssis", 
            $row['sitin_id'], 
            $row['student_idno'], 
            $row['full_name'], 
            $row['sitin_purpose'], 
            $row['lab_room'], 
            $row['start_time'], 
            $end_time_str, 
            $duration, 
            $row['sitin_date']
        );

        if ($insertStmt->execute()) {
            // After inserting into sitin_history, delete from current_sitin table
            $deleteStmt = $conn->prepare("DELETE FROM current_sitin WHERE sitin_id = ?");
            $deleteStmt->bind_param("s", $sitin_id);
            $deleteStmt->execute();

            // Subtract 1 from the student's remaining sit-in sessions
            $updateStmt = $conn->prepare("UPDATE student SET 
                                        remaining_sitin = remaining_sitin - 1,
                                        points = points + 1,
                                        total_points = total_points + 1
                                        WHERE student_idno = ?");
            $updateStmt->bind_param("s", $row['student_idno']);
            $updateStmt->execute();

            echo json_encode([
                "success" => true,
                "message" => "Student checked out successfully!",
                "sitin_id" => $row['sitin_id'],
                "student_idno" => $row['student_idno'],
                "full_name" => $row['full_name'],
                "sitin_purpose" => $row['sitin_purpose'],
                "lab_room" => $row['lab_room'],
                "start_time" => $row['start_time'],
                "end_time" => $end_time_str,
                "duration" => $duration,
                "points_added" => 1
            ]);
        } else {
            echo json_encode(["success" => false, "message" => "Error inserting into sitin_history: " . $insertStmt->error]);
        }
    } else {
        echo json_encode(["success" => false, "message" => "Sit-in record not found."]);
    }

    $stmt->close();
    exit();
}
?>
