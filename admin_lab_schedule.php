<?php
session_start();
include 'db.php';

// Set the timezone for Philippines
date_default_timezone_set('Asia/Manila');

// Ensure only admins can access
if (!isset($_SESSION["idno"]) || $_SESSION["role"] !== "admin") {
    header("Location: login.php");
    exit();
}

// Get user info from session
$firstname = $_SESSION['firstname'] ?? 'Admin';
$profile_picture = $_SESSION['profile_picture'] ?? 'default_avatar.png';

// List of available labs
$labs = array('Lab 517', 'Lab 524', 'Lab 526', 'Lab 528', 'Lab 530', 'Lab 542', 'Lab 544');

// Define day groups
$day_groups = array(
    'MW' => 'Monday/Wednesday',
    'TTh' => 'Tuesday/Thursday',
    'Fri' => 'Friday',
    'Sat' => 'Saturday'
);

// Generate time slots from 7:30 AM to 9:00 PM in 1.5 hour increments
$time_slots = array();
$start_time = strtotime('7:30 AM');
$end_time = strtotime('9:00 PM');

while ($start_time < $end_time) {
    $end_slot = $start_time + (90 * 60); // 1.5 hours in seconds
    $time_slots[] = array(
        'start' => date('h:i A', $start_time),
        'end' => date('h:i A', $end_slot),
        'start_time' => date('H:i:s', $start_time),
        'end_time' => date('H:i:s', $end_slot),
        'display' => date('g:iA', $start_time) . '-' . date('g:iA', $end_slot)
    );
    $start_time = $end_slot;
}

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['lab'], $_POST['time_slot'], $_POST['day_group'], $_POST['status'])) {
    $lab = $_POST['lab'];
    $time_slot = $_POST['time_slot'];
    $day_group = $_POST['day_group'];
    $status = $_POST['status']; // 'available' or 'occupied'
    
    // Check if record exists
    $check_query = "SELECT id FROM static_lab_schedules WHERE lab_name = ? AND day_group = ? AND time_slot = ?";
    $stmt = $conn->prepare($check_query);
    $stmt->bind_param("sss", $lab, $day_group, $time_slot);
    $stmt->execute();
    $stmt->store_result();
    
    if ($stmt->num_rows > 0) {
        // Update existing record
        $update_query = "UPDATE static_lab_schedules SET status = ? WHERE lab_name = ? AND day_group = ? AND time_slot = ?";
        $stmt = $conn->prepare($update_query);
        $stmt->bind_param("ssss", $status, $lab, $day_group, $time_slot);
    } else {
        // Insert new record
        $insert_query = "INSERT INTO static_lab_schedules (lab_name, day_group, time_slot, status) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($insert_query);
        $stmt->bind_param("ssss", $lab, $day_group, $time_slot, $status);
    }
    
    $stmt->execute();
    $stmt->close();
    
    header("Location: admin_lab_schedule.php");
    exit();
}

// Fetch all lab availability from database
$lab_availability = array();
$query = "SELECT lab_name, day_group, time_slot, status FROM static_lab_schedules";
$result = $conn->query($query);

while ($row = $result->fetch_assoc()) {
    $lab_availability[$row['lab_name']][$row['day_group']][$row['time_slot']] = ($row['status'] === 'available');
}

