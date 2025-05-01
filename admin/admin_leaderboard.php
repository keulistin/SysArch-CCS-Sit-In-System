<?php
require '../db_connect.php'; // Include your mysqli connection

// Query to join users and students tables
$sql = "
    SELECT u.first_name, u.last_name, s.total_points
    FROM student s
    INNER JOIN user u ON s.user_id = u.user_id
    ORDER BY s.total_points DESC
";

$result = $conn->query($sql);

if (!$result) {
    die("Query failed: " . $conn->error);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Leaderboard</title>
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

<?php include 'admin_navbar.php'; ?>

<div class="content">
    <h1>Student Leaderboard</h1>

    <div class="leaderboard-table-container">
        <table border="0" cellpadding="5" border-radius="10px">
            <thead>
                <tr>
                    <th>Rank</th>
                    <th>Name</th>
                    <th>Total Points</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $rank = 1;
                while ($student = $result->fetch_assoc()) {
                    $fullName = htmlspecialchars($student['first_name'] . ' ' . $student['last_name']);
                    $points = htmlspecialchars($student['total_points']);
                    echo "<tr class='rank-{$rank}'>";
                    echo "<td>{$rank}</td>";
                    echo "<td>{$fullName}</td>";
                    echo "<td>{$points}</td>";
                    echo "</tr>";
                    $rank++;
                }
                ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>
