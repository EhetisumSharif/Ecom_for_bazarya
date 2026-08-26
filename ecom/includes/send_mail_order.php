<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Adjust paths based on your directory structure
require __DIR__ . '/../PHPMailer/src/Exception.php';
require __DIR__ . '/../PHPMailer/src/PHPMailer.php';
require __DIR__ . '/../PHPMailer/src/SMTP.php';

// IMPORTANT: This file assumes you have PHPMailer library in a folder named 'PHPMailer/src/'
// This function requires the format_aed() function to be available, usually via functions.php

/**
 * Helper function to replace template variables with actual order data.
 * NOTE: format_aed() is assumed to be available.
 */
function replace_template_variables($template, $order_details, $order_id) {
    // Map placeholders to actual data
    $replacements = [
        '{ORDER_ID}'     => htmlspecialchars($order_id),
        '{CONTACT_NAME}' => htmlspecialchars($order_details['contact_name'] ?? 'Customer'),
        '{COMPANY_NAME}' => htmlspecialchars($order_details['company_name'] ?? 'N/A'),
        // Format the currency amount using the external function format_aed()
        // This line assumes format_aed() is defined elsewhere (e.g., functions.php)
        '{TOTAL_AMOUNT}' => format_aed($order_details['total_amount']),
        // Preserve line breaks for address field
        '{ADDRESS}'      => nl2br(htmlspecialchars($order_details['delivery_address'] ?? 'N/A')),
    ];
    
    // Perform the replacement
    $body = str_replace(array_keys($replacements), array_values($replacements), $template);

    // Wrap the body in a basic HTML structure for better email formatting
    $html_body = "<html><body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>";
    // Convert newlines in the plain text template to <br> for HTML email
    $html_body .= "<div style='padding: 20px; border: 1px solid #eee; border-radius: 5px; white-space: pre-wrap;'>" . nl2br($body) . "</div>";
    $html_body .= "</body></html>";
    
    return $html_body;
}


/**
 * Sends order confirmation emails to both the customer and the admin.
 * @param int $order_id The ID of the order.
 * @param array $order_details Array containing contact_name, total_amount, etc.
 * @param string $customer_email Customer's email address.
 * @param string $buyer_template The custom buyer confirmation template from settings.
 * @param string $admin_email The Admin/Seller email (Contact Form Recipient Email) from settings.
 * @param string $admin_template The fixed or custom admin notification template.
 * @param string $site_name The name of the website.
 * @return bool True if both emails were sent successfully, false otherwise.
 */
function sendOrderEmail($order_id, $order_details, $customer_email, $buyer_template, $admin_email, $admin_template, $site_name) {
    $mail = new PHPMailer(true);
    try {
        // Server settings (MUST be replaced with secure credentials)
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com'; 
        $mail->SMTPAuth = true;
        // !! CRITICAL: REPLACE WITH YOUR SECURE CREDENTIALS !!
        // NOTE: These credentials must be changed for production use.
        $mail->Username = 'bazaryatradingafz@gmail.com'; 
        $mail->Password = 'bihi dbjr yvfc riep'; // <--- USE A GMAIL APP PASSWORD
        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;
        
        // Sender address (must be the same as Username for Gmail SMTP)
        $sender_address = 'bazaryatradingafz@gmail.com'; 
        $mail->isHTML(true); // Set to send HTML email

        // --- 1. CUSTOMER EMAIL (Uses Customizable Buyer Template) ---
        $mail->ClearAllRecipients();
        $mail->setFrom($sender_address, $site_name . ' Orders');
        $mail->addAddress($customer_email);
        $mail->Subject = "Order Confirmation - ID #{$order_id} - Thank You!";
        
        // Use the custom buyer template and replace variables
        $mail->Body = replace_template_variables($buyer_template, $order_details, $order_id);
        
        $customer_sent = $mail->send();
        
        // --- 2. ADMIN EMAIL (Uses Admin Template and Admin/Seller Email from settings) ---
        $mail->ClearAllRecipients(); // Clear customer recipient
        // Use the admin email from the settings.php file
        $mail->addAddress($admin_email); 
        $mail->Subject = "NEW Order Placed: #{$order_id} by " . ($order_details['company_name'] ?? 'Unknown Company');
        
        // Use the admin template and replace variables
        $mail->Body = replace_template_variables($admin_template, $order_details, $order_id);

        $admin_sent = $mail->send();

        return $customer_sent && $admin_sent;
    } catch (Exception $e) {
        // For debugging, you can use: error_log("Mailer Error: " . $mail->ErrorInfo);
        return false;
    }
}
?>