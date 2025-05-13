<?php
session_start();
include 'db.php';

// Ensure only admins can access
if (!isset($_SESSION["idno"]) || $_SESSION["role"] !== "admin") {
    header("Location: login.php");
    exit();
}

$idno = $_SESSION["idno"];
$stmt = $conn->prepare("SELECT firstname, lastname, profile_picture FROM users WHERE idno = ?");
$stmt->bind_param("s", $idno);
$stmt->execute();
$stmt->bind_result($firstname, $lastname, $profile_picture);
$stmt->fetch();
$stmt->close();

// Set default profile picture if none exists
if (empty($profile_picture)) {
    $profile_picture = "default_avatar.png";
}

// Handle file upload
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES["resource_file"])) {
    $title = $_POST['title'] ?? '';
    $description = $_POST['description'] ?? '';
    $available_to = $_POST['available_to'] ?? 'all';
    $file = $_FILES['resource_file'];

    if (empty($title) || empty($file['name'])) {
        $_SESSION['upload_error'] = "Title and file are required.";
    } else {
        $upload_dir = 'resources/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        $file_name = basename($file['name']);
        $file_path = $upload_dir . uniqid() . '_' . $file_name;
        $file_size = $file['size'];
        $file_type = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));

        $allowed_types = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'jpg', 'jpeg', 'png', 'gif', 'zip', 'txt'];

        if (!in_array($file_type, $allowed_types)) {
            $_SESSION['upload_error'] = "File type not allowed. Allowed types: " . implode(', ', $allowed_types);
        } elseif ($file_size > 50 * 1024 * 1024) {
            $_SESSION['upload_error'] = "File size exceeds 50MB limit.";
        } elseif (move_uploaded_file($file['tmp_name'], $file_path)) {
            $stmt = $conn->prepare("INSERT INTO resources (title, description, file_name, file_path, file_size, file_type, available_to, uploaded_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssisss", $title, $description, $file_name, $file_path, $file_size, $file_type, $available_to, $idno);

            if ($stmt->execute()) {
                $_SESSION['upload_success'] = "Resource uploaded successfully!";
            } else {
                $_SESSION['upload_error'] = "Database error: " . $conn->error;
                unlink($file_path);
            }

            $stmt->close();
        } else {
            $_SESSION['upload_error'] = "Error uploading file.";
        }
    }

    // Redirect to prevent form resubmission
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// Fetch all resources
$resources_query = "SELECT * FROM resources ORDER BY upload_date DESC";
$resources_result = mysqli_query($conn, $resources_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Resources - Admin</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        body {
            background-color: #F1E6EF;
        }
        .main-content-cont{
            padding: 8rem 15rem 5rem 15rem;
        }
        .sidebar-scroll {
            scrollbar-width: thin;
            scrollbar-color: #4b5563 #1e293b;
        }
        .sidebar-scroll::-webkit-scrollbar {
            width: 6px;
        }
        .sidebar-scroll::-webkit-scrollbar-track {
            background: #1e293b;
        }
        .sidebar-scroll::-webkit-scrollbar-thumb {
            background-color: #4b5563;
            border-radius: 3px;
        }
        .file-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2);
        }
        .file-icon {
            transition: all 0.3s ease;
        }
        .file-card:hover .file-icon {
            transform: scale(1.1);
        }
        .upload-dropzone {
            transition: all 0.3s ease;
        }
        .upload-dropzone.dragover {
            border-color: #3b82f6;
            background-color: rgba(59, 130, 246, 0.1);
        }
    </style>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Poppins', 'sans-serif'],
                    },
                },
            },
        }
    </script>
