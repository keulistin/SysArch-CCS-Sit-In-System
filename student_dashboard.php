<?php
session_start();
include 'db.php';

// Ensure only students can access
if (!isset($_SESSION["idno"]) || $_SESSION["role"] !== "student") {
    header("Location: login.php");
    exit();
}

$idno = $_SESSION["idno"];
$stmt = $conn->prepare("SELECT firstname, lastname, remaining_sessions, profile_picture FROM users WHERE idno = ?");
$stmt->bind_param("s", $idno);
$stmt->execute();
$stmt->bind_result($firstname, $lastname, $remaining_sessions, $profile_picture);
$stmt->fetch();
$stmt->close();

// Get today's sit-ins for this student
$today_sitins_query = "SELECT COUNT(*) AS today_sitins FROM sit_in_records WHERE student_id = ? AND DATE(start_time) = CURDATE()";
$today_stmt = $conn->prepare($today_sitins_query);
$today_stmt->bind_param("s", $idno);
$today_stmt->execute();
$today_stmt->bind_result($today_sitins);
$today_stmt->fetch();
$today_stmt->close();

// Get total sit-ins for this student
$total_sitins_query = "SELECT COUNT(*) AS total_sitins FROM sit_in_records WHERE student_id = ?";
$total_stmt = $conn->prepare($total_sitins_query);
$total_stmt->bind_param("s", $idno);
$total_stmt->execute();
$total_stmt->bind_result($total_sitins);
$total_stmt->fetch();
$total_stmt->close();
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
    </style>
</head>
<body class="font-sans text-black">
<!-- Top Navigation Bar for Student -->
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

    <!-- User Avatar and Logout -->
    <div class="flex items-center space-x-4">
      <!-- Avatar -->
      <div class="w-10 h-10 rounded-full bg-purple-600 flex items-center justify-center">
                <img src="uploads/<?php echo htmlspecialchars(!empty($profile_picture) ? $profile_picture : 'default_avatar.jpg'); ?>" 
                     alt="User Avatar" 
                     class="w-10 h-10 rounded-full object-cover border-2 border-custom-purple"
                     onerror="this.src='assets/default_avatar.png'">
      </div>
      <h2 class="px-4 py-2 text-gray-700 font-bold"><?php echo htmlspecialchars($firstname); ?></h2>

      <!-- Logout -->
        <div class="ml-4">
            
            <a href="logout.php" class="flex items-center px-4 py-2 bg-purple-600 text-white rounded-full border-2 border-purple-700 hover:bg-purple-700 transition-all duration-200 shadow-md">
            <i class="fas fa-sign-out-alt mr-2"></i>
            <span class="hidden md:inline">Log Out</span>
            </a>
        </div>
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
            
            <!-- Today's Sit-ins Card -->
            <div class="bg-white p-8 rounded-lg border border-gray-100 shadow-xs hover:shadow-sm transition-all duration-300 transform hover:-translate-y-1">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-500 uppercase tracking-wider">Today's Sit-ins</p>
                        <h3 class="text-5xl font-light text-gray-800 mt-2"><?php echo $today_sitins; ?></h3>
                        <p class="text-sm text-gray-500 mt-1"><?php echo date("F j, Y"); ?></p>
                    </div>
                    <div class="p-3 rounded-full bg-blue-50 text-blue-600">
                        <i class="fas fa-laptop-code text-xl"></i>
                    </div>
                </div>
            </div>
            
            <!-- Total Sit-ins Card -->
            <div class="bg-white p-8 rounded-lg border border-gray-100 shadow-xs hover:shadow-sm transition-all duration-300 transform hover:-translate-y-1">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-500 uppercase tracking-wider">Total Sit-ins</p>
                        <h3 class="text-5xl font-light text-gray-800 mt-2"><?php echo $total_sitins; ?></h3>
                        <p class="text-sm text-gray-500 mt-1">all-time sessions</p>
                    </div>
                    <div class="p-3 rounded-full bg-green-50 text-green-600">
                        <i class="fas fa-clock-rotate-left text-xl"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions Section -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-12">

            
            <!-- New Reservation Card -->
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

        <!-- Recent Activity Section -->
        <div class="bg-white rounded-xl shadow-xs border border-gray-100 p-8">
            <div class="mb-6">
                <div class="flex items-center mb-3">
                    <div class="w-8 h-8 rounded-full bg-purple-100 flex items-center justify-center text-purple-600 mr-3">
                        <i class="fas fa-history text-sm"></i>
                    </div>
                    <h4 class="text-xl font-medium text-gray-800 tracking-tight">Recent Activity</h4>
                </div>
                <p class="text-gray-500 font-light pl-11">Your recent lab sessions</p>
            </div>
            
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="p-3 text-sm font-medium text-gray-500 uppercase">Date</th>
                            <th class="p-3 text-sm font-medium text-gray-500 uppercase">Lab</th>
                            <th class="p-3 text-sm font-medium text-gray-500 uppercase">Purpose</th>
                            <th class="p-3 text-sm font-medium text-gray-500 uppercase">Duration</th>
                            <th class="p-3 text-sm font-medium text-gray-500 uppercase">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // Get recent sit-ins
                        $recent_query = "SELECT lab, purpose, start_time, end_time 
                                        FROM sit_in_records 
                                        WHERE student_id = ? 
                                        ORDER BY start_time DESC 
                                        LIMIT 5";
                        $recent_stmt = $conn->prepare($recent_query);
                        $recent_stmt->bind_param("s", $idno);
                        $recent_stmt->execute();
                        $recent_result = $recent_stmt->get_result();
                        
                        if ($recent_result->num_rows > 0) {
                            while ($row = $recent_result->fetch_assoc()) {
                                $start_time = new DateTime($row['start_time']);
                                $end_time = $row['end_time'] ? new DateTime($row['end_time']) : null;
                                $duration = $end_time ? $start_time->diff($end_time)->format('%Hh %Im') : 'Active';
                                $status = $end_time ? 'Completed' : 'Active';
                                
                                echo "<tr class='border-t border-gray-100 hover:bg-gray-50'>";
                                echo "<td class='p-3'>".$start_time->format('M j, Y')."</td>";
                                echo "<td class='p-3'>".htmlspecialchars($row['lab'])."</td>";
                                echo "<td class='p-3'>".htmlspecialchars($row['purpose'])."</td>";
                                echo "<td class='p-3'>".$duration."</td>";
                                echo "<td class='p-3'><span class='px-2 py-1 text-xs rounded-full ".
                                    ($status == 'Active' ? 'bg-blue-100 text-blue-800' : 'bg-green-100 text-green-800')."'>".
                                    $status."</span></td>";
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='5' class='p-4 text-center text-gray-500'>No recent activity found</td></tr>";
                        }
                        $recent_stmt->close();
                        ?>
                    </tbody>
                </table>
            </div>
            
            <div class="mt-4 text-right">
                <a href="sit_in_history.php" class="text-sm text-purple-600 hover:text-purple-800">View full history →</a>
            </div>
        </div>
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