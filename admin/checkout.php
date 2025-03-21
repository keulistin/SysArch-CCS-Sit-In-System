<?php
require '../db_connect.php';  // Ensure proper database connection

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_idno = $_POST['student_idno'] ?? null;

    if (!$student_idno) {
        echo json_encode(["success" => false, "message" => "Invalid student ID."]);
        exit();
    }

    // Start a transaction
    $conn->begin_transaction();

    try {
        // Move student sit-in record from `current_sitin` to `sitin_history`
        $sql_insert = "INSERT INTO sitin_history (student_idno, admin_idno, lab_room, sitin_purpose, start_time, end_time, duration) 
                       SELECT student_idno, 1, lab_room, sitin_purpose, start_time, NOW(), TIMESTAMPDIFF(MINUTE, start_time, NOW()) 
                       FROM current_sitin 
                       WHERE student_idno = ?";
        $stmt_insert = $conn->prepare($sql_insert);
        $stmt_insert->bind_param("s", $student_idno);
        $stmt_insert->execute();
        $stmt_insert->close();

        // Fetch the newly inserted record to return to frontend
        $sql_get = "SELECT h.student_idno, 
                           CONCAT(u.first_name, ' ', u.middle_name, ' ', u.last_name) AS full_name, 
                           h.sitin_purpose, 
                           h.lab_room, 
                           h.start_time, 
                           h.end_time, 
                           h.duration 
                    FROM sitin_history h
                    JOIN student s ON h.student_idno = s.student_idno
                    JOIN user u ON s.user_id = u.user_id
                    WHERE h.student_idno = ? 
                    ORDER BY h.end_time DESC LIMIT 1";
        
        $stmt_get = $conn->prepare($sql_get);
        $stmt_get->bind_param("s", $student_idno);
        $stmt_get->execute();
        $result = $stmt_get->get_result();
        $checked_out_student = $result->fetch_assoc();
        $stmt_get->close();

        // Delete from `current_sitin`
        $sql_delete = "DELETE FROM current_sitin WHERE student_idno = ?";
        $stmt_delete = $conn->prepare($sql_delete);
        $stmt_delete->bind_param("s", $student_idno);
        $stmt_delete->execute();
        $stmt_delete->close();

        // Decrease remaining sit-in session count in the student table
        $sql_update = "UPDATE student SET remaining_sitin = remaining_sitin - 1 WHERE student_idno = ? AND remaining_sitin > 0";
        $stmt_update = $conn->prepare($sql_update);
        $stmt_update->bind_param("s", $student_idno);
        $stmt_update->execute();
        $stmt_update->close();

        // Commit the transaction
        $conn->commit();

        echo json_encode(["success" => true, "message" => "Checkout successful!", "data" => $checked_out_student]);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(["success" => false, "message" => "Error: " . $e->getMessage()]);
    }

    $conn->close();
}
?>
