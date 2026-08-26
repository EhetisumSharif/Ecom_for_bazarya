<?php
require_once '../includes/functions.php';
require_once '../includes/db.php'; 

// Admin session flag
if (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true) {
    redirect('index.php'); // Redirect to the main Admin dashboard
}

$error = '';

// --- Handle Login Form Submission ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['admin_login'])) {
    
    // INPUTS
    $email = sanitize_input($_POST['email']);
    $password = $_POST['password']; // Password remains plain text
    
    // 1. Fetch the admin user and the password (which is stored as PLAIN TEXT per request)
    $sql = "SELECT id, password FROM admin_users WHERE email = ?";
    
    if (!isset($conn) || $conn->connect_error) {
        $error = 'Database connection error.';
    } else {
        $stmt = $conn->prepare($sql);
        
        if ($stmt) {
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            
            // Check if user exists
            if ($result->num_rows === 1) {
                $admin = $result->fetch_assoc();
                // WARNING: This variable now holds the PLAIN TEXT password from the database.
                $stored_password_plaintext = $admin['password']; 
                
                // 2. INSECURE FIX: Use direct comparison to check the plain password against the plain text stored in the database
                if ($password === $stored_password_plaintext) {
                    
                    // Login successful - Set Admin session variables
                    $_SESSION['is_admin'] = true;
                    $_SESSION['admin_id'] = $admin['id']; 

                    // Redirect to Admin Product Management page
                    redirect('index.php'); 
                    
                } else {
                    // Invalid Password (or email/password combination)
                    $error = 'Invalid admin credentials.';
                }
            } else {
                // Invalid Email (or user does not exist)
                $error = 'Invalid admin credentials.';
            }

            $stmt->close();
        } else {
             $error = 'SQL preparation error.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@100..900&display=swap');
        body { font-family: 'Inter', sans-serif; background-color: #f3f4f6; }
    </style>
</head>
<body class="flex items-center justify-center min-h-screen p-4 sm:p-8">

<div class="w-full max-w-md bg-white p-6 sm:p-8 rounded-xl shadow-lg sm:shadow-2xl border border-gray-100">
    <h2 class="text-3xl font-bold text-gray-800 mb-2 text-center">Admin Panel</h2>
    <p class="text-center text-gray-500 mb-6">Secured Access Required</p>

    <?php if ($error): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
            <p class="text-sm"><?php echo htmlspecialchars($error); ?></p>
        </div>
    <?php endif; ?>

    <form method="POST" action="login.php">
        <input type="hidden" name="admin_login" value="1">
        
        <div class="mb-5">
            <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Admin Email</label>
            <input type="email" id="email" name="email" required 
                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-red-500 focus:border-red-500 shadow-sm"
                   value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>">
        </div>

        <div class="mb-6">
            <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Password</label>
            <div class="relative">
                <input type="password" id="password" name="password" required 
                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-red-500 focus:border-red-500 shadow-sm pr-16">
                <button type="button" id="togglePassword" 
                        class="absolute inset-y-0 right-0 pr-3 flex items-center text-sm leading-5 text-gray-500 hover:text-red-600 focus:outline-none font-semibold">
                    <span id="toggleText">Show</span>
                </button>
            </div>
        </div>
        <button type="submit" 
                class="w-full py-3 bg-red-600 text-white font-semibold rounded-lg shadow-md hover:bg-red-700 transition duration-150 transform hover:scale-[1.01]">
            Login
        </button>
    </form>
    
    <div class="mt-6 text-center">
        <a href="../index.php" class="text-sm text-gray-500 hover:text-gray-700 font-medium">
            ← Return to Public Site
        </a>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const passwordInput = document.getElementById('password');
        const toggleButton = document.getElementById('togglePassword');
        const toggleText = document.getElementById('toggleText');

        toggleButton.addEventListener('click', function (e) {
            // Check the current state: Is the password input type 'password'?
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

</body>
</html>