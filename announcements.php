<?php
session_start();
include 'db.php';

// Check if the user is logged in
if (!isset($_SESSION['idno'])) {
    header("Location: login.php");
    exit();
}

$idno = $_SESSION['idno'];

// Fetch student info
$user_query = "SELECT firstname, lastname, profile_picture FROM users WHERE idno = ?";
$stmt = $conn->prepare($user_query);
$stmt->bind_param("s", $idno);
$stmt->execute();
$stmt->bind_result($firstname, $lastname, $profile_picture);
$stmt->fetch();
$stmt->close();

// Set default profile picture if none exists
if (empty($profile_picture)) {
    $upload_dir = 'uploads/';
    $images = glob($upload_dir . '*.{jpg,jpeg,png,gif}', GLOB_BRACE);

    if (!empty($images)) {
        $random_image = $images[array_rand($images)];
        $profile_picture = basename($random_image);

        $update_sql = "UPDATE users SET profile_picture = ? WHERE idno = ?";
        if ($update_stmt = $conn->prepare($update_sql)) {
            $update_stmt->bind_param("ss", $profile_picture, $idno);
            $update_stmt->execute();
            $update_stmt->close();
        }
    } else {
        $profile_picture = "default_avatar.png";
    }
}

// Fetch all announcements
$announcement_query = "SELECT * FROM announcements ORDER BY created_at DESC";
$announcement_result = mysqli_query($conn, $announcement_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Announcements</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
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
    <style>
        /* Custom scrollbar for sidebar */
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
        .announcement-card {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .announcement-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2);
        }
        .dark-mode .announcement-card {
            background: rgba(30, 30, 30, 0.5);
            border-color: rgba(255, 255, 255, 0.1);
        }
    </style>
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
        Profile
      </a>

      <!-- Rules Dropdown -->
      <div class="relative group">
        <button class="px-4 py-2 text-gray-700 font-medium hover:bg-gray-100 rounded-md transition-all duration-200 flex items-center">
          Rules
          <i class="fas fa-chevron-down ml-2 text-xs"></i>
        </button>
        <div class="absolute left-0 mt-2 w-48 bg-white rounded-md shadow-lg py-1 z-10 hidden group-hover:block">
          <a href="sit-in-rules.php" class="block px-4 py-2 text-gray-700 hover:bg-gray-100">Sit-in Rules</a>
          <a href="lab-rules.php" class="block px-4 py-2 text-gray-700 hover:bg-gray-100">Lab Rules</a>
        </div>
      </div>

      <!-- Sit-ins Dropdown -->
      <div class="relative group">
        <button class="px-4 py-2 text-gray-700 font-medium hover:bg-gray-100 rounded-md transition-all duration-200 flex items-center">
          Sit-ins
          <i class="fas fa-chevron-down ml-2 text-xs"></i>
        </button>
        <div class="absolute left-0 mt-2 w-48 bg-white rounded-md shadow-lg py-1 z-10 hidden group-hover:block">
          <a href="reservation.php" class="block px-4 py-2 text-gray-700 hover:bg-gray-100">Reservation</a>
          <a href="sit_in_history.php" class="block px-4 py-2 text-gray-700 hover:bg-gray-100">History</a>
        </div>
      </div>

      <!-- Resources Dropdown -->
      <div class="relative group">
        <button class="px-4 py-2 text-gray-700 font-medium hover:bg-gray-100 rounded-md transition-all duration-200 flex items-center">
          Resources
          <i class="fas fa-chevron-down ml-2 text-xs"></i>
        </button>
        <div class="absolute left-0 mt-2 w-48 bg-white rounded-md shadow-lg py-1 z-10 hidden group-hover:block">
          <a href="upload_resources.php" class="block px-4 py-2 text-gray-700 hover:bg-gray-100">View Resources</a>
          <a href="student_leaderboard.php" class="block px-4 py-2 text-gray-700 hover:bg-gray-100">Leaderboard</a>
          <a href="student_lab_schedule.php" class="block px-4 py-2 text-gray-700 hover:bg-gray-100">Lab Schedule</a>
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
        <img 
          src="uploads/<?php echo htmlspecialchars($profile_picture); ?>" 
          alt="Avatar" 
          class="w-10 h-10 rounded-full object-cover border-2 border-purple-700"
          onerror="this.src='assets/default_avatar.png'"
        >
      </div>
      <h2 class="font-bold text-gray-700"><?php echo htmlspecialchars($firstname); ?></h2>

      <!-- Logout -->
      <a href="logout.php" class="flex items-center px-4 py-2 bg-purple-600 text-white rounded-full hover:bg-purple-700 transition-all duration-200 shadow-md">
        <i class="fas fa-sign-out-alt mr-2"></i>
        <span class="hidden md:inline">Log Out</span>
      </a>
    </div>
  </div>
</div>

    <!-- Main Content -->
    <div class="pt-24 px-20 pb-8">
        <h2 class="text-3xl font-semibold text-gray-800">Announcements 📢</h2>
        <p class="text-center text-slate-500 mb-6">Stay informed with the latest updates.</p>

        <?php if (mysqli_num_rows($announcement_result) > 0): ?>
            <div class="space-y-4">
                <?php while ($announcement = mysqli_fetch_assoc($announcement_result)): ?>
                    <div class="announcement-card p-6 rounded-lg">
                        <h4 class="text-xl font-semibold mb-2"><?php echo htmlspecialchars($announcement['title']); ?></h4>
                        <p class="text-slate-300 mb-4"><?php echo nl2br(htmlspecialchars($announcement['message'])); ?></p>
                        <p class="text-sm text-slate-400"><small>Posted on: <?php echo $announcement['created_at']; ?></small></p>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <p class="text-center text-slate-300">No announcements available.</p>
        <?php endif; ?>
    </div>
</body>
</html>