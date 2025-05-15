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

// Handle PC status update
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST["update_status"])) {
        $pc_id = $_POST["pc_id"];
        $new_status = $_POST["status"];
        $lab_name = $_POST["lab_name"];
        
        $stmt = $conn->prepare("UPDATE lab_pcs SET status = ? WHERE id = ? AND lab_name = ?");
        $stmt->bind_param("sis", $new_status, $pc_id, $lab_name);
        $stmt->execute();
        $stmt->close();
        
        // Log the change
        $action = "Updated PC $pc_id in $lab_name to status: $new_status";
        $log_stmt = $conn->prepare("INSERT INTO admin_logs (admin_id, action) VALUES (?, ?)");
        $log_stmt->bind_param("ss", $idno, $action);
        $log_stmt->execute();
        $log_stmt->close();
    } 
    // Handle bulk update
    elseif (isset($_POST["bulk_update"])) {
        $lab_name = $_POST["lab_name"];
        $bulk_status = $_POST["bulk_status"];
        
        $stmt = $conn->prepare("UPDATE lab_pcs SET status = ? WHERE lab_name = ?");
        $stmt->bind_param("ss", $bulk_status, $lab_name);
        $stmt->execute();
        $stmt->close();
        
        // Log the change
        $action = "Updated ALL PCs in $lab_name to status: $bulk_status";
        $log_stmt = $conn->prepare("INSERT INTO admin_logs (admin_id, action) VALUES (?, ?)");
        $log_stmt->bind_param("ss", $idno, $action);
        $log_stmt->execute();
        $log_stmt->close();
        
        $_SESSION['bulk_message'] = "All PCs in $lab_name have been marked as $bulk_status";
    }
}

// Get all labs
$labs = ['Lab 517', 'Lab 524', 'Lab 526', 'Lab 528', 'Lab 530', 'Lab 542', 'Lab 544'];

// Initialize PC data array
$lab_pcs = [];

