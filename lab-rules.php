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

$page_title = "Lab Rules";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lab Rules - CCS SIT Monitoring System</title>
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
<body class="bg-gradient-to-br from-slate-800 to-slate-900 min-h-screen font-sans text-white">
    <!-- Sidebar -->
    <div class="fixed inset-y-0 left-0 w-64 bg-slate-900/80 backdrop-blur-md border-r border-white/10 shadow-xl z-50 flex flex-col">
        <!-- Fixed header -->
        <div class="p-5 border-b border-white/10 flex-shrink-0">
            <div class="flex items-center space-x-3">
                <!-- Profile Picture -->
                <img 
                    src="uploads/<?php echo htmlspecialchars($profile_picture); ?>" 
                    alt="Profile Picture" 
                    class="w-10 h-10 rounded-full border-2 border-white/10 object-cover"
                    onerror="this.src='assets/default_avatar.png'"
                >
                <!-- First Name -->
                <h2 class="text-xl font-semibold text-white"><?php echo htmlspecialchars($firstname); ?></h2>
            </div>
            <p class="text-sm text-slate-400 mt-2"><?php echo $page_title; ?></p>
        </div>
        
        <!-- Scrollable navigation -->
        <nav class="mt-5 flex-1 overflow-y-auto sidebar-scroll">
            <ul>
                <li>
                    <a href="student_dashboard.php" class="flex items-center px-5 py-3 text-slate-300 hover:bg-slate-700/20 hover:text-white transition-all duration-200 <?php echo basename($_SERVER['PHP_SELF']) === 'student_dashboard.php' ? 'bg-slate-700/20 text-white' : ''; ?>">
                        <span>Profile</span>
                    </a>
                </li>
                <li>
                    <a href="edit-profile.php" class="flex items-center px-5 py-3 text-slate-300 hover:bg-slate-700/20 hover:text-white transition-all duration-200 <?php echo basename($_SERVER['PHP_SELF']) === 'edit-profile.php' ? 'bg-slate-700/20 text-white' : ''; ?>">
                        <span>Edit Profile</span>
                    </a>
                </li>
                <li>
                    <a href="announcements.php" class="flex items-center px-5 py-3 text-slate-300 hover:bg-slate-700/20 hover:text-white transition-all duration-200 <?php echo basename($_SERVER['PHP_SELF']) === 'announcements.php' ? 'bg-slate-700/20 text-white' : ''; ?>">
                        <span>View Announcements</span>
                    </a>
                </li>
                <li>
                    <a href="sit-in-rules.php" class="flex items-center px-5 py-3 text-slate-300 hover:bg-slate-700/20 hover:text-white transition-all duration-200 <?php echo basename($_SERVER['PHP_SELF']) === 'sit-in-rules.php' ? 'bg-slate-700/20 text-white' : ''; ?>">
                        <span>Sit-in Rules</span>
                    </a>
                </li>
                <li>
                    <a href="lab-rules.php" class="flex items-center px-5 py-3 text-slate-300 hover:bg-slate-700/20 hover:text-white transition-all duration-200 <?php echo basename($_SERVER['PHP_SELF']) === 'lab-rules.php' ? 'bg-slate-700/20 text-white' : ''; ?>">
                        <span>Lab Rules & Regulations</span>
                    </a>
                </li>
                <li>
                    <a href="reservation.php" class="flex items-center px-5 py-3 text-slate-300 hover:bg-slate-700/20 hover:text-white transition-all duration-200 <?php echo basename($_SERVER['PHP_SELF']) === 'reservation.php' ? 'bg-slate-700/20 text-white' : ''; ?>">
                        <span>Reservation</span>
                    </a>
                </li>
                <li>
                    <a href="sit_in_history.php" class="flex items-center px-5 py-3 text-slate-300 hover:bg-slate-700/20 hover:text-white transition-all duration-200 <?php echo basename($_SERVER['PHP_SELF']) === 'sit_in_history.php' ? 'bg-slate-700/20 text-white' : ''; ?>">
                        <span>Sit-in History</span>
                    </a>
                </li>
                <li>
                    <a href="upload_resources.php" class="flex items-center px-5 py-3 text-slate-300 hover:bg-slate-700/20 hover:text-white transition-all duration-200 <?php echo basename($_SERVER['PHP_SELF']) === 'upload_resources.php' ? 'bg-slate-700/20 text-white' : ''; ?>">
                        <span>View Lab Resources</span>
                    </a>
                </li>
                <li>
                    <a href="student_leaderboard.php" class="flex items-center px-5 py-3 text-slate-300 hover:bg-slate-700/20 hover:text-white transition-all duration-200 <?php echo basename($_SERVER['PHP_SELF']) === 'student_leaderboard.php' ? 'bg-slate-700/20 text-white' : ''; ?>">
                        <span>Leaderboard</span>
                    </a>
                </li>
                <li>
                    <a href="student_lab_schedule.php" class="flex items-center px-5 py-3 text-slate-300 hover:bg-slate-700/20 hover:text-white transition-all duration-200 <?php echo basename($_SERVER['PHP_SELF']) === 'student_lab_schedule.php' ? 'bg-slate-700/20 text-white' : ''; ?>">
                        <span>Lab Schedule</span>
                    </a>
                </li>
            </ul>
        </nav>
        
        <!-- Fixed footer with logout -->
        <div class="p-5 border-t border-white/10 flex-shrink-0">
            <a href="logout.php" onclick="return confirm('Are you sure you want to log out?')" class="flex items-center px-5 py-3 text-slate-300 hover:bg-red-600/20 hover:text-red-400 transition-all duration-200">
                <span>Log Out</span>
            </a>
        </div>
    </div>

    <!-- Main Content -->
    <div class="ml-64 p-6">
        <div class="bg-slate-800/50 backdrop-blur-sm rounded-xl shadow-lg border border-white/5 p-6 hover:shadow-xl transition-all duration-300">
            <h2 class="text-2xl font-semibold mb-6 text-white border-b border-white/10 pb-2">📜 Laboratory Rules & Regulations</h2>
            <p class="text-slate-300 mb-6">University of Cebu - College of Information & Computer Studies</p>

            <!-- Rules Sections -->
            <div class="space-y-6">
                <!-- General Guidelines -->
                <div class="bg-slate-700/50 rounded-xl border border-white/5 p-6">
                    <h3 class="text-xl font-semibold mb-4 text-white">General Guidelines</h3>
                    <ul class="space-y-3">
                        <li class="flex items-start">
                            <i class="fas fa-volume-mute text-blue-400 mt-1 mr-3"></i>
                            <span>Maintain <strong>silence</strong> inside the lab.</span>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-gamepad text-blue-400 mt-1 mr-3"></i>
                            <span><strong>Gaming</strong> is not allowed in the lab.</span>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-globe text-blue-400 mt-1 mr-3"></i>
                            <span><strong>Internet surfing</strong> only with instructor's permission.</span>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-ban text-blue-400 mt-1 mr-3"></i>
                            <span><strong>Accessing illicit websites</strong> is strictly prohibited.</span>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-trash text-blue-400 mt-1 mr-3"></i>
                            <span>Do not <strong>delete files</strong> or change system settings.</span>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-clock text-blue-400 mt-1 mr-3"></i>
                            <span><strong>Use computer time wisely</strong> - 15-minute allowance.</span>
                        </li>
                    </ul>
                </div>

                <!-- Proper Conduct -->
                <div class="bg-slate-700/50 rounded-xl border border-white/5 p-6">
                    <h3 class="text-xl font-semibold mb-4 text-white">Proper Conduct</h3>
                    <ul class="space-y-3">
                        <li class="flex items-start">
                            <i class="fas fa-user text-blue-400 mt-1 mr-3"></i>
                            <span>Enter the lab <strong>only when the instructor is present</strong>.</span>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-box text-blue-400 mt-1 mr-3"></i>
                            <span><strong>Store bags</strong> at the designated area.</span>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-chair text-blue-400 mt-1 mr-3"></i>
                            <span><strong>Return chairs</strong> after using them.</span>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-mug-hot text-blue-400 mt-1 mr-3"></i>
                            <span><strong>No food, drinks, or gum</strong> inside the lab.</span>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-exclamation-triangle text-blue-400 mt-1 mr-3"></i>
                            <span><strong>Vandalism and misconduct</strong> will lead to disciplinary action.</span>
                        </li>
                    </ul>
                </div>

                <!-- Disciplinary Actions -->
                <div class="bg-slate-700/50 rounded-xl border border-white/5 p-6">
                    <h3 class="text-xl font-semibold mb-4 text-white">Disciplinary Actions</h3>
                    <ul class="space-y-3">
                        <li class="flex items-start">
                            <i class="fas fa-triangle-exclamation text-blue-400 mt-1 mr-3"></i>
                            <span><strong>First Offense</strong> - Warning & possible suspension.</span>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-hand-paper text-blue-400 mt-1 mr-3"></i>
                            <span><strong>Second Offense</strong> - Strict disciplinary action.</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <script>
        function confirmLogout(event) {
            event.preventDefault();
            if (confirm("Are you sure you want to log out?")) {
                window.location.href = 'logout.php';
            }
        }
    </script>
</body>
</html>