<?php
session_start();
include 'db.php';

// Ensure only admins can access
if (!isset($_SESSION["idno"]) || $_SESSION["role"] !== "admin") {
    header("Location: login.php");
    exit();
}

$idno = $_SESSION["idno"];
$stmt = $conn->prepare("SELECT firstname, lastname FROM users WHERE idno = ?");
$stmt->bind_param("s", $idno);
$stmt->execute();
$stmt->bind_result($firstname, $lastname);
$stmt->fetch();
$stmt->close();

// Get total registered students
$student_count_query = "SELECT COUNT(*) AS student_count FROM users WHERE role = 'student'";
$student_count_result = mysqli_query($conn, $student_count_query);
$student_count_row = mysqli_fetch_assoc($student_count_result);
$student_count = $student_count_row['student_count'];

// Get active sit-ins
$active_sitins_query = "SELECT COUNT(*) AS active_sit_ins FROM sit_in_records WHERE end_time IS NULL";
$active_sitins_result = mysqli_query($conn, $active_sitins_query);
$active_sitins_row = mysqli_fetch_assoc($active_sitins_result);
$active_sit_ins = $active_sitins_row['active_sit_ins'];

$search_result = ""; 
$search_open = false; // Track if the form should stay open
// Handle Student Search
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["search_query"])) {
    $search_query = trim($_POST["search_query"]);
    
    // Search by ID or name
    $stmt = $conn->prepare("SELECT id, idno, firstname, lastname, profile_picture FROM users WHERE (idno LIKE ? OR CONCAT(firstname, ' ', lastname) LIKE ?) AND role = 'student'");
    $search_param = "%$search_query%";
    $stmt->bind_param("ss", $search_param, $search_param);
    $stmt->execute();
    $stmt->store_result();
    
    if ($stmt->num_rows > 0) {
        $stmt->bind_result($id, $student_idno, $student_firstname, $student_lastname, $profile_picture);
        $stmt->fetch();
        $fullname = htmlspecialchars($student_firstname . ' ' . $student_lastname);
        
        // Get total sit-ins for this student
        $sitins_count = 0;
        $count_stmt = $conn->prepare("SELECT COUNT(*) FROM sit_in_records WHERE student_id = ?");
        $count_stmt->bind_param("i", $id);
        $count_stmt->execute();
        $count_stmt->bind_result($sitins_count);
        $count_stmt->fetch();
        $count_stmt->close();
        
        // Check survey completion status
        $survey_completed = false;
        $survey_stmt = $conn->prepare("SELECT survey_completed FROM users WHERE id = ?");
        $survey_stmt->bind_param("i", $id);
        $survey_stmt->execute();
        $survey_stmt->bind_result($survey_completed);
        $survey_stmt->fetch();
        $survey_stmt->close();
        
        // Check if student already has an active session
        $active_session_check = $conn->prepare("SELECT COUNT(*) FROM sit_in_records WHERE student_id = ? AND end_time IS NULL");
        $active_session_check->bind_param("i", $id);
        $active_session_check->execute();
        $active_session_check->bind_result($active_sessions);
        $active_session_check->fetch();
        $active_session_check->close();
        
        // Default profile picture if not set
        $profile_pic = $profile_picture ? 'uploads/'.$profile_picture : 'assets/default_avatar.png';
        
        // Always show student information
        $search_result = "
            <div class='flex items-start space-x-4'>
                <div class='flex-shrink-0'>
                    <img src='$profile_pic' alt='Profile Picture' class='w-16 h-16 rounded-full border-2 border-white/20 object-cover' onerror=\"this.src='assets/default_avatar.png'\">
                </div>
                <div class='flex-1 min-w-0'>
                    <h4 class='text-xl font-semibold text-green-400'>$fullname</h4>
                    <p class='text-sm text-slate-300'><strong>ID:</strong> $student_idno</p>
                    <p class='text-sm text-slate-400 mt-1'>Sit-ins completed: $sitins_count</p>
                </div>
            </div>";
        
        // Show warning if student has active session
        if ($active_sessions > 0) {
            $search_result .= "
            <div class='mt-4 p-4 bg-red-100 border border-red-700 rounded-md'>
                <div class='flex items-center'>
                    <i class='fas fa-exclamation-circle text-red-400 mr-2'></i>
                    <span class='font-medium text-red-300'>This student already has an active sit-in session.</span>
                </div>
                <p class='text-sm text-red-300 mt-2'>Please end the current session before logging a new one.</p>
            </div>";
        }
        
        // Add survey notification if applicable
        if ($sitins_count >= 10 && !$survey_completed) {
            $search_result .= "
            <div class='mt-4 p-4 bg-yellow-900/20 border border-yellow-700 rounded-md'>
                <div class='flex items-center'>
                    <i class='fas fa-exclamation-triangle text-yellow-400 mr-2'></i>
                    <span class='font-medium text-yellow-300'>This student hasn't completed their satisfaction survey yet.</span>
                </div>
                <p class='text-sm text-yellow-300 mt-2'>They must complete the survey before any new sit-ins can be logged.</p>
            </div>
            <div class='mt-4 p-4 bg-slate-700/50 rounded-md border border-slate-600'>
                <p class='text-slate-300 text-center'>Cannot log sit-in - Survey required</p>
            </div>";
        } 
        // Only show the sit-in form if:
        // 1. No active session exists
        // 2. Either survey is completed or not required yet (sitins_count < 10)
        elseif ($active_sessions == 0 && ($survey_completed || $sitins_count < 10)) {
            $search_result .= "
                <form action='log_sit_in.php' method='POST' class='mt-6 space-y-4' id='sitInForm'>
                    <input type='hidden' name='student_id' value='$student_idno'>
                    
                    <div class='grid grid-cols-1 md:grid-cols-2 gap-4'>
                        <div>
                            <label class='block text-sm font-medium text-slate-300 mb-1'>Purpose</label>
                            <select name='purpose' class='w-full p-2 bg-slate-700/50 border border-slate-600 rounded-md text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200' required>
                                <option value='' disabled selected>Select Purpose</option>
                                <option value='C Programming'>C Programming</option>
                                <option value='Java Programming'>Java Programming</option>
                                <option value='C# Programming'>C# Programming</option>
                                <option value='Systems Integration & Architecture'>Systems Integration & Architecture</option>
                                <option value='Embedded Systems & IoT'>Embedded Systems & IoT</option>
                                <option value='Computer Application'>Computer Application</option>
                                <option value='Database'>Database</option>
                                <option value='Project Management'>Project Management</option>
                                <option value='Python Programming'>Python Programming</option>
                                <option value='Mobile Appilication'>Mobile Appilication</option>
                                <option value='Web Design'>Web Design</option>
                                <option value='Php Programming'>Php Programming</option>
                                <option value='Other'>Others...</option>
                            </select>
                        </div>
                        
                        <div>
                            <label class='block text-sm font-medium text-slate-300 mb-1'>Lab</label>
                            <select name='lab' id='lab_room' class='w-full p-2 bg-slate-700/50 border border-slate-600 rounded-md text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200' required onchange='updatePcAvailability()'>
                                <option value='' disabled selected>Select Lab</option>
                                <option value='Lab 517'>Lab 517</option>
                                <option value='Lab 524'>Lab 524</option>
                                <option value='Lab 526'>Lab 526</option>
                                <option value='Lab 528'>Lab 528</option>
                                <option value='Lab 530'>Lab 530</option>
                                <option value='Lab 542'>Lab 542</option>
                                <option value='Lab 544'>Lab 544</option>
                            </select>
                        </div>
                    </div>
                    
                    <div id='pcSelectionContainer' class='hidden'>
                        <label class='block text-sm font-medium text-slate-300 mb-1'>Select PC (Optional)</label>
                        <div id='pcGrid' class='grid grid-cols-4 gap-2 mb-2 max-h-40 overflow-y-auto p-2 bg-slate-800/50 rounded-md'>
                            <!-- PCs will be loaded here via AJAX -->
                        </div>
                        <input type='hidden' name='pc_number' id='selectedPc'>
                    </div>
                    
                    <div class='pt-2'>
                        <button type='submit' class='w-full flex items-center justify-center p-2 bg-gradient-to-r from-blue-600 to-blue-500 text-white rounded-md hover:from-blue-700 hover:to-blue-600 transition-all duration-200 shadow-md'>
                            <i class='fa-solid fa-check mr-2'></i> Log Sit-in
                        </button>
                    </div>
                </form>";
        }
    } else {
        $search_result = "
            <div class='text-center py-6'>
                <i class='fas fa-user-slash text-4xl text-red-400 mb-3'></i>
                <p class='text-red-400 text-lg'>Student not found</p>
                <p class='text-slate-400 text-sm mt-1'>Please check the ID or name and try again</p>
            </div>";
    }
    $search_open = true;



    
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
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
        .main-content-cont{
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
        .pc-card {
            transition: all 0.2s ease;
        }
        .pc-card:hover {
            transform: translateY(-2px);
        }
        .status-available {
            background-color: rgba(16, 185, 129, 0.1);
            border-color: rgba(16, 185, 129, 0.3);
        }
        .status-used {
            background-color: rgba(239, 68, 68, 0.1);
            border-color: rgba(239, 68, 68, 0.3);
        }
        .status-maintenance {
            background-color: rgba(245, 158, 11, 0.1);
            border-color: rgba(245, 158, 11, 0.3);
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
            <div class="relative">
                <button onclick="toggleDropdown('recordsDropdown')" class="px-4 py-2 text-gray-700 font-medium hover:bg-gray-100 rounded-md transition-all duration-200 flex items-center">
                    Records <i class="fas fa-chevron-down ml-2 text-xs"></i>
                </button>
                <div id="recordsDropdown" class="nav-dropdown absolute left-0 mt-2 w-48 bg-white rounded-md shadow-lg py-1 z-10 hidden">
                    <a href="todays_sitins.php" class="block px-4 py-2 text-gray-700 hover:bg-gray-100">Current Sit-ins</a>
                    <a href="sit_in_records.php" class="block px-4 py-2 text-gray-700 hover:bg-gray-100">Sit-in Reports</a>
                    <a href="feedback_records.php" class="block px-4 py-2 text-gray-700 hover:bg-gray-100">Feedback Reports</a>
                </div>
            </div>

            
            <!-- Management Dropdown -->
            <div class="relative">
                <button onclick="toggleDropdown('managementDropdown')" class="px-4 py-2 text-gray-700 font-medium hover:bg-gray-100 rounded-md transition-all duration-200">
                    Management <i class="fas fa-chevron-down ml-2 text-xs"></i>
                </button>
                <div id="managementDropdown" class="nav-dropdown absolute left-0 mt-2 w-48 bg-white rounded-md shadow-lg py-1 z-10 hidden">
                    <a href="manage_sitins.php" class="block px-4 py-2 text-gray-700 hover:bg-gray-100">Manage Sit-ins</a>
                    <a href="studentlist.php" class="block px-4 py-2 text-gray-700 hover:bg-gray-100">Students</a>
                    <a href="create_announcement.php" class="block px-4 py-2 text-gray-700 hover:bg-gray-100">Announcements</a>
                </div>
            </div>

            
            <!-- Reservations Dropdown -->
            <div class="relative">
                <button onclick="toggleDropdown('reservationsDropdown')" class="px-4 py-2 text-gray-700 font-medium hover:bg-gray-100 rounded-md transition-all duration-200">
                    Reservations <i class="fas fa-chevron-down ml-2 text-xs"></i>
                </button>
                <div id="reservationsDropdown" class="nav-dropdown absolute left-0 mt-2 w-48 bg-white rounded-md shadow-lg py-1 z-10 hidden">
                    <a href="manage_reservation.php" class="block px-4 py-2 text-gray-700 hover:bg-gray-100">Reservations</a>
                    <a href="reservation_logs.php" class="block px-4 py-2 text-gray-700 hover:bg-gray-100">Reservation Logs</a>
                </div>
            </div>
            
            <!-- Resources Dropdown -->
            <div class="relative">
                <button onclick="toggleDropdown('resourcesDropdown')" class="px-4 py-2 text-gray-700 font-medium hover:bg-gray-100 rounded-md transition-all duration-200">
                    Resources <i class="fas fa-chevron-down ml-2 text-xs"></i>
                </button>
                <div id="resourcesDropdown" class="nav-dropdown absolute left-0 mt-2 w-48 bg-white rounded-md shadow-lg py-1 z-10 hidden">
                    <a href="admin_upload_resources.php" class="block px-4 py-2 text-gray-700 hover:bg-gray-100">Upload Resources</a>
                    <a href="admin_leaderboard.php" class="block px-4 py-2 text-gray-700 hover:bg-gray-100">Leaderboard</a>
                </div>
            </div>

            
            <!-- Labs Dropdown -->
            <div class="relative">
                <button onclick="toggleDropdown('labsDropdown')" class="px-4 py-2 text-gray-700 font-medium hover:bg-gray-100 rounded-md transition-all duration-200">
                    Labs <i class="fas fa-chevron-down ml-2 text-xs"></i>
                </button>
                <div id="labsDropdown" class="nav-dropdown absolute left-0 mt-2 w-48 bg-white rounded-md shadow-lg py-1 z-10 hidden">
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
                <h2 class="px-4 py-2 text-gray-700 font-bold"><?php echo htmlspecialchars($firstname); ?></h2>
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


<!-- Main Content - Elegant Admin Dashboard -->
<div class="min-h-screen bg-purple-100 main-content-cont">
    <!-- Welcome Header with Subtle Accents -->
    <div class="mb-12">
        <div class="flex items-center mb-2">
            <h2 class="text-3xl font-medium text-gray-800 tracking-tight">Welcome, <?php echo htmlspecialchars($firstname); ?>!</h2>
        </div>
        <p class="text-gray-500 font-light">Manage lab resources and student activities with precision</p>
        <div class="w-16 h-1 bg-gradient-to-r from-purple-400 to-indigo-500 mt-4 rounded-full"></div>
    </div>
    
    <!-- Statistics Cards - Sophisticated Design -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-12">
        <!-- Student Count Card -->
        <div class="bg-white p-8 rounded-lg border border-gray-100 shadow-xs hover:shadow-sm transition-all duration-300 transform hover:-translate-y-1">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500 uppercase tracking-wider">Registered Students</p>
                    <h3 class="text-5xl font-light text-gray-800 mt-2"><?php echo $student_count; ?></h3>
                </div>
                <div class="p-3 rounded-full bg-purple-50 text-purple-600">
                    <i class="fas fa-users text-xl"></i>
                </div>
            </div>
        </div>
        
        <!-- Active Sit-ins Card -->
        <div class="bg-white p-8 rounded-lg border border-gray-100 shadow-xs hover:shadow-sm transition-all duration-300 transform hover:-translate-y-1">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500 uppercase tracking-wider">Active Sit-ins</p>
                    <h3 class="text-5xl font-light text-gray-800 mt-2"><?php echo $active_sit_ins; ?></h3>
                </div>
                <div class="p-3 rounded-full bg-blue-50 text-blue-600">
                    <i class="fas fa-laptop-code text-xl"></i>
                </div>
            </div>
        </div>
        
        <!-- Add a third card for completeness -->
        <div class="bg-white p-8 rounded-lg border border-gray-100 shadow-xs hover:shadow-sm transition-all duration-300 transform hover:-translate-y-1">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500 uppercase tracking-wider">Pending Reservations</p>
                    <h3 class="text-5xl font-light text-gray-800 mt-2">14</h3>
                </div>
                <div class="p-3 rounded-full bg-amber-50 text-amber-600">
                    <i class="fas fa-clock text-xl"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Search Section - Elevated Design -->
    <div class="bg-white rounded-xl shadow-xs border border-gray-100 p-8 mb-12">
        <div class="mb-8">
            <div class="flex items-center mb-3">
                <div class="w-8 h-8 rounded-full bg-purple-100 flex items-center justify-center text-purple-600 mr-3">
                    <i class="fas fa-search text-sm"></i>
                </div>
                <h4 class="text-xl font-medium text-gray-800 tracking-tight">Student Search</h4>
            </div>
            <p class="text-gray-500 font-light pl-11">Find students by ID, name, or course</p>
        </div>
        
        <form method="POST" class="space-y-6">
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <i class="fas fa-user text-gray-400"></i>
                </div>
                <input 
                    type="text" 
                    name="search_query" 
                    class="w-full pl-10 pr-4 py-3 bg-gray-50 border border-gray-200 rounded-lg text-gray-700 placeholder-gray-400 focus:ring-2 focus:ring-purple-200 focus:border-transparent transition-all duration-200" 
                    placeholder="Student ID, name, or course"
                    required
                    autocomplete="off"
                >
            </div>
            
            <button type="submit" class="w-full flex items-center justify-center px-6 py-3 bg-gradient-to-r from-purple-600 to-indigo-600 text-white rounded-lg hover:from-purple-700 hover:to-indigo-700 transition-all duration-200 shadow-sm">
                <i class="fas fa-magnifying-glass mr-2"></i> Search Student
            </button>
        </form>
    </div>
</div>

<!-- Search Result Modal - Luxury Design -->
<div id="overlay" class="fixed inset-0 bg-black/20 backdrop-blur-sm hidden z-50 transition-opacity duration-300 ease-in-out"></div>
<div id="searchResultModal" class="fixed inset-0 flex items-center justify-center hidden z-50 p-6">
    <div class="bg-white rounded-xl shadow-2xl p-0 w-full max-w-md transform transition-all duration-300 ease-out scale-95 opacity-0 overflow-hidden">

        <div id="searchResultContent" class="p-6 text-gray-300 max-h-[60vh] overflow-y-auto">
            <?php echo $search_result; ?>
        </div>
        <div class="px-6 pb-6">
            <button onclick="closeSearchResultModal()" class="w-full px-6 py-2.5 border border-gray-200 text-gray-600 rounded-lg hover:bg-gray-50 transition-all duration-200">
                <i class="fas fa-times mr-2"></i> Close
            </button>
        </div>
    </div>
</div>


<script>
// Modal animation
function showSearchResultModal() {
    const overlay = document.getElementById('overlay');
    const modal = document.getElementById('searchResultModal');
    const modalContent = modal.querySelector('div');
    
    overlay.classList.remove('hidden');
    modal.classList.remove('hidden');
    
    setTimeout(() => {
        overlay.classList.add('opacity-100');
        modalContent.classList.remove('scale-95', 'opacity-0');
        modalContent.classList.add('scale-100', 'opacity-100');
    }, 10);
}

function closeSearchResultModal() {
    const overlay = document.getElementById('overlay');
    const modal = document.getElementById('searchResultModal');
    const modalContent = modal.querySelector('div');
    
    modalContent.classList.remove('scale-100', 'opacity-100');
    modalContent.classList.add('scale-95', 'opacity-0');
    overlay.classList.remove('opacity-100');
    
    setTimeout(() => {
        overlay.classList.add('hidden');
        modal.classList.add('hidden');
    }, 300);
}

    function toggleDropdown(id) {
  const dropdown = document.getElementById(id);
  dropdown.classList.toggle('hidden');
  document.querySelectorAll('.nav-dropdown').forEach(el => {
    if (el.id !== id) el.classList.add('hidden');
  });
}

document.addEventListener('click', function(event) {
  if (!event.target.closest('.relative')) {
    document.querySelectorAll('.nav-dropdown').forEach(el => el.classList.add('hidden'));
  }
});

        // Toggle mobile menu
        document.getElementById('mobile-menu-button').addEventListener('click', function() {
                const menu = document.getElementById('mobile-menu');
                menu.classList.toggle('hidden');
            });

            // Toggle mobile dropdowns
            function toggleMobileDropdown(id) {
                const dropdown = document.getElementById(id);
                dropdown.classList.toggle('hidden');
            }

            // Close dropdowns when clicking outside
            document.addEventListener('click', function(event) {
                if (!event.target.matches('.group *')) {
                    const dropdowns = document.querySelectorAll('.group .absolute');
                    dropdowns.forEach(dropdown => {
                        dropdown.classList.add('hidden');
                    });
                }
            });


        // Open search result modal with animation
        function openSearchResultModal() {
            document.getElementById("overlay").classList.remove("hidden");
            document.getElementById("searchResultModal").classList.remove("hidden");
            
            // Trigger animation
            setTimeout(() => {
                document.getElementById("searchResultModal").querySelector('div').classList.remove('scale-95', 'opacity-0');
                document.getElementById("searchResultModal").querySelector('div').classList.add('scale-100', 'opacity-100');
            }, 10);
        }

        // Close search result modal with animation
        function closeSearchResultModal() {
            document.getElementById("searchResultModal").querySelector('div').classList.remove('scale-100', 'opacity-100');
            document.getElementById("searchResultModal").querySelector('div').classList.add('scale-95', 'opacity-0');
            
            setTimeout(() => {
                document.getElementById("overlay").classList.add("hidden");
                document.getElementById("searchResultModal").classList.add("hidden");
            }, 300);
        }

        // Close modal when clicking outside
        document.getElementById('overlay').addEventListener('click', closeSearchResultModal);

        // Open modal if search was performed
        <?php if ($search_open) { ?>
            window.onload = function() {
                openSearchResultModal();
            };
        <?php } ?>

        // Function to update PC availability when lab is selected
        function updatePcAvailability() {
            const labSelect = document.getElementById('lab_room');
            const labName = labSelect.value;
            const pcContainer = document.getElementById('pcSelectionContainer');
            const pcGrid = document.getElementById('pcGrid');
            const selectedPc = document.getElementById('selectedPc');
            
            if (labName) {
                // Show loading state
                pcGrid.innerHTML = '<div class="col-span-4 text-center py-4"><i class="fas fa-spinner fa-spin mr-2"></i> Loading PCs...</div>';
                pcContainer.classList.remove('hidden');
                
                // Load PCs via AJAX
                fetch(`get_pcs.php?lab=${encodeURIComponent(labName)}`, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.text();
                })
                .then(data => {
                    pcGrid.innerHTML = data;
                })
                .catch(error => {
                    console.error('Error:', error);
                    pcGrid.innerHTML = `
                        <div class="col-span-4 text-center py-4 text-red-400">
                            <i class="fas fa-exclamation-triangle mr-2"></i>
                            Failed to load PCs. Please try again.
                        </div>
                    `;
                });
            } else {
                pcContainer.classList.add('hidden');
                selectedPc.value = '';
            }
        }
        
        // Function to select a PC
        function selectPc(pcNumber) {
            const pcCards = document.querySelectorAll('#pcGrid .pc-card');
            const selectedPc = document.getElementById('selectedPc');
            
            pcCards.forEach(card => {
                if (parseInt(card.dataset.pcNumber) === pcNumber) {
                    card.classList.add('border-blue-500', 'bg-blue-900/20');
                    selectedPc.value = pcNumber;
                } else {
                    card.classList.remove('border-blue-500', 'bg-blue-900/20');
                }
            });
        }

        // Handle form submission
        document.getElementById('sitInForm')?.addEventListener('submit', function(e) {
            // You can add any additional form validation here if needed
        });
    </script>
</body>
</html>