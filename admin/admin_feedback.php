<?php
require '../db_connect.php';

// Define foul words list (same as in submit_feedback.php)
$foulWords = ['atay', 'yawa', 'ass', 'shit', 'fuck', 'damn'];

// Fetch sit-in history from all students who submitted feedback
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
                WHERE h.feedback_desc IS NOT NULL AND h.feedback_desc != ''
                ORDER BY h.end_time DESC";

$result = $conn->query($sql_history);
$feedbacks = [];

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $feedbacks[] = $row;
    }
}
$conn->close();
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Sit-In</title>
    <link rel="stylesheet" href="admin_styles.css">
    <script defer src="../script.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">

</head>
<body>
    <!-- Include Navbar -->
    <?php include 'admin_navbar.php'; ?>

    <div class="content"> <!-- content -->
        <div class="feedback-table-container">
            <div class="feedback-table">
            <table>
                <thead>
                    <tr>
                        <th>Student ID</th>
                        <th>Name</th>
                        <th>Course</th>
                        <th>Year</th>
                        <th>Lab</th>
                        <th>Purpose</th>
                        <th>Date</th>
                        <th>Time-In</th>
                        <th>Time-Out</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($feedbacks)): ?>
                        <?php foreach ($feedbacks as $row): ?>
                            <?php
                                $containsFoul = false;
                                foreach ($foulWords as $word) {
                                    if (stripos($row['feedback_desc'], $word) !== false) {
                                        $containsFoul = true;
                                        break;
                                    }
                                }
                                $class = $containsFoul ? 'foul-feedback' : '';
                            ?>
                            <tr class="<?= $class ?>">
                                <td><?= htmlspecialchars($row['student_idno']) ?></td>
                                <td><?= htmlspecialchars($row['full_name']) ?></td>
                                <td><?= htmlspecialchars($row['course']) ?></td>
                                <td><?= htmlspecialchars($row['year_level']) ?></td>
                                <td><?= htmlspecialchars($row['lab_room']) ?></td>
                                <td><?= htmlspecialchars($row['sitin_purpose']) ?></td>
                                <td><?= htmlspecialchars($row['sitin_date']) ?></td>
                                <td><?= date("h:i A", strtotime($row['start_time'])) ?></td>
                                <td><?= date("h:i A", strtotime($row['end_time'])) ?></td>
                                <td>
                                    <button class="view-feedback-btn" 
                                            data-id="<?= $row['history_id'] ?>" 
                                            data-feedback="<?= htmlspecialchars($row['feedback_desc']) ?>" 
                                            style="background: none; border: none; cursor: pointer;">
                                        <img src="../images/view-icon.png" alt="View" width="60" height="24">
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="10">No feedbacks submitted yet.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
            </div>
        </div>

        <!-- View Feedback Modal -->
        <div id="viewFeedbackModal" class="sitIn-modal">
            <div class="sitIn-modal-content">
                <span class="view-close">&times;</span>
                <h2>Feedback</h2>
                <div id="feedback-display" class="feedback-display"></div>
            </div>
        </div>
        
    </div> <!-- content -->

</body>
</html>
