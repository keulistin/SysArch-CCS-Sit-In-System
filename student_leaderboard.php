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
        .podium-item {
            transition: all 0.3s ease;
        }
        .podium-item:hover {
            transform: translateY(-5px);
        }
        .highlight-row {
            background-color: rgba(30, 58, 138, 0.2);
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
<body class="bg-gradient-to-br from-slate-800 to-slate-900 min-h-screen font-sans text-white">
    <!-- Sidebar -->
    <div class="fixed inset-y-0 left-0 w-64 bg-slate-900/80 backdrop-blur-md border-r border-white/10 shadow-xl z-50 flex flex-col">
        <!-- Fixed header -->
        <div class="p-5 border-b border-white/10 flex-shrink-0">
            <div class="flex items-center space-x-3">
                <img 
                    src="uploads/<?php echo htmlspecialchars($profile_picture); ?>" 
                    alt="Profile Picture" 
                    class="w-10 h-10 rounded-full border-2 border-white/10 object-cover"
                    onerror="this.src='assets/default_avatar.png'"
                >
                <h2 class="text-xl font-semibold text-white"><?php echo htmlspecialchars($firstname); ?></h2>
            </div>
            <p class="text-sm text-slate-400 mt-2"><?php echo $page_title; ?></p>
        </div>
        
        <!-- Scrollable navigation -->
        <nav class="mt-5 flex-1 overflow-y-auto sidebar-scroll">
            <ul>
                <li>
                    <a href="student_dashboard.php" class="flex items-center px-5 py-3 text-slate-300 hover:bg-slate-700/20 hover:text-white transition-all duration-200 <?php echo basename($_SERVER['PHP_SELF']) === 'student_dashboard.php' ? 'bg-slate-700/20 text-white' : ''; ?>">
                        <span>Profile</span>
                    </a>
                </li>
                <li>
                    <a href="edit-profile.php" class="flex items-center px-5 py-3 text-slate-300 hover:bg-slate-700/20 hover:text-white transition-all duration-200 <?php echo basename($_SERVER['PHP_SELF']) === 'edit-profile.php' ? 'bg-slate-700/20 text-white' : ''; ?>">
                        <span>Edit Profile</span>
                    </a>
                </li>
                <li>
                    <a href="announcements.php" class="flex items-center px-5 py-3 text-slate-300 hover:bg-slate-700/20 hover:text-white transition-all duration-200 <?php echo basename($_SERVER['PHP_SELF']) === 'announcements.php' ? 'bg-slate-700/20 text-white' : ''; ?>">
                        <span>View Announcements</span>
                    </a>
                </li>
                <li>
                    <a href="sit-in-rules.php" class="flex items-center px-5 py-3 text-slate-300 hover:bg-slate-700/20 hover:text-white transition-all duration-200 <?php echo basename($_SERVER['PHP_SELF']) === 'sit-in-rules.php' ? 'bg-slate-700/20 text-white' : ''; ?>">
                        <span>Sit-in Rules</span>
                    </a>
                </li>
                <li>
                    <a href="lab-rules.php" class="flex items-center px-5 py-3 text-slate-300 hover:bg-slate-700/20 hover:text-white transition-all duration-200 <?php echo basename($_SERVER['PHP_SELF']) === 'lab-rules.php' ? 'bg-slate-700/20 text-white' : ''; ?>">
                        <span>Lab Rules & Regulations</span>
                    </a>
                </li>
                <li>
                    <a href="reservation.php" class="flex items-center px-5 py-3 text-slate-300 hover:bg-slate-700/20 hover:text-white transition-all duration-200 <?php echo basename($_SERVER['PHP_SELF']) === 'reservation.php' ? 'bg-slate-700/20 text-white' : ''; ?>">
                        <span>Reservation</span>
                    </a>
                </li>
                <li>
                    <a href="sit_in_history.php" class="flex items-center px-5 py-3 text-slate-300 hover:bg-slate-700/20 hover:text-white transition-all duration-200 <?php echo basename($_SERVER['PHP_SELF']) === 'sit_in_history.php' ? 'bg-slate-700/20 text-white' : ''; ?>">
                        <span>Sit-in History</span>
                    </a>
                </li>
                <li>
                    <a href="upload_resources.php" class="flex items-center px-5 py-3 text-slate-300 hover:bg-slate-700/20 hover:text-white transition-all duration-200 <?php echo basename($_SERVER['PHP_SELF']) === 'upload_resources.php' ? 'bg-slate-700/20 text-white' : ''; ?>">
                        <span>View Lab Resources</span>
                    </a>
                </li>
                <li>
                    <a href="student_leaderboard.php" class="flex items-center px-5 py-3 bg-blue-600/20 text-white transition-all duration-200">
                        <span>Leaderboard</span>
                    </a>
                </li>
                <li>
                    <a href="student_lab_schedule.php" class="flex items-center px-5 py-3 text-slate-300 hover:bg-slate-700/20 hover:text-white transition-all duration-200 <?php echo basename($_SERVER['PHP_SELF']) === 'student_lab_schedule.php' ? 'bg-slate-700/20 text-white' : ''; ?>">
                        <span>Lab Schedule</span>
                    </a>
                </li>
            </ul>
        </nav>
        
        <!-- Fixed footer with logout -->
        <div class="p-5 border-t border-white/10 flex-shrink-0">
            <a href="logout.php" onclick="return confirm('Are you sure you want to log out?')" class="flex items-center px-5 py-3 text-slate-300 hover:bg-red-600/20 hover:text-red-400 transition-all duration-200">
                <span>Log Out</span>
            </a>
        </div>
    </div>

    <!-- Main Content -->
    <div class="ml-64 p-6">
        <div class="bg-slate-800/50 backdrop-blur-sm rounded-xl shadow-lg border border-white/5 p-6 hover:shadow-xl transition-all duration-300">
            <h2 class="text-2xl font-semibold mb-6 text-white border-b border-white/10 pb-2">
                <i class="fas fa-trophy mr-2 text-yellow-400"></i> Leaderboard
            </h2>

            <!-- Student Stats -->
            <div class="bg-slate-700/30 rounded-lg p-6 mb-6 border border-white/10">
                <h3 class="text-xl font-semibold mb-4">Your Ranking</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="bg-slate-600/30 p-4 rounded-lg">
                        <p class="text-sm text-slate-300">Current Rank</p>
                        <p class="text-2xl font-bold">
                            <?php if($student_rank <= 3): ?>
                                <?php if($student_rank == 1): ?>
                                    <span class="text-yellow-400">🥇</span>
                                <?php elseif($student_rank == 2): ?>
                                    <span class="text-gray-300">🥈</span>
                                <?php else: ?>
                                    <span class="text-amber-600">🥉</span>
                                <?php endif; ?>
                            <?php endif; ?>
                            <?php echo $student_rank; ?>
                        </p>
                    </div>
                    <div class="bg-slate-600/30 p-4 rounded-lg">
                        <p class="text-sm text-slate-300">Points</p>
                        <p class="text-2xl font-bold"><?php echo $points; ?></p>
                    </div>
                    <div class="bg-slate-600/30 p-4 rounded-lg">
                        <p class="text-sm text-slate-300">Sessions</p>
                        <p class="text-2xl font-bold"><?php echo $remaining_sessions; ?></p>
                    </div>
                </div>
            </div>

            <!-- Podium for Top 3 Students -->
            <div class="mb-12">
                <h3 class="text-xl font-semibold mb-6 text-center">Top Performers</h3>
                <div class="flex items-end justify-center gap-4 h-64">
                    <!-- 2nd Place -->
                    <?php if (isset($top3[1])): ?>
                    <div class="podium-item flex flex-col items-center w-1/4">
                        <div class="bg-gray-400 w-full rounded-t-lg h-40 flex items-center justify-center relative">
                            <span class="text-4xl">🥈</span>
                            <div class="absolute -bottom-6 w-full text-center">
                                <div class="text-lg font-bold"><?= htmlspecialchars($top3[1]['firstname'].' '.$top3[1]['lastname']) ?></div>
                                <div class="text-sm"><?= $top3[1]['points'] ?> points</div>
                            </div>
                        </div>
                        <div class="text-center mt-8">
                            <div class="text-xl font-bold text-gray-300">2nd</div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- 1st Place -->
                    <?php if (isset($top3[0])): ?>
                    <div class="podium-item flex flex-col items-center w-1/3">
                        <div class="bg-yellow-400 w-full rounded-t-lg h-56 flex items-center justify-center relative">
                            <span class="text-4xl">🥇</span>
                            <div class="absolute -bottom-6 w-full text-center">
                                <div class="text-lg font-bold"><?= htmlspecialchars($top3[0]['firstname'].' '.$top3[0]['lastname']) ?></div>
                                <div class="text-sm"><?= $top3[0]['points'] ?> points</div>
                            </div>
                        </div>
                        <div class="text-center mt-8">
                            <div class="text-xl font-bold text-yellow-400">1st</div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- 3rd Place -->
                    <?php if (isset($top3[2])): ?>
                    <div class="podium-item flex flex-col items-center w-1/4">
                        <div class="bg-amber-600 w-full rounded-t-lg h-32 flex items-center justify-center relative">
                            <span class="text-4xl">🥉</span>
                            <div class="absolute -bottom-6 w-full text-center">
                                <div class="text-lg font-bold"><?= htmlspecialchars($top3[2]['firstname'].' '.$top3[2]['lastname']) ?></div>
                                <div class="text-sm"><?= $top3[2]['points'] ?> points</div>
                            </div>
                        </div>
                        <div class="text-center mt-8">
                            <div class="text-xl font-bold text-amber-600">3rd</div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Top 10 Leaderboard Table -->
            <div class="bg-slate-700/30 rounded-lg p-6 border border-white/10">
                <h3 class="text-xl font-semibold mb-4">Top Students</h3>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b border-white/10">
                                <th class="text-left p-3">Rank</th>
                                <th class="text-left p-3">Student</th>
                                <th class="text-right p-3">Points</th>
                                <th class="text-right p-3">Sessions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $rank = 1;
                            mysqli_data_seek($leaderboard_result, 0); // Reset pointer
                            while($row = mysqli_fetch_assoc($leaderboard_result)): 
                                $highlight = ($row['firstname'] == $firstname && $row['lastname'] == $lastname);
                            ?>
                            <tr class="border-b border-white/5 hover:bg-slate-700/50 <?php echo $highlight ? 'highlight-row' : ''; ?>">
                                <td class="p-3">
                                    <?php if($rank == 1): ?>
                                        <span class="text-yellow-400">🥇</span>
                                    <?php elseif($rank == 2): ?>
                                        <span class="text-gray-300">🥈</span>
                                    <?php elseif($rank == 3): ?>
                                        <span class="text-amber-600">🥉</span>
                                    <?php else: ?>
                                        <?php echo $rank; ?>
                                    <?php endif; ?>
                                </td>
                                <td class="p-3">
                                    <?php echo htmlspecialchars($row['firstname'] . ' ' . $row['lastname']); ?>
                                    <?php if($highlight): ?>
                                        <span class="text-xs bg-blue-600/50 px-2 py-0.5 rounded-full ml-2">You</span>
                                    <?php endif; ?>
                                </td>
                                <td class="p-3 text-right font-medium"><?php echo $row['points']; ?></td>
                                <td class="p-3 text-right font-medium"><?php echo $row['remaining_sessions']; ?></td>
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