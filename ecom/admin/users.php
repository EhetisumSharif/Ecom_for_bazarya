<?php
require_once '../includes/functions.php';
require_once '../includes/db.php';

// --- Admin Authentication Check (Requirement H) ---
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    redirect('login.php');
}

$message = '';
$error = '';

// --- Admin Navigation Header/Footer with Responsive Toggle (Updated) ---
function render_admin_header($page_title) {
    $current_page = basename($_SERVER['PHP_SELF']);
    
    // Define base classes for all links
    $base_classes = "block px-4 py-2 text-sm font-medium rounded-lg transition duration-150";
    
    // Define active and inactive class groups
    $active_classes = "bg-red-700 text-white";
    $inactive_classes = "text-red-100 hover:bg-red-600";
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0, shrink-to-fit=no">
		<link rel="icon" href="../logo.jpeg" type="image/jpeg">
        <link rel="apple-touch-icon" href="../logo.jpeg">
        <title>Admin | <?php echo htmlspecialchars($page_title); ?></title>
        <script src="https://cdn.tailwindcss.com"></script>
        <style>
            @import url('https://fonts.googleapis.com/css2?family=Inter:wght@100..900&display=swap');
            body { font-family: 'Inter', sans-serif; background-color: #f3f4f6; overflow-x: hidden; }
            .clear-both { clear: both; }
        </style>
    </head>
    <body>
    <div class="flex min-h-screen">
        
        <!-- Responsive Sidebar -->
        <aside id="adminSidebar" class="w-64 bg-red-800 text-white p-6 fixed z-30 top-0 left-0 h-screen transition-transform duration-300 transform -translate-x-full md:relative md:translate-x-0 md:block">
            <h1 class="text-2xl font-bold mb-8 border-b border-red-700 pb-4">B2B Admin</h1>
            <nav class="space-y-2">
                <a href="index.php" class="<?php echo $base_classes; ?> <?php echo $current_page == 'index.php' ? $active_classes : $inactive_classes; ?>">
                    Dashboard
                </a>
                <a href="products_crud.php" class="<?php echo $base_classes; ?> <?php echo $current_page == 'products_crud.php' ? $active_classes : $inactive_classes; ?>">
                    Product Management
                </a>
                <a href="orders.php" class="<?php echo $base_classes; ?> <?php echo $current_page == 'orders.php' ? $active_classes : $inactive_classes; ?>">
                    Order Management
                </a>
                <a href="users.php" class="<?php echo $base_classes; ?> <?php echo $current_page == 'users.php' ? $active_classes : $inactive_classes; ?>">
                    B2B User Management
                </a>
                <a href="settings.php" class="<?php echo $base_classes; ?> <?php echo $current_page == 'settings.php' ? $active_classes : $inactive_classes; ?>">
                    Settings & Backups
                </a>
            </nav>
            <div class="mt-8 pt-4 border-t border-red-700">
                <a href="logout.php" class="block px-4 py-2 text-sm font-medium rounded-lg transition duration-150 bg-red-600 hover:bg-red-500 text-white">
                    Logout
                </a>
            </div>
        </aside>
        
        <div id="sidebarOverlay" class="fixed inset-0 bg-black opacity-0 z-20 transition-opacity duration-300 pointer-events-none md:hidden"></div>

        <main class="flex-grow p-4 md:p-8">
            
            <!-- Mobile Toggle Button -->
            <button id="sidebarToggle" class="mb-4 p-2 bg-red-800 text-white rounded-lg shadow-lg md:hidden float-left mr-3">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
            </button>
            
            <h1 class="text-3xl font-bold text-gray-800 mb-6 block md:inline-block">
                <?php echo htmlspecialchars($page_title); ?>
            </h1>
            <div class="clear-both md:hidden"></div> 
    <?php
}
function render_admin_footer() {
    ?>
        <footer class="mt-8 pt-4 border-t border-gray-300 text-center text-sm text-gray-500">
            <p>&copy; <?php echo date("Y"); ?> Bazarya Trading AFZ. All rights reserved.</p>
            <p>Powerd by: Aelyth IT solution</p>
        </footer>
        </main>
    </div>

    <script>
        // --- Sidebar Toggle Logic (Copied from orders.php) ---
        const sidebar = document.getElementById('adminSidebar');
        const toggleButton = document.getElementById('sidebarToggle');
        const overlay = document.getElementById('sidebarOverlay');

        function toggleSidebar() {
            const isHidden = sidebar.classList.contains('-translate-x-full');

            if (isHidden) {
                // Show sidebar
                sidebar.classList.remove('-translate-x-full');
                sidebar.classList.add('translate-x-0');
                overlay.classList.remove('opacity-0', 'pointer-events-none');
                overlay.classList.add('opacity-50');
            } else {
                // Hide sidebar
                sidebar.classList.remove('translate-x-0');
                sidebar.classList.add('-translate-x-full');
                overlay.classList.remove('opacity-50');
                overlay.classList.add('opacity-0', 'pointer-events-none');
            }
        }

        toggleButton.addEventListener('click', toggleSidebar);
        overlay.addEventListener('click', toggleSidebar); // Close when clicking outside

        // Optionally, close sidebar on link click (important for mobile UX)
        sidebar.querySelectorAll('a').forEach(link => {
            link.addEventListener('click', () => {
                // Check if on a mobile screen (Tailwind's md breakpoint is 768px)
                if (window.innerWidth < 768) { 
                    toggleSidebar();
                }
            });
        });
        
        // --- End Sidebar Toggle Logic ---
        
        // --- Modal Control ---
        function openModal(id) {
            document.getElementById(id).classList.remove('hidden');
        }

        function closeModal(id) {
            document.getElementById(id).classList.add('hidden');
        }
        
        // New function to open the Reset Password modal and populate fields
        function openResetPasswordModal(userId, userEmail) {
            document.getElementById('reset_user_id').value = userId;
            document.getElementById('reset_user_email').textContent = userEmail;
            document.getElementById('new_password_field').value = '';
            openModal('resetPasswordModal');
        }
        
        // Close modal on outside click
        document.getElementById('createModal').addEventListener('click', (e) => {
            if (e.target === document.getElementById('createModal')) {
                closeModal('createModal');
            }
        });

        // Close reset password modal on outside click
        document.getElementById('resetPasswordModal').addEventListener('click', (e) => {
            if (e.target === document.getElementById('resetPasswordModal')) {
                closeModal('resetPasswordModal');
            }
        });
    </script>
    
    </body>
    </html>
    <?php
}
// --- End Admin Header/Footer ---


// --- 1. CRUD and Action Logic (Requirement F.3) ---

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $user_id = (int)($_POST['user_id'] ?? 0);

    // --- Create Account (Still uses raw password storage as per original file) ---
    if ($action === 'create') {
        $contact_name = sanitize_input($_POST['contact_name'] ?? '');
        $email = sanitize_input($_POST['email'] ?? '');
        $phone = sanitize_input($_POST['phone'] ?? '');
        $company_name = sanitize_input($_POST['company_name'] ?? '');
        $initial_password = $_POST['initial_password'] ?? '';
        
        // --- Store raw password instead of hash ---
        $raw_password = $initial_password; // STORE RAW PASSWORD
        
        if (empty($contact_name) || empty($email) || empty($initial_password)) {
            $error = "Name, Email, and Initial Password are required to create an account.";
        } else {
            $sql = "INSERT INTO b2b_users (contact_name, email, password_hash, phone, company_name, is_active) VALUES (?, ?, ?, ?, ?, TRUE)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssss", $contact_name, $email, $raw_password, $phone, $company_name); // BIND RAW PASSWORD

            if ($stmt->execute()) {
                $message = "Account for {$contact_name} created successfully. Initial password: {$initial_password}";
                // log_activity(0, "Created B2B User: {$email}");
            } else {
                $error = "Error creating account. Email may already exist. " . $conn->error;
            }
            $stmt->close();
        }
    }

    // --- Activate/Deactivate Account ---
    if ($action === 'toggle_status' && $user_id > 0) {
        $new_status = (int)($_POST['new_status'] ?? 0); // 1 for active, 0 for inactive
        $status_word = $new_status ? 'Activated' : 'Deactivated';
        
        $sql = "UPDATE b2b_users SET is_active = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $new_status, $user_id);

        if ($stmt->execute()) {
            $message = "User successfully {$status_word}.";
            // log_activity(0, "Toggled B2B User status ID: {$user_id} to {$status_word}");
        } else {
            $error = "Error updating user status: " . $conn->error;
        }
        $stmt->close();
    }
    
    // --- Reset Password (Stores new password RAW) ---
    if ($action === 'reset_password' && $user_id > 0) {
        $new_password = $_POST['new_password'] ?? ''; 
        
        if (empty($new_password)) {
             $error = "New Password is required to reset (change) the password.";
        } else {
            $raw_new_password = $new_password; // STORE RAW USER-DEFINED PASSWORD
            
            $sql = "UPDATE b2b_users SET password_hash = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("si", $raw_new_password, $user_id); // BIND RAW PASSWORD

            if ($stmt->execute()) {
                $message = "Password reset successfully.";
                // log_activity(0, "Set new raw password for B2B User ID: {$user_id}");
            } else {
                $error = "Error resetting password: " . $conn->error;
            }
            $stmt->close();
        }
    }
    
    // Redirect to clear POST data
    if (empty($error)) {
        redirect('users.php?msg=' . urlencode($message));
    }
}

