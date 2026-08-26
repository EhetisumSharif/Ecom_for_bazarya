<?php
require_once 'includes/functions.php';
require_once 'includes/db.php';
require_once 'includes/header.php';

// --- NEW: Include PHPMailer files for Contact Form
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Assuming PHPMailer library is correctly set up as per send_mail_order.php's configuration
require_once __DIR__ . '/PHPMailer/src/Exception.php';
require_once __DIR__ . '/PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/PHPMailer/src/SMTP.php';


// --- START: COUNTRY DATA ---

// Define the countries and their corresponding data
$countries_data = [
    // UAE added as the market base
    '+971' => ['name' => 'UAE', 'flag' => '🇦🇪'],
    '+880' => ['name' => 'Bangladesh', 'flag' => '🇧🇩'],
    '+20'  => ['name' => 'Egypt', 'flag' => '🇪🇬'],
    '+91'  => ['name' => 'India', 'flag' => '🇮🇳'],
    '+92'  => ['name' => 'Pakistan', 'flag' => '🇵🇰'],
    '+63'  => ['name' => 'Philippines', 'flag' => '🇵🇭'],
    '+84'  => ['name' => 'Vietnam', 'flag' => '🇻🇳'],
];

// --- END: COUNTRY DATA ---


/**
 * Sends the contact form message to the admin.
 * NOTE: Uses the same SMTP settings as send_mail_order.php
 * @param string $country_code The phone number's country code (from select). 
 * @param string $local_phone The local part of the phone number.
 */
function sendContactMessageEmail($name, $user_email, $country_code, $local_phone, $subject, $message, $admin_email) {
    global $countries_data; // Access the global country data
    $mail = new PHPMailer(true);
    
    // Combine phone parts for the email body
    $full_phone = !empty($country_code) || !empty($local_phone) ? htmlspecialchars($country_code . $local_phone) : 'N/A';
    
    // Get country name for display in email
    $country_name = $countries_data[$country_code]['name'] ?? 'N/A';

    try {
        // --- Server settings (Copied from send_mail_order.php) ---
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com'; 
        $mail->SMTPAuth = true;
        $mail->Username = 'bazaryatradingafz@gmail.com'; 
        $mail->Password = 'bihi dbjr yvfc riep'; // <--- USE A GMAIL APP PASSWORD
        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;
        
        // Sender address (must be the same as Username for Gmail SMTP)
        $sender_address = 'bazaryatradingafz@gmail.com'; 
        $site_name = 'Bazarya Trading AFZ'; // Using site name from functions.php

        $mail->isHTML(true); 
        $mail->setFrom($sender_address, $site_name . ' Contact Form');
        
        // --- Recipient: Admin ---
        $mail->addAddress($admin_email); 
        
        // --- Content ---
        $mail->Subject = "Contact Form Inquiry: " . htmlspecialchars($subject);
        
        $body_html = "<html><body style='font-family: Arial, sans-serif; line-height: 1.6;'>";
        $body_html .= "<h2 style='color: #4CAF50;'>New Contact Message Received</h2>";
        $body_html .= "<p><strong>From:</strong> " . htmlspecialchars($name) . "</p>";
        $body_html .= "<p><strong>Email:</strong> " . htmlspecialchars($user_email) . "</p>";
        // UPDATED: Include Country Name in Email Body
        $body_html .= "<p><strong>Phone:</strong> {$full_phone} (Country: {$country_name})</p>"; 
        $body_html .= "<p><strong>Subject:</strong> " . htmlspecialchars($subject) . "</p>";
        $body_html .= "<hr style='border: 0; border-top: 1px solid #eee;'>";
        $body_html .= "<p><strong>Message:</strong></p>";
        $body_html .= "<div style='padding: 15px; background-color: #f9f9f9; border-left: 3px solid #4CAF50; white-space: pre-wrap;'>" . nl2br(htmlspecialchars($message)) . "</div>";
        $body_html .= "</body></html>";
        
        $mail->Body = $body_html;
        $mail->AltBody = "From: {$name} ({$user_email})\nPhone: {$full_phone} (Country: {$country_name})\nSubject: {$subject}\n\nMessage: {$message}";

        return $mail->send();
    } catch (Exception $e) {
        error_log("Contact Form Mailer Error: " . $mail->ErrorInfo);
        return false;
    }
}


$success_message = '';
$error_message = '';

