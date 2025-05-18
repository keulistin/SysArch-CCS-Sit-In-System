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

        <!-- User and Notification Controls -->
        <div class="flex gap-4 ml-4">
            <!-- Notification Button -->
            <div class="relative">
                <button id="notificationButton" class="relative p-2 text-light hover:text-secondary rounded-full transition-all duration-200 focus:outline-none">
                    <i class="fas fa-bell text-lg text-purple-500"></i>
                    <span class="notification-badge hidden">0</span>
                </button>

                <!-- Notification Dropdown -->
                <div id="notificationDropdown" class="hidden absolute right-0 mt-2 w-72 bg-white rounded-lg shadow-xl border border-secondary/20 z-50 overflow-hidden">
                    <div class="p-3 bg-purple-500 text-white flex justify-between items-center">
                        <span class="font-semibold">Notifications</span>
                        <button id="markAllRead" class="text-xs bg-white/20 hover:bg-white/30 px-2 py-1 rounded transition-all">
                            <i class="fas fa-check text-xl"></i>
                        </button>
                    </div>
                    <div id="notificationList" class="max-h-80 overflow-y-auto">
                        <div class="p-4 text-center text-gray-500">No notifications</div>
                    </div>
                </div>
            </div>

            <!-- User Avatar and Logout -->
            <div class="flex items-center space-x-4">
  
                <h2 class="px-4 py-2 text-gray-700 font-bold"><?php echo htmlspecialchars($firstname); ?></h2>

                <!-- Logout -->
                <div class="ml-4">
                    <a href="logout.php" onclick="return confirm('Are you sure you want to log out?')" class="flex items-center px-4 py-2 bg-purple-600 text-white rounded-full border-2 border-purple-700 hover:bg-purple-700 transition-all duration-200 shadow-md">
                        <i class="fas fa-sign-out-alt mr-2"></i>
                        <span class="hidden md:inline">Log Out</span>
                    </a>
                </div>
            </div>

            <!-- User Profile Dropdown (Placeholder for future) -->
            <div class="relative">
                <button id="userMenuButton" class="flex items-center gap-2 group focus:outline-none"></button>
            </div>
        </div>

        <!-- Mobile menu button -->
        <div class="mobile-menu md:hidden flex items-center">
            <button id="mobileMenuButton" class="text-light hover:text-secondary focus:outline-none">
                <i class="fas fa-bars text-xl"></i>
            </button>
        </div>
    </div>

    <!-- Mobile Menu (hidden by default) -->
    <div id="mobileMenu" class="hidden md:hidden bg-primary">
        <div class="px-2 pt-2 pb-3 space-y-1 sm:px-3">
            <a href="student_dashboard.php" class="block px-3 py-2 rounded-md text-base font-medium text-light hover:bg-primary/20">Profile</a>
            <a href="edit-profile.php" class="block px-3 py-2 rounded-md text-base font-medium text-secondary hover:bg-primary/20">Edit Profile</a>
            <a href="announcements.php" class="block px-3 py-2 rounded-md text-base font-medium text-secondary hover:bg-primary/20">Announcements</a>
            <a href="reservation.php" class="block px-3 py-2 rounded-md text-base font-medium text-secondary hover:bg-primary/20">Reservation</a>
            <a href="sit_in_history.php" class="block px-3 py-2 rounded-md text-base font-medium text-secondary hover:bg-primary/20">Sit-in History</a>
            <a href="student_leaderboard.php" class="block px-3 py-2 rounded-md text-base font-medium text-secondary hover:bg-primary/20">Leaderboard</a>
            <a href="sit-in-rules.php" class="block px-3 py-2 rounded-md text-base font-medium text-secondary hover:bg-primary/20">Sit-in Rules</a>
            <a href="lab-rules.php" class="block px-3 py-2 rounded-md text-base font-medium text-secondary hover:bg-primary/20">Lab Rules</a>
            <a href="upload_resources.php" class="block px-3 py-2 rounded-md text-base font-medium text-secondary hover:bg-primary/20">Lab Resources</a>
            <a href="student_lab_schedule.php" class="block px-3 py-2 rounded-md text-base font-medium text-secondary hover:bg-primary/20">Lab Schedule</a>
            <a href="logout.php" onclick="return confirm('Are you sure you want to log out?')" class="block px-3 py-2 rounded-md text-base font-medium text-secondary hover:bg-primary/20">Log Out</a>
        </div>
    </div>
