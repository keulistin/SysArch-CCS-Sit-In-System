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
$sql_history = "SELECT h.history_id,
                        s.student_idno, 
                       CONCAT(u.first_name, ' ', u.middle_name, ' ', u.last_name) AS full_name, 
                       s.course, 
                       s.year_level, 
                       u.email, 
                       h.sitin_purpose, 
                       h.lab_room, 
                       h.start_time, 
                       h.end_time
                FROM sitin_history h
                JOIN student s ON h.student_idno = s.student_idno
                JOIN user u ON s.user_id = u.user_id
                ORDER BY h.end_time DESC";

$result_history = $conn->query($sql_history);



// Fetch current sit-in records
$sql = "SELECT c.sitin_id,  -- Include sitin_id here
               s.student_idno, 
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
    <script src="https://cdnjs.cloudflare.com/ajax/libs/FileSaver.js/2.0.5/FileSaver.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf-lib/1.17.1/pdf-lib.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/FileSaver.js/2.0.5/FileSaver.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/fontfaceobserver/2.3.0/fontfaceobserver.standalone.js"></script>
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
            <div>
            <button class="tab active" data-target="currentSitin">Current</button>
            <button class="tab" data-target="historySitin">History</button>
            </div>
                    <!-- GENERATE REPORT  BUTTON -->
                    <div class="report-actions">
                        <button class="report-btn">PDF</button>
                        <button class="report-btn">Excel</button>
                        <button class="report-btn">CSV</button>
                    </div>
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
                                <button class="checkout-btn" 
                                        data-id="' . htmlspecialchars($row['student_idno']) . '" 
                                        data-sitin-id="' . htmlspecialchars($row['sitin_id']) . '" 
                                        style="background: none; border: none; cursor: pointer;">
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
                            <select id="sitin_purpose">
                                <option value="" disabled selected>Select Purpose</option>
                                <option>C# Programming</option>
                                <option>C Programming</option>
                                <option>Python Programming</option>
                                <option>Java Programming</option>
                                <option>ASP.Net Programming</option>
                                <option>Php Programming</option>
                            </select>
                            <select id="lab_room">
                                <option value="" disabled selected>Select Laboratory</option>
                                <option>524</option>
                                <option>526</option>
                                <option>528</option>
                                <option>530</option>
                                <option>542</option>
                                <option>544</option>
                                <option>517</option>
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

    <!-- Report Filter Modal -->
<div id="reportFilterModal" class="modal" style="display:none;">
    <div class="modal-content">
        <span class="close-modal" onclick="closeReportModal()">&times;</span>
        <h2>Generate Report</h2>
        <form id="reportFilterForm">
            <div class="form-group">
                <label for="reportLabFilter">Filter by Laboratory:</label>
                <select id="reportLabFilter" name="lab_room">
                    <option value="all">All Laboratories</option>
                    <option value="524">524</option>
                    <option value="526">526</option>
                    <option value="528">528</option>
                    <option value="530">530</option>
                    <option value="542">542</option>
                    <option value="544">544</option>
                    <option value="517">517</option>
                </select>
            </div>
            <div class="form-group">
                <label for="reportPurposeFilter">Filter by Purpose:</label>
                <select id="reportPurposeFilter" name="sitin_purpose">
                    <option value="all">All Purposes</option>
                    <option>C# Programming</option>
                    <option>C Programming</option>
                    <option>Python Programming</option>
                    <option>Java Programming</option>
                    <option>ASP.Net Programming</option>
                    <option>Php Programming</option>
                </select>
            </div>
            <div class="form-group">
                <label for="reportTimeFilter">Filter by Time Period:</label>
                <select id="reportTimeFilter" name="time_period">
                    <option value="all">All Time</option>
                    <option value="today">Today</option>
                    <option value="week">This Week</option>
                    <option value="month">This Month</option>
                    <option value="custom">Custom Range</option>
                </select>
                <div id="customDateRange" style="display:none; margin-top:10px;">
                    <input type="date" id="reportStartDate" name="start_date">
                    <span>to</span>
                    <input type="date" id="reportEndDate" name="end_date">
                </div>
            </div>
            <input type="hidden" id="reportFormat" name="format">
            <button type="submit" class="generate-btn">Generate Report</button>
        </form>
    </div>
</div>

</body>
</html>
