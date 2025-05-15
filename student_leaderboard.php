<?php
session_start();
include 'db.php';

// Check if the user is logged in
if (!isset($_SESSION['idno'])) {
    header("Location: login.php");
    exit();
}

$idno = $_SESSION['idno'];

// Fetch student info with points
$user_query = "SELECT id, firstname, lastname, profile_picture, remaining_sessions, points, idno, course, yearlevel, email 
               FROM users 
               WHERE idno = ?";
$stmt = $conn->prepare($user_query);
$stmt->bind_param("s", $idno);
$stmt->execute();
$stmt->bind_result($user_id, $firstname, $lastname, $profile_picture, $remaining_sessions, $points, $idno, $course, $yearlevel, $email);
$stmt->fetch();
$stmt->close();

// Set default profile picture if none exists
if (empty($profile_picture)) {
    $profile_picture = "default_avatar.png";
}

// Fetch top 3 students for the podium
$top3_query = "SELECT id, firstname, lastname, points, remaining_sessions, profile_picture
              FROM users 
              WHERE role = 'student'
              ORDER BY points DESC, remaining_sessions DESC
              LIMIT 3";
$top3_result = mysqli_query($conn, $top3_query);
$top3 = [];
while ($row = mysqli_fetch_assoc($top3_result)) {
    $top3[] = $row;
}

// Fetch top 10 students for leaderboard table
$leaderboard_query = "SELECT id, firstname, lastname, points, remaining_sessions 
                     FROM users 
                     WHERE role = 'student'
                     ORDER BY points DESC, remaining_sessions DESC
                     LIMIT 10";
$leaderboard_result = mysqli_query($conn, $leaderboard_query);

// Calculate student's rank
$rank_query = "SELECT COUNT(*) + 1 as rank 
              FROM users 
              WHERE role = 'student' AND (points > ? OR (points = ? AND remaining_sessions > ?))";
$stmt = $conn->prepare($rank_query);
$stmt->bind_param("iii", $points, $points, $remaining_sessions);
$stmt->execute();
$stmt->bind_result($student_rank);
$stmt->fetch();
$stmt->close();

// Get all students for accurate ranking
$all_students_query = "SELECT id, firstname, lastname, points, remaining_sessions 
                      FROM users 
                      WHERE role = 'student'
                      ORDER BY points DESC, remaining_sessions DESC";
$all_students_result = mysqli_query($conn, $all_students_query);

// Find actual rank by iterating through all students
$actual_rank = 1;
$found = false;
while ($row = mysqli_fetch_assoc($all_students_result)) {
    if ($row['id'] == $user_id) {
        $found = true;
        break;
    }
    $actual_rank++;
}

// Use the more accurate ranking method
$student_rank = $found ? $actual_rank : $student_rank;

