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
    <!-- Sidebar -->
    <div class="fixed inset-y-0 left-0 w-64 bg-slate-900/80 backdrop-blur-md border-r border-white/10 shadow-xl z-50 flex flex-col">
        <!-- Fixed header with profile icon -->
        <div class="p-5 border-b border-white/10 flex-shrink-0">
            <div class="flex items-center space-x-3">
                <!-- Admin Icon -->
                <div class="w-10 h-10 rounded-full bg-blue-600 flex items-center justify-center">
                    <i class="fas fa-user-shield text-white"></i>
                </div>
                <!-- Admin Name -->
                <h2 class="text-xl font-semibold text-white">Admin <?php echo htmlspecialchars($firstname); ?></h2>
            </div>
            <p class="text-sm text-slate-400 mt-2">Reservation Logs</p>
        </div>
        
        <!-- Scrollable navigation -->
        <nav class="mt-5 flex-1 overflow-y-auto sidebar-scroll">
            <ul>
                <li>
                    <a href="admin_dashboard.php" class="flex items-center px-5 py-3 text-slate-300 hover:bg-slate-700/20 hover:text-white transition-all duration-200">
                        <span>Dashboard</span>
                    </a>
                </li>
                <li>
                    <a href="todays_sitins.php" class="flex items-center px-5 py-3 text-slate-300 hover:bg-slate-700/20 hover:text-white transition-all duration-200">
                        <span>Current Sit-in Records</span>
                    </a>
                </li>
                <li>
                    <a href="sit_in_records.php" class="flex items-center px-5 py-3 text-slate-300 hover:bg-slate-700/20 hover:text-white transition-all duration-200">
                        <span>Sit-in Reports</span>
                    </a>
                </li>
                <li>
                    <a href="feedback_records.php" class="flex items-center px-5 py-3 text-slate-300 hover:bg-slate-700/20 hover:text-white transition-all duration-200">
                        <span>Feedback Reports</span>
                    </a>
                </li>
                <li>
                    <a href="manage_sitins.php" class="flex items-center px-5 py-3 text-slate-300 hover:bg-slate-700/20 hover:text-white transition-all duration-200">
                        <span>Manage Sit-ins</span>
                    </a>
                </li>
                <li>
                    <a href="create_announcement.php" class="flex items-center px-5 py-3 text-slate-300 hover:bg-slate-700/20 hover:text-white transition-all duration-200">
                        <span>Announcements</span>
                    </a>
                </li>
                <li>
                    <a href="studentlist.php" class="flex items-center px-5 py-3 text-slate-300 hover:bg-slate-700/20 hover:text-white transition-all duration-200">
                        <span>List of Students</span>
                    </a>
                </li>
                <li>
                    <a href="manage_reservation.php" class="flex items-center px-5 py-3 text-slate-300 hover:bg-slate-700/20 hover:text-white transition-all duration-200">
                        <span>Reservations Requests</span>
                    </a>
                </li>
                <li>
                    <a href="reservation_logs.php" class="flex items-center px-5 py-3 bg-slate-700/20 text-white">
                        <span>Reservation Logs</span>
                    </a>
                </li>
                <li>
                    <a href="admin_upload_resources.php" class="flex items-center px-5 py-3 text-slate-300 hover:bg-slate-700/20 hover:text-white transition-all duration-200">
                        <span>Upload Resources</span>
                    </a>
                </li>
                <li>
                    <a href="admin_leaderboard.php" class="flex items-center px-5 py-3 text-slate-300 hover:bg-slate-700/20 hover:text-white transition-all duration-200">
                        <span>Leaderboard</span>
                    </a>
                </li>
                <li>
                    <a href="admin_lab_schedule.php" class="flex items-center px-5 py-3 text-slate-300 hover:bg-slate-700/20 hover:text-white transition-all duration-200">
                        <span>Lab Schedule</span>
                    </a>
                </li>
                <li>
                    <a href="lab_management.php" class="flex items-center px-5 py-3 text-slate-300 hover:bg-slate-700/20 hover:text-white transition-all duration-200">
                        <span>Lab Management</span>
                    </a>
                </li>
            </ul>
        </nav>
        
        <!-- Fixed footer with logout -->
        <div class="p-5 border-t border-white/10 flex-shrink-0">
            <a href="logout.php" class="flex items-center px-5 py-3 text-slate-300 hover:bg-red-600/20 hover:text-red-400 transition-all duration-200">
                <span>Log Out</span>
            </a>
        </div>
    </div>

    <!-- Main Content -->
    <div class="ml-64 p-6">
        <div class="bg-slate-800/50 backdrop-blur-sm rounded-xl shadow-lg border border-white/5 p-6 hover:shadow-xl transition-all duration-300">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-2xl font-semibold text-white">Reservation Logs</h2>
                
                <!-- Filters -->
                <div class="flex items-center space-x-4">
                    <form method="GET" class="flex items-center space-x-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-300 mb-1">Status</label>
                            <select name="status" class="p-2 bg-slate-700/50 border border-slate-600 rounded-md text-white">
                                <option value="all" <?php echo $status_filter == 'all' ? 'selected' : ''; ?>>All Statuses</option>
                                <option value="pending" <?php echo $status_filter == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                <option value="approved" <?php echo $status_filter == 'approved' ? 'selected' : ''; ?>>Approved</option>
                                <option value="disapproved" <?php echo $status_filter == 'disapproved' ? 'selected' : ''; ?>>Disapproved</option>
                            </select>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-slate-300 mb-1">Lab</label>
                            <select name="lab" class="p-2 bg-slate-700/50 border border-slate-600 rounded-md text-white">
                                <option value="all" <?php echo $lab_filter == 'all' ? 'selected' : ''; ?>>All Labs</option>
                                <?php foreach ($labs as $lab): ?>
                                    <option value="<?php echo $lab; ?>" <?php echo $lab_filter == $lab ? 'selected' : ''; ?>>
                                        <?php echo $lab; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-slate-300 mb-1">Date</label>
                            <input type="date" name="date" value="<?php echo $date_filter; ?>" class="p-2 bg-slate-700/50 border border-slate-600 rounded-md text-white">
                        </div>
                        
                        <div class="pt-5">
                            <button type="submit" class="p-2 bg-blue-600/50 hover:bg-blue-600 text-white rounded-md transition-all duration-200">
                                <i class="fas fa-filter mr-1"></i> Filter
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <?php if (isset($_SESSION['success_message'])): ?>
                <div class="bg-green-600/20 text-green-400 p-3 rounded-md mb-4">
                    <?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?>
                </div>
            <?php endif; ?>
            
            <div class="overflow-x-auto main-content-scroll">
                <table class="min-w-full divide-y divide-slate-600">
                    <thead class="bg-slate-700/50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-slate-300 uppercase tracking-wider">Student</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-slate-300 uppercase tracking-wider">ID</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-slate-300 uppercase tracking-wider">Purpose</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-slate-300 uppercase tracking-wider">Lab</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-slate-300 uppercase tracking-wider">PC</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-slate-300 uppercase tracking-wider">Date</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-slate-300 uppercase tracking-wider">Time</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-slate-300 uppercase tracking-wider">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-700">
                        <?php while ($row = $reservations->fetch_assoc()): ?>
                        <tr>
                            <td class="px-4 py-4 text-sm"><?php echo htmlspecialchars($row['firstname'] . ' ' . $row['lastname']); ?></td>
                            <td class="px-4 py-4 text-sm"><?php echo htmlspecialchars($row['idno']); ?></td>
                            <td class="px-4 py-4 text-sm"><?php echo htmlspecialchars($row['purpose']); ?></td>
                            <td class="px-4 py-4 text-sm"><?php echo htmlspecialchars($row['lab_room']); ?></td>
                            <td class="px-4 py-4 text-sm"><?php echo $row['pc_number'] ? 'PC ' . htmlspecialchars($row['pc_number']) : '-'; ?></td>
                            <td class="px-4 py-4 text-sm"><?php echo htmlspecialchars($row['reservation_date']); ?></td>
                            <td class="px-4 py-4 text-sm"><?php echo htmlspecialchars($row['time_in']); ?></td>
                            <td class="px-4 py-4 text-sm">
                                <span class="px-2 py-1 rounded-full text-xs font-medium 
                                    <?php echo 'status-' . $row['status'] ?>">
                                    <?php echo ucfirst($row['status']); ?>
                                </span>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                        
                        <?php if ($reservations->num_rows === 0): ?>
                        <tr>
                            <td colspan="8" class="px-4 py-4 text-center text-sm text-slate-400">No reservations found</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Status Update Modal -->
    <div id="statusModal" class="fixed inset-0 bg-black/50 backdrop-blur-sm hidden z-50 flex items-center justify-center p-4">
        <div class="bg-slate-800/90 backdrop-blur-md rounded-xl shadow-2xl border border-white/10 p-6 w-full max-w-md">
            <h3 class="text-xl font-semibold mb-4">Update Reservation Status</h3>
            <form id="statusForm" method="POST">
                <input type="hidden" name="reservation_id" id="modalReservationId">
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-slate-300 mb-1">Status</label>
                    <select name="status" id="modalStatus" class="w-full p-2 bg-slate-700/50 border border-slate-600 rounded-md text-white">
                        <option value="pending">Pending</option>
                        <option value="approved">Approved</option>
                        <option value="disapproved">Disapproved</option>
                    </select>
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-slate-300 mb-1">Admin Notes</label>
                    <textarea name="notes" id="modalNotes" rows="3" class="w-full p-2 bg-slate-700/50 border border-slate-600 rounded-md text-white"></textarea>
                </div>
                
                <div class="flex justify-end space-x-3">
                    <button type="button" onclick="closeStatusModal()" class="px-4 py-2 bg-slate-600/50 hover:bg-slate-600 text-white rounded-md transition-all duration-200">
                        Cancel
                    </button>
                    <button type="submit" name="update_status" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-md transition-all duration-200">
                        Update Status
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openStatusModal(reservationId, currentStatus, currentNotes) {
            document.getElementById('modalReservationId').value = reservationId;
            document.getElementById('modalStatus').value = currentStatus;
            document.getElementById('modalNotes').value = currentNotes;
            document.getElementById('statusModal').classList.remove('hidden');
        }
        
        function closeStatusModal() {
            document.getElementById('statusModal').classList.add('hidden');
        }
    </script>
</body>
</html>