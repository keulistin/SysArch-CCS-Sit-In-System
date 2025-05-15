<?php
session_start();
include("db.php"); // Include database connection file

$login_error = ""; // Initialize error message variable

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST["user"]);
    $password = trim($_POST["pass"]);

    if (!empty($username) && !empty($password)) {
        // Prepare statement to prevent SQL injection
        $stmt = $conn->prepare("SELECT idno, firstname, lastname, password, role FROM users WHERE username = ?");
        if ($stmt === false) {
            // Handle prepare error, e.g., log it or show a generic error
            $login_error = "An error occurred. Please try again later.";
            // error_log("Prepare failed: (" . $conn->errno . ") " . $conn->error);
        } else {
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $stmt->store_result();
            
            if ($stmt->num_rows > 0) {
                $stmt->bind_result($idno, $firstname, $lastname, $hashed_password, $role);
                $stmt->fetch();

                if (password_verify($password, $hashed_password)) {
                    $_SESSION["idno"] = $idno;
                    $_SESSION["fullname"] = $firstname . " " . $lastname;
                    $_SESSION["role"] = $role;

                    if ($role === "admin") {
                        header("Location: admin_dashboard.php");
                    } else {
                        header("Location: student_dashboard.php"); // Or your student dashboard
                    }
                    exit();
                } else {
                    $login_error = "Invalid username or password.";
                }
            } else {
                $login_error = "User not found.";
            }
            $stmt->close();
        }
    } else {
        $login_error = "Please enter both username and password.";
    }
}
$conn->close(); // Close connection
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - CCS SIT-IN MONITORING SYSTEM</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Font Awesome (for icons) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Poppins', 'sans-serif'],
                    },
                    colors: {
                        'custom-purple': '#6D28D9', // A purple similar to your dashboard
                        'custom-indigo': '#4F46E5', // An indigo similar to your dashboard
                        'light-bg': '#F1E6EF',      // Background from admin dashboard
                        'card-bg': '#FFFFFF',       // White cards like admin dashboard
                        'text-primary': '#1F2937',  // Dark gray for primary text
                        'text-secondary': '#6B7280',// Lighter gray for secondary text
                        'accent-green': '#10B981',  // Green accent for success/info
                        'accent-red': '#EF4444',    // Red accent for errors
                    }
                },
            },
        }
    </script>
    <style>
        body {
            /* Using a subtle gradient or a solid color from your dashboard's palette */
            background-color: theme('colors.light-bg'); /* Reference Tailwind color */
        }
        .input-focus-ring:focus {
            box-shadow: 0 0 0 2px theme('colors.custom-purple'), 0 0 0 4px rgba(109, 40, 217, 0.3);
            border-color: theme('colors.custom-purple');
        }
    </style>
</head>
<body class="font-sans antialiased">

    <div class="min-h-screen flex flex-col items-center justify-center px-4 sm:px-6 lg:px-8 py-12">
        <div class="w-full max-w-md space-y-8">
            <div>
                <img class="mx-auto h-20 w-auto sm:h-24" src="images/CCS.png" alt="CCS Logo">
                <h2 class="mt-6 text-center text-3xl font-extrabold text-text-primary">
                    Sign in to your account
                </h2>
                <p class="mt-2 text-center text-sm text-text-secondary">
                    CCS Sit-In Monitoring System
                </p>
            </div>

            <?php if (!empty($login_error)): ?>
                <div class="bg-red-100 border-l-4 border-accent-red text-accent-red p-4 rounded-md" role="alert">
                    <div class="flex">
                        <div class="py-1"><i class="fas fa-exclamation-triangle mr-3"></i></div>
                        <div>
                            <p class="font-bold">Login Failed</p>
                            <p class="text-sm"><?php echo htmlspecialchars($login_error); ?></p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <form class="mt-8 space-y-6 bg-card-bg p-8 sm:p-10 rounded-xl shadow-2xl" method="POST">
                <input type="hidden" name="remember" value="true">
                <div class="rounded-md shadow-sm -space-y-px">
                    <div>
                        <label for="user" class="sr-only">Username</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-user text-gray-400"></i>
                            </div>
                            <input id="user" name="user" type="text" autocomplete="username" required
                                   class="appearance-none rounded-none relative block w-full px-3 py-3 pl-10 border border-gray-300 placeholder-gray-500 text-text-primary rounded-t-md focus:outline-none input-focus-ring sm:text-sm transition-all duration-200"
                                   placeholder="Username">
                        </div>
                    </div>
                    <div>
                        <label for="pass" class="sr-only">Password</label>
                        <div class="relative">
                             <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-lock text-gray-400"></i>
                            </div>
                            <input id="pass" name="pass" type="password" autocomplete="current-password" required
                                   class="appearance-none rounded-none relative block w-full px-3 py-3 pl-10 border border-gray-300 placeholder-gray-500 text-text-primary rounded-b-md focus:outline-none input-focus-ring sm:text-sm transition-all duration-200"
                                   placeholder="Password">
                            <button type="button" onclick="togglePasswordVisibility('pass')" class="absolute inset-y-0 right-0 pr-3 flex items-center text-sm leading-5 text-gray-500 hover:text-custom-purple">
                                <i id="pass-toggle-icon" class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Remember me and Forgot password (Optional) -->
                <!-- <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <input id="remember-me" name="remember-me" type="checkbox"
                               class="h-4 w-4 text-custom-indigo focus:ring-custom-purple border-gray-300 rounded">
                        <label for="remember-me" class="ml-2 block text-sm text-text-secondary">
                            Remember me
                        </label>
                    </div>
                    <div class="text-sm">
                        <a href="#" class="font-medium text-custom-purple hover:text-custom-indigo">
                            Forgot your password?
                        </a>
                    </div>
                </div> -->

                <div>
                    <button type="submit"
                            class="group relative w-full flex justify-center py-3 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-gradient-to-r from-custom-purple to-custom-indigo hover:from-purple-700 hover:to-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-custom-indigo transition-all duration-200 shadow-md">
                        <span class="absolute left-0 inset-y-0 flex items-center pl-3">
                            <i class="fas fa-sign-in-alt text-purple-300 group-hover:text-purple-100"></i>
                        </span>
                        Sign in
                    </button>
                </div>
            </form>
            <p class="mt-6 text-center text-sm text-text-secondary">
                Don't have an account?
                <a href="register.php" class="font-medium text-custom-purple hover:text-custom-indigo">
                    Register here
                </a>
            </p>
        </div>
    </div>

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