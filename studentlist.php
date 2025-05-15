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


// Initialize students array
$students = [];

// Handle reset all sessions
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["reset_all_sessions"])) {
    $reset_query = "UPDATE users SET remaining_sessions = 30 WHERE role = 'student'";
    if ($conn->query($reset_query)) {
        $_SESSION['success_message'] = "All student sessions have been reset!";
    } else {
        $_SESSION['error_message'] = "Error resetting sessions: " . $conn->error;
    }
}

// Handle individual session reset
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["reset_student_session"])) {
    $student_id = $_POST["student_id"];
    $reset_query = "UPDATE users SET remaining_sessions = 30 WHERE idno = ?";
    $stmt = $conn->prepare($reset_query);
    $stmt->bind_param("s", $student_id);
    if ($stmt->execute()) {
        $_SESSION['success_message'] = "Session for student ID $student_id has been reset!";
    } else {
        $_SESSION['error_message'] = "Error resetting session: " . $stmt->error;
    }
    $stmt->close();
}

// Fetch all students from the database (exclude admins)
$sql = "SELECT idno, firstname, lastname, middlename, course, yearlevel, email, profile_picture, remaining_sessions FROM users WHERE role = 'student' ORDER BY lastname, firstname ASC";
$result = $conn->query($sql);

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $students[] = $row;
    }
} else {
    $_SESSION['error_message'] = "Error fetching students: " . $conn->error;
    $students = []; // Ensure it's an empty array even if there's an error
}

// Close connection
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>List of Students</title>
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
    </style>
</head>
<body class="bg-gradient-to-br from-slate-800 to-slate-900 min-h-screen font-sans text-white">
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

