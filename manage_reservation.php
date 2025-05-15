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
                    <h2 class="text-3xl font-medium text-gray-800 tracking-tight">Reservation Management</h2>
                    <p class="text-gray-500 font-light mt-2">Review and manage computer lab reservations</p>
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
        
        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mx-6 mt-4 rounded-md">
                <div class="flex items-center">
                    <i class="fas fa-exclamation-circle mr-2"></i>
                    <p><?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?></p>
                </div>
            </div>
        <?php endif; ?>

        <!-- Filter and Search -->
        <div class="p-6 border-b border-gray-200">
            <div class="flex flex-col md:flex-row md:items-center gap-4">
                <div class="relative w-full md:w-64">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <i class="fas fa-search text-gray-400"></i>
                    </div>
                    <input 
                        type="text" 
                        id="reservationSearch"
                        placeholder="Search reservations..." 
                        class="w-full pl-10 pr-4 py-2 bg-gray-50 border border-gray-200 rounded-lg text-gray-700 placeholder-gray-400 focus:ring-2 focus:ring-purple-200 focus:border-transparent"
                        onkeyup="searchReservations()"
                    >
                </div>

            </div>
        </div>

        <!-- Reservations Table -->
        <div class="p-6">
            <?php if ($reservations && $reservations->num_rows > 0): ?>
                <div class="overflow-x-auto">
                    <table class="bg-white w-full" id="reservationsTable">
                        <thead class="bg-gradient-to-r from-purple-400 to-indigo-500">
                            <tr>
                                <th class="p-4 text-left text-sm font-medium text-white cursor-pointer" onclick="sortTable(0)">Student <i class="fas fa-sort ml-1 text-white"></i></th>
                                <th class="p-4 text-left text-sm font-medium text-white cursor-pointer" onclick="sortTable(1)">ID <i class="fas fa-sort ml-1 text-white"></i></th>
                                <th class="p-4 text-left text-sm font-medium text-white cursor-pointer" onclick="sortTable(2)">Purpose <i class="fas fa-sort ml-1 text-white"></i></th>
                                <th class="p-4 text-left text-sm font-medium text-white cursor-pointer" onclick="sortTable(3)">Lab <i class="fas fa-sort ml-1 text-white"></i></th>
                                <th class="p-4 text-left text-sm font-medium text-white cursor-pointer" onclick="sortTable(4)">PC <i class="fas fa-sort ml-1 text-white"></i></th>
                                <th class="p-4 text-left text-sm font-medium text-white cursor-pointer" onclick="sortTable(5)">Date <i class="fas fa-sort ml-1 text-white"></i></th>
                                <th class="p-4 text-left text-sm font-medium text-white cursor-pointer" onclick="sortTable(6)">Time <i class="fas fa-sort ml-1 text-white"></i></th>
                                <th class="p-4 text-left text-sm font-medium text-white cursor-pointer" onclick="sortTable(7)">Sessions <i class="fas fa-sort ml-1 text-white"></i></th>
                                <th class="p-4 text-left text-sm font-medium text-white">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200" id="reservationsBody">
                            <?php while ($row = $reservations->fetch_assoc()): ?>
                                <tr class="hover:bg-purple-50 transition-colors duration-150 reservation-row" data-status="<?php echo isset($row['status']) ? htmlspecialchars($row['status']) : 'Pending'; ?>">
                                    <td class="p-4 text-gray-800 font-medium"><?php echo htmlspecialchars($row['firstname'] . ' ' . $row['lastname']); ?></td>
                                    <td class="p-4 text-gray-600"><?php echo htmlspecialchars($row['idno']); ?></td>
                                    <td class="p-4 text-gray-600"><?php echo htmlspecialchars($row['purpose']); ?></td>
                                    <td class="p-4 text-gray-600"><?php echo htmlspecialchars($row['lab_room']); ?></td>
                                    <td class="p-4 text-gray-600"><?php echo $row['pc_number'] ? 'PC ' . htmlspecialchars($row['pc_number']) : '-'; ?></td>
                                    <td class="p-4 text-gray-600" data-sort="<?php echo strtotime($row['reservation_date']); ?>"><?php echo htmlspecialchars($row['reservation_date']); ?></td>
                                    <td class="p-4 text-gray-600" data-sort="<?php echo strtotime($row['time_in']); ?>"><?php echo htmlspecialchars(date('h:i A', strtotime($row['time_in']))); ?></td>
                                    <td class="p-4 text-gray-600"><?php echo htmlspecialchars($row['remaining_sessions']); ?></td>
                                    <td class="p-4">
                                        <div class="flex space-x-2">
                                            <form method="POST" class="inline">
                                                <input type="hidden" name="reservation_id" value="<?php echo $row['id']; ?>">
                                                <input type="hidden" name="action" value="approve">
                                                <button type="submit" class="px-3 py-1 bg-green-50 text-green-600 rounded-md hover:bg-green-100 transition-colors duration-200 flex items-center">
                                                    <i class="fas fa-check mr-1 text-sm"></i> Approve
                                                </button>
                                            </form>
                                            <button onclick="openRejectModal(<?php echo $row['id']; ?>)" class="px-3 py-1 bg-red-50 text-red-600 rounded-md hover:bg-red-100 transition-colors duration-200 flex items-center">
                                                <i class="fas fa-times mr-1 text-sm"></i> Reject
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="bg-gray-50 p-8 text-center rounded-lg">
                    <i class="fas fa-calendar-times text-4xl text-gray-300 mb-3"></i>
                    <p class="text-gray-500">No pending reservations found.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Reject Modal -->
<div id="rejectModal" class="fixed inset-0 flex items-center justify-center hidden z-50 p-4 bg-black/30 backdrop-blur-sm">
    <div class="bg-white rounded-xl shadow-2xl border border-gray-200 p-6 w-full max-w-md">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-xl font-semibold text-gray-800">Reject Reservation</h3>
            <button onclick="closeRejectModal()" class="text-gray-400 hover:text-gray-500">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <form id="rejectForm" method="POST">
            <input type="hidden" name="reservation_id" id="modalReservationId">
            <input type="hidden" name="action" value="reject">
            
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Reason (optional)</label>
                <textarea name="notes" rows="3" class="w-full p-2 bg-gray-50 border border-gray-200 rounded-md text-gray-700 focus:ring-2 focus:ring-purple-200 focus:border-transparent"></textarea>
            </div>
            
            <div class="flex justify-end space-x-3">
                <button type="button" onclick="closeRejectModal()" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-md transition-colors duration-200">
                    Cancel
                </button>
                <button type="submit" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-md transition-colors duration-200">
                    Confirm Reject
                </button>
            </div>
        </form>
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