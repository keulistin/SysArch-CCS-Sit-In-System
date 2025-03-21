document.addEventListener("DOMContentLoaded", function () {
    const loginForm = document.getElementById("loginForm");
    
    if (loginForm) { // ✅ Check if form exists before using it
        loginForm.addEventListener("submit", function (event) {
            event.preventDefault(); // Prevent default form submission

            let formData = new FormData(this);

            fetch("login.php", {
                method: "POST",
                body: formData
            })
            .then(response => response.json()) // Ensure JSON response handling
            .then(data => {
                if (data.success) {
                    // Redirect based on user role
                    window.location.href = data.role === "admin" ? "admin/admin_dashboard.php" : "student/student_dashboard.php";
                } else {
                    document.getElementById("error-message").textContent = data.message;
                }
            })
            .catch(error => {
                document.getElementById("error-message").textContent = "Error: Unable to connect to the server.";
                console.error("Login Error:", error);
            });
        });
    } else {
        console.warn("⚠ Warning: 'loginForm' not found in the document.");
    }
});



document.addEventListener("DOMContentLoaded", function () {
    const logoutBtn = document.getElementById("logoutBtn");

    logoutBtn.addEventListener("click", function () {
        alert("Logging out...");
        window.location.href = "login.html"; // Redirect to login page
    });
});
//logout


//add announcement
document.addEventListener("DOMContentLoaded", function () {
    const openModalBtn = document.getElementById("openModalBtn");
    const closeModalBtn = document.getElementById("closeModalBtn");
    const modalOverlay = document.getElementById("modalOverlay");
    const announcementForm = document.getElementById("announcementForm");

    // Open Modal
    openModalBtn.addEventListener("click", () => {
        modalOverlay.style.display = "flex";
    });

    // Close Modal
    closeModalBtn.addEventListener("click", () => {
        modalOverlay.style.display = "none";
    });

    // Submit Form via AJAX
    announcementForm.addEventListener("submit", function (e) {
        e.preventDefault();

        let formData = new FormData(this);

        fetch("add_announcement.php", {  // Use the new separate PHP file
            method: "POST",
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(data.message); // Show success message
                modalOverlay.style.display = "none"; // Close the modal
                location.reload(); // Refresh the page to display the new announcement
            } else {
                alert(data.message); // Show error message
            }
        })
        .catch(error => console.error("Error:", error));
    });
});


//admin sit in tab
document.addEventListener("DOMContentLoaded", function () {
    const tabs = document.querySelectorAll(".tab");
    const tabContents = {
        "currentSitin": document.getElementById("currentSitin"),
        "historySitin": document.getElementById("historySitin")
    };

    tabs.forEach(tab => {
        tab.addEventListener("click", function () {
            tabs.forEach(t => t.classList.remove("active"));
            this.classList.add("active");

            // Hide all tab content
            Object.values(tabContents).forEach(content => content.style.display = "none");

            // Show the selected tab content
            const target = this.getAttribute("data-target");
            if (tabContents[target]) {
                tabContents[target].style.display = "block";
            }
        });
    });
});