<!-- Main Content -->
<div class="min-h-screen bg-purple-100 main-content-cont">
    <div>
        <!-- Header Section with Stats -->
        <div class="mb-8">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-6">
                <div>
                    <h2 class="text-3xl font-medium text-gray-800 tracking-tight">Student Management</h2>
                    <p class="text-gray-500 font-light mt-2">Manage student records and session allocations</p>
                </div>
                
                <!-- Quick Stats -->
                <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mt-4 md:mt-0">
                    <div class="bg-white p-3 rounded-lg border border-gray-200 shadow-xs">
                        <p class="text-xs text-gray-500">Total Students</p>
                        <p class="text-xl font-semibold text-purple-600"><?= count($students) ?></p>
                    </div>
                    <div class="bg-white p-3 rounded-lg border border-gray-200 shadow-xs">
                        <p class="text-xs text-gray-500">Active Today</p>
                        <p class="text-xl font-semibold text-indigo-600"><?= array_reduce($students, function($carry, $student) { 
                            return $carry + ($student['remaining_sessions'] > 0 ? 1 : 0); 
                        }, 0) ?></p>
                    </div>
                    <div class="bg-white p-3 rounded-lg border border-gray-200 shadow-xs">
                        <p class="text-xs text-gray-500">Sessions Used</p>
                        <p class="text-xl font-semibold text-red-600">
                            <?= array_reduce($students, function($carry, $student) { 
                                return $carry + (10 - $student['remaining_sessions']); 
                            }, 0) ?>
                        </p>
                    </div>
                    <div class="bg-white p-3 rounded-lg border border-gray-200 shadow-xs">
                        <p class="text-xs text-gray-500">Available Sessions</p>
                        <p class="text-xl font-semibold text-green-600">
                            <?= array_reduce($students, function($carry, $student) { 
                                return $carry + $student['remaining_sessions']; 
                            }, 0) ?>
                        </p>
                    </div>
                </div>
            </div>
            <div class="w-20 h-1 bg-gradient-to-r from-purple-400 to-indigo-400 rounded-full"></div>
        </div>

        <!-- Display messages -->
        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="mb-6 p-4 bg-green-100 border-l-4 border-green-500 text-green-700 rounded-md">
                <div class="flex items-center">
                    <i class="fas fa-check-circle mr-2"></i>
                    <p><?= $_SESSION['success_message'] ?></p>
                </div>
            </div>
            <?php unset($_SESSION['success_message']); ?>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="mb-6 p-4 bg-red-100 border-l-4 border-red-500 text-red-700 rounded-md">
                <div class="flex items-center">
                    <i class="fas fa-exclamation-circle mr-2"></i>
                    <p><?= $_SESSION['error_message'] ?></p>
                </div>
            </div>
            <?php unset($_SESSION['error_message']); ?>
        <?php endif; ?>

        <!-- Action Bar -->
        <div class="mb-6 bg-white rounded-lg border border-gray-200 p-4">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <!-- Search Bar -->
                <div class="relative flex-1">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <i class="fas fa-search text-gray-400"></i>
                    </div>
                    <input 
                        type="text" 
                        id="search" 
                        class="w-full pl-10 pr-4 py-2 bg-gray-50 border border-gray-200 rounded-lg text-gray-700 placeholder-gray-400 focus:ring-2 focus:ring-purple-200 focus:border-transparent transition-all duration-200"
                        placeholder="Search students by name, ID or course..."
                        onkeyup="filterStudents()"
                    >
                </div>
                
                <!-- Action Buttons -->
                <div class="flex gap-3">
                    <form method="POST" class="mb-0">
                        <button type="submit" name="reset_all_sessions" class="flex items-center px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-md transition-all duration-200" onclick="return confirm('Are you sure you want to reset ALL student sessions?')">
                            <i class="fas fa-sync-alt mr-2"></i>
                            Reset All Sessions
                        </button>
                    </form>
                    <button class="flex items-center px-4 py-2 bg-white border border-gray-200 text-gray-700 rounded-md hover:bg-gray-50 transition-all duration-200">
                        <i class="fas fa-file-export mr-2"></i>
                        Export
                    </button>
                </div>
            </div>
            
            <!-- Filter Legend -->
            <div class="flex flex-wrap gap-3 text-xs text-gray-500 mt-4">
                <div class="flex items-center">
                    <div class="w-3 h-3 rounded-full bg-green-400 mr-1"></div>
                    <span>Available Sessions</span>
                </div>
                <div class="flex items-center">
                    <div class="w-3 h-3 rounded-full bg-yellow-400 mr-1"></div>
                    <span>Low Sessions</span>
                </div>
                <div class="flex items-center">
                    <div class="w-3 h-3 rounded-full bg-red-400 mr-1"></div>
                    <span>No Sessions</span>
                </div>
                <div class="flex items-center ml-auto">
                    <span>Last updated: <?= date("M d, Y h:i A") ?></span>
                </div>
            </div>
        </div>

        <!-- Student Table -->
        <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
            <table class="w-full text-left">
                <thead class="bg-gradient-to-r from-purple-400 to-indigo-400">
                    <tr>
                        <th class="p-4 text-sm font-medium text-purple-600 uppercase">Student</th>
                        <th class="p-4 text-sm font-medium text-purple-600 uppercase">ID</th>
                        <th class="p-4 text-sm font-medium text-purple-600 uppercase">Course</th>
                        <th class="p-4 text-sm font-medium text-purple-600 uppercase">Year</th>
                        <th class="p-4 text-sm font-medium text-purple-600 uppercase">Sessions</th>
                        <th class="p-4 text-sm font-medium text-purple-600 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody id="studentTableBody">
                    <?php if (!empty($students)): ?>
                        <?php foreach ($students as $student): ?>
                            <tr class="border-t border-gray-100 hover:bg-gray-50 transition-colors duration-150">
                                <!-- Student Column -->
                                <td class="p-4">
                                    <div class="flex items-center">
                                        <img 
                                            src="uploads/<?= htmlspecialchars($student['profile_picture'] ?? 'default_avatar.png') ?>" 
                                            alt="Profile" 
                                            class="w-10 h-10 rounded-full object-cover mr-3 border-2 border-white shadow-xs"
                                            onerror="this.src='assets/default_avatar.png'"
                                        >
                                        <div>
                                            <div class="font-medium text-gray-800">
                                                <?= htmlspecialchars($student['firstname'] . ' ' . $student['lastname']) ?>
                                            </div>
                                            <div class="text-xs text-gray-500">
                                                <?= htmlspecialchars($student['email']) ?>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                
                                <!-- ID Column -->
                                <td class="p-4 text-sm text-gray-700">
                                    <?= htmlspecialchars($student['idno']) ?>
                                </td>
                                
                                <!-- Course Column -->
                                <td class="p-4 text-sm text-gray-700">
                                    <?= htmlspecialchars($student['course']) ?>
                                </td>
                                
                                <!-- Year Column -->
                                <td class="p-4 text-sm text-gray-700">
                                    <?= htmlspecialchars($student['yearlevel']) ?>
                                </td>
                                
                                <!-- Sessions Column -->
                                <td class="p-4">
                                    <div class="flex items-center">
                                        <div class="w-24 bg-gray-200 rounded-full h-2.5 mr-3">
                                            <div class="h-2.5 rounded-full 
                                                <?= $student['remaining_sessions'] <= 0 ? 'bg-red-500' : 
                                                   ($student['remaining_sessions'] <= 3 ? 'bg-yellow-500' : 'bg-green-500') ?>" 
                                                style="width: <?= ($student['remaining_sessions'] / 30) * 100 ?>%">
                                            </div>
                                        </div>
                                        <span class="text-sm font-medium 
                                            <?= $student['remaining_sessions'] <= 0 ? 'text-red-600' : 
                                               ($student['remaining_sessions'] <= 3 ? 'text-yellow-600' : 'text-green-600') ?>">
                                            <?= $student['remaining_sessions'] ?>/30
                                        </span>
                                    </div>
                                </td>
                                

                                
                                <!-- Actions Column -->
                                <td class="p-4">
                                    <div class="flex items-center gap-2">
                                        <form method="POST" class="m-0">
                                            <input type="hidden" name="student_id" value="<?= htmlspecialchars($student['idno']) ?>">
                                            <button type="submit" name="reset_student_session" 
                                                class="p-2 bg-purple-100 hover:bg-purple-200 text-purple-600 rounded-md transition-all duration-200 flex items-center"
                                                title="Reset Sessions"
                                                onclick="return confirm('Reset sessions for <?= htmlspecialchars($student['firstname'] . ' ' . $student['lastname']) ?>?')">
                                                <i class="fas fa-sync-alt text-sm"></i>
                                            </button>
                                        </form>

                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="p-6 text-center">
                                <div class="flex flex-col items-center justify-center py-6">
                                    <div class="bg-gray-100 p-4 rounded-full mb-3">
                                        <i class="fas fa-user-graduate text-gray-400 text-xl"></i>
                                    </div>
                                    <h4 class="text-lg font-medium text-gray-500">No students found</h4>
                                    <p class="text-sm text-gray-400">When students register, they will appear here</p>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="flex items-center justify-between mt-6">
            <div class="text-sm text-gray-500">
                Showing <span class="font-medium text-gray-700">1</span> to <span class="font-medium text-gray-700">10</span> of <span class="font-medium text-gray-700"><?= count($students) ?></span> entries
            </div>
            <div class="flex gap-1">
                <button class="px-3 py-1 rounded-md bg-white border border-gray-200 text-gray-700 hover:bg-gray-50 transition-all duration-200">
                    <i class="fas fa-angle-left"></i>
                </button>
                <button class="px-3 py-1 rounded-md bg-purple-600 text-white">1</button>
                <button class="px-3 py-1 rounded-md bg-white border border-gray-200 text-gray-700 hover:bg-gray-50 transition-all duration-200">2</button>
                <button class="px-3 py-1 rounded-md bg-white border border-gray-200 text-gray-700 hover:bg-gray-50 transition-all duration-200">
                    <i class="fas fa-angle-right"></i>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Student Details Modal -->
