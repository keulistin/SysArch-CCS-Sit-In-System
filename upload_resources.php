<?php
session_start();
include 'db.php'; // Make sure this path is correct

// Check if the user is logged in as a student
if (!isset($_SESSION['idno']) || $_SESSION['role'] !== 'student') {
    header("Location: login.php");
    exit();
}

$idno_session = $_SESSION['idno'];
$page_title = "View Lab Resources";

// Initialize variables for navbar display
$navbar_firstname = 'Student'; // Default
$navbar_profile_picture = 'default_avatar.png'; // Default

// Fetch minimal user data for navbar
$stmt_nav = $conn->prepare("SELECT firstname, profile_picture FROM users WHERE idno = ?");
if ($stmt_nav) {
    $stmt_nav->bind_param("s", $idno_session);
    $stmt_nav->execute();
    $stmt_nav->bind_result($nav_fn, $nav_pp);
    if ($stmt_nav->fetch()) {
        $navbar_firstname = $nav_fn;
        $navbar_profile_picture = !empty($nav_pp) ? $nav_pp : 'default_avatar.png';
    }
    $stmt_nav->close();
} else {
    error_log("Error preparing navbar user statement: " . $conn->error);
}

// Fetch resources available to students
$resources_query = "SELECT id, title, description, file_name, file_type, file_size, upload_date 
                    FROM resources 
                    WHERE available_to = 'students' OR available_to = 'all' 
                    ORDER BY upload_date DESC";
$resources_result = mysqli_query($conn, $resources_query);
// Check for query error
if (!$resources_result) {
    error_log("Error fetching resources: " . mysqli_error($conn));
    // Optionally set a page error message
    $page_error = "Could not load resources at this time.";
}

// Helper function to format file sizes (moved to top for clarity)
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
$conn->close(); // Close DB connection
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?> - CCS SIT-IN MONITORING SYSTEM</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Poppins', 'sans-serif'],
                    },
                    colors: {
                        'custom-purple': '#6D28D9',
                        'custom-indigo': '#4F46E5',
                        'light-bg': '#F1E6EF',
                        'card-bg': '#FFFFFF',
                        'nav-bg': '#FFFFFF',
                        'text-primary': '#1F2937',
                        'text-secondary': '#6B7280',
                        'accent-blue': '#3B82F6',
                         'file-icon-bg': '#E0E7FF', // Indigo 100 for file icons
                        'file-icon-color': '#4338CA', // Indigo 700
                    }
                },
            },
        }
    </script>
    <style>
        body {
                        background-color: #F1E6EF;
            padding-top: 76px; /* Adjust based on your navbar height */
        }
        .main-content-area {
            padding: 2rem 1rem;
        }
        @media (min-width: 768px) { .main-content-area { padding: 2rem; } }
        @media (min-width: 1024px) { .main-content-area { padding: 3rem 4rem; } }
        
        .nav-dropdown a:hover {
            background-color: theme('colors.purple.50');
            color: theme('colors.custom-purple');
        }
        .nav-dropdown { z-index: 20; }

        .resource-card {
            background-color: theme('colors.card-bg');
            border: 1px solid theme('colors.gray.200');
            transition: all 0.3s ease-in-out;
            display: flex;
            flex-direction: column;
        }
        .resource-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 10px 20px -5px rgba(0,0,0,0.1), 0 6px 6px -5px rgba(0,0,0,0.06);
        }
        .file-type-icon-container {
            width: 4rem; /* w-16 */
            height: 4rem; /* h-16 */
            background-color: theme('colors.file-icon-bg');
            color: theme('colors.file-icon-color');
            font-size: 1.75rem; /* text-3xl */
        }
        .page-header-icon { color: theme('colors.custom-purple'); }
    </style>
</head>
<body class="font-sans antialiased text-text-primary">

   <!-- Top Navigation Bar for Student -->
