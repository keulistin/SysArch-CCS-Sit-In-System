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

// Handle status updates
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_status'])) {
    // ... (keep your existing status update logic) ...
}

// Get all reservations with filter options
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';
$lab_filter = isset($_GET['lab']) ? $_GET['lab'] : 'all';
$date_filter = isset($_GET['date']) ? $_GET['date'] : '';

$query = "SELECT r.id, r.student_id, u.idno, u.firstname, u.lastname, 
                 r.purpose, r.lab_room, r.pc_number, 
                 r.reservation_date, r.time_in, r.status, r.admin_notes, r.created_at
          FROM reservations r
          JOIN users u ON r.student_id = u.id
          WHERE 1=1";

$params = [];
$types = '';

if ($status_filter != 'all') {
    $query .= " AND r.status = ?";
    $params[] = $status_filter;
    $types .= 's';
}

if ($lab_filter != 'all') {
    $query .= " AND r.lab_room = ?";
    $params[] = $lab_filter;
    $types .= 's';
}

if (!empty($date_filter)) {
    $query .= " AND r.reservation_date = ?";
    $params[] = $date_filter;
    $types .= 's';
}

$query .= " ORDER BY r.reservation_date DESC, r.time_in DESC";

$stmt = $conn->prepare($query);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$reservations = $stmt->get_result();