foreach ($labs as $lab) {
    // Check if PCs exist for this lab
    $stmt = $conn->prepare("SELECT COUNT(*) FROM lab_pcs WHERE lab_name = ?");
    $stmt->bind_param("s", $lab);
    $stmt->execute();
    $stmt->bind_result($pc_count);
    $stmt->fetch();
    $stmt->close();
    
    // If no PCs exist, initialize them
    if ($pc_count == 0) {
        for ($i = 1; $i <= 48; $i++) {
            $insert_stmt = $conn->prepare("INSERT INTO lab_pcs (lab_name, pc_number, status) VALUES (?, ?, 'Available')");
            $insert_stmt->bind_param("si", $lab, $i);
            $insert_stmt->execute();
            $insert_stmt->close();
        }
    }
    
    // Get all PCs for this lab
    $stmt = $conn->prepare("SELECT id, pc_number, status FROM lab_pcs WHERE lab_name = ? ORDER BY pc_number");
    $stmt->bind_param("s", $lab);
    $stmt->execute();
    $result = $stmt->get_result();
    $lab_pcs[$lab] = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Computer Lab Management</title>
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
                    <h2 class="text-3xl font-medium text-gray-800 tracking-tight">Computer Lab Management</h2>
                    <p class="text-gray-500 font-light mt-2">Streamline Laboratory Operations with Smart, Automated Control</p>
                </div>
                
                <!-- Quick Stats -->
                <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mt-4 md:mt-0">
                    <div class="bg-white p-3 rounded-lg border border-gray-200 shadow-xs">
                        <p class="text-xs text-gray-500">Total Labs</p>
                        <p class="text-xl font-semibold text-purple-600"><?php echo count($labs); ?></p>
                    </div>
                    <div class="bg-white p-3 rounded-lg border border-gray-200 shadow-xs">
                        <p class="text-xs text-gray-500">Total PCs</p>
                        <p class="text-xl font-semibold text-indigo-600"><?php echo array_sum(array_map('count', $lab_pcs)); ?></p>
                    </div>
                    <div class="bg-white p-3 rounded-lg border border-gray-200 shadow-xs">
                        <p class="text-xs text-gray-500">In Use</p>
                        <p class="text-xl font-semibold text-red-600">
                            <?php 
                                $used = 0;
                                foreach ($lab_pcs as $lab => $pcs) {
                                    $used += count(array_filter($pcs, function($pc) { 
                                        return $pc['status'] === 'Used'; 
                                    }));
                                }
                                echo $used;
                            ?>
                        </p>
                    </div>
                    <div class="bg-white p-3 rounded-lg border border-gray-200 shadow-xs">
                        <p class="text-xs text-gray-500">Available</p>
                        <p class="text-xl font-semibold text-green-600">
                            <?php 
                                $available = 0;
                                foreach ($lab_pcs as $lab => $pcs) {
                                    $available += count(array_filter($pcs, function($pc) { 
                                        return $pc['status'] === 'Available'; 
                                    }));
                                }
                                echo $available;
                            ?>
                        </p>
                    </div>
                </div>
            </div>
            <div class="w-20 h-1 bg-gradient-to-r from-purple-400 to-indigo-400 rounded-full"></div>
        </div>

        <?php if (isset($_SESSION['bulk_message'])): ?>
            <div class="bg-blue-100 border-l-4 border-blue-500 text-blue-700 p-4 rounded-md mb-6">
                <div class="flex items-center">
                    <i class="fas fa-info-circle mr-2"></i>
                    <p><?php echo $_SESSION['bulk_message']; unset($_SESSION['bulk_message']); ?></p>
                </div>
            </div>
        <?php endif; ?>

        <!-- Lab Tabs with Search -->
        <div class="mb-6 bg-white rounded-lg border border-gray-200 p-4">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-4">
                <ul class="flex flex-wrap -mb-px" id="labTabs" role="tablist">
                    <?php foreach ($labs as $index => $lab): ?>
                        <li class="mr-2" role="presentation">
                            <button 
                                onclick="showLabTab('<?php echo str_replace(' ', '-', strtolower($lab)); ?>')" 
                                id="tab-<?php echo str_replace(' ', '-', strtolower($lab)); ?>"
                                class="inline-block p-3 border-b-2 rounded-t-lg transition-all duration-200 <?php echo $index === 0 ? 'border-purple-500 text-purple-600' : 'border-transparent text-gray-500 hover:text-purple-500 hover:border-purple-300'; ?>"
                                role="tab"
                                aria-controls="<?php echo str_replace(' ', '-', strtolower($lab)); ?>"
                                aria-selected="<?php echo $index === 0 ? 'true' : 'false'; ?>"
                            >
                                <i class="fas fa-laptop mr-2"></i>
                                <?php echo htmlspecialchars($lab); ?>
                            </button>
                        </li>
                    <?php endforeach; ?>
                </ul>
                
                <div class="relative w-full md:w-64">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <i class="fas fa-search text-gray-400"></i>
                    </div>
                    <input 
                        type="text" 
                        id="pcSearch" 
                        placeholder="Search PC..." 
                        class="w-full pl-10 pr-4 py-2 bg-gray-50 border border-gray-200 rounded-lg text-gray-700 placeholder-gray-400 focus:ring-2 focus:ring-purple-200 focus:border-transparent transition-all duration-200"
                        onkeyup="filterPCs()"
                    >
                </div>
            </div>
            
            <!-- Status Legend -->
            <div class="flex flex-wrap gap-3 text-xs text-slate-400">
                <div class="flex items-center">
                    <div class="w-3 h-3 rounded-full bg-green-400 mr-1 "></div>
                    <span>Available</span>
                </div>
                <div class="flex items-center">
                    <div class="w-3 h-3 rounded-full bg-red-400 mr-1"></div>
                    <span>In Use</span>
                </div>
                <div class="flex items-center">
                    <div class="w-3 h-3 rounded-full bg-yellow-400 mr-1"></div>
                    <span>Maintenance</span>
                </div>
                <div class="flex items-center ml-auto">
                    <span class="text-gray-500">Last updated: <?php echo date("M d, Y h:i A"); ?></span>
                </div>
            </div>
        </div>

        <!-- Lab Content -->
        <div id="labContent">
            <?php foreach ($labs as $index => $lab): ?>
                <div class="<?php echo $index === 0 ? 'block' : 'hidden'; ?> bg-white rounded-lg border border-gray-200 p-6 shadow-xs" 
                     id="<?php echo str_replace(' ', '-', strtolower($lab)); ?>" 
                     role="tabpanel" 
                     aria-labelledby="<?php echo str_replace(' ', '-', strtolower($lab)); ?>-tab">
                    
                    <div class="mb-6 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                        <div>
                            <h3 class="text-xl font-semibold text-gray-700 flex items-center">
                                <i class="fas fa-door-open text-purple-500 mr-2"></i>
                                <?php echo $lab; ?>
                            </h3>
                            <p class="text-sm text-gray-500 mt-1"><?php echo count($lab_pcs[$lab]); ?> computer stations</p>
                        </div>
                        
                        <div class="flex flex-wrap gap-4">
                            <div class="text-center">
                                <span class="block text-sm font-medium text-gray-500">Available</span>
                                <span class="text-lg font-semibold text-green-600">
                                    <?php echo count(array_filter($lab_pcs[$lab], function($pc) { return $pc['status'] === 'Available'; })); ?>
                                </span>
                            </div>
                            <div class="text-center">
                                <span class="block text-sm font-medium text-gray-500">In Use</span>
                                <span class="text-lg font-semibold text-red-600">
                                    <?php echo count(array_filter($lab_pcs[$lab], function($pc) { return $pc['status'] === 'Used'; })); ?>
                                </span>
                            </div>
                            <div class="text-center">
                                <span class="block text-sm font-medium text-gray-500">Maintenance</span>
                                <span class="text-lg font-semibold text-yellow-600">
                                    <?php echo count(array_filter($lab_pcs[$lab], function($pc) { return $pc['status'] === 'Maintenance'; })); ?>
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Bulk Update Form -->
                    <form method="POST" class="mb-6 bg-gradient-to-r from-purple-50 to-indigo-50 p-4 rounded-lg border border-purple-100">
                        <input type="hidden" name="lab_name" value="<?php echo $lab; ?>">
                        <div class="flex flex-col md:flex-row md:items-end gap-4">
                            <div class="flex-1">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Batch Update Status</label>
                                <div class="flex gap-2">
                                    <select name="bulk_status" class="flex-1 p-2 bg-white border border-gray-200 rounded-md text-gray-700 focus:ring-2 focus:ring-purple-200 focus:border-transparent">
                                        <option value="Available">Set All to Available</option>
                                        <option value="Used">Set All to In Use</option>
                                        <option value="Maintenance">Set All to Maintenance</option>
                                    </select>
                                    <button type="submit" name="bulk_update" class="px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-md transition-all duration-200 flex items-center">
                                        <i class="fas fa-sync-alt mr-2"></i> Apply
                                    </button>
                                </div>
                            </div>
                            <div>
                                <button type="button" onclick="printLabReport('<?php echo $lab; ?>')" class="px-4 py-2 bg-white border border-gray-200 text-gray-700 rounded-md hover:bg-gray-50 transition-all duration-200 flex items-center">
                                    <i class="fas fa-print mr-2"></i> Print Report
                                </button>
                            </div>
                        </div>
                    </form>
                    
                    <!-- PC Grid -->
                    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-4" id="pcGrid-<?php echo str_replace(' ', '-', strtolower($lab)); ?>">
                        <?php foreach ($lab_pcs[$lab] as $pc): ?>
                            <div class="pc-card relative p-4 rounded-lg border cursor-pointer transition-all duration-200 hover:shadow-md hover:-translate-y-1
                                <?php 
                                    if ($pc['status'] === 'Available') echo 'bg-green-50 border-green-200';
                                    elseif ($pc['status'] === 'Used') echo 'bg-red-50 border-red-200';
                                    else echo 'bg-yellow-50 border-yellow-200';
                                ?>"
                                onclick="openPcModal('<?php echo $pc['id']; ?>', '<?php echo $lab; ?>', '<?php echo $pc['pc_number']; ?>', '<?php echo $pc['status']; ?>')">
                                
                                <div class="flex flex-col items-center text-center">
                                    <i class="fas fa-desktop text-4xl mb-3 
                                        <?php 
                                            if ($pc['status'] === 'Available') echo 'text-green-500';
                                            elseif ($pc['status'] === 'Used') echo 'text-red-500';
                                            else echo 'text-yellow-500';
                                        ?>">
                                    </i>
                                    <div class="text-lg font-semibold mb-1 text-slate-500">PC <?php echo $pc['pc_number']; ?></div>
                                    <div class="text-xs px-3 py-1 rounded-full font-medium
                                        <?php 
                                            if ($pc['status'] === 'Available') echo 'bg-green-100 text-green-800';
                                            elseif ($pc['status'] === 'Used') echo 'bg-red-100 text-red-800';
                                            else echo 'bg-yellow-100 text-yellow-800';
                                        ?>">
                                        <?php echo $pc['status']; ?>
                                    </div>
                                    <?php if ($pc['status'] === 'Used'): ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- PC Status Modal -->
<div id="pcModal" class="fixed inset-0 flex items-center justify-center hidden z-50 p-4 bg-black/30 backdrop-blur-sm">
    <div class="bg-white rounded-xl shadow-2xl border border-gray-200 p-6 w-full max-w-md transform transition-all duration-300 scale-95 opacity-0" id="modalContent">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-xl font-semibold text-gray-800" id="pcModalTitle">Update PC Status</h3>
            <button onclick="closePcModal()" class="text-gray-400 hover:text-gray-500">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <form id="pcStatusForm" method="POST">
            <input type="hidden" name="pc_id" id="modalPcId">
            <input type="hidden" name="lab_name" id="modalLabName">
            
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">PC Number</label>
                <input type="text" id="modalPcNumber" class="w-full p-2 bg-gray-50 border border-gray-200 rounded-md text-gray-700" readonly>
            </div>
            
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Current Status</label>
                <div id="modalCurrentStatus" class="w-full p-2 rounded-md text-sm font-medium
                    <?php 
                        // This will be dynamically set by JavaScript
                    ?>">
                </div>
            </div>
            
            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-1">Update Status</label>
                <select name="status" id="modalStatusSelect" class="w-full p-2 bg-white border border-gray-200 rounded-md text-gray-700 focus:ring-2 focus:ring-purple-200 focus:border-transparent" required>
                    <option value="Available">Available</option>
                    <option value="Used">In Use</option>
                    <option value="Maintenance">Maintenance</option>
                </select>
            </div>
            
            <div class="flex justify-end space-x-3">
                <button type="button" onclick="closePcModal()" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-md transition-all duration-200">
                    Cancel
                </button>
                <button type="submit" name="update_status" class="px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-md transition-all duration-200 flex items-center">
                    <i class="fas fa-save mr-2"></i> Update
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// Enhanced JavaScript functions
function showLabTab(labId) {
    document.querySelectorAll('#labContent > div').forEach(content => {
        content.classList.add('hidden');
    });
    document.getElementById(labId).classList.remove('hidden');
    
    // Update active tab styling
    document.querySelectorAll('#labTabs button').forEach(tab => {
        tab.classList.remove('border-purple-500', 'text-purple-600');
        tab.classList.add('border-transparent', 'text-gray-500');
    });
    document.getElementById(`tab-${labId}`).classList.add('border-purple-500', 'text-purple-600');
    document.getElementById(`tab-${labId}`).classList.remove('border-transparent', 'text-gray-500');
}

function openPcModal(pcId, labName, pcNumber, currentStatus) {
    document.getElementById('modalPcId').value = pcId;
    document.getElementById('modalLabName').value = labName;
    document.getElementById('modalPcNumber').value = pcNumber;
    
    // Dynamically set status display
    const statusElement = document.getElementById('modalCurrentStatus');
    statusElement.textContent = currentStatus;
    statusElement.className = `w-full p-2 rounded-md text-sm font-medium ${
        currentStatus === 'Available' ? 'bg-green-100 text-green-800' :
        currentStatus === 'Used' ? 'bg-red-100 text-red-800' :
        'bg-yellow-100 text-yellow-800'
    }`;
    
    document.getElementById('modalStatusSelect').value = currentStatus;
    
    // Show modal with animation
    const modal = document.getElementById('pcModal');
    modal.classList.remove('hidden');
    setTimeout(() => {
        document.getElementById('modalContent').classList.remove('scale-95', 'opacity-0');
    }, 10);
}

function closePcModal() {
    const modalContent = document.getElementById('modalContent');
    modalContent.classList.add('scale-95', 'opacity-0');
    setTimeout(() => {
        document.getElementById('pcModal').classList.add('hidden');
    }, 300);
}

function filterPCs() {
    const searchTerm = document.getElementById('pcSearch').value.toLowerCase();
    const activeLab = document.querySelector('#labContent > div:not(.hidden)').id;
    const pcGrid = document.getElementById(`pcGrid-${activeLab}`);
    
    if (pcGrid) {
        const pcCards = pcGrid.getElementsByClassName('pc-card');
        Array.from(pcCards).forEach(card => {
            const pcNumber = card.textContent.toLowerCase();
            if (pcNumber.includes(searchTerm)) {
                card.classList.remove('hidden');
            } else {
                card.classList.add('hidden');
            }
        });
    }
}

function printLabReport(labName) {
    // This would open a print-friendly version of the lab report
    window.open(`lab_report.php?lab=${encodeURIComponent(labName)}`, '_blank');
}

// Close modal when clicking outside
document.getElementById('pcModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closePcModal();
    }
});

        function showLabTab(labId) {
            // Hide all lab content sections
            document.querySelectorAll('[role="tabpanel"]').forEach(panel => {
                panel.classList.add('hidden');
            });

            // Deactivate all tabs
            document.querySelectorAll('[role="tab"]').forEach(tab => {
                tab.classList.remove('border-purple-500', 'text-purple-500');
                tab.classList.add('border-transparent', 'text-slate-400');
                tab.setAttribute('aria-selected', 'false');
            });

            // Activate clicked tab
            const activeTab = document.getElementById(`tab-${labId}`);
            activeTab.classList.add('border-purple-500', 'text-purple-500');
            activeTab.classList.remove('border-transparent', 'text-slate-400');
            activeTab.setAttribute('aria-selected', 'true');

            // Show corresponding content
            const activePanel = document.getElementById(labId);
            if (activePanel) activePanel.classList.remove('hidden');
        }
        

        // PC Modal functions
        function openPcModal(pcId, labName, pcNumber, currentStatus) {
            document.getElementById('modalPcId').value = pcId;
            document.getElementById('modalLabName').value = labName;
            document.getElementById('modalPcNumber').value = 'PC ' + pcNumber;
            document.getElementById('modalCurrentStatus').value = currentStatus;
            document.getElementById('modalStatusSelect').value = currentStatus;
            
            document.getElementById('pcModalTitle').textContent = labName + ' - PC ' + pcNumber;
            
            document.getElementById('pcModal').classList.remove('hidden');
            setTimeout(() => {
                document.getElementById('pcModal').querySelector('div').classList.remove('scale-95', 'opacity-0');
                document.getElementById('pcModal').querySelector('div').classList.add('scale-100', 'opacity-100');
            }, 10);
        }

        function closePcModal() {
            document.getElementById('pcModal').querySelector('div').classList.remove('scale-100', 'opacity-100');
            document.getElementById('pcModal').querySelector('div').classList.add('scale-95', 'opacity-0');
            
            setTimeout(() => {
                document.getElementById('pcModal').classList.add('hidden');
            }, 300);
        }

        // Close modal when clicking outside
        document.getElementById('pcModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closePcModal();
            }
        });

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