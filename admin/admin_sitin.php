<?php
require '../db_connect.php';  // Ensure this connects to your database

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['searchStudent'])) {
    $query = $_POST['searchStudent'];

    // Debugging: Print the raw input to check if it's correct
    error_log("Search Query: " . $query);

    $stmt = $conn->prepare("SELECT s.student_idno, 
                                    CONCAT(u.first_name, ' ', u.middle_name, ' ', u.last_name) AS full_name, 
                                    s.course, s.year_level, u.email, s.remaining_sitin 
                            FROM student s
                            JOIN user u ON s.user_id = u.user_id
                            WHERE s.student_idno = ? OR u.first_name LIKE ? OR u.last_name LIKE ?");

    $searchTerm = "$query";
    $stmt->bind_param("sss", $query, $searchTerm, $searchTerm);
    $stmt->execute();
    $result = $stmt->get_result();

    // Debugging: Print number of rows found
    error_log("Number of rows found: " . $result->num_rows);

    if ($result->num_rows > 0) {
        $student = $result->fetch_assoc();
        echo json_encode(["success" => true, "data" => $student]);
    } else {
        echo json_encode(["success" => false, "message" => "Student not found."]);
    }

    $stmt->close();
    exit();
}

// Fetch sit-in history records
$sql_history = "SELECT s.student_idno, 
                       CONCAT(u.first_name, ' ', u.middle_name, ' ', u.last_name) AS full_name, 
                       s.course, 
                       s.year_level, 
                       u.email, 
                       h.sitin_purpose, 
                       h.lab_room, 
                       h.start_time, 
                       h.end_time, 
                       h.duration 
                FROM sitin_history h
                JOIN student s ON h.student_idno = s.student_idno
                JOIN user u ON s.user_id = u.user_id
                ORDER BY h.end_time DESC";

$result_history = $conn->query($sql_history);



// Fetch current sit-in records
$sql = "SELECT s.student_idno, 
               CONCAT(u.first_name, ' ', u.middle_name, ' ', u.last_name) AS full_name, 
               s.course, 
               s.year_level, 
               u.email, 
               s.remaining_sitin, 
               c.sitin_purpose, 
               c.lab_room, 
               c.start_time 
        FROM current_sitin c
        JOIN student s ON c.student_idno = s.student_idno
        JOIN user u ON s.user_id = u.user_id
        ORDER BY c.start_time DESC";

$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Sit-In</title>
    <link rel="stylesheet" href="admin_styles.css">
    <script defer src="../script.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">

</head>
<body>
    <!-- Include Navbar -->
    <?php include 'admin_navbar.php'; ?>

    <div class="content"> <!-- content -->

        <div class="admin-search-container">
            <form id="adminSitinSearchForm">
                <div class="admin-sitin-search-box">
                    <img src="../images/search-icon.png" alt="Search" class="search-icon">
                    <input type="text" id="adminSitinSearchInput" placeholder="Search student" name="searchStudent">
                </div>
            </form>
        </div>


        <!-- Tabs -->
        <div class="tabs">
            <button class="tab active" data-target="currentSitin">Current</button>
            <button class="tab" data-target="historySitin">History</button>
        </div>

        <!--CURRENT SITIN-->
        <div class="admin-sitin-container" id="currentSitin">
        <!-- Table -->
            <div class="sitin-table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Student ID</th>
                            <th>Name</th>
                            <th>Purpose</th>
                            <th>Lab</th>
                            <th>Time-In</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        if ($result->num_rows > 0) {
                            while ($row = $result->fetch_assoc()) {
                                echo "<tr>";
                                echo "<td>" . htmlspecialchars($row['student_idno']) . "</td>";
                                echo "<td>" . htmlspecialchars($row['full_name']) . "</td>";
                                echo "<td>" . htmlspecialchars($row['sitin_purpose']) . "</td>";
                                echo "<td>" . htmlspecialchars($row['lab_room']) . "</td>";
                                echo "<td>" . date("h:i A", strtotime($row['start_time'])) . "</td>"; 
                                echo '<td>
                                        <button class="checkout-btn" data-id="' . htmlspecialchars($row['student_idno']) .'" style="background: none; border: none; cursor: pointer;">
                                            <img src="../images/checkout.png" alt="Exit" width="50" height="24">
                                        </button>
                                    </td>';
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='6'>No sit-in students found.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>


        
<!-- Sit-In History Table -->
        <div class="admin-sitin-container" id="historySitin" style="display: none;">
            <div class="sitin-table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Student ID</th>
                            <th>Name</th>
                            <th>Purpose</th>
                            <th>Lab</th>
                            <th>Time-In</th>
                            <th>Time-Out</th>
                            <th>Duration (mins)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        if ($result_history->num_rows > 0) {
                            while ($row = $result_history->fetch_assoc()) {
                                echo "<tr>";
                                echo "<td>" . htmlspecialchars($row['student_idno']) . "</td>";
                                echo "<td>" . htmlspecialchars($row['full_name']) . "</td>";
                                echo "<td>" . htmlspecialchars($row['sitin_purpose']) . "</td>";
                                echo "<td>" . htmlspecialchars($row['lab_room']) . "</td>";
                                echo "<td>" . date("h:i A", strtotime($row['start_time'])) . "</td>";
                                echo "<td>" . date("h:i A", strtotime($row['end_time'])) . "</td>";
                                echo "<td>" . htmlspecialchars($row['duration']) . "</td>";
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='7'>No sit-in history found.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>


        <!-- Student Sit-In Overlay -->
        <div class="admin-sitin-overlay" id="adminSitinOverlay">
            <div class="admin-sitin-overlay-content">
                <h2>Sit-In Student</h2>
                <img src="../images/kuromi.webp" alt="Student Profile" class="student-profile-img">

                <div id="admin-sitin-student-info">
                    <div class="student-details">
                        <div class="student-labels">
                            <p>Student ID:</p>
                            <p>Name:</p>
                            <p>Course:</p>
                            <p>Year Level:</p>
                            <p>Email:</p>
                            <p>Remaining Session:</p>
                        </div>
                        <div class="student-values">
                            <p id="student-id">22613871</p>
                            <p id="student-name">Christine Anne A. Alesna</p>
                            <p id="student-course">BSIT</p>
                            <p id="student-year">2</p>
                            <p><a id="student-email" href="mailto:christine@gmail.com">christine@gmail.com</a></p>
                            <p id="student-session">30</p>
                        </div>
                    </div>
                    <div class="sitin-form-group">
                        <div class="sitin-labels">
                            <label for="purpose">Purpose</label>
                            <label for="lab">Laboratory</label>
                        </div>
                        <div class="sitin-select">
                            <select id="purpose">
                                <option value="" disabled selected>Select Purpose</option>
                                <option>C# Programming</option>
                                <option>Java Programming</option>
                                <option>ASP.Net Programming</option>
                                <option>Php Programming</option>
                            </select>
                            <select id="lab">
                                <option value="" disabled selected>Select Laboratory</option>
                                <option>512</option>
                                <option>602</option>
                                <option>533</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="overlay-buttons">
                    <button class="cancel-btn" onclick="adminSitinCloseOverlay()">Cancel</button>
                    <button class="checkin-btn">Check-In</button>
                </div>
            </div>
        </div>


    </div> <!-- content -->

</body>
</html>