</head>
<body class="font-sans text-black">
<!-- Top Navigation Bar -->
<div class="fixed top-0 left-0 right-0 bg-white shadow-md z-50">
    <div class="flex items-center justify-between px-6 py-3">
        <!-- CCS Logo -->
        <div class="flex items-center">
            <img src="images/CCS.png" alt="CCS Logo" class="h-14">
        </div>
        
        <!-- Main Navigation Links -->
        <nav class="hidden md:flex items-center space-x-2">
            <a href="admin_dashboard.php" class="px-4 py-2 text-gray-700 font-medium hover:bg-gray-100 rounded-md transition-all duration-200">
                Dashboard
            </a>
            
            <!-- Records Dropdown -->
            <div class="relative">
                <button onclick="toggleDropdown('recordsDropdown')" class="px-4 py-2 text-gray-700 font-medium hover:bg-gray-100 rounded-md transition-all duration-200 flex items-center">
                    Records <i class="fas fa-chevron-down ml-2 text-xs"></i>
                </button>
                <div id="recordsDropdown" class="nav-dropdown absolute left-0 mt-2 w-48 bg-white rounded-md shadow-lg py-1 z-10 hidden">
                    <a href="todays_sitins.php" class="block px-4 py-2 text-gray-700 hover:bg-gray-100">Current Sit-ins</a>
                    <a href="sit_in_records.php" class="block px-4 py-2 text-gray-700 hover:bg-gray-100">Sit-in Reports</a>
                    <a href="feedback_records.php" class="block px-4 py-2 text-gray-700 hover:bg-gray-100">Feedback Reports</a>
                </div>
            </div>

            
            <!-- Management Dropdown -->
            <div class="relative">
                <button onclick="toggleDropdown('managementDropdown')" class="px-4 py-2 text-gray-700 font-medium hover:bg-gray-100 rounded-md transition-all duration-200">
                    Management <i class="fas fa-chevron-down ml-2 text-xs"></i>
                </button>
                <div id="managementDropdown" class="nav-dropdown absolute left-0 mt-2 w-48 bg-white rounded-md shadow-lg py-1 z-10 hidden">
                    <a href="manage_sitins.php" class="block px-4 py-2 text-gray-700 hover:bg-gray-100">Manage Sit-ins</a>
                    <a href="studentlist.php" class="block px-4 py-2 text-gray-700 hover:bg-gray-100">Students</a>
                    <a href="create_announcement.php" class="block px-4 py-2 text-gray-700 hover:bg-gray-100">Announcements</a>
                </div>
            </div>

            
            <!-- Reservations Dropdown -->
            <div class="relative">
                <button onclick="toggleDropdown('reservationsDropdown')" class="px-4 py-2 text-gray-700 font-medium hover:bg-gray-100 rounded-md transition-all duration-200">
                    Reservations <i class="fas fa-chevron-down ml-2 text-xs"></i>
                </button>
                <div id="reservationsDropdown" class="nav-dropdown absolute left-0 mt-2 w-48 bg-white rounded-md shadow-lg py-1 z-10 hidden">
                    <a href="manage_reservation.php" class="block px-4 py-2 text-gray-700 hover:bg-gray-100">Reservations</a>
                    <a href="reservation_logs.php" class="block px-4 py-2 text-gray-700 hover:bg-gray-100">Reservation Logs</a>
                </div>
            </div>
            
            <!-- Resources Dropdown -->
            <div class="relative">
                <button onclick="toggleDropdown('resourcesDropdown')" class="px-4 py-2 text-gray-700 font-medium hover:bg-gray-100 rounded-md transition-all duration-200">
                    Resources <i class="fas fa-chevron-down ml-2 text-xs"></i>
                </button>
                <div id="resourcesDropdown" class="nav-dropdown absolute left-0 mt-2 w-48 bg-white rounded-md shadow-lg py-1 z-10 hidden">
                    <a href="admin_upload_resources.php" class="block px-4 py-2 text-gray-700 hover:bg-gray-100">Upload Resources</a>
                    <a href="admin_leaderboard.php" class="block px-4 py-2 text-gray-700 hover:bg-gray-100">Leaderboard</a>
                </div>
            </div>

            
            <!-- Labs Dropdown -->
            <div class="relative">
                <button onclick="toggleDropdown('labsDropdown')" class="px-4 py-2 text-gray-700 font-medium hover:bg-gray-100 rounded-md transition-all duration-200">
                    Labs <i class="fas fa-chevron-down ml-2 text-xs"></i>
                </button>
                <div id="labsDropdown" class="nav-dropdown absolute left-0 mt-2 w-48 bg-white rounded-md shadow-lg py-1 z-10 hidden">
                    <a href="admin_lab_schedule.php" class="block px-4 py-2 text-gray-700 hover:bg-gray-100">Lab Schedule</a>
                    <a href="lab_management.php" class="block px-4 py-2 text-gray-700 hover:bg-gray-100">Lab Management</a>
                </div>
            </div>

        </nav>
        
        <!-- Mobile Menu Button (hidden on larger screens) -->
        <div class="md:hidden">
            <button id="mobile-menu-button" class="text-gray-700 hover:text-gray-900">
                <i class="fas fa-bars text-xl"></i>
            </button>
        </div>
        
        <div class = "flex">
                     <!-- Admin Info -->
         <div class="flex items-center space-x-0">
                <!-- Admin Icon -->
                <div class="w-10 h-10 rounded-full bg-purple-600 flex items-center justify-center">
                    <i class="fas fa-user-shield text-white"></i>
                </div>
                <!-- Admin Name -->
                <h2 class="px-4 py-2 text-gray-700 font-bold"><?php echo htmlspecialchars($firstname); ?></h2>
            </div>

        <!-- Logout Button -->
        <div class="ml-4">
            
        <a href="logout.php" class="flex items-center px-4 py-2 bg-purple-600 text-white rounded-full border-2 border-purple-700 hover:bg-purple-700 transition-all duration-200 shadow-md">
        <i class="fas fa-sign-out-alt mr-2"></i>
        <span class="hidden md:inline">Log Out</span>
    </a>
        </div>
        </div>
    </div>
    
    <!-- Mobile Menu (hidden by default) -->
    <div id="mobile-menu" class="hidden md:hidden bg-white border-t border-gray-200 px-6 py-3">
        <a href="admin_dashboard.php" class="block py-2 text-gray-700">Dashboard</a>
        
        <div class="py-2">
            <button class="flex items-center justify-between w-full text-gray-700" onclick="toggleMobileDropdown('records-dropdown')">
                Records
                <i class="fas fa-chevron-down"></i>
            </button>
            <div id="records-dropdown" class="hidden pl-4">
                <a href="todays_sitins.php" class="block py-2 text-gray-700">Current Sit-ins</a>
                <a href="sit_in_records.php" class="block py-2 text-gray-700">Sit-in Reports</a>
                <a href="feedback_records.php" class="block py-2 text-gray-700">Feedback Reports</a>
            </div>
        </div>
        
        <div class="py-2">
            <button class="flex items-center justify-between w-full text-gray-700" onclick="toggleMobileDropdown('management-dropdown')">
                Management
                <i class="fas fa-chevron-down"></i>
            </button>
            <div id="management-dropdown" class="hidden pl-4">
                <a href="manage_sitins.php" class="block py-2 text-gray-700">Manage Sit-ins</a>
                <a href="studentlist.php" class="block py-2 text-gray-700">Students</a>
                <a href="create_announcement.php" class="block py-2 text-gray-700">Announcements</a>
            </div>
        </div>
        
        <div class="py-2">
            <button class="flex items-center justify-between w-full text-gray-700" onclick="toggleMobileDropdown('reservations-dropdown')">
                Reservations
                <i class="fas fa-chevron-down"></i>
            </button>
            <div id="reservations-dropdown" class="hidden pl-4">
                <a href="manage_reservation.php" class="block py-2 text-gray-700">Reservations</a>
                <a href="reservation_logs.php" class="block py-2 text-gray-700">Reservation Logs</a>
            </div>
        </div>
        
        <div class="py-2">
            <button class="flex items-center justify-between w-full text-gray-700" onclick="toggleMobileDropdown('resources-dropdown')">
                Resources
                <i class="fas fa-chevron-down"></i>
            </button>
            <div id="resources-dropdown" class="hidden pl-4">
                <a href="admin_upload_resources.php" class="block py-2 text-gray-700">Upload Resources</a>
                <a href="admin_leaderboard.php" class="block py-2 text-gray-700">Leaderboard</a>
            </div>
        </div>
        
        <div class="py-2">
            <button class="flex items-center justify-between w-full text-gray-700" onclick="toggleMobileDropdown('labs-dropdown')">
                Labs
                <i class="fas fa-chevron-down"></i>
            </button>
            <div id="labs-dropdown" class="hidden pl-4">
                <a href="admin_lab_schedule.php" class="block py-2 text-gray-700">Lab Schedule</a>
                <a href="lab_management.php" class="block py-2 text-gray-700">Lab Management</a>
            </div>
        </div>
    </div>
