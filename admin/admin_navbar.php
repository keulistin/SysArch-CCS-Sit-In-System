<?php
session_start();

$current_page = basename($_SERVER['PHP_SELF']);


// Define an array of pages under "Sit-in" dropdown
$sit_in_pages = ['admin_current_sitin.php', 'admin_history.php', 'admin_report.php'];
$is_sitin_active = in_array($current_page, $sit_in_pages);

// Check if the logout button was clicked
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['logout'])) {
    session_unset(); // Clear session variables
    session_destroy(); // Destroy the session
    header("Location: ../login.php"); // Redirect to login page
    exit();
}
?>

<nav class="navbar">
    <div class="left-nav">
        <div class="logo">
            <img src="../images/CCS_LOGO.png" alt="CCS Logo">
        </div>
        <ul class="nav-links">
            <li><a href="admin_dashboard.php" class="<?= $current_page == 'admin_dashboard.php' ? 'active' : '' ?>">Dashboard</a></li>
            <li><a href="admin_announcement.php" class="<?= $current_page == 'admin_announcement.php' ? 'active' : '' ?>">Announcement</a></li>
            <li><a href="admin_sitin.php" class="<?= $current_page == 'admin_sitin.php' ? 'active' : '' ?>">Sit-In</a></li>
            <li><a href="admin_lab.php" class="<?= $current_page == 'admin_lab.php' ? 'active' : '' ?>">Labs</a></li>
            <li><a href="admin_reservation.php" class="<?= $current_page == 'admin_reservation.php' ? 'active' : '' ?>">Reservations</a></li>
            <li><a href="admin_studentlist.php" class="<?= $current_page == 'admin_studentlist.php' ? 'active' : '' ?>">Students</a></li>
            <li><a href="admin_feedback.php" class="<?= $current_page == 'admin_feedback.php' ? 'active' : '' ?>">Feedback</a></li>
            <li><a href="admin_leaderboard.php" class="<?= $current_page == 'admin_leaderboard.php' ? 'active' : '' ?>">Leaderboards</a></li>
            <li><a href="admin_resources.php" class="<?= $current_page == 'admin_resources.php' ? 'active' : '' ?>">Resources</a></li>
        </ul>
    </div>
    <div class="user-section">
        <img src="../images/notif.png" alt="notification" id="notif-icon">
        <a href="admin_profile.php">
            <img src="../images/cinna.png" alt="User Profile" class="profile-pic">
        </a>
        <!-- Logout Button as a Form -->
        <form method="POST" style="display:inline;">
            <button type="submit" name="logout" id="logoutBtn">Log out</button>
        </form>
    </div>
</nav>
