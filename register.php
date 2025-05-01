<?php
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "SysArch_SitIn";

    // Create a connection
    $conn = new mysqli($servername, $username, $password, $dbname);

    // Check connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // Set character encoding
    $conn->set_charset("utf8mb4");

    // Hash the password
    $hashed_password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $remaining_sitin = isset($_POST['remaining_sitin']) ? $_POST['remaining_sitin'] : 30;
    $user_role = isset($_POST['user_role']) ? $_POST['user_role'] : 'student';

    // Check if username already exists
    $stmt = $conn->prepare("SELECT user_id FROM user WHERE username = ?");
    $stmt->bind_param("s", $_POST['username']);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        echo "<script>alert('Error: Username already exists!'); window.location.href='register.php';</script>";
        exit();
    }

    // Insert into users table
    $stmt = $conn->prepare("INSERT INTO user (first_name, middle_name, last_name, email, username, password, user_role) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssss", $_POST['first_name'], $_POST['middle_name'], $_POST['last_name'], $_POST['email'], $_POST['username'], $hashed_password, $user_role);

    if ($stmt->execute()) {
        $user_id = $stmt->insert_id;

        // Check if student ID already exists
        $stmt = $conn->prepare("SELECT student_idno FROM student WHERE student_idno = ?");
        $stmt->bind_param("s", $_POST['student_idno']);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            echo "<script>alert('Error: Student ID already exists!'); window.location.href='register.php';</script>";
            exit();
        }

        // Insert into student table
        $stmt = $conn->prepare("INSERT INTO student (user_id, student_idno, year_level, course, remaining_sitin) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("isisi", $user_id, $_POST['student_idno'], $_POST['year_level'], $_POST['course'], $remaining_sitin);

        if ($stmt->execute()) {
            echo "<script>alert('Registered successfully!'); window.location.href='register.php';</script>";
            exit();
        } else {
            echo "<script>alert('Error registering student details.'); window.location.href='register.php';</script>";
            exit();
        }
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
    <title>Register Student</title>
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
        input, select {
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
            margin-bottom: 5px;
        }
        #login {
            text-decoration: none;
            font-size: 14px;
        }
        #password {
            margin-bottom: 30px;
        }
        #container h2 {
            font-size: 20px;
            font-weight: bold;
            color: #5F3A74;
        }
    </style>
</head>
<body>
    <div id="container">
        <h2>Register Form</h2>
        <form id="registerform" method="POST">
            <input type="text" id="student_idno" name="student_idno" placeholder="Student ID" required> <br>
            <input type="text" id="first_name" name="first_name" placeholder="First Name" required> <br>
            <input type="text" id="middle_name" name="middle_name" placeholder="Middle Name"> <br>
            <input type="text" id="last_name" name="last_name" placeholder="Last Name" required> <br>
            <select name="course" id="course" required>
                <option value="">Select Course</option>
                <option value="BSIT">BSIT</option>
                <option value="BSCS">BSCS</option>
                <option value="BSED">BSED</option>
                <option value="HM">HM</option>
            </select> <br>
            <select name="year_level" id="year_level" required>
                <option value="">Select Year Level</option>
                <option value="1">1</option>
                <option value="2">2</option>
                <option value="3">3</option>
                <option value="4">4</option>
            </select> <br>
            <input type="email" id="email" name="email" placeholder="Email Address" required> <br>
            <input type="text" id="username" name="username" placeholder="Username" required> <br>
            <input type="password" id="password" name="password" placeholder="Password" required>
        
            <button type="submit" id="register">Register</button>
        </form>
        <a href="login.php" id="login">Back to Login</a>
    </div>

    <script>
        document.getElementById('register').addEventListener('click', function(event) {
            event.preventDefault();
            
            let form = document.getElementById('registerform');
            let formData = new FormData(form);

            fetch('register.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.text()) 
            .then(data => {
                alert('Registered successfully!');
                window.location.href = 'register.php'; // Redirect to reload the page
            })
            .catch(error => console.error("Error:", error));
        });
    </script>
</body>
</html>
