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
        const studentId = document.getElementById("student-id").innerText.trim();
        const studentName = document.getElementById("student-name").innerText.trim();
        const purpose = document.getElementById("sitin_purpose").value;
        const lab = document.getElementById("lab_room").value;

        if (!studentId || !studentName || !purpose || !lab) {
            alert("All fields are required.");
            return;
        }

        let formData = new FormData();
        formData.append("student_idno", studentId);
        formData.append("full_name", studentName);
        formData.append("sitin_purpose", purpose);
        formData.append("lab_room", lab);

        fetch("checkin.php", {
            method: "POST",
            body: formData
        })
        .then(response => response.text()) // First, get raw text
        .then(text => {
            console.log(text); // Debugging output
            return JSON.parse(text); // Convert to JSON
        })
        .then(data => {
            alert(data.message);
            if (data.success) {
                localStorage.setItem("sitin_id" + studentId, data.sitin_id);
                location.reload();
            }
        })
        .catch(error => console.error("Error:", error));
    });
});





//checkout
document.addEventListener("DOMContentLoaded", function () {
    document.body.addEventListener("click", function (event) {
        if (event.target.closest(".checkout-btn")) {
            const button = event.target.closest(".checkout-btn");
            const sitinId = button.getAttribute("data-sitin-id"); // Get sitin_id

            if (!sitinId) {
                alert("Error: Sit-in ID not found.");
                return;
            }

            fetch("checkout.php", {
                method: "POST",
                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                body: `sitin_id=${encodeURIComponent(sitinId)}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    location.reload();
                } else {
                    alert(data.message);
                }
            })
            .catch(error => console.error("Error:", error));
        }
    });
});






// Student history
document.addEventListener("DOMContentLoaded", function () {
    fetch("../SysArch_SitIn/student_history.php") // Fetch only logged-in student's history
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
                    <td>${student.duration} mins</td>
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
    return date.toLocaleTimeString(); // Automatically converts to local time
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
    const historyIdField = document.getElementById("sitIn-historyId");
    const feedbackField = document.getElementById("sitIn-feedback");

    // Event delegation for feedback and view buttons
    document.body.addEventListener("click", function (event) {
        const button = event.target.closest("button");

        if (!button) return;

        if (button.classList.contains("feedback-btn")) {
            // Open feedback submission modal
            historyIdField.value = button.getAttribute("data-id");
            feedbackField.value = ""; // Clear previous input
            feedbackField.readOnly = false; // Allow typing
            feedbackForm.style.display = "block";
            modal.style.display = "flex"; // Show modal
        } else if (button.classList.contains("view-feedback-btn")) {
            // Open view-only feedback modal
            historyIdField.value = button.getAttribute("data-id");
            feedbackField.value = button.getAttribute("data-feedback"); // Load feedback
            feedbackField.readOnly = true; // Prevent editing
            feedbackForm.style.display = "none"; // Hide submit button
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



//STUDENT LIST
document.addEventListener("DOMContentLoaded", function () {
    const searchForm = document.getElementById("adminStudentListSearchForm");
    const searchInput = document.getElementById("adminStudentListSearchInput");
    const studentTableBody = document.getElementById("studentTableBody");
    const cancelSearchBtn = document.getElementById("cancelSearchBtn");

    let defaultTableContent = studentTableBody.innerHTML; // Store the original table data

    // Function to load the default full student list
    function resetTable() {
        studentTableBody.innerHTML = defaultTableContent; // Restore original content
        searchInput.value = ""; // Clear search input
    }

    if (searchForm && searchInput && studentTableBody) {
        searchForm.addEventListener("submit", function (event) {
            event.preventDefault();
            let query = searchInput.value.trim();

            if (query !== "") {
                fetch("admin_studentlist.php", {
                    method: "POST",
                    headers: { "Content-Type": "application/x-www-form-urlencoded" },
                    body: `searchStudent=${encodeURIComponent(query)}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.data.length > 0) {
                        studentTableBody.innerHTML = ""; // Clear existing rows

                        data.data.forEach(student => {
                            let row = `
                                <tr>
                                    <td>${student.student_idno}</td>
                                    <td>${student.full_name}</td>
                                    <td>${student.username}</td>
                                    <td><a href="mailto:${student.email}">${student.email}</a></td>
                                    <td>${student.course}</td>
                                    <td>${student.year_level}</td>
                                    <td>${student.remaining_sitin}</td>
                                    <td>
                                        <button class="reset-btn" data-id="${student.student_idno}" style="background: none; border: none; cursor: pointer;">
                                            <img src="../images/reset.png" alt="Exit" width="60" height="24">
                                        </button>
                                    </td>
                                </tr>`;
                            studentTableBody.innerHTML += row;
                        });
                    } else {
                        studentTableBody.innerHTML = "<tr><td colspan='8'>No students found.</td></tr>";
                    }
                })
                .catch(error => console.error("Error:", error));
            } else {
                alert("Please enter a student name or ID.");
            }
        });
    }

    // Cancel (X) Button: Clears search input & restores full student list
    if (cancelSearchBtn) {
        cancelSearchBtn.addEventListener("click", resetTable);
    }
});




