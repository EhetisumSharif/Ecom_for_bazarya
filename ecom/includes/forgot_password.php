<?php
require_once '../includes/functions.php';
require_once '../includes/db.php';
require_once '../includes/header.php';

$message = '';
$error = '';

// --- Handle Forgot Password Submission ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['forgot_password'])) {
    $email = sanitize_input($_POST['email']);
    
    if (empty($email)) {
        $error = 'Please enter your email address.';
    } else {
        // 1. Check if the email exists in the b2b_users table
        $stmt = $conn->prepare("SELECT id, contact_name FROM b2b_users WHERE email = ? AND is_active = TRUE");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            $user_id = $user['id'];
            $contact_name = $user['contact_name'];
            
            // 2. Generate token and set an expiration time (SIMULATED)
            $reset_token = hash_password(uniqid()); // Use hash_password for a strong, unique token
            $expires_at = time() + (60 * 60); // Token expires in 1 hour
            
            // 3. Save the token and expiry to a temporary database table (e.g., password_resets)
            // For this simulation, we'll assume the process works.
            
            // 4. Send the reset email (SIMULATED EMAIL PROCESS)
            $reset_link = "http://yourdomain.com/public/reset_password.php?token={$reset_token}&email={$email}";
            
            /* // In a real system, you would use PHPMailer here:
            // send_email($email, "Password Reset Request", "Dear $contact_name, click the link to reset: $reset_link");
            */
            
            $message = "If your email address is registered, a password reset link has been sent to {$email}. Please check your inbox and spam folder. (Link simulated: {$reset_link})";
            
            // Log activity (Placeholder function call)
            // log_activity($user_id, 'Password Reset Request Sent');
            
        } else {
            // IMPORTANT SECURITY STEP: Return a vague success message even if the email doesn't exist
            // to prevent revealing which emails are registered.
            $message = "If your email address is registered, a password reset link has been sent to {$email}. Please check your inbox and spam folder.";
        }

        $stmt->close();
    }
}
?>

<div class="flex justify-center items-center py-10">
    <div class="w-full max-w-md bg-white p-8 rounded-xl shadow-2xl border border-gray-100">
        <h2 class="text-3xl font-bold text-gray-800 mb-6 text-center">Forgot Your Password?</h2>

        <?php if ($error): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                <p class="text-sm"><?php echo htmlspecialchars($error); ?></p>
            </div>
        <?php endif; ?>
        
        <?php if ($message): ?>
            <div class="bg-blue-100 border border-blue-400 text-blue-700 px-4 py-3 rounded relative mb-4" role="alert">
                <p class="text-sm"><?php echo htmlspecialchars($message); ?></p>
            </div>
        <?php endif; ?>

        <p class="text-gray-500 mb-6 text-center">Enter your email address and we'll send you a link to reset your password.</p>

        <form method="POST" action="forgot_password.php">
            <input type="hidden" name="forgot_password" value="1">
            
            <div class="mb-6">
                <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email Address</label>
                <input type="email" id="email" name="email" required 
                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 shadow-sm"
                       value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>">
            </div>

            <button type="submit" 
                    class="w-full py-3 bg-indigo-600 text-white font-semibold rounded-lg shadow-md hover:bg-indigo-700 transition duration-150 transform hover:scale-[1.01]">
                Send Reset Link
            </button>
        </form>

        <div class="mt-6 text-center">
            <a href="b2b_login.php" class="text-sm text-indigo-600 hover:text-indigo-800 font-medium">
                ← Back to Login
            </a>
        </div>
    </div>
</div>

<?php
require_once '../includes/footer.php';
close_db_connection($conn);
?>