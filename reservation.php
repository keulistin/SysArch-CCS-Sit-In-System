<?php
session_start();
include 'db.php';

// Check if user is logged in as student
if (!isset($_SESSION['idno']) || $_SESSION['role'] !== 'student') {
    header("Location: login.php");
    exit();
}

// Set page title
$page_title = "Reservation";

// Get student info
$idno = $_SESSION['idno'];
$stmt = $conn->prepare("SELECT id, firstname, lastname, remaining_sessions, profile_picture FROM users WHERE idno = ?");
$stmt->bind_param("s", $idno);
$stmt->execute();
$stmt->bind_result($student_id, $firstname, $lastname, $remaining_sessions, $profile_picture);
$stmt->fetch();
$stmt->close();

// Set default profile picture if none exists
if (empty($profile_picture)) {
    $profile_picture = "default_avatar.png";
}

// Check if student has completed the satisfaction survey
$survey_completed = false;
$stmt = $conn->prepare("SELECT survey_completed FROM users WHERE idno = ?");
$stmt->bind_param("s", $idno);
$stmt->execute();
$stmt->bind_result($survey_completed);
$stmt->fetch();
$stmt->close();

// Get total sit-ins count
$total_sitins = 0;
$stmt = $conn->prepare("SELECT COUNT(*) FROM sit_in_records WHERE student_id = ?");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$stmt->bind_result($total_sitins);
$stmt->fetch();
$stmt->close();

// Check for pending reservations
$pending_reservations = 0;
$stmt = $conn->prepare("SELECT COUNT(*) FROM reservations WHERE student_id = ? AND status = 'pending'");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$stmt->bind_result($pending_reservations);
$stmt->fetch();
$stmt->close();

// Get PC availability data
$lab_pcs = [];
$labs = ['Lab 517', 'Lab 524', 'Lab 526', 'Lab 528', 'Lab 530', 'Lab 542', 'Lab 544'];

foreach ($labs as $lab) {
    $stmt = $conn->prepare("SELECT COUNT(*) as total, 
                           SUM(CASE WHEN status = 'Available' THEN 1 ELSE 0 END) as available,
                           SUM(CASE WHEN status = 'Used' THEN 1 ELSE 0 END) as used,
                           SUM(CASE WHEN status = 'Maintenance' THEN 1 ELSE 0 END) as maintenance
                           FROM lab_pcs WHERE lab_name = ?");
    $stmt->bind_param("s", $lab);
    $stmt->execute();
    $result = $stmt->get_result();
    $lab_pcs[$lab] = $result->fetch_assoc();
    $stmt->close();
}

// Handle survey submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_survey'])) {
    $satisfaction = isset($_POST['satisfaction']) ? intval($_POST['satisfaction']) : 0;
    $comments = isset($_POST['comments']) ? trim($_POST['comments']) : '';
    
    if ($satisfaction > 0) {
        // Insert survey response
        $stmt = $conn->prepare("INSERT INTO satisfaction_surveys (student_id, satisfaction, comments) VALUES (?, ?, ?)");
        $stmt->bind_param("iis", $student_id, $satisfaction, $comments);
        $stmt->execute();
        $stmt->close();
        
        // Mark survey as completed
        $stmt = $conn->prepare("UPDATE users SET survey_completed = 1 WHERE id = ?");
        $stmt->bind_param("i", $student_id);
        $stmt->execute();
        $stmt->close();
        
        $survey_completed = true;
    }
}