// Get unique labs for filter
$labs_result = $conn->query("SELECT DISTINCT lab_room FROM reservations ORDER BY lab_room");
$labs = [];
while ($row = $labs_result->fetch_assoc()) {
    $labs[] = $row['lab_room'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reservation Logs - Admin</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
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
        .main-content-scroll {
            scrollbar-width: thin;
            scrollbar-color: #4b5563 #1e293b;
            max-height: calc(100vh - 16rem);
        }
        .main-content-scroll::-webkit-scrollbar {
            width: 6px;
        }
        .main-content-scroll::-webkit-scrollbar-track {
            background: #1e293b;
        }
        .main-content-scroll::-webkit-scrollbar-thumb {
            background-color: #4b5563;
            border-radius: 3px;
        }
        .status-pending {
            background-color: rgba(245, 158, 11, 0.1);
            color: rgba(245, 158, 11, 1);
        }
        .status-approved {
            background-color: rgba(16, 185, 129, 0.1);
            color: rgba(16, 185, 129, 1);
        }
        .status-disapproved {
            background-color: rgba(239, 68, 68, 0.1);
            color: rgba(239, 68, 68, 1);
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

<!-- Main Content -->
<div class="min-h-screen bg-purple-100 main-content-cont">
    <div>
        <!-- Header Section -->
        <div class="border-b border-gray-200 p-6">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between">
                <div>
                    <h2 class="text-3xl font-medium text-gray-800 tracking-tight">Reservation Logs</h2>
                    <p class="text-gray-500 font-light mt-2">Track and manage all reservation records</p>
                </div>
                
                <!-- Quick Stats -->
                <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mt-4 md:mt-0">
                    <div class="bg-white p-3 rounded-lg border border-gray-200 shadow-xs">
                        <p class="text-xs text-gray-500">Total</p>
                        <p class="text-xl font-semibold text-purple-600"><?php echo $reservations->num_rows; ?></p>
                    </div>
                    <div class="bg-white p-3 rounded-lg border border-gray-200 shadow-xs">
                        <p class="text-xs text-gray-500">Pending</p>
                        <p class="text-xl font-semibold text-yellow-600">
                            <?php 
                                $pending = 0;
                                $reservations->data_seek(0);
                                while ($row = $reservations->fetch_assoc()) {
                                    if ($row['status'] === 'Pending') $pending++;
                                }
                                echo $pending;
                                $reservations->data_seek(0);
                            ?>
                        </p>
                    </div>
                    <div class="bg-white p-3 rounded-lg border border-gray-200 shadow-xs">
                        <p class="text-xs text-gray-500">Approved</p>
                        <p class="text-xl font-semibold text-green-600">
                            <?php 
                                $approved = 0;
                                $reservations->data_seek(0);
                                while ($row = $reservations->fetch_assoc()) {
                                    if ($row['status'] === 'approved') $approved++;
                                }
                                echo $approved;
                                $reservations->data_seek(0);
                            ?>
                        </p>
                    </div>
                    <div class="bg-white p-3 rounded-lg border border-gray-200 shadow-xs">
                        <p class="text-xs text-gray-500">Rejected</p>
                        <p class="text-xl font-semibold text-red-600">
                            <?php 
                                $rejected = 0;
                                $reservations->data_seek(0);
                                while ($row = $reservations->fetch_assoc()) {
                                    if ($row['status'] === 'disapproved') $rejected++;
                                }
                                echo $rejected;
                                $reservations->data_seek(0);
                            ?>
                        </p>
                    </div>
                </div>
            </div>
            <div class="w-20 h-1 bg-gradient-to-r from-purple-400 to-indigo-400 rounded-full mt-4"></div>
        </div>

        <!-- Messages -->
        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="bg-blue-100 border-l-4 border-blue-500 text-blue-700 p-4 mx-6 mt-4 rounded-md">
                <div class="flex items-center">
                    <i class="fas fa-check-circle mr-2"></i>
                    <p><?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?></p>
                </div>
            </div>
        <?php endif; ?>

        <!-- Filters -->
        <div class="p-6 bg-white border-b border-gray-200">
            <form method="GET" class="flex flex-col md:flex-row md:items-end gap-4">
                <div class="flex-1 grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                        <select name="status" class="w-full p-2 bg-gray-50 border border-gray-200 rounded-md text-gray-700 focus:ring-2 focus:ring-purple-200">
                            <option value="all" <?php echo $status_filter == 'all' ? 'selected' : ''; ?>>All Statuses</option>
                            <option value="Pending" <?php echo $status_filter == 'Pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="approved" <?php echo $status_filter == 'approved' ? 'selected' : ''; ?>>Approved</option>
                            <option value="disapproved" <?php echo $status_filter == 'disapproved' ? 'selected' : ''; ?>>Disapproved</option>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Lab</label>
                        <select name="lab" class="w-full p-2 bg-gray-50 border border-gray-200 rounded-md text-gray-700 focus:ring-2 focus:ring-purple-200">
                            <option value="all" <?php echo $lab_filter == 'all' ? 'selected' : ''; ?>>All Labs</option>
                            <?php foreach ($labs as $lab): ?>
                                <option value="<?php echo $lab; ?>" <?php echo $lab_filter == $lab ? 'selected' : ''; ?>>
                                    <?php echo $lab; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Date</label>
                        <input type="date" name="date" value="<?php echo $date_filter; ?>" class="w-full p-2 bg-gray-50 border border-gray-200 rounded-md text-gray-700 focus:ring-2 focus:ring-purple-200">
                    </div>
                </div>
                
                <div>
                    <button type="submit" class="px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-md transition-all duration-200 flex items-center">
                        <i class="fas fa-filter mr-2"></i> Apply Filters
                    </button>
                </div>
            </form>
        </div>

        <!-- Reservations Table -->
        <div class="bg-white rounded-lg border border-gray-200 overflow-hidden mt-5">
            <div class="overflow-x-auto">
                <table class="w-full divide-y divide-gray-200">
                <thead class="bg-gradient-to-r from-purple-400 to-indigo-400">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">Student</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">ID</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">Purpose</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">Lab</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">PC</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">Date</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">Time</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php while ($row = $reservations->fetch_assoc()): ?>
                        <tr class="hover:bg-purple-50 transition-colors duration-150">
                            <td class="px-4 py-4 text-sm text-gray-800 font-medium"><?php echo htmlspecialchars($row['firstname'] . ' ' . $row['lastname']); ?></td>
                            <td class="px-4 py-4 text-sm text-gray-600"><?php echo htmlspecialchars($row['idno']); ?></td>
                            <td class="px-4 py-4 text-sm text-gray-600"><?php echo htmlspecialchars($row['purpose']); ?></td>
                            <td class="px-4 py-4 text-sm text-gray-600"><?php echo htmlspecialchars($row['lab_room']); ?></td>
                            <td class="px-4 py-4 text-sm text-gray-600"><?php echo $row['pc_number'] ? 'PC ' . htmlspecialchars($row['pc_number']) : '-'; ?></td>
                            <td class="px-4 py-4 text-sm text-gray-600"><?php echo htmlspecialchars($row['reservation_date']); ?></td>
                            <td class="px-4 py-4 text-sm text-gray-600"><?php echo htmlspecialchars($row['time_in']); ?></td>
                            <td class="px-4 py-4 text-sm">
                                <span class="px-2 py-1 rounded-full text-xs font-medium 
                                    <?php 
                                        if ($row['status'] === 'approved') echo 'bg-green-100 text-green-800';
                                        elseif ($row['status'] === 'Pending') echo 'bg-yellow-100 text-yellow-800';
                                        else echo 'bg-red-100 text-red-800';
                                    ?>">
                                    <?php echo ucfirst($row['status']); ?>
                                </span>
                            </td>

                        </tr>
                        <?php endwhile; ?>
                        
                        <?php if ($reservations->num_rows === 0): ?>
                        <tr>
                            <td colspan="9" class="px-4 py-4 text-center text-sm text-white">
                                <i class="fas fa-calendar-times text-2xl text-gray-300 mb-2"></i>
                                <p>No reservations found matching your filters</p>
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Status Update Modal -->
<div id="statusModal" class="fixed inset-0 flex items-center justify-center hidden z-50 p-4 bg-black/30 backdrop-blur-sm">
    <div class="bg-white rounded-xl shadow-2xl border border-gray-200 p-6 w-full max-w-md">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-xl font-semibold text-gray-800">Update Reservation Status</h3>
            <button onclick="closeStatusModal()" class="text-gray-400 hover:text-gray-500">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <form id="statusForm" method="POST">
            <input type="hidden" name="reservation_id" id="modalReservationId">
            
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                <select name="status" id="modalStatus" class="w-full p-2 bg-gray-50 border border-gray-200 rounded-md text-gray-700 focus:ring-2 focus:ring-purple-200">
                    <option value="pending">Pending</option>
                    <option value="approved">Approved</option>
                    <option value="disapproved">Disapproved</option>
                </select>
            </div>
            
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Admin Notes</label>
                <textarea name="notes" id="modalNotes" rows="3" class="w-full p-2 bg-gray-50 border border-gray-200 rounded-md text-gray-700 focus:ring-2 focus:ring-purple-200"></textarea>
            </div>
            
            <div class="flex justify-end space-x-3">
                <button type="button" onclick="closeStatusModal()" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-md transition-colors duration-200">
                    Cancel
                </button>
                <button type="submit" name="update_status" class="px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-md transition-colors duration-200">
                    Update Status
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openStatusModal(reservationId, currentStatus, notes) {
    document.getElementById('modalReservationId').value = reservationId;
    document.getElementById('modalStatus').value = currentStatus;
    document.getElementById('modalNotes').value = notes;
    document.getElementById('statusModal').classList.remove('hidden');
}

function closeStatusModal() {
    document.getElementById('statusModal').classList.add('hidden');
}

        function openStatusModal(reservationId, currentStatus, currentNotes) {
            document.getElementById('modalReservationId').value = reservationId;
            document.getElementById('modalStatus').value = currentStatus;
            document.getElementById('modalNotes').value = currentNotes;
            document.getElementById('statusModal').classList.remove('hidden');
        }
        
        function closeStatusModal() {
            document.getElementById('statusModal').classList.add('hidden');
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