<?php


$current_page = basename($_SERVER['PHP_SELF']);

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
            <li><a href="student_dashboard.php" class="<?= $current_page == 'student_dashboard.php' ? 'active' : '' ?>">Dashboard</a></li>
            <li><a href="student_announcement.php" class="<?= $current_page == 'student_announcement.php' ? 'active' : '' ?>">Announcement</a></li>
            <li><a href="student_history.php" class="<?= $current_page == 'student_history.php' ? 'active' : '' ?>">Sit-in History</a></li>
            <li><a href="student_reservation.php" class="<?= $current_page == 'student_reservation.php' ? 'active' : '' ?>">Reservation</a></li>
        </ul>
    </div>
    <div class="user-section">
        <img src="../images/notif.png" alt="notification" id="notif-icon">
        <a href="student_profile.php">
            <img src="../images/kuromi.webp" alt="User Profile" class="profile-pic">
        </a>
        <!-- Logout Button as a Form -->
        <form method="POST" style="display:inline;">
            <button type="submit" name="logout" id="logoutBtn">Log out</button>
        </form>
    </div>
</nav>
