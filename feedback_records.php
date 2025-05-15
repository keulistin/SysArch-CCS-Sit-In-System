<?php
session_start();
include 'db.php';

// Ensure only admins can access
if (!isset($_SESSION["idno"]) || $_SESSION["role"] !== "admin") {
    header("Location: login.php");
    exit();
}

$foulWords = ["shit", "bogo", "atay", "wtf", "fuck"]; // Add more words
function containsFoulWords($text, $foulWords) {
    foreach ($foulWords as $word) {
        if (stripos($text, $word) !== false) {
            return true;
        }
    }
    return false;
}

$idno = $_SESSION["idno"];
$stmt = $conn->prepare("SELECT firstname, lastname FROM users WHERE idno = ?");
$stmt->bind_param("s", $idno);
$stmt->execute();
$stmt->bind_result($firstname, $lastname);
$stmt->fetch();
$stmt->close();

// Fetch all sit-in records (both with and without feedback)
$records_query = "SELECT sr.id, sr.purpose, sr.lab, sr.start_time, sr.end_time, sr.feedback,
                         u.firstname, u.lastname, u.idno
                  FROM sit_in_records sr
                  JOIN users u ON sr.student_id = u.id
                  ORDER BY sr.start_time DESC";

$result = $conn->query($records_query);
$all_records = $result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Feedback Reports</title>
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
        .feedback-cell {
            max-width: 300px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .feedback-cell:hover {
            white-space: normal;
            overflow: visible;
            position: relative;
            z-index: 10;
            background-color: #1e293b;
            box-shadow: 0 0 10px rgba(0,0,0,0.5);
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

 <!-- Main Content - Premium Feedback Dashboard -->
 <div class="min-h-screen bg-purple-100 main-content-cont">
    <div class="max-w-7xl mx-auto">
        <!-- Header Section with Decorative Elements -->
    <div class="mb-10">
      <div class="flex items-center justify-between mb-4">
        <div>
        <h2 class="text-3xl font-medium text-gray-800 tracking-tight">Feedback Reports</h2>
        <p class="text-gray-500 font-light mt-2">Comprehensive overview of student lab sessions and feedback</p>
        </div>

      </div>
      <div class="w-20 h-1 bg-gradient-to-r from-purple-400 to-indigo-400 rounded-full"></div>
    </div>


        <!-- Records Table Container -->
        <div class=" rounded-2xl overflow-visible">
            <!-- Table Header with Export -->
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center p-4 border-b border-white/10">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-2xl font-light text-gray-700 flex items-center">
                    <i class="fas fa-comments text-purple-500 mr-3"></i>
                    Session Records
                    </h3>
                </div>                
                <div class="flex gap-2">
                    <button class="flex items-center gap-2 bg-white/5 hover:bg-white/10 border border-white/10 rounded-xl px-4 py-2 text-sm text-white transition-all duration-200">
                        <i class="fas fa-file-export"></i> Export
                    </button>
                </div>
            </div>

            <!-- Table -->
            <div class="rounded-xl shadow-xs border border-gray-200 bg-white"> <!-- Removed overflow-hidden -->
                <table class="min-w-full divide-y divide-gray-200 overflow-visible"> <!-- Added overflow-visible -->
                    <thead class="bg-gradient-to-r from-purple-600 to-indigo-600">
                        <tr>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-white/90 uppercase tracking-wider">Student</th>
                            <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-white/90 uppercase tracking-wider">ID</th>
                            <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-white/90 uppercase tracking-wider">Lab</th>
                            <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-white/90 uppercase tracking-wider">Purpose</th>
                            <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-white/90 uppercase tracking-wider">Date</th>
                            <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-white/90 uppercase tracking-wider">Feedback</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-100 overflow-visible"> <!-- Added overflow-visible -->
                    <?php if (empty($all_records)) : ?>
                            <tr class="hover:bg-gray-50 transition-colors duration-150">
                                <td colspan="6" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <div class="flex flex-col items-center justify-center py-10">
                                        <div class="bg-white/5 p-6 rounded-full mb-4">
                                            <i class="fas fa-inbox text-3xl text-white/30"></i>
                                        </div>
                                        <h4 class="text-lg font-medium mb-1">No records found</h4>
                                        <p class="text-sm max-w-md text-center">When students complete lab sessions, their records will appear here for review.</p>
                                    </div>
                                </td>
                            </tr>
                        <?php else : ?>
                            <?php foreach ($all_records as $record) : ?>
                                <tr class="hover:bg-white/5 transition-colors duration-150 group">
                                    <!-- Student Column -->
                                    <td class="p-4 overflow-visible"> <!-- Added overflow-visible -->
                                        <div class="flex items-center">
                                            <div class="relative">
                                                <div class="bg-blue-500/20 w-10 h-10 rounded-xl flex items-center justify-center mr-3">
                                                    <i class="fas fa-user text-lg text-blue-300"></i>
                                                </div>
                                            </div>
                                            <div>
                                                <p class="font-medium text-gray-500"><?php echo htmlspecialchars($record['firstname'] . ' ' . $record['lastname']); ?></p>
                                                <p class="text-xs text-gray-500 truncate max-w-[160px]"><?php echo htmlspecialchars($record['email'] ?? ''); ?></p>
                                            </div>
                                        </div>
                                    </td>
                                    
                                    <!-- ID Column -->
                                    <td class="p-4 overflow-visible"> <!-- Added overflow-visible -->
                                        <span class="font-mono text-sm text-gray-500 bg-white/5 px-2 py-1 rounded"><?php echo htmlspecialchars($record['idno']); ?></span>
                                    </td>
                                    
                                    <!-- Lab Column -->
                                    <td class="p-4 overflow-visible"> <!-- Added overflow-visible -->
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-indigo-500/20 text-indigo-500">
                                            <?php echo htmlspecialchars($record['lab']); ?>
                                        </span>
                                    </td>
                                    
                                    <!-- Purpose Column -->
                                    <td class="p-4 max-w-[200px] overflow-visible"> <!-- Added overflow-visible -->
                                        <div class="text-gray-500 text-sm truncate hover:text-clip group-hover:whitespace-normal" title="<?php echo htmlspecialchars($record['purpose']); ?>">
                                            <?php echo htmlspecialchars($record['purpose']); ?>
                                        </div>
                                    </td>
                                    
                                    <!-- Date Column -->
                                    <td class="p-4 overflow-visible"> <!-- Added overflow-visible -->
                                        <div class="text-gray-500 text-sm">
                                            <?php echo date("M d, Y", strtotime($record['start_time'])); ?>
                                            <p class="text-sm text-slate-400"><?php echo date("h:i A", strtotime($record['start_time'])); ?></p>
                                        </div>
                                    </td>
                                    
                                    <!-- Feedback Column (Modified) -->
                                    <td class="p-4 overflow-visible"> <!-- Added overflow-visible -->
                                        <?php if (!empty($record['feedback'])) : ?>
                                            <?php 
                                            $hasFoulWords = containsFoulWords($record['feedback'], $foulWords);
                                            $textColorClass = $hasFoulWords ? 'text-red-500' : 'text-blue-500';
                                            $bgColorClass = $hasFoulWords ? 'bg-red-500/10 hover:bg-red-500/20' : 'bg-blue-500/10 hover:bg-blue-500/20';
                                            ?>
                                            
                                            <div class="group/feedback relative">
                                                <button class="flex items-center text-left w-full <?php echo $bgColorClass; ?> <?php echo $textColorClass; ?> px-3 py-2 rounded-lg transition-colors duration-200">
                                                    <span class="truncate text-sm flex-1"><?php echo htmlspecialchars($record['feedback']); ?></span>
                                                    <i class="fas fa-chevron-down text-xs ml-2 opacity-0 group-hover/feedback:opacity-100 transition-opacity"></i>
                                                </button>
                                                <div class="absolute z-50 hidden group-hover/feedback:block bg-purple-600/95 text-white p-4 rounded-xl shadow-2xl w-72 border border-white/10 mt-1 backdrop-blur-sm" style="left: 0; transform: translateX(-25%);">
                                                    <div class="flex justify-between items-start mb-2">
                                                        <h4 class="font-medium text-sm">Feedback Details</h4>
                                                        <span class="text-xs <?php echo $hasFoulWords ? 'text-red-200/80 bg-red-900/60' : 'text-blue-200/80 bg-blue-900/60'; ?> px-2 py-1 rounded">
                                                            <?php echo date("M d, Y", strtotime($record['feedback_date'] ?? $record['start_time'])); ?>
                                                        </span>
                                                    </div>
                                                    <p class="text-sm text-white/90 mb-3"><?php echo htmlspecialchars($record['feedback']); ?></p>
                                                    <div class="text-xs text-slate-200 flex items-center justify-end">
                                                        <i class="fas fa-clock mr-1.5"></i> 
                                                        <?php echo date("h:i A", strtotime($record['feedback_date'] ?? $record['start_time'])); ?>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php else : ?>
                                            <span class="inline-flex items-center text-sm text-slate-400/80 italic">
                                                <i class="fas fa-minus-circle mr-1.5"></i> No feedback
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <div class="flex flex-col sm:flex-row items-center justify-between px-6 py-4 border-t border-white/10 gap-4">
                <div class="text-sm text-slate-300/80">
                    Showing <span class="font-medium text-white">1</span> to <span class="font-medium text-white">10</span> of <span class="font-medium text-white"><?php echo number_format(count($all_records)); ?></span> entries
                </div>
                <div class="flex items-center gap-1">
                    <button class="w-9 h-9 flex items-center justify-center rounded-lg bg-white/5 border border-white/10 text-white hover:bg-white/10 disabled:opacity-50 disabled:cursor-not-allowed transition-colors">
                        <i class="fas fa-chevron-left text-xs"></i>
                    </button>
                    <button class="w-9 h-9 flex items-center justify-center rounded-lg bg-blue-600 border border-blue-600 text-white font-medium">
                        1
                    </button>
                    <button class="w-9 h-9 flex items-center justify-center rounded-lg bg-white/5 border border-white/10 text-white hover:bg-white/10 transition-colors">
                        2
                    </button>
                    <button class="w-9 h-9 flex items-center justify-center rounded-lg bg-white/5 border border-white/10 text-white hover:bg-white/10 transition-colors">
                        3
                    </button>
                    <span class="px-2 text-white/50">...</span>
                    <button class="w-9 h-9 flex items-center justify-center rounded-lg bg-white/5 border border-white/10 text-white hover:bg-white/10 transition-colors">
                        8
                    </button>
                    <button class="w-9 h-9 flex items-center justify-center rounded-lg bg-white/5 border border-white/10 text-white hover:bg-white/10 transition-colors">
                        <i class="fas fa-chevron-right text-xs"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>


    <script>
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