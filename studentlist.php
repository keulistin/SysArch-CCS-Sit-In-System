<?php
session_start();
include 'db.php';

// Ensure only admins can access
if (!isset($_SESSION["idno"]) || $_SESSION["role"] !== "admin") {
    header("Location: login.php");
    exit();
}

// Initialize students array
$students = [];

// Handle reset all sessions
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["reset_all_sessions"])) {
    $reset_query = "UPDATE users SET remaining_sessions = 30 WHERE role = 'student'";
    if ($conn->query($reset_query)) {
        $_SESSION['success_message'] = "All student sessions have been reset!";
    } else {
        $_SESSION['error_message'] = "Error resetting sessions: " . $conn->error;
    }
}

// Handle individual session reset
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["reset_student_session"])) {
    $student_id = $_POST["student_id"];
    $reset_query = "UPDATE users SET remaining_sessions = 30 WHERE idno = ?";
    $stmt = $conn->prepare($reset_query);
    $stmt->bind_param("s", $student_id);
    if ($stmt->execute()) {
        $_SESSION['success_message'] = "Session for student ID $student_id has been reset!";
    } else {
        $_SESSION['error_message'] = "Error resetting session: " . $stmt->error;
    }
    $stmt->close();
}

// Fetch all students from the database (exclude admins)
$sql = "SELECT idno, firstname, lastname, middlename, course, yearlevel, email, profile_picture, remaining_sessions FROM users WHERE role = 'student' ORDER BY lastname, firstname ASC";
$result = $conn->query($sql);

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $students[] = $row;
    }
} else {
    $_SESSION['error_message'] = "Error fetching students: " . $conn->error;
    $students = []; // Ensure it's an empty array even if there's an error
}

// Close connection
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>List of Students</title>
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
                <!-- Admin Icon -->
                <div class="w-10 h-10 rounded-full bg-blue-600 flex items-center justify-center">
                    <i class="fas fa-user-shield text-white"></i>
                </div>
                <!-- Admin Name -->
                <h2 class="text-xl font-semibold text-white">Admin <?php echo htmlspecialchars($_SESSION['firstname'] ?? ''); ?></h2>
            </div>
            <p class="text-sm text-slate-400 mt-2">List of Students</p>
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
                    <a href="studentlist.php" class="flex items-center px-5 py-3 bg-slate-700/20 text-white">
                        <span>List of Students</span>
                    </a>
                </li>
                <li>
                    <a href="manage_reservation.php" class="flex items-center px-5 py-3 text-slate-300 hover:bg-slate-700/20 hover:text-white transition-all duration-200">
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
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-2xl font-semibold text-white border-b border-white/10 pb-2">📚 List of Students</h2>
                <div class="flex space-x-3">
                    <form method="POST" class="mb-0">
                        <button type="submit" name="reset_all_sessions" class="flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg transition-colors duration-200" onclick="return confirm('Are you sure you want to reset ALL student sessions?')">
                            <i class="fas fa-sync-alt mr-2"></i>
                            Reset All Sessions
                        </button>
                    </form>
                </div>
            </div>

            <!-- Display messages -->
            <?php if (isset($_SESSION['success_message'])): ?>
                <div class="mb-4 p-4 bg-green-500/20 text-green-400 rounded-lg border border-green-500/30">
                    <?= $_SESSION['success_message']; ?>
                    <?php unset($_SESSION['success_message']); ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['error_message'])): ?>
                <div class="mb-4 p-4 bg-red-500/20 text-red-400 rounded-lg border border-red-500/30">
                    <?= $_SESSION['error_message']; ?>
                    <?php unset($_SESSION['error_message']); ?>
                </div>
            <?php endif; ?>

            <!-- Search Bar -->
            <div class="mb-6">
                <div class="relative">
                    <input 
                        type="text" 
                        id="search" 
                        class="w-full p-3 bg-slate-700/50 border border-slate-600 rounded-lg text-white placeholder-slate-400 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200" 
                        placeholder="Search by name, ID, or course..."
                        onkeyup="filterStudents()"
                    >
                    <i class="fas fa-search absolute right-3 top-3 text-slate-400"></i>
                </div>
            </div>

            <!-- Student Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6" id="studentGrid">
                <?php if (!empty($students)): ?>
                    <?php foreach ($students as $student): ?>
                        <div class="student-card bg-slate-700/50 rounded-xl border border-white/5 p-6 hover:shadow-xl transition-all duration-300">
                            <!-- Profile Picture -->
                            <div class="flex justify-center mb-4">
                                <img 
                                    src="uploads/<?= htmlspecialchars($student['profile_picture'] ?? 'default_avatar.png'); ?>" 
                                    alt="Profile Picture" 
                                    class="w-24 h-24 rounded-full border-4 border-white/10 object-cover"
                                    onerror="this.src='assets/default_avatar.png'"
                                >
                            </div>

                            <!-- Student Details -->
                            <div class="space-y-2">
                                <p class="text-sm text-slate-300"><strong>ID:</strong> <?= htmlspecialchars($student['idno']); ?></p>
                                <p class="text-sm text-slate-300"><strong>Name:</strong> <?= htmlspecialchars($student['firstname'] . ' ' . ($student['middlename'] ? $student['middlename'] . ' ' : '') . $student['lastname']); ?></p>
                                <p class="text-sm text-slate-300"><strong>Course:</strong> <?= htmlspecialchars($student['course']); ?></p>
                                <p class="text-sm text-slate-300"><strong>Year Level:</strong> <?= htmlspecialchars($student['yearlevel']); ?></p>
                                <p class="text-sm text-slate-300"><strong>Email:</strong> <?= htmlspecialchars($student['email']); ?></p>
                                <p class="text-sm <?= $student['remaining_sessions'] <= 0 ? 'text-red-400' : 'text-green-400' ?>">
                                    <strong>Remaining Sessions:</strong> <?= htmlspecialchars($student['remaining_sessions']); ?>
                                </p>
                            </div>

                            <!-- Reset Session Button -->
                            <form method="POST" class="mt-4">
                                <input type="hidden" name="student_id" value="<?= htmlspecialchars($student['idno']); ?>">
                                <button type="submit" name="reset_student_session" class="w-full flex items-center justify-center p-2 bg-gradient-to-r from-green-600 to-green-500 text-white rounded-md hover:from-green-700 hover:to-green-600 transition-all duration-200 shadow-md" onclick="return confirm('Reset sessions for <?= htmlspecialchars($student['firstname'] . ' ' . $student['lastname']); ?>?')">
                                    <i class="fas fa-sync-alt mr-2"></i> Reset Session
                                </button>
                            </form>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="col-span-full text-center py-10">
                        <i class="fas fa-user-graduate text-4xl text-slate-400 mb-3"></i>
                        <p class="text-slate-400">No students found in the database.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- JavaScript for Filtering -->
    <script>
        function filterStudents() {
            const input = document.getElementById('search').value.toLowerCase();
            const cards = document.querySelectorAll('.student-card');
            
            cards.forEach(card => {
                const text = card.innerText.toLowerCase();
                card.style.display = text.includes(input) ? 'block' : 'none';
            });
        }
    </script>
</body>
</html>