</div>


<!-- Main Content - Adjusted for Navbar -->
<div class="min-h-screen bg-purple-100 main-content-cont">
    <div class="">      
      <!-- Header Section with Decorative Elements -->
      <div class="mb-10">
      <div class="flex items-center justify-between mb-4">
        <div>
        <h2 class="text-3xl font-medium text-gray-800 tracking-tight">Resources</h2>
        <p class="text-gray-500 font-light mt-2">Upload and download resources for admin and students</p>
        </div>

      </div>
      <div class="w-20 h-1 bg-gradient-to-r from-purple-400 to-indigo-400 rounded-full"></div>
    </div>
   






<!-- Resource Grid -->
<div class="flex justify-between">
<h3 class="text-lg font-medium text-gray-700 flex items-center">
        <i class="fas fa-list-ul text-purple-500 mr-2"></i>
        Manage Files
      </h3><!-- Upload Modal Trigger Button -->
<div class="text-right mb-6">
    <button onclick="openUploadModal()" class="flex items-center px-5 py-2.5 bg-gradient-to-r from-purple-600 to-indigo-600 text-white rounded-lg shadow-md hover:shadow-lg transition-all duration-300 group">
                <span class="relative z-10 flex items-center">
              <i class="fas fa-upload mr-2 text-white/90"></i>
              Upload Resources
            </span>
  </button>
