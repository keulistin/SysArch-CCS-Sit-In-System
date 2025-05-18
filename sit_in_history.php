<?php
session_start();
include 'db.php';

// Ensure the user is logged in
if (!isset($_SESSION['idno'])) {
    header("Location: login.php");
    exit();
}

$idno = $_SESSION['idno'];

// Fetch student info
$user_query = "SELECT firstname, lastname, profile_picture FROM users WHERE idno = ?";
$stmt = $conn->prepare($user_query);
$stmt->bind_param("s", $idno);
$stmt->execute();
$stmt->bind_result($firstname, $lastname, $profile_picture);
$stmt->fetch();
$stmt->close();

// Set default profile picture if none exists
if (empty($profile_picture)) {
    $profile_picture = "default_avatar.png";
}

// Handle feedback submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['feedback'])) {
    $record_id = $_POST['record_id'];
    $feedback = $_POST['feedback'];
    
    $feedback_query = "UPDATE sit_in_records SET feedback = ? WHERE id = ? AND student_id = (SELECT id FROM users WHERE idno = ?)";
    $stmt = $conn->prepare($feedback_query);
    $stmt->bind_param("sis", $feedback, $record_id, $idno);
    $stmt->execute();
    $stmt->close();
    
    // Reload the page to show updated feedback
    header("Location: sit_in_history.php");
    exit();
}

// Fetch the sit-in history for the logged-in user
$sit_in_history_query = "SELECT id, purpose, lab, start_time, end_time, feedback 
                         FROM sit_in_records 
                         WHERE student_id = (SELECT id FROM users WHERE idno = ?) 
                         ORDER BY start_time DESC";
$stmt = $conn->prepare($sit_in_history_query);
$stmt->bind_param("s", $idno);
$stmt->execute();
$result = $stmt->get_result();
$sit_in_history = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$page_title = "Sit-in History";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sit-in History - CCS SIT Monitoring System</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Poppins', 'sans-serif'],
                    },
                },
            },
        }
        
        function toggleFeedbackForm(rowId, event) {
            if (event) event.stopPropagation();
            const overlay = document.getElementById(`feedback-overlay-${rowId}`);
            
            if (overlay.classList.contains('hidden')) {
                // Close any other open feedback forms first
                closeAllFeedbackForms();
                // Open this one
                overlay.classList.remove('hidden');
                document.body.style.overflow = 'hidden';
            } else {
                overlay.classList.add('hidden');
                document.body.style.overflow = '';
            }
        }

        function closeAllFeedbackForms() {
            document.querySelectorAll('[id^="feedback-overlay-"]').forEach(overlay => {
                overlay.classList.add('hidden');
            });
            document.body.style.overflow = '';
        }

        // Close overlay when clicking outside the form
        document.addEventListener('DOMContentLoaded', function() {
            document.addEventListener('click', function(e) {
                if (!e.target.closest('.feedback-form-container') && 
                    !e.target.closest('[onclick^="toggleFeedbackForm"]')) {
                    closeAllFeedbackForms();
                }
            });
        });
    </script>
    <style>
        body {
            background-color: #F1E6EF;
        }
        .main-content-cont {
            padding: 8rem 15rem 5rem 15rem;
        }
        .sidebar-scroll {
            scrollbar-width: thin;
            scrollbar-color: #4b5563 #1e293b;
        }
        .sidebar-scroll::-webkit-scrollbar {
            width: 6px;
        }
        .sidebar-scroll::-webkit-scrollbar-track {
            background: #1e293b;
        }
        .sidebar-scroll::-webkit-scrollbar-thumb {
            background-color: #4b5563;
            border-radius: 3px;
        }
        .fade-in {
            opacity: 0;
            animation: fadeIn 0.5s ease-in forwards;
        }
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        .feedback-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(0, 0, 0, 0.7);
            z-index: 1000;
            display: flex;
            justify-content: center;
            align-items: center;
            backdrop-filter: blur(2px);
        }
        
        .feedback-form-container {
            background-color: #1e293b;
            border-radius: 0.5rem;
            padding: 1.5rem;
            width: 90%;
            max-width: 500px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.5);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        .highlight-row {
            background-color: rgba(30, 58, 138, 0.2);
        }
    </style>
