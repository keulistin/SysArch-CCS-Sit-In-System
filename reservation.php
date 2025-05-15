<?php
session_start();
include 'db.php'; // Make sure this path is correct

// Check if user is logged in as student
if (!isset($_SESSION['idno']) || $_SESSION['role'] !== 'student') {
    header("Location: login.php");
    exit();
}

// Set page title
$page_title = "Make a Reservation";

// Get student info
$idno_session = $_SESSION['idno'];
$student_id = null; // Will be fetched
$firstname = 'Student'; // Default
$lastname = '';
$remaining_sessions = 0;
$profile_picture_nav = 'default_avatar.png';

$stmt_user = $conn->prepare("SELECT id, firstname, lastname, remaining_sessions, profile_picture, survey_completed FROM users WHERE idno = ?");
if ($stmt_user) {
    $stmt_user->bind_param("s", $idno_session);
    $stmt_user->execute();
    $stmt_user->bind_result($fetched_student_id, $fetched_firstname, $fetched_lastname, $fetched_remaining_sessions, $fetched_profile_picture, $fetched_survey_completed);
    if ($stmt_user->fetch()) {
        $student_id = $fetched_student_id;
        $firstname = $fetched_firstname;
        $lastname = $fetched_lastname;
        $remaining_sessions = $fetched_remaining_sessions ?? 0;
        $profile_picture_nav = !empty($fetched_profile_picture) ? $fetched_profile_picture : 'default_avatar.png';
        $survey_completed = (bool)$fetched_survey_completed;
    }
    $stmt_user->close();
} else {
    error_log("Error preparing student info query: " . $conn->error);
    // Handle error appropriately, maybe redirect or show error
}

// Get total sit-ins count (needed for survey trigger)
$total_sitins = 0;
if ($student_id) { // Only query if student_id is known
    $stmt_total_sitins = $conn->prepare("SELECT COUNT(*) FROM sit_in_records WHERE student_id = ?");
    if ($stmt_total_sitins) {
        $stmt_total_sitins->bind_param("i", $student_id);
        $stmt_total_sitins->execute();
        $stmt_total_sitins->bind_result($total_sitins_count);
        if($stmt_total_sitins->fetch()){
            $total_sitins = $total_sitins_count;
        }
        $stmt_total_sitins->close();
    } else {
        error_log("Error preparing total sit-ins query: " . $conn->error);
    }
}

// Check for pending reservations
$pending_reservations = 0;
if ($student_id) {
    $stmt_pending = $conn->prepare("SELECT COUNT(*) FROM reservations WHERE student_id = ? AND status = 'pending'");
    if ($stmt_pending) {
        $stmt_pending->bind_param("i", $student_id);
        $stmt_pending->execute();
        $stmt_pending->bind_result($pending_reservations_count);
        if($stmt_pending->fetch()){
            $pending_reservations = $pending_reservations_count;
        }
        $stmt_pending->close();
    } else {
        error_log("Error preparing pending reservations query: " . $conn->error);
    }
}


// Get PC availability data for select options
$lab_pcs_summary = [];
$labs = ['Lab 517', 'Lab 524', 'Lab 526', 'Lab 528', 'Lab 530', 'Lab 542', 'Lab 544'];
foreach ($labs as $lab) {
    $stmt_lab_avail = $conn->prepare("SELECT COUNT(CASE WHEN status = 'Available' THEN 1 END) as available_count FROM lab_pcs WHERE lab_name = ?");
    if ($stmt_lab_avail) {
        $stmt_lab_avail->bind_param("s", $lab);
        $stmt_lab_avail->execute();
        $stmt_lab_avail->bind_result($available_count);
        $stmt_lab_avail->fetch();
        $lab_pcs_summary[$lab] = ['available' => $available_count ?? 0];
        $stmt_lab_avail->close();
    } else {
        error_log("Error preparing lab PC summary query for $lab: " . $conn->error);
        $lab_pcs_summary[$lab] = ['available' => 0]; // Default if query fails
    }
}

