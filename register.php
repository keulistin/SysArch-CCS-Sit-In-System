<?php
require 'db.php'; // Include database connection
$error = "";
$success = "";
$showModal = false;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $idno = trim($_POST['idno'] ?? '');
    $lastname = trim($_POST['lastname'] ?? '');
    $firstname = trim($_POST['firstname'] ?? '');
    $middlename = trim($_POST['middlename'] ?? '');
    $course = trim($_POST['course'] ?? '');
    $yearlevel = trim($_POST['yearlevel'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $confirm_password = trim($_POST['confirm_password'] ?? '');

    // Basic Validations
    if (empty($idno) || empty($lastname) || empty($firstname) || empty($course) || empty($yearlevel) || empty($email) || empty($username) || empty($password) || empty($confirm_password)) {
        $error = "All fields marked with * are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format.";
    } elseif (strlen($password) < 8) {
        $error = "Password must be at least 8 characters long.";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } else {
        // Check if ID number or username or email already exists
        $check_stmt = $conn->prepare("SELECT id FROM users WHERE idno = ? OR username = ? OR email = ?");
        if ($check_stmt) {
            $check_stmt->bind_param("sss", $idno, $username, $email);
            $check_stmt->execute();
            $check_stmt->store_result();
            if ($check_stmt->num_rows > 0) {
                $error = "ID Number, Username, or Email already exists.";
            }
            $check_stmt->close();
        } else {
            $error = "Database error: Unable to prepare check statement.";
        }

        if (empty($error)) { // Proceed if no errors
            // Hash the password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            // Insert data into the database
            $sql = "INSERT INTO users (idno, lastname, firstname, middlename, course, yearlevel, email, username, password, role) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'student')"; // Default role to 'student'

            if ($stmt = $conn->prepare($sql)) {
                $stmt->bind_param("sssssssss", $idno, $lastname, $firstname, $middlename, $course, $yearlevel, $email, $username, $hashed_password);

                if ($stmt->execute()) {
                    $showModal = true;
                    $success = "Registration successful!";
                } else {
                    $error = "Error during registration: " . $stmt->error;
                }
                $stmt->close();
            } else {
                $error = "Database error: Unable to prepare insert statement.";
            }
        }
    }
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - CCS SIT-IN MONITORING SYSTEM</title>
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
                        'light-bg': '#F1E6EF',
                        'card-bg': '#FFFFFF',
                        'text-primary': '#1F2937',
                        'text-secondary': '#6B7280',
                        'accent-red': '#EF4444',
                        'input-bg': '#F9FAFB', // Light gray for input background
                        'input-border': '#D1D5DB', // Standard border color
                    }
                },
            },
        }
    </script>
    <style>
        body {
            background-color: theme('colors.light-bg');
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
            font-weight: 500; /* Medium weight for labels */
        }
        .animate-fade-in {
            animation: fadeIn 0.3s ease-out;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: scale(0.95); }
            to { opacity: 1; transform: scale(1); }
        }
        .success-modal-icon {
            color: theme('colors.emerald.600'); /* Tailwind's emerald for success */
            background-color: theme('colors.emerald.100');
        }
        .error-message-box {
            background-color: theme('colors.red.50');
            border-left: 4px solid theme('colors.accent-red');
            color: theme('colors.red.700');
        }
    </style>
