<?php
require_once '../includes/functions.php'; // This includes session_start()

// Check if an admin is currently logged in
if (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true) {
    // 1. Unset the admin-specific session variable
    unset($_SESSION['is_admin']); 
    
    // Log activity (Placeholder function call)
    // log_activity(0, 'Admin User Logged Out');
}

// Redirect to the Admin login page
redirect('login.php');
?>