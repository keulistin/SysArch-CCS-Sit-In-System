<?php
session_start();
include 'db.php';

/**
 * Export sit-in records to CSV format
 */
function exportToCSV($records) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="sit_in_records_' . date('Y-m-d') . '.csv"');
    
    $output = fopen('php://output', 'w');
    
    // Add UTF-8 BOM for Excel compatibility
    fputs($output, "\xEF\xBB\xBF");
    
    // Add title headers
    fputcsv($output, ['University of Cebu-Main']);
    fputcsv($output, ['College of Computer Studies']);
    fputcsv($output, ['Computer Laboratory Sitin Monitoring System Report']);
    fputcsv($output, []); // Empty line
    
    // Add column headers
    fputcsv($output, ['Student Name', 'Student ID', 'Purpose', 'Lab', 'Start Time', 'End Time']);
    
    // Add data rows
    foreach ($records as $record) {
        $end_time = $record['end_time'] ? 
            date("F d, Y h:i A", strtotime($record['end_time'])) : 
            'Still Active';
            
        fputcsv($output, [
            $record['firstname'] . ' ' . $record['lastname'],
            $record['idno'],
            $record['purpose'],
            $record['lab'],
            date("F d, Y h:i A", strtotime($record['start_time'])),
            $end_time
        ]);
    }
    
    fclose($output);
    exit;
}

/**
 * Export sit-in records to Excel format (HTML table that Excel can open)
 */
