<?php
session_start();
include 'db.php'; // Make sure this path is correct

// Check if user is logged in as student
if (!isset($_SESSION['idno']) || $_SESSION['role'] !== 'student') {
    header("Location: login.php");
    exit();
}

// Set page title
$page_title = "Sit-in History";
$idno_session = $_SESSION['idno']; // Use a different variable for session ID


// Fetch minimal user data for navbar
$stmt_nav = $conn->prepare("SELECT firstname, profile_picture FROM users WHERE idno = ?");
if ($stmt_nav) {
    $stmt_nav->bind_param("s", $idno_session);
    $stmt_nav->execute();
    $stmt_nav->bind_result($nav_fn, $nav_pp);
    if ($stmt_nav->fetch()) {
        $navbar_firstname = $nav_fn;
        $navbar_profile_picture = !empty($nav_pp) ? $nav_pp : 'default_avatar.jpg';
    }
    $stmt_nav->close();
} else {
    error_log("Error preparing navbar user statement: " . $conn->error);
}

// Handle feedback submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['feedback_text']) && isset($_POST['record_id'])) {
    $record_id = (int)$_POST['record_id']; // Cast to int
    $feedback = trim($_POST['feedback_text']);
    
    // Validate record_id belongs to the current user to prevent unauthorized updates
    $check_owner_stmt = $conn->prepare("SELECT student_id FROM sit_in_records WHERE id = ?");
    if($check_owner_stmt){
        $check_owner_stmt->bind_param("i", $record_id);
        $check_owner_stmt->execute();
        $check_owner_stmt->bind_result($record_student_id); // This should be the actual 'id' from users table
        $check_owner_stmt->fetch();
        $check_owner_stmt->close();

        $current_user_db_id_stmt = $conn->prepare("SELECT id FROM users WHERE idno = ?");
        $current_user_db_id_stmt->bind_param("s", $idno_session);
        $current_user_db_id_stmt->execute();
        $current_user_db_id_stmt->bind_result($current_user_db_id);
        $current_user_db_id_stmt->fetch();
        $current_user_db_id_stmt->close();

        if ($record_student_id == $current_user_db_id) {
            $feedback_query = "UPDATE sit_in_records SET feedback = ? WHERE id = ?";
            $stmt_feedback = $conn->prepare($feedback_query);
            if ($stmt_feedback) {
                $stmt_feedback->bind_param("si", $feedback, $record_id);
                if($stmt_feedback->execute()){
                    $_SESSION['success_message_feedback'] = "Feedback submitted successfully!";
                } else {
                    $_SESSION['error_message_feedback'] = "Error submitting feedback: " . $stmt_feedback->error;
                }
                $stmt_feedback->close();
            } else {
                 $_SESSION['error_message_feedback'] = "Database error preparing feedback update.";
            }
        } else {
             $_SESSION['error_message_feedback'] = "Unauthorized attempt to submit feedback.";
        }
    } else {
        $_SESSION['error_message_feedback'] = "Error verifying record ownership.";
    }
    
    header("Location: sit_in_history.php"); // Reload to show updated feedback and messages
    exit();
}

