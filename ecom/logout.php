<?php
require_once 'includes/functions.php'; // This includes session_start()

// Check if a user is currently logged in as B2B
if (isset($_SESSION['user_id'])) {
    // 1. Unset all session variables related to the B2B user
    unset($_SESSION['user_id']); 
    
    // 2. Destroy the session if needed (though usually done later)
    // session_destroy(); 
    
    // Log activity (Placeholder function call)
    // log_activity($user_id, 'B2B User Logged Out');
}

// Redirect to the public home page
redirect('index.php');
?>