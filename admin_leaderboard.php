<?php
session_start();
include 'db.php';

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

// Modified query to include profile pictures
$students_query = "SELECT 
                    u.id, 
                    u.idno, 
                    u.firstname, 
                    u.lastname, 
                    u.points, 
                    u.remaining_sessions,
                    u.profile_picture,
                    SEC_TO_TIME(SUM(TIME_TO_SEC(TIMEDIFF(s.end_time, s.start_time)))) AS total_duration
                  FROM users u
                  LEFT JOIN sit_in_records s ON u.id = s.student_id
                  WHERE u.role = 'student'
                  GROUP BY u.id
                  ORDER BY u.points DESC, u.remaining_sessions DESC
                  LIMIT 50";
$students_result = mysqli_query($conn, $students_query);

// Get top 3 students separately for the podium
$top3_query = "SELECT 
                u.id, 
                u.idno, 
                u.firstname, 
                u.lastname, 
                u.points, 
                u.remaining_sessions,
                u.profile_picture
              FROM users u
              WHERE u.role = 'student'
              ORDER BY u.points DESC, u.remaining_sessions DESC
              LIMIT 3";
$top3_result = mysqli_query($conn, $top3_query);
$top3 = [];
while ($row = mysqli_fetch_assoc($top3_result)) {
    $top3[] = $row;
}

// Recent activity logs - simplified to show points only
$logs_query = "SELECT r.*, u.firstname, u.lastname 
              FROM rewards_log r
              JOIN users u ON r.user_id = u.id
              ORDER BY r.created_at DESC
              LIMIT 50";