// Handle survey submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_survey'])) {
    if ($student_id) {
        $satisfaction = isset($_POST['satisfaction']) ? intval($_POST['satisfaction']) : 0;
        $comments = isset($_POST['comments']) ? trim($_POST['comments']) : '';
        
        if ($satisfaction >= 1 && $satisfaction <= 5) {
            $stmt_insert_survey = $conn->prepare("INSERT INTO satisfaction_surveys (student_id, satisfaction_rating, comments) VALUES (?, ?, ?)");
            if ($stmt_insert_survey) {
                $stmt_insert_survey->bind_param("iis", $student_id, $satisfaction, $comments);
                if ($stmt_insert_survey->execute()) {
                    $stmt_update_user = $conn->prepare("UPDATE users SET survey_completed = 1 WHERE id = ?");
                    if ($stmt_update_user) {
                        $stmt_update_user->bind_param("i", $student_id);
                        $stmt_update_user->execute();
                        $stmt_update_user->close();
                        $survey_completed = true; // Update local variable
                        $_SESSION['success_message_survey'] = "Thank you for your feedback!";
                    } else {
                         $_SESSION['error_message_survey'] = "Error updating survey status.";
                    }
                } else {
                     $_SESSION['error_message_survey'] = "Error submitting survey.";
                }
                $stmt_insert_survey->close();
            } else {
                $_SESSION['error_message_survey'] = "Database error preparing survey insert.";
            }
        } else {
            $_SESSION['error_message_survey'] = "Please select a satisfaction rating.";
        }
    }
    header("Location: reservation.php"); // Refresh to show messages and updated state
    exit();
}

// Handle reservation form submission
$error_reservation = '';
$success_reservation = '';
// Retain form values on error
$form_purpose = $_POST['purpose'] ?? '';
$form_lab_room = $_POST['lab_room'] ?? '';
$form_reservation_date = $_POST['reservation_date'] ?? '';
$form_time_in = $_POST['time_in'] ?? '';
$form_pc_number = $_POST['pc_number'] ?? '';


if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_reservation'])) {
    if (!$student_id) {
        $error_reservation = "User information not found. Please try logging in again.";
    } elseif ($pending_reservations > 0) {
        $error_reservation = 'You already have a pending reservation. Please wait for it to be processed.';
    } elseif ($total_sitins >= 10 && !$survey_completed) {
        $error_reservation = 'Please complete the satisfaction survey before making new reservations.';
    } elseif ($remaining_sessions <= 0) {
        $error_reservation = 'You have no remaining sit-in sessions left for this week.';
    } else {
        $purpose = trim($_POST['purpose'] ?? '');
        $lab_room = trim($_POST['lab_room'] ?? '');
        $reservation_date_str = trim($_POST['reservation_date'] ?? '');
        $time_in_str = trim($_POST['time_in'] ?? '');
        $pc_number_val = trim($_POST['pc_number'] ?? '');

        if (empty($purpose) || empty($lab_room) || empty($reservation_date_str) || empty($time_in_str)) {
            $error_reservation = 'Purpose, Lab, Date, and Time In are required fields.';
        } else {
            // Validate date and time format and ensure it's not in the past
            try {
                $reservation_datetime = new DateTime($reservation_date_str . ' ' . $time_in_str);
                $now = new DateTime();
                if ($reservation_datetime < $now) {
                    $error_reservation = 'Reservation date and time cannot be in the past.';
                }
            } catch (Exception $e) {
                $error_reservation = 'Invalid date or time format.';
            }

            if (empty($error_reservation) && !empty($pc_number_val)) {
                $stmt_pc_check = $conn->prepare("SELECT status FROM lab_pcs WHERE lab_name = ? AND pc_number = ?");
                if ($stmt_pc_check) {
                    $stmt_pc_check->bind_param("si", $lab_room, $pc_number_val);
                    $stmt_pc_check->execute();
                    $stmt_pc_check->bind_result($pc_status);
                    if ($stmt_pc_check->fetch() && $pc_status !== 'Available') {
                        $error_reservation = 'The selected PC ('.$pc_number_val.') in '.$lab_room.' is not available at this moment. Please try another or proceed without selecting a PC.';
                    }
                    $stmt_pc_check->close();
                } else {
                     $error_reservation = 'Error checking PC status.';
                }
            }
            
            if (empty($error_reservation)) {
                $stmt_insert_res = $conn->prepare("INSERT INTO reservations (student_id, purpose, lab_room, pc_number, reservation_date, time_in, status) VALUES (?, ?, ?, ?, ?, ?, 'pending')");
                $db_pc_number = empty($pc_number_val) ? NULL : (int)$pc_number_val;
                if ($stmt_insert_res) {
                    $stmt_insert_res->bind_param("isssis", $student_id, $purpose, $lab_room, $db_pc_number, $reservation_date_str, $time_in_str);
                    if ($stmt_insert_res->execute()) {
                        $_SESSION['success_message_reservation'] = 'Reservation submitted successfully! It is now pending admin approval.';
                        // Clear form values by redirecting
                        header("Location: reservation.php");
                        exit();
                    } else {
                        $error_reservation = 'Error submitting reservation: ' . $stmt_insert_res->error;
                    }
                    $stmt_insert_res->close();
                } else {
                    $error_reservation = 'Database error preparing reservation insert: ' . $conn->error;
                }
            }
        }
    }
    // If errors occurred, set session message to display after redirect
    if (!empty($error_reservation)) {
        $_SESSION['error_message_reservation'] = $error_reservation;
        // Preserve form values for redirection
        $_SESSION['form_values_reservation'] = $_POST;
        header("Location: reservation.php");
        exit();
    }
}