<div id="studentModal" class="fixed inset-0 flex items-center justify-center hidden z-50 p-4 bg-black/30 backdrop-blur-sm">
    <div class="bg-white rounded-xl shadow-2xl border border-gray-200 p-6 w-full max-w-md transform transition-all duration-300 scale-95 opacity-0">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-xl font-semibold text-gray-800" id="studentModalTitle">Student Details</h3>
            <button onclick="closeStudentModal()" class="text-gray-400 hover:text-gray-500">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <div class="space-y-4" id="studentModalContent">
            <!-- Content will be loaded via AJAX -->
        </div>
        
        <div class="pt-4 flex justify-end">
            <button onclick="closeStudentModal()" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-md transition-all duration-200">
                Close
            </button>
        </div>
    </div>
</div>


<script>
// Filter students function
function filterStudents() {
    const input = document.getElementById('search');
    const filter = input.value.toUpperCase();
    const table = document.getElementById('studentTableBody');
    const rows = table.getElementsByTagName('tr');
    
    for (let i = 0; i < rows.length; i++) {
        const name = rows[i].getElementsByTagName('td')[0].textContent || rows[i].getElementsByTagName('td')[0].innerText;
        const id = rows[i].getElementsByTagName('td')[1].textContent || rows[i].getElementsByTagName('td')[1].innerText;
        const course = rows[i].getElementsByTagName('td')[2].textContent || rows[i].getElementsByTagName('td')[2].innerText;
        
        if (name.toUpperCase().indexOf(filter) > -1 || 
            id.toUpperCase().indexOf(filter) > -1 || 
            course.toUpperCase().indexOf(filter) > -1) {
            rows[i].style.display = "";
        } else {
            rows[i].style.display = "none";
        }
    }
}