// Default to available if no record exists in database
foreach ($labs as $lab) {
    foreach ($day_groups as $group_code => $group_name) {
        foreach ($time_slots as $slot) {
            $time_slot_str = $slot['start'] . ' - ' . $slot['end'];
            if (!isset($lab_availability[$lab][$group_code][$time_slot_str])) {
                $lab_availability[$lab][$group_code][$time_slot_str] = true;
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Lab Schedule</title>
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

<!-- Main Content -->
<div class="min-h-screen bg-purple-50 main-content-cont">
    <!-- Welcome Header with Subtle Accents -->
    <div class="mb-8">
        <div class="flex items-center mb-2">
            <h2 class="text-3xl font-medium text-gray-800 tracking-tight flex items-center">
                Lab Schedules
            </h2>
        </div>
        <p class="text-gray-500 font-light">Manage and update laboratory availability for different time slots</p>
        <div class="w-16 h-1 bg-gradient-to-r from-purple-400 to-indigo-500 mt-4 rounded-full"></div>
    </div>

    <!-- Status Legend -->
    <div class="mb-6 flex flex-wrap items-center gap-4 bg-white p-4 rounded-lg shadow-sm border border-purple-100">
        <div class="flex items-center">
            <span class="w-3 h-3 rounded-full bg-green-400 mr-2"></span>
            <span class="text-sm text-gray-600">Available</span>
        </div>
        <div class="flex items-center">
            <span class="w-3 h-3 rounded-full bg-red-400 mr-2"></span>
            <span class="text-sm text-gray-600">Occupied</span>
        </div>
        <div class="flex items-center ml-auto">
            <i class="fas fa-info-circle text-purple-500 mr-2"></i>
            <span class="text-sm text-gray-600">Click on status buttons to toggle availability</span>
        </div>
    </div>

    <!-- Day Group Tabs - Simplified Style -->
    <div class="mb-6">
        <ul class="flex flex-wrap -mb-px border-b border-gray-200">
            <?php foreach ($day_groups as $group_code => $group_name): ?>
                <li class="mr-2">
                    <button 
                        onclick="showDayGroup('<?php echo $group_code; ?>')" 
                        id="tab-<?php echo $group_code; ?>"
                        class="inline-block p-4 border-b-2 rounded-t-lg font-medium transition-colors duration-200 day-group-tab <?php echo $group_code === 'MW' ? 'border-purple-500 text-purple-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'; ?>"
                    >
                        <?php echo htmlspecialchars($group_name); ?>
                    </button>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>

    <!-- Schedule Tables for each Day Group -->
    <?php foreach ($day_groups as $group_code => $group_name): ?>
        <div id="table-<?php echo $group_code; ?>" class="day-group-table mb-8 <?php echo $group_code !== 'MW' ? 'hidden' : ''; ?>">
            <div class="overflow-x-auto rounded-lg shadow-sm border border-purple-100">
                <table class="min-w-full bg-white divide-y divide-purple-200">
                    <thead>
                        <tr class="bg-gradient-to-r from-purple-600 to-indigo-600 text-white">
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider w-48">
                                <i class="fas fa-clock mr-2"></i>Time Slot
                            </th>
                            <?php foreach ($labs as $lab): ?>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider">
                                    <i class="fas fa-flask mr-2"></i><?php echo htmlspecialchars($lab); ?>
                                </th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-purple-200">
                        <?php foreach ($time_slots as $index => $slot): 
                            $time_slot_str = $slot['start'] . ' - ' . $slot['end'];
                        ?>
                            <tr class="<?php echo $index % 2 === 0 ? 'bg-purple-50/50' : 'bg-white'; ?> hover:bg-purple-100/30 transition-colors duration-150">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 w-48">
                                    <div class="flex items-center">
                                        <i class="fas fa-hourglass-half mr-3 text-purple-500"></i>
                                        <?php echo htmlspecialchars($slot['display']); ?>
                                    </div>
                                </td>
                                <?php foreach ($labs as $lab): 
                                    $is_available = $lab_availability[$lab][$group_code][$time_slot_str];
                                    $opposite_status = $is_available ? 'occupied' : 'available';
                                ?>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <form method="POST" class="inline">
                                            <input type="hidden" name="lab" value="<?php echo htmlspecialchars($lab); ?>">
                                            <input type="hidden" name="time_slot" value="<?php echo htmlspecialchars($time_slot_str); ?>">
                                            <input type="hidden" name="day_group" value="<?php echo htmlspecialchars($group_code); ?>">
                                            <button 
                                                type="submit" 
                                                name="status" 
                                                value="<?php echo $opposite_status; ?>"
                                                class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-medium transition-all duration-200 shadow-sm hover:shadow-md <?php echo $is_available ? 'bg-green-100 text-green-800 hover:bg-green-200' : 'bg-red-100 text-red-800 hover:bg-red-200'; ?>"
                                                title="Click to mark as <?php echo $opposite_status; ?>"
                                            >
                                                <i class="fas <?php echo $is_available ? 'fa-check-circle' : 'fa-times-circle'; ?> mr-1.5"></i>
                                                <?php echo $is_available ? 'Available' : 'Occupied'; ?>
                                            </button>
                                        </form>
                                    </td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Table Footer -->
            <div class="mt-3 flex justify-between items-center text-sm text-gray-500">
                <div>
                    <i class="fas fa-sync-alt mr-1 text-purple-500"></i>
                    Last updated: <?php echo date('M j, Y g:i A'); ?>
                </div>
                <div>
                    Showing schedule for <span class="font-medium text-purple-600"><?php echo htmlspecialchars($group_name); ?></span>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<!-- Simplified JavaScript for Tab Switching -->
<script>
function showDayGroup(groupCode) {
    // Hide all tables
    document.querySelectorAll('.day-group-table').forEach(table => {
        table.classList.add('hidden');
    });
    
    // Show selected table
    document.getElementById(`table-${groupCode}`).classList.remove('hidden');
    
    // Update tab styles - simplified to just change border and text color
    document.querySelectorAll('.day-group-tab').forEach(tab => {
        tab.classList.remove('border-purple-500', 'text-purple-600');
        tab.classList.add('border-transparent', 'text-gray-500');
    });
    
    // Style active tab - just purple border and text
    const activeTab = document.getElementById(`tab-${groupCode}`);
    activeTab.classList.add('border-purple-500', 'text-purple-600');
    activeTab.classList.remove('border-transparent', 'text-gray-500');
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
</script>
</body>
</html>