</div>
</div>
<div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
    <?php while($resource = mysqli_fetch_assoc($resources_result)): ?>
        <?php
        $file_ext = pathinfo($resource['file_name'], PATHINFO_EXTENSION);
        $icon = 'fa-file';
        if (in_array($file_ext, ['pdf'])) $icon = 'fa-file-pdf';
        elseif (in_array($file_ext, ['doc', 'docx'])) $icon = 'fa-file-word';
        elseif (in_array($file_ext, ['xls', 'xlsx'])) $icon = 'fa-file-excel';
        elseif (in_array($file_ext, ['ppt', 'pptx'])) $icon = 'fa-file-powerpoint';
        elseif (in_array($file_ext, ['zip', 'rar', '7z'])) $icon = 'fa-file-archive';
        elseif (in_array($file_ext, ['jpg', 'jpeg', 'png', 'gif'])) $icon = 'fa-file-image';
        ?>
        <div class="bg-white rounded border border-gray-300 p-4">
            <div class="flex flex-col items-center text-center">
                <div class="bg-gray-100 rounded-full w-14 h-14 flex items-center justify-center mb-3">
                    <i class="fas <?php echo $icon; ?> text-xl text-blue-500"></i>
                </div>
                <h4 class="font-semibold text-sm mb-1 truncate w-full"><?php echo htmlspecialchars($resource['title']); ?></h4>
                <p class="text-xs text-gray-500 mb-2"><?php echo formatSizeUnits($resource['file_size']); ?></p>
                <div class="flex space-x-2">
                    <a href="download_resource.php?id=<?php echo $resource['id']; ?>" class="text-xs bg-blue-100 text-blue-600 hover:bg-blue-200 px-2 py-1 rounded">
                        <i class="fas fa-download mr-1"></i> Download
                    </a>
                    <a href="delete_resource.php?id=<?php echo $resource['id']; ?>" class="text-xs bg-red-100 text-red-600 hover:bg-red-200 px-2 py-1 rounded" onclick="return confirm('Are you sure you want to delete this resource?')">
                        <i class="fas fa-trash mr-1"></i> Delete
                    </a>
                </div>
            </div>
        </div>
    <?php endwhile; ?>
</div>
<!-- File Upload Modal -->
<div id="uploadModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 hidden">
  <div class="bg-white rounded-md p-6 border border-gray-300 max-w-2xl w-full mx-4 relative">
    <button onclick="closeUploadModal()" class="absolute top-2 right-2 text-gray-500 hover:text-gray-700">
      <i class="fas fa-times"></i>
    </button>
    
    <form id="uploadForm" method="POST" enctype="multipart/form-data" class="space-y-4 mt-4">
      <?php if(isset($_SESSION['upload_success'])): ?>
        <div class="bg-green-100 text-green-700 p-3 rounded">
          <?php echo $_SESSION['upload_success']; unset($_SESSION['upload_success']); ?>
        </div>
      <?php endif; ?>

      <?php if(isset($_SESSION['upload_error'])): ?>
        <div class="bg-red-100 text-red-700 p-3 rounded">
          <?php echo $_SESSION['upload_error']; unset($_SESSION['upload_error']); ?>
        </div>
      <?php endif; ?>

      <div class="text-center">
        <i class="fas fa-cloud-upload-alt text-3xl text-blue-500 mb-2"></i>
        <p class="text-gray-600 mb-4">Drag & drop files here or click to browse</p>
        <input type="file" name="resource_file" id="resourceFile" class="hidden" required>
        <button type="button" onclick="document.getElementById('resourceFile').click()" 
                class="px-4 py-2 bg-blue-500 hover:bg-blue-600 text-white rounded">
          <i class="fas fa-upload mr-2"></i> Select File
        </button>
        <p class="text-xs text-gray-500 mt-2">Max file size: 50MB</p>
        <p id="fileName" class="text-sm text-gray-600 mt-2 hidden"></p>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Title *</label>
          <input type="text" name="title" class="w-full p-2 border border-gray-300 rounded" required>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Available To *</label>
          <select name="available_to" class="w-full p-2 border border-gray-300 rounded">
            <option value="all">All Users</option>
            <option value="students">Students Only</option>
            <option value="admins">Admins Only</option>
          </select>
        </div>

        <div class="md:col-span-2">
          <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
          <textarea name="description" rows="2" class="w-full p-2 border border-gray-300 rounded"></textarea>
        </div>
      </div>

      <div class="pt-4">
        <button type="submit" class="w-full flex items-center justify-center p-2 bg-blue-600 text-white rounded hover:bg-blue-700">
          <i class="fas fa-upload mr-2"></i> Upload Resource
        </button>
      </div>
    </form>
  </div>
