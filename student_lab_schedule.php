<?php
session_start();
include 'db.php';

// Set the timezone for Philippines
date_default_timezone_set('Asia/Manila');

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
        'display' => date('g:iA', $start_time) . '-' . date('g:iA', $end_slot)
    );
    $start_time = $end_slot;
}

// Get current day of week to determine which schedule to show by default
$current_day = date('w'); // 0 (Sunday) to 6 (Saturday)
$default_group = 'MW'; // Default to MW

if ($current_day == 1 || $current_day == 3) { // Monday or Wednesday
    $default_group = 'MW';
} elseif ($current_day == 2 || $current_day == 4) { // Tuesday or Thursday
    $default_group = 'TTh';
} elseif ($current_day == 5) { // Friday
    $default_group = 'Fri';
} elseif ($current_day == 6) { // Saturday
    $default_group = 'Sat';
}

// Check if a specific day group was requested
$selected_group = isset($_GET['group']) ? $_GET['group'] : $default_group;

// Fetch lab availability from database
$lab_availability = array();
$query = "SELECT lab_name, time_slot, status FROM static_lab_schedules WHERE day_group = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $selected_group);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $lab_availability[$row['lab_name']][$row['time_slot']] = ($row['status'] === 'open');
}

$stmt->close();

// Default to available if no record exists in database
foreach ($labs as $lab) {
    foreach ($time_slots as $slot) {
        $time_slot_str = $slot['start'] . ' - ' . $slot['end'];
        if (!isset($lab_availability[$lab][$time_slot_str])) {
            $lab_availability[$lab][$time_slot_str] = true;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lab Schedule</title>
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

<!-- Main Content -->
<div class="min-h-screen bg-purple-50 main-content-cont">
    <!-- Welcome Header with Subtle Accents -->
    <div class="mb-8">
        <div class="flex items-center mb-2">
            <h2 class="text-3xl font-medium text-gray-800 tracking-tight flex items-center">
                Lab Schedules
            </h2>
        </div>
        <p class="text-gray-500 font-light">View laboratory availability for different time slots</p>
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
            <span class="text-sm text-gray-600">Lab availability is managed by administrators</span>
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
                        class="inline-block p-4 border-b-2 rounded-t-lg font-medium transition-colors duration-200 day-group-tab <?php echo $group_code === $selected_group ? 'border-purple-500 text-purple-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'; ?>"
                    >
                        <?php echo htmlspecialchars($group_name); ?>
                    </button>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>

    <!-- Schedule Tables for each Day Group -->
    <?php foreach ($day_groups as $group_code => $group_name): ?>
        <div id="table-<?php echo $group_code; ?>" class="day-group-table mb-8 <?php echo $group_code !== $selected_group ? 'hidden' : ''; ?>">
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
                                    $is_available = $lab_availability[$lab][$time_slot_str] ?? true;
                                    $classes = $is_available
                                        ? 'bg-green-100 text-green-800'
                                        : 'bg-red-100 text-red-800';
                                    $icon = $is_available ? 'fa-check-circle' : 'fa-times-circle';
                                    $label = $is_available ? 'Available' : 'Occupied';
                                ?>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-medium <?php echo $classes; ?>">
                                            <i class="fas <?php echo $icon; ?> mr-1.5"></i>
                                            <?php echo $label; ?>
                                        </div>
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
    
    <!-- Additional Information Section -->
    <div class="bg-white p-6 rounded-lg shadow-sm border border-purple-100">
        <h3 class="text-lg font-medium text-gray-800 mb-3 flex items-center">
            <i class="fas fa-info-circle text-purple-500 mr-2"></i>
            Important Information
        </h3>
        <div class="space-y-2 text-gray-600">
            <p><i class="fas fa-circle text-xs text-purple-400 mr-2"></i> Lab availability is managed by administrators and applies for the entire semester.</p>
            <p><i class="fas fa-circle text-xs text-purple-400 mr-2"></i> Please check the schedule before planning to use any lab facility.</p>
            <p><i class="fas fa-circle text-xs text-purple-400 mr-2"></i> Some labs may be temporarily unavailable due to special classes or maintenance.</p>
        </div>
    </div>
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
    
    // Update tab styles
    document.querySelectorAll('.day-group-tab').forEach(tab => {
        tab.classList.remove('border-purple-500', 'text-purple-600');
        tab.classList.add('border-transparent', 'text-gray-500');
    });
    
    // Style active tab
    const activeTab = document.getElementById(`tab-${groupCode}`);
    activeTab.classList.add('border-purple-500', 'text-purple-600');
    activeTab.classList.remove('border-transparent', 'text-gray-500');
    
    // Update URL without reloading
    history.pushState(null, '', `?group=${groupCode}`);
}

// Mobile menu toggle
document.getElementById('mobile-menu-button').addEventListener('click', function() {
    document.getElementById('mobile-menu').classList.toggle('hidden');
});

// Close dropdowns when clicking outside
document.addEventListener('click', function(event) {
    if (!event.target.closest('.relative')) {
        document.querySelectorAll('.nav-dropdown').forEach(el => el.classList.add('hidden'));
    }
});

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
</script>
</body>
</html>