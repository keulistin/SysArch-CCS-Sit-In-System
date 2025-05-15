<?php
session_start();
include 'db.php';

// Verify it's an AJAX request
if (empty($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) != 'xmlhttprequest') {
    http_response_code(403);
    echo '<div class="col-span-4 text-center text-red-400">Access denied</div>';
    exit();
}

// Verify user is logged in (either admin or student)
if (!isset($_SESSION['idno'])) {
    http_response_code(401);
    echo '<div class="col-span-4 text-center text-red-400">Please login first</div>';
    exit();
}

// Validate lab parameter
if (empty($_GET['lab'])) {
    http_response_code(400);
    echo '<div class="col-span-4 text-center text-red-400">No lab specified</div>';
    exit();
}

$allowed_labs = ['Lab 517', 'Lab 524', 'Lab 526', 'Lab 528', 'Lab 530', 'Lab 542', 'Lab 544'];
$lab = trim($_GET['lab']);

if (!in_array($lab, $allowed_labs)) {
    http_response_code(400);
    echo '<div class="col-span-4 text-center text-red-400">Invalid lab specified</div>';
    exit();
}

try {
    $stmt = $conn->prepare("SELECT pc_number, status FROM lab_pcs WHERE lab_name = ? ORDER BY pc_number");
    $stmt->bind_param("s", $lab);
    $stmt->execute();
    $result = $stmt->get_result();

    $html = '';
    while ($row = $result->fetch_assoc()) {
        $statusClass = $row['status'] === 'Available' ? 'status-available' : 
                      ($row['status'] === 'Used' ? 'status-used' : 'status-maintenance');
        
        $html .= '<div class="pc-card p-2 rounded-md border text-center cursor-pointer ' . $statusClass . '" 
                 data-pc-number="' . htmlspecialchars($row['pc_number']) . '" 
                 onclick="selectPc(' . (int)$row['pc_number'] . ')">
                    PC ' . htmlspecialchars($row['pc_number']) . '<br>
                    <span class="text-xs ' . 
                    ($row['status'] === 'Available' ? 'text-green-400' : 
                     ($row['status'] === 'Used' ? 'text-red-400' : 'text-yellow-400')) . '">' . 
                    htmlspecialchars($row['status']) . '</span>
                 </div>';
    }

    echo $html ?: '<div class="col-span-4 text-center text-slate-400">No PCs found for this lab</div>';
    
} catch (Exception $e) {
    error_log("PC loading error: " . $e->getMessage());
    http_response_code(500);
    echo '<div class="col-span-4 text-center text-red-400">Database error occurred</div>';
}
?>