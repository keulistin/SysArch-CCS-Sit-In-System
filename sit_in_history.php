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
            <a href="admin_dashboard.php" class="px-4 py-2 text-gray-700 font-medium hover:bg-gray-100 rounded-md transition-all duration-200">
                Dashboard
            </a>
            
            <!-- Records Dropdown -->
            <div class="relative group">
                <button class="px-4 py-2 text-gray-700 font-medium hover:bg-gray-100 rounded-md transition-all duration-200 flex items-center">
                    Records
                    <i class="fas fa-chevron-down ml-2 text-xs"></i>
                </button>
                <div class="absolute left-0 mt-2 w-48 bg-white rounded-md shadow-lg py-1 z-10 hidden group-hover:block">
                    <a href="todays_sitins.php" class="block px-4 py-2 text-gray-700 hover:bg-gray-100">Current Sit-ins</a>
                    <a href="sit_in_records.php" class="block px-4 py-2 text-gray-700 hover:bg-gray-100">Sit-in Reports</a>
                    <a href="feedback_records.php" class="block px-4 py-2 text-gray-700 hover:bg-gray-100">Feedback Reports</a>
                </div>
            </div>
            
            <!-- Management Dropdown -->
            <div class="relative group">
                <button class="px-4 py-2 text-gray-700 font-medium hover:bg-gray-100 rounded-md transition-all duration-200 flex items-center">
                    Management
                    <i class="fas fa-chevron-down ml-2 text-xs"></i>
                </button>
                <div class="absolute left-0 mt-2 w-48 bg-white rounded-md shadow-lg py-1 z-10 hidden group-hover:block">
                    <a href="manage_sitins.php" class="block px-4 py-2 text-gray-700 hover:bg-gray-100">Manage Sit-ins</a>
                    <a href="studentlist.php" class="block px-4 py-2 text-gray-700 hover:bg-gray-100">Students</a>
                    <a href="create_announcement.php" class="block px-4 py-2 text-gray-700 hover:bg-gray-100">Announcements</a>
                </div>
            </div>
            
            <!-- Reservations Dropdown -->
            <div class="relative group">
                <button class="px-4 py-2 text-gray-700 font-medium hover:bg-gray-100 rounded-md transition-all duration-200 flex items-center">
                    Reservations
                    <i class="fas fa-chevron-down ml-2 text-xs"></i>
                </button>
                <div class="absolute left-0 mt-2 w-48 bg-white rounded-md shadow-lg py-1 z-10 hidden group-hover:block">
                    <a href="manage_reservation.php" class="block px-4 py-2 text-gray-700 hover:bg-gray-100">Reservations</a>
                    <a href="reservation_logs.php" class="block px-4 py-2 text-gray-700 hover:bg-gray-100">Reservation Logs</a>
                </div>
            </div>
            
            <!-- Resources Dropdown -->
            <div class="relative group">
                <button class="px-4 py-2 text-gray-700 font-medium hover:bg-gray-100 rounded-md transition-all duration-200 flex items-center">
                    Resources
                    <i class="fas fa-chevron-down ml-2 text-xs"></i>
                </button>
                <div class="absolute left-0 mt-2 w-48 bg-white rounded-md shadow-lg py-1 z-10 hidden group-hover:block">
                    <a href="admin_upload_resources.php" class="block px-4 py-2 text-gray-700 hover:bg-gray-100">Upload Resources</a>
                    <a href="admin_leaderboard.php" class="block px-4 py-2 text-gray-700 hover:bg-gray-100">Leaderboard</a>
                </div>
            </div>
            
            <!-- Labs Dropdown -->
            <div class="relative group">
                <button class="px-4 py-2 text-gray-700 font-medium hover:bg-gray-100 rounded-md transition-all duration-200 flex items-center">
                    Labs
                    <i class="fas fa-chevron-down ml-2 text-xs"></i>
                </button>
                <div class="absolute left-0 mt-2 w-48 bg-white rounded-md shadow-lg py-1 z-10 hidden group-hover:block">
                    <a href="admin_lab_schedule.php" class="block px-4 py-2 text-gray-700 hover:bg-gray-100">Lab Schedule</a>
                    <a href="lab_management.php" class="block px-4 py-2 text-gray-700 hover:bg-gray-100">Lab Management</a>
                </div>
            </div>
        </nav>
        
        <!-- Mobile Menu Button (hidden on larger screens) -->
        <div class="md:hidden">
            <button id="mobile-menu-button" class="text-gray-700 hover:text-gray-900">
                <i class="fas fa-bars text-xl"></i>
            </button>
        </div>
        
        <div class = "flex">
                     <!-- Admin Info -->
         <div class="flex items-center space-x-0">
                <!-- Admin Icon -->
                <div class="w-10 h-10 rounded-full bg-purple-600 flex items-center justify-center">
                    <i class="fas fa-user-shield text-white"></i>
                </div>
                <!-- Admin Name -->
                <h2 class="px-4 py-2 text-gray-700 font-bold">Admin <?php echo htmlspecialchars($firstname); ?></h2>
            </div>

        <!-- Logout Button -->
        <div class="ml-4">
            
        <a href="logout.php" class="flex items-center px-4 py-2 bg-purple-600 text-white rounded-full border-2 border-purple-700 hover:bg-purple-700 transition-all duration-200 shadow-md">
        <i class="fas fa-sign-out-alt mr-2"></i>
        <span class="hidden md:inline">Log Out</span>
    </a>
        </div>
        </div>
    </div>
    
    <!-- Mobile Menu (hidden by default) -->
    <div id="mobile-menu" class="hidden md:hidden bg-white border-t border-gray-200 px-6 py-3">
        <a href="admin_dashboard.php" class="block py-2 text-gray-700">Dashboard</a>
        
        <div class="py-2">
            <button class="flex items-center justify-between w-full text-gray-700" onclick="toggleMobileDropdown('records-dropdown')">
                Records
                <i class="fas fa-chevron-down"></i>
            </button>
            <div id="records-dropdown" class="hidden pl-4">
                <a href="todays_sitins.php" class="block py-2 text-gray-700">Current Sit-ins</a>
                <a href="sit_in_records.php" class="block py-2 text-gray-700">Sit-in Reports</a>
                <a href="feedback_records.php" class="block py-2 text-gray-700">Feedback Reports</a>
            </div>
        </div>
        
        <div class="py-2">
            <button class="flex items-center justify-between w-full text-gray-700" onclick="toggleMobileDropdown('management-dropdown')">
                Management
                <i class="fas fa-chevron-down"></i>
            </button>
            <div id="management-dropdown" class="hidden pl-4">
                <a href="manage_sitins.php" class="block py-2 text-gray-700">Manage Sit-ins</a>
                <a href="studentlist.php" class="block py-2 text-gray-700">Students</a>
                <a href="create_announcement.php" class="block py-2 text-gray-700">Announcements</a>
            </div>
        </div>
        
        <div class="py-2">
            <button class="flex items-center justify-between w-full text-gray-700" onclick="toggleMobileDropdown('reservations-dropdown')">
                Reservations
                <i class="fas fa-chevron-down"></i>
            </button>
            <div id="reservations-dropdown" class="hidden pl-4">
                <a href="manage_reservation.php" class="block py-2 text-gray-700">Reservations</a>
                <a href="reservation_logs.php" class="block py-2 text-gray-700">Reservation Logs</a>
            </div>
        </div>
        
        <div class="py-2">
            <button class="flex items-center justify-between w-full text-gray-700" onclick="toggleMobileDropdown('resources-dropdown')">
                Resources
                <i class="fas fa-chevron-down"></i>
            </button>
            <div id="resources-dropdown" class="hidden pl-4">
                <a href="admin_upload_resources.php" class="block py-2 text-gray-700">Upload Resources</a>
                <a href="admin_leaderboard.php" class="block py-2 text-gray-700">Leaderboard</a>
            </div>
        </div>
        
        <div class="py-2">
            <button class="flex items-center justify-between w-full text-gray-700" onclick="toggleMobileDropdown('labs-dropdown')">
                Labs
                <i class="fas fa-chevron-down"></i>
            </button>
            <div id="labs-dropdown" class="hidden pl-4">
                <a href="admin_lab_schedule.php" class="block py-2 text-gray-700">Lab Schedule</a>
                <a href="lab_management.php" class="block py-2 text-gray-700">Lab Management</a>
            </div>
        </div>
    </div>