// Fetch the sit-in history for the logged-in user
$sit_in_history = [];
$stmt_history = $conn->prepare("SELECT sir.id, sir.purpose, sir.lab, sir.start_time, sir.end_time, sir.feedback 
                                FROM sit_in_records sir
                                JOIN users u ON sir.student_id = u.id
                                WHERE u.idno = ? 
                                ORDER BY sir.start_time DESC");
if ($stmt_history) {
    $stmt_history->bind_param("s", $idno_session);
    $stmt_history->execute();
    $result = $stmt_history->get_result();
    $sit_in_history = $result->fetch_all(MYSQLI_ASSOC);
    $stmt_history->close();
} else {
    error_log("Error preparing sit-in history query: " . $conn->error);
    // Optionally set an error message to display on the page
    $page_error = "Could not load sit-in history.";
}

// Retrieve messages from session and then unset them
if (isset($_SESSION['success_message_feedback'])) {
    $success_feedback = $_SESSION['success_message_feedback'];
    unset($_SESSION['success_message_feedback']);
}
if (isset($_SESSION['error_message_feedback'])) {
    $error_feedback = $_SESSION['error_message_feedback'];
    unset($_SESSION['error_message_feedback']);
}

$conn->close();
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
                        'input-bg': '#F9FAFB', 'input-border': '#D1D5DB',
                    }
                },
            },
        }
    </script>
    <style>
        body {             background-color: #F1E6EF; padding-top: 76px; }
        .main-content-area { padding: 2rem 1rem; }
        @media (min-width: 768px) { .main-content-area { padding: 2rem; } }
        @media (min-width: 1024px) { .main-content-area { padding: 3rem 4rem; } }
        .nav-dropdown a:hover { background-color: theme('colors.purple.50'); color: theme('colors.custom-purple'); }
        .nav-dropdown { z-index: 20; }
        .input-field { background-color: theme('colors.input-bg'); border-color: theme('colors.input-border'); color: theme('colors.text-primary'); }
        .input-field:focus { outline: none; border-color: theme('colors.custom-purple'); box-shadow: 0 0 0 2px theme('colors.custom-purple'), 0 0 0 4px rgba(109, 40, 217, 0.2); }
        .page-header-icon { color: theme('colors.custom-purple'); }

        /* Feedback Modal Styles */
        .feedback-modal-overlay {
            position: fixed; inset: 0;
            background-color: rgba(0, 0, 0, 0.6); backdrop-filter: blur(4px);
            z-index: 100; display: flex;
            justify-content: center; align-items: center;
            opacity: 0; transition: opacity 0.3s ease-out; pointer-events: none;
        }
        .feedback-modal-overlay.active { opacity: 1; pointer-events: auto; }
        .feedback-modal-content {
            background-color: theme('colors.card-bg');
            border-radius: 0.75rem; /* rounded-xl */
            padding: 1.5rem; /* p-6 */
            width: 90%; max-width: 500px; /* md */
            box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1), 0 10px 10px -5px rgba(0,0,0,0.04); /* shadow-2xl */
            transform: scale(0.95); opacity: 0;
            transition: transform 0.3s ease-out, opacity 0.3s ease-out;
        }
        .feedback-modal-overlay.active .feedback-modal-content { transform: scale(1); opacity: 1;}
        .modal-textarea { background-color: theme('colors.input-bg'); border: 1px solid theme('colors.input-border'); color: theme('colors.text-primary');}
        .modal-textarea:focus { border-color: theme('colors.custom-purple'); box-shadow: 0 0 0 2px theme('colors.custom-purple'), 0 0 0 4px rgba(109, 40, 217, 0.2); }
    </style>