// Retrieve form values from session if they exist (after a failed POST attempt)
if (isset($_SESSION['form_values_reservation'])) {
    $form_purpose = $_SESSION['form_values_reservation']['purpose'] ?? '';
    $form_lab_room = $_SESSION['form_values_reservation']['lab_room'] ?? '';
    $form_reservation_date = $_SESSION['form_values_reservation']['reservation_date'] ?? '';
    $form_time_in = $_SESSION['form_values_reservation']['time_in'] ?? '';
    $form_pc_number = $_SESSION['form_values_reservation']['pc_number'] ?? '';
    unset($_SESSION['form_values_reservation']);
}

// Retrieve messages from session and then unset them
if (isset($_SESSION['success_message_reservation'])) {
    $success_reservation = $_SESSION['success_message_reservation'];
    unset($_SESSION['success_message_reservation']);
}
if (isset($_SESSION['error_message_reservation'])) {
    $error_reservation = $_SESSION['error_message_reservation'];
    unset($_SESSION['error_message_reservation']);
}
if (isset($_SESSION['success_message_survey'])) {
    $success_survey = $_SESSION['success_message_survey'];
    unset($_SESSION['success_message_survey']);
}
if (isset($_SESSION['error_message_survey'])) {
    $error_survey = $_SESSION['error_message_survey'];
    unset($_SESSION['error_message_survey']);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?> - CCS SIT-IN MONITORING SYSTEM</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['Poppins', 'sans-serif'], },
                    colors: {
                        'custom-purple': '#6D28D9', 'custom-indigo': '#4F46E5',
                        'light-bg': '#F1E6EF', 'card-bg': '#FFFFFF', 'nav-bg': '#FFFFFF',
                        'text-primary': '#1F2937', 'text-secondary': '#6B7280',
                        'accent-red': '#EF4444', 'accent-green': '#10B981', 'accent-blue': '#3B82F6',
                        'accent-yellow': '#F59E0B',
                        'input-bg': '#F9FAFB', 'input-border': '#D1D5DB',
                    }
                },
            },
        }
    </script>
    <style>
        body {background-color: #F1E6EF; padding-top: 76px; }
        .main-content-area { padding: 2rem 1rem; }
        @media (min-width: 768px) { .main-content-area { padding: 2rem; } }
        @media (min-width: 1024px) { .main-content-area { padding: 3rem 4rem; } }
        .nav-dropdown a:hover { background-color: theme('colors.purple.50'); color: theme('colors.custom-purple'); }
        .nav-dropdown { z-index: 20; }
        .input-field { background-color: theme('colors.input-bg'); border-color: theme('colors.input-border'); color: theme('colors.text-primary'); }
        .input-field:focus, .select-field:focus { outline: none; border-color: theme('colors.custom-purple'); box-shadow: 0 0 0 2px theme('colors.custom-purple'), 0 0 0 4px rgba(109, 40, 217, 0.2); }
        .form-label { color: theme('colors.text-primary'); font-weight: 500; }
        .pc-card { transition: all 0.2s ease; cursor: pointer; border: 2px solid transparent; }
        .pc-card:hover { transform: translateY(-2px); box-shadow: 0 4px 8px rgba(0,0,0,0.1); }
        .pc-card.selected { border-color: theme('colors.custom-indigo'); background-color: theme('colors.indigo.50');}
        .pc-card.unavailable { background-color: theme('colors.slate.200'); color: theme('colors.slate.500'); cursor: not-allowed; opacity: 0.7; }
        .star-rating { display: flex; flex-direction: row-reverse; justify-content: center; }
        .star-rating input[type="radio"] { display: none; }
        .star-rating label { font-size: 2.5rem; color: #E0E0E0; cursor: pointer; transition: color 0.2s; padding: 0 0.2rem;}
        .star-rating input[type="radio"]:checked ~ label, .star-rating label:hover, .star-rating label:hover ~ label { color: #FFD700; }
        .animate-fade-in-scale { animation: fadeInScale 0.3s ease-out forwards; }
        @keyframes fadeInScale {
            0% { opacity: 0; transform: scale(0.95); }
            100% { opacity: 1; transform: scale(1); }
        }
    </style>
</head>
<body class="font-sans antialiased">

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
                <img src="uploads/<?php echo htmlspecialchars(!empty($profile_picture_nav) ? $profile_picture_nav : 'default_avatar.jpg'); ?>" 
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

    <!-- Main Content Area -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 sm:py-12 main-content-area">
        <div class="bg-card-bg p-6 sm:p-8 rounded-xl shadow-2xl">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6 sm:mb-8 pb-4 border-b border-gray-200">
                <div class="flex items-center mb-4 sm:mb-0">
                    <div class="flex-shrink-0 w-12 h-12 rounded-lg bg-custom-indigo/10 flex items-center justify-center text-custom-indigo mr-4">
                        <i class="fas fa-calendar-alt text-2xl page-header-icon"></i>
                    </div>
                    <div>
                        <h1 class="text-2xl sm:text-3xl font-bold text-text-primary"><?php echo htmlspecialchars($page_title); ?></h1>
                        <p class="text-sm text-text-secondary mt-1">Book your lab sessions in advance.</p>
                    </div>
                </div>
                <div class="text-sm text-text-secondary">
                    Remaining Sessions: <span class="font-semibold text-custom-purple"><?php echo $remaining_sessions; ?></span> / 30
                </div>
            </div>

            <!-- Messages -->
            <?php if (!empty($error_reservation)): ?>
                <div class="bg-red-50 border-l-4 border-accent-red text-accent-red p-4 mb-6 rounded-md" role="alert">
                    <div class="flex"><div class="py-1"><i class="fas fa-times-circle mr-3"></i></div>
                    <div><p class="font-bold">Reservation Failed</p><p class="text-sm"><?php echo $error_reservation; ?></p></div></div>
                </div>
            <?php endif; ?>
            <?php if (!empty($success_reservation)): ?>
                 <div class="bg-green-50 border-l-4 border-accent-green text-accent-green p-4 mb-6 rounded-md" role="alert">
                    <div class="flex"><div class="py-1"><i class="fas fa-check-circle mr-3"></i></div>
                    <div><p class="font-bold">Success!</p><p class="text-sm"><?php echo $success_reservation; ?></p></div></div>
                </div>
            <?php endif; ?>
            <?php if (!empty($error_survey)): ?>
                <div class="bg-red-50 border-l-4 border-accent-red text-accent-red p-4 mb-6 rounded-md" role="alert">
                    <div class="flex"><div class="py-1"><i class="fas fa-times-circle mr-3"></i></div>
                    <div><p class="font-bold">Survey Error</p><p class="text-sm"><?php echo $error_survey; ?></p></div></div>
                </div>
            <?php endif; ?>
             <?php if (!empty($success_survey)): ?>
                 <div class="bg-green-50 border-l-4 border-accent-green text-accent-green p-4 mb-6 rounded-md" role="alert">
                    <div class="flex"><div class="py-1"><i class="fas fa-check-circle mr-3"></i></div>
                    <div><p class="font-bold">Survey Submitted!</p><p class="text-sm"><?php echo $success_survey; ?></p></div></div>
                </div>
            <?php endif; ?>


            <!-- Main Content Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Reservation Form Column -->
                <div class="lg:col-span-2 bg-gray-50 p-6 rounded-lg border border-gray-200">
                    <h3 class="text-xl font-semibold mb-1 text-text-primary">Create New Reservation</h3>
                    <p class="text-sm text-text-secondary mb-6">Fields marked with <span class="text-accent-red">*</span> are required.</p>

                    <?php if ($pending_reservations > 0): ?>
                        <div class="bg-yellow-50 border-l-4 border-accent-yellow text-yellow-700 p-4 rounded-md">
                            <div class="flex"><div class="py-1"><i class="fas fa-info-circle mr-3"></i></div>
                            <div><p class="font-bold">Pending Reservation</p><p class="text-sm">You already have a reservation pending approval. Please wait for it to be processed.</p></div></div>
                        </div>
                    <?php elseif ($total_sitins >= 10 && !$survey_completed): ?>
                        <div class="bg-blue-50 border-l-4 border-accent-blue text-accent-blue p-4 rounded-md text-center">
                             <div class="flex mb-2"><div class="py-1"><i class="fas fa-poll mr-3 text-xl"></i></div>
                            <div><p class="font-bold">Survey Required</p><p class="text-sm">Please complete the satisfaction survey to make new reservations.</p></div></div>
                            <button onclick="showSurveyModal()" class="mt-2 inline-flex items-center justify-center px-5 py-2.5 bg-gradient-to-r from-custom-purple to-custom-indigo text-white font-medium rounded-lg hover:from-purple-700 hover:to-indigo-700 transition-all shadow-md text-sm">
                                <i class="fas fa-clipboard-check mr-2"></i> Complete Survey Now
                            </button>
                        </div>
                    <?php elseif ($remaining_sessions <= 0): ?>
                         <div class="bg-orange-50 border-l-4 border-orange-500 text-orange-700 p-4 rounded-md">
                            <div class="flex"><div class="py-1"><i class="fas fa-hourglass-end mr-3"></i></div>
                            <div><p class="font-bold">No Sessions Left</p><p class="text-sm">You have used all your available sit-in sessions for this week.</p></div></div>
                        </div>
                    <?php else: ?>
                        <form method="POST" action="reservation.php" class="space-y-5">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                                <div>
                                    <label for="studentIdDisplay" class="block text-sm form-label">Student ID</label>
                                    <input type="text" id="studentIdDisplay" value="<?php echo htmlspecialchars($idno_session); ?>" readonly class="mt-1 block w-full px-3 py-2.5 bg-gray-200 text-gray-500 rounded-md shadow-sm sm:text-sm border-gray-300 cursor-not-allowed">
                                </div>
                                <div>
                                    <label for="studentNameDisplay" class="block text-sm form-label">Name</label>
                                    <input type="text" id="studentNameDisplay" value="<?php echo htmlspecialchars($firstname . ' ' . $lastname); ?>" readonly class="mt-1 block w-full px-3 py-2.5 bg-gray-200 text-gray-500 rounded-md shadow-sm sm:text-sm border-gray-300 cursor-not-allowed">
                                </div>
                            </div>
                            <div>
                                <label for="purpose" class="block text-sm form-label">Purpose <span class="text-accent-red">*</span></label>
                                <select id="purpose" name="purpose" required class="mt-1 block w-full px-3 py-2.5 input-field select-field rounded-md shadow-sm sm:text-sm transition-all">
                                    <option value="" <?php echo ($form_purpose == '') ? 'selected' : ''; ?> disabled>Select Purpose</option>
                                    <option value="C Programming" <?php echo ($form_purpose == 'C Programming') ? 'selected' : ''; ?>>C Programming</option>
                                    <option value="Java Programming" <?php echo ($form_purpose == 'Java Programming') ? 'selected' : ''; ?>>Java Programming</option>
                                    <option value="C# Programming" <?php echo ($form_purpose == 'C# Programming') ? 'selected' : ''; ?>>C# Programming</option>
                                    <option value="Systems Integration & Architecture" <?php echo ($form_purpose == 'Systems Integration & Architecture') ? 'selected' : ''; ?>>Systems Integration & Architecture</option>
                                     <option value="Embedded Systems & IoT" <?php echo ($form_purpose == 'Embedded Systems & IoT') ? 'selected' : ''; ?>>Embedded Systems & IoT</option>
                                    <option value="Computer Application" <?php echo ($form_purpose == 'Computer Application') ? 'selected' : ''; ?>>Computer Application</option>
                                    <option value="Database" <?php echo ($form_purpose == 'Database') ? 'selected' : ''; ?>>Database</option>
                                    <option value="Project Management" <?php echo ($form_purpose == 'Project Management') ? 'selected' : ''; ?>>Project Management</option>
                                    <option value="Python Programming" <?php echo ($form_purpose == 'Python Programming') ? 'selected' : ''; ?>>Python Programming</option>
                                    <option value="Mobile Application" <?php echo ($form_purpose == 'Mobile Application') ? 'selected' : ''; ?>>Mobile Application</option>
                                    <option value="Web Design" <?php echo ($form_purpose == 'Web Design') ? 'selected' : ''; ?>>Web Design</option>
                                    <option value="Php Programming" <?php echo ($form_purpose == 'Php Programming') ? 'selected' : ''; ?>>Php Programming</option>
                                    <option value="Other" <?php echo ($form_purpose == 'Other') ? 'selected' : ''; ?>>Other</option>
                                </select>
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                                <div>
                                    <label for="lab_room" class="block text-sm form-label">Laboratory Room <span class="text-accent-red">*</span></label>
                                    <select id="lab_room" name="lab_room" required class="mt-1 block w-full px-3 py-2.5 input-field select-field rounded-md shadow-sm sm:text-sm transition-all" onchange="updatePcAvailability()">
                                        <option value="" <?php echo ($form_lab_room == '') ? 'selected' : ''; ?> disabled>Select Lab</option>
                                        <?php foreach ($labs as $lab_option): ?>
                                            <option value="<?php echo $lab_option; ?>" <?php echo ($form_lab_room == $lab_option) ? 'selected' : ''; ?>>
                                                <?php echo $lab_option; ?> 
                                                (<?php echo $lab_pcs_summary[$lab_option]['available']; ?> Available)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div id="pcSelectionContainer" class="hidden">
                                    <label for="pc_number_display" class="block text-sm form-label">Select PC (Optional)</label>
                                    <div id="pcGrid" class="mt-1 grid grid-cols-3 sm:grid-cols-4 gap-2 p-3 bg-input-bg border border-input-border rounded-md max-h-32 overflow-y-auto">
                                    </div>
                                    <input type="hidden" name="pc_number" id="selectedPcInput" value="<?php echo htmlspecialchars($form_pc_number); ?>">
                                </div>
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                                <div>
                                    <label for="reservation_date" class="block text-sm form-label">Date <span class="text-accent-red">*</span></label>
                                    <input type="date" id="reservation_date" name="reservation_date" min="<?php echo date('Y-m-d'); ?>" value="<?php echo htmlspecialchars($form_reservation_date); ?>" required 
                                           class="mt-1 block w-full px-3 py-2.5 input-field rounded-md shadow-sm sm:text-sm transition-all">
                                </div>
                                <div>
                                    <label for="time_in" class="block text-sm form-label">Time In <span class="text-accent-red">*</span></label>
                                    <input type="time" id="time_in" name="time_in" value="<?php echo htmlspecialchars($form_time_in); ?>" required 
                                           class="mt-1 block w-full px-3 py-2.5 input-field rounded-md shadow-sm sm:text-sm transition-all">
                                </div>
                            </div>
                            <div class="pt-2">
                                <button type="submit" name="submit_reservation"
                                        class="w-full flex items-center justify-center py-3 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-gradient-to-r from-custom-purple to-custom-indigo hover:from-purple-700 hover:to-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-custom-indigo transition-all duration-200 shadow-md">
                                    <i class="fas fa-calendar-plus mr-2"></i> Submit Reservation
                                </button>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>

                <!-- Reservation History Column -->
                <div class="lg:col-span-1 bg-gray-50 p-6 rounded-lg border border-gray-200">
                    <h3 class="text-xl font-semibold mb-4 text-text-primary">Your Reservation History</h3>
                    <div class="max-h-96 overflow-y-auto space-y-3">
                        <?php
                        $reservations_hist_stmt = $conn->prepare("SELECT id, reservation_date, lab_room, pc_number, time_in, status FROM reservations WHERE student_id = ? ORDER BY created_at DESC LIMIT 10"); // Show recent 10
                        if ($reservations_hist_stmt) {
                            $reservations_hist_stmt->bind_param("i", $student_id);
                            $reservations_hist_stmt->execute();
                            $result_hist = $reservations_hist_stmt->get_result();
                            
                            if ($result_hist->num_rows > 0) {
                                while ($row_hist = $result_hist->fetch_assoc()) {
                                    $status_class = '';
                                    $status_icon = '';
                                    switch (strtolower($row_hist['status'])) {
                                        case 'approved': $status_class = 'bg-green-100 text-accent-green'; $status_icon = 'fas fa-check-circle'; break;
                                        case 'disapproved': $status_class = 'bg-red-100 text-accent-red'; $status_icon = 'fas fa-times-circle'; break;
                                        case 'pending': $status_class = 'bg-yellow-100 text-accent-yellow'; $status_icon = 'fas fa-hourglass-half'; break;
                                        default: $status_class = 'bg-gray-100 text-gray-500'; $status_icon = 'fas fa-question-circle';
                                    }
                                    echo "<div class='p-3 border border-gray-200 rounded-md bg-white shadow-sm'>";
                                    echo "<div class='flex justify-between items-center mb-1'>";
                                    echo "<p class='text-sm font-medium text-text-primary'>" . htmlspecialchars($row_hist['lab_room']) . ($row_hist['pc_number'] ? " - PC " . htmlspecialchars($row_hist['pc_number']) : "") . "</p>";
                                    echo "<span class='px-2 py-0.5 text-xs font-semibold rounded-full " . $status_class . "'><i class='" . $status_icon . " mr-1'></i>" . ucfirst(htmlspecialchars($row_hist['status'])) . "</span>";
                                    echo "</div>";
                                    echo "<p class='text-xs text-text-secondary'>" . (new DateTime($row_hist['reservation_date']))->format('M d, Y') . " at " . (new DateTime('1970-01-01 ' . $row_hist['time_in']))->format('h:i A') . "</p>";
                                    echo "</div>";
                                }
                            } else {
                                echo "<p class='text-sm text-text-secondary text-center py-4'>No reservation history found.</p>";
                            }
                            $reservations_hist_stmt->close();
                        } else {
                             echo "<p class='text-sm text-red-500 text-center py-4'>Error loading reservation history.</p>";
                             error_log("Error preparing reservation history query: " . $conn->error);
                        }
                        ?>
                    </div>
                    <?php if ($result_hist && $result_hist->num_rows > 0) : ?>
                    <div class="mt-4 text-center">
                        <a href="sit_in_history.php#reservations" class="text-sm font-medium text-custom-purple hover:text-custom-indigo hover:underline">View All Reservations</a>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Satisfaction Survey Modal -->
    <?php if ($total_sitins >= 10 && !$survey_completed): ?>
    <div id="surveyModalContainer" class="fixed inset-0 bg-black bg-opacity-60 backdrop-blur-sm flex items-center justify-center z-[100] animate-fade-in-scale">
        <div class="bg-card-bg rounded-xl shadow-2xl p-6 sm:p-8 w-full max-w-lg mx-4 relative">
            <h3 class="text-2xl font-semibold mb-2 text-text-primary text-center">Sit-in Experience Survey</h3>
            <p class="text-text-secondary mb-6 text-center text-sm">Your feedback is valuable! Please rate your overall experience after completing 10 sit-in sessions.</p>
            
            <form method="POST" action="reservation.php" class="space-y-6">
                <div>
                    <p class="text-center form-label mb-2">Overall Satisfaction:</p>
                    <div class="star-rating">
                        <input type="radio" id="star5" name="satisfaction" value="5" required /><label for="star5" title="5 stars">★</label>
                        <input type="radio" id="star4" name="satisfaction" value="4" /><label for="star4" title="4 stars">★</label>
                        <input type="radio" id="star3" name="satisfaction" value="3" /><label for="star3" title="3 stars">★</label>
                        <input type="radio" id="star2" name="satisfaction" value="2" /><label for="star2" title="2 stars">★</label>
                        <input type="radio" id="star1" name="satisfaction" value="1" /><label for="star1" title="1 star">★</label>
                    </div>
                </div>
                
                <div>
                    <label for="comments" class="block text-sm form-label mb-1">Comments & Suggestions (Optional)</label>
                    <textarea name="comments" id="comments" rows="4" class="w-full px-3 py-2.5 input-field select-field rounded-md shadow-sm sm:text-sm transition-all" placeholder="Tell us more about your experience or how we can improve..."></textarea>
                </div>
                
                <div class="pt-2 flex flex-col sm:flex-row sm:justify-end sm:space-x-3 space-y-2 sm:space-y-0">
                     <button type="button" onclick="closeSurveyModal()" 
                            class="w-full sm:w-auto order-2 sm:order-1 px-5 py-2.5 border border-input-border text-sm font-medium rounded-md text-text-secondary hover:bg-gray-100 transition-all">
                        Maybe Later
                    </button>
                    <button type="submit" name="submit_survey" 
                            class="w-full sm:w-auto order-1 sm:order-2 flex items-center justify-center px-5 py-2.5 bg-gradient-to-r from-custom-purple to-custom-indigo text-white font-medium rounded-md hover:from-purple-700 hover:to-indigo-700 transition-all shadow-md text-sm">
                        <i class="fas fa-paper-plane mr-2"></i> Submit Survey
                    </button>
                </div>
            </form>
        </div>
    </div>
    <script>
        function showSurveyModal() {
            const modalContainer = document.getElementById('surveyModalContainer');
            if (modalContainer) modalContainer.classList.remove('hidden');
        }
        function closeSurveyModal() {
            const modalContainer = document.getElementById('surveyModalContainer');
            if (modalContainer) modalContainer.classList.add('hidden');
        }
        <?php if ($total_sitins >= 10 && !$survey_completed && !isset($_POST['submit_survey'])): // Auto-show if not just submitted ?>
        window.addEventListener('load', () => {
             // Don't auto-show if there was an error trying to submit it
            <?php if (empty($error_survey)): ?>
                showSurveyModal();
            <?php endif; ?>
        });
        <?php endif; ?>
    </script>
    <?php endif; ?>


    <script>
        // Dropdown toggle functions
function toggleDropdown(id) {
    const dropdown = document.getElementById(id);
    dropdown.classList.toggle('hidden');
    // Hide other dropdowns
    document.querySelectorAll('.nav-dropdown').forEach(el => {
        if (el.id !== id) el.classList.add('hidden');
    });
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

        // PC Availability JS
        function updatePcAvailability() {
            const labSelect = document.getElementById('lab_room');
            const pcContainer = document.getElementById('pcSelectionContainer');
            const pcGrid = document.getElementById('pcGrid');
            const selectedPcInput = document.getElementById('selectedPcInput'); // Changed ID

            if (!labSelect || !pcContainer || !pcGrid || !selectedPcInput) return;
            const labName = labSelect.value;
            
            if (labName) {
                pcGrid.innerHTML = '<div class="col-span-full text-center py-4 text-text-secondary"><i class="fas fa-spinner fa-spin mr-2"></i>Loading PCs...</div>';
                pcContainer.classList.remove('hidden');
                
                fetch(`get_pcs.php?lab=${encodeURIComponent(labName)}`, { headers: {'X-Requested-With': 'XMLHttpRequest'} })
                .then(response => {
                    if (!response.ok) throw new Error('Network response was not ok: ' + response.statusText);
                    return response.text();
                })
                .then(data => { pcGrid.innerHTML = data; })
                .catch(error => {
                    console.error('Error fetching PCs:', error);
                    pcGrid.innerHTML = `<div class="col-span-full text-center py-4 text-accent-red"><i class="fas fa-exclamation-triangle mr-2"></i>Failed to load PCs.</div>`;
                });
            } else {
                pcContainer.classList.add('hidden');
                selectedPcInput.value = '';
            }
        }
        
        function selectPc(pcNumber, cardElement) {
            const pcCards = document.querySelectorAll('#pcGrid .pc-card');
            const selectedPcInput = document.getElementById('selectedPcInput');
             if (!selectedPcInput) return;

            // Check if the clicked PC is already selected
            const isAlreadySelected = cardElement.classList.contains('selected');

            pcCards.forEach(card => card.classList.remove('selected', 'border-custom-indigo', 'bg-indigo-50'));
            
            if (isAlreadySelected) {
                selectedPcInput.value = ''; // Deselect
            } else {
                cardElement.classList.add('selected', 'border-custom-indigo', 'bg-indigo-50');
                selectedPcInput.value = pcNumber;
            }
        }
        // Call on page load if a lab is pre-selected (e.g., due to form resubmission with errors)
        window.addEventListener('load', () => {
            if (document.getElementById('lab_room')?.value) {
                updatePcAvailability();
            }
        });
    </script>
</body>
</html>
<?php
if (isset($conn)) {
    $conn->close();
}
?>