$page_title = "Leaderboard";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Leaderboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        body {
            background-color: #F1E6EF;
            min-height: 100vh;
        }
        .main-content-cont {
            padding: 8rem 15rem 5rem 15rem;
        }
        .podium-item {
            transition: all 0.3s ease;
        }
        .podium-item:hover {
            transform: translateY(-5px);
        }
        .highlight-row {
            background-color: rgba(167, 139, 250, 0.2);
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

        // Mobile menu toggle
        document.getElementById('mobile-menu-button').addEventListener('click', function() {
            document.getElementById('mobile-menu').classList.toggle('hidden');
        });

        // Logout confirmation
        function confirmLogout(event) {
            if (!confirm('Are you sure you want to log out?')) {
                event.preventDefault();
            }
        }
    </script>
</head>
<body class="font-sans text-black">
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

        <!-- Mobile Menu Button (hidden on larger screens) -->
        <div class="md:hidden">
            <button id="mobile-menu-button" class="text-gray-700 hover:text-gray-900">
                <i class="fas fa-bars text-xl"></i>
            </button>
        </div>

        <!-- User Avatar and Logout -->
        <div class="flex items-center space-x-4">
            <!-- Avatar -->
            <div class="w-10 h-10 rounded-full bg-purple-600 flex items-center justify-center">
                <img src="uploads/<?php echo htmlspecialchars(!empty($profile_picture) ? $profile_picture : 'default_avatar.jpg'); ?>" 
                     alt="User Avatar" 
                     class="w-10 h-10 rounded-full object-cover border-2 border-custom-purple"
                     onerror="this.src='assets/default_avatar.png'">
            </div>
            <h2 class="px-4 py-2 text-gray-700 font-bold"><?php echo htmlspecialchars($firstname); ?></h2>

            <!-- Logout -->
            <div class="ml-4">
                <a href="logout.php" onclick="return confirm('Are you sure you want to log out?')" class="flex items-center px-4 py-2 bg-purple-600 text-white rounded-full border-2 border-purple-700 hover:bg-purple-700 transition-all duration-200 shadow-md">
                    <i class="fas fa-sign-out-alt mr-2"></i>
                    <span class="hidden md:inline">Log Out</span>
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Mobile Menu (hidden by default) -->
<div id="mobile-menu" class="hidden md:hidden bg-white border-t border-gray-200 px-6 py-3">
    <a href="student_dashboard.php" class="block py-2 text-gray-700">Dashboard</a>
    
    <div class="py-2">
        <button class="flex items-center justify-between w-full text-gray-700" onclick="toggleMobileDropdown('rules-dropdown')">
            Rules
            <i class="fas fa-chevron-down"></i>
        </button>
        <div id="rules-dropdown" class="hidden pl-4">
            <a href="sit-in-rules.php" class="block py-2 text-gray-700">Sit-in Rules</a>
            <a href="lab-rules.php" class="block py-2 text-gray-700">Lab Rules</a>
        </div>
    </div>
    
    <div class="py-2">
        <button class="flex items-center justify-between w-full text-gray-700" onclick="toggleMobileDropdown('sit-ins-dropdown')">
            Sit-ins
            <i class="fas fa-chevron-down"></i>
        </button>
        <div id="sit-ins-dropdown" class="hidden pl-4">
            <a href="reservation.php" class="block py-2 text-gray-700">Reservation</a>
            <a href="sit_in_history.php" class="block py-2 text-gray-700">History</a>
        </div>
    </div>
    
    <div class="py-2">
        <button class="flex items-center justify-between w-full text-gray-700" onclick="toggleMobileDropdown('resources-dropdown')">
            Resources
            <i class="fas fa-chevron-down"></i>
        </button>
        <div id="resources-dropdown" class="hidden pl-4">
            <a href="upload_resources.php" class="block py-2 text-gray-700">View Resources</a>
            <a href="student_leaderboard.php" class="block py-2 text-gray-700">Leaderboard</a>
            <a href="student_lab_schedule.php" class="block py-2 text-gray-700">Lab Schedule</a>
        </div>
    </div>
    
    <a href="announcements.php" class="block py-2 text-gray-700">Announcements</a>
    <a href="edit-profile.php" class="block py-2 text-gray-700">Edit Profile</a>
</div>

<!-- Main Content -->
<div class="min-h-screen bg-purple-100 main-content-cont">
    <div class="max-w-7xl mx-auto">
        <!-- Header Section -->
        <div class="mb-10">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h2 class="text-3xl font-medium text-gray-800 tracking-tight">Leaderboard</h2>
                    <p class="text-gray-500 font-light mt-2">See where you stand among your peers</p>
                </div>
            </div>
            <div class="w-20 h-1 bg-gradient-to-r from-purple-400 to-indigo-400 rounded-full"></div>
        </div>

        <!-- Student Stats -->
        <div class="bg-white rounded-xl shadow-md border border-gray-200 p-6 mb-8">
            <h3 class="text-xl font-semibold mb-4 text-gray-700">Your Ranking</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="bg-purple-50 p-4 rounded-lg border border-purple-100">
                    <p class="text-sm text-purple-600">Current Rank</p>
                    <p class="text-2xl font-bold text-gray-800">
                        <?php if($student_rank <= 3): ?>
                            <?php if($student_rank == 1): ?>
                                <span class="text-yellow-500">🥇</span>
                            <?php elseif($student_rank == 2): ?>
                                <span class="text-gray-400">🥈</span>
                            <?php else: ?>
                                <span class="text-amber-600">🥉</span>
                            <?php endif; ?>
                        <?php endif; ?>
                        <?php echo $student_rank; ?>
                    </p>
                </div>
                <div class="bg-purple-50 p-4 rounded-lg border border-purple-100">
                    <p class="text-sm text-purple-600">Points</p>
                    <p class="text-2xl font-bold text-gray-800"><?php echo $points; ?></p>
                </div>
                <div class="bg-purple-50 p-4 rounded-lg border border-purple-100">
                    <p class="text-sm text-purple-600">Sessions</p>
                    <p class="text-2xl font-bold text-gray-800"><?php echo $remaining_sessions; ?></p>
                </div>
            </div>
        </div>

        <!-- Podium for Top 3 Students -->
        <div class="mb-16">
            <h3 class="text-xl font-semibold mb-6 text-center text-gray-700">Top Performers</h3>
            <div class="flex items-end justify-center gap-6 h-72">
                <!-- 2nd Place -->
                <?php if (isset($top3[1])): ?>
                <div class="podium-item flex flex-col items-center w-1/4 transform hover:scale-105 transition-transform duration-300">
                    <div class="bg-gradient-to-b from-gray-300 to-gray-400 w-full rounded-t-lg h-48 flex flex-col items-center justify-end relative shadow-md">
                        <div class="absolute -top-8 w-16 h-16 bg-white rounded-full flex items-center justify-center shadow-lg border-4 border-gray-300">
                            <span class="text-3xl">🥈</span>
                        </div>
                        <div class="w-full px-4 pb-6 text-center">
                            <div class="text-lg font-semibold text-gray-800"><?= htmlspecialchars($top3[1]['firstname'].' '.$top3[1]['lastname']) ?></div>
                            <div class="text-sm text-gray-600 mt-1"><?= $top3[1]['points'] ?> points</div>
                        </div>
                    </div>
                    <div class="text-center mt-4">
                        <div class="text-lg font-bold text-gray-500 bg-gray-100 px-4 py-1 rounded-full">2nd</div>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- 1st Place -->
                <?php if (isset($top3[0])): ?>
                <div class="podium-item flex flex-col items-center w-1/3 transform hover:scale-105 transition-transform duration-300">
                    <div class="bg-gradient-to-b from-yellow-300 to-yellow-500 w-full rounded-t-lg h-64 flex flex-col items-center justify-end relative shadow-lg">
                        <div class="absolute -top-10 w-20 h-20 bg-white rounded-full flex items-center justify-center shadow-xl border-4 border-yellow-400">
                            <span class="text-4xl">🥇</span>
                        </div>
                        <div class="w-full px-4 pb-8 text-center">
                            <div class="text-xl font-semibold text-gray-800"><?= htmlspecialchars($top3[0]['firstname'].' '.$top3[0]['lastname']) ?></div>
                            <div class="text-sm text-gray-700 mt-1"><?= $top3[0]['points'] ?> points</div>
                        </div>
                    </div>
                    <div class="text-center mt-4">
                        <div class="text-lg font-bold text-yellow-600 bg-yellow-100 px-4 py-1 rounded-full">1st</div>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- 3rd Place -->
                <?php if (isset($top3[2])): ?>
                <div class="podium-item flex flex-col items-center w-1/4 transform hover:scale-105 transition-transform duration-300">
                    <div class="bg-gradient-to-b from-amber-400 to-amber-600 w-full rounded-t-lg h-40 flex flex-col items-center justify-end relative shadow-md">
                        <div class="absolute -top-8 w-16 h-16 bg-white rounded-full flex items-center justify-center shadow-lg border-4 border-amber-400">
                            <span class="text-3xl">🥉</span>
                        </div>
                        <div class="w-full px-4 pb-6 text-center">
                            <div class="text-lg font-semibold text-gray-800"><?= htmlspecialchars($top3[2]['firstname'].' '.$top3[2]['lastname']) ?></div>
                            <div class="text-sm text-gray-600 mt-1"><?= $top3[2]['points'] ?> points</div>
                        </div>
                    </div>
                    <div class="text-center mt-4">
                        <div class="text-lg font-bold text-amber-600 bg-amber-100 px-4 py-1 rounded-full">3rd</div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Top 10 Leaderboard Table -->
        <div class="mb-16">
            <h3 class="text-2xl font-light text-gray-700 flex items-center mb-6">
                <i class="fas fa-list-ol text-purple-500 mr-3"></i>
                Top Students
            </h3>
            <div class="overflow-hidden rounded-xl shadow-xs border border-gray-200 bg-white">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gradient-to-r from-purple-600 to-indigo-600">
                        <tr>
                            <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-white/90 uppercase tracking-wider">Rank</th>
                            <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-white/90 uppercase tracking-wider">Student</th>
                            <th scope="col" class="px-6 py-4 text-right text-xs font-semibold text-white/90 uppercase tracking-wider">Points</th>
                            <th scope="col" class="px-6 py-4 text-right text-xs font-semibold text-white/90 uppercase tracking-wider">Sessions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-100">
                        <?php 
                        $rank = 1;
                        mysqli_data_seek($leaderboard_result, 0);
                        while($row = mysqli_fetch_assoc($leaderboard_result)): 
                            $highlight = ($row['firstname'] == $firstname && $row['lastname'] == $lastname);
                        ?>
                        <tr class="<?php echo $highlight ? 'highlight-row' : 'hover:bg-gray-50'; ?> transition-colors duration-150">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                <?php if($rank == 1): ?>
                                    <span class="text-yellow-500">🥇</span>
                                <?php elseif($rank == 2): ?>
                                    <span class="text-gray-400">🥈</span>
                                <?php elseif($rank == 3): ?>
                                    <span class="text-amber-600">🥉</span>
                                <?php else: ?>
                                    <?php echo $rank; ?>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                                <?php echo htmlspecialchars($row['firstname'] . ' ' . $row['lastname']); ?>
                                <?php if($highlight): ?>
                                    <span class="text-xs bg-purple-600 text-white px-2 py-0.5 rounded-full ml-2">You</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right font-medium"><?php echo $row['points']; ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-right"><?php echo $row['remaining_sessions']; ?></td>
                        </tr>
                        <?php $rank++; endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
</body>
</html>