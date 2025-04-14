<?php
require '../db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['searchStudent'])) {
    $query = $_POST['searchStudent'];

    // Debugging: Print the raw input to check if it's correct
    error_log("Search Query: " . $query);

    $stmt = $conn->prepare("SELECT s.student_idno, 
                                    CONCAT(u.first_name, ' ', u.middle_name, ' ', u.last_name) AS full_name, 
                                    u.username, 
                                    u.email,
                                    s.course, 
                                    s.year_level, 
                                    s.remaining_sitin 
                            FROM student s
                            JOIN user u ON s.user_id = u.user_id
                            WHERE s.student_idno = ? 
                               OR u.first_name LIKE ? 
                               OR u.last_name LIKE ?");

    // Use wildcard % for LIKE queries
    $searchTerm = "%$query%";
    $stmt->bind_param("sss", $query, $searchTerm, $searchTerm);
    $stmt->execute();
    $result = $stmt->get_result();

    // Debugging: Print number of rows found
    error_log("Number of rows found: " . $result->num_rows);

    $students = [];
    while ($row = $result->fetch_assoc()) {
        $students[] = $row;
    }

    echo json_encode(["success" => true, "data" => $students]);
    $stmt->close();
    exit();
}

// Fetch student list (Default Table Load)
$sql = "SELECT s.student_idno, 
               CONCAT(u.first_name, ' ', u.middle_name, ' ', u.last_name) AS full_name, 
               u.username, 
               u.email,
               s.course, 
               s.year_level, 
               s.remaining_sitin 
        FROM student s
        JOIN user u ON s.user_id = u.user_id
        ORDER BY s.student_idno DESC";

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

    <div class="content">
        <div class="search-student-container">
            <form id="adminStudentListSearchForm">
                <div class="admin-student-list-search-box">
                    <img src="../images/search-icon.png" alt="Search" class="search-icon">
                    <input type="text" id="adminStudentListSearchInput" placeholder="Search student" name="searchStudent">
                </div>
                <button type="button" id="cancelSearchBtn" class="cancel-search-btn">✖</button> <!-- Cancel Button -->
                <button type="button" id="resetAllBtn" class="reset-all-btn">Reset All</button>  <!-- Reset Remaining Sitins Button -->
            </form>
        </div>

        <!-- Table List -->
        <div class="student-list-container" id="studentList">
            <div class="student-list-table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Student ID</th>
                            <th>Name</th>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Course</th>
                            <th>Year Level</th>
                            <th>Rem. Sit-In</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody id="studentTableBody">
                        <?php
                        if ($result->num_rows > 0) {
                            while ($row = $result->fetch_assoc()) {
                                echo "<tr>";
                                echo "<td>" . htmlspecialchars($row['student_idno']) . "</td>";
                                echo "<td>" . htmlspecialchars($row['full_name']) . "</td>";
                                echo "<td>" . htmlspecialchars($row['username']) . "</td>";
                                echo "<td><a href='mailto:" . htmlspecialchars($row['email']) . "'>" . htmlspecialchars($row['email']) . "</a></td>";
                                echo "<td>" . htmlspecialchars($row['course']) . "</td>";
                                echo "<td>" . htmlspecialchars($row['year_level']) . "</td>";
                                echo "<td>" . htmlspecialchars($row['remaining_sitin']) . "</td>";
                                echo '<td>
                                        <button class="reset-btn" data-id="' . htmlspecialchars($row['student_idno']) . '" style="background: none; border: none; cursor: pointer;">
                                            <img src="../images/reset.png" alt="Exit" width="60" height="24">
                                        </button>
                                    </td>';
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='8'>No students found.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>