// View student details
function viewStudentDetails(studentId) {
    // In a real implementation, you would fetch this data via AJAX
    fetch(`get_student_details.php?id=${studentId}`)
        .then(response => response.json())
        .then(data => {
            document.getElementById('studentModalContent').innerHTML = `
                <div class="flex justify-center mb-4">
                    <img src="uploads/${data.profile_picture || 'default_avatar.png'}" 
                         alt="Profile" 
                         class="w-24 h-24 rounded-full border-4 border-purple-100 object-cover"
                         onerror="this.src='assets/default_avatar.png'">
                </div>
                <div class="space-y-2">
                    <p><strong>ID:</strong> ${data.idno}</p>
                    <p><strong>Name:</strong> ${data.firstname} ${data.lastname}</p>
                    <p><strong>Course:</strong> ${data.course}</p>
                    <p><strong>Year Level:</strong> ${data.yearlevel}</p>
                    <p><strong>Email:</strong> ${data.email}</p>
                    <p><strong>Remaining Sessions:</strong> 
                        <span class="${data.remaining_sessions <= 0 ? 'text-red-600' : 
                                      (data.remaining_sessions <= 3 ? 'text-yellow-600' : 'text-green-600')}">
                            ${data.remaining_sessions}/10
                        </span>
                    </p>
                    <p><strong>Last Active:</strong> ${data.last_active || 'Never'}</p>
                </div>
            `;
            
            const modal = document.getElementById('studentModal');
            modal.classList.remove('hidden');
            setTimeout(() => {
                modal.querySelector('div').classList.remove('scale-95', 'opacity-0');
            }, 10);
        });
}

function closeStudentModal() {
    const modal = document.getElementById('studentModal');
    modal.querySelector('div').classList.add('scale-95', 'opacity-0');
    setTimeout(() => {
        modal.classList.add('hidden');
    }, 300);
}

// Close modal when clicking outside
document.getElementById('studentModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeStudentModal();
    }
});

        function filterStudents() {
            const input = document.getElementById('search').value.toLowerCase();
            const cards = document.querySelectorAll('.student-card');
            
            cards.forEach(card => {
                const text = card.innerText.toLowerCase();
                card.style.display = text.includes(input) ? 'block' : 'none';
            });
        }


        //dropdown
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

        function editAnnouncement(id, title, message) {
            document.getElementById("editId").value = id;
            document.getElementById("editTitle").value = title;
            document.getElementById("editMessage").value = message;
            document.getElementById("editModal").classList.remove("hidden");
        }
    </script>
</body>
</html>