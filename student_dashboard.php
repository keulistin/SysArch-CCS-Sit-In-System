<?php
session_start();
include 'db.php';

// Check if the user is logged in
if (!isset($_SESSION['idno'])) {
    header("Location: login.php");
    exit();
}

// Initialize variables with default values
$firstname = $lastname = $profile_picture = $remaining_sessions = $idno = $course = $yearlevel = $email = '';
$profile_picture = 'default_avatar.png';

if (isset($_SESSION['idno'])) {
    $idno = $_SESSION['idno'];
    
    // Fetch student info with additional fields
    $user_query = "SELECT firstname, lastname, profile_picture, remaining_sessions, idno, course, yearlevel, email FROM users WHERE idno = ?";
    $stmt = $conn->prepare($user_query);
    $stmt->bind_param("s", $idno);
    $stmt->execute();
    $stmt->bind_result($firstname, $lastname, $profile_picture, $remaining_sessions, $idno, $course, $yearlevel, $email);
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
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard</title>
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
        .notification-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background-color: #ef4444;
            color: white;
            border-radius: 50%;
            width: 18px;
            height: 18px;
            font-size: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .notification-dropdown {
            max-height: 400px;
            overflow-y: auto;
        }
        .notification-item {
            border-bottom: 1px solid #374151;
        }
        .notification-item:last-child {
            border-bottom: none;
        }
        
        /* NEW: Chatbot styles */
        #chatbot-messages {
            scrollbar-width: thin;
            scrollbar-color: #4b5563 #1e293b;
        }
        #chatbot-messages::-webkit-scrollbar {
            width: 6px;
        }
        #chatbot-messages::-webkit-scrollbar-track {
            background: #1e293b;
        }
        #chatbot-messages::-webkit-scrollbar-thumb {
            background-color: #4b5563;
            border-radius: 3px;
        }
        .chatbot-message {
            word-wrap: break-word;
            max-width: 90%;
        }
    </style>
