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

// Handle approval/rejection
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    $reservation_id = $_POST['reservation_id'];
    $action = $_POST['action'];
    $notes = isset($_POST['notes']) ? trim($_POST['notes']) : '';
    
    if ($action == 'approve') {
        // Get reservation details including user's session count and PC number
        $stmt = $conn->prepare("SELECT r.student_id, r.purpose, r.lab_room, r.pc_number, r.reservation_date, r.time_in, u.idno, u.remaining_sessions FROM reservations r JOIN users u ON r.student_id = u.id WHERE r.id = ?");
        $stmt->bind_param("i", $reservation_id);
        $stmt->execute();
        $stmt->bind_result($student_id, $purpose, $lab_room, $pc_number, $reservation_date, $time_in, $student_idno, $remaining_sessions);
        $stmt->fetch();
        $stmt->close();
        
        // Combine date and time to create a proper datetime value
        $start_datetime = $reservation_date . ' ' . $time_in;
        
        // Validate the datetime
        if (!strtotime($start_datetime)) {
            $_SESSION['error_message'] = "Invalid reservation date/time combination";
            header("Location: manage_reservation.php");
            exit();
        }
        
        // Start transaction
        $conn->begin_transaction();
        
        try {
            // Update reservation status (no session deduction here)
            $stmt = $conn->prepare("UPDATE reservations SET status = 'approved', admin_notes = ? WHERE id = ?");
            $stmt->bind_param("si", $notes, $reservation_id);
            $stmt->execute();
            $stmt->close();
            
            // If PC was specified, mark it as used
            if (!empty($pc_number)) {
                $stmt = $conn->prepare("UPDATE lab_pcs SET status = 'Used' WHERE lab_name = ? AND pc_number = ?");
                $stmt->bind_param("si", $lab_room, $pc_number);
                $stmt->execute();
                $stmt->close();
            }
            
            // Create sit-in record with proper datetime
            $stmt = $conn->prepare("INSERT INTO sit_in_records (student_id, purpose, lab, pc_number, start_time) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("issss", $student_id, $purpose, $lab_room, $pc_number, $start_datetime);
            if (!$stmt->execute()) {
                throw new Exception("Error creating sit-in record: " . $conn->error);
            }
            $stmt->close();
            
            // Add notification for the student
            $notification_msg = "Your reservation for $lab_room has been approved!";
            $stmt = $conn->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)");
            $stmt->bind_param("ss", $student_idno, $notification_msg);
            $stmt->execute();
            $stmt->close();
            
            $conn->commit();
            
            $_SESSION['success_message'] = "Reservation approved successfully!";
            header("Location: manage_sitins.php");
            exit();
            
        } catch (Exception $e) {
            $conn->rollback();
            $_SESSION['error_message'] = "Error: " . $e->getMessage();
            header("Location: manage_reservation.php");
            exit();
        }
        
    } elseif ($action == 'reject') {
        $stmt = $conn->prepare("UPDATE reservations SET status = 'disapproved', admin_notes = ? WHERE id = ?");
        $stmt->bind_param("si", $notes, $reservation_id);
        $stmt->execute();
        $stmt->close();
        
        // Get student IDNO for notification
        $stmt = $conn->prepare("SELECT u.idno FROM reservations r JOIN users u ON r.student_id = u.id WHERE r.id = ?");
        $stmt->bind_param("i", $reservation_id);
        $stmt->execute();
        $stmt->bind_result($student_idno);
        $stmt->fetch();
        $stmt->close();
        
        // Add notification for rejection
        $notification_msg = "Your reservation has been disapproved" . ($notes ? ": $notes" : "");
        $stmt = $conn->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)");
        $stmt->bind_param("ss", $student_idno, $notification_msg);
        $stmt->execute();
        $stmt->close();
        
        $_SESSION['success_message'] = "Reservation rejected successfully!";
        header("Location: manage_reservation.php");
        exit();
    }
}

