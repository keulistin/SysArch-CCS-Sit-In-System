<?php include '../db_connect.php'; ?> <!-- Only keep the database connection -->

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Sit-In</title>
    <link rel="stylesheet" href="admin_styles.css">
    <script defer src="script.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
</head>
<body>
    <!-- Include Navbar -->
    <?php include 'admin_navbar.php'; ?>

    <div class="content"> <!-- content -->
        <div class="announcement-container">
            <button class="add-announcement-btn" id="openModalBtn">+ Add Announcement</button>

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

        <!-- ADD ANNOUNCEMENT Overlay -->
        <div class="modal-overlay" id="modalOverlay">
            <div class="modal-content">
                <h2>Create Announcement</h2>
                <form id="announcementForm">
                    <label for="title">Title:</label>
                    <input type="text" id="title" name="title" required>

                    <label for="description">Description:</label>
                    <textarea id="description" name="description" rows="4" required></textarea>

                    <div class="modal-buttons">
                        <button type="button" id="closeModalBtn" class="cancel-btn">Cancel</button>
                        <button type="submit" class="submit-btn">Post</button>
                    </div>
                </form>
            </div>
        </div>
    </div> <!-- content -->

<script src="../script.js"></script>
</body>
</html>
