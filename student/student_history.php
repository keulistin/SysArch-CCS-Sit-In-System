<?php
session_start();
require '../db_connect.php';

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(["success" => false, "message" => "Unauthorized access. Please log in."]);
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch sit-in history for the logged-in user
$sql_history = "SELECT s.student_idno, 
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
                WHERE u.user_id = ?
                ORDER BY h.end_time DESC";

$stmt = $conn->prepare($sql_history);
$stmt->bind_param("s", $user_id);
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
        <div class="student-search-container">
            <form id="studentSitinSearchForm">
                <div class="student-search-box">
                    <img src="../images/search-icon.png" alt="Search" class="search-icon">
                    <input type="text" id="searchStudent" placeholder="Search student" name="studentHistory">
                </div>
            </form>   
        </div>

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
                            if (!empty($history)) {
                                foreach ($history as $row) {
                                    echo "<tr>";
                                    echo "<td>" . htmlspecialchars($row['student_idno']) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['full_name']) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['sitin_purpose']) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['lab_room']) . "</td>";
                                    echo "<td>" . date("h:i A", strtotime($row['start_time'])) . "</td>";
                                    echo "<td>" . date("h:i A", strtotime($row['end_time'])) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['sitin_date']) . "</td>";
                                    echo '<td>
                                        <button class="feedback-btn" data-id="' . htmlspecialchars($row['student_idno']) .'" style="background: none; border: none; cursor: pointer;">
                                            <img src="../images/feedback-icon.png" alt="Exit" width="60" height="24">
                                            </button>
                                        </td>';
                                    echo "</tr>";
                                }
                            } else {
                                echo "<tr><td colspan='8'>No sit-in history found.</td></tr>";
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

    </div> <!-- content -->
</body>
</html>