</head>
<body class="font-sans antialiased text-text-primary">

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
                <img src="uploads/<?php echo htmlspecialchars(!empty($navbar_profile_picture) ? $navbar_profile_picture : 'default_avatar.jpg'); ?>" 
                     alt="User Avatar" 
                     class="w-10 h-10 rounded-full object-cover border-2 border-custom-purple"
                     onerror="this.src='uploads/default_avatar.jpg'">
      </div>
      <h2 class="px-4 py-2 text-gray-700 font-bold"><?php echo htmlspecialchars($navbar_firstname); ?></h2>

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
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 sm:py-12 main-content-area max-h-100vh">
        <div class="bg-card-bg p-6 sm:p-8 rounded-xl shadow-2xl">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6 sm:mb-8 pb-4 border-b border-gray-200">
                <div class="flex items-center mb-4 sm:mb-0">
                    <div class="flex-shrink-0 w-12 h-12 rounded-lg bg-custom-purple/10 flex items-center justify-center text-custom-purple mr-4">
                         <i class="fas fa-history text-2xl page-header-icon"></i>
                    </div>
                    <div>
                        <h1 class="text-2xl sm:text-3xl font-bold text-text-primary"><?php echo htmlspecialchars($page_title); ?></h1>
                        <p class="text-sm text-text-secondary mt-1">Review your past and current sit-in sessions.</p>
                    </div>
                </div>
                <!-- Optional: Add a button or filter here if needed -->
            </div>

            <?php if (isset($success_feedback)): ?>
                <div class="bg-green-50 border-l-4 border-accent-green text-accent-green p-4 mb-6 rounded-md" role="alert">
                    <div class="flex"><div class="py-1"><i class="fas fa-check-circle mr-3"></i></div>
                    <div><p class="font-bold">Feedback Submitted!</p><p class="text-sm"><?php echo $success_feedback; ?></p></div></div>
                </div>
            <?php endif; ?>
            <?php if (isset($error_feedback)): ?>
                <div class="bg-red-50 border-l-4 border-accent-red text-accent-red p-4 mb-6 rounded-md" role="alert">
                    <div class="flex"><div class="py-1"><i class="fas fa-times-circle mr-3"></i></div>
                    <div><p class="font-bold">Feedback Error</p><p class="text-sm"><?php echo $error_feedback; ?></p></div></div>
                </div>
            <?php endif; ?>
            <?php if (isset($page_error)): ?>
                 <div class="bg-red-50 border-l-4 border-accent-red text-accent-red p-4 mb-6 rounded-md" role="alert">
                    <p class="text-sm"><?php echo $page_error; ?></p>
                </div>
            <?php endif; ?>


            <!-- History Table -->
            <div class="overflow-x-auto rounded-lg border border-gray-200">
                <table class="w-full min-w-[768px]">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-text-secondary uppercase tracking-wider">Purpose</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-text-secondary uppercase tracking-wider">Lab</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-text-secondary uppercase tracking-wider">Start Time</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-text-secondary uppercase tracking-wider">End Time</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-text-secondary uppercase tracking-wider text-center">Status</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-text-secondary uppercase tracking-wider">Feedback</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if (empty($sit_in_history)): ?>
                            <tr>
                                <td colspan="6" class="px-6 py-10 text-center text-text-secondary">
                                    <div class="flex flex-col items-center">
                                        <i class="fas fa-folder-open text-4xl text-gray-300 mb-3"></i>
                                        No sit-in records found.
                                    </div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($sit_in_history as $record): ?>
                                <?php
                                    $start_time_obj = new DateTime($record['start_time']);
                                    $end_time_obj = $record['end_time'] ? new DateTime($record['end_time']) : null;
                                    $status_text = $end_time_obj ? 'Completed' : 'Active';
                                    $status_class = $end_time_obj ? 'bg-green-100 text-accent-green' : 'bg-blue-100 text-accent-blue';
                                ?>
                                <tr class="hover:bg-gray-50 transition-colors duration-150">
                                    <td class="px-4 py-3 text-sm text-text-primary whitespace-nowrap"><?php echo htmlspecialchars($record['purpose']); ?></td>
                                    <td class="px-4 py-3 text-sm text-text-primary whitespace-nowrap"><?php echo htmlspecialchars($record['lab']); ?></td>
                                    <td class="px-4 py-3 text-sm text-text-secondary whitespace-nowrap"><?php echo $start_time_obj->format('M d, Y - h:i A'); ?></td>
                                    <td class="px-4 py-3 text-sm text-text-secondary whitespace-nowrap">
                                        <?php echo $end_time_obj ? $end_time_obj->format('M d, Y - h:i A') : '<span class="font-medium">In Progress</span>'; ?>
                                    </td>
                                    <td class="px-4 py-3 text-center whitespace-nowrap">
                                        <span class="px-2.5 py-1 text-xs font-semibold rounded-full <?php echo $status_class; ?>">
                                            <?php echo $status_text; ?>
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-text-primary">
                                        <?php if (!empty($record['feedback'])): ?>
                                            <div class="group relative">
                                                <span class="truncate block max-w-[150px]"><?php echo htmlspecialchars($record['feedback']); ?></span>
                                                <div class="absolute z-10 hidden group-hover:block bottom-full mb-2 w-64 p-2 text-xs text-white bg-gray-800 rounded-md shadow-lg">
                                                    <?php echo nl2br(htmlspecialchars($record['feedback'])); ?>
                                                </div>
                                            </div>
                                            <button onclick="openFeedbackModal(<?php echo $record['id']; ?>, '<?php echo htmlspecialchars(addslashes($record['feedback'])); ?>')" 
                                                    class="mt-1 text-xs text-custom-purple hover:text-custom-indigo hover:underline">
                                                Edit Feedback
                                            </button>
                                        <?php elseif ($end_time_obj): // Only allow adding feedback if session is completed ?>
                                            <button onclick="openFeedbackModal(<?php echo $record['id']; ?>, '')" 
                                                    class="text-xs text-custom-purple hover:text-custom-indigo hover:underline flex items-center">
                                                <i class="fas fa-plus-circle mr-1"></i> Add Feedback
                                            </button>
                                        <?php else: ?>
                                            <span class="text-xs text-gray-400 italic">N/A (Active)</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
             <?php if (!empty($sit_in_history)): ?>
             <div class="mt-6 text-center">
                 <a href="student_dashboard.php" class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-md shadow-sm text-white bg-gradient-to-r from-custom-purple to-custom-indigo hover:from-purple-700 hover:to-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-custom-indigo">
                    <i class="fas fa-arrow-left mr-2"></i> Back to Dashboard
                </a>
             </div>
             <?php endif; ?>
        </div>
    </div>

    <!-- Feedback Modal Structure (Single Modal, Content updated by JS) -->
    <div id="feedbackModalOverlay" class="feedback-modal-overlay hidden">
        <div id="feedbackModalContent" class="feedback-modal-content">
            <div class="flex justify-between items-center mb-4">
                <h3 id="feedbackModalTitle" class="text-xl font-semibold text-text-primary">Provide Feedback</h3>
                <button onclick="closeFeedbackModal()" class="text-text-secondary hover:text-text-primary transition-colors">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            <form id="feedbackForm" method="POST" action="sit_in_history.php" class="space-y-4">
                <input type="hidden" name="record_id" id="modalRecordId">
                <div>
                    <label for="modalFeedbackText" class="block text-sm form-label mb-1">Your Feedback:</label>
                    <textarea name="feedback_text" id="modalFeedbackText" rows="5" 
                              class="w-full px-3 py-2.5 modal-textarea rounded-md shadow-sm sm:text-sm transition-all"
                              placeholder="Share your thoughts on this sit-in session..."></textarea>
                </div>
                <div class="pt-2 flex flex-col sm:flex-row sm:justify-end sm:space-x-3 space-y-2 sm:space-y-0">
                    <button type="button" onclick="closeFeedbackModal()" 
                            class="w-full sm:w-auto order-2 sm:order-1 px-5 py-2.5 border border-input-border text-sm font-medium rounded-md text-text-secondary hover:bg-gray-100 transition-all">
                        Cancel
                    </button>
                    <button type="submit"
                            class="w-full sm:w-auto order-1 sm:order-2 flex items-center justify-center px-5 py-2.5 bg-gradient-to-r from-custom-purple to-custom-indigo text-white font-medium rounded-md hover:from-purple-700 hover:to-indigo-700 transition-all shadow-md text-sm">
                        <i class="fas fa-save mr-2"></i> Save Feedback
                    </button>
                </div>
            </form>
        </div>
    </div>

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

        // Feedback Modal JS
        const feedbackModalOverlay = document.getElementById('feedbackModalOverlay');
        const feedbackModalContent = document.getElementById('feedbackModalContent');
        const modalRecordIdInput = document.getElementById('modalRecordId');
        const modalFeedbackTextInput = document.getElementById('modalFeedbackText');
        const feedbackModalTitle = document.getElementById('feedbackModalTitle');

        function openFeedbackModal(recordId, currentFeedback) {
            if (modalRecordIdInput && modalFeedbackTextInput && feedbackModalOverlay && feedbackModalTitle) {
                modalRecordIdInput.value = recordId;
                modalFeedbackTextInput.value = currentFeedback;
                feedbackModalTitle.textContent = currentFeedback ? 'Edit Feedback' : 'Add Feedback';
                feedbackModalOverlay.classList.add('active');
                document.body.style.overflow = 'hidden'; // Prevent background scroll
            }
        }

        function closeFeedbackModal() {
            if (feedbackModalOverlay) {
                feedbackModalOverlay.classList.remove('active');
                document.body.style.overflow = ''; // Restore background scroll
            }
        }
        // Close modal if overlay is clicked
        if(feedbackModalOverlay) {
            feedbackModalOverlay.addEventListener('click', function(event) {
                if (event.target === feedbackModalOverlay) {
                    closeFeedbackModal();
                }
            });
        }
    </script>
</body>
</html>