function exportToExcel($records) {
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="sit_in_records_' . date('Y-m-d') . '.xls"');
    
    echo '<html xmlns:x="urn:schemas-microsoft-com:office:excel">
    <head>
        <meta charset="UTF-8">
        <style>
            .title { font-weight: bold; text-align: center; font-size: 16px; }
            .subtitle { text-align: center; }
            table { border-collapse: collapse; width: 100%; }
            th { background-color: #f2f2f2; font-weight: bold; text-align: left; }
            th, td { border: 1px solid #dddddd; padding: 8px; }
        </style>
    </head>
    <body>
        <div class="title">University of Cebu-Main</div>
        <div class="title">College of Computer Studies</div>
        <div class="subtitle">Computer Laboratory Sitin Monitoring System Report</div>
        <br>
        
        <table>
            <tr>
                <th>Student Name</th>
                <th>Student ID</th>
                <th>Purpose</th>
                <th>Lab</th>
                <th>Start Time</th>
                <th>End Time</th>
            </tr>';
    
    foreach ($records as $record) {
        $end_time = $record['end_time'] ? 
            date("F d, Y h:i A", strtotime($record['end_time'])) : 
            'Still Active';
            
        echo '<tr>
            <td>' . htmlspecialchars($record['firstname'] . ' ' . $record['lastname']) . '</td>
            <td>' . htmlspecialchars($record['idno']) . '</td>
            <td>' . htmlspecialchars($record['purpose']) . '</td>
            <td>' . htmlspecialchars($record['lab']) . '</td>
            <td>' . date("F d, Y h:i A", strtotime($record['start_time'])) . '</td>
            <td>' . htmlspecialchars($end_time) . '</td>
        </tr>';
    }
    
    echo '</table>
    </body>
    </html>';
    exit;
}

/**
 * Export sit-in records to PDF format (returns JSON for client-side generation)
 */
function exportToPDF($records) {
    // Prepare data for JavaScript
    $jsData = [
        'title' => 'Sit-in Records',
        'headers' => ['University of Cebu-Main', 'College of Computer Studies', 
                     'Computer Laboratory Sitin Monitoring System Report',
                     ], 
        'columns' => ['Student Name', 'Student ID', 'Purpose', 'Lab', 'Start Time', 'End Time'],
        'rows' => []
    ];
    
    foreach ($records as $record) {
        $end_time = $record['end_time'] ? 
            date("F d, Y h:i A", strtotime($record['end_time'])) : 
            'Still Active';
            
        $jsData['rows'][] = [
            htmlspecialchars($record['firstname'] . ' ' . $record['lastname']),
            htmlspecialchars($record['idno']),
            htmlspecialchars($record['purpose']),
            htmlspecialchars($record['lab']),
            date("F d, Y h:i A", strtotime($record['start_time'])),
            htmlspecialchars($end_time)
        ];
    }
    
    // Return JSON data for client-side processing
    header('Content-Type: application/json');
    echo json_encode($jsData);
    exit;
}

/**
 * Generate printable HTML view
 */
function generatePrintView($records) {
    header('Content-Type: text/html');
    
    echo '<!DOCTYPE html>
    <html>
    <head>
        <title>Sit-in Records - Print View</title>
        <style>
            body { font-family: Arial; margin: 20px; }
            .header { text-align: center; margin-bottom: 20px; }
            .title { font-weight: bold; font-size: 18px; }
            .subtitle { font-size: 14px; }
            table { width: 100%; border-collapse: collapse; margin-top: 20px; }
            th, td { border: 1px solid #000; padding: 8px; text-align: left; }
            th { background-color: #f2f2f2; }
            @media print {
                @page { size: A4 landscape; margin: 10mm; }
                body { margin: 0; padding: 0; }
                .no-print { display: none; }
            }
        </style>
    </head>
    <body>
        <div class="header">
            <div class="title">University of Cebu-Main</div>
            <div class="title">College of Computer Studies</div>
            <div class="subtitle">Computer Laboratory Sitin Monitoring System Report</div>
        </div>
        
        <button class="no-print" onclick="window.print()" style="padding: 8px 16px; background: #4CAF50; color: white; border: none; border-radius: 4px; cursor: pointer;">
            Print Report
        </button>
        <button class="no-print" onclick="window.close()" style="padding: 8px 16px; background: #f44336; color: white; border: none; border-radius: 4px; cursor: pointer;">
            Close Window
        </button>
        
        <table>
            <thead>
                <tr>
                    <th>Student Name</th>
                    <th>Student ID</th>
                    <th>Purpose</th>
                    <th>Lab</th>
                    <th>Start Time</th>
                    <th>End Time</th>
                </tr>
            </thead>
            <tbody>';
    
    foreach ($records as $record) {
        $end_time = $record['end_time'] ? 
            date("F d, Y h:i A", strtotime($record['end_time'])) : 
            'Still Active';
            
        echo '<tr>
            <td>' . htmlspecialchars($record['firstname'] . ' ' . $record['lastname']) . '</td>
            <td>' . htmlspecialchars($record['idno']) . '</td>
            <td>' . htmlspecialchars($record['purpose']) . '</td>
            <td>' . htmlspecialchars($record['lab']) . '</td>
            <td>' . date("F d, Y h:i A", strtotime($record['start_time'])) . '</td>
            <td>' . htmlspecialchars($end_time) . '</td>
        </tr>';
    }
    
    echo '</tbody>
        </table>
        <script>
            window.onload = function() {
                setTimeout(function() {
                    window.print();
                }, 500);
            };
        </script>
    </body>
    </html>';
    exit;
}

// Ensure only admins can access
if (!isset($_SESSION["idno"]) || $_SESSION["role"] !== "admin") {
    header("Location: login.php");
    exit();
}

// Get admin info
$idno = $_SESSION["idno"];
$stmt = $conn->prepare("SELECT firstname, lastname FROM users WHERE idno = ?");
$stmt->bind_param("s", $idno);
$stmt->execute();
$stmt->bind_result($firstname, $lastname);
$stmt->fetch();
$stmt->close();

// Initialize filter variables
$lab_filter = isset($_GET['lab_filter']) ? $_GET['lab_filter'] : '';
$date_filter = isset($_GET['date_filter']) ? $_GET['date_filter'] : '';
$purpose_filter = isset($_GET['purpose_filter']) ? $_GET['purpose_filter'] : '';

// Build the main query for displaying records
$query = "SELECT sr.id, sr.purpose, sr.lab, sr.start_time, sr.end_time,
                 u.firstname, u.lastname, u.idno
          FROM sit_in_records sr
          JOIN users u ON sr.student_id = u.id";

// Add filters if they exist
$where_clauses = [];
if (!empty($lab_filter)) {
    $where_clauses[] = "sr.lab = '" . $conn->real_escape_string($lab_filter) . "'";
}
if (!empty($date_filter)) {
    $where_clauses[] = "DATE(sr.start_time) = '" . $conn->real_escape_string($date_filter) . "'";
}
if (!empty($purpose_filter)) {
    $where_clauses[] = "sr.purpose = '" . $conn->real_escape_string($purpose_filter) . "'";
}

if (!empty($where_clauses)) {
    $query .= " WHERE " . implode(" AND ", $where_clauses);
}

$query .= " ORDER BY sr.start_time DESC";
$result = $conn->query($query);
$sit_in_history = $result->fetch_all(MYSQLI_ASSOC);

// Handle export actions
if (isset($_GET['export'])) {
    $export_type = $_GET['export'];
    
    // Reuse the same query with filters for export
    $export_query = $query;
    $export_result = $conn->query($export_query);
    $export_data = $export_result->fetch_all(MYSQLI_ASSOC);
    
    switch ($export_type) {
        case 'csv':
            exportToCSV($export_data);
            break;
        case 'excel':
            exportToExcel($export_data);
            break;
        case 'pdf':
            exportToPDF($export_data);
            break;
        case 'print':
            generatePrintView($export_data);
            break;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Sit-in Records</title>
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
    </style>
</head>
<body class="bg-gradient-to-br from-slate-800 to-slate-900 min-h-screen font-sans text-white">
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

<!-- Main Content -->
<div class="min-h-screen bg-purple-100 main-content-cont">
        <div class="max-w-7xl mx-auto">
        
        <!-- Header Section with Decorative Elements -->
        <div class="mb-10">
            <div class="flex items-center justify-between mb-4">
                <div>
                <h2 class="text-3xl font-medium text-gray-800 tracking-tight">Sit-In Records</h2>
                <p class="text-gray-500 font-light mt-2">View and manage student lab sessions</p>
                </div>

            </div>
            <div class="w-20 h-1 bg-gradient-to-r from-purple-400 to-indigo-400 rounded-full"></div>
        </div>

        <div class="grid grid-cols-3 gap-4 mb-8">
    <div class="bg-white p-4 rounded-lg border border-gray-200 shadow-xs">
        <p class="text-sm text-gray-500">Active Sessions</p>
        <p class="text-2xl font-semibold text-purple-600">12</p>
    </div>
    <div class="bg-white p-4 rounded-lg border border-gray-200 shadow-xs">
        <p class="text-sm text-gray-500">Most Used Lab</p>
        <p class="text-2xl font-semibold text-indigo-600">Lab 524</p>
    </div>
    <div class="bg-white p-4 rounded-lg border border-gray-200 shadow-xs">
        <p class="text-sm text-gray-500">Popular Purpose</p>
        <p class="text-2xl font-semibold text-blue-600">C Programming</p>
    </div>
</div>

        <!-- Filter Controls - Elegant Design -->
        <div class="bg-gradient-to-r from-purple-400 to-indigo-400 rounded-lg p-6 mb-8 border border-gray-200">
            <form id="filter-form" method="get" class="space-y-4 md:space-y-0 md:grid md:grid-cols-3 md:gap-6">
                <!-- Date Filter -->
                <div>
                    <label class="block text-sm font-medium text-white mb-1">Date</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-calendar text-gray-400"></i>
                        </div>
                        <input 
                            type="date" 
                            name="date_filter" 
                            class="w-full pl-10 pr-4 py-2 bg-white border border-gray-200 rounded-lg text-gray-700 placeholder-gray-400 focus:ring-2 focus:ring-purple-200 focus:border-transparent" 
                            value="<?php echo htmlspecialchars($date_filter); ?>"
                            onchange="this.form.submit()"
                        >
                    </div>
                </div>
                

                
                <!-- Lab Filter -->
                <div>
                    <label class="block text-sm font-medium text-white mb-1">Lab Room</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-door-open text-gray-400"></i>
                        </div>
                        <select 
                            name="lab_filter" 
                            class="w-full pl-10 pr-4 py-2 bg-white border border-gray-200 rounded-lg text-gray-700 placeholder-gray-400 focus:ring-2 focus:ring-purple-200 focus:border-transparent appearance-none"
                            onchange="this.form.submit()"
                        >
                            <option value="">All Labs</option>
                            <option value="Lab 517" <?php echo $lab_filter == 'Lab 517' ? 'selected' : ''; ?>>Lab 517</option>
                            <option value="Lab 524" <?php echo $lab_filter == 'Lab 524' ? 'selected' : ''; ?>>Lab 524</option>
                            <option value="Lab 526" <?php echo $lab_filter == 'Lab 526' ? 'selected' : ''; ?>>Lab 526</option>
                            <option value="Lab 528" <?php echo $lab_filter == 'Lab 528' ? 'selected' : ''; ?>>Lab 528</option>
                            <option value="Lab 530" <?php echo $lab_filter == 'Lab 530' ? 'selected' : ''; ?>>Lab 530</option>
                            <option value="Lab 542" <?php echo $lab_filter == 'Lab 542' ? 'selected' : ''; ?>>Lab 542</option>
                            <option value="Lab 544" <?php echo $lab_filter == 'Lab 544' ? 'selected' : ''; ?>>Lab 544</option>
                        </select>
                    </div>
                </div>
                
                <!-- Purpose Filter -->
                <div>
                    <label class="block text-sm font-medium text-white mb-1">Purpose</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-bullseye text-gray-400"></i>
                        </div>
                        <select 
                            name="purpose_filter" 
                            class="w-full pl-10 pr-4 py-2 bg-white border border-gray-200 rounded-lg text-gray-700 placeholder-gray-400 focus:ring-2 focus:ring-purple-200 focus:border-transparent appearance-none"
                            onchange="this.form.submit()"
                        >
                            <option value="">All Purposes</option>
                            <option value="C Programming" <?php echo $purpose_filter == 'C Programming' ? 'selected' : ''; ?>>C Programming</option>
                            <option value="Java Programming" <?php echo $purpose_filter == 'Java Programming' ? 'selected' : ''; ?>>Java Programming</option>
                            <option value="C# Programming" <?php echo $purpose_filter == 'C# Programming' ? 'selected' : ''; ?>>C# Programming</option>
                            <option value="Systems Integration & Architecture" <?php echo $purpose_filter == 'Systems Integration & Architecture' ? 'selected' : ''; ?>>Systems Integration & Architecture</option>
                            <option value="Embedded Systems & IoT" <?php echo $purpose_filter == 'Embedded Systems & IoT' ? 'selected' : ''; ?>>Embedded Systems & IoT</option>
                            <option value="Computer Application" <?php echo $purpose_filter == 'Computer Application' ? 'selected' : ''; ?>>Computer Application</option>
                            <option value="Database" <?php echo $purpose_filter == 'Database' ? 'selected' : ''; ?>>Database</option>
                            <option value="Project Management" <?php echo $purpose_filter == 'Project Management' ? 'selected' : ''; ?>>Project Management</option>
                            <option value="Python Programming" <?php echo $purpose_filter == 'Python Programming' ? 'selected' : ''; ?>>Python Programming</option>
                            <option value="Mobile Application" <?php echo $purpose_filter == 'Mobile Application' ? 'selected' : ''; ?>>Mobile Application</option>
                            <option value="Web Design" <?php echo $purpose_filter == 'Web Design' ? 'selected' : ''; ?>>Web Design</option>
                            <option value="Php Programming" <?php echo $purpose_filter == 'Php Programming' ? 'selected' : ''; ?>>Php Programming</option>
                            <option value="Other" <?php echo $purpose_filter == 'Other' ? 'selected' : ''; ?>>Others...</option>
                        </select>
                    </div>
                </div>
            </form>
        </div>

<!-- Export Buttons - Right Aligned with Enhanced Style -->
<div class="flex flex-wrap justify-end gap-3 mb-8">
    <a href="?export=csv<?php echo !empty($lab_filter) ? '&lab_filter=' . urlencode($lab_filter) : ''; ?><?php echo !empty($date_filter) ? '&date_filter=' . urlencode($date_filter) : ''; ?><?php echo !empty($purpose_filter) ? '&purpose_filter=' . urlencode($purpose_filter) : ''; ?>" 
       class="flex items-center px-4 py-2 bg-white border border-gray-200 text-gray-700 rounded-lg hover:bg-gray-50 transition-all duration-200 shadow-xs hover:-translate-y-0.5 hover:shadow-sm">
        <i class="fas fa-file-csv text-purple-600 mr-2 text-sm"></i> 
        <span class="text-sm font-medium">CSV</span>
    </a>
    <a href="?export=excel<?php echo !empty($lab_filter) ? '&lab_filter=' . urlencode($lab_filter) : ''; ?><?php echo !empty($date_filter) ? '&date_filter=' . urlencode($date_filter) : ''; ?><?php echo !empty($purpose_filter) ? '&purpose_filter=' . urlencode($purpose_filter) : ''; ?>" 
       class="flex items-center px-4 py-2 bg-white border border-gray-200 text-gray-700 rounded-lg hover:bg-gray-50 transition-all duration-200 shadow-xs hover:-translate-y-0.5 hover:shadow-sm">
        <i class="fas fa-file-excel text-green-600 mr-2 text-sm"></i>
        <span class="text-sm font-medium">Excel</span>
    </a>
    <a href="#" onclick="exportToPDF(event)" 
       class="flex items-center px-4 py-2 bg-white border border-gray-200 text-gray-700 rounded-lg hover:bg-gray-50 transition-all duration-200 shadow-xs hover:-translate-y-0.5 hover:shadow-sm">
        <i class="fas fa-file-pdf text-red-600 mr-2 text-sm"></i>
        <span class="text-sm font-medium">PDF</span>
    </a>
    <a href="?export=print<?php echo !empty($lab_filter) ? '&lab_filter=' . urlencode($lab_filter) : ''; ?><?php echo !empty($date_filter) ? '&date_filter=' . urlencode($date_filter) : ''; ?><?php echo !empty($purpose_filter) ? '&purpose_filter=' . urlencode($purpose_filter) : ''; ?>" target="_blank" 
       class="flex items-center px-4 py-2 bg-white border border-gray-200 text-gray-700 rounded-lg hover:bg-gray-50 transition-all duration-200 shadow-xs hover:-translate-y-0.5 hover:shadow-sm">
        <i class="fas fa-print text-blue-600 mr-2 text-sm"></i>
        <span class="text-sm font-medium">Print</span>
    </a>
</div>

        <!-- Sit-in Records Table - Elegant Design -->
        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gradient-to-r from-purple-400 to-indigo-400">
                    <tr>
                        <th class="px-6 py-3 text-left text-s font-medium text-white uppercase tracking-wider">Student</th>
                        <th class="px-6 py-3 text-left text-s font-medium text-white uppercase tracking-wider">ID</th>
                        <th class="px-6 py-3 text-left text-s font-medium text-white uppercase tracking-wider">Purpose</th>
                        <th class="px-6 py-3 text-left text-s font-medium text-white uppercase tracking-wider">Lab</th>
                        <th class="px-6 py-3 text-left text-s font-medium text-white uppercase tracking-wider">Start Time</th>
                        <th class="px-6 py-3 text-left text-s font-medium text-white uppercase tracking-wider">End Time</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-100">
                    <?php if (empty($sit_in_history)) { ?>
                        <tr>
                            <td colspan="6" class="px-6 py-4 text-center text-gray-500">
                                <div class="flex flex-col items-center justify-center py-6">
                                    <div class="bg-gray-100 p-4 rounded-full mb-3">
                                        <i class="fas fa-inbox text-gray-400 text-xl"></i>
                                    </div>
                                    <h4 class="text-lg font-medium text-gray-500">No records found</h4>
                                    <p class="text-sm text-gray-400">When students complete lab sessions, their records will appear here</p>
                                </div>
                            </td>
                        </tr>
                    <?php } else { ?>
                        <?php foreach ($sit_in_history as $record) { ?>
                            <tr class="hover:bg-gray-50 transition-colors duration-150">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-10 w-10 bg-purple-100 rounded-full flex items-center justify-center text-purple-600">
                                            <i class="fas fa-user"></i>
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($record['firstname'] . ' ' . $record['lastname']); ?></div>
                                            <div class="text-sm text-gray-500"><?php echo htmlspecialchars($record['email'] ?? ''); ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php echo htmlspecialchars($record['idno']); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php echo htmlspecialchars($record['purpose']); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php echo htmlspecialchars($record['lab']); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php echo date("M d, Y h:i A", strtotime($record['start_time'])); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-500">
                                    <?php 
                                        echo $record['end_time'] ? 
                                            date("M d, Y h:i A", strtotime($record['end_time'])) : 
                                            '<span class="text-green-600 bg-green-100 px-2 py-1 rounded-full text-xs">Active</span>'; 
                                    ?>
                                </td>
                            </tr>
                        <?php } ?>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

    <!-- PDF Generation Script -->
    <script>
        // Function to handle PDF export
        function exportToPDF(event) {
            event.preventDefault();
            
            // Get current filters
            const labFilter = document.querySelector('[name="lab_filter"]').value;
            const dateFilter = document.querySelector('[name="date_filter"]').value;
            const purposeFilter = document.querySelector('[name="purpose_filter"]').value;
            
            // Build export URL
            let url = '?export=pdf';
            if (labFilter) url += '&lab_filter=' + encodeURIComponent(labFilter);
            if (dateFilter) url += '&date_filter=' + encodeURIComponent(dateFilter);
            if (purposeFilter) url += '&purpose_filter=' + encodeURIComponent(purposeFilter);
            
            // Check if jsPDF is already loaded
            if (window.jspdf) {
                fetchAndGeneratePDF(url);
            } else {
                // Load jsPDF dynamically
                const script = document.createElement('script');
                script.src = 'https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js';
                script.onload = function() {
                    const autoTableScript = document.createElement('script');
                    autoTableScript.src = 'https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.25/jspdf.plugin.autotable.min.js';
                    autoTableScript.onload = function() {
                        fetchAndGeneratePDF(url);
                    };
                    document.head.appendChild(autoTableScript);
                };
                document.head.appendChild(script);
            }
        }

        function fetchAndGeneratePDF(url) {
            fetch(url)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    generatePDF(data);
                })
                .catch(error => {
                    console.error('Error generating PDF:', error);
                    alert('Error generating PDF. Please try again.');
                });
        }

        // Function to generate PDF from data
        function generatePDF(data) {
            const { jsPDF } = window.jspdf;
            const doc = new jsPDF({
                orientation: 'landscape'
            });

            // Add headers
            doc.setFontSize(16);
            doc.setFont('helvetica', 'bold');
            doc.text(data.headers[0], doc.internal.pageSize.width / 2, 15, { align: 'center' });
            doc.text(data.headers[1], doc.internal.pageSize.width / 2, 22, { align: 'center' });
            
            doc.setFontSize(12);
            doc.setFont('helvetica', 'normal');
            doc.text(data.headers[2], doc.internal.pageSize.width / 2, 29, { align: 'center' });

            // AutoTable configuration
            doc.autoTable({
                head: [data.columns],
                body: data.rows,
                startY: 40,
                theme: 'grid',
                headStyles: {
                    fillColor: [61, 71, 79], // Dark gray color
                    textColor: 255, // White text
                    fontStyle: 'bold'
                },
                styles: {
                    fontSize: 9,
                    cellPadding: 2,
                    overflow: 'linebreak'
                },
                margin: { left: 10, right: 10 }
            });

            // Save the PDF
            doc.save('sit_in_records_' + new Date().toISOString().slice(0, 10) + '.pdf');
        }

               //dropdown
                    function toggleDropdown(id) {
        const dropdown = document.getElementById(id);
        dropdown.classList.toggle('hidden');
        document.querySelectorAll('.nav-dropdown').forEach(el => {
            if (el.id !== id) el.classList.add('hidden');
        });
        }

        document.addEventListener('click', function(event) {
        if (!event.target.closest('.relative')) {
            document.querySelectorAll('.nav-dropdown').forEach(el => el.classList.add('hidden'));
        }
        });

        function editAnnouncement(id, title, message) {
            document.getElementById("editId").value = id;
            document.getElementById("editTitle").value = title;
            document.getElementById("editMessage").value = message;
            document.getElementById("editModal").classList.remove("hidden");
        }   
    </script>
</body>
</html>