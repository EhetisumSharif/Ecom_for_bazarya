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
        // --- Sidebar Toggle Logic (Copied for consistency) ---
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
    </script>
    
    </body>
    </html>
    <?php
}
// --- End Admin Header/Footer ---


// --- 1. Settings & Template Management (Simulated) ---

// Define placeholder template content (would normally be stored in DB or config files)
$buyer_template_default = "Dear {CONTACT_NAME},\n\nThank you for your order! Your Order ID is {ORDER_ID} for a total of {TOTAL_AMOUNT}. Payment is Cash on Delivery.\n\nNote: Only product price charged, no extra fees.\n\nRegards,\nAdmin Team";
$admin_template_default = "New Order: {ORDER_ID} placed by {COMPANY_NAME}. Total: {TOTAL_AMOUNT}. Delivery Address: {ADDRESS}";

// Load current settings (simulated read from a file or DB)
$settings = [
    'buyer_template' => $buyer_template_default,
    'contact_form_email' => 'bazaryatradingafz@gmail.com' 
]; 

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    // --- Update Email Settings (Requirement F.4) ---
    if ($action === 'update_emails') {
        $new_buyer_template = sanitize_input($_POST['buyer_template'] ?? '');
        $new_contact_email = sanitize_input($_POST['contact_form_email'] ?? '');
        
        if (empty($new_buyer_template) || empty($new_contact_email)) {
             $error = "Both email template and contact email must be filled.";
        } else {
             // SIMULATED: Save settings
             $settings['buyer_template'] = $new_buyer_template;
             $settings['contact_form_email'] = $new_contact_email;
             $message = "Email settings updated successfully. (Note: Admin template and contact form template are placeholders.)";
             
             // In a real application, you would save $settings to the database or a configuration file.
        }
    }
    
    // --- Perform Database Backup (Requirement H) ---
    if ($action === 'db_backup') {
        // SIMULATED: Database Backup process
        $backup_filename = "b2b_db_backup_" . date("Ymd_His") . ".sql";
        $message = "Database backup process initiated. File simulated as '{$backup_filename}'.";
        // log_activity(0, "Database backup initiated.");
    }
}

// Ensure the templates are loaded back for display after POST (even if simulated)
$buyer_template_content = $settings['buyer_template'];
$contact_email_content = $settings['contact_form_email'];

render_admin_header('Settings & Backups');
?>

<?php if ($message): ?>
    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
        <p class="text-sm"><?php echo htmlspecialchars($message); ?></p>
    </div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
        <p class="text-sm"><?php echo htmlspecialchars($error); ?></p>
    </div>
<?php endif; ?>

<!-- Responsive Grid Layout (Switches from 1 column on mobile to 3 columns on large screens) -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

    <!-- Database Management (Column 1 on Desktop, Full Width on Mobile) -->
    <div class="lg:col-span-1 bg-white p-6 rounded-xl shadow-lg border border-gray-100 h-fit">
        <h2 class="text-2xl font-bold text-gray-800 mb-4 border-b pb-2">Database Management</h2>
        
        <div class="p-4 bg-yellow-50 rounded-lg mb-4">
            <p class="text-sm font-semibold text-yellow-800">System Feature (H):</p>
            <p class="text-sm text-gray-700">Creates a point-in-time snapshot of the database.</p>
        </div>

        <form method="POST" action="settings.php">
            <input type="hidden" name="action" value="db_backup">
            <button type="submit" onclick="return confirm('Are you sure you want to run a database backup now?')"
                    class="w-full py-3 bg-red-600 hover:bg-red-700 text-white font-semibold rounded-lg shadow-md transition duration-150">
                <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
                Perform Database Backup
            </button>
        </form>
    </div>

    <!-- Email Settings (Columns 2 & 3 on Desktop, Full Width on Mobile) -->
    <div class="lg:col-span-2 bg-white p-6 rounded-xl shadow-lg border border-gray-100">
        <h2 class="text-2xl font-bold text-gray-800 mb-4 border-b pb-2">Email Settings</h2>
        
        <form method="POST" action="settings.php" class="space-y-6">
            <input type="hidden" name="action" value="update_emails">

            <div>
                <label for="contact_form_email" class="block text-sm font-medium text-gray-700">Contact Form Recipient Email (Admin/Seller Email) <span class="text-red-500">*</span></label>
                <input type="email" name="contact_form_email" id="contact_form_email" required 
                       class="w-full mt-1 px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:ring-red-500 focus:border-red-500"
                       value="<?php echo htmlspecialchars($contact_email_content); ?>">
                <p class="mt-1 text-xs text-gray-500">This email receives messages from the public Contact Us page.</p>
            </div>
            
            <div>
                <label for="buyer_template" class="block text-sm font-medium text-gray-700">Buyer Order Confirmation Template <span class="text-red-500">*</span></label>
                <textarea name="buyer_template" id="buyer_template" rows="8" required
                          class="w-full mt-1 px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:ring-red-500 focus:border-red-500"><?php echo htmlspecialchars($buyer_template_content); ?></textarea>
                <p class="mt-1 text-xs text-indigo-600">
                    Available Variables: <code>{CONTACT_NAME}</code>, <code>{ORDER_ID}</code>, <code>{TOTAL_AMOUNT}</code>, <code>{ADDRESS}</code>.
                    The product list is appended automatically.
                </p>
            </div>
            
            <div>
                <label for="admin_template" class="block text-sm font-medium text-gray-700">Admin/Seller Confirmation Template (Fixed for now)</label>
                <textarea id="admin_template" rows="3" disabled
                          class="w-full mt-1 px-3 py-2 border border-gray-200 rounded-lg bg-gray-50 text-gray-500"><?php echo htmlspecialchars($admin_template_default); ?></textarea>
                <p class="mt-1 text-xs text-gray-500">This template is currently hardcoded for stability (Requirement E).</p>
            </div>

            <div class="pt-4 flex justify-end">
                <button type="submit" 
                        class="px-6 py-3 bg-red-600 text-white font-semibold rounded-lg shadow-md hover:bg-red-700 transition duration-150">
                    Save Email Settings
                </button>
            </div>
        </form>
    </div>

</div>

<?php 
close_db_connection($conn);
render_admin_footer(); 
?>