// Add student overlay functionality
document.addEventListener("DOMContentLoaded", function () {
    const searchForm = document.getElementById("adminSitinSearchForm");
    const searchInput = document.getElementById("adminSitinSearchInput");
    const adminSitinOverlay = document.getElementById("adminSitinOverlay");

    if (searchForm && searchInput && adminSitinOverlay) {
        searchForm.addEventListener("submit", function (event) {
            event.preventDefault();

            let query = searchInput.value.trim();

            if (query !== "") {
                fetch("admin_sitin.php", {
                    method: "POST",
                    headers: { "Content-Type": "application/x-www-form-urlencoded" },
                    body: `searchStudent=${encodeURIComponent(query)}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Populate student details in the overlay
                        document.getElementById("student-id").innerText = data.data.student_idno;
                        document.getElementById("student-name").innerText = data.data.full_name;
                        document.getElementById("student-course").innerText = data.data.course;
                        document.getElementById("student-year").innerText = data.data.year_level;
                        document.getElementById("student-email").innerHTML = `<a href="mailto:${data.data.email}">${data.data.email}</a>`;
                        document.getElementById("student-session").innerText = data.data.remaining_sitin;

                        // Show the overlay
                        adminSitinOverlay.style.display = "flex";
                    } else {
                        alert(data.message);
                    }
                })
                .catch(error => console.error("Error:", error));
            } else {
                alert("Please enter a student name or ID.");
            }
        });
    }
});

// Function to close the overlay
function adminSitinCloseOverlay() {
    const overlay = document.getElementById("adminSitinOverlay");
    if (overlay) {
        overlay.style.display = "none";
    }
}

//check in button
document.addEventListener("DOMContentLoaded", function () {
    const checkInBtn = document.querySelector(".checkin-btn");

    checkInBtn.addEventListener("click", function () {
        const studentId = document.getElementById("student-id").innerText;
        const purpose = document.getElementById("purpose").value;
        const lab = document.getElementById("lab").value;

        if (!purpose || !lab) {
            alert("Please select a purpose and a laboratory.");
            return;
        }

        let formData = new FormData();
        formData.append("student_idno", studentId);
        formData.append("purpose", purpose);
        formData.append("lab", lab);

        fetch("checkin.php", {
            method: "POST",
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            alert(data.message);
            if (data.success) {
                location.reload(); // Reload to show updates
            }
        })
        .catch(error => console.error("Error:", error));
    });
});



//checkout
document.addEventListener("DOMContentLoaded", function () {
    document.querySelectorAll(".checkout-btn").forEach(button => {
        button.addEventListener("click", function () {
            let studentId = this.getAttribute("data-id");

            if (!confirm("Are you sure you want to check out this student?")) return;

            fetch("checkout.php", {
                method: "POST",
                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                body: "student_idno=" + encodeURIComponent(studentId)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert("Checkout successful!");

                    // Remove row from current sit-in table
                    this.closest("tr").remove();

                    // Reduce session count in UI if overlay is open
                    let sessionElement = document.getElementById("student-session");
                    if (sessionElement) {
                        let currentSessions = parseInt(sessionElement.innerText, 10);
                        sessionElement.innerText = Math.max(0, currentSessions - 1);
                    }

                    // **Dynamically Add Entry to Sit-In History Table**
                    if (data.data) {
                        let historyTable = document.getElementById("historySitin").querySelector("tbody");
                        let newRow = document.createElement("tr");

                        newRow.innerHTML = `
                            <td>${data.data.student_idno}</td>
                            <td>${data.data.full_name}</td>
                            <td>${data.data.sitin_purpose}</td>
                            <td>${data.data.lab_room}</td>
                            <td>${formatTime(data.data.start_time)}</td>
                            <td>${formatTime(data.data.end_time)}</td>
                            <td>${data.data.duration} mins</td>
                        `;

                        historyTable.prepend(newRow); // Add new history entry at the top
                    }

                } else {
                    alert("Error: " + data.message);
                }
            })
            .catch(error => console.error("Error:", error));
        });
    });
});

// **Helper Function to Format Time (12-Hour Format)**
function formatTime(time) {
    let date = new Date(time);
    let hours = date.getHours();
    let minutes = date.getMinutes();
    let ampm = hours >= 12 ? 'PM' : 'AM';
    hours = hours % 12 || 12;
    minutes = minutes < 10 ? '0' + minutes : minutes;
    return hours + ':' + minutes + ' ' + ampm;
}



document.addEventListener("DOMContentLoaded", function () {
    fetch("student_history.php") // Fetch only logged-in student's history
        .then(response => response.json())
        .then(data => {
            if (!data.success) {
                console.error("Error:", data.message);
                return;
            }

            let tableBody = document.getElementById("studentSitinHistoryTable");
            tableBody.innerHTML = ""; // Clear existing table

            data.data.forEach(student => {
                let row = document.createElement("tr");
                row.innerHTML = `
                    <td>${student.student_idno}</td>
                    <td>${student.full_name}</td>
                    <td>${student.sitin_purpose}</td>
                    <td>${student.lab_room}</td>
                    <td>${formatTime(student.start_time)}</td>
                    <td>${formatTime(student.end_time)}</td>
                    <td>${formatDate(student.sitin_date)}</td>
                    <td><button class="feedback-btn">Feedback</button></td>
                `;
                tableBody.appendChild(row);
            });
        })
        .catch(error => console.error("Error fetching sit-in history:", error));
});

// Helper functions
function formatTime(time) {
    let date = new Date(time);
    let hours = date.getHours();
    let minutes = date.getMinutes();
    let ampm = hours >= 12 ? "PM" : "AM";
    hours = hours % 12 || 12;
    minutes = minutes < 10 ? "0" + minutes : minutes;
    return hours + ":" + minutes + " " + ampm;
}

function formatDate(dateString) {
    let date = new Date(dateString);
    return date.toISOString().split("T")[0]; // YYYY-MM-DD format
}


//feedbackkk
document.addEventListener("DOMContentLoaded", function () {
    const modal = document.getElementById("sitIn-feedbackModal");
    const closeModal = document.querySelector(".sitIn-close");
    const feedbackForm = document.getElementById("sitIn-feedbackForm");
    const historyIdField = document.getElementById("sitIn-historyId"); // Change ID to reflect history_id

    // Event delegation for feedback button click
    document.body.addEventListener("click", function (event) {
        if (event.target.closest(".feedback-btn")) {
            const button = event.target.closest(".feedback-btn");
            const historyId = button.getAttribute("data-id"); // Use history_id instead
            historyIdField.value = historyId; // Set history ID in modal
            modal.style.display = "flex"; // Show modal
        }
    });

    // Close modal when clicking the close button
    closeModal.addEventListener("click", function () {
        modal.style.display = "none";
    });

    // Handle feedback form submission
    feedbackForm.addEventListener("submit", function (event) {
        event.preventDefault(); // Prevent form refresh

        const formData = new FormData(feedbackForm);

        fetch("submit_feedback.php", {
            method: "POST",
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            alert(data.message);
            modal.style.display = "none"; // Hide modal after submission
            location.reload(); // Refresh page to show updated feedback
        })
        .catch(error => console.error("Error:", error));
    });
});
