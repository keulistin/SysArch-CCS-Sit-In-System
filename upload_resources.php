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
    $profile_picture = "default_avatar.png";
}

// Fetch resources available to students
$resources_query = "SELECT * FROM resources WHERE available_to = 'students' OR available_to = 'all' ORDER BY upload_date DESC";
$resources_result = mysqli_query($conn, $resources_query);

$page_title = "Lab Resources";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Resources - Student</title>
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
        .file-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2);
        }
        .file-icon {
            transition: all 0.3s ease;
        }
        .file-card:hover .file-icon {
            transform: scale(1.1);
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
                <a href="reservation.php" class="flex items-center px-5 py-3 text-slate-300 hover:bg-slate-700/20 hover:text-white transition-all duration-200">
                        <span>Reservation</span>
                    </a>
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
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-2xl font-semibold text-white">
                    <i class="fas fa-folder-open mr-2 text-blue-400"></i> Uploaded Resources
                </h2>
            </div>

            <!-- Resource Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                <?php while($resource = mysqli_fetch_assoc($resources_result)): ?>
                    <?php
                    // Get file icon based on type
                    $file_ext = pathinfo($resource['file_name'], PATHINFO_EXTENSION);
                    $icon = 'fa-file';
                    
                    if (in_array($file_ext, ['pdf'])) {
                        $icon = 'fa-file-pdf';
                    } elseif (in_array($file_ext, ['doc', 'docx'])) {
                        $icon = 'fa-file-word';
                    } elseif (in_array($file_ext, ['xls', 'xlsx'])) {
                        $icon = 'fa-file-excel';
                    } elseif (in_array($file_ext, ['ppt', 'pptx'])) {
                        $icon = 'fa-file-powerpoint';
                    } elseif (in_array($file_ext, ['zip', 'rar', '7z'])) {
                        $icon = 'fa-file-archive';
                    } elseif (in_array($file_ext, ['jpg', 'jpeg', 'png', 'gif'])) {
                        $icon = 'fa-file-image';
                    }
                    ?>
                    <div class="file-card bg-slate-700/50 rounded-lg p-4 border border-slate-600 hover:border-blue-500 transition-all duration-200 cursor-pointer">
                        <div class="flex flex-col items-center text-center">
                            <div class="file-icon bg-slate-600/50 rounded-full w-16 h-16 flex items-center justify-center mb-3">
                                <i class="fas <?php echo $icon; ?> text-2xl text-blue-400"></i>
                            </div>
                            <h4 class="font-medium text-sm mb-1 truncate w-full"><?php echo htmlspecialchars($resource['title']); ?></h4>
                            <p class="text-xs text-slate-400 mb-2"><?php echo formatSizeUnits($resource['file_size']); ?></p>
                            <div class="flex space-x-2">
                                <a href="download_resource.php?id=<?php echo $resource['id']; ?>" class="text-xs bg-blue-600/50 hover:bg-blue-600 px-2 py-1 rounded">
                                    <i class="fas fa-download mr-1"></i> Download
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>

            <?php if(mysqli_num_rows($resources_result) == 0): ?>
                <div class="text-center py-10 text-slate-400">
                    <i class="fas fa-folder-open text-4xl mb-3"></i>
                    <p>No resources available yet</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <?php
    // Helper function to format file sizes
    function formatSizeUnits($bytes) {
        if ($bytes >= 1073741824) {
            $bytes = number_format($bytes / 1073741824, 2) . ' GB';
        } elseif ($bytes >= 1048576) {
            $bytes = number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            $bytes = number_format($bytes / 1024, 2) . ' KB';
        } elseif ($bytes > 1) {
            $bytes = $bytes . ' bytes';
        } elseif ($bytes == 1) {
            $bytes = $bytes . ' byte';
        } else {
            $bytes = '0 bytes';
        }
        return $bytes;
    }
    ?>
</body>
</html>