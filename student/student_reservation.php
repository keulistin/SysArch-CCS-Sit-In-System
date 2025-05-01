<?php
require '../db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
    $input = json_decode(file_get_contents('php://input'), true);
    $user_id = $input['user_id'] ?? null;

    if (!$user_id) {
        echo json_encode(["error" => "User ID not provided!"]);
        exit;
    }

    // GET RESERVATIONS (added to the same script)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($input['get_reservations'])) {
    $user_id = $input['user_id'] ?? null;

    if (!$user_id) {
        echo json_encode([]);
        exit;
    }

    $stmt = $conn->prepare("SELECT s.student_idno FROM student s WHERE s.user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if (!($row = $result->fetch_assoc())) {
        echo json_encode([]);
        exit;
    }

    $student_idno = $row['student_idno'];

    $stmt = $conn->prepare("SELECT reservation_no, lab_room, seat_no, reservation_date, reservation_time, reservation_status FROM lab_reservation WHERE student_idno = ?");
    $stmt->bind_param("i", $student_idno);
    $stmt->execute();
    $result = $stmt->get_result();

    $reservations = [];
    while ($row = $result->fetch_assoc()) {
        $reservations[] = $row;
    }

    echo json_encode($reservations);
    exit;
}

    // FETCH STUDENT INFO
    if (!isset($input['submit_reservation'])) {
        $sql_account = "SELECT CONCAT(u.first_name, ' ', u.middle_name, ' ', u.last_name) AS full_name,
                        u.email,
                        s.course,
                        s.year_level,
                        s.student_idno,
                        s.remaining_sitin
                        FROM user u
                        JOIN student s ON u.user_id = s.user_id
                        WHERE u.user_id = ?";
        $stmt = $conn->prepare($sql_account);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($row = $result->fetch_assoc()) {
            echo json_encode($row);
        } else {
            echo json_encode(["error" => "No user found"]);
        }
        exit;
    }

   // SUBMIT RESERVATION
$lab_room = $input['lab_room'] ?? '';
$seat_no = $input['seat_no'] ?? '';
$purpose = $input['sitin_purpose'] ?? '';
$date = $input['date'] ?? '';
$time = $input['time'] ?? '';

// Get student_idno
$stmt = $conn->prepare("SELECT student_idno FROM student WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
if (!($row = $result->fetch_assoc())) {
    echo json_encode(["error" => "Student not found"]);
    exit;
}

$student_idno = $row['student_idno'];

// Generate random 6-digit reservation number
$reservation_no = mt_rand(100000, 999999);

// Insert reservation
$stmt = $conn->prepare("INSERT INTO lab_reservation (reservation_no, student_idno, user_id, lab_room, seat_no, sitin_purpose, reservation_date, reservation_time, reservation_status)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Pending')");
$stmt->bind_param("iiisssss", $reservation_no, $student_idno, $user_id, $lab_room, $seat_no, $purpose, $date, $time);

if ($stmt->execute()) {
    echo json_encode(["success" => true, "reservation_no" => $reservation_no]);
} else {
    echo json_encode(["error" => "Reservation failed"]);
}
exit;
}   

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Sit-In</title>
    <link rel="stylesheet" href="student_styles.css">
    <script defer src="script.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <style>
        .reservation-form {
            display: none;
            margin-top: 20px;
            background: #f9f9f9;
            padding: 15px;
            border-radius: 10px;
            width: 100%;
            max-width: 500px;
        }
        table {
            width: 100%;
            margin-top: 30px;
            border-collapse: collapse;
        }
        table, th, td {
            border: 1px solid #ccc;
        }
        th, td {
            padding: 10px;
            text-align: left;
        }
        #reserveBtn {
            padding: 10px 20px;
            background-color: #5F3A74;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <?php include 'student_navbar.php'; ?>

    <div class="content">
        <h1>Reservation</h1>
        <button id="reserveBtn">Make a Reservation</button>

        <form id="reservationForm" class="reservation-form">
            <label>StudentID: </label> <label id="student_idno"></label>
            <label>Name: </label> <label id="full_name"></label>
            <label>Course: </label> <label id="course"></label>
            <label>Year Level: </label> <label id="year_level"></label>
            <label>Email: </label> <label id="email"></label>
            <label>Remaining Sessions: </label> <label id="remaining_sitin"></label>
            <label for="lab_room">Lab:</label><br>
            <select id="lab_room" name="lab_room" required>
                <option value="" disabled selected>Select Laboratory</option>
                <option>524</option><option>526</option><option>528</option><option>530</option><option>542</option><option>544</option><option>517</option>
            </select>
            <select id="seat_no" name="seat_no" required>
                <option value="" disabled selected>Select Seat</option>
                <?php for ($i = 1; $i <= 50; $i++): ?>
                    <option><?= $i ?></option>
                <?php endfor; ?>
            </select>
            <label for="sitin_purpose">Purpose:</label><br>
            <select id="sitin_purpose" name="sitin_purpose" required>
                <option value="" disabled selected>Select Purpose</option>
                <option>C# Programming</option>
                <option>C Programming</option>
                <option>Python Programming</option>
                <option>Java Programming</option>
                <option>ASP.Net Programming</option>
                <option>Php Programming</option>
            </select><br><br>

            <label for="date">Date:</label><br>
            <input type="date" id="date" name="date" required><br><br>

            <label for="time">Time:</label><br>
            <input type="time" id="time" name="time" required><br><br>

            <button type="submit">Submit Reservation</button>
        </form>

        <h2>Your Reservations</h2>
        <table>
        <thead>
    <tr>
        <th>Reservation #</th>
        <th>Lab</th>
        <th>Seat</th>
        <th>Date</th>
        <th>Time</th>
        <th>Status</th>
    </tr>
