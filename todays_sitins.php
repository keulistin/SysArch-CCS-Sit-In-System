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

// Get today's date in YYYY-MM-DD format
$today = date("Y-m-d");

// Fetch today's sit-in records
$today_records_query = "SELECT sr.id, sr.purpose, sr.lab, sr.start_time, sr.end_time, sr.feedback,
                                u.firstname, u.lastname 
                         FROM sit_in_records sr
                         JOIN users u ON sr.student_id = u.id
                         WHERE DATE(sr.start_time) = ?
                         ORDER BY sr.start_time DESC";

$stmt = $conn->prepare($today_records_query);
$stmt->bind_param("s", $today);
$stmt->execute();
$result = $stmt->get_result();
$today_records = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Get data for charts
$purpose_counts = [];
$lab_counts = [];

foreach ($today_records as $record) {
    // Count purposes
    if (!isset($purpose_counts[$record['purpose']])) {
        $purpose_counts[$record['purpose']] = 0;
    }
    $purpose_counts[$record['purpose']]++;
    
    // Count labs
    if (!isset($lab_counts[$record['lab']])) {
        $lab_counts[$record['lab']] = 0;
    }
    $lab_counts[$record['lab']]++;
}

// Prepare data for JavaScript
$purpose_labels = json_encode(array_keys($purpose_counts));
$purpose_data = json_encode(array_values($purpose_counts));
$lab_labels = json_encode(array_keys($lab_counts));
$lab_data = json_encode(array_values($lab_counts));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Today's Sit-in Records</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
        .chart-container {
            position: relative;
            height: 250px;
            width: 100%;
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

<!-- Main Content - Dashboard Styled Today's Sit-ins -->
<div class="min-h-screen bg-purple-100 main-content-cont">
  <!-- Header -->
  <div class="mb-10">
    <div class="flex items-center justify-between mb-4">
      <div>
        <h2 class="text-3xl font-medium text-gray-800 tracking-tight">Today's Sit-in Records</h2>
        <p class="text-gray-500 font-light mt-2">Tracking sit-in activity for <?php echo date("F d, Y"); ?></p>
      </div>
      <div class="bg-blue-100 text-blue-700 font-medium px-4 py-2 rounded-lg shadow-sm flex items-center">
        <i class="fas fa-calendar-day mr-2"></i>
        <?php echo date("l, F j"); ?>
      </div>
    </div>
    <div class="w-20 h-1 bg-gradient-to-r from-purple-400 to-indigo-400 rounded-full"></div>
  </div>

  <!-- Charts Section -->
  <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-12">
    <!-- Purpose Chart -->
    <div class="bg-white border border-gray-200 rounded-xl p-6 shadow-sm hover:shadow-md transition">
      <h3 class="text-lg font-semibold text-gray-700 text-center mb-4">Purpose Distribution</h3>
      <div class="chart-container">
        <canvas id="purposeChart"></canvas>
      </div>
    </div>

    <!-- Lab Chart -->
    <div class="bg-white border border-gray-200 rounded-xl p-6 shadow-sm hover:shadow-md transition">
      <h3 class="text-lg font-semibold text-gray-700 text-center mb-4">Lab Distribution</h3>
      <div class="chart-container">
        <canvas id="labChart"></canvas>
      </div>
    </div>
  </div>

  <!-- Sit-in Records Table -->
  <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-x-auto">
    <div class="p-6 border-b border-gray-200 flex items-center justify-between">
      <h3 class="text-lg font-medium text-gray-800">Sit-in Details</h3>
    </div>
    <table class="min-w-full divide-y divide-gray-100">
      <thead class="bg-gradient-to-r from-purple-600 to-indigo-600">
        <tr>
          <th class="p-4 text-left text-sm font-medium text-white uppercase">Student</th>
          <th class="p-4 text-left text-sm font-medium text-white uppercase">Purpose</th>
          <th class="p-4 text-left text-sm font-medium text-white uppercase">Lab</th>
          <th class="p-4 text-left text-sm font-medium text-white uppercase">Start Time</th>
          <th class="p-4 text-left text-sm font-medium text-white uppercase">End Time</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-gray-100">
        <?php if (empty($today_records)) : ?>
          <tr>
            <td colspan="6" class="text-center p-6 text-gray-500">No sit-in records found for today.</td>
          </tr>
        <?php else : ?>
          <?php foreach ($today_records as $record) : ?>
            <tr class="hover:bg-purple-50 transition">
              <td class="p-4 text-gray-800"><?php echo htmlspecialchars($record['firstname'] . ' ' . $record['lastname']); ?></td>
              <td class="p-4 text-gray-700"><?php echo htmlspecialchars($record['purpose']); ?></td>
              <td class="p-4">
                <span class="inline-flex items-center px-3 py-1 text-xs font-medium bg-indigo-100 text-indigo-700 rounded-full">
                  <i class="fas fa-door-open mr-1.5"></i>
                  <?php echo htmlspecialchars($record['lab']); ?>
                </span>
              </td>
              <td class="p-4 text-gray-700">
                <?php echo date("h:i A", strtotime($record['start_time'])); ?>
              </td>
              <td class="p-4 text-gray-700">
                <?php echo $record['end_time'] ? date("h:i A", strtotime($record['end_time'])) : "<span class='text-green-600 font-medium'>Active Now</span>"; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
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
        // Chart colors
        const chartColors = [
            'rgba(99, 102, 241, 0.8)',  // indigo
            'rgba(59, 130, 246, 0.8)',   // blue
            'rgba(16, 185, 129, 0.8)',    // emerald
            'rgba(245, 158, 11, 0.8)',   // amber
            'rgba(244, 63, 94, 0.8)',     // rose
            'rgba(139, 92, 246, 0.8)',    // violet
            'rgba(20, 184, 166, 0.8)',   // teal
            'rgba(234, 88, 12, 0.8)',     // orange
            'rgba(220, 38, 38, 0.8)',     // red
            'rgba(5, 150, 105, 0.8)',     // green
            'rgba(217, 119, 6, 0.8)',     // yellow
            'rgba(6, 182, 212, 0.8)'     // cyan
        ];

        // Purpose Chart
        const purposeCtx = document.getElementById('purposeChart').getContext('2d');
        const purposeChart = new Chart(purposeCtx, {
            type: 'pie',
            data: {
                labels: <?php echo $purpose_labels; ?>,
                datasets: [{
                    data: <?php echo $purpose_data; ?>,
                    backgroundColor: chartColors.slice(0, <?php echo count($purpose_counts); ?>),
                    borderColor: 'rgba(30, 41, 59, 0.5)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right',
                        labels: {
                            color: 'gray',
                            font: {
                                family: 'Poppins'
                            }
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const label = context.label || '';
                                const value = context.raw || 0;
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = Math.round((value / total) * 100);
                                return `${label}: ${value} (${percentage}%)`;
                            }
                        }
                    }
                }
            }
        });

        // Lab Chart
        const labCtx = document.getElementById('labChart').getContext('2d');
        const labChart = new Chart(labCtx, {
            type: 'pie',
            data: {
                labels: <?php echo $lab_labels; ?>,
                datasets: [{
                    data: <?php echo $lab_data; ?>,
                    backgroundColor: chartColors.slice(0, <?php echo count($lab_counts); ?>),
                    borderColor: 'rgba(30, 41, 59, 0.5)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right',
                        labels: {
                            color: 'gray',
                            font: {
                                family: 'Poppins'
                            }
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const label = context.label || '';
                                const value = context.raw || 0;
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = Math.round((value / total) * 100);
                                return `${label}: ${value} (${percentage}%)`;
                            }
                        }
                    }
                }
            }
        });
    </script>
</body>
</html>