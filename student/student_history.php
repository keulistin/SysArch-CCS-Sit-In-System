<?php
session_start();
require '../db_connect.php';

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(["success" => false, "message" => "Unauthorized access. Please log in."]);
    exit();
}

// Define foul words list (same as in submit_feedback.php)
$foulWords = ['atay', 'yawa', 'ass', 'shit', 'fuck', 'damn'];

$user_id = $_SESSION['user_id'];

// Fetch the student's ID based on the logged-in user
$sql_student = "SELECT student_idno FROM student WHERE user_id = ?";
$stmt_student = $conn->prepare($sql_student);
$stmt_student->bind_param("s", $user_id);
$stmt_student->execute();
$result_student = $stmt_student->get_result();
$student = $result_student->fetch_assoc();
$stmt_student->close();

if (!$student) {
    echo json_encode(["success" => false, "message" => "Student record not found."]);
    exit();
}

$student_idno = $student['student_idno']; // Get the correct student ID

// Fetch sit-in history for the logged-in student
$sql_history = "SELECT h.history_id,
                       h.student_idno, 
                       CONCAT(u.first_name, ' ', u.middle_name, ' ', u.last_name) AS full_name, 
                       s.course, 
                       s.year_level, 
                       u.email, 
                       h.sitin_purpose, 
                       h.lab_room, 
                       h.start_time, 
                       h.end_time, 
                       h.sitin_date,
                       h.feedback_desc
                FROM sitin_history h
                JOIN student s ON h.student_idno = s.student_idno
                JOIN user u ON s.user_id = u.user_id
                WHERE h.student_idno = ?
                ORDER BY h.end_time DESC";

$stmt = $conn->prepare($sql_history);
$stmt->bind_param("s", $student_idno);
$stmt->execute();
$result_history = $stmt->get_result();

$history = [];
while ($row = $result_history->fetch_assoc()) {
    $history[] = $row;
}

$stmt->close();
$conn->close();

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Sit-In</title>
    <link rel="stylesheet" href="student_styles.css">
    <script defer src="../script.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
</head>
<body>
    <!-- Include Navbar -->
    <?php include 'student_navbar.php'; ?>

    <div class="content"> <!-- content -->


        <div class="student-sitin-container" id="student-history-container">
            <div class="student-table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Student ID</th>
                            <th>Name</th>
                            <th>Purpose</th>
                            <th>Lab</th>
                            <th>Login</th>
                            <th>Logout</th>
                            <th>Date</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody id="studentSitinHistoryTable">
                        <?php
                            foreach ($history as $row) {
                                echo "<tr>";
                                echo "<td>" . htmlspecialchars($row['student_idno']) . "</td>";
                                echo "<td>" . htmlspecialchars($row['full_name']) . "</td>";
                                echo "<td>" . htmlspecialchars($row['sitin_purpose']) . "</td>";
                                echo "<td>" . htmlspecialchars($row['lab_room']) . "</td>";
                                echo "<td>" . date("h:i A", strtotime($row['start_time'])) . "</td>";
                                echo "<td>" . date("h:i A", strtotime($row['end_time'])) . "</td>";
                                echo "<td>" . htmlspecialchars($row['sitin_date']) . "</td>";
                               
                                if (!empty($row['feedback_desc'])) {
                                    // Check for foul words
                                    $containsFoulWord = false;
                                    foreach ($foulWords as $word) {
                                        if (stripos($row['feedback_desc'], $word) !== false) {
                                            $containsFoulWord = true;
                                            break;
                                        }
                                    }
                                    
                                    $buttonClass = 'view-feedback-btn' . ($containsFoulWord ? ' foul-feedback' : '');
                                    
                                    echo '<td>
                                        <button class="'.$buttonClass.'" data-id="'.htmlspecialchars($row['history_id']).'" 
                                            data-feedback="'.htmlspecialchars($row['feedback_desc']).'"
                                            style="background: none; border: none; cursor: pointer;">
                                            <img src="../images/view-icon.png" alt="View" width="60" height="24">
                                        </button>
                                    </td>';
                                } else {
                                    // No feedback: Show "Submit Feedback" button
                                    echo '<td>
                                        <button class="feedback-btn" data-id="' . htmlspecialchars($row['history_id']) . '" 
                                            style="background: none; border: none; cursor: pointer;">
                                            <img src="../images/feedback-icon.png" alt="Feedback" width="60" height="24">
                                        </button>
                                    </td>';
                                }

                                echo "</tr>";
                            }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Feedback Modal Overlay -->
        <div id="sitIn-feedbackModal" class="sitIn-modal">
            <div class="sitIn-modal-content">
                <span class="sitIn-close">&times;</span>
                <h2>Submit Feedback</h2>
                <form id="sitIn-feedbackForm">
                    <input type="hidden" id="sitIn-historyId" name="history_id">
                    <label for="sitIn-feedback">Your Feedback:</label>
                    <textarea id="sitIn-feedback" name="feedback" rows="4" placeholder="Enter your feedback here..." required></textarea>
                    <button type="submit">Submit</button>
                </form>
            </div>
        </div>

        <!-- View Feedback Modal -->
        <div id="viewFeedbackModal" class="sitIn-modal">
            <div class="sitIn-modal-content">
                <span class="view-close">&times;</span>
                <h2>Your Feedback</h2>
                <div id="feedback-display" class="feedback-display"></div>
            </div>
        </div>

    </div> <!-- content -->
</body>
</html>