<div class="fixed top-0 left-0 right-0 bg-white shadow-md z-50">
  <div class="flex items-center justify-between px-6 py-3">
    <!-- CCS Logo -->
    <div class="flex items-center">
      <img src="images/CCS.png" alt="CCS Logo" class="h-14">
    </div>

    <!-- Main Navigation Links -->
    <nav class="hidden md:flex items-center space-x-2">
      <a href="student_dashboard.php" class="px-4 py-2 text-gray-700 font-medium hover:bg-gray-100 rounded-md transition-all duration-200">
        Dashboard
      </a>

    <!-- Rules Dropdown -->
    <div class="relative">
        <button onclick="toggleDropdown('rulesDropdownStudent')" class="px-4 py-2 text-gray-700 font-medium hover:bg-gray-100 rounded-md transition-all duration-200">
            Rules <i class="fas fa-chevron-down ml-1 text-xs"></i>
        </button>
        <div id="rulesDropdownStudent" class="nav-dropdown absolute left-0 mt-2 w-48 bg-white rounded-md shadow-xl py-1 hidden">
            <a href="sit-in-rules.php" class="block px-4 py-2 text-sm text-text-secondary">Sit-in Rules</a>
            <a href="lab-rules.php" class="block px-4 py-2 text-sm text-text-secondary">Lab Rules</a>
        </div>
    </div>

    <!-- Sit-ins Dropdown -->
    <div class="relative">
        <button onclick="toggleDropdown('sitInsDropdownStudent')" class="px-4 py-2 text-gray-700 font-medium hover:bg-gray-100 rounded-md transition-all duration-200">
          Sit-ins <i class="fas fa-chevron-down ml-1 text-xs"></i>
        </button>
        <div id="sitInsDropdownStudent" class="nav-dropdown absolute left-0 mt-2 w-48 bg-white rounded-md shadow-xl py-1 hidden">
          <a href="reservation.php" class="block px-4 py-2 text-sm text-text-secondary">Reservation</a>
          <a href="sit_in_history.php" class="block px-4 py-2 text-sm text-text-secondary">History</a>
        </div>
    </div>

    <!-- Resources Dropdown -->
    <div class="relative">
        <button onclick="toggleDropdown('resourcesDropdownStudent')" class="px-4 py-2 text-gray-700 font-medium hover:bg-gray-100 rounded-md transition-all duration-200">
          Resources <i class="fas fa-chevron-down ml-1 text-xs"></i>
        </button>
        <div id="resourcesDropdownStudent" class="nav-dropdown absolute left-0 mt-2 w-48 bg-white rounded-md shadow-xl py-1 hidden">
          <a href="upload_resources.php" class="block px-4 py-2 text-sm text-text-secondary">View Resources</a>
          <a href="student_leaderboard.php" class="block px-4 py-2 text-sm text-text-secondary">Leaderboard</a>
          <a href="student_lab_schedule.php" class="block px-4 py-2 text-sm text-text-secondary">Lab Schedule</a>
        </div>
    </div>

      <!-- Announcements -->
      <a href="announcements.php" class="px-4 py-2 text-gray-700 font-medium hover:bg-gray-100 rounded-md transition-all duration-200">
        Announcements
      </a>

      <!-- Edit Profile -->
      <a href="edit-profile.php" class="px-4 py-2 text-gray-700 font-medium hover:bg-gray-100 rounded-md transition-all duration-200">
        Edit Profile
      </a>
    </nav>

    <!-- User Avatar and Logout -->
    <div class="flex items-center space-x-4">
      <!-- Avatar -->
      <div class="w-10 h-10 rounded-full bg-purple-600 flex items-center justify-center">
                <img src="uploads/<?php echo htmlspecialchars(!empty($navbar_profile_picture) ? $navbar_profile_picture : 'default_avatar.jpg'); ?>" 
                     alt="User Avatar" 
                     class="w-10 h-10 rounded-full object-cover border-2 border-custom-purple"
                     onerror="this.src='assets/default_avatar.png'">
      </div>
      <h2 class="px-4 py-2 text-gray-700 font-bold"><?php echo htmlspecialchars($navbar_firstname); ?></h2>

      <!-- Logout -->
        <div class="ml-4">
            
            <a href="logout.php" class="flex items-center px-4 py-2 bg-purple-600 text-white rounded-full border-2 border-purple-700 hover:bg-purple-700 transition-all duration-200 shadow-md">
            <i class="fas fa-sign-out-alt mr-2"></i>
            <span class="hidden md:inline">Log Out</span>
            </a>
        </div>
    </div>
  </div>