// Check for successful redirection message
if (isset($_GET['msg'])) {
    $message = sanitize_input($_GET['msg']);
}


// --- 2. Data Fetching: All B2B Users ---

$users = [];
$sql = "SELECT id, contact_name, email, phone, company_name, is_active, created_at FROM b2b_users ORDER BY created_at DESC";
$result = $conn->query($sql);

if ($result) {
    while($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
}

render_admin_header('B2B User Management');
?>

<?php if ($message): ?>
    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
        <p class="text-sm"><?php echo $message; ?></p>
    </div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
        <p class="text-sm"><?php echo htmlspecialchars($error); ?></p>
    </div>
<?php endif; ?>

<div class="bg-white p-4 md:p-6 rounded-xl shadow-lg mb-8 border border-gray-100">
    <h2 class="text-2xl font-bold text-gray-800 mb-4 border-b pb-2 flex justify-between items-center">
        Registered B2B Users (<?php echo count($users); ?>)
        <button onclick="openModal('createModal')" class="bg-red-600 hover:bg-red-700 text-white font-semibold py-2 px-4 rounded-lg shadow-md transition duration-150 text-sm whitespace-nowrap">
            + Create New Account
        </button>
    </h2>

    <!-- Desktop Table View -->
    <div class="overflow-x-auto hidden md:block">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase">ID / Date</th>
                    <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase">Contact / Company</th>
                    <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase">Email / Phone</th>
                    <th class="px-3 py-3 text-center text-xs font-medium text-gray-500 uppercase">Status</th>
                    <th class="px-3 py-3 text-center text-xs font-medium text-gray-500 uppercase w-48">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php if (!empty($users)): ?>
                    <?php foreach ($users as $user): 
                        $is_active = (bool)$user['is_active'];
                    ?>
                    <tr class="<?php echo $is_active ? '' : 'bg-gray-50 text-gray-500'; ?> hover:bg-gray-50">
                        <td class="px-3 py-2 font-medium text-gray-900">
                            #<?php echo $user['id']; ?><br>
                            <span class="text-xs text-gray-500"><?php echo date('Y-m-d', strtotime($user['created_at'])); ?></span>
                        </td>
                        <td class="px-3 py-2">
                            <?php echo htmlspecialchars($user['contact_name']); ?><br>
                            <span class="text-xs text-gray-600"><?php echo htmlspecialchars($user['company_name'] ?? 'N/A'); ?></span>
                        </td>
                        <td class="px-3 py-2">
                            <?php echo htmlspecialchars($user['email']); ?><br>
                            <span class="text-xs text-gray-500"><?php echo htmlspecialchars($user['phone'] ?? 'N/A'); ?></span>
                        </td>
                        <td class="px-3 py-2 text-center">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                <?php echo $is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                <?php echo $is_active ? 'Active' : 'Inactive'; ?>
                            </span>
                        </td>
                        <td class="px-3 py-2 text-center whitespace-nowrap">
                            <form method="POST" action="users.php" class="inline-block mr-2">
                                <input type="hidden" name="action" value="toggle_status">
                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                <input type="hidden" name="new_status" value="<?php echo $is_active ? 0 : 1; ?>">
                                <button type="submit" class="text-xs font-medium py-1 px-3 rounded-lg shadow-sm transition duration-150 
                                    <?php echo $is_active ? 'bg-red-500 hover:bg-red-600 text-white' : 'bg-green-500 hover:bg-green-600 text-white'; ?>">
                                    <?php echo $is_active ? 'Deactivate' : 'Activate'; ?>
                                </button>
                            </form>
                            
                            <button type="button" onclick="openResetPasswordModal(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['email']); ?>')" class="text-xs font-medium py-1 px-3 bg-indigo-500 hover:bg-indigo-600 text-white rounded-lg shadow-sm transition duration-150">
                                Reset Password
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" class="text-center py-8 text-gray-500">No B2B user accounts found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    
    <!-- Mobile Card View -->
    <div class="md:hidden space-y-4">
        <?php if (!empty($users)): ?>
            <?php foreach ($users as $user): 
                $is_active = (bool)$user['is_active'];
            ?>
                <div class="bg-gray-50 p-4 rounded-lg shadow-sm border border-gray-200 <?php echo $is_active ? '' : 'opacity-70'; ?>">
                    
                    <!-- Header: ID, Date, Status -->
                    <div class="flex justify-between items-start mb-3 border-b pb-3">
                        <div>
                            <p class="text-xs text-gray-500 font-medium">User ID / Created</p>
                            <p class="font-bold text-gray-900">#<?php echo $user['id']; ?> 
                                <span class="text-gray-500 font-normal ml-2 text-sm">(<?php echo date('Y-m-d', strtotime($user['created_at'])); ?>)</span>
                            </p>
                        </div>
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium 
                            <?php echo $is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                            <?php echo $is_active ? 'Active' : 'Inactive'; ?>
                        </span>
                    </div>

                    <!-- Details -->
                    <div class="mb-4 space-y-2">
                        <div>
                            <p class="text-xs text-gray-500 font-medium">Contact / Company</p>
                            <p class="font-medium text-gray-900"><?php echo htmlspecialchars($user['contact_name']); ?></p>
                            <p class="text-sm text-gray-600"><?php echo htmlspecialchars($user['company_name'] ?? 'N/A'); ?></p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 font-medium">Contact Details</p>
                            <p class="text-sm font-medium text-indigo-600 truncate"><?php echo htmlspecialchars($user['email']); ?></p>
                            <p class="text-sm text-gray-500"><?php echo htmlspecialchars($user['phone'] ?? 'N/A'); ?></p>
                        </div>
                    </div>
                    
                    <!-- Actions -->
                    <div class="pt-3 border-t border-gray-200 flex space-x-2">
                        <form method="POST" action="users.php" class="flex-1">
                            <input type="hidden" name="action" value="toggle_status">
                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                            <input type="hidden" name="new_status" value="<?php echo $is_active ? 0 : 1; ?>">
                            <button type="submit" class="w-full text-sm font-medium py-2 rounded-lg shadow-sm transition duration-150 
                                <?php echo $is_active ? 'bg-red-500 hover:bg-red-600 text-white' : 'bg-green-500 hover:bg-green-600 text-white'; ?>">
                                <?php echo $is_active ? 'Deactivate' : 'Activate'; ?>
                            </button>
                        </form>
                        
                        <button type="button" onclick="openResetPasswordModal(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['email']); ?>')" class="flex-1 text-sm font-medium py-2 bg-indigo-50 hover:bg-indigo-100 text-indigo-600 rounded-lg shadow-sm transition duration-150">
                            Reset Password
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="text-center py-8 text-gray-500">
                No B2B user accounts found.
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modals remain mostly the same, minor responsiveness improvement -->
<div id="createModal" class="fixed inset-0 bg-gray-600 bg-opacity-75 z-50 hidden overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-lg p-6 relative">
            
            <h3 class="text-2xl font-bold text-gray-800 mb-4 border-b pb-2">Create New B2B Account</h3>
            
            <form method="POST" action="users.php" class="space-y-4">
                <input type="hidden" name="action" value="create">
                
                <div>
                    <label for="contact_name" class="block text-sm font-medium text-gray-700">Contact Name <span class="text-red-500">*</span></label>
                    <input type="text" name="contact_name" id="contact_name" required class="w-full mt-1 px-3 py-2 border border-gray-300 rounded-lg shadow-sm">
                </div>
                
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="company_name" class="block text-sm font-medium text-gray-700">Company Name</label>
                        <input type="text" name="company_name" id="company_name" class="w-full mt-1 px-3 py-2 border border-gray-300 rounded-lg shadow-sm">
                    </div>
                    <div>
                        <label for="phone" class="block text-sm font-medium text-gray-700">Phone</label>
                        <input type="tel" name="phone" id="phone" class="w-full mt-1 px-3 py-2 border border-gray-300 rounded-lg shadow-sm">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700">Email <span class="text-red-500">*</span></label>
                        <input type="email" name="email" id="email" required class="w-full mt-1 px-3 py-2 border border-gray-300 rounded-lg shadow-sm">
                    </div>
                    <div>
                        <label for="initial_password" class="block text-sm font-medium text-gray-700">Password <span class="text-red-500">*</span></label>
                        <input type="text" name="initial_password" id="initial_password" required class="w-full mt-1 px-3 py-2 border border-gray-300 rounded-lg shadow-sm" placeholder="e.g., Use strong temp password">
                    </div>
                </div>

                <div class="pt-4 flex justify-end space-x-3">
                    <button type="button" onclick="closeModal('createModal')" class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-200 rounded-lg hover:bg-gray-300 transition duration-150">
                        Cancel
                    </button>
                    <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700 transition duration-150">
                        Create Account
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<div id="resetPasswordModal" class="fixed inset-0 bg-gray-600 bg-opacity-75 z-50 hidden overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-lg p-6 relative">
            
            <h3 class="text-2xl font-bold text-gray-800 mb-4 border-b pb-2">Reset/Change User Password</h3>
            <p class="text-sm text-gray-600 mb-4">Setting new password for: <span id="reset_user_email" class="font-semibold text-red-700"></span></p>
            
            <form method="POST" action="users.php" class="space-y-4">
                <input type="hidden" name="action" value="reset_password">
                <input type="hidden" name="user_id" id="reset_user_id">
                
                <div>
                    <label for="new_password_field" class="block text-sm font-medium text-gray-700">New Password<span class="text-red-500">*</span></label>
                    <input type="text" name="new_password" id="new_password_field" required class="w-full mt-1 px-3 py-2 border border-gray-300 rounded-lg shadow-sm">
                </div>
                
                <div class="pt-4 flex justify-end space-x-3">
                    <button type="button" onclick="closeModal('resetPasswordModal')" class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-200 rounded-lg hover:bg-gray-300 transition duration-150">
                        Cancel
                    </button>
                    <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 transition duration-150">
                        Set New Password
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php 
close_db_connection($conn);
render_admin_footer(); 
?>