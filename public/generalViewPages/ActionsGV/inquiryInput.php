<?php
// Start session
session_start();

// Include your database connection
include($_SERVER['DOCUMENT_ROOT'] . "/Zeppelin-Suites/public/php_files/db.php");

// Get form data and sanitize inputs
// Sanitize inputs
$sender_name = $conn->real_escape_string($_POST['sender_name']);
$sender_email = $conn->real_escape_string($_POST['sender_email']);
$sender_contact = $conn->real_escape_string($_POST['sender_contact']);
$inquiry_type = $conn->real_escape_string($_POST['inquiry_type']);
$Preferred_unit_id = isset($_POST['Preferred_unit_id']) ? $conn->real_escape_string($_POST['Preferred_unit_id']) : NULL;
$lease_duration = isset($_POST['lease_duration']) ? $conn->real_escape_string($_POST['lease_duration']) : NULL;
$Message = $conn->real_escape_string($_POST['Message']);
$status = "pending";  // Default status

// Prepare SQL query to insert inquiry into the database
$sql = "INSERT INTO Inquiry_table (sender_name, sender_email, sender_contact, inquiry_type, Preferred_unit_id, lease_duration, Message, status)
        VALUES ('$sender_name', '$sender_email', '$sender_contact', '$inquiry_type', '$Preferred_unit_id', '$lease_duration', '$Message', '$status')";
// Execute the query
if ($conn->query($sql) === TRUE) {
    $_SESSION['form_success'] = true;  // success flag
    header("Location: ../contact.php");  // Redirect on success
    exit();
} else {
    $_SESSION['form_success'] = false; // error flag
    $_SESSION['error_message'] = "There was an issue submitting the form. Please try again later.";  // Error message
    header("Location: ../contact.php");  // Redirect back to form page
    exit();
}
$conn->close(); // Close the database connection
exit();
?>