<?php
// includes/functions.php - Core Utility Functions

// Start session immediately to manage user state
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

/**
 * Checks if the current user is logged in (B2B).
 * @return bool
 */
function is_b2b_logged_in() {
    return isset($_SESSION['user_id']);
}

/**
 * Redirects the user to a specified URL.
 * @param string $url The destination URL.
 */
function redirect($url) {
    header("Location: " . $url);
    exit;
}

/**
 * Sanitizes input data before use (basic security).
 * @param string $data The input data.
 * @return string The sanitized data.
 */
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

/**
 * Hashes a password for secure storage (Requirement H).
 * @param string $password The plain text password.
 * @return string The hashed password.
 */
function hash_password($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

/**
 * Verifies a password against a hash.
 * ⚠️ DANGER: MODIFIED to compare plain-text passwords directly (INSECURE).
 * @param string $password The plain text password entered by user.
 * @param string $stored_password The plain text password stored in the DB (was 'hash').
 * @return bool True if password matches, false otherwise.
 */
function verify_password($password, $stored_password) {
    // ⚠️ INSECURE: Comparing two strings directly.
    return $password === $stored_password;
}

/**
 * Formats a price into AED currency string.
 * @param float $price The numeric price value.
 * @return string Formatted price string.
 */
function format_aed($price) {
    // MODIFIED: Changed currency prefix from 'AED ' to 'د.إ '
    return 'د.إ ' . number_format($price, 2);
}

/**
 * Gets the count of distinct products currently in the B2B cart.
 * CORRECTION: This now uses the $_SESSION['user_id'] to look up the correct cart array.
 * @return int The number of unique items in the cart.
 */
function get_cart_item_count() {
    // Check if a user is logged in
    if (isset($_SESSION['user_id'])) {
        $user_id = $_SESSION['user_id'];
        
        // Check if the cart exists for THIS user and count the items inside it
        if (isset($_SESSION['cart'][$user_id]) && is_array($_SESSION['cart'][$user_id])) {
            return count($_SESSION['cart'][$user_id]);
        }
    }
    return 0;
}

// =========================================================================
// NEW: Order Confirmation Email Functionality (Customer & Admin)
// =========================================================================

// Include the new PHPMailer utility file for order confirmation
require_once 'send_mail_order.php'; 

/**
 * Sends order confirmation emails to the customer and the admin using PHPMailer.
 * NOTE: This function now correctly loads settings and passes 7 arguments to sendOrderEmail().
 * @param int $order_id The ID of the newly placed order.
 * @param array $order_details Contains full order data (total, company, contact, address).
 * @param string $customer_email The email address provided by the customer at checkout.
 * @return bool True if emails were sent successfully, False otherwise.
 */
function send_order_confirmation_email($order_id, $order_details, $customer_email) {
    global $conn;
    
    // --- 1. LOAD CONFIGURATION FROM settings.php LOGIC ---
    // In a real application, these would be loaded from the database or a saved config file.
    
    // Simulate loading the saved B2B Buyer Template and Contact Email
    // NOTE: This assumes you have a get_setting() or similar function using $conn
    // For this example, we'll hardcode the defaults defined in settings.php:
    
    $site_name = 'Bazarya Trading AFZ'; // Site Name
    
    // The admin/seller email (Contact Form Recipient Email from settings.php)
    // CRITICAL: This MUST be fetched from where settings.php saves it.
    // Simulating the default value:
    $admin_email = 'bazaryatradingafz@gmail.com'; 
    
    // The customizable buyer template (from settings.php)
    // CRITICAL: This MUST be fetched from where settings.php saves it.
    // Simulating the default value:
    $buyer_template = "Dear {CONTACT_NAME},\nThank you for your order! Your Order ID is {ORDER_ID} for a total of {TOTAL_AMOUNT}. Payment is Cash on Delivery.\nNote: Only product price charged, no extra fees.\nRegards,\nBazarya Trading AFZ";
    
    // The fixed admin template (from settings.php default)
    $admin_template = "New Order: {ORDER_ID} placed by {COMPANY_NAME}. \nTotal: {TOTAL_AMOUNT}. \nDelivery Address: {ADDRESS}";


    // 2. CALL THE PHPMailer function implemented in send_mail_order.php with all 7 arguments
    return sendOrderEmail(
        $order_id, 
        $order_details, 
        $customer_email, 
        $buyer_template,    // Argument 4 (Missing before)
        $admin_email,       // Argument 5 (The correct admin recipient)
        $admin_template,    // Argument 6 (Missing before)
        $site_name          // Argument 7
    );
}

// Placeholder for Activity Log (Requirement H)
// function log_activity($user_id, $action) { ... }

// Placeholder for Database Backup Option (Requirement H)
// function perform_db_backup() { ... }
?>