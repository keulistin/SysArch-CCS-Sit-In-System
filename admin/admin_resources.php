<?php
$uploadDir = 'uploads/'; // Uploads folder

// Create uploads folder if it doesn't exist
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true); // Create folder with permissions
}

// Handle file upload
if (isset($_POST['upload'])) {
    if (isset($_FILES['file']) && $_FILES['file']['error'] == 0) {
        $filename = basename($_FILES['file']['name']);
        $targetPath = $uploadDir . $filename;

        if (move_uploaded_file($_FILES['file']['tmp_name'], $targetPath)) {
            $message = "File uploaded successfully!";
        } else {
            $message = "Failed to upload file.";
        }
    } else {
        $message = "No file selected or upload error.";
    }
}

// Handle file deletion
if (isset($_GET['delete'])) {
    $fileToDelete = basename($_GET['delete']);
    $filePath = $uploadDir . $fileToDelete;

    if (file_exists($filePath)) {
        unlink($filePath);
        $message = "File deleted successfully!";
    } else {
        $message = "File not found.";
    }
}

// Fetch list of files
$files = array_diff(scandir($uploadDir), array('.', '..'));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>File Manager</title>
    <link rel="stylesheet" href="style.css"> <!-- Optional -->
    <link rel="stylesheet" href="admin_styles.css"> <!-- Optional -->
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
<div class="container">
<h1>File Manager</h1>

<div id="file-manager-container">

    <?php if (isset($message)) : ?>
        <p class="message"><?php echo htmlspecialchars($message); ?></p>
    <?php endif; ?>

    <!-- Upload Form -->
    <form action="" method="post" enctype="multipart/form-data">
        <input type="file" name="file" required>
        <button type="submit" name="upload">Upload</button>
    </form>

    <!-- Files List -->
    <h2>Uploaded Files</h2>
    <table border="1" cellpadding="5">
        <thead>
            <tr>
                <th>Filename</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($files)) : ?>
                <?php foreach ($files as $file) : ?>
                    <tr>
                        <td><?php echo htmlspecialchars($file); ?></td>
                        <td>
                            <a href="<?php echo $uploadDir . urlencode($file); ?>" download>Download</a> |
                            <a href="#" onclick="confirmDelete('<?php echo urlencode($file); ?>')">Delete</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else : ?>
                <tr>
                    <td colspan="2">No files uploaded yet.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
</div>

<script>
function confirmDelete(filename) {
    if (confirm("Are you sure you want to delete " + filename + "?")) {
        window.location.href = "?delete=" + filename;
    }
}
</script>

</body>
</html>
