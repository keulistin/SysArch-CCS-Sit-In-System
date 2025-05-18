<?php
session_start();
include 'db.php';

// Check if the user is logged in
if (!isset($_SESSION['idno'])) {
    header("Location: login.php");
    exit();
}

// Initialize variables with default values
$firstname = $lastname = $profile_picture = $remaining_sessions = $idno = $course = $yearlevel = $email = '';
$profile_picture = 'default_avatar.png';

if (isset($_SESSION['idno'])) {
    $idno = $_SESSION['idno'];
    
    // Fetch student info with additional fields
    $user_query = "SELECT firstname, lastname, profile_picture, remaining_sessions, idno, course, yearlevel, email FROM users WHERE idno = ?";
    $stmt = $conn->prepare($user_query);
    $stmt->bind_param("s", $idno);
    $stmt->execute();
    $stmt->bind_result($firstname, $lastname, $profile_picture, $remaining_sessions, $idno, $course, $yearlevel, $email);
    $stmt->fetch();
    $stmt->close();

    // Set default profile picture if none exists
    if (empty($profile_picture)) {
        $upload_dir = 'uploads/';
        $images = glob($upload_dir . '*.{jpg,jpeg,png,gif}', GLOB_BRACE);

        if (!empty($images)) {
            $random_image = $images[array_rand($images)];
            $profile_picture = basename($random_image);

            $update_sql = "UPDATE users SET profile_picture = ? WHERE idno = ?";
            if ($update_stmt = $conn->prepare($update_sql)) {
                $update_stmt->bind_param("ss", $profile_picture, $idno);
                $update_stmt->execute();
                $update_stmt->close();
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard</title>
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
                    colors: {
                        primary: '#123458',
                        secondary: '#D4C9BE',
                        light: '#F1EFEC',
                        dark: '#030303',
                    }
                },
            },
        }
    </script>
    <style>
        .notification-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background-color: #ef4444;
            color: white;
            border-radius: 50%;
            width: 18px;
            height: 18px;
            font-size: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .notification-dropdown {
            max-height: 400px;
            overflow-y: auto;
        }
        .notification-item {
            border-bottom: 1px solid #D4C9BE;
        }
        .notification-item:last-child {
            border-bottom: none;
        }
        
        .dropdown-menu {
            display: none;
            position: absolute;
            right: 0;
            background-color: #F1EFEC;
            min-width: 200px;
            box-shadow: 0px 8px 16px 0px rgba(0,0,0,0.2);
            z-index: 1;
            border-radius: 0.5rem;
            border: 1px solid #D4C9BE;
        }
        
        .dropdown-menu a {
            color: #030303;
            padding: 12px 16px;
            text-decoration: none;
            display: block;
        }
        
        .dropdown-menu a:hover {
            background-color: rgba(212, 201, 190, 0.3);
        }
        
        .show {
            display: block;
        }
        
        body {
            background-color: #F1E6EF;
            min-height: 100vh;
        }
        .main-content-cont {
            padding: 8rem 15rem 5rem 15rem;
        }
        
        .topnav {
            background-color: #123458;
            color: #F1EFEC;
        }
        
        .card {
            background-color: white;
            border: 1px solid #D4C9BE;
        }
        
        .hover-effect:hover {
            background-color: rgba(212, 201, 190, 0.3);
        }
        
        .mobile-menu {
            display: none;
        }
        
        @media (max-width: 768px) {
            .mobile-menu {
                display: block;
            }
            .desktop-menu {
                display: none;
            }
        }
        
        /* New dropdown styles */
        .nav-dropdown {
            position: relative;
        }
        
        .nav-dropdown-content {
            display: none;
            position: absolute;
            background-color: #123458;
            min-width: 200px;
            box-shadow: 0px 8px 16px 0px rgba(0,0,0,0.2);
            z-index: 1;
            border-radius: 0 0 0.5rem 0.5rem;
        }
        
        .nav-dropdown-content a {
            color: #F1EFEC;
            padding: 12px 16px;
            text-decoration: none;
            display: block;
            font-size: 0.875rem;
        }
        
        .nav-dropdown-content a:hover {
            background-color: rgba(255,255,255,0.1);
        }
        
        .nav-dropdown:hover .nav-dropdown-content {
            display: block;
        }
        
        .nav-dropdown-btn {
            display: flex;
            align-items: center;
        }
        
        .nav-dropdown-btn::after {
            content: "▾";
            margin-left: 5px;
            font-size: 0.8em;
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


    <!-- Main Content - Student Dashboard -->
<div class="min-h-screen bg-purple-100 main-content-cont">
        <!-- Welcome Header -->
        <div class="mb-12">
            <div class="flex items-center mb-2">
                <h2 class="text-3xl font-medium text-gray-800 tracking-tight">Welcome, <?php echo htmlspecialchars($firstname); ?>!</h2>
            </div>
            <p class="text-gray-500 font-light">Track your lab sessions and reservations</p>
            <div class="w-16 h-1 bg-gradient-to-r from-purple-400 to-indigo-500 mt-4 rounded-full"></div>
        </div>
        
        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-12">
            <!-- Remaining Sessions Card -->
            <div class="bg-white p-8 rounded-lg border border-gray-100 shadow-xs hover:shadow-sm transition-all duration-300 transform hover:-translate-y-1">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-500 uppercase tracking-wider">Remaining Sessions</p>
                        <h3 class="text-5xl font-light text-gray-800 mt-2"><?php echo $remaining_sessions; ?></h3>
                        <p class="text-sm text-gray-500 mt-1">out of 10 weekly sessions</p>
                    </div>
                    <div class="p-3 rounded-full bg-purple-50 text-purple-600">
                        <i class="fas fa-hourglass-half text-xl"></i>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-xl shadow-xs border border-gray-100 p-8 hover:shadow-sm transition-all duration-300">
                <div class="mb-6">
                    <div class="flex items-center mb-3">
                        <div class="w-8 h-8 rounded-full bg-green-100 flex items-center justify-center text-green-600 mr-3">
                            <i class="fas fa-calendar-check text-sm"></i>
                        </div>
                        <h4 class="text-xl font-medium text-gray-800 tracking-tight">Lab Reservation</h4>
                    </div>
                    <p class="text-gray-500 font-light pl-11">Book a lab in advance</p>
                </div>
                
                <a href="reservation.php" class="w-full flex items-center justify-center px-6 py-3 bg-gradient-to-r from-green-600 to-green-500 text-white rounded-lg hover:from-green-700 hover:to-green-600 transition-all duration-200 shadow-sm">
                    <i class="fas fa-calendar-plus mr-2"></i> Make Reservation
                </a>
            </div>
        </div>

        <!-- Quick Actions Section -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-12">

            
            <!-- New Reservation Card -->

        </div>


    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Mobile menu toggle
            const mobileMenuButton = document.getElementById('mobileMenuButton');
            const mobileMenu = document.getElementById('mobileMenu');
            
            mobileMenuButton.addEventListener('click', function() {
                mobileMenu.classList.toggle('hidden');
            });
            
            // Profile dropdown toggle
            const userMenuButton = document.getElementById('userMenuButton');
            const userDropdown = document.getElementById('userDropdown');

            userMenuButton.addEventListener('click', function(e) {
                e.stopPropagation();
                userDropdown.classList.toggle('hidden'); // Changed from 'show' to 'hidden'
            });
            
            // Close dropdowns when clicking outside
            document.addEventListener('click', function() {
                userDropdown.classList.add('hidden'); // Changed from removing 'show' to adding 'hidden'
                notificationDropdown.classList.add('hidden');
            });
            
            // Notification functionality
            const notificationButton = document.getElementById('notificationButton');
            const notificationDropdown = document.getElementById('notificationDropdown');
            const notificationBadge = document.querySelector('.notification-badge');
            
            // Toggle notification dropdown
            notificationButton.addEventListener('click', function(e) {
                e.stopPropagation();
                notificationDropdown.classList.toggle('hidden');
                loadNotifications();
            });
            
            // Prevent dropdown from closing when clicking inside
            notificationDropdown.addEventListener('click', function(e) {
                e.stopPropagation();
            });
            
            // Mark all as read
            document.getElementById('markAllRead').addEventListener('click', function() {
                markAllNotificationsAsRead();
            });
            
            // Function to load notifications
            function loadNotifications() {
                fetch('get_notifications.php')
                    .then(response => response.json())
                    .then(data => {
                        const notificationList = document.getElementById('notificationList');
                        
                        if (data.length === 0) {
                            notificationList.innerHTML = '<div class="p-3 text-center text-secondary">No notifications</div>';
                            notificationBadge.classList.add('hidden');
                            return;
                        }
                        
                        notificationList.innerHTML = '';
                        let unreadCount = 0;
                        
                        data.forEach(notification => {
                            const notificationItem = document.createElement('div');
                            notificationItem.className = `p-3 notification-item ${notification.is_read ? 'text-secondary' : 'text-dark bg-secondary/50'}`;
                            notificationItem.innerHTML = `
                                <div class="flex justify-between items-start">
                                    <div class="flex-1">
                                        <p class="text-sm text-slate-700">${notification.message}</p>
                                        <p class="text-xs text-gray-500 mt-1">${notification.created_at}</p>
                                    </div>
                                    ${notification.is_read ? '' : '<span class="w-2 h-2 rounded-full bg-primary ml-2"></span>'}
                                </div>
                            `;
                            notificationList.appendChild(notificationItem);
                            
                            if (!notification.is_read) {
                                unreadCount++;
                            }
                        });
                        
                        if (unreadCount > 0) {
                            notificationBadge.textContent = unreadCount;
                            notificationBadge.classList.remove('hidden');
                        } else {
                            notificationBadge.classList.add('hidden');
                        }
                    });
            }
            
            // Function to mark all notifications as read
            function markAllNotificationsAsRead() {
                fetch('mark_notifications_read.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        loadNotifications();
                    }
                });
            }
            
            // Load notifications on page load
            loadNotifications();

            // Check for new notifications every 30 seconds
            setInterval(loadNotifications, 30000);
        });

         // JavaScript for Navbar Dropdowns (Desktop)
        function toggleDropdown(dropdownId) {
            const allDropdowns = document.querySelectorAll('.nav-dropdown');
            allDropdowns.forEach(function(dd) {
                if (dd.id !== dropdownId) {
                    dd.classList.add('hidden');
                }
            });
            const targetDropdown = document.getElementById(dropdownId);
            if (targetDropdown) {
                targetDropdown.classList.toggle('hidden');
            }
        }

        document.addEventListener('click', function(event) {
            let clickedElement = event.target;
            let isDropdownButtonOrInsideDropdown = false;
            while (clickedElement != null) {
                if (clickedElement.matches('button[onclick^="toggleDropdown"]') || (clickedElement.classList && clickedElement.classList.contains('nav-dropdown') && !clickedElement.classList.contains('hidden'))) {
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

        // Mobile Menu Toggle
        const mobileMenuButtonStudent = document.getElementById('mobile-menu-button-student');
        const mobileMenuStudent = document.getElementById('mobile-menu-student');
        if (mobileMenuButtonStudent && mobileMenuStudent) {
            mobileMenuButtonStudent.addEventListener('click', (event) => {
                event.stopPropagation();
                mobileMenuStudent.classList.toggle('hidden');
            });
        }

        function toggleMobileDropdown(dropdownId) {
            const dropdown = document.getElementById(dropdownId);
            if (dropdown) {
                document.querySelectorAll('#mobile-menu-student div[id^="mobile"][id$="DropdownStudentNav"]').forEach(el => {
                    if (el.id !== dropdownId && !el.classList.contains('hidden')) {
                        el.classList.add('hidden');
                    }
                });
                dropdown.classList.toggle('hidden');
            }
        }
    </script>
</body>
</html>