$logs_result = mysqli_query($conn, $logs_query);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['student_id'])) {
    // ... (keep your existing point adjustment logic) ...
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Leaderboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        body {
            overflow-x: hidden;
            min-height: 100vh;
            height: 100%;
            background-color: #F1E6EF;

        }
        .main-content-cont{
            padding: 8rem 15rem 0 15rem;
        }
        .sidebar {
            position: fixed;
            height: 100vh;
            overflow-y: auto;
        }
        .main-content {
            margin-left: 16rem;
            min-height: 100vh;
        }
        .podium-item {
            transition: all 0.3s ease;
        }
        .podium-item:hover {
            transform: translateY(-5px);
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
    <script>
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


<!-- Main Content - Elegant Leaderboard -->
<div class="min-h-screen bg-purple-100 main-content-cont">
<div class="max-w-7xl mx-auto">

        <!-- Header Section with Decorative Elements -->
        <div class="mb-10">
      <div class="flex items-center justify-between mb-4">
        <div>
        <h2 class="text-3xl font-medium text-gray-800 tracking-tight">Leaderboard</h2>
        <p class="text-gray-500 font-light mt-2">Recognizing top performing students</p>
        </div>

      </div>
      <div class="w-20 h-1 bg-gradient-to-r from-purple-400 to-indigo-400 rounded-full"></div>
    </div>


    <!-- Podium for Top 3 Students -->
    <div class="mb-16">
      <div class="flex items-end justify-center gap-6 h-72">
        <!-- 2nd Place -->
        <?php if (isset($top3[1])): ?>
        <div class="podium-item flex flex-col items-center w-1/4 transform hover:scale-105 transition-transform duration-300">
          <div class="bg-gradient-to-b from-gray-300 to-gray-400 w-full rounded-t-lg h-48 flex flex-col items-center justify-end relative shadow-md">
            <div class="absolute -top-8 w-16 h-16 bg-white rounded-full flex items-center justify-center shadow-lg border-4 border-gray-300">
              <span class="text-3xl">🥈</span>
            </div>
            <div class="w-full px-4 pb-6 text-center">
              <div class="text-lg font-semibold text-gray-800"><?= htmlspecialchars($top3[1]['firstname'].' '.$top3[1]['lastname']) ?></div>
              <div class="text-sm text-gray-600 mt-1"><?= $top3[1]['points'] ?> points</div>
            </div>
          </div>
          <div class="text-center mt-4">
            <div class="text-lg font-bold text-gray-500 bg-gray-100 px-4 py-1 rounded-full">2nd</div>
          </div>
        </div>
        <?php endif; ?>
        
        <!-- 1st Place -->
        <?php if (isset($top3[0])): ?>
        <div class="podium-item flex flex-col items-center w-1/3 transform hover:scale-105 transition-transform duration-300">
          <div class="bg-gradient-to-b from-yellow-300 to-yellow-500 w-full rounded-t-lg h-64 flex flex-col items-center justify-end relative shadow-lg">
            <div class="absolute -top-10 w-20 h-20 bg-white rounded-full flex items-center justify-center shadow-xl border-4 border-yellow-400">
              <span class="text-4xl">🥇</span>
            </div>
            <div class="w-full px-4 pb-8 text-center">
              <div class="text-xl font-semibold text-gray-800"><?= htmlspecialchars($top3[0]['firstname'].' '.$top3[0]['lastname']) ?></div>
              <div class="text-sm text-gray-700 mt-1"><?= $top3[0]['points'] ?> points</div>
            </div>
          </div>
          <div class="text-center mt-4">
            <div class="text-lg font-bold text-yellow-600 bg-yellow-100 px-4 py-1 rounded-full">1st</div>
          </div>
        </div>
        <?php endif; ?>
        
        <!-- 3rd Place -->
        <?php if (isset($top3[2])): ?>
        <div class="podium-item flex flex-col items-center w-1/4 transform hover:scale-105 transition-transform duration-300">
          <div class="bg-gradient-to-b from-amber-400 to-amber-600 w-full rounded-t-lg h-40 flex flex-col items-center justify-end relative shadow-md">
            <div class="absolute -top-8 w-16 h-16 bg-white rounded-full flex items-center justify-center shadow-lg border-4 border-amber-400">
              <span class="text-3xl">🥉</span>
            </div>
            <div class="w-full px-4 pb-6 text-center">
              <div class="text-lg font-semibold text-gray-800"><?= htmlspecialchars($top3[2]['firstname'].' '.$top3[2]['lastname']) ?></div>
              <div class="text-sm text-gray-600 mt-1"><?= $top3[2]['points'] ?> points</div>
            </div>
          </div>
          <div class="text-center mt-4">
            <div class="text-lg font-bold text-amber-600 bg-amber-100 px-4 py-1 rounded-full">3rd</div>
          </div>
        </div>
        <?php endif; ?>
      </div>
    </div>

    <!-- Full Leaderboard Table -->
    <div class="mb-16">
      <div class="flex justify-between items-center mb-6">
        <h3 class="text-2xl font-light text-gray-700 flex items-center">
          <i class="fas fa-list-ol text-purple-500 mr-3"></i>
          All Students
        </h3>
      </div>
      <div class="overflow-hidden rounded-xl shadow-xs border border-gray-200 bg-white">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gradient-to-r from-purple-600 to-indigo-600">
            <tr>
              <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-white/90 uppercase tracking-wider">Rank</th>
              <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-white/90 uppercase tracking-wider">Student</th>
              <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-white/90 uppercase tracking-wider">ID</th>
              <th scope="col" class="px-6 py-4 text-right text-xs font-semibold text-white/90 uppercase tracking-wider">Points</th>
              <th scope="col" class="px-6 py-4 text-right text-xs font-semibold text-white/90 uppercase tracking-wider">Sessions</th>
              <th scope="col" class="px-6 py-4 text-right text-xs font-semibold text-white/90 uppercase tracking-wider">Duration</th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-100">
            <?php 
            $rank = 1;
            mysqli_data_seek($students_result, 0);
            while($student = mysqli_fetch_assoc($students_result)): 
              if ($rank <= 3) {
                $rank++;
                continue;
              }
            ?>
            <tr class="hover:bg-gray-50 transition-colors duration-150">
              <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?= $rank ?></td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700"><?= htmlspecialchars($student['firstname'].' '.$student['lastname']) ?></td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 font-mono"><?= htmlspecialchars($student['idno']) ?></td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right font-medium"><?= $student['points'] ?></td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-right"><?= $student['remaining_sessions'] ?></td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-right font-mono"><?= $student['total_duration'] ?: '00:00:00' ?></td>
            </tr>
            <?php $rank++; endwhile; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Recent Reward Activities -->
    <div>
      <h3 class="text-2xl font-light text-gray-700 flex items-center mb-6">
        <i class="fas fa-history text-purple-500 mr-3"></i>
        Recent Reward Activities
      </h3>
      <div class="overflow-hidden rounded-xl shadow-xs border border-gray-200 bg-white">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gradient-to-r from-purple-600 to-indigo-600">
            <tr>
              <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-white/90 uppercase tracking-wider">Date</th>
              <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-white/90 uppercase tracking-wider">Student</th>
              <th scope="col" class="px-6 py-4 text-right text-xs font-semibold text-white/90 uppercase tracking-wider">Points</th>
              <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-white/90 uppercase tracking-wider">Action</th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-100">
            <?php 
            mysqli_data_seek($logs_result, 0);
            while($log = mysqli_fetch_assoc($logs_result)): 
            ?>
            <tr class="hover:bg-gray-50 transition-colors duration-150">
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= date('M d, Y h:i A', strtotime($log['created_at'])) ?></td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700"><?= htmlspecialchars($log['firstname'].' '.$log['lastname']) ?></td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-right font-mono">
                <span class="<?= ($log['points_earned'] > 0 ? 'text-green-600' : 'text-red-600') ?>">
                  <?= ($log['points_earned'] > 0 ? '+' : '').$log['points_earned'] ?>
                </span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span class="px-3 py-1 inline-flex text-xs leading-5 font-medium rounded-full bg-purple-100 text-purple-800">
                  <?= ucfirst(str_replace('_', ' ', $log['action'])) ?>
                </span>
              </td>
            </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div></body>
</html>