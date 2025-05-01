<?php
require '../db_connect.php';
// Database connection (edit these values)
$host = "localhost";
$user = "root";
$password = "";
$database = "sysarch_sitin";

$conn = new mysqli($host, $user, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get current server time
date_default_timezone_set('Asia/Manila'); // Example: 'America/New_York'
$currentTime = date("H:i:s");

// Update statuses
$conn->query("UPDATE lab_schedules SET status = CASE 
    WHEN '$currentTime' >= open_time AND '$currentTime' < close_time THEN 'open'
    ELSE 'closed'
END");

// Fetch updated lab schedules
$sql = "SELECT * FROM lab_schedules";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Lab Schedules</title>
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
<div class="container mt-5">
    <div class="row">
        <?php while($row = $result->fetch_assoc()): ?>
            <div class="row_cards col-md-4 mb-4">
                <div class="card <?php echo ($row['status'] == 'open') ? 'border-success' : 'border-danger'; ?>">
                    <div class="card-body">
                        <h5 class="card-title">Lab Room: <?php echo $row['lab_room']; ?></h5>
                        <p class="card-text">
                            <strong>Opens:</strong> <?php echo date("h:i A", strtotime($row['open_time'])); ?><br>
                            <strong>Closes:</strong> <?php echo date("h:i A", strtotime($row['close_time'])); ?><br>
                            <strong>Status:</strong> 
                            <span class="badge <?php echo ($row['status'] == 'open') ? 'bg-success' : 'bg-danger'; ?>">
                                <?php echo ($row['status'] == 'open') ? 'Open' : 'Closed'; ?>
                            </span>
                        </p>
                    </div>
                </div>
            </div>
        <?php endwhile; ?>
    </div>
</div>
</body>
</html>

<?php
$conn->close();
?>