</head>
<body class="font-sans antialiased">

    <div class="min-h-screen flex flex-col items-center justify-center px-4 sm:px-6 lg:px-8 py-12">
        <div class="w-full max-w-2xl space-y-8">
                <a href="login.php">
                    <img class="mx-auto h-20 w-auto sm:h-24" src="images/CCS.png" alt="CCS Logo">
                </a>
                <h2 class="mt-6 text-center text-3xl font-extrabold text-text-primary">
                    Create your Account
                </h2>
                <p class="mt-2 text-center text-sm text-text-secondary">
                    Join the CCS Sit-In Monitoring System
                </p>
            </div>

            <div class="bg-card-bg p-8 sm:p-10 rounded-xl shadow-2xl">
                <?php if (!empty($error)): ?>
                    <div class="error-message-box p-4 mb-6 rounded-md" role="alert">
                        <div class="flex">
                            <div class="py-1"><i class="fas fa-times-circle mr-3"></i></div>
                            <div>
                                <p class="font-bold">Registration Error</p>
                                <p class="text-sm"><?php echo htmlspecialchars($error); ?></p>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <form method="POST" class="space-y-6">
                    <div>
                        <label for="idno" class="block text-sm form-label">ID Number <span class="text-accent-red">*</span></label>
                        <input type="text" id="idno" name="idno" required value="<?php echo htmlspecialchars($_POST['idno'] ?? ''); ?>"
                               class="mt-1 block w-full px-3 py-2.5 input-field rounded-md shadow-sm sm:text-sm transition-all duration-200">
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-x-6 gap-y-6">
                        <div>
                            <label for="lastname" class="block text-sm form-label">Last Name <span class="text-accent-red">*</span></label>
                            <input type="text" id="lastname" name="lastname" required value="<?php echo htmlspecialchars($_POST['lastname'] ?? ''); ?>"
                                   class="mt-1 block w-full px-3 py-2.5 input-field rounded-md shadow-sm sm:text-sm transition-all duration-200">
                        </div>
                        <div>
                            <label for="firstname" class="block text-sm form-label">First Name <span class="text-accent-red">*</span></label>
                            <input type="text" id="firstname" name="firstname" required value="<?php echo htmlspecialchars($_POST['firstname'] ?? ''); ?>"
                                   class="mt-1 block w-full px-3 py-2.5 input-field rounded-md shadow-sm sm:text-sm transition-all duration-200">
                        </div>
                        <div>
                            <label for="middlename" class="block text-sm form-label">Middle Name</label>
                            <input type="text" id="middlename" name="middlename" value="<?php echo htmlspecialchars($_POST['middlename'] ?? ''); ?>"
                                   class="mt-1 block w-full px-3 py-2.5 input-field rounded-md shadow-sm sm:text-sm transition-all duration-200">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-6">
                        <div>
                            <label for="course" class="block text-sm form-label">Course <span class="text-accent-red">*</span></label>
                            <select id="course" name="course" required
                                    class="mt-1 block w-full px-3 py-2.5 input-field rounded-md shadow-sm sm:text-sm transition-all duration-200">
                                <option value="" <?php echo (!isset($_POST['course']) || $_POST['course'] == '') ? 'selected' : ''; ?> disabled>Select Course</option>
                                <option value="Bachelor of Science in Information Technology" <?php echo (isset($_POST['course']) && $_POST['course'] == 'Bachelor of Science in Information Technology') ? 'selected' : ''; ?>>BS Information Technology</option>
                                <option value="Bachelor of Science in Computer Science" <?php echo (isset($_POST['course']) && $_POST['course'] == 'Bachelor of Science in Computer Science') ? 'selected' : ''; ?>>BS Computer Science</option>
                                <option value="Bachelor of Science in Computer Engineering" <?php echo (isset($_POST['course']) && $_POST['course'] == 'Bachelor of Science in Computer Engineering') ? 'selected' : ''; ?>>BS Computer Engineering</option>
                            </select>
                        </div>
                        <div>
                            <label for="yearlevel" class="block text-sm form-label">Year Level <span class="text-accent-red">*</span></label>
                            <select id="yearlevel" name="yearlevel" required
                                    class="mt-1 block w-full px-3 py-2.5 input-field rounded-md shadow-sm sm:text-sm transition-all duration-200">
                                <option value="" <?php echo (!isset($_POST['yearlevel']) || $_POST['yearlevel'] == '') ? 'selected' : ''; ?> disabled>Select Year Level</option>
                                <option value="1" <?php echo (isset($_POST['yearlevel']) && $_POST['yearlevel'] == '1') ? 'selected' : ''; ?>>1st Year</option>
                                <option value="2" <?php echo (isset($_POST['yearlevel']) && $_POST['yearlevel'] == '2') ? 'selected' : ''; ?>>2nd Year</option>
                                <option value="3" <?php echo (isset($_POST['yearlevel']) && $_POST['yearlevel'] == '3') ? 'selected' : ''; ?>>3rd Year</option>
                                <option value="4" <?php echo (isset($_POST['yearlevel']) && $_POST['yearlevel'] == '4') ? 'selected' : ''; ?>>4th Year</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label for="email" class="block text-sm form-label">Email Address <span class="text-accent-red">*</span></label>
                        <input type="email" id="email" name="email" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                               class="mt-1 block w-full px-3 py-2.5 input-field rounded-md shadow-sm sm:text-sm transition-all duration-200">
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-6">
                        <div>
                            <label for="username" class="block text-sm form-label">Username <span class="text-accent-red">*</span></label>
                            <input type="text" id="username" name="username" required value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>"
                                   class="mt-1 block w-full px-3 py-2.5 input-field rounded-md shadow-sm sm:text-sm transition-all duration-200">
                        </div>
                        <div>
                            <label for="password" class="block text-sm form-label">Password <span class="text-accent-red">*</span></label>
                            <div class="relative">
                                <input type="password" id="password" name="password" required
                                       class="mt-1 block w-full px-3 py-2.5 input-field rounded-md shadow-sm sm:text-sm transition-all duration-200 pr-10">
                                <button type="button" onclick="togglePasswordVisibility('password')" class="absolute inset-y-0 right-0 pr-3 flex items-center text-sm leading-5 text-gray-500 hover:text-custom-purple">
                                    <i id="password-toggle-icon" class="fas fa-eye"></i>
                                </button>
                            </div>
                             <p class="mt-1 text-xs text-text-secondary">Must be at least 8 characters.</p>
                        </div>
                    </div>
                     <div>
                        <label for="confirm_password" class="block text-sm form-label">Confirm Password <span class="text-accent-red">*</span></label>
                        <div class="relative">
                            <input type="password" id="confirm_password" name="confirm_password" required
                                   class="mt-1 block w-full px-3 py-2.5 input-field rounded-md shadow-sm sm:text-sm transition-all duration-200 pr-10">
                            <button type="button" onclick="togglePasswordVisibility('confirm_password')" class="absolute inset-y-0 right-0 pr-3 flex items-center text-sm leading-5 text-gray-500 hover:text-custom-purple">
                                    <i id="confirm_password-toggle-icon" class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>


                    <div class="pt-2">
                        <button type="submit"
                                class="group relative w-full flex justify-center py-3 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-gradient-to-r from-custom-purple to-custom-indigo hover:from-purple-700 hover:to-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-custom-indigo transition-all duration-200 shadow-md">
                            <span class="absolute left-0 inset-y-0 flex items-center pl-3">
                                <i class="fas fa-user-plus text-purple-300 group-hover:text-purple-100"></i>
                            </span>
                            Create Account
                        </button>
                    </div>
                </form>

                <p class="mt-8 text-center text-sm text-text-secondary">
                    Already have an account?
                    <a href="login.php" class="font-medium text-custom-purple hover:text-custom-indigo">
                        Sign in here
                    </a>
                </p>
            </div>
        </div>
    </div>

    <!-- Success Modal -->
    <?php if (isset($showModal) && $showModal && empty($error)): ?>
    <div id="successModalOverlay" class="fixed inset-0 bg-black bg-opacity-60 backdrop-blur-sm flex items-center justify-center z-50 transition-opacity duration-300 ease-in-out">
        <div id="successModal" class="bg-white rounded-xl shadow-2xl p-6 sm:p-8 max-w-md w-full mx-4 transform transition-all duration-300 ease-out scale-95 opacity-0">
            <div class="text-center">
                <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full success-modal-icon mb-4">
                    <i class="fas fa-check text-3xl"></i>
                </div>
                <h3 class="text-xl font-semibold text-text-primary mt-3">Registration Successful!</h3>
                <div class="mt-2">
                    <p class="text-md text-text-secondary">Your account has been created. You can now log in.</p>
                </div>
                <div class="mt-6">
                    <a href="login.php"
                       class="w-full inline-flex justify-center px-6 py-3 text-base font-medium text-white bg-gradient-to-r from-custom-purple to-custom-indigo border border-transparent rounded-md hover:from-purple-700 hover:to-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-custom-indigo transition-all duration-200 shadow-md">
                        Go to Login
                    </a>
                </div>
            </div>
        </div>
    </div>
    <script>
        // Show success modal with animation
        window.addEventListener('load', () => {
            const overlay = document.getElementById('successModalOverlay');
            const modal = document.getElementById('successModal');
            if (overlay && modal) {
                overlay.classList.remove('hidden'); // Should not be needed if not initially hidden
                modal.classList.remove('hidden'); // Should not be needed if not initially hidden
                setTimeout(() => {
                    modal.classList.remove('scale-95', 'opacity-0');
                    modal.classList.add('scale-100', 'opacity-100');
                }, 10);

                // Close modal when clicking outside (on the overlay)
                overlay.addEventListener('click', function(e) {
                    if (e.target === overlay) {
                        window.location.href = 'login.php';
                    }
                });
            }
        });
    </script>
    <?php endif; ?>

    <script>
        function togglePasswordVisibility(inputId) {
            const input = document.getElementById(inputId);
            const icon = document.getElementById(inputId + '-toggle-icon');
            if (input.type === "password") {
                input.type = "text";
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = "password";
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }
    </script>
</body>
</html>