\<?php
// Start session
session_start();

// Database connection parameters
$servername = "localhost";
$username = "root"; // Replace with your MySQL username
$password = ""; // Replace with your MySQL password
$dbname = "online_shop";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Prepare and bind SQL statement
    $stmt = $conn->prepare("INSERT INTO feedback (name, feedback, email) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $name, $feedback, $email);

    // Set parameters and execute
    $name = $_POST["name"];
    $feedback = $_POST["feedback"];
    $email = $_POST["email"];
    $stmt->execute();

    // Close statement
    $stmt->close();

    // Close connection
    $conn->close();

    // Set success message in session
    $_SESSION["feedback_success"] = true;

    // Redirect to home.html
    header("Location: home.html");
    exit();
}
?>
