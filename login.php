<?php
session_start();
include("db.php"); // Include database connection file

$login_error = ""; // Initialize error message variable

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST["user"]);
    $password = trim($_POST["pass"]);

    if (!empty($username) && !empty($password)) {
        $stmt = $conn->prepare("SELECT idno, firstname, lastname, password, role FROM users WHERE username = ?");
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
                    header("Location: student_dashboard.php");
                }
                exit();
            } else {
                $login_error = "Invalid username or password.";
            }
        } else {
            $login_error = "User not found.";
        }
        $stmt->close();
    } else {
        $login_error = "Please enter both username and password.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CSS SIT-IN MONITORING SYSTEM</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gradient-to-br from-blue-500 to-indigo-600 bg-cover bg-center bg-no-repeat h-screen flex items-center justify-center" style="background-image: url('login.png');">

    <!-- Main Content Box -->
    <div class="bg-gradient-to-r from-gray-500 via-transparent to-gray-500 p-8 rounded-xl shadow-lg w-full max-w-md flex flex-col items-center justify-center">
        <!-- Logo and Heading -->
        <img src="images/CCS.png" alt="CCS Logo" class="w-24 h-24 mb-4">
        <h2 class="text-3xl font-semibold text-white mb-6 text-center">CSS SIT-IN MONITORING SYSTEM</h2>

        <!-- Error Message (if any) -->
        <?php if (!empty($login_error)): ?>
            <p class="text-red-500 text-sm mb-4"><?php echo htmlspecialchars($login_error); ?></p>
        <?php endif; ?>

        <!-- Login Form -->
        <form method="POST" class="w-full space-y-6">
            <!-- Username Field -->
            <div class="flex flex-col">
                <label for="user" class="text-white font-medium">Username:</label>
                <input type="text" id="user" name="user" required class="mt-2 p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            <!-- Password Field -->
            <div class="flex flex-col">
                <label for="pass" class="text-white font-medium">Password:</label>
                <input type="password" id="pass" name="pass" required class="mt-2 p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            <!-- Button Group -->
            <div class="flex justify-between">
                <button type="submit" class="w-full py-3 bg-blue-500 text-white rounded-lg font-semibold hover:bg-blue-600 transition-colors">
                    Login
                </button>
                <button type="button" onclick="window.location.href='register.php'" class="w-full py-3 bg-gray-200 text-gray-800 rounded-lg font-semibold hover:bg-gray-300 transition-colors ml-4">
                    Register
                </button>
            </div>
        </form>
    </div>

    <!-- JavaScript for Show Password -->
    <script>
        function togglePassword(inputId) {
            var input = document.getElementById(inputId);
            input.type = input.type === "password" ? "text" : "password";
        }
    </script>
</body>
</html>