</head>
<body class="bg-gradient-to-br from-slate-800 to-slate-900 min-h-screen font-sans text-white">
    <!-- Sidebar -->
    <div class="fixed inset-y-0 left-0 w-64 bg-slate-900/80 backdrop-blur-md border-r border-white/10 shadow-xl z-50 flex flex-col">
        <!-- Fixed header -->
        <div class="p-5 border-b border-white/10 flex-shrink-0">
            <div class="flex items-center space-x-3">
                <!-- Profile Picture -->
                <img 
                    src="uploads/<?php echo htmlspecialchars($profile_picture); ?>" 
                    alt="Profile Picture" 
                    class="w-10 h-10 rounded-full border-2 border-white/10 object-cover"
                    onerror="this.src='assets/default_avatar.png'"
                >
                <!-- First Name -->
                <h2 class="text-xl font-semibold text-white"><?php echo htmlspecialchars($firstname); ?></h2>
            </div>
            <p class="text-sm text-slate-400 mt-2">Dashboard</p>
        </div>
        
        <!-- Scrollable navigation -->
        <nav class="mt-5 flex-1 overflow-y-auto sidebar-scroll">
            <ul>
                <li>
                    <a href="student_dashboard.php" class="flex items-center px-5 py-3 bg-slate-700/20 text-white">
                        <span>Profile</span>
                    </a>
                </li>
                <li>
                    <a href="edit-profile.php" class="flex items-center px-5 py-3 text-slate-300 hover:bg-slate-700/20 hover:text-white transition-all duration-200">
                        <span>Edit Profile</span>
                    </a>
                </li>
                <li>
                    <a href="announcements.php" class="flex items-center px-5 py-3 text-slate-300 hover:bg-slate-700/20 hover:text-white transition-all duration-200">
                        <span>View Announcements</span>
                    </a>
                </li>
                <li>
                    <a href="sit-in-rules.php" class="flex items-center px-5 py-3 text-slate-300 hover:bg-slate-700/20 hover:text-white transition-all duration-200">
                        <span>Sit-in Rules</span>
                    </a>
                </li>
                <li>
                    <a href="lab-rules.php" class="flex items-center px-5 py-3 text-slate-300 hover:bg-slate-700/20 hover:text-white transition-all duration-200">
                        <span>Lab Rules & Regulations</span>
                    </a>
                </li>
                <li>
                    <a href="reservation.php" class="flex items-center px-5 py-3 text-slate-300 hover:bg-slate-700/20 hover:text-white transition-all duration-200">
                        <span>Reservation</span>
                    </a>
                </li>
                <li>
                    <a href="sit_in_history.php" class="flex items-center px-5 py-3 text-slate-300 hover:bg-slate-700/20 hover:text-white transition-all duration-200">
                        <span>Sit-in History</span>
                    </a>
                </li>
                <li>
                    <a href="upload_resources.php" class="flex items-center px-5 py-3 text-slate-300 hover:bg-slate-700/20 hover:text-white transition-all duration-200">
                        <span>View Lab Resources</span>
                    </a>
                </li>
                <li>
                    <a href="student_leaderboard.php" class="flex items-center px-5 py-3 text-slate-300 hover:bg-slate-700/20 hover:text-white transition-all duration-200">
                        <span>Leaderboard</span>
                    </a>
                </li>
                <li>
                    <a href="student_lab_schedule.php" class="flex items-center px-5 py-3 text-slate-300 hover:bg-slate-700/20 hover:text-white transition-all duration-200">
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
            <!-- Profile Section - Side-aligned Single Column -->
            <div class="flex flex-col md:flex-row gap-6 mb-8">
                <!-- Profile Picture (centered) -->
                <div class="flex justify-center md:justify-start">
                    <img 
                        src="uploads/<?php echo htmlspecialchars($profile_picture); ?>" 
                        alt="Profile Picture" 
                        class="w-32 h-32 rounded-full border-4 border-white/10 object-cover"
                        onerror="this.src='assets/default_avatar.png'"
                    >
                </div>
                
                <!-- Information in single column on the side -->
                <div class="flex-1">
                    <div class="flex justify-between items-start">
                        <h2 class="text-2xl font-semibold mb-4 text-white">Welcome, <?php echo htmlspecialchars($firstname . " " . $lastname); ?>! 👋</h2>
                        <!-- Notification Button - Aligned to the right of the welcome message -->
                        <div class="relative">
                            <button id="notificationButton" class="text-white hover:text-blue-300 focus:outline-none">
                                <i class="fas fa-bell text-xl"></i>
                                <span class="notification-badge hidden">0</span>
                            </button>
                            <!-- Notification Dropdown -->
                            <div id="notificationDropdown" class="hidden absolute right-0 mt-2 w-64 bg-slate-800 rounded-md shadow-lg z-50 border border-white/10 notification-dropdown">
                                <div class="p-2 border-b border-white/10 flex justify-between items-center">
                                    <span class="font-semibold">Notifications</span>
                                    <button id="markAllRead" class="text-xs text-blue-400 hover:text-blue-300">Mark all as read</button>
                                </div>
                                <div id="notificationList" class="divide-y divide-white/10">
                                    <!-- Notifications will be loaded here -->
                                    <div class="p-3 text-center text-slate-400">No notifications</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="space-y-3 text-sm text-slate-300">
                        <div><strong>ID:</strong> <?php echo htmlspecialchars($idno); ?></div>
                        <div><strong>Year Level:</strong> <?php echo htmlspecialchars($yearlevel); ?></div>
                        <div><strong>Course:</strong> <?php echo htmlspecialchars($course); ?></div>
                        <div><strong>Email:</strong> <?php echo htmlspecialchars($email); ?></div>
                    </div>
                </div>
            </div>

            <!-- Dashboard Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="bg-slate-700/50 p-6 rounded-lg hover:bg-slate-700/70 transition-all duration-200">
                    <h4 class="text-lg font-semibold mb-2">Remaining Sessions</h4>
                    <p class="text-2xl font-bold"><?php echo htmlspecialchars($remaining_sessions); ?></p>
                </div>
                <div class="bg-slate-700/50 p-6 rounded-lg hover:bg-slate-700/70 transition-all duration-200">
                    <h4 class="text-lg font-semibold mb-2">Reservation</h4>
                    <a href="reservation.php" class="text-blue-400 hover:text-blue-300">Reserve Now</a>
                </div>
                <div class="bg-slate-700/50 p-6 rounded-lg hover:bg-slate-700/70 transition-all duration-200">
                    <h4 class="text-lg font-semibold mb-2">Sit-in History</h4>
                    <a href="sit_in_history.php" class="text-blue-400 hover:text-blue-300">View History</a>
                </div>
            </div>
        </div>
    </div>

    <!-- NEW: Chatbot Container -->
    <div id="chatbot-container" class="fixed bottom-6 right-6 z-50">
        <!-- Chatbot Button -->
        <button id="chatbot-toggle" class="w-14 h-14 rounded-full bg-blue-600 hover:bg-blue-700 text-white shadow-lg flex items-center justify-center transition-all duration-300">
            <i class="fas fa-robot text-2xl"></i>
        </button>
        
        <!-- Chatbot Window (hidden by default) -->
        <div id="chatbot-window" class="hidden w-80 h-[500px] bg-slate-800 rounded-lg shadow-xl border border-white/10 flex flex-col absolute bottom-16 right-0">
            <!-- Chatbot Header -->
            <div class="bg-slate-700/50 p-3 rounded-t-lg flex items-center justify-between">
                <div class="flex items-center space-x-2">
                    <i class="fas fa-robot text-blue-400"></i>
                    <h3 class="font-semibold">Lab Assistant</h3>
                </div>
                <button id="chatbot-close" class="text-slate-300 hover:text-white">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <!-- Chat Messages Area -->
            <div id="chatbot-messages" class="flex-1 p-4 overflow-y-auto space-y-3">
                <!-- Welcome message -->
                <div class="chatbot-message bg-slate-700/50 rounded-lg p-3 text-sm">
                    <p>Hello <?php echo htmlspecialchars($firstname); ?>! I'm your Lab Assistant. How can I help you today?</p>
                    <p class="text-xs text-slate-400 mt-1">Here are some things you can ask:</p>
                    <ul class="text-xs text-blue-300 mt-1 list-disc list-inside">
                        <li>How do I make a reservation?</li>
                        <li>What are the lab rules?</li>
                        <li>When is the lab open?</li>
                    </ul>
                </div>
            </div>
            
            <!-- Chat Input Area -->
            <div class="p-3 border-t border-white/10">
                <form id="chatbot-form" class="flex space-x-2">
                    <input 
                        type="text" 
                        id="chatbot-input" 
                        placeholder="Type your question..." 
                        class="flex-1 bg-slate-700/50 border border-white/10 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-blue-500"
                        autocomplete="off"
                    >
                    <button 
                        type="submit" 
                        class="bg-blue-600 hover:bg-blue-700 text-white rounded-lg px-3 py-2 text-sm"
                    >
                        <i class="fas fa-paper-plane"></i>
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const notificationButton = document.getElementById('notificationButton');
            const notificationDropdown = document.getElementById('notificationDropdown');
            const notificationBadge = document.querySelector('.notification-badge');
            
            // Toggle notification dropdown
            notificationButton.addEventListener('click', function(e) {
                e.stopPropagation();
                notificationDropdown.classList.toggle('hidden');
                loadNotifications();
            });
            
            // Close dropdown when clicking outside
            document.addEventListener('click', function() {
                notificationDropdown.classList.add('hidden');
            });
            
            // Prevent dropdown from closing when clicking inside
            notificationDropdown.addEventListener('click', function(e) {
                e.stopPropagation();
            });
            
            // Mark all as read
            document.getElementById('markAllRead').addEventListener('click', function() {
                markAllNotificationsAsRead();
            });
            
            // Function to load notifications
            function loadNotifications() {
                fetch('get_notifications.php')
                    .then(response => response.json())
                    .then(data => {
                        const notificationList = document.getElementById('notificationList');
                        
                        if (data.length === 0) {
                            notificationList.innerHTML = '<div class="p-3 text-center text-slate-400">No notifications</div>';
                            notificationBadge.classList.add('hidden');
                            return;
                        }
                        
                        notificationList.innerHTML = '';
                        let unreadCount = 0;
                        
                        data.forEach(notification => {
                            const notificationItem = document.createElement('div');
                            notificationItem.className = `p-3 notification-item ${notification.is_read ? 'text-slate-400' : 'text-white bg-slate-700/50'}`;
                            notificationItem.innerHTML = `
                                <div class="flex justify-between items-start">
                                    <div class="flex-1">
                                        <p class="text-sm">${notification.message}</p>
                                        <p class="text-xs text-slate-400 mt-1">${notification.created_at}</p>
                                    </div>
                                    ${notification.is_read ? '' : '<span class="w-2 h-2 rounded-full bg-blue-500 ml-2"></span>'}
                                </div>
                            `;
                            notificationList.appendChild(notificationItem);
                            
                            if (!notification.is_read) {
                                unreadCount++;
                            }
                        });
                        
                        if (unreadCount > 0) {
                            notificationBadge.textContent = unreadCount;
                            notificationBadge.classList.remove('hidden');
                        } else {
                            notificationBadge.classList.add('hidden');
                        }
                    });
            }
            
            // Function to mark all notifications as read
            function markAllNotificationsAsRead() {
                fetch('mark_notifications_read.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        loadNotifications();
                    }
                });
            }
            
            // Load notifications on page load
            loadNotifications();

            // Check for new notifications every 30 seconds
            setInterval(loadNotifications, 30000);

            // NEW: Chatbot functionality
            const chatbotToggle = document.getElementById('chatbot-toggle');
            const chatbotWindow = document.getElementById('chatbot-window');
            const chatbotClose = document.getElementById('chatbot-close');
            const chatbotForm = document.getElementById('chatbot-form');
            const chatbotInput = document.getElementById('chatbot-input');
            const chatbotMessages = document.getElementById('chatbot-messages');
            
            // Toggle chatbot window
            chatbotToggle.addEventListener('click', function() {
                chatbotWindow.classList.toggle('hidden');
            });
            
            // Close chatbot window
            chatbotClose.addEventListener('click', function() {
                chatbotWindow.classList.add('hidden');
            });
            
            // Handle form submission
            chatbotForm.addEventListener('submit', function(e) {
                e.preventDefault();
                const message = chatbotInput.value.trim();
                
                if (message) {
                    // Add user message to chat
                    addMessage(message, 'user');
                    chatbotInput.value = '';
                    
                    // Process the message and get bot response
                    processMessage(message);
                }
            });
            
            // Add a message to the chat
            function addMessage(text, sender) {
                const messageDiv = document.createElement('div');
                messageDiv.className = `chatbot-message ${sender === 'user' ? 'bg-blue-600/50 ml-8' : 'bg-slate-700/50 mr-8'} rounded-lg p-3 text-sm`;
                messageDiv.innerHTML = `<p>${text}</p>`;
                chatbotMessages.appendChild(messageDiv);
                chatbotMessages.scrollTop = chatbotMessages.scrollHeight;
            }
            
            // Process user message and generate response
            function processMessage(message) {
                // Show typing indicator
                const typingIndicator = document.createElement('div');
                typingIndicator.className = 'chatbot-message bg-slate-700/50 mr-8 rounded-lg p-3 text-sm';
                typingIndicator.innerHTML = '<p class="italic">Lab Assistant is typing...</p>';
                chatbotMessages.appendChild(typingIndicator);
                chatbotMessages.scrollTop = chatbotMessages.scrollHeight;
                
                // Remove typing indicator after delay
                setTimeout(() => {
                    typingIndicator.remove();
                    
                    // Generate response based on user message
                    const response = generateResponse(message.toLowerCase());
                    
                    // Add bot response to chat
                    addMessage(response, 'bot');
                }, 1000);
            }
            
            // Simple response generation (you can expand this)
            function generateResponse(message) {
                // Reservation related questions
                if (message.includes('reserve') || message.includes('reservation') || message.includes('book')) {
                    return "You can make a reservation by going to the 'Reservation' page in the sidebar. There you can select a date, time, and computer station. You have <?php echo htmlspecialchars($remaining_sessions); ?> sessions remaining.";
                }
                
                // Lab rules questions
                else if (message.includes('rule') || message.includes('regulation')) {
                    return "You can find all lab rules and regulations on the 'Lab Rules & Regulations' page in the sidebar. This includes policies on food/drinks, equipment use, and behavior guidelines.";
                }
                
                // Schedule questions
                else if (message.includes('schedule') || message.includes('open') || message.includes('time')) {
                    return "The lab schedule is available on the 'Lab Schedule' page. Typically, the lab is open from 7:30 AM to 9:00 PM on Monday to Saturday.";
                }
                
                // Sit-in questions
                else if (message.includes('sit-in') || message.includes('sit in')) {
                    return "Sit-in sessions allow you to use the lab without a reservation when seats are available. Check the 'Sit-in Rules' page for details. Your history is available under 'Sit-in History'.";
                }
                
                // Resources questions
                else if (message.includes('resource') || message.includes('material') || message.includes('file')) {
                    return "Available lab resources can be found on the 'View Lab Resources' page. This includes software, manuals, and course materials.";
                }
                
                // Greetings
                else if (message.includes('hi') || message.includes('hello') || message.includes('hey')) {
                    return "Hello <?php echo htmlspecialchars($firstname); ?>! How can I help you with the computer lab today?";
                }
                
                // Default response
                else {
                    return "I'm not sure I understand. You can ask me about reservations, lab rules, schedules, or sit-in sessions. Try asking something like 'How do I make a reservation?'";
                }
            }
        });
    </script>
</body>
</html>