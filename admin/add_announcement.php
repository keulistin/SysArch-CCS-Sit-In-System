<?php
include '../db_connect.php'; // Ensure database connection file is included

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $title = isset($_POST['title']) ? trim($_POST['title']) : "";
    $description = isset($_POST['description']) ? trim($_POST['description']) : "";

    if (!empty($title) && !empty($description)) {
        $stmt = $conn->prepare("INSERT INTO announcement (ann_title, ann_description) VALUES (?, ?)");
        $stmt->bind_param("ss", $title, $description);

        if ($stmt->execute()) {
            echo json_encode(["success" => true, "message" => "Announcement added successfully!"]);
        } else {
            echo json_encode(["success" => false, "message" => "Error adding announcement."]);
        }
        $stmt->close();
    } else {
        echo json_encode(["success" => false, "message" => "Title and description cannot be empty."]);
    }

    $conn->close();
} else {
    echo json_encode(["success" => false, "message" => "Invalid request."]);
}
?>



