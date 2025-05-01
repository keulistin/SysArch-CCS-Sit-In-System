window.loggedInUser = {}; // ari e store ang user_id sa ni login, can be usesd globally
const userId = localStorage.getItem("user_id");

document.addEventListener("DOMContentLoaded", function () {
    const loginForm = document.getElementById("loginForm");

        loginForm.addEventListener("submit", function (event) {
            event.preventDefault(); // Prevent default form submission

            let formData = new FormData(this);

            fetch("login.php", {
                method: "POST",
                body: formData
            })
                .then(response => response.json()) // Ensure JSON response handling
                .then(data => {

                    // Store user_id globally and in localStorage
                    if (data.success) {
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
});



document.addEventListener("DOMContentLoaded", function () {
    const logoutBtn = document.getElementById("logoutBtn");

    logoutBtn.addEventListener("click", function () {
        alert("Logging out...");
        localStorage.removeItem("user_id");
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
    // Feedback submission modal
    const feedbackModal = document.getElementById("sitIn-feedbackModal");
    const feedbackClose = document.querySelector(".sitIn-close");
    const feedbackForm = document.getElementById("sitIn-feedbackForm");

    // View feedback modal
    const viewModal = document.getElementById("viewFeedbackModal");
    const viewClose = document.querySelector(".view-close");
    const feedbackDisplay = document.getElementById("feedback-display");

    // Open modals when buttons are clicked
    document.addEventListener("click", function (event) {
        const button = event.target.closest("button");
        if (!button) return;

        if (button.classList.contains("feedback-btn")) {
            // Open feedback submission modal
            document.getElementById("sitIn-historyId").value = button.getAttribute("data-id");
            feedbackModal.style.display = "flex";
        }
        else if (button.classList.contains("view-feedback-btn")) {
            // Open view feedback modal
            feedbackDisplay.textContent = button.getAttribute("data-feedback");
            viewModal.style.display = "flex";
        }
    });

    // Close modals
    feedbackClose.addEventListener("click", function () {
        feedbackModal.style.display = "none";
    });

    viewClose.addEventListener("click", function () {
        viewModal.style.display = "none";
    });

    // Close when clicking outside modal
    window.addEventListener("click", function (event) {
        if (event.target === feedbackModal) {
            feedbackModal.style.display = "none";
        }
        if (event.target === viewModal) {
            viewModal.style.display = "none";
        }
    });

    // Handle feedback form submission
    feedbackForm.addEventListener("submit", function (event) {
        event.preventDefault();

        const formData = new FormData(feedbackForm);
        const historyId = formData.get('history_id');
        const feedbackText = formData.get('feedback'); // Store the feedback text before resetting

        fetch("submit_feedback.php", {
            method: "POST",
            body: formData
        })
            .then(response => response.json())
            .then(data => {
                alert(data.message);
                feedbackModal.style.display = "none";

                // Reset the form textarea
                document.getElementById("sitIn-feedback").value = "";

                if (data.success) {
                    const feedbackBtn = document.querySelector(`.feedback-btn[data-id="${historyId}"]`);

                    if (feedbackBtn) {
                        feedbackBtn.classList.remove('feedback-btn');
                        feedbackBtn.classList.add('view-feedback-btn');

                        if (data.hasFoulWord) {
                            feedbackBtn.classList.add('foul-feedback');
                        }

                        feedbackBtn.innerHTML = '<img src="../images/view-icon.png" alt="View" width="65" height="30">';
                        feedbackBtn.setAttribute('data-feedback', feedbackText);
                    }
                }
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


//GENERATE REPORT AND DOWNLOAD
// CORRECTED GENERATE PDF FUNCTION
async function generatePDF(data, fileName) {
    const { PDFDocument, StandardFonts, rgb } = PDFLib;

    try {
        // Create a new PDF document
        const pdfDoc = await PDFDocument.create();
        const page = pdfDoc.addPage([600, 800]);
        const { width, height } = page.getSize();
        const margin = 50;

        // Set font
        const font = await pdfDoc.embedFont(StandardFonts.Helvetica);
        const boldFont = await pdfDoc.embedFont(StandardFonts.HelveticaBold);

        // Add title
        page.drawText('Sit-In History Report', {
            x: margin,
            y: height - margin - 30,
            size: 20,
            font: boldFont,
            color: rgb(0, 0, 0),
        });

        // Add date
        const currentDate = new Date().toLocaleDateString();
        page.drawText(`Generated on: ${currentDate}`, {
            x: margin,
            y: height - margin - 60,
            size: 12,
            font: font,
            color: rgb(0, 0, 0),
        });

        // Table setup
        const headers = ['Student ID', 'Name', 'Purpose', 'Lab', 'Time-In', 'Time-Out', 'Date'];
        const columnWidths = [80, 120, 100, 60, 80, 80, 80];
        let y = height - margin - 100;

        // Draw headers
        headers.forEach((header, i) => {
            page.drawText(header, {
                x: margin + columnWidths.slice(0, i).reduce((a, b) => a + b, 0),
                y: y,
                size: 10,
                font: boldFont,
                color: rgb(0, 0, 0),
            });
        });

        y -= 20;

        // Draw data rows
        for (const row of data) {
            const values = [
                row.student_idno,
                row.full_name,
                row.sitin_purpose,
                row.lab_room,
                formatTime(row.start_time),
                formatTime(row.end_time),
                formatDate(row.start_time)
            ];

            // Check if we need a new page
            if (y < margin + 30) {
                page.drawText('Continued on next page...', {
                    x: margin,
                    y: margin - 20,
                    size: 8,
                    font: font,
                    color: rgb(0, 0, 0),
                });

                const newPage = pdfDoc.addPage([600, 800]);
                y = height - margin - 50;

                // Draw headers on new page
                headers.forEach((header, i) => {
                    newPage.drawText(header, {
                        x: margin + columnWidths.slice(0, i).reduce((a, b) => a + b, 0),
                        y: y,
                        size: 10,
                        font: boldFont,
                        color: rgb(0, 0, 0),
                    });
                });

                y -= 20;
            }

            // Draw row data
            values.forEach((value, i) => {
                page.drawText(value, {
                    x: margin + columnWidths.slice(0, i).reduce((a, b) => a + b, 0),
                    y: y,
                    size: 8,
                    font: font,
                    color: rgb(0, 0, 0),
                });
            });

            y -= 15;
        }

        // CORRECTED SAVE OPERATION
        const pdfBytes = await pdfDoc.save();
        const blob = new Blob([pdfBytes], { type: 'application/pdf' });
        saveAs(blob, `${fileName}.pdf`);

    } catch (error) {
        console.error('Error generating PDF:', error);
        alert('Error generating PDF report');
    }
}

function formatTime(time) {
    let date = new Date(time);
    return date.toLocaleTimeString();
}

function formatDate(dateString) {
    let date = new Date(dateString);
    return date.toISOString().split('T')[0];
}




/*
// GENERATE REPORT FUNCTION
async function generateReport(format) {
    try {
        // First fetch the sit-in history data
        const response = await fetch('generate_reports.php');
        const data = await response.json();

        if (!data.success) {
            alert('Error: ' + data.message);
            return;
        }

        const reportData = data.data;
        const currentDate = new Date().toISOString().split('T')[0];
        const fileName = `SitIn_Report_${currentDate}`;

        switch (format) {
            case 'pdf':
                await generatePDF(reportData, fileName);
                break;
            case 'excel':
                generateExcel(reportData, fileName);
                break;
            case 'csv':
                generateCSV(reportData, fileName);
                break;
            default:
                alert('Invalid report format');
        }
    } catch (error) {
        console.error('Report generation error:', error);
        alert('Error generating report');
    }
}
*/
// PDF Generation
async function generatePDF(data, fileName) {
    const { PDFDocument, StandardFonts, rgb } = PDFLib;

    try {
        const pdfDoc = await PDFDocument.create();
        const page = pdfDoc.addPage([600, 800]);
        const { width, height } = page.getSize();
        const margin = 50;

        const font = await pdfDoc.embedFont(StandardFonts.Helvetica);
        const boldFont = await pdfDoc.embedFont(StandardFonts.HelveticaBold);

        // UNIVERSITY HEADER - Centered and formatted
        const headerLines = [
            { text: "University of Cebu-Main", bold: true, size: 16 },
            { text: "College of Computer Studies", bold: false, size: 14 },
            { text: "Computer Laboratory Sit-In Monitoring System Report", bold: false, size: 12 }
        ];

        let y = height - margin - 30;

        // Draw each header line centered
        headerLines.forEach(line => {
            const textWidth = line.bold ?
                boldFont.widthOfTextAtSize(line.text, line.size) :
                font.widthOfTextAtSize(line.text, line.size);

            page.drawText(line.text, {
                x: (width - textWidth) / 2, // Center calculation
                y: y,
                size: line.size,
                font: line.bold ? boldFont : font,
                color: rgb(0, 0, 0),
            });
            y -= line.size + 8; // Adjust vertical spacing
        });

        // Report title and date below the header
        y -= 20; // Extra space after header
        page.drawText('Sit-In History Report', {
            x: margin,
            y: y,
            size: 14,
            font: boldFont,
            color: rgb(0, 0, 0),
        });

        y -= 20;
        page.drawText(`Generated on: ${new Date().toLocaleDateString()}`, {
            x: margin,
            y: y,
            size: 10,
            font: font,
            color: rgb(0, 0, 0),
        });

        // Table headers
        const headers = ['Student ID', 'Name', 'Purpose', 'Lab', 'Time-In', 'Time-Out'];
        const columnWidths = [90, 130, 110, 60, 80, 80];
        y -= 40; // Space before table

        headers.forEach((header, i) => {
            page.drawText(header, {
                x: margin + columnWidths.slice(0, i).reduce((a, b) => a + b, 0),
                y: y,
                size: 10,
                font: boldFont,
                color: rgb(0, 0, 0),
            });
        });

        y -= 20;

        // Data rows (unchanged from your original)
        for (const row of data) {
            if (y < margin + 30) {
                page.drawText('Continued...', {
                    x: margin,
                    y: margin - 20,
                    size: 8,
                    font: font,
                });

                const newPage = pdfDoc.addPage([600, 800]);
                y = height - margin - 50;

                headers.forEach((header, i) => {
                    newPage.drawText(header, {
                        x: margin + columnWidths.slice(0, i).reduce((a, b) => a + b, 0),
                        y: y,
                        size: 10,
                        font: boldFont,
                    });
                });

                y -= 20;
            }

            const values = [
                row.student_idno,
                row.full_name,
                row.sitin_purpose,
                row.lab_room,
                formatTime(row.start_time),
                formatTime(row.end_time)
            ];

            values.forEach((value, i) => {
                page.drawText(value, {
                    x: margin + columnWidths.slice(0, i).reduce((a, b) => a + b, 0),
                    y: y,
                    size: 8,
                    font: font,
                });
            });

            y -= 15;
        }

        const pdfBytes = await pdfDoc.save();
        saveAs(new Blob([pdfBytes], { type: 'application/pdf' }), `${fileName}.pdf`);

    } catch (error) {
        console.error('PDF generation error:', error);
        alert('Error generating PDF');
    }
}

// Excel Generation
function generateExcel(data, fileName) {
    try {
        const header = ['Student ID', 'Name', 'Purpose', 'Lab', 'Time-In', 'Time-Out'];
        const rows = data.map(row => [
            row.student_idno,
            row.full_name,
            row.sitin_purpose,
            row.lab_room,
            formatTime(row.start_time),
            formatTime(row.end_time)
        ]);

        const ws = XLSX.utils.aoa_to_sheet([header, ...rows]);
        const wb = XLSX.utils.book_new();
        XLSX.utils.book_append_sheet(wb, ws, "SitIn History");
        XLSX.writeFile(wb, `${fileName}.xlsx`);

    } catch (error) {
        console.error('Excel generation error:', error);
        alert('Error generating Excel file');
    }
}

// CSV Generation
function generateCSV(data, fileName) {
    try {
        let csv = 'Student ID,Name,Purpose,Lab,Time-In,Time-Out\n';

        data.forEach(row => {
            csv += `"${row.student_idno}","${row.full_name}","${row.sitin_purpose}",` +
                `"${row.lab_room}","${formatTime(row.start_time)}","${formatTime(row.end_time)}"\n`;
        });

        const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
        saveAs(blob, `${fileName}.csv`);

    } catch (error) {
        console.error('CSV generation error:', error);
        alert('Error generating CSV file');
    }
}

// Helper functions
function formatTime(time) {
    const date = new Date(time);
    return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
}

function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toISOString().split('T')[0];
}

// Initialize report buttons
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.report-btn').forEach(button => {
        button.addEventListener('click', function () {
            const format = this.textContent.toLowerCase();
            generateReport(format);
        });
    });
});


document.addEventListener('DOMContentLoaded', function () {
    document.addEventListener('click', function (e) {
        if (e.target.classList.contains('report-btn')) {
            const format = e.target.textContent.toLowerCase();
            generateReport(format);
        }
    });
});



// Add this to your existing script.js

// Report Generation Functions
document.addEventListener('DOMContentLoaded', function () {
    // Handle report button clicks
    document.querySelectorAll('.report-btn').forEach(button => {
        button.addEventListener('click', function () {
            const format = this.textContent.toLowerCase();
            openReportModal(format);
        });
    });

    // Handle time period filter change
    document.getElementById('reportTimeFilter').addEventListener('change', function () {
        const customDateRange = document.getElementById('customDateRange');
        customDateRange.style.display = this.value === 'custom' ? 'block' : 'none';
    });

    // Handle report form submission
    document.getElementById('reportFilterForm').addEventListener('submit', function (e) {
        e.preventDefault();
        generateFilteredReport();
    });
});

function openReportModal(format) {
    document.getElementById('reportFormat').value = format;
    document.getElementById('reportFilterModal').style.display = 'block';
}

function closeReportModal() {
    document.getElementById('reportFilterModal').style.display = 'none';
}

async function generateFilteredReport() {
    const form = document.getElementById('reportFilterForm');
    const formData = new FormData(form);
    const format = formData.get('format');

    try {
        // Get filtered data
        const response = await fetch('get_filtered_history.php', {
            method: 'POST',
            body: formData
        });

        const data = await response.json();

        if (!data.success) {
            alert('Error: ' + data.message);
            return;
        }

        const reportData = data.data;
        const currentDate = new Date().toISOString().split('T')[0];

        // Generate filename with filters
        const labFilter = formData.get('lab_room') !== 'all' ? `_Lab-${formData.get('lab_room')}` : '';
        const purposeFilter = formData.get('sitin_purpose') !== 'all' ? `_Purpose-${formData.get('sitin_purpose')}` : '';
        const fileName = `SitIn_Report_${currentDate}${labFilter}${purposeFilter}`;

        // Generate report based on format
        switch (format) {
            case 'pdf':
                await generatePDF(reportData, fileName);
                break;
            case 'excel':
                generateExcel(reportData, fileName);
                break;
            case 'csv':
                generateCSV(reportData, fileName);
                break;
        }

        closeReportModal();
    } catch (error) {
        console.error('Error generating report:', error);
        alert('Error generating report');
    }
}





//ADMIN FEEDBACK
document.addEventListener("DOMContentLoaded", function () {
    const viewModal = document.getElementById("viewFeedbackModal");
    const viewClose = document.querySelector(".view-close");
    const feedbackDisplay = document.getElementById("feedback-display");

    document.addEventListener("click", function (event) {
        const button = event.target.closest(".view-feedback-btn");
        if (!button) return;

        const feedback = button.getAttribute("data-feedback");
        feedbackDisplay.textContent = feedback;
        viewModal.style.display = "flex";
    });

    viewClose.addEventListener("click", function () {
        viewModal.style.display = "none";
    });

    window.addEventListener("click", function (event) {
        if (event.target === viewModal) {
            viewModal.style.display = "none";
        }
    });
});


// POINTS FUNCTION
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.checkout-btn').forEach(button => {
        button.addEventListener('click', function () {
            const sitinId = this.getAttribute('data-sitin-id');
            const studentId = this.getAttribute('data-id');

            // Show confirmation dialog for points
            const awardPoints = confirm('Award 1 point to this student for this session?');

            fetch(window.location.href, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `sitin_id=${sitinId}&award_points=${awardPoints ? 1 : 0}`
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert(data.message);
                        location.reload();
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                });
        });
    });
});