// Get pending reservations
$reservations = $conn->query("SELECT r.id, r.student_id, u.idno, u.firstname, u.lastname, u.remaining_sessions, r.purpose, r.lab_room, r.pc_number, r.reservation_date, r.time_in, r.created_at FROM reservations r JOIN users u ON r.student_id = u.id WHERE r.status = 'pending' ORDER BY r.reservation_date, r.time_in");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Reservations</title>
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
        .time-cell {
            min-width: 160px;
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
        
        function openRejectModal(reservationId) {
            document.getElementById('modalReservationId').value = reservationId;
            document.getElementById('rejectModal').classList.remove('hidden');
        }
        
        function closeRejectModal() {
            document.getElementById('rejectModal').classList.add('hidden');
        }
    </script>
</head>
<body class="bg-gradient-to-br from-slate-800 to-slate-900 min-h-screen font-sans text-white">
    <!-- Sidebar -->
    <div class="fixed inset-y-0 left-0 w-64 bg-slate-900/80 backdrop-blur-md border-r border-white/10 shadow-xl z-50 flex flex-col">
        <!-- Fixed header -->
        <div class="p-5 border-b border-white/10 flex-shrink-0">
            <div class="flex items-center space-x-3">
                <!-- Admin Icon -->
                <div class="w-10 h-10 rounded-full bg-blue-600 flex items-center justify-center">
                    <i class="fas fa-user-shield text-white"></i>
                </div>
                <!-- Admin Name -->
                <h2 class="text-xl font-semibold text-white">Admin <?php echo htmlspecialchars($firstname); ?></h2>
            </div>
            <p class="text-sm text-slate-400 mt-2">Reservation Requests</p>
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
                    <a href="manage_reservation.php" class="flex items-center px-5 py-3 bg-blue-600/20 text-white">
                        <span>Reservations Requests</span>
                    </a>
                </li>
                <li>
                    <a href="reservation_logs.php" class="flex items-center px-5 py-3 text-slate-300 hover:bg-slate-700/20 hover:text-white transition-all duration-200">
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
            <h2 class="text-2xl font-semibold mb-6 text-white border-b border-white/10 pb-2">Manage Reservations</h2>
            
            <?php if (isset($_SESSION['success_message'])): ?>
                <div class="bg-green-600/20 text-green-400 p-3 rounded-md mb-4">
                    <?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['error_message'])): ?>
                <div class="bg-red-600/20 text-red-400 p-3 rounded-md mb-4">
                    <?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($reservations && $reservations->num_rows > 0): ?>
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="border-b border-white/10">
                                <th class="p-4 w-4">Student</th>
                                <th class="p-4">ID</th>
                                <th class="p-4">Purpose</th>
                                <th class="p-3">Lab</th>
                                <th class="p-3">PC</th>
                                <th class="p-3">Date</th>
                                <th class="p-3 time-cell">Time</th>
                                <th class="p-3">Sessions Left</th>
                                <th class="p-3">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $reservations->fetch_assoc()): ?>
                                <tr class="hover:bg-slate-700/20 transition-all duration-200 border-b border-white/5">
                                    <td class="p-3"><?php echo htmlspecialchars($row['firstname'] . ' ' . $row['lastname']); ?></td>
                                    <td class="p-3"><?php echo htmlspecialchars($row['idno']); ?></td>
                                    <td class="p-3"><?php echo htmlspecialchars($row['purpose']); ?></td>
                                    <td class="p-3"><?php echo htmlspecialchars($row['lab_room']); ?></td>
                                    <td class="p-3"><?php echo $row['pc_number'] ? 'PC ' . htmlspecialchars($row['pc_number']) : '-'; ?></td>
                                    <td class="p-3"><?php echo htmlspecialchars($row['reservation_date']); ?></td>
                                    <td class="p-3 time-cell"><?php echo htmlspecialchars(date('h:i A', strtotime($row['time_in']))); ?></td>
                                    <td class="p-3"><?php echo htmlspecialchars($row['remaining_sessions']); ?></td>
                                    <td class="p-3 flex space-x-2">
                                        <form method="POST" class="inline">
                                            <input type="hidden" name="reservation_id" value="<?php echo $row['id']; ?>">
                                            <input type="hidden" name="action" value="approve">
                                            <button type="submit" class="p-2 bg-green-600/20 text-green-400 rounded-md hover:bg-green-700/20 transition-all duration-200">
                                                <i class="fas fa-check mr-1"></i> Approve
                                            </button>
                                        </form>
                                        <button onclick="openRejectModal(<?php echo $row['id']; ?>)" class="p-2 bg-red-600/20 text-red-400 rounded-md hover:bg-red-700/20 transition-all duration-200">
                                            <i class="fas fa-times mr-1"></i> Disapprove
                                        </button>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="bg-slate-700/50 p-4 rounded-lg text-center">
                    <p class="text-slate-300">No pending reservations found.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Reject Modal -->
    <div id="rejectModal" class="fixed inset-0 bg-black/50 backdrop-blur-sm hidden z-50 flex items-center justify-center p-4">
        <div class="bg-slate-800/90 backdrop-blur-md rounded-xl shadow-2xl border border-white/10 p-6 w-full max-w-md">
            <h3 class="text-xl font-semibold mb-4">Reject Reservation</h3>
            <form id="rejectForm" method="POST">
                <input type="hidden" name="reservation_id" id="modalReservationId">
                <input type="hidden" name="action" value="reject">
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-slate-300 mb-1">Reason (optional)</label>
                    <textarea name="notes" rows="3" class="w-full p-2 bg-slate-700/50 border border-slate-600 rounded-md text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200"></textarea>
                </div>
                
                <div class="flex justify-end space-x-3">
                    <button type="button" onclick="closeRejectModal()" class="px-4 py-2 bg-slate-600/50 hover:bg-slate-600 text-white rounded-md transition-all duration-200">
                        Cancel
                    </button>
                    <button type="submit" class="px-4 py-2 bg-red-600/50 hover:bg-red-600 text-white rounded-md transition-all duration-200">
                        Confirm Reject
                    </button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>