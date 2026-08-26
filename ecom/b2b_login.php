<?php
require_once 'includes/functions.php';
require_once 'includes/db.php';

// If the user is already logged in, redirect them to the dashboard
if (is_b2b_logged_in()) {
    redirect('b2b_dashboard.php');
}

$error = '';

// Handle Login Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $email = sanitize_input($_POST['email']);
    $password = $_POST['password']; // The password entered by the user

    // Prepare SQL to fetch user details
    $stmt = $conn->prepare("SELECT id, password_hash, is_active FROM b2b_users WHERE email = ?"); 
    $stmt->bind_param("s", $email); 
    $stmt->execute(); 
    $result = $stmt->get_result(); 

    if ($result->num_rows === 1) { 
        $user = $result->fetch_assoc();

        // ✅ SECURITY FIX: Use the secure verify_password function
        if (verify_password($password, $user['password_hash'])) {
            if ($user['is_active']) { 
                // Login successful - Set session variables
                $_SESSION['user_id'] = $user['id']; 
                // Optionally log activity: log_activity($user['id'], 'B2B Login Successful');

                // Redirect to the B2B Dashboard
                redirect('b2b_dashboard.php'); 
            } else {
                $error = 'Your account is currently inactive. Please contact the administrator.'; 
            }
        } else {
            // Password verification failed
            $error = 'Invalid email or password.'; 
        }
    } else {
        $error = 'Invalid email or password.'; 
    }

    $stmt->close(); 
}

// NOTE: For demonstration purposes, you need an admin to create the first B2B account
// manually in the DB, e.g., 'INSERT INTO b2b_users (contact_name, email, password_hash, is_active) VALUES ('Demo User', 'demo@b2b.com', '...hash...', TRUE);'

// ✅ LOGIC FIX: include header after all potential redirects
require_once 'includes/header.php';
?>

<div class="flex justify-center items-center py-10 px-4 sm:px-6">
    <div class="w-full max-w-md bg-white p-6 sm:p-8 rounded-xl shadow-2xl border border-gray-100">
        <h2 class="text-2xl sm:text-3xl font-bold text-gray-800 mb-6 text-center">B2B Partner Login</h2>

        <?php if ($error): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                <p class="text-sm"><?php echo htmlspecialchars($error); ?></p>
            </div>
        <?php endif; ?>

        <form method="POST" action="b2b_login.php">
            <input type="hidden" name="login" value="1">
            
            <div class="mb-5">
                <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email Address</label>
                <input type="email" id="email" name="email" required 
                       class="w-full px-4 py-2 sm:py-3 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 shadow-sm"
                       value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>">
            </div>

            <div class="mb-6">
                <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                <div class="relative">
                    <input type="password" id="password" name="password" required 
                           class="w-full px-4 py-2 sm:py-3 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 shadow-sm pr-16">
                    <button type="button" id="togglePassword" 
                            class="absolute inset-y-0 right-0 pr-3 flex items-center text-sm leading-5 text-gray-500 hover:text-indigo-600 focus:outline-none font-semibold">
                        <span id="toggleText">Show</span>
                    </button>
                </div>
            </div>
            <button type="submit" 
                    class="w-full py-2 sm:py-3 bg-indigo-600 text-white font-semibold rounded-lg shadow-md hover:bg-indigo-700 transition duration-150 transform hover:scale-[1.01]">
                Sign In Securely
            </button>
        </form>

        <div class="mt-6 text-center">
            <p class="text-xs text-gray-400 mt-3">Access is restricted to approved B2B accounts only.</p>
        </div>
    </div>
</div>

<?php
require_once 'includes/footer.php';
close_db_connection($conn);
?>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const passwordInput = document.getElementById('password');
        const toggleButton = document.getElementById('togglePassword');
        const toggleText = document.getElementById('toggleText');

        toggleButton.addEventListener('click', function (e) {
            // Toggle the type attribute
            const isPasswordHidden = passwordInput.getAttribute('type') === 'password';

            if (isPasswordHidden) {
                // Currently hidden -> SHOW password (set type to 'text')
                passwordInput.setAttribute('type', 'text');
                // Change text to 'Hide'
                toggleText.textContent = 'Hide';
            } else {
                // Currently shown -> HIDE password (set type to 'password')
                passwordInput.setAttribute('type', 'password');
                // Change text to 'Show'
                toggleText.textContent = 'Show';
            }
        });
    });
</script>