</thead>
            <tbody id="reservationTable">
                <!-- Filled by JS -->
            </tbody>
        </table>
    </div>

    <script>
    document.addEventListener("DOMContentLoaded", () => {
    const userId = localStorage.getItem('user_id');  // Get userId from localStorage
    if (!userId) return;  // If no userId is found in localStorage, stop further execution

    loadStudentInfo(userId);  // Load student info with the userId

    // Toggle reservation form visibility
    document.getElementById("reserveBtn").addEventListener("click", function () {
        const form = document.getElementById("reservationForm");
        form.style.display = form.style.display === "none" ? "block" : "none";
    });

    // Handle reservation form submission
    document.getElementById('reservationForm').addEventListener('submit', function (e) {
        e.preventDefault();

        const payload = {
            submit_reservation: true,
            user_id: userId,  // Use userId from localStorage
            lab_room: document.getElementById('lab_room').value,
            seat_no: document.getElementById('seat_no').value,
            sitin_purpose: document.getElementById('sitin_purpose').value,
            date: document.getElementById('date').value,
            time: document.getElementById('time').value
        };

        // Send reservation request to server
        fetch('student_reservation.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify(payload)
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                alert("Reservation submitted!");
                document.getElementById('reservationForm').reset();  // Reset form
                document.getElementById('reservationForm').style.display = 'none';  // Hide form
            } else {
                alert(data.error || "Submission failed.");
            }
        });
    });
});

// Load student information using the userId
function loadStudentInfo(userId) {
    fetch('student_reservation.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({ user_id: userId })
    })
    .then(res => res.json())
    .then(data => {
        if (data.error) {
            console.error(data.error);
        } else {
            document.getElementById('student_idno').textContent = data.student_idno;
            document.getElementById('full_name').textContent = data.full_name;
            document.getElementById('course').textContent = data.course;
            document.getElementById('year_level').textContent = data.year_level;
            document.getElementById('email').textContent = data.email;
            document.getElementById('remaining_sitin').textContent = data.remaining_sitin;

            // Now that we have the student_idno, load their reservations
            loadReservations(userId);  // Use student_idno here
        }
    });
}

// Load reservations for the student based on their student_idno
function loadReservations(userId) {
    console.log("Loading reservations for user ID:", userId);
    fetch('student_reservation.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({ user_id: userId, get_reservations: true })
    })
    .then(res => res.json())
    .then(data => {
        console.log("Reservations data:", data);
        const tbody = document.getElementById('reservationTable');
        tbody.innerHTML = '';  // Clear existing rows

        if (data.error) {
            console.error(data.error);
            tbody.innerHTML = '<tr><td colspan="4">Error: No reservations found.</td></tr>';
        } else if (data.length === 0) {
            tbody.innerHTML = '<tr><td colspan="4">No reservations found.</td></tr>';
        } else {
            console.log("Data length:", data.length);
            
            // Populate table with reservation data
            data.forEach(row => {
                const tr = document.createElement('tr');
                // Convert reservation_time to 12-hour format
const time = new Date(`1970-01-01T${row.reservation_time}`);
const formattedTime = time.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', hour12: true });

// In the loadReservations function, update the table row generation:
tr.innerHTML = `<td>${row.reservation_no}</td><td>${row.lab_room}</td><td>${row.seat_no}</td><td>${row.reservation_date}</td><td>${formattedTime}</td><td>${row.reservation_status}</td>`;
                tbody.appendChild(tr);
            });
        }
    });
}


    </script>
</body>
</html>
