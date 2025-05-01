<?php
session_start();
session_regenerate_id(true); // Prevent session fixation attacks

// Database Connection
$conn = new mysqli('localhost', 'root', '', 'SysArch_SitIn');
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

// If login request is made via AJAX (API)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['username']) && isset($_POST['password'])) {
    header("Content-Type: application/json"); // Set response type to JSON

    $username = trim($_POST['username']);
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT user_id, password, user_role FROM user WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $user_id = $row['user_id'];
        $hashed_password = $row['password'];
        $user_role = $row['user_role'];

        if (password_verify($password, $hashed_password)) {
            $_SESSION['user_id'] = $user_id;
            $_SESSION['username'] = $username;
            $_SESSION['user_role'] = $user_role;

            echo json_encode(["success" => true, "role" => $user_role, "user_id" => $user_id]);
            exit();
        } else {
            echo json_encode(["success" => false, "message" => "Incorrect password."]);
            exit();
        }
    } else {
        echo json_encode(["success" => false, "message" => "Username not found."]);
        exit();
    }

    $stmt->close();
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Form</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <script defer src="../script.js"></script>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            height: 100vh;
            background-image: url('images/background.png');
            background-size: cover;
            background-position: center;
        }
        #container {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            text-align: center;
            width: 400px;
        }
        input {
            width: 90%;
            padding: 10px;
            margin: 5px 0;
            border: 1px solid #ccc;
            border-radius: 5px;
        }

        button {
            background-color: #5F3A74;
            color: white;
            border: none;
            padding: 10px 20px;
            font-size: 14px;
            border-radius: 20px;
            cursor: pointer;
            width: 60%;
        }
        #error-message {
            color: red;
            font-size: 14px;
        }
        
        #register {
            font-size: 13px;
            text-decoration: none;
            color: grey;
            margin-top: 20px;
        }

        .button-login-register{
            display: flex;
            width: 100%;
        }

        #password {
            margin-bottom: 30px;
        }

        #container h2 {
            font-size: 20px;
            font-weight:bold;
            color: #5F3A74;
        }
    </style>
</head>
<body>
    <div id="container">
        <img src="images/CCS_LOGO.png" alt="Logo" width="100px">
        <h2>CSS Sit-In Monitoring</h2>
        <p id="error-message"></p>
        <form id="loginForm">
            <input type="text" id="username" name="username" placeholder="Username" required>
            <input type="password" id="password" name="password" placeholder="Password" required>
            <button type="submit">Login</button>
        </form>
        <a id="register" href="register.php">Register</a>

    </div>

    <script>
        document.getElementById("loginForm").addEventListener("submit", function (event) {
            event.preventDefault();

            let formData = new FormData(this);

            fetch("login.php", {
                method: "POST",
                body: formData
            })
            .then(response => response.json()) // Expect JSON response
            .then(data => {
                if (data.success) {
                    window.loggedInUser = {
                            user_id: data.user_id
                        };
                        localStorage.setItem("user_id", data.user_id);
                        // Redirect based on user role
                        alert("User ID: " + data.user_id);
                    window.location.href = data.role === "admin" ? "admin/admin_dashboard.php" : "student/student_dashboard.php";
                } else {
                    document.getElementById("error-message").textContent = data.message;
                }
            })
            .catch(error => {
                document.getElementById("error-message").textContent = "Error: Unable to connect.";
                console.error("Login Error:", error);
            });
        });
    </script>
</body>
</html>