// Handle reservation form submission
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit'])) {
    // Check if student has pending reservations
    if ($pending_reservations > 0) {
        $error = 'You already have a pending reservation. Please wait for it to be processed before making a new one.';
    } else {
        // Initialize variables with empty values
        $purpose = isset($_POST['purpose']) ? trim($_POST['purpose']) : '';
        $lab_room = isset($_POST['lab_room']) ? trim($_POST['lab_room']) : '';
        $reservation_date = isset($_POST['reservation_date']) ? trim($_POST['reservation_date']) : '';
        $time_in = isset($_POST['time_in']) ? trim($_POST['time_in']) : '';
        $pc_number = isset($_POST['pc_number']) ? trim($_POST['pc_number']) : '';
        
        // Validate inputs
        if (empty($purpose) || empty($lab_room) || empty($reservation_date) || empty($time_in)) {
            $error = 'All fields are required!';
        } elseif ($remaining_sessions <= 0) {
            $error = 'You have no remaining sessions left!';
        } else {
            // Check if selected PC is available (if one was selected)
            if (!empty($pc_number)) {
                $stmt = $conn->prepare("SELECT status FROM lab_pcs WHERE lab_name = ? AND pc_number = ?");
                $stmt->bind_param("si", $lab_room, $pc_number);
                $stmt->execute();
                $stmt->bind_result($pc_status);
                $stmt->fetch();
                $stmt->close();
                
                if ($pc_status !== 'Available') {
                    $error = 'The selected PC is not available!';
                }
            }
            
            if (empty($error)) {
                // Insert reservation
                $stmt = $conn->prepare("INSERT INTO reservations (student_id, purpose, lab_room, pc_number, reservation_date, time_in) VALUES (?, ?, ?, ?, ?, ?)");
                $pc_number = empty($pc_number) ? NULL : $pc_number;
                $stmt->bind_param("isssss", $student_id, $purpose, $lab_room, $pc_number, $reservation_date, $time_in);
                
                if ($stmt->execute()) {
                    $success = 'Reservation submitted successfully! Waiting for admin approval.';
                    // Clear form values after successful submission
                    $purpose = $lab_room = $reservation_date = $time_in = $pc_number = '';
                    // Update pending reservations count
                    $pending_reservations = 1;
                } else {
                    $error = 'Error submitting reservation: ' . $conn->error;
                }
                $stmt->close();
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
    <title><?php echo $page_title; ?> - CCS SIT Monitoring System</title>
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
        .star-rating {
            display: flex;
            justify-content: center;
            margin: 20px 0;
        }
        .star-rating input {
            display: none;
        }
        .star-rating label {
            font-size: 30px;
            color: #ccc;
            cursor: pointer;
            transition: color 0.2s;
        }
        .star-rating input:checked ~ label {
            color: #ffc107;
        }
        .star-rating label:hover,
        .star-rating label:hover ~ label {
            color: #ffc107;
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
                    <a href="reservation.php" class="flex items-center px-5 py-3 bg-blue-600/20 text-white transition-all duration-200">
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
        <!-- Satisfaction Survey Modal (shown only if needed) -->
<!-- Satisfaction Survey Modal (shown only if needed) -->
        <?php if ($total_sitins >= 10 && !$survey_completed): ?>
        <div id="surveyModal" class="fixed inset-0 bg-black/75 backdrop-blur-sm flex items-center justify-center z-50">
            <div class="bg-slate-800/90 backdrop-blur-md rounded-xl shadow-2xl border border-white/10 p-6 w-full max-w-md relative">
                <!-- Close button -->
                <button onclick="document.getElementById('surveyModal').classList.add('hidden')" 
                        class="absolute top-3 right-3 text-slate-400 hover:text-white transition-colors duration-200">
                    <i class="fas fa-times"></i>
                </button>
                
                <h3 class="text-xl font-semibold mb-4 text-white text-center">Sit-in Experience Survey</h3>
                <p class="text-slate-300 mb-6 text-center">Please take a moment to share your experience after completing 10 sit-in sessions.</p>
                
                <form method="POST" class="space-y-4">
                    <div class="text-center">
                        <p class="text-slate-300 mb-2">How satisfied are you with your sit-in experience?</p>
                        <div class="star-rating">
                            <input type="radio" id="star5" name="satisfaction" value="5" required />
                            <label for="star5" title="5 stars">★</label>
                            <input type="radio" id="star4" name="satisfaction" value="4" />
                            <label for="star4" title="4 stars">★</label>
                            <input type="radio" id="star3" name="satisfaction" value="3" />
                            <label for="star3" title="3 stars">★</label>
                            <input type="radio" id="star2" name="satisfaction" value="2" />
                            <label for="star2" title="2 stars">★</label>
                            <input type="radio" id="star1" name="satisfaction" value="1" />
                            <label for="star1" title="1 star">★</label>
                        </div>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-slate-300 mb-1">Comments (optional)</label>
                        <textarea name="comments" rows="3" class="w-full p-2 bg-slate-700/50 border border-slate-600 rounded-md text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200" placeholder="Any suggestions or feedback..."></textarea>
                    </div>
                    
                    <div class="pt-2">
                        <button type="submit" name="submit_survey" class="w-full flex items-center justify-center p-3 bg-gradient-to-r from-blue-600 to-blue-500 text-white rounded-md hover:from-blue-700 hover:to-blue-600 transition-all duration-200 shadow-md">
                            <i class="fa-solid fa-check mr-2"></i> Submit Survey
                        </button>
                    </div>
                </form>
            </div>
        </div>
        <?php endif; ?>

        <div class="bg-slate-800/50 backdrop-blur-sm rounded-xl shadow-lg border border-white/5 p-6 hover:shadow-xl transition-all duration-300">
            <h2 class="text-2xl font-semibold mb-6 text-white border-b border-white/10 pb-2">Lab Reservation</h2>
            
            <!-- Display error/success messages -->
            <?php if ($error): ?>
                <div class="bg-red-600/20 border border-red-600 text-red-400 px-4 py-3 rounded mb-4">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="bg-green-600/20 border border-green-600 text-green-400 px-4 py-3 rounded mb-4">
                    <?php echo $success; ?>
                </div>
            <?php endif; ?>
            
            <!-- Survey reminder -->
            <?php if ($total_sitins >= 10 && !$survey_completed): ?>
                <div class="bg-yellow-600/20 border border-yellow-600 text-yellow-400 px-4 py-3 rounded mb-4">
                    <div class="flex items-center">
                        <i class="fas fa-exclamation-triangle mr-2"></i>
                        <span>Please complete the satisfaction survey to continue making reservations.</span>
                    </div>
                </div>
            <?php endif; ?>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <!-- Reservation Form -->
                <div class="bg-slate-700/20 p-6 rounded-lg border border-white/5">
                    <h3 class="text-xl font-semibold mb-4 text-white">New Reservation</h3>
                    
                    <?php if ($pending_reservations > 0): ?>
                        <div class="bg-yellow-600/20 border border-yellow-600 text-yellow-400 px-4 py-3 rounded mb-4">
                            You have a pending reservation. Please wait for it to be processed before making a new one.
                        </div>
                    <?php elseif ($total_sitins >= 10 && !$survey_completed): ?>
                        <div class="bg-blue-600/20 border border-blue-600 text-blue-400 px-4 py-3 rounded mb-4 text-center">
                            <button onclick="document.getElementById('surveyModal').classList.remove('hidden')" class="flex items-center justify-center w-full p-2 bg-blue-600 hover:bg-blue-700 rounded-md transition-all duration-200">
                                <i class="fas fa-clipboard-check mr-2"></i> Complete Survey to Reserve
                            </button>
                        </div>
                    <?php else: ?>
                        <form method="POST" class="space-y-4">
                            <!-- Student Info (readonly) -->
                            <div>
                                <label class="block text-sm font-medium text-slate-300 mb-1">Student ID</label>
                                <input type="text" value="<?php echo htmlspecialchars($idno); ?>" class="w-full p-2 bg-slate-700/50 border border-slate-600 rounded-md text-white" readonly>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-slate-300 mb-1">Name</label>
                                <input type="text" value="<?php echo htmlspecialchars($firstname . ' ' . $lastname); ?>" class="w-full p-2 bg-slate-700/50 border border-slate-600 rounded-md text-white" readonly>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-slate-300 mb-1">Remaining Sessions</label>
                                <input type="text" value="<?php echo htmlspecialchars($remaining_sessions); ?>" class="w-full p-2 bg-slate-700/50 border border-slate-600 rounded-md text-white" readonly>
                            </div>
                            
                            <!-- Editable fields -->
                            <div>
                                <label class="block text-sm font-medium text-slate-300 mb-1">Purpose *</label>
                                <select name="purpose" class="w-full p-2 bg-slate-700/50 border border-slate-600 rounded-md text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200" required>
                                    <option value="">Select Purpose</option>
                                    <option value="C Programming" <?php echo (isset($purpose) && $purpose == 'C Programming' ? 'selected' : ''); ?>>C Programming</option>
                                    <option value="Java Programming" <?php echo (isset($purpose) && $purpose == 'Java Programming' ? 'selected' : ''); ?>>Java Programming</option>
                                    <option value="C# Programming" <?php echo (isset($purpose) && $purpose == 'C# Programming' ? 'selected' : ''); ?>>C# Programming</option>
                                    <option value="Systems Integration & Architecture" <?php echo (isset($purpose) && $purpose == 'Systems Integration & Architecture' ? 'selected' : ''); ?>>Systems Integration & Architecture</option>
                                    <option value="Embedded Systems & IoT" <?php echo (isset($purpose) && $purpose == 'Embedded Systems & IoT' ? 'selected' : ''); ?>>Embedded Systems & IoT</option>
                                    <option value="Computer Application" <?php echo (isset($purpose) && $purpose == 'Computer Application' ? 'selected' : ''); ?>>Computer Application</option>
                                    <option value="Database" <?php echo (isset($purpose) && $purpose == 'Database' ? 'selected' : ''); ?>>Database</option>
                                    <option value="Project Management" <?php echo (isset($purpose) && $purpose == 'Project Management' ? 'selected' : ''); ?>>Project Management</option>
                                    <option value="Python Programming" <?php echo (isset($purpose) && $purpose == 'Python Programming' ? 'selected' : ''); ?>>Python Programming</option>
                                    <option value="Mobile Application" <?php echo (isset($purpose) && $purpose == 'Mobile Application' ? 'selected' : ''); ?>>Mobile Application</option>
                                    <option value="Web Design" <?php echo (isset($purpose) && $purpose == 'Web Design' ? 'selected' : ''); ?>>Web Design</option>
                                    <option value="Php Programming" <?php echo (isset($purpose) && $purpose == 'Php Programming' ? 'selected' : ''); ?>>Php Programming</option>
                                    <option value="Other" <?php echo (isset($purpose) && $purpose == 'Other' ? 'selected' : ''); ?>>Other</option>
                                </select>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-slate-300 mb-1">Laboratory Room *</label>
                                <select name="lab_room" id="lab_room" class="w-full p-2 bg-slate-700/50 border border-slate-600 rounded-md text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200" required onchange="updatePcAvailability()">
                                    <option value="">Select Lab</option>
                                    <?php foreach ($labs as $lab): ?>
                                        <option value="<?php echo $lab; ?>" <?php echo (isset($lab_room) && $lab_room == $lab) ? 'selected' : ''; ?>>
                                            <?php echo $lab; ?> 
                                            (<?php echo $lab_pcs[$lab]['available']; ?> available)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div id="pcSelectionContainer" class="hidden">
                                <label class="block text-sm font-medium text-slate-300 mb-1">Select PC </label>
                                <div id="pcGrid" class="grid grid-cols-4 gap-2 mb-2 max-h-40 overflow-y-auto p-2 bg-slate-800/50 rounded-md">
                                    <!-- PCs will be loaded here via AJAX -->
                                </div>
                                <input type="hidden" name="pc_number" id="selectedPc">
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-slate-300 mb-1">Date *</label>
                                <input type="date" name="reservation_date" min="<?php echo date('Y-m-d'); ?>" value="<?php echo isset($reservation_date) ? htmlspecialchars($reservation_date) : ''; ?>" class="w-full p-2 bg-slate-700/50 border border-slate-600 rounded-md text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200" required>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-slate-300 mb-1">Time In *</label>
                                <input type="time" name="time_in" value="<?php echo isset($time_in) ? htmlspecialchars($time_in) : ''; ?>" class="w-full p-2 bg-slate-700/50 border border-slate-600 rounded-md text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200" required>
                            </div>
                            
                            <div class="pt-4">
                                <button type="submit" name="submit" class="w-full flex items-center justify-center p-3 bg-gradient-to-r from-blue-600 to-blue-500 text-white rounded-md hover:from-blue-700 hover:to-blue-600 transition-all duration-200 shadow-md">
                                    <i class="fa-solid fa-paper-plane mr-2"></i> Submit Reservation
                                </button>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>
                
                <!-- Reservation History -->
                <div class="bg-slate-700/20 p-6 rounded-lg border border-white/5">
                    <h3 class="text-xl font-semibold mb-4 text-white">Your Reservations</h3>
                    
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-600">
                            <thead class="bg-slate-700/50">
                                <tr>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-slate-300 uppercase tracking-wider">Date</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-slate-300 uppercase tracking-wider">Lab</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-slate-300 uppercase tracking-wider">PC</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-slate-300 uppercase tracking-wider">Time</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-slate-300 uppercase tracking-wider">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-700">
                                <?php
                                $reservations = $conn->prepare("SELECT reservation_date, lab_room, pc_number, time_in, status FROM reservations WHERE student_id = ? ORDER BY reservation_date DESC, time_in DESC");
                                $reservations->bind_param("i", $student_id);
                                $reservations->execute();
                                $result = $reservations->get_result();
                                
                                if ($result->num_rows > 0) {
                                    while ($row = $result->fetch_assoc()) {
                                        $status_color = '';
                                        if ($row['status'] == 'approved') $status_color = 'text-green-400';
                                        elseif ($row['status'] == 'disapproved') $status_color = 'text-red-400';
                                        else $status_color = 'text-yellow-400';
                                        
                                        echo "<tr>";
                                        echo "<td class='px-4 py-2 text-sm'>" . htmlspecialchars($row['reservation_date']) . "</td>";
                                        echo "<td class='px-4 py-2 text-sm'>" . htmlspecialchars($row['lab_room']) . "</td>";
                                        echo "<td class='px-4 py-2 text-sm'>" . ($row['pc_number'] ? 'PC ' . htmlspecialchars($row['pc_number']) : '-') . "</td>";
                                        echo "<td class='px-4 py-2 text-sm'>" . htmlspecialchars($row['time_in']) . "</td>";
                                        echo "<td class='px-4 py-2 text-sm $status_color'>" . ucfirst(htmlspecialchars($row['status'])) . "</td>";
                                        echo "</tr>";
                                    }
                                } else {
                                    echo "<tr><td colspan='5' class='px-4 py-4 text-center text-sm text-slate-400'>No reservations found</td></tr>";
                                }
                                
                                $reservations->close();
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Function to update PC availability when lab is selected
        function updatePcAvailability() {
            const labSelect = document.getElementById('lab_room');
            const labName = labSelect.value;
            const pcContainer = document.getElementById('pcSelectionContainer');
            const pcGrid = document.getElementById('pcGrid');
            const selectedPc = document.getElementById('selectedPc');
            
            if (labName) {
                // Show loading state
                pcGrid.innerHTML = '<div class="col-span-4 text-center py-4"><i class="fas fa-spinner fa-spin mr-2"></i> Loading PCs...</div>';
                pcContainer.classList.remove('hidden');
                
                // Load PCs via AJAX
                fetch(`get_pcs.php?lab=${encodeURIComponent(labName)}`, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.text();
                })
                .then(data => {
                    pcGrid.innerHTML = data;
                })
                .catch(error => {
                    console.error('Error:', error);
                    pcGrid.innerHTML = `
                        <div class="col-span-4 text-center py-4 text-red-400">
                            <i class="fas fa-exclamation-triangle mr-2"></i>
                            Failed to load PCs. Please try again.
                        </div>
                    `;
                });
            } else {
                pcContainer.classList.add('hidden');
                selectedPc.value = '';
            }
        }
        
        // Function to select a PC
        function selectPc(pcNumber) {
            const pcCards = document.querySelectorAll('#pcGrid .pc-card');
            const selectedPc = document.getElementById('selectedPc');
            
            pcCards.forEach(card => {
                if (parseInt(card.dataset.pcNumber) === pcNumber) {
                    card.classList.add('border-blue-500', 'bg-blue-900/20');
                    selectedPc.value = pcNumber;
                } else {
                    card.classList.remove('border-blue-500', 'bg-blue-900/20');
                }
            });
        }
    </script>
</body>
</html>