// Handle Contact Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['contact_form'])) {
    $name = sanitize_input($_POST['name']);
    $email = sanitize_input($_POST['email']);
    // Get and sanitize Country Code and Local Phone
    $country_code = sanitize_input($_POST['country_code'] ?? ''); 
    $local_phone = sanitize_input($_POST['local_phone'] ?? '');   
    $subject = sanitize_input($_POST['subject']);
    $message = sanitize_input($_POST['message']);

    // --- EMAIL PROCESSING ---
    $admin_email = "bazaryatradingafz@gmail.com"; 
    
    // Check if required fields are present
    // UPDATED PHP LOGIC: All fields are checked as required
    if (empty($name) || empty($email) || empty($country_code) || empty($local_phone) || empty($subject) || empty($message)) {
        $error_message = 'Please fill out all required fields (Name, Email, Country Code, Local Number, Subject, Message).';
    } else {
        
        // --- NEW: Pass all contact components to the email function ---
        if (sendContactMessageEmail($name, $email, $country_code, $local_phone, $subject, $message, $admin_email)) {
            $success_message = 'Thank you for your message us!';
        } else {
            $error_message = 'Failed to send your message. Please try again or contact us by phone.';
        }

        // You might log this request to a simple 'contact_requests' table in the DB for audit
    }
}
?>

<div class="pb-8 px-4 sm:px-6 lg:px-8">
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        
        <div class="lg:col-span-1 bg-white p-6 rounded-xl shadow-2xl border border-gray-100 h-fit">
            <h3 class="text-2xl font-bold text-gray-800 mb-4 border-b pb-2">Get In Touch</h3>
            <p class="text-gray-600 mb-6 text-sm">Visit our stand at Ras Al Khor or contact us for wholesale inquiries.</p>

            <div class="space-y-4 text-sm">
                <div>
                    <span class="font-semibold text-green-600 block">Phone/WhatsApp</span>
                    <p class="text-gray-700">+971 55 168 7755</p>
                </div>
                <div>
                    <span class="font-semibold text-green-600 block">Email</span>
                    <p class="text-gray-700">taqwasteelaluminum@gmail.com</p>
                </div>
                <div>
                    <span class="font-semibold text-green-600 block">Open Hours</span>
                    <p class="text-gray-700">8 AM – 6 PM (Mon–Sat)</p>
                </div>
                <div>
                    <span class="font-semibold text-green-600 block">Address</span>
                    <p class="text-gray-700">Ras Al Khor Central Fruits & Vegetables Market, Dubai, UAE</p>
                </div>
            </div>
        </div>

        <div class="lg:col-span-2 bg-white p-6 sm:p-8 rounded-xl shadow-2xl border border-gray-100">
            <h3 class="text-2xl font-bold text-gray-800 mb-6 border-b pb-2">Send Us a Message</h3>

            <?php if ($success_message): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
                    <p class="text-sm"><?php echo htmlspecialchars($success_message); ?></p>
                </div>
            <?php endif; ?>

            <?php if ($error_message): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                    <p class="text-sm"><?php echo htmlspecialchars($error_message); ?></p>
                </div>
            <?php endif; ?>

            <form method="POST" action="contact.php" class="space-y-5">
                <input type="hidden" name="contact_form" value="1">
                
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700">Your Name <span class="text-red-500">*</span></label>
                    <input type="text" id="name" name="name" required 
                           class="w-full mt-1 px-4 py-3 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500 shadow-sm"
                           value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>">
                </div>

                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700">Email Address <span class="text-red-500">*</span></label>
                    <input type="email" id="email" name="email" required 
                           class="w-full mt-1 px-4 py-3 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500 shadow-sm"
                           value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                </div>
                
                <div>
                    <label for="local_phone" class="block text-sm font-medium text-gray-700">Phone Number <span class="text-red-500">*</span></label>
                    <div class="flex space-x-2 mt-1">
                        <select id="country_code" name="country_code" required
                                class="w-1/3 max-w-[150px] px-2 sm:px-4 py-3 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500 shadow-sm text-sm">
                            <option value="">Code</option>
                            <?php foreach ($countries_data as $code => $country): ?>
                                <option value="<?php echo $code; ?>" <?php echo (($_POST['country_code'] ?? '') == $code) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($country['flag'] . ' ' . $country['name'] . ' (' . $code . ')'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <input type="tel" id="local_phone" name="local_phone" placeholder="Local Number" required
                               class="flex-grow px-4 py-3 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500 shadow-sm">
                    </div>
                </div>

                <div>
                    <label for="subject" class="block text-sm font-medium text-gray-700">Subject <span class="text-red-500">*</span></label>
                    <input type="text" id="subject" name="subject" required
                           class="w-full mt-1 px-4 py-3 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500 shadow-sm"
                           value="<?php echo htmlspecialchars($_POST['subject'] ?? ''); ?>">
                </div>

                <div>
                    <label for="message" class="block text-sm font-medium text-gray-700">Message <span class="text-red-500">*</span></label>
                    <textarea id="message" name="message" rows="5" required 
                              class="w-full mt-1 px-4 py-3 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500 shadow-sm"><?php echo htmlspecialchars($_POST['message'] ?? ''); ?></textarea>
                </div>

                <button type="submit" 
                        class="w-full py-3 bg-green-600 text-white font-semibold rounded-lg shadow-md hover:bg-green-700 transition duration-150">
                    Send Message
                </button>
            </form>
        </div>
    </div>
</div>

<?php
require_once 'includes/footer.php';
close_db_connection($conn);
?>