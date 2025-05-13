<?php
session_start();
include 'db.php';

// Ensure only admins can access
if (!isset($_SESSION["idno"]) || $_SESSION["role"] !== "admin") {
    header("Location: login.php");
    exit();
}

// Get admin info
$idno = $_SESSION["idno"];
$stmt = $conn->prepare("SELECT firstname, lastname FROM users WHERE idno = ?");
$stmt->bind_param("s", $idno);
$stmt->execute();
$stmt->bind_result($firstname, $lastname);
$stmt->fetch();
$stmt->close();

if (isset($_GET['action'])) {
    $sit_in_id = $_GET['id'];
    $action = $_GET['action'];
    
    $conn->begin_transaction();
    
    try {
        $stmt = $conn->prepare("SELECT student_id, lab, pc_number FROM sit_in_records WHERE id = ?");
        $stmt->bind_param("i", $sit_in_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $sit_in = $result->fetch_assoc();
        $student_id = $sit_in['student_id'];
        $lab = $sit_in['lab'];
        $pc_number = $sit_in['pc_number'];
        
        $stmt = $conn->prepare("UPDATE sit_in_records SET end_time = NOW() WHERE id = ?");
        $stmt->bind_param("i", $sit_in_id);
        $stmt->execute();
        
        // If PC was assigned, mark it as available again
        if (!empty($pc_number)) {
            $stmt = $conn->prepare("UPDATE lab_pcs SET status = 'Available' WHERE lab_name = ? AND pc_number = ?");
            $stmt->bind_param("si", $lab, $pc_number);
            $stmt->execute();
            $stmt->close();
        }
        
        // Get student info and deduct a session (for both reward and timeout)
        $stmt = $conn->prepare("SELECT points, remaining_sessions, idno FROM users WHERE id = ? FOR UPDATE");
        $stmt->bind_param("i", $student_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $student = $result->fetch_assoc();
        
        $current_sessions = $student['remaining_sessions'];
        $student_idno = $student['idno'];
        
        // Deduct one session (only if they have sessions available)
        if ($current_sessions > 0) {
            $current_sessions -= 1;
        } else {
            $current_sessions = 0; // Prevent negative sessions
        }
        
        if ($action === 'reward') {
            $new_points = $student['points'] + 1;
            
            // Add notification for point reward
            if ($new_points == 1) {
                $notification_msg = "You gained 1 point for your sit-in session (Total: $new_points point)";
            } else {
                $notification_msg = "You gained 1 point for your sit-in session (Total: $new_points points)";
            }
            $stmt = $conn->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)");
            $stmt->bind_param("ss", $student_idno, $notification_msg);
            $stmt->execute();
            
            // Check if earned a session (every 3 points)s
            $session_added = false;
            if ($new_points % 3 === 0) {
                $current_sessions += 1;
                $session_added = true;
            }
            
            $stmt = $conn->prepare("UPDATE users SET points = ?, remaining_sessions = ? WHERE id = ?");
            $stmt->bind_param("iii", $new_points, $current_sessions, $student_id);
            $stmt->execute();
            
            $stmt = $conn->prepare("INSERT INTO rewards_log (user_id, points_earned, action) VALUES (?, 1, 'sit_in_completion')");
            $stmt->bind_param("i", $student_id);
            $stmt->execute();
            
            if ($session_added) {
                $session_msg = "You earned +1 session for reaching {$new_points} points! (Total: $current_sessions sessions)";
                $stmt = $conn->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)");
                $stmt->bind_param("ss", $student_idno, $session_msg);
                $stmt->execute();
                
                $stmt = $conn->prepare("INSERT INTO rewards_log (user_id, points_earned, action) VALUES (?, 1, 'session_reward')");
                $stmt->bind_param("i", $student_id);
                $stmt->execute();
            }
            
            $_SESSION['reward_message'] = "Student logged out and rewarded 1 point!" . ($session_added ? " (+1 session awarded)" : "");
        } else {
            // For timeout, just notify and update session count
            $stmt = $conn->prepare("UPDATE users SET remaining_sessions = ? WHERE id = ?");
            $stmt->bind_param("ii", $current_sessions, $student_id);
            $stmt->execute();
            
            $notification_msg = "You logged out without earning points this session. Remaining sessions: $current_sessions";
            $stmt = $conn->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)");
            $stmt->bind_param("ss", $student_idno, $notification_msg);
            $stmt->execute();
            
            $_SESSION['reward_message'] = "Student logged out successfully (1 session deducted)";
        }
        
        $conn->commit();
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error_message'] = "Error: " . $e->getMessage();
        error_log("Sit-in management error: " . $e->getMessage());
    }
    
    header("Location: manage_sitins.php");
    exit();
}

$active_sitins_query = "SELECT s.id, u.id as user_id, u.idno, u.firstname, u.lastname, s.purpose, s.lab, s.pc_number, s.start_time 
                        FROM sit_in_records s
                        JOIN users u ON s.student_id = u.id
                        WHERE s.end_time IS NULL
                        ORDER BY s.start_time DESC";