</div>

    <!-- Main Content Area -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 sm:py-12 main-content-area">
        <div class="bg-card-bg p-6 sm:p-8 rounded-xl shadow-2xl">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6 sm:mb-8 pb-4 border-b border-gray-200">
                <div class="flex items-center mb-4 sm:mb-0">
                    <div class="flex-shrink-0 w-12 h-12 rounded-lg bg-custom-indigo/10 flex items-center justify-center text-custom-indigo mr-4">
                         <i class="fas fa-book-open text-2xl page-header-icon"></i>
                    </div>
                    <div>
                        <h1 class="text-2xl sm:text-3xl font-bold text-text-primary"><?php echo htmlspecialchars($page_title); ?></h1>
                        <p class="text-sm text-text-secondary mt-1">Access shared documents, lecture notes, and other useful materials.</p>
                    </div>
                </div>
                <!-- Optional: Add a filter or search bar here if needed -->
            </div>

            <?php if (isset($page_error)): ?>
                 <div class="bg-red-50 border-l-4 border-accent-red text-accent-red p-4 mb-6 rounded-md" role="alert">
                    <p class="text-sm"><?php echo $page_error; ?></p>
                </div>
            <?php endif; ?>

            <!-- Resource Grid -->
            <?php if ($resources_result && mysqli_num_rows($resources_result) > 0): ?>
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
                    <?php while($resource = mysqli_fetch_assoc($resources_result)): ?>
                        <?php
                        $file_ext = strtolower(pathinfo($resource['file_name'], PATHINFO_EXTENSION));
                        $icon_class = 'fa-file-alt'; // Default icon
                        $icon_color_class = 'text-gray-500';

                        if (in_array($file_ext, ['pdf'])) { $icon_class = 'fa-file-pdf'; $icon_color_class = 'text-red-500'; }
                        elseif (in_array($file_ext, ['doc', 'docx'])) { $icon_class = 'fa-file-word'; $icon_color_class = 'text-blue-500'; }
                        elseif (in_array($file_ext, ['xls', 'xlsx'])) { $icon_class = 'fa-file-excel'; $icon_color_class = 'text-green-500'; }
                        elseif (in_array($file_ext, ['ppt', 'pptx'])) { $icon_class = 'fa-file-powerpoint'; $icon_color_class = 'text-orange-500'; }
                        elseif (in_array($file_ext, ['zip', 'rar', '7z'])) { $icon_class = 'fa-file-archive'; $icon_color_class = 'text-yellow-500'; }
                        elseif (in_array($file_ext, ['jpg', 'jpeg', 'png', 'gif', 'svg', 'webp'])) { $icon_class = 'fa-file-image'; $icon_color_class = 'text-purple-500'; }
                        elseif (in_array($file_ext, ['txt'])) { $icon_class = 'fa-file-lines'; $icon_color_class = 'text-gray-500'; }
                        ?>
                        <div class="resource-card rounded-lg p-4 shadow-md hover:shadow-lg flex">
                            <div class="flex-shrink-0 file-type-icon-container rounded-lg flex items-center justify-center mr-4">
                                <i class="fas <?php echo $icon_class; ?> <?php echo $icon_color_class; ?>"></i>
                            </div>
                            <div class="flex-1 min-w-0">
                                <h4 class="font-semibold text-sm text-text-primary truncate mb-0.5" title="<?php echo htmlspecialchars($resource['title']); ?>">
                                    <?php echo htmlspecialchars($resource['title']); ?>
                                </h4>
                                <?php if (!empty($resource['description'])): ?>
                                <p class="text-xs text-text-secondary truncate mb-1" title="<?php echo htmlspecialchars($resource['description']); ?>">
                                    <?php echo htmlspecialchars($resource['description']); ?>
                                </p>
                                <?php endif; ?>
                                <p class="text-xs text-gray-400">
                                    <i class="fas fa-database mr-1"></i> <?php echo formatSizeUnits($resource['file_size']); ?>
                                    <span class="mx-1">|</span>
                                    <i class="fas fa-calendar-alt mr-1"></i> <?php echo (new DateTime($resource['upload_date']))->format('M d, Y'); ?>
                                </p>
                                <div class="mt-3">
                                    <a href="download_resource.php?id=<?php echo $resource['id']; ?>" 
                                       class="inline-flex items-center text-xs bg-custom-indigo text-white hover:bg-indigo-700 px-3 py-1.5 rounded-md font-medium transition-colors duration-150">
                                        <i class="fas fa-download mr-1.5"></i> Download
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="text-center py-12 text-text-secondary">
                    <div class="flex flex-col items-center">
                        <i class="fas fa-folder-open text-5xl text-gray-300 mb-4"></i>
                        <p class="text-lg font-medium">No resources available at the moment.</p>
                        <p class="text-sm">Please check back later.</p>
                    </div>
                </div>
            <?php endif; ?>

             <div class="text-center mt-8 pt-6 border-t border-gray-200">
                <a href="student_dashboard.php" class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-md shadow-sm text-white bg-gradient-to-r from-custom-purple to-custom-indigo hover:from-purple-700 hover:to-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-custom-indigo">
                    <i class="fas fa-arrow-left mr-2"></i> Back to Dashboard
                </a>
            </div>
        </div>
    </div>

    <script>
// Dropdown toggle functions
function toggleDropdown(id) {
    const dropdown = document.getElementById(id);
    dropdown.classList.toggle('hidden');
    // Hide other dropdowns
    document.querySelectorAll('.nav-dropdown').forEach(el => {
        if (el.id !== id) el.classList.add('hidden');
    });
}

function toggleMobileDropdown(id) {
    document.getElementById(id).classList.toggle('hidden');
}

// Close dropdowns when clicking outside
document.addEventListener('click', function(event) {
    let clickedElement = event.target;
    let isDropdownButtonOrInsideDropdown = false;

    while (clickedElement != null) {
        // Check if click is on a dropdown button OR inside an open dropdown menu
        if (clickedElement.matches('button[onclick^="toggleDropdown"]') || 
            (clickedElement.classList && clickedElement.classList.contains('nav-dropdown') && !clickedElement.classList.contains('hidden'))) {
            isDropdownButtonOrInsideDropdown = true;
            break;
        }
        clickedElement = clickedElement.parentElement;
    }

    if (!isDropdownButtonOrInsideDropdown) {
        document.querySelectorAll('.nav-dropdown').forEach(function(dd) {
            dd.classList.add('hidden');
        });
    }
});
    </script>
</body>
</html>