</head>
<body class="font-sans text-black">
<!-- Top Navigation Bar -->
<div class="fixed top-0 left-0 right-0 bg-white shadow-md z-50">
    <div class="flex items-center justify-between px-6 py-3">
        <!-- CCS Logo -->
        <div class="flex items-center">
            <img src="images/CCS.png" alt="CCS Logo" class="h-14">
        </div>

        <!-- Main Navigation Links -->
        <nav class="hidden md:flex items-center space-x-2">
            <a href="student_dashboard.php" class="px-4 py-2 text-gray-700 font-medium hover:bg-gray-100 rounded-md transition-all duration-200">
                Dashboard
            </a>

            <!-- Rules Dropdown -->
            <div class="relative">
                <button onclick="toggleDropdown('rulesDropdownStudent')" class="px-4 py-2 text-gray-700 font-medium hover:bg-gray-100 rounded-md transition-all duration-200">
                    Rules <i class="fas fa-chevron-down ml-1 text-xs"></i>
                </button>
                <div id="rulesDropdownStudent" class="nav-dropdown absolute left-0 mt-2 w-48 bg-white rounded-md shadow-xl py-1 hidden">
                    <a href="sit-in-rules.php" class="block px-4 py-2 text-sm text-text-secondary">Sit-in Rules</a>
                    <a href="lab-rules.php" class="block px-4 py-2 text-sm text-text-secondary">Lab Rules</a>
                </div>
            </div>

            <!-- Sit-ins Dropdown -->
            <div class="relative">
                <button onclick="toggleDropdown('sitInsDropdownStudent')" class="px-4 py-2 text-gray-700 font-medium hover:bg-gray-100 rounded-md transition-all duration-200">
                    Sit-ins <i class="fas fa-chevron-down ml-1 text-xs"></i>
                </button>
                <div id="sitInsDropdownStudent" class="nav-dropdown absolute left-0 mt-2 w-48 bg-white rounded-md shadow-xl py-1 hidden">
                    <a href="reservation.php" class="block px-4 py-2 text-sm text-text-secondary">Reservation</a>
                    <a href="sit_in_history.php" class="block px-4 py-2 text-sm text-text-secondary">History</a>
                </div>
            </div>

            <!-- Resources Dropdown -->
            <div class="relative">
                <button onclick="toggleDropdown('resourcesDropdownStudent')" class="px-4 py-2 text-gray-700 font-medium hover:bg-gray-100 rounded-md transition-all duration-200">
                    Resources <i class="fas fa-chevron-down ml-1 text-xs"></i>
                </button>
                <div id="resourcesDropdownStudent" class="nav-dropdown absolute left-0 mt-2 w-48 bg-white rounded-md shadow-xl py-1 hidden">
                    <a href="upload_resources.php" class="block px-4 py-2 text-sm text-text-secondary">View Resources</a>
                    <a href="student_leaderboard.php" class="block px-4 py-2 text-sm text-text-secondary">Leaderboard</a>
                    <a href="student_lab_schedule.php" class="block px-4 py-2 text-sm text-text-secondary">Lab Schedule</a>
                </div>
            </div>

            <!-- Announcements -->
            <a href="announcements.php" class="px-4 py-2 text-gray-700 font-medium hover:bg-gray-100 rounded-md transition-all duration-200">
                Announcements
            </a>

            <!-- Edit Profile -->
            <a href="edit-profile.php" class="px-4 py-2 text-gray-700 font-medium hover:bg-gray-100 rounded-md transition-all duration-200">
                Edit Profile
            </a>
        </nav>

        <!-- User and Notification Controls -->
        <div class="flex gap-4 ml-4">
            <!-- Notification Button -->
            <div class="relative">
                <button id="notificationButton" class="relative p-2 text-light hover:text-secondary rounded-full transition-all duration-200 focus:outline-none">
                    <i class="fas fa-bell text-lg text-purple-500"></i>
                    <span class="notification-badge hidden">0</span>
                </button>

                <!-- Notification Dropdown -->
                <div id="notificationDropdown" class="hidden absolute right-0 mt-2 w-72 bg-white rounded-lg shadow-xl border border-secondary/20 z-50 overflow-hidden">
                    <div class="p-3 bg-purple-500 text-white flex justify-between items-center">
                        <span class="font-semibold">Notifications</span>
                        <button id="markAllRead" class="text-xs bg-white/20 hover:bg-white/30 px-2 py-1 rounded transition-all">
                            <i class="fas fa-check text-xl"></i>
                        </button>
                    </div>
                    <div id="notificationList" class="max-h-80 overflow-y-auto">
                        <div class="p-4 text-center text-gray-500">No notifications</div>
                    </div>
                </div>
            </div>

            <!-- User Avatar and Logout -->
            <div class="flex items-center space-x-4">
  
                <h2 class="px-4 py-2 text-gray-700 font-bold"><?php echo htmlspecialchars($firstname); ?></h2>

                <!-- Logout -->
                <div class="ml-4">
                    <a href="logout.php" onclick="return confirm('Are you sure you want to log out?')" class="flex items-center px-4 py-2 bg-purple-600 text-white rounded-full border-2 border-purple-700 hover:bg-purple-700 transition-all duration-200 shadow-md">
                        <i class="fas fa-sign-out-alt mr-2"></i>
                        <span class="hidden md:inline">Log Out</span>
                    </a>
                </div>
            </div>

            <!-- User Profile Dropdown (Placeholder for future) -->
            <div class="relative">
                <button id="userMenuButton" class="flex items-center gap-2 group focus:outline-none"></button>
            </div>
        </div>

        <!-- Mobile menu button -->
        <div class="mobile-menu md:hidden flex items-center">
            <button id="mobileMenuButton" class="text-light hover:text-secondary focus:outline-none">
                <i class="fas fa-bars text-xl"></i>
            </button>
        </div>
    </div>

    <!-- Mobile Menu (hidden by default) -->
    <div id="mobileMenu" class="hidden md:hidden bg-primary">
        <div class="px-2 pt-2 pb-3 space-y-1 sm:px-3">
            <a href="student_dashboard.php" class="block px-3 py-2 rounded-md text-base font-medium text-light hover:bg-primary/20">Profile</a>
            <a href="edit-profile.php" class="block px-3 py-2 rounded-md text-base font-medium text-secondary hover:bg-primary/20">Edit Profile</a>
            <a href="announcements.php" class="block px-3 py-2 rounded-md text-base font-medium text-secondary hover:bg-primary/20">Announcements</a>
            <a href="reservation.php" class="block px-3 py-2 rounded-md text-base font-medium text-secondary hover:bg-primary/20">Reservation</a>
            <a href="sit_in_history.php" class="block px-3 py-2 rounded-md text-base font-medium text-secondary hover:bg-primary/20">Sit-in History</a>
            <a href="student_leaderboard.php" class="block px-3 py-2 rounded-md text-base font-medium text-secondary hover:bg-primary/20">Leaderboard</a>
            <a href="sit-in-rules.php" class="block px-3 py-2 rounded-md text-base font-medium text-secondary hover:bg-primary/20">Sit-in Rules</a>
            <a href="lab-rules.php" class="block px-3 py-2 rounded-md text-base font-medium text-secondary hover:bg-primary/20">Lab Rules</a>
            <a href="upload_resources.php" class="block px-3 py-2 rounded-md text-base font-medium text-secondary hover:bg-primary/20">Lab Resources</a>
            <a href="student_lab_schedule.php" class="block px-3 py-2 rounded-md text-base font-medium text-secondary hover:bg-primary/20">Lab Schedule</a>
            <a href="logout.php" onclick="return confirm('Are you sure you want to log out?')" class="block px-3 py-2 rounded-md text-base font-medium text-secondary hover:bg-primary/20">Log Out</a>
        </div>
    </div>
