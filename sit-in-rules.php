<?php
session_start();
include 'db.php'; // Make sure this path is correct

// Check if the user is logged in
if (!isset($_SESSION['idno'])) {
    header("Location: login.php");
    exit();
}

if (isset($_SESSION['idno'])) {
    $idno_session = $_SESSION['idno'];
    
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
}
$conn->close(); // Close DB connection after fetching necessary data
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sit-in Rules - CCS SIT-IN MONITORING SYSTEM</title>
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
                        'accent-blue': '#3B82F6', // For icons in rules list
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
            padding: 2rem 1rem; /* Default padding */
        }
        @media (min-width: 768px) { /* md breakpoint */
            .main-content-area {
                padding: 2rem;
            }
        }
        @media (min-width: 1024px) { /* lg breakpoint */
            .main-content-area {
                padding: 3rem 4rem;
            }
        }
        .nav-dropdown a:hover {
            background-color: theme('colors.purple.50');
            color: theme('colors.custom-purple');
        }
        .nav-dropdown {
            z-index: 20;
        }
        .rule-card {
            transition: all 0.3s ease-in-out;
        }
        .rule-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 12px -3px rgba(0,0,0,0.07);
        }
        .rule-icon {
            color: theme('colors.custom-purple'); /* Using purple for icons */
            opacity: 0.8;
        }
        .page-header-icon {
            color: theme('colors.custom-indigo');
        }
    </style>
