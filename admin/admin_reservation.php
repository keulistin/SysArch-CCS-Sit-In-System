<?php
require '../db_connect.php'; // adjust path if needed

if (isset($_GET['action'])) {
    header('Content-Type: application/json');

    if ($_GET['action'] === 'fetch') {
        $sql = "SELECT 
                    r.reservation_no,
                    r.student_idno,
                    r.lab_room,
                    r.seat_no,
                    r.sitin_purpose,
                    r.reservation_date,
                    r.reservation_time,
                    r.reservation_status,
                    CONCAT(u.first_name, ' ', u.middle_name, ' ', u.last_name) AS full_name,
                    u.email,
                    s.course,
                    s.year_level,
                    s.remaining_sitin 
                FROM lab_reservation r
                JOIN student s ON r.student_idno = s.student_idno
                JOIN user u ON r.user_id = u.user_id
                ORDER BY r.reservation_date, r.reservation_time";

        $result = $conn->query($sql);

        if (!$result) {
            echo json_encode(['error' => $conn->error]);
            $conn->close();
            exit();
        }

        $reservations = [];
        while ($row = $result->fetch_assoc()) {
            $reservations[] = $row;
        }

        echo json_encode($reservations);
        $conn->close();
        exit();
    }

    if ($_GET['action'] === 'update_status' && isset($_POST['reservation_no']) && isset($_POST['status'])) {
        $reservation_no = $conn->real_escape_string($_POST['reservation_no']);
        $status = $conn->real_escape_string($_POST['status']);

        $updateSql = "UPDATE lab_reservation SET reservation_status = '$status' WHERE reservation_no = '$reservation_no'";

        if ($conn->query($updateSql)) {
            echo json_encode(['success' => true, 'message' => "Status updated to $status"]);
        } else {
            echo json_encode(['success' => false, 'message' => $conn->error]);
        }
        $conn->close();
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Sit-In</title>
    <link rel="stylesheet" href="admin_styles.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <style>
        #reservationModal {
            display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0,0,0,0.5); justify-content: center; align-items: center;
        }
        #reservationModal .modal-content {
            background: white; padding: 20px; border-radius: 8px; width: 400px; position: relative;
        }
        #reservationModal button { margin: 5px; }
        table { border-collapse: collapse; width: 100%; }
        th, td { padding: 8px; border: 1px solid black; }
        tr:hover { background: #f0f0f0; cursor: pointer; }
        button:disabled { background: #ccc; cursor: not-allowed; }
    </style>
</head>
<body>
<?php include 'admin_navbar.php'; ?>
<div class="content">
    <h1>Reservations</h1>
</div>

<!-- Modal -->
<div id="reservationModal">
    <div class="modal-content">
        <h2>Reservation Details</h2>
        <div id="modalContent"></div>
        <button id="approveBtn">Approve</button>
        <button id="declineBtn">Decline</button>
        <button id="closeModal" style="position:absolute; top:10px; right:10px;">X</button>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    let reservations = [];
    const contentDiv = document.querySelector('.content');
    const modal = document.getElementById('reservationModal');
    const modalContent = document.getElementById('modalContent');
    const approveBtn = document.getElementById('approveBtn');
    const declineBtn = document.getElementById('declineBtn');
    const closeModal = document.getElementById('closeModal');
    let currentReservation = null;

    fetch('<?= basename(__FILE__) ?>?action=fetch')
        .then(response => response.json())
        .then(data => {
            reservations = data;
            const table = document.createElement('table');
            const headerRow = `<tr>
                <th>Reservation No</th>
                <th>Full Name</th>
                <th>Lab Room</th>
                <th>Seat No</th>
                <th>Date</th>
                <th>Time</th>
                <th>Status</th>
            </tr>`;
            table.innerHTML = headerRow;

            if (data.length > 0 && !data.error) {
                data.forEach(reservation => {
                    const row = document.createElement('tr');
                    row.innerHTML = `
                        <td>${reservation.reservation_no}</td>
                        <td>${reservation.full_name}</td>
                        <td>${reservation.lab_room}</td>
                        <td>${reservation.seat_no}</td>
                        <td>${reservation.reservation_date}</td>
                        <td>${reservation.reservation_time}</td>
                        <td>${reservation.reservation_status}</td>`;
                    row.addEventListener('click', () => {
                        currentReservation = reservation;
                        modalContent.innerHTML = `
                            <p><strong>Reservation No:</strong> ${reservation.reservation_no}</p>
                            <p><strong>Full Name:</strong> ${reservation.full_name}</p>
                            <p><strong>Email:</strong> ${reservation.email}</p>
                            <p><strong>Course:</strong> ${reservation.course}</p>
                            <p><strong>Year Level:</strong> ${reservation.year_level}</p>
                            <p><strong>Lab Room:</strong> ${reservation.lab_room}</p>
                            <p><strong>Seat No:</strong> ${reservation.seat_no}</p>
                            <p><strong>Purpose:</strong> ${reservation.sitin_purpose}</p>
                            <p><strong>Date:</strong> ${reservation.reservation_date}</p>
                            <p><strong>Time:</strong> ${reservation.reservation_time}</p>
                            <p><strong>Status:</strong> ${reservation.reservation_status}</p>
                            <p><strong>Remaining Sit-In:</strong> ${reservation.remaining_sitin}</p>`;
                        modal.style.display = 'flex';

                        // Disable buttons if already approved/declined
                        if (reservation.reservation_status === 'Approved' || reservation.reservation_status === 'Declined') {
                            approveBtn.disabled = true;
                            declineBtn.disabled = true;
                        } else {
                            approveBtn.disabled = false;
                            declineBtn.disabled = false;
                        }
                    });
                    table.appendChild(row);
                });
            } else if (data.error) {
                const errorRow = document.createElement('tr');
                errorRow.innerHTML = `<td colspan="7">Error: ${data.error}</td>`;
                table.appendChild(errorRow);
            } else {
                const row = document.createElement('tr');
                row.innerHTML = `<td colspan="7">No reservations found.</td>`;
                table.appendChild(row);
            }

            contentDiv.appendChild(table);
        })
        .catch(err => {
            console.error('Error fetching reservations:', err);
            contentDiv.textContent = 'Error loading reservations.';
        });

    approveBtn.addEventListener('click', () => updateStatus('Approved'));
    declineBtn.addEventListener('click', () => updateStatus('Declined'));
    closeModal.addEventListener('click', () => modal.style.display = 'none');

    function updateStatus(status) {
        if (!currentReservation) return;
        const formData = new FormData();
        formData.append('reservation_no', currentReservation.reservation_no);
        formData.append('status', status);

        fetch('<?= basename(__FILE__) ?>?action=update_status', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(result => {
            alert(result.message);
            if (result.success) {
                modal.style.display = 'none';
                location.reload();
            }
        })
        .catch(err => {
            console.error('Error updating status:', err);
            alert('Error updating reservation.');
        });
    }
});
</script>
</body>
</html>