</div>



<?php if(mysqli_num_rows($resources_result) == 0): ?>
    <div class="text-center py-10 text-gray-500">
        <i class="fas fa-folder-open text-3xl mb-3"></i>
        <p>No resources uploaded yet.</p>
    </div>
<?php endif; ?>
        </div>
    </div>

    <script>

        // Show file name when selected
  document.getElementById('resourceFile').addEventListener('change', function() {
    const fileName = this.files.length ? this.files[0].name : '';
    const fileNameElement = document.getElementById('fileName');
    fileNameElement.textContent = fileName;
    fileNameElement.classList.toggle('hidden', !fileName);
  });

  function openUploadModal() {
    document.getElementById('uploadModal').classList.remove('hidden');
  }

  function closeUploadModal() {
    document.getElementById('uploadModal').classList.add('hidden');
  }

  // Auto open modal if session message exists
  <?php if(isset($_SESSION['upload_success']) || isset($_SESSION['upload_error'])): ?>
    window.addEventListener('DOMContentLoaded', () => {
      openUploadModal();
    });
  <?php endif; ?>

        //pop up for file upload
        function openUploadModal() {
            document.getElementById('uploadModal').classList.remove('hidden');
        }
        function closeUploadModal() {
            document.getElementById('uploadModal').classList.add('hidden');
        }

        // Handle file input change
        document.getElementById('resourceFile').addEventListener('change', function(e) {
            const fileNameElement = document.getElementById('fileName');
            if (this.files.length > 0) {
                fileNameElement.textContent = this.files[0].name;
                fileNameElement.classList.remove('hidden');
            } else {
                fileNameElement.classList.add('hidden');
            }
        });

        // Drag and drop functionality
        const dropzone = document.getElementById('uploadDropzone');
        const fileInput = document.getElementById('resourceFile');

        dropzone.addEventListener('dragover', (e) => {
            e.preventDefault();
            dropzone.classList.add('dragover');
        });

        dropzone.addEventListener('dragleave', () => {
            dropzone.classList.remove('dragover');
        });

        dropzone.addEventListener('drop', (e) => {
            e.preventDefault();
            dropzone.classList.remove('dragover');
            
            if (e.dataTransfer.files.length) {
                fileInput.files = e.dataTransfer.files;
                const fileNameElement = document.getElementById('fileName');
                fileNameElement.textContent = e.dataTransfer.files[0].name;
                fileNameElement.classList.remove('hidden');
            }
        });


        function toggleDropdown(id) {
  const dropdown = document.getElementById(id);
  dropdown.classList.toggle('hidden');

  // Optional: close other open dropdowns
  document.querySelectorAll('.nav-dropdown').forEach(el => {
    if (el.id !== id) el.classList.add('hidden');
  });
}

// Optional: close when clicking outside
document.addEventListener('click', function(event) {
  if (!event.target.closest('.relative')) {
    document.querySelectorAll('.nav-dropdown').forEach(el => el.classList.add('hidden'));
  }
});

    </script>

    <?php
    // Helper function to format file sizes
    function formatSizeUnits($bytes) {
        if ($bytes >= 1073741824) {
            $bytes = number_format($bytes / 1073741824, 2) . ' GB';
        } elseif ($bytes >= 1048576) {
            $bytes = number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            $bytes = number_format($bytes / 1024, 2) . ' KB';
        } elseif ($bytes > 1) {
            $bytes = $bytes . ' bytes';
        } elseif ($bytes == 1) {
            $bytes = $bytes . ' byte';
        } else {
            $bytes = '0 bytes';
        }
        return $bytes;
    }
    ?>
</body>
</html>