<?php
session_start();
include 'db.php';

// Check if the user is logged in
if (!isset($_SESSION['idno'])) {
    header("Location: login.php");
    exit();
}

$idno_session = $_SESSION['idno']; // Use a different variable for session ID to avoid conflict

// Initialize variables with default values from session or database
$firstname = $_SESSION['db_firstname'] ?? ''; // Assuming these are set at login from db
$lastname = $_SESSION['db_lastname'] ?? '';
$email = $_SESSION['db_email'] ?? '';
$course = $_SESSION['db_course'] ?? '';
$yearlevel = $_SESSION['db_yearlevel'] ?? '';
$username_current = $_SESSION['db_username'] ?? ''; // Use a different var for current username
$profile_picture = $_SESSION['db_profile_picture'] ?? 'default_avatar.png';


// Fetch fresh data from DB on initial load if not already in session or to ensure it's current
if (empty($firstname) || empty($username_current)) { // Example condition to fetch fresh
    $stmt_fetch = $conn->prepare("SELECT firstname, lastname, email, course, yearlevel, username, profile_picture FROM users WHERE idno = ?");
    if ($stmt_fetch) {
        $stmt_fetch->bind_param("s", $idno_session);
        $stmt_fetch->execute();
        $stmt_fetch->bind_result($db_firstname, $db_lastname, $db_email, $db_course, $db_yearlevel, $db_username, $db_profile_picture);
        if ($stmt_fetch->fetch()) {
            $firstname = $db_firstname;
            $lastname = $db_lastname;
            $email = $db_email;
            $course = $db_course;
            $yearlevel = $db_yearlevel;
            $username_current = $db_username;
            $profile_picture = $db_profile_picture ?: 'default_avatar.png';

            // Update session variables with fresh data
            $_SESSION['db_firstname'] = $firstname;
            $_SESSION['db_lastname'] = $lastname;
            $_SESSION['db_email'] = $email;
            $_SESSION['db_course'] = $course;
            $_SESSION['db_yearlevel'] = $yearlevel;
            $_SESSION['db_username'] = $username_current;
            $_SESSION['db_profile_picture'] = $profile_picture;
        }
        $stmt_fetch->close();
    }
}


// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $posted_firstname = trim($_POST['firstname']);
    $posted_lastname = trim($_POST['lastname']);
    $posted_email = trim($_POST['email']);
    $posted_course = trim($_POST['course']);
    $posted_yearlevel = trim($_POST['yearlevel']);
    $new_username = trim($_POST['username']);

    $errors = [];

    // Validations
    if (empty($posted_firstname)) $errors[] = "First name is required.";
    if (empty($posted_lastname)) $errors[] = "Last name is required.";
    if (!filter_var($posted_email, FILTER_VALIDATE_EMAIL)) $errors[] = "Invalid email format.";
    if (empty($posted_course)) $errors[] = "Course is required.";
    if (empty($posted_yearlevel)) $errors[] = "Year level is required.";
    if (empty($new_username)) $errors[] = "Username is required.";


    // Check if the email is already used by another user
    if (empty($errors) && $posted_email !== $email) { // Only check if email changed
        $email_check_sql = "SELECT idno FROM users WHERE email = ? AND idno != ?";
        if ($stmt = $conn->prepare($email_check_sql)) {
            $stmt->bind_param("ss", $posted_email, $idno_session);
            $stmt->execute();
            $stmt->store_result();
            if ($stmt->num_rows > 0) {
                $errors[] = "Email is already in use by another account.";
            }
            $stmt->close();
        } else {
            $errors[] = "Database error: Unable to check email.";
        }
    }
    
    // Check if the username is already used by another user
    if (empty($errors) && $new_username !== $username_current) { // Only check if username changed
        $username_check_sql = "SELECT idno FROM users WHERE username = ? AND idno != ?";
        if ($stmt = $conn->prepare($username_check_sql)) {
            $stmt->bind_param("ss", $new_username, $idno_session);
            $stmt->execute();
            $stmt->store_result();
            if ($stmt->num_rows > 0) {
                $errors[] = "Username is already in use by another account.";
            }
            $stmt->close();
        } else {
            $errors[] = "Database error: Unable to check username.";
        }
    }


    if (empty($errors)) {
        // Update user details
        $update_sql = "UPDATE users SET firstname=?, lastname=?, email=?, course=?, yearlevel=?, username=? WHERE idno=?";
        if ($stmt = $conn->prepare($update_sql)) {
            $stmt->bind_param("sssssss", $posted_firstname, $posted_lastname, $posted_email, $posted_course, $posted_yearlevel, $new_username, $idno_session);
            
            if ($stmt->execute()) {
                $_SESSION['success_message'] = "Profile updated successfully!";
                // Update session variables with new data
                $_SESSION['db_firstname'] = $firstname = $posted_firstname;
                $_SESSION['db_lastname'] = $lastname = $posted_lastname;
                $_SESSION['db_email'] = $email = $posted_email;
                $_SESSION['db_course'] = $course = $posted_course;
                $_SESSION['db_yearlevel'] = $yearlevel = $posted_yearlevel;
                $_SESSION['db_username'] = $username_current = $new_username;
                $_SESSION['fullname'] = $firstname . " " . $lastname; // Update fullname if used in nav

                $current_profile_picture_db = $profile_picture; // Store current pic before potential update

                // Handle profile picture upload
                if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] == UPLOAD_ERR_OK) {
                    $target_dir = "uploads/";
                    if (!is_dir($target_dir)) {
                        mkdir($target_dir, 0755, true);
                    }
                    $file_tmp_name = $_FILES['profile_picture']['tmp_name'];
                    $file_name = $_FILES['profile_picture']['name'];
                    $file_size = $_FILES['profile_picture']['size'];
                    $file_error = $_FILES['profile_picture']['error'];
                    $file_extension = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                    
                    // Sanitize filename (basic)
                    $safe_idno = preg_replace("/[^a-zA-Z0-9_-]/", "_", $idno_session);
                    $new_filename = $safe_idno . '_' . time() . '.' . $file_extension; // Add timestamp for uniqueness
                    $target_file = $target_dir . $new_filename;
                    
                    $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
                    $max_file_size = 2 * 1024 * 1024; // 2MB

                    if (!in_array($file_extension, $allowed_types)) {
                        $_SESSION['error_message'] = "Invalid file type. Only JPG, JPEG, PNG, and GIF are allowed.";
                    } elseif ($file_size > $max_file_size) {
                        $_SESSION['error_message'] = "File is too large. Maximum size is 2MB.";
                    } else {
                        // Delete old profile picture if it exists, isn't the default, and a new one is successfully uploaded
                        if (move_uploaded_file($file_tmp_name, $target_file)) {
                            if (!empty($current_profile_picture_db) && $current_profile_picture_db != "default_avatar.png" && file_exists($target_dir . $current_profile_picture_db)) {
                                if ($current_profile_picture_db !== $new_filename) { // Don't delete if it's the same file (though unlikely with timestamp)
                                   unlink($target_dir . $current_profile_picture_db);
                                }
                            }
                            
                            // Update database with new filename
                            $update_pic_sql = "UPDATE users SET profile_picture = ? WHERE idno = ?";
                            if ($update_pic_stmt = $conn->prepare($update_pic_sql)) {
                                $update_pic_stmt->bind_param("ss", $new_filename, $idno_session);
                                $update_pic_stmt->execute();
                                $update_pic_stmt->close();
                                $_SESSION['db_profile_picture'] = $profile_picture = $new_filename; // Update session and current page variable
                                $_SESSION['success_message'] = "Profile and picture updated successfully!";
                            } else {
                                 $_SESSION['error_message'] = "Database error updating picture.";
                            }
                        } else {
                            $_SESSION['error_message'] = "Error uploading new profile picture. Check folder permissions.";
                        }
                    }
                }
                
                header("Location: edit-profile.php"); // Refresh to show updated info and messages
                exit();
            } else {
                $_SESSION['error_message'] = "Error updating profile: " . $stmt->error;
            }
            $stmt->close();
        } else {
             $_SESSION['error_message'] = "Database error: Unable to prepare update statement.";
        }
    } else {
        // If there are validation errors, set the first one to display
        $_SESSION['error_message'] = implode("<br>", $errors);
    }
    // Redirect to show errors/success message and prevent form resubmission
    header("Location: edit-profile.php");
    exit();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile - CCS SIT-IN MONITORING SYSTEM</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Poppins', 'sans-serif'],
                    },
                    colors: {
                        'custom-purple': '#6D28D9',
                        'custom-indigo': '#4F46E5',
                        'light-bg': '#F1E6EF', // Assuming this is the desired page background
                        'card-bg': '#FFFFFF',
                        'text-primary': '#1F2937',  // Dark gray for main text
                        'text-secondary': '#6B7280',// Lighter gray
                        'accent-red': '#EF4444',
                        'accent-green': '#10B981',
                        'input-bg': '#F9FAFB',
                        'input-border': '#D1D5DB',
                        'nav-bg': '#FFFFFF', // For the top navigation
                    }
                },
            },
        }
    </script>
    <style>
        body {
            background-color: #F1E6EF;
            padding-top: 80px; /* Adjust based on your navbar height */
        }
        .input-field {
            background-color: theme('colors.input-bg');
            border-color: theme('colors.input-border');
            color: theme('colors.text-primary');
        }
        .input-field:focus {
            outline: none;
            border-color: theme('colors.custom-purple');
            box-shadow: 0 0 0 2px theme('colors.custom-purple'), 0 0 0 4px rgba(109, 40, 217, 0.2);
        }
        .form-label {
            color: theme('colors.text-primary');
            font-weight: 500;
        }
        .profile-pic-preview {
            width: 128px; /* 8rem */
            height: 128px; /* 8rem */
            object-fit: cover;
            border: 4px solid theme('colors.card-bg');
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1), 0 2px 4px -1px rgba(0,0,0,0.06);
        }
        .file-input-label {
            cursor: pointer;
            background-color: theme('colors.slate.100');
            color: theme('colors.slate.700');
            border-color: theme('colors.slate.300');
        }
        .file-input-label:hover {
            background-color: theme('colors.slate.200');
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
                <img src="uploads/<?php echo htmlspecialchars(!empty($profile_picture) ? $profile_picture : 'default_avatar.jpg'); ?>" 
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

    <!-- Main Content -->
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <div class="bg-card-bg p-6 sm:p-8 rounded-xl shadow-xl">
            <h2 class="text-2xl sm:text-3xl font-bold text-text-primary mb-1 text-center sm:text-left">Edit Your Profile</h2>
            <p class="text-text-secondary mb-6 sm:mb-8 text-center sm:text-left">Keep your information up to date.</p>
            <hr class="mb-6 sm:mb-8 border-gray-200">

            <?php if (isset($_SESSION['success_message'])): ?>
                <div class="bg-green-50 border-l-4 border-accent-green text-accent-green p-4 mb-6 rounded-md" role="alert">
                    <div class="flex">
                        <div class="py-1"><i class="fas fa-check-circle mr-3"></i></div>
                        <div>
                            <p class="font-bold">Success!</p>
                            <p class="text-sm"><?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?></p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['error_message'])): ?>
                <div class="bg-red-50 border-l-4 border-accent-red text-accent-red p-4 mb-6 rounded-md" role="alert">
                     <div class="flex">
                        <div class="py-1"><i class="fas fa-exclamation-triangle mr-3"></i></div>
                        <div>
                            <p class="font-bold">Error</p>
                            <p class="text-sm"><?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?></p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <form method="POST" action="edit-profile.php" enctype="multipart/form-data" class="space-y-6">
                <div class="flex flex-col items-center space-y-4 mb-8">
                    <img id="profilePicPreview"
                         src="uploads/<?php echo htmlspecialchars(!empty($profile_picture) ? $profile_picture : 'default_avatar.png'); ?>" 
                         alt="Current Profile Picture" 
                         class="w-32 h-32 rounded-full profile-pic-preview"
                         onerror="this.src='assets/default_avatar.png'"
                    >
                    <div>
                        <label for="profile_picture" class="file-input-label inline-flex items-center px-4 py-2 border rounded-md shadow-sm text-sm font-medium hover:shadow-md transition-all">
                            <i class="fas fa-upload mr-2"></i> Change Picture
                        </label>
                        <input type="file" name="profile_picture" id="profile_picture" accept="image/jpeg, image/png, image/gif" class="sr-only" onchange="previewProfilePicture(event)">
                    </div>
                     <p class="text-xs text-text-secondary">Max: 2MB (JPG, PNG, GIF)</p>
                </div>


                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="firstname" class="block text-sm form-label">First Name <span class="text-accent-red">*</span></label>
                        <input type="text" name="firstname" id="firstname" value="<?php echo htmlspecialchars($firstname); ?>" required 
                               class="mt-1 block w-full px-3 py-2.5 input-field rounded-md shadow-sm sm:text-sm transition-all">
                    </div>
                    <div>
                        <label for="lastname" class="block text-sm form-label">Last Name <span class="text-accent-red">*</span></label>
                        <input type="text" name="lastname" id="lastname" value="<?php echo htmlspecialchars($lastname); ?>" required 
                               class="mt-1 block w-full px-3 py-2.5 input-field rounded-md shadow-sm sm:text-sm transition-all">
                    </div>
                </div>
                
                <div>
                    <label for="email" class="block text-sm form-label">Email Address <span class="text-accent-red">*</span></label>
                    <input type="email" name="email" id="email" value="<?php echo htmlspecialchars($email); ?>" required 
                           class="mt-1 block w-full px-3 py-2.5 input-field rounded-md shadow-sm sm:text-sm transition-all">
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="course" class="block text-sm form-label">Course <span class="text-accent-red">*</span></label>
                        <select name="course" id="course" required class="mt-1 block w-full px-3 py-2.5 input-field rounded-md shadow-sm sm:text-sm transition-all">
                            <option value="Bachelor of Science in Information Technology" <?php echo ($course == 'Bachelor of Science in Information Technology') ? 'selected' : ''; ?>>BS Information Technology</option>
                            <option value="Bachelor of Science in Computer Science" <?php echo ($course == 'Bachelor of Science in Computer Science') ? 'selected' : ''; ?>>BS Computer Science</option>
                            <option value="Bachelor of Science in Computer Engineering" <?php echo ($course == 'Bachelor of Science in Computer Engineering') ? 'selected' : ''; ?>>BS Computer Engineering</option>
                        </select>
                    </div>
                    <div>
                        <label for="yearlevel" class="block text-sm form-label">Year Level <span class="text-accent-red">*</span></label>
                         <select name="yearlevel" id="yearlevel" required class="mt-1 block w-full px-3 py-2.5 input-field rounded-md shadow-sm sm:text-sm transition-all">
                            <option value="1" <?php echo ($yearlevel == '1') ? 'selected' : ''; ?>>1st Year</option>
                            <option value="2" <?php echo ($yearlevel == '2') ? 'selected' : ''; ?>>2nd Year</option>
                            <option value="3" <?php echo ($yearlevel == '3') ? 'selected' : ''; ?>>3rd Year</option>
                            <option value="4" <?php echo ($yearlevel == '4') ? 'selected' : ''; ?>>4th Year</option>
                        </select>
                    </div>
                </div>
                
                <div>
                    <label for="username" class="block text-sm form-label">Username <span class="text-accent-red">*</span></label>
                    <input type="text" name="username" id="username" value="<?php echo htmlspecialchars($username_current); ?>" required 
                           class="mt-1 block w-full px-3 py-2.5 input-field rounded-md shadow-sm sm:text-sm transition-all">
                </div>
                
                <div class="pt-4">
                    <button type="submit" 
                            class="w-full flex justify-center py-3 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-gradient-to-r from-custom-purple to-custom-indigo hover:from-purple-700 hover:to-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-custom-indigo transition-all duration-200 shadow-md">
                        <i class="fas fa-save mr-2"></i> Save Changes
                    </button>
                </div>
                 <div class="text-center mt-4">
                    <a href="change_password.php" class="text-sm font-medium text-custom-purple hover:text-custom-indigo">
                        Change Password?
                    </a>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Preview profile picture
        function previewProfilePicture(event) {
            const reader = new FileReader();
            reader.onload = function() {
                const output = document.getElementById('profilePicPreview');
                output.src = reader.result;
            }
            if (event.target.files[0]) {
                reader.readAsDataURL(event.target.files[0]);
            } else {
                 // Optionally revert to original if no file selected, or do nothing
                const originalPic = "uploads/<?php echo htmlspecialchars(!empty($profile_picture) ? $profile_picture : 'default_avatar.png'); ?>";
                document.getElementById('profilePicPreview').src = originalPic;
            }
        }

        // Restore scroll position after page reload (useful if there are error/success messages)
        window.addEventListener('load', function() {
            if (localStorage.getItem('editProfileScrollPosition')) {
                window.scrollTo(0, parseInt(localStorage.getItem('editProfileScrollPosition')));
                localStorage.removeItem('editProfileScrollPosition');
            }
        });
        // Save scroll position before form submission
        document.querySelector('form').addEventListener('submit', function() {
            localStorage.setItem('editProfileScrollPosition', window.scrollY.toString());
        });

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
    </script>
</body>
</html>