const userId = localStorage.getItem("user_id");
if (userId) {
    console.log("Logged-in user ID:", userId);
    // You can use this ID to fetch user data or personalize UI
} else {
    console.warn("User ID not found. Possibly not logged in.");
}