</div>


<!-- Main Content - Adjusted for Navbar -->
<div class="pt-24 px-20 pb-8"> <!-- Added pt-24 to account for fixed navbar -->
    <div class="">        
                    <div class="flex justify-between items-center mb-6">
                <div>
                    <h2 class="text-2xl font-semibold mb-2 text-white">Sit-in History</h2>
                    <p class="text-slate-400">View your past and current sit-in sessions</p>
                </div>
            </div>

            <!-- History Table -->
            <div class="bg-slate-800/50 rounded-lg overflow-hidden border border-white/10 shadow-lg fade-in">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-slate-700/50">
                            <tr>
                                <th class="px-6 py-3 text-left text-sm font-semibold text-white">Purpose</th>
                                <th class="px-6 py-3 text-left text-sm font-semibold text-white">Lab</th>
                                <th class="px-6 py-3 text-left text-sm font-semibold text-white">Start Time</th>
                                <th class="px-6 py-3 text-left text-sm font-semibold text-white">End Time</th>
                                <th class="px-6 py-3 text-left text-sm font-semibold text-white">Feedback</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/10">
                            <?php if (empty($sit_in_history)) { ?>
                                <tr>
                                    <td colspan="5" class="px-6 py-4 text-center text-slate-400">No sit-in records found.</td>
                                </tr>
                            <?php } else { ?>
                                <?php foreach ($sit_in_history as $record) { ?>
                                    <tr class="hover:bg-slate-700/30 transition-colors duration-150">
                                        <td class="px-6 py-4 whitespace-nowrap"><?php echo htmlspecialchars($record['purpose']); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap"><?php echo htmlspecialchars($record['lab']); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap"><?php echo date("F d, Y - h:i A", strtotime($record['start_time'])); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <?php 
                                                echo $record['end_time'] ? 
                                                    date("F d, Y - h:i A", strtotime($record['end_time'])) : 
                                                    '<span class="text-red-400 font-semibold">Still Active</span>'; 
                                            ?>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div id="feedback-display-<?php echo $record['id']; ?>" class="flex items-center">
                                                <?php if (!empty($record['feedback'])) { ?>
                                                    <span class="text-slate-300"><?php echo htmlspecialchars($record['feedback']); ?></span>
                                                    <button onclick="toggleFeedbackForm(<?php echo $record['id']; ?>, event)" class="ml-2 text-blue-400 hover:text-blue-300">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                <?php } else { ?>
                                                    <button onclick="toggleFeedbackForm(<?php echo $record['id']; ?>, event)" class="text-blue-400 hover:text-blue-300">
                                                        <i class="fas fa-plus-circle"></i> Add Feedback
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
    </div>

    <!-- Feedback Overlays -->
    <?php foreach ($sit_in_history as $record) { ?>
        <div id="feedback-overlay-<?php echo $record['id']; ?>" class="feedback-overlay hidden">
            <div class="feedback-form-container">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-semibold"><?php echo empty($record['feedback']) ? 'Add Feedback' : 'Edit Feedback'; ?></h3>
                    <button onclick="toggleFeedbackForm(<?php echo $record['id']; ?>, event)" class="text-slate-400 hover:text-white">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                
                <form method="POST" class="space-y-4">
                    <input type="hidden" name="record_id" value="<?php echo $record['id']; ?>">
                    <div>
                        <textarea name="feedback" rows="4" class="w-full bg-slate-700 border border-slate-600 rounded px-3 py-2 text-white focus:outline-none focus:ring-1 focus:ring-blue-500"
                            placeholder="Enter your feedback..."><?php echo htmlspecialchars($record['feedback'] ?? ''); ?></textarea>
                    </div>
                    <div class="flex justify-end space-x-2">
                        <button type="button" onclick="toggleFeedbackForm(<?php echo $record['id']; ?>, event)" class="bg-slate-600 hover:bg-slate-700 text-white px-4 py-2 rounded">
                            Cancel
                        </button>
                        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded">
                            Save Feedback
                        </button>
                    </div>
                </form>
            </div>
        </div>
    <?php } ?>
</body>
</html>