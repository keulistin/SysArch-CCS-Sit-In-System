<?php
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "SysArch_SitIn";

    // Create a connection
    $conn = new mysqli($servername, $username, $password);

    // Check connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // Create database if it doesn't exist
    $sql = "CREATE DATABASE IF NOT EXISTS $dbname";
    if ($conn->query($sql) === TRUE) {
        $conn->select_db($dbname);
    } else {
        die("Error creating database: " . $conn->error);
    }

    // Set character encoding
    $conn->set_charset("utf8mb4");

    // Create users table
    $sql = "CREATE TABLE IF NOT EXISTS user (
        user_id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        first_name VARCHAR(30) NOT NULL,
        last_name VARCHAR(30) NOT NULL,
        middle_name VARCHAR(30),
        email VARCHAR(50) NOT NULL UNIQUE,
        username VARCHAR(30) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        user_role VARCHAR(10) NOT NULL DEFAULT 'student',
        reg_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    $conn->query($sql);

    // Create students table
    $sql = "CREATE TABLE IF NOT EXISTS student (
        student_idno INT(10) PRIMARY KEY,
        user_id INT(11) NOT NULL,
        year_level INT(1) NOT NULL,
        course VARCHAR(30) NOT NULL,
        remaining_sitin INT(11) DEFAULT 30,
        FOREIGN KEY (user_id) REFERENCES user(user_id) ON DELETE CASCADE
    )";
    $conn->query($sql);
    $conn->select_db($dbname); // Ensure the database is selected

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
        echo("Error: Username already exists!");
    } else {
        // Insert into users table
        $stmt = $conn->prepare("INSERT INTO user (first_name, middle_name, last_name, email, username, password, user_role) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssss", $_POST['first_name'], $_POST['middle_name'], $_POST['last_name'], $_POST['email'], $_POST['username'], $hashed_password, $user_role);

        if ($stmt->execute()) {
            $user_id = $stmt->insert_id; // Get the new user ID

            // Check if student ID already exists
            $stmt = $conn->prepare("SELECT student_idno FROM student WHERE student_idno = ?");
            $stmt->bind_param("s", $_POST['student_idno']);
            $stmt->execute();
            $stmt->store_result();

            if ($stmt->num_rows > 0) {
                die("Error: Student ID already exists!");
            }

            // Now insert into student table
            $stmt = $conn->prepare("INSERT INTO student (user_id, student_idno, year_level, course, remaining_sitin) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("isisi", $user_id, $_POST['student_idno'], $_POST['year_level'], $_POST['course'], $remaining_sitin);

            if ($stmt->execute()) {
                echo json_encode(["success" => true, "message" => "Registered successfully!"]);
            } else {
                die("Error registering student details: " . $stmt->error);
            }
      
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

        input {
            width: 90%;
            padding: 10px;
            margin: 5px 0;
            border: 1px solid #ccc;
            border-radius: 5px;
        }

        select{
            width: 96%;
            padding: 10px;
            margin: 5px 0;
            border: 1px solid #ccc;
            border-radius: 5px;
s        }

        option {
            color: black;
            margin-right:20px;
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
            margin-bottom:5px;
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
            font-weight:bold;
            color: #5F3A74;
        }
    </style>
</head>
<body>
    <div id="container">
    <h2>Register Form</h2>
    <form id="registerform" method="POST">
            <input type="text" id="student_idno" name="student_idno" placeholder="Student ID"> <br>
            <input type="text" id="first_name" name="first_name" placeholder="First Name"> <br>
            <input type="text" id="middle_name" name="middle_name" placeholder="Middle Name"> <br>
            <input type="text" id="last_name" name="last_name" placeholder="Last Name"> <br>
            <select name="course" id="course" placeholder="Course">
                <option value="BSIT">BSIT</option>
                <option value="BSCS">BSCS</option>
                <option value="BSED">BSED</option>
                <option value="HM">HM</option>
            </select> <br>
            <select name="year_level" id="year_level" placeholder="Year Level">
                <option value="1">1</option>
                <option value="2">2</option>
                <option value="3">3</option>
                <option value="4">4</option>
            </select> <br>
            <input type="email" id="email" name="email" placeholder="Email Address"> <br>
            <input type="text" id="username" name="username" placeholder="Username"> <br>
            <input type="password" id="password" name="password" placeholder="Password">
        
            <button id="register">Register</button>
        </form>
            <a href="login.php" id="login">Back to Login</a>
        
    </div>

<script>
        document.getElementById('register').addEventListener('click', function(event) {
            event.preventDefault();
            
            let inputs = document.querySelectorAll('input');
            let missingInputs = false;
            
            inputs.forEach(function(input) {
                if (input.value.trim() === '') {
                    missingInputs = true;
                } else {
                    input.style.borderColor = '';
                }
            });
            
            if (missingInputs) {
                alert('Please fill in all fields');
            } else {
                document.querySelector('form').submit();
            }
        });

</script>
</body>
</html>

