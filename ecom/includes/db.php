<?php
// includes/db.php - Database Connection Setup

// --- Configuration (Update these for your environment) ---
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'b2b_ecommerce_db'); // Assuming you created this DB via the schema

// Attempt to connect to MySQL database
$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

// Check connection
if ($conn === false) {
    // Note: In production, do not expose connection error details.
    die("ERROR: Could not connect. " . $conn->connect_error);
}

// Function to safely close the connection
function close_db_connection($conn) {
    if ($conn) {
        $conn->close();
    }
}
?>