$active_sitins_result = mysqli_query($conn, $active_sitins_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Sit-ins</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        body {
            background-color: #F1E6EF;
        }
        .main-content-cont{
            padding: 8rem 15rem 5rem 15rem;
        }
        .time-cell {
            min-width: 160px;
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
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Poppins', 'sans-serif'],
                    },
                },
            },
        }
        
        function confirmAction(action, sitInId) {
            if (action === 'reward') {
                return confirm("Give this student 1 point for their sit-in? (1 session will be deducted)");
            } else {
                return confirm("Log out this student without giving points? (1 session will be deducted)");
            }
        }
    </script>
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

<!-- Main Content - Dashboard Styled Manage Sit-ins -->
<div class="min-h-screen bg-purple-100 main-content-cont">
  <!-- Header Section -->
  <div class="mb-10">
    <div class="flex items-center justify-between mb-4">
      <div>
        <h2 class="text-3xl font-medium text-gray-800 tracking-tight">Manage Sit-ins</h2>
        <p class="text-gray-500 font-light mt-2">Track and manage active sit-ins in real-time</p>
      </div>
    </div>
    <div class="w-20 h-1 bg-gradient-to-r from-purple-400 to-indigo-400 rounded-full"></div>
  </div>

  <!-- Message Alerts -->
  <?php if (isset($_SESSION['reward_message'])): ?>
    <div class="mb-6 p-3 rounded bg-green-100 text-green-700 border border-green-200">
      <?php echo $_SESSION['reward_message']; unset($_SESSION['reward_message']); ?>
    </div>
  <?php endif; ?>

  <?php if (isset($_SESSION['error_message'])): ?>
    <div class="mb-6 p-3 rounded bg-red-100 text-red-700 border border-red-200">
      <?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?>
    </div>
  <?php endif; ?>

  <!-- Sit-ins Table -->
  <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-x-auto">
    <div class="p-6 border-b border-gray-100 flex items-center justify-between">
      <h3 class="text-lg font-medium text-gray-800">Active Sit-in Records</h3>
    </div>

    <?php if ($active_sitins_result && mysqli_num_rows($active_sitins_result) > 0): ?>
      <table class="min-w-full divide-y divide-gray-100">
        <thead class="bg-gradient-to-r from-purple-600 to-indigo-600">
          <tr>
            <th class="p-4 text-left text-sm font-medium text-blue-200 uppercase">Student</th>
            <th class="p-4 text-left text-sm font-medium text-blue-200 uppercase">ID</th>
            <th class="p-4 text-left text-sm font-medium text-blue-200 uppercase">Purpose</th>
            <th class="p-4 text-left text-sm font-medium text-blue-200 uppercase">Lab</th>
            <th class="p-4 text-left text-sm font-medium text-blue-200 uppercase">PC</th>
            <th class="p-4 text-left text-sm font-medium text-blue-200 uppercase">Start Time</th>
            <th class="p-4 text-left text-sm font-medium text-blue-200 uppercase">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
          <?php while ($row = mysqli_fetch_assoc($active_sitins_result)): ?>
            <tr class="hover:bg-purple-50 transition">
              <td class="p-4 text-gray-800 font-medium">
                <?php echo htmlspecialchars($row['firstname'] . ' ' . $row['lastname']); ?>
              </td>
              <td class="p-4 text-gray-700"><?php echo htmlspecialchars($row['idno']); ?></td>
              <td class="p-4 text-gray-700"><?php echo htmlspecialchars($row['purpose']); ?></td>
              <td class="p-4">
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-indigo-100 text-indigo-700">
                  <i class="fas fa-door-open mr-1.5"></i> <?php echo htmlspecialchars($row['lab']); ?>
                </span>
              </td>
              <td class="p-4 text-gray-700">
                <?php echo $row['pc_number'] ? 'PC ' . htmlspecialchars($row['pc_number']) : '-'; ?>
              </td>
              <td class="p-4 text-gray-600">
                <?php 
                if ($row['start_time'] == '0000-00-00 00:00:00' || empty($row['start_time'])) {
                  echo '<span class="italic text-gray-400">Not recorded</span>';
                } else {
                  echo (new DateTime($row['start_time']))->format('M d, Y h:i A');
                }
                ?>
              </td>
              <td class="p-4">
                <div class="flex gap-2">
                  <a href="manage_sitins.php?action=reward&id=<?php echo $row['id']; ?>"
                    class="bg-green-100 text-green-700 hover:bg-green-200 px-3 py-1 rounded text-sm font-medium transition"
                    onclick="return confirmAction('reward', <?php echo $row['id']; ?>);">
                    <i class="fas fa-gift mr-1"></i> Reward
                  </a>
                  <a href="manage_sitins.php?action=timeout&id=<?php echo $row['id']; ?>"
                    class="bg-red-100 text-red-700 hover:bg-red-200 px-3 py-1 rounded text-sm font-medium transition"
                    onclick="return confirmAction('timeout', <?php echo $row['id']; ?>);">
                    <i class="fas fa-sign-out-alt mr-1"></i> Timeout
                  </a>
                </div>
              </td>
            </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    <?php else: ?>
      <div class="p-6 text-center text-gray-500 italic">
        <i class="fas fa-info-circle mr-1"></i> No active sit-ins found.
      </div>
    <?php endif; ?>
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