</div>


<!-- Main Content -->
    <div class="min-h-screen bg-purple-100 main-content-cont">
    <!-- Page Header -->
    <div class="mb-8">
        <h2 class="text-3xl font-medium text-gray-800 tracking-tight">Sit-in History</h2>
        <p class="text-gray-500 font-light">View your past and current sit-in sessions</p>
        <div class="w-16 h-1 bg-gradient-to-r from-purple-400 to-indigo-500 mt-4 rounded-full"></div>
    </div>

    <!-- History Table -->
    <div class="bg-white rounded-xl border border-gray-100 shadow-xs hover:shadow-sm transition-all duration-300">
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead class="bg-gradient-to-r from-purple-400 to-indigo-500">
                    <tr>
                        <th class="p-3 text-sm font-medium text-white uppercase">Purpose</th>
                        <th class="p-3 text-sm font-medium text-white uppercase">Lab</th>
                        <th class="p-3 text-sm font-medium text-white uppercase">Start Time</th>
                        <th class="p-3 text-sm font-medium text-white uppercase">End Time</th>
                        <th class="p-3 text-sm font-medium text-white uppercase">Feedback</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php if (empty($sit_in_history)) { ?>
                        <tr>
                            <td colspan="5" class="p-4 text-center text-gray-500">No sit-in records found.</td>
                        </tr>
                    <?php } else { ?>
                        <?php foreach ($sit_in_history as $record) { ?>
                            <tr class="hover:bg-gray-50">
                                <td class="p-3"><?php echo htmlspecialchars($record['purpose']); ?></td>
                                <td class="p-3"><?php echo htmlspecialchars($record['lab']); ?></td>
                                <td class="p-3"><?php echo date("F d, Y - h:i A", strtotime($record['start_time'])); ?></td>
                                <td class="p-3">
                                    <?php 
                                        echo $record['end_time'] ? 
                                            date("F d, Y - h:i A", strtotime($record['end_time'])) : 
                                            '<span class="text-red-500 font-medium">Still Active</span>'; 
                                    ?>
                                </td>
                                <td class="p-3">
                                    <div id="feedback-display-<?php echo $record['id']; ?>" class="flex items-center">
                                        <?php if (!empty($record['feedback'])) { ?>
                                            <span class="text-gray-600"><?php echo htmlspecialchars($record['feedback']); ?></span>
                                            <button onclick="toggleFeedbackForm(<?php echo $record['id']; ?>, event)" class="ml-2 text-purple-600 hover:text-purple-800">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                        <?php } else { ?>
                                            <button onclick="toggleFeedbackForm(<?php echo $record['id']; ?>, event)" class="text-purple-600 hover:text-purple-800">
                                                <i class="fas fa-plus-circle mr-1"></i> Add Feedback
                                            </button>
                                        <?php } ?>
                                    </div>
                                </td>
                            </tr>
                        <?php } ?>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Feedback Overlays -->
<?php foreach ($sit_in_history as $record) { ?>
    <div id="feedback-overlay-<?php echo $record['id']; ?>" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 hidden">
        <div class="bg-white rounded-xl p-6 w-full max-w-md border border-gray-200 shadow-lg">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-xl font-medium text-gray-800"><?php echo empty($record['feedback']) ? 'Add Feedback' : 'Edit Feedback'; ?></h3>
                <button onclick="toggleFeedbackForm(<?php echo $record['id']; ?>, event)" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <form method="POST" class="space-y-4">
                <input type="hidden" name="record_id" value="<?php echo $record['id']; ?>">
                <div>
                    <textarea name="feedback" rows="4" class="w-full p-3 bg-gray-50 border border-gray-200 rounded-lg text-gray-700 focus:ring-2 focus:ring-purple-400 focus:border-purple-400"
                        placeholder="Enter your feedback..."><?php echo htmlspecialchars($record['feedback'] ?? ''); ?></textarea>
                </div>
                <div class="flex justify-end space-x-3">
                    <button type="button" onclick="toggleFeedbackForm(<?php echo $record['id']; ?>, event)" class="px-4 py-2 text-gray-600 hover:text-gray-800 border border-gray-300 rounded-lg hover:bg-gray-50 transition-all duration-200">
                        Cancel
                    </button>
                    <button type="submit" class="px-4 py-2 bg-gradient-to-r from-purple-500 to-purple-600 text-white rounded-lg hover:from-purple-600 hover:to-purple-700 transition-all duration-200 shadow-sm">
                        Save Feedback
                    </button>
                </div>
            </form>
        </div>
    </div>
<?php } ?>

<script>
function toggleFeedbackForm(recordId, event) {
    event.preventDefault();
    const overlay = document.getElementById(`feedback-overlay-${recordId}`);
    overlay.classList.toggle('hidden');
    document.body.style.overflow = overlay.classList.contains('hidden') ? 'auto' : 'hidden';
}

// Dropdown toggle functions
function toggleDropdown(id) {
    const dropdown = document.getElementById(id);
    dropdown.classList.toggle('hidden');
    // Hide other dropdowns
    document.querySelectorAll('.nav-dropdown').forEach(el => {
        if (el.id !== id) el.classList.add('hidden');
    });
}

function toggleMobileDropdown(id) {
    document.getElementById(id).classList.toggle('hidden');
}

// Close dropdowns when clicking outside
document.addEventListener('click', function(event) {
    let clickedElement = event.target;
    let isDropdownButtonOrInsideDropdown = false;

    while (clickedElement != null) {
        // Check if click is on a dropdown button OR inside an open dropdown menu
        if (clickedElement.matches('button[onclick^="toggleDropdown"]') || 
            (clickedElement.classList && clickedElement.classList.contains('nav-dropdown') && !clickedElement.classList.contains('hidden'))) {
            isDropdownButtonOrInsideDropdown = true;
            break;
        }
        clickedElement = clickedElement.parentElement;
    }

    if (!isDropdownButtonOrInsideDropdown) {
        document.querySelectorAll('.nav-dropdown').forEach(function(dd) {
            dd.classList.add('hidden');
        });
    }
});

// Mobile menu toggle
document.getElementById('mobile-menu-button').addEventListener('click', function() {
    document.getElementById('mobile-menu').classList.toggle('hidden');
});

// Logout confirmation
function confirmLogout(event) {
    if (!confirm('Are you sure you want to log out?')) {
        event.preventDefault();
    }
}

    </script>
</body>
</html>