</div>

<!-- Main Content -->
    <div class="min-h-screen bg-purple-100 main-content-cont">
    <!-- Satisfaction Survey Modal (shown only if needed) -->
    <?php if ($total_sitins >= 10 && !$survey_completed): ?>
    <div id="surveyModal" class="fixed inset-0 bg-black/75 backdrop-blur-sm flex items-center justify-center z-50">
        <div class="bg-white rounded-xl shadow-lg border border-gray-200 p-6 w-full max-w-md relative">
            <!-- Close button -->
            <button onclick="document.getElementById('surveyModal').classList.add('hidden')" 
                    class="absolute top-3 right-3 text-gray-400 hover:text-gray-600 transition-colors duration-200">
                <i class="fas fa-times"></i>
            </button>
            
            <h3 class="text-xl font-medium mb-4 text-gray-800 text-center">Sit-in Experience Survey</h3>
            <p class="text-gray-500 mb-6 text-center">Please take a moment to share your experience after completing 10 sit-in sessions.</p>
            
            <form method="POST" class="space-y-4">
                <div class="text-center">
                    <p class="text-gray-500 mb-2">How satisfied are you with your sit-in experience?</p>
                    <div class="star-rating text-yellow-400 text-2xl">
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
                    <label class="block text-sm font-medium text-gray-500 mb-1">Comments (optional)</label>
                    <textarea name="comments" rows="3" class="w-full p-3 bg-gray-50 border border-gray-200 rounded-lg text-gray-700 focus:ring-2 focus:ring-purple-400 focus:border-purple-400 transition-all duration-200" placeholder="Any suggestions or feedback..."></textarea>
                </div>
                
                <div class="pt-2">
                    <button type="submit" name="submit_survey" class="w-full flex items-center justify-center p-3 bg-gradient-to-r from-purple-500 to-purple-600 text-white rounded-lg hover:from-purple-600 hover:to-purple-700 transition-all duration-200 shadow-sm">
                        <i class="fa-solid fa-check mr-2"></i> Submit Survey
                    </button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <!-- Page Header -->
    <div class="mb-8">
        <h2 class="text-3xl font-medium text-gray-800 tracking-tight">Lab Reservation</h2>
        <p class="text-gray-500 font-light">Book your lab sessions in advance</p>
        <div class="w-16 h-1 bg-gradient-to-r from-purple-400 to-indigo-500 mt-4 rounded-full"></div>
    </div>
    
    <!-- Display error/success messages -->
    <?php if ($error): ?>
        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded mb-6">
            <div class="flex items-center">
                <i class="fas fa-exclamation-circle mr-2"></i>
                <p><?php echo $error; ?></p>
            </div>
        </div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 rounded mb-6">
            <div class="flex items-center">
                <i class="fas fa-check-circle mr-2"></i>
                <p><?php echo $success; ?></p>
            </div>
        </div>
    <?php endif; ?>
    
    <!-- Survey reminder -->
    <?php if ($total_sitins >= 10 && !$survey_completed): ?>
        <div class="bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-4 rounded mb-6">
            <div class="flex items-center">
                <i class="fas fa-exclamation-triangle mr-2"></i>
                <span>Please complete the satisfaction survey to continue making reservations.</span>
            </div>
        </div>
    <?php endif; ?>
    
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- Reservation Form -->
        <div class="bg-white rounded-xl border border-gray-100 shadow-xs p-8 hover:shadow-sm transition-all duration-300">
            <div class="mb-6">
                <div class="flex items-center mb-3">
                    <div class="w-8 h-8 rounded-full bg-purple-100 flex items-center justify-center text-purple-600 mr-3">
                        <i class="fas fa-calendar-plus text-sm"></i>
                    </div>
                    <h3 class="text-xl font-medium text-gray-800 tracking-tight">New Reservation</h3>
                </div>
                <p class="text-gray-500 font-light pl-11">Fill out the form to book a lab session</p>
            </div>
            

            <?php if ($total_sitins >= 10 && !$survey_completed): ?>
                <div class="bg-blue-100 border-l-4 border-blue-500 text-blue-700 p-4 rounded mb-6 text-center">
                    <button onclick="document.getElementById('surveyModal').classList.remove('hidden')" class="w-full flex items-center justify-center p-3 bg-gradient-to-r from-blue-500 to-blue-600 text-white rounded-lg hover:from-blue-600 hover:to-blue-700 transition-all duration-200 shadow-sm">
                        <i class="fas fa-clipboard-check mr-2"></i> Complete Survey to Reserve
                    </button>
                </div>
            <?php else: ?>
                <form method="POST" class="space-y-4">
                    <!-- Student Info (readonly) -->
                    <div>
                        <label class="block text-sm font-medium text-gray-500 mb-1">Student ID</label>
                        <input type="text" value="<?php echo htmlspecialchars($idno); ?>" class="w-full p-3 bg-gray-50 border border-gray-200 rounded-lg text-gray-700" readonly>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-500 mb-1">Name</label>
                        <input type="text" value="<?php echo htmlspecialchars($firstname . ' ' . $lastname); ?>" class="w-full p-3 bg-gray-50 border border-gray-200 rounded-lg text-gray-700" readonly>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-500 mb-1">Remaining Sessions</label>
                        <input type="text" value="<?php echo htmlspecialchars($remaining_sessions); ?>" class="w-full p-3 bg-gray-50 border border-gray-200 rounded-lg text-gray-700" readonly>
                    </div>
                    
                    <!-- Editable fields -->
                    <div>
                        <label class="block text-sm font-medium text-gray-500 mb-1">Purpose *</label>
                        <select name="purpose" class="w-full p-3 bg-gray-50 border border-gray-200 rounded-lg text-gray-700 focus:ring-2 focus:ring-purple-400 focus:border-purple-400 transition-all duration-200" required>
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
                        <label class="block text-sm font-medium text-gray-500 mb-1">Laboratory Room *</label>
                        <select name="lab_room" id="lab_room" class="w-full p-3 bg-gray-50 border border-gray-200 rounded-lg text-gray-700 focus:ring-2 focus:ring-purple-400 focus:border-purple-400 transition-all duration-200" required onchange="updatePcAvailability()">
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
                        <label class="block text-sm font-medium text-gray-500 mb-1">Select PC </label>
                        <div id="pcGrid" class="grid grid-cols-4 gap-3 mb-3 max-h-40 overflow-y-auto p-3 bg-gray-50 rounded-lg">
                            <!-- PCs will be loaded here via AJAX -->
                        </div>
                        <input type="hidden" name="pc_number" id="selectedPc">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-500 mb-1">Date *</label>
                        <input type="date" name="reservation_date" min="<?php echo date('Y-m-d'); ?>" value="<?php echo isset($reservation_date) ? htmlspecialchars($reservation_date) : ''; ?>" class="w-full p-3 bg-gray-50 border border-gray-200 rounded-lg text-gray-700 focus:ring-2 focus:ring-purple-400 focus:border-purple-400 transition-all duration-200" required>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-500 mb-1">Time In *</label>
                        <input type="time" name="time_in" value="<?php echo isset($time_in) ? htmlspecialchars($time_in) : ''; ?>" class="w-full p-3 bg-gray-50 border border-gray-200 rounded-lg text-gray-700 focus:ring-2 focus:ring-purple-400 focus:border-purple-400 transition-all duration-200" required>
                    </div>
                    
                    <div class="pt-4">
                        <button type="submit" name="submit" class="w-full flex items-center justify-center p-3 bg-gradient-to-r from-purple-500 to-purple-600 text-white rounded-lg hover:from-purple-600 hover:to-purple-700 transition-all duration-200 shadow-sm">
                            <i class="fa-solid fa-paper-plane mr-2"></i> Submit Reservation
                        </button>
                    </div>
                </form>
            <?php endif; ?>
        </div>
        
        <!-- Reservation History -->
        <div class="bg-white rounded-xl border border-gray-100 shadow-xs p-8 hover:shadow-sm transition-all duration-300">
            <div class="mb-6">
                <div class="flex items-center mb-3">
                    <div class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 mr-3">
                        <i class="fas fa-history text-sm"></i>
                    </div>
                    <h3 class="text-xl font-medium text-gray-800 tracking-tight">Your Reservations</h3>
                </div>
                <p class="text-gray-500 font-light pl-11">View your reservation history</p>
            </div>
            
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="p-3 text-sm font-medium text-gray-500 uppercase">Date</th>
                            <th class="p-3 text-sm font-medium text-gray-500 uppercase">Lab</th>
                            <th class="p-3 text-sm font-medium text-gray-500 uppercase">PC</th>
                            <th class="p-3 text-sm font-medium text-gray-500 uppercase">Time</th>
                            <th class="p-3 text-sm font-medium text-gray-500 uppercase">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $reservations = $conn->prepare("SELECT reservation_date, lab_room, pc_number, time_in, status FROM reservations WHERE student_id = ? ORDER BY reservation_date DESC, time_in DESC");
                        $reservations->bind_param("i", $student_id);
                        $reservations->execute();
                        $result = $reservations->get_result();
                        
                        if ($result->num_rows > 0) {
                            while ($row = $result->fetch_assoc()) {
                                $status_color = '';
                                if ($row['status'] == 'approved') $status_color = 'bg-green-100 text-green-800';
                                elseif ($row['status'] == 'disapproved') $status_color = 'bg-red-100 text-red-800';
                                else $status_color = 'bg-yellow-100 text-yellow-800';
                                
                                echo "<tr class='border-t border-gray-100 hover:bg-gray-50'>";
                                echo "<td class='p-3'>" . htmlspecialchars($row['reservation_date']) . "</td>";
                                echo "<td class='p-3'>" . htmlspecialchars($row['lab_room']) . "</td>";
                                echo "<td class='p-3'>" . ($row['pc_number'] ? 'PC ' . htmlspecialchars($row['pc_number']) : '-') . "</td>";
                                echo "<td class='p-3'>" . htmlspecialchars($row['time_in']) . "</td>";
                                echo "<td class='p-3'><span class='px-2 py-1 text-xs rounded-full $status_color'>" . ucfirst(htmlspecialchars($row['status'])) . "</span></td>";
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='5' class='p-4 text-center text-gray-500'>No reservations found</td></tr>";
                        }
                        
                        $reservations->close();
                        ?>
                    </tbody>
                </table>
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
            pcGrid.innerHTML = '<div class="col-span-4 text-center py-4 text-gray-500"><i class="fas fa-spinner fa-spin mr-2"></i> Loading PCs...</div>';
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
                    <div class="col-span-4 text-center py-4 text-red-500">
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
                card.classList.add('border-purple-500', 'bg-purple-100');
                selectedPc.value = pcNumber;
            } else {
                card.classList.remove('border-purple-500', 'bg-purple-100');
            }
        });
    }

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

// Mobile menu toggle
document.getElementById('mobile-menu-button').addEventListener('click', function() {
    document.getElementById('mobile-menu').classList.toggle('hidden');
});

// Logout confirmation
function confirmLogout(event) {
    if (!confirm('Are you sure you want to log out?')) {
        event.preventDefault();
    }
}
</script>

</body>
</html>