//RESET REMAINING SIT_IN
document.addEventListener("DOMContentLoaded", function () {
    const searchForm = document.getElementById("adminStudentListSearchForm");
    const searchInput = document.getElementById("adminStudentListSearchInput");
    const studentTableBody = document.getElementById("studentTableBody");
    const cancelSearchBtn = document.getElementById("cancelSearchBtn");
    const resetAllBtn = document.getElementById("resetAllBtn");  // Reset All Button

    // Search student event
    searchForm.addEventListener("submit", function (event) {
        event.preventDefault();
        let query = searchInput.value.trim();

        if (query !== "") {
            fetch("admin_studentlist.php", {
                method: "POST",
                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                body: `searchStudent=${encodeURIComponent(query)}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success && data.data.length > 0) {
                    studentTableBody.innerHTML = ""; // Clear table

                    data.data.forEach(student => {
                        let row = `
                            <tr>
                                <td>${student.student_idno}</td>
                                <td>${student.full_name}</td>
                                <td>${student.username}</td>
                                <td><a href="mailto:${student.email}">${student.email}</a></td>
                                <td>${student.course}</td>
                                <td>${student.year_level}</td>
                                <td class="remaining-sitin" data-id="${student.student_idno}">${student.remaining_sitin}</td>
                                <td>
                                    <button class="reset-btn" data-id="${student.student_idno}" style="background: none; border: none; cursor: pointer;">
                                        <img src="../images/reset.png" alt="Reset" width="60" height="24">
                                    </button>
                                </td>
                            </tr>`;
                        studentTableBody.innerHTML += row;
                    });

                    attachResetButtonListeners(); // Attach event listeners again
                } else {
                    studentTableBody.innerHTML = "<tr><td colspan='8'>No students found.</td></tr>";
                }
            })
            .catch(error => console.error("Error:", error));
        } else {
            alert("Please enter a student name or ID.");
        }
    });

    // Cancel (X) Button: Clears search input & restores full student list
    cancelSearchBtn.addEventListener("click", function () {
        location.reload(); // Reloads the page to show the full student list
    });

    // 🔹 Separate function for resetting remaining sit-in for a single student
    function resetRemainingSitIn(studentId) {
        if (confirm("Are you sure you want to reset the remaining sit-in for this student?")) {
            fetch("reset_sitin.php", {
                method: "POST",
                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                body: `student_idno=${encodeURIComponent(studentId)}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    location.reload(); // 🔄 Reload the page after confirmation
                } else {
                    alert("Error: " + data.message);
                }
            })
            .catch(error => console.error("Error:", error));
        }
    }

    // 🔹 New function for resetting all remaining sit-ins to 30
    function resetAllRemainingSitIn() {
        if (confirm("Are you sure you want to reset all students' remaining sit-ins to 30?")) {
            fetch("reset_all_sitin.php", {
                method: "POST",
                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                body: `resetAll=true`  // Send a flag to indicate reset all
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    location.reload(); // 🔄 Reload the page after reset
                } else {
                    alert("Error: " + data.message);
                }
            })
            .catch(error => console.error("Error:", error));
        }
    }

    // Function to attach event listeners to reset buttons
    function attachResetButtonListeners() {
        document.querySelectorAll(".reset-btn").forEach(button => {
            button.addEventListener("click", function () {
                let studentId = this.getAttribute("data-id");
                resetRemainingSitIn(studentId); // Calls the separate reset function
            });
        });
    }

    // Attach event listener for the "Reset All" button
    resetAllBtn.addEventListener("click", resetAllRemainingSitIn);

    attachResetButtonListeners(); // Attach event listeners when page loads
});
