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
    $lab_availability[$row['lab_name']][$row['time_slot']] = ($row['status'] === 'available');
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

$page_title = "Lab Schedule";
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
        
        // Define day groups mapping for JavaScript
        const dayGroups = {
            'MW': 'Monday/Wednesday',
            'TTh': 'Tuesday/Thursday',
            'Fri': 'Friday',
            'Sat': 'Saturday'
        };
        
        function showDayGroup(groupCode) {
            // Hide all tables and deactivate all tabs
            document.querySelectorAll('.day-group-table').forEach(table => {
                table.classList.add('hidden');
            });
            document.querySelectorAll('.day-group-tab').forEach(tab => {
                tab.classList.remove('border-blue-500', 'text-white');
                tab.classList.add('border-transparent', 'text-slate-400');
            });
            
            // Show selected table and activate tab
            document.getElementById(`table-${groupCode}`).classList.remove('hidden');
            document.getElementById(`tab-${groupCode}`).classList.add('border-blue-500', 'text-white');
            document.getElementById(`tab-${groupCode}`).classList.remove('border-transparent', 'text-slate-400');
            
            // Update the title
            document.getElementById('schedule-title').textContent = `Lab Schedule - ${dayGroups[groupCode]}`;
            
            // Update URL without reloading
            history.pushState(null, '', `?group=${groupCode}`);
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
                <li>
                    <a href="reservation.php" class="flex items-center px-5 py-3 text-slate-300 hover:bg-slate-700/20 hover:text-white transition-all duration-200 <?php echo basename($_SERVER['PHP_SELF']) === 'reservation.php' ? 'bg-slate-700/20 text-white' : ''; ?>">
                        <span>Reservation</span>
                    </a>
                </li>
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
                <h2 id="schedule-title" class="text-2xl font-semibold text-white">Lab Schedule - <?php echo htmlspecialchars($day_groups[$selected_group]); ?></h2>
            </div>

            <!-- Day Group Tabs -->
            <div class="mb-6">
                <ul class="flex flex-wrap -mb-px">
                    <?php foreach ($day_groups as $group_code => $group_name): ?>
                        <li class="mr-2">
                            <button 
                                onclick="showDayGroup('<?php echo $group_code; ?>')" 
                                id="tab-<?php echo $group_code; ?>"
                                class="inline-block p-4 border-b-2 rounded-t-lg hover:text-white hover:border-blue-500 transition-all duration-200 day-group-tab <?php echo $group_code === $selected_group ? 'border-blue-500 text-white' : 'border-transparent text-slate-400'; ?>"
                            >
                                <?php echo htmlspecialchars($group_name); ?>
                            </button>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <!-- Schedule Table for selected day group -->
            <div id="table-<?php echo $selected_group; ?>" class="day-group-table">
                <div class="overflow-x-auto">
                    <table class="min-w-full bg-slate-700/50 rounded-lg overflow-hidden">
                        <thead>
                            <tr class="bg-slate-700/80 text-left">
                                <th class="p-4 font-medium text-slate-300 w-48">Time Slot</th>
                                <?php foreach ($labs as $lab): ?>
                                    <th class="p-4 font-medium text-slate-300"><?php echo htmlspecialchars($lab); ?></th>
                                <?php endforeach; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($time_slots as $index => $slot): 
                                $time_slot_str = $slot['start'] . ' - ' . $slot['end'];
                            ?>
                                <tr class="<?php echo $index % 2 === 0 ? 'bg-slate-700/30' : 'bg-slate-700/10'; ?> hover:bg-slate-700/40 transition-all duration-150">
                                    <td class="p-4 font-medium text-slate-200 w-48 whitespace-nowrap">
                                        <?php echo htmlspecialchars($slot['display']); ?>
                                    </td>
                                    <?php foreach ($labs as $lab): 
                                        $is_available = $lab_availability[$lab][$time_slot_str];
                                    ?>
                                        <td class="p-4">
                                            <div class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium transition-all duration-200 <?php echo $is_available ? 'bg-green-600/20 text-green-400' : 'bg-red-600/20 text-red-400'; ?>">
                                                <i class="fas <?php echo $is_available ? 'fa-check-circle' : 'fa-times-circle'; ?> mr-1"></i>
                                                <?php echo $is_available ? 'Available' : 'Occupied'; ?>
                                            </div>
                                        </td>
                                    <?php endforeach; ?>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Create tables for other day groups (hidden by default) -->
            <?php foreach ($day_groups as $group_code => $group_name): ?>
                <?php if ($group_code !== $selected_group): ?>
                    <?php
                    // Fetch data for this day group
                    $query = "SELECT lab_name, time_slot, status FROM static_lab_schedules WHERE day_group = ?";
                    $stmt = $conn->prepare($query);
                    $stmt->bind_param("s", $group_code);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    
                    $group_availability = array();
                    while ($row = $result->fetch_assoc()) {
                        $group_availability[$row['lab_name']][$row['time_slot']] = ($row['status'] === 'available');
                    }
                    $stmt->close();
                    
                    // Default to available if no record exists in database
                    foreach ($labs as $lab) {
                        foreach ($time_slots as $slot) {
                            $time_slot_str = $slot['start'] . ' - ' . $slot['end'];
                            if (!isset($group_availability[$lab][$time_slot_str])) {
                                $group_availability[$lab][$time_slot_str] = true;
                            }
                        }
                    }
                    ?>
                    <div id="table-<?php echo $group_code; ?>" class="day-group-table hidden">
                        <div class="overflow-x-auto">
                            <table class="min-w-full bg-slate-700/50 rounded-lg overflow-hidden">
                                <thead>
                                    <tr class="bg-slate-700/80 text-left">
                                        <th class="p-4 font-medium text-slate-300 w-48">Time Slot</th>
                                        <?php foreach ($labs as $lab): ?>
                                            <th class="p-4 font-medium text-slate-300"><?php echo htmlspecialchars($lab); ?></th>
                                        <?php endforeach; ?>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($time_slots as $index => $slot): 
                                        $time_slot_str = $slot['start'] . ' - ' . $slot['end'];
                                    ?>
                                        <tr class="<?php echo $index % 2 === 0 ? 'bg-slate-700/30' : 'bg-slate-700/10'; ?> hover:bg-slate-700/40 transition-all duration-150">
                                            <td class="p-4 font-medium text-slate-200 w-48 whitespace-nowrap">
                                                <?php echo htmlspecialchars($slot['display']); ?>
                                            </td>
                                            <?php foreach ($labs as $lab): 
                                                $is_available = $group_availability[$lab][$time_slot_str];
                                            ?>
                                                <td class="p-4">
                                                    <div class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium transition-all duration-200 <?php echo $is_available ? 'bg-green-600/20 text-green-400' : 'bg-red-600/20 text-red-400'; ?>">
                                                        <i class="fas <?php echo $is_available ? 'fa-check-circle' : 'fa-times-circle'; ?> mr-1"></i>
                                                        <?php echo $is_available ? 'Available' : 'Occupied'; ?>
                                                    </div>
                                                </td>
                                            <?php endforeach; ?>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>

            <div class="mt-6 bg-slate-700/30 rounded-lg p-4 border border-white/5">
                <h4 class="text-lg font-semibold text-white mb-2">Legend</h4>
                <div class="flex flex-wrap gap-4">
                    <div class="flex items-center">
                        <span class="inline-block w-3 h-3 rounded-full bg-green-500 mr-2"></span>
                        <span class="text-sm text-slate-300">Available - Lab is vacant during this time</span>
                    </div>
                    <div class="flex items-center">
                        <span class="inline-block w-3 h-3 rounded-full bg-red-500 mr-2"></span>
                        <span class="text-sm text-slate-300">Occupied - Lab is in use during this time</span>
                    </div>
                </div>
                <div class="mt-3 text-sm text-slate-400">
                    <p>Note: Lab availability is managed by administrators and applies for the entire semester.</p>
                    <p class="mt-1">Please check the schedule before planning to use any lab facility.</p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>