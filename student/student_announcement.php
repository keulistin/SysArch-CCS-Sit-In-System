<?php include '../db_connect.php'; ?> <!-- Only keep the database connection -->

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
        
    <div class="announcement-container">
        <h3>Latest Announcement!</h3>
            <div class="announcement-list">
                <?php
                $result = $conn->query("SELECT * FROM announcement ORDER BY ann_timestamp DESC");

                while ($row = $result->fetch_assoc()) {
                    echo "<div class='announcement-item'>";
                    echo "<h3>" . htmlspecialchars($row['ann_title']) . "</h3>";
                    echo "<p>" . htmlspecialchars($row['ann_description']) . "</p>";
                    echo "<span class='timestamp'>" . $row['ann_timestamp'] . "</span>";
                    echo "</div>";
                }

                $conn->close();
                ?>
            </div>
        </div>

    </div> <!-- content -->

</body>
</html>
