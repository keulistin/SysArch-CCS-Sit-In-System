<?php
session_start();
include 'db.php';

// Check if the user is logged in
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
    } else {
        $profile_picture = "default_avatar.png";
    }
}

// Fetch all announcements
$announcement_query = "SELECT * FROM announcements ORDER BY created_at DESC";
$announcement_result = mysqli_query($conn, $announcement_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Announcements</title>
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
    </script>
    <style>
        body {
            background-color: #F1E6EF;
        }
        .main-content-cont {
            padding: 8rem 15rem 5rem 15rem;
        }
        .announcement-card {
            transition: all 0.3s ease;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        .announcement-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
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
<div class="min-h-screen bg-purple-50 pt-24 pb-8 px-20">
    <!-- Welcome Header with Subtle Accents -->
    <div class="mb-8">
        <div class="flex items-center mb-2">
            <h2 class="text-3xl font-medium text-gray-800 tracking-tight flex items-center">
                Announcements <span class="ml-3 text-2xl">📢</span>
            </h2>
        </div>
        <p class="text-gray-500 font-light">Stay informed with the latest updates from CCS</p>
        <div class="w-16 h-1 bg-gradient-to-r from-purple-400 to-indigo-500 mt-4 rounded-full"></div>
    </div>

    <?php if (mysqli_num_rows($announcement_result) > 0): ?>
        <div class="space-y-6">
            <?php while ($announcement = mysqli_fetch_assoc($announcement_result)): ?>
                <div class="announcement-card bg-white p-6 rounded-lg shadow-sm border border-purple-100">
                    <div class="flex justify-between items-start mb-2">
                        <h3 class="text-xl font-semibold text-gray-800"><?php echo htmlspecialchars($announcement['title']); ?></h3>
                        <span class="text-sm text-gray-500">
                            <i class="far fa-calendar-alt mr-1"></i>
                            <?php echo date('M j, Y', strtotime($announcement['created_at'])); ?>
                        </span>
                    </div>
                    <div class="prose max-w-none text-gray-600 mb-4">
                        <?php echo nl2br(htmlspecialchars($announcement['message'])); ?>
                    </div>
                    <div class="text-sm text-gray-400">
                        <i class="far fa-clock mr-1"></i>
                        Posted at <?php echo date('g:i A', strtotime($announcement['created_at'])); ?>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    <?php else: ?>
        <div class="bg-white p-8 rounded-lg shadow-sm border border-purple-100 text-center">
            <i class="fas fa-bullhorn text-4xl text-gray-300 mb-4"></i>
            <h3 class="text-xl font-medium text-gray-700 mb-2">No announcements yet</h3>
            <p class="text-gray-500">Check back later for updates from CCS</p>
        </div>
    <?php endif; ?>
</div>

<script>
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

    // Mobile menu toggle
    document.getElementById('mobile-menu-button').addEventListener('click', function() {
        document.getElementById('mobile-menu').classList.toggle('hidden');
    });

    // Close dropdowns when clicking outside
    document.addEventListener('click', function(event) {
        if (!event.target.closest('.relative') && !event.target.closest('#mobile-menu')) {
            document.querySelectorAll('.nav-dropdown').forEach(el => el.classList.add('hidden'));
        }
    });
</script>
</body>
</html>