</head>
<body class="font-sans text-text-primary">

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
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8 sm:py-12 main-content-area">
        <div class="bg-card-bg p-6 sm:p-10 rounded-xl shadow-2xl">
            <div class="flex items-center mb-6 sm:mb-8 pb-4 border-b border-gray-200">
                <div class="flex-shrink-0 w-12 h-12 rounded-lg bg-custom-indigo/10 flex items-center justify-center text-custom-indigo mr-4">
                     <i class="fas fa-gavel text-2xl page-header-icon"></i>
                </div>
                <div>
                    <h1 class="text-2xl sm:text-3xl font-bold text-text-primary">Sit-in Rules & Guidelines</h1>
                    <p class="text-sm text-text-secondary mt-1">Please read and adhere to the following regulations.</p>
                </div>
            </div>
            
            <div class="space-y-8">
                <!-- General Rules -->
                <section class="rule-card bg-gray-50 p-6 rounded-lg border border-gray-200">
                    <h3 class="text-xl font-semibold mb-4 text-text-primary flex items-center">
                        <i class="fas fa-shield-alt rule-icon mr-3 text-lg"></i> General Conduct
                    </h3>
                    <ul class="space-y-3 text-text-secondary text-sm sm:text-base">
                        <li class="flex items-start">
                            <i class="fas fa-user-check rule-icon mt-1 mr-3 w-4 text-center"></i>
                            <span>Students must **present their ID** and **log their sit-in accurately** upon entry and exit of the laboratory.</span>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-tshirt rule-icon mt-1 mr-3 w-4 text-center"></i>
                            <span>Adherence to the university's **proper dress code** is mandatory at all times within the laboratory.</span>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-clock rule-icon mt-1 mr-3 w-4 text-center"></i>
                            <span>**Punctuality is expected.** Entry may be restricted for latecomers more than 15 minutes after a scheduled session or reservation begins.</span>
                        </li>
                         <li class="flex items-start">
                            <i class="fas fa-utensils rule-icon mt-1 mr-3 w-4 text-center"></i>
                            <span>**No eating or drinking** (except for water in a sealed container) is allowed inside the laboratory to maintain cleanliness and protect equipment.</span>
                        </li>
                    </ul>
                </section>

                <!-- Behavior Rules -->
                <section class="rule-card bg-gray-50 p-6 rounded-lg border border-gray-200">
                    <h3 class="text-xl font-semibold mb-4 text-text-primary flex items-center">
                        <i class="fas fa-users-cog rule-icon mr-3 text-lg"></i> Laboratory Etiquette
                    </h3>
                    <ul class="space-y-3 text-text-secondary text-sm sm:text-base">
                        <li class="flex items-start">
                            <i class="fas fa-volume-mute rule-icon mt-1 mr-3 w-4 text-center"></i>
                            <span>Maintain a **quiet and conducive learning environment.** Unnecessary noise and disturbances are prohibited.</span>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-mobile-alt rule-icon mt-1 mr-3 w-4 text-center"></i>
                            <span>Use of **mobile phones for calls and non-academic purposes** should be minimized. Set devices to silent mode.</span>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-chair rule-icon mt-1 mr-3 w-4 text-center"></i>
                            <span>Keep workstations **clean and orderly.** Return all equipment and chairs to their proper places after use.</span>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-plug rule-icon mt-1 mr-3 w-4 text-center"></i>
                            <span>Do not tamper with, disconnect, or reconfigure any lab equipment, wiring, or network connections without authorization.</span>
                        </li>
                    </ul>
                </section>

                <!-- Consequences -->
                <section class="rule-card bg-red-50 p-6 rounded-lg border border-accent-red/50">
                    <h3 class="text-xl font-semibold mb-4 text-red-700 flex items-center">
                        <i class="fas fa-exclamation-triangle text-accent-red mr-3 text-lg"></i> Violations & Consequences
                    </h3>
                    <ul class="space-y-3 text-red-600 text-sm sm:text-base">
                        <li class="flex items-start">
                            <i class="fas fa-ban rule-icon mt-1 mr-3 w-4 text-center text-accent-red"></i>
                            <span>Failure to comply with these rules may result in a **verbal warning, temporary suspension of lab privileges, or further disciplinary action** as deemed appropriate by the lab administrator or faculty.</span>
                        </li>
                         <li class="flex items-start">
                            <i class="fas fa-laptop-code rule-icon mt-1 mr-3 w-4 text-center text-accent-red"></i>
                            <span>Unauthorized software installation, accessing inappropriate content, or any form of academic dishonesty will lead to immediate revocation of lab access and reporting to university authorities.</span>
                        </li>
                    </ul>
                </section>
                 <div class="text-center mt-8">
                    <a href="student_dashboard.php" class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-md shadow-sm text-white bg-gradient-to-r from-custom-purple to-custom-indigo hover:from-purple-700 hover:to-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-custom-indigo">
                        <i class="fas fa-arrow-left mr-2"></i> Back to Dashboard
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script>
        // JavaScript for Navbar Dropdowns (Desktop)
        function toggleDropdown(dropdownId) {
            const allDropdowns = document.querySelectorAll('.nav-dropdown');
            allDropdowns.forEach(function(dd) {
                if (dd.id !== dropdownId) {
                    dd.classList.add('hidden');
                }
            });
            const targetDropdown = document.getElementById(dropdownId);
            if (targetDropdown) {
                targetDropdown.classList.toggle('hidden');
            }
        }

        document.addEventListener('click', function(event) {
            let clickedElement = event.target;
            let isDropdownButtonOrInsideDropdown = false;
            while (clickedElement != null) {
                if (clickedElement.matches('button[onclick^="toggleDropdown"]') || (clickedElement.classList && clickedElement.classList.contains('nav-dropdown') && !clickedElement.classList.contains('hidden'))) {
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

        // Mobile Menu Toggle
        const mobileMenuButtonStudent = document.getElementById('mobile-menu-button-student');
        const mobileMenuStudent = document.getElementById('mobile-menu-student');
        if (mobileMenuButtonStudent && mobileMenuStudent) {
            mobileMenuButtonStudent.addEventListener('click', (event) => {
                event.stopPropagation();
                mobileMenuStudent.classList.toggle('hidden');
            });
        }

        function toggleMobileDropdown(dropdownId) {
            const dropdown = document.getElementById(dropdownId);
            if (dropdown) {
                document.querySelectorAll('#mobile-menu-student div[id^="mobile"][id$="DropdownStudentNav"]').forEach(el => {
                    if (el.id !== dropdownId && !el.classList.contains('hidden')) {
                        el.classList.add('hidden');
                    }
                });
                dropdown.classList.toggle('hidden');
            }
        }
    </script>
</body>
</html>