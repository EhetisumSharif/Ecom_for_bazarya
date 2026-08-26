<?php
require_once '../includes/functions.php';
require_once '../includes/db.php'; 

// --- Admin Authentication Check (Requirement H) ---
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    redirect('login.php');
}

$page_title = 'Admin Dashboard';
$current_page = basename($_SERVER['PHP_SELF']);

// --- DATABASE FETCH FUNCTIONS (MySQLi Compatible) ---
/**
 * Executes a query that returns a single scalar value (e.g., COUNT(*)).
 * NOTE: This implementation uses MySQLi syntax to match the database connection.
 */
function fetch_single_value($conn, $sql) {
    // Check if the connection object is valid
    if (!$conn || $conn->connect_error) {
        error_log("DB Connection Error: Cannot connect to database.");
        return 0;
    }
    
    // Prepare the statement
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        error_log("MySQLi Prepare Error: " . $conn->error);
        return 0;
    }
    
    try {
        // Execute the statement
        $stmt->execute();
        
        // Bind the result to a variable
        $result = 0;
        $stmt->bind_result($result);
        
        // Fetch the result
        $stmt->fetch();
        
        // Close the statement
        $stmt->close();
        
        // Return the fetched value
        return $result;
        
    } catch (Throwable $e) {
        // Log error, but return 0 to prevent dashboard breakage
        error_log("Database Execution Error: " . $e->getMessage());
        if ($stmt) $stmt->close();
        return 0;
    }
}

// --- ACTUAL Dashboard Data Fetching ---
// We use the assumed $conn object from '../includes/db.php'

// 1. Total Products
$total_products = fetch_single_value($conn, "SELECT COUNT(id) FROM products WHERE is_visible = 1;");

// 2. Total B2B User Accounts
$total_users = fetch_single_value($conn, "SELECT COUNT(id) FROM b2b_users;");

// 3. Pending Orders
$pending_orders = fetch_single_value($conn, "SELECT COUNT(id) FROM orders WHERE status = 'Pending';");


$data = [
    'total_products' => $total_products, 
    'total_users' => $total_users,       
    'pending_orders' => $pending_orders, 
    'total_revenue_aed' => 'N/A' // Set to N/A or remove completely if the card is deleted
];
// --- End Dashboard Data Fetching ---

// --- Admin Navigation Header/Footer with Responsive Toggle (Updated for consistency) ---
function render_admin_header($page_title, $current_page) {
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

// Data for quick action cards (unchanged)
$actions = [
    ['title' => 'Manage Products', 'icon' => '📦', 'link' => 'products_crud.php', 'description' => 'Add, edit, or delete products and update stock levels.'],
    ['title' => 'Process Orders', 'icon' => '📋', 'link' => 'orders.php?status=Pending', 'description' => 'View and update the status of pending customer orders.'],
    ['title' => 'Manage B2B Users', 'icon' => '👥', 'link' => 'users.php', 'description' => 'Activate, deactivate, or reset passwords for B2B accounts.'],
    ['title' => 'System Settings', 'icon' => '⚙️', 'link' => 'settings.php', 'description' => 'Configure email templates and perform database backups.'],
];


render_admin_header($page_title, $current_page);
?>

<div class="max-w-7xl mx-auto">
    <!-- Key Metrics Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 mb-10">
    
        <div class="bg-white p-6 rounded-xl shadow-lg border-l-4 border-red-600 transition duration-150 hover:shadow-xl">
            <div class="text-sm font-medium text-gray-500 flex items-center">
                <svg class="w-5 h-5 mr-2 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c1.657 0 3 .895 3 2s-1.343 2-3 2-3-.895-3-2 1.343-2 3-2zM4.332 10.332a8 8 0 0115.336 0"></path></svg>
                Total Products (Visible)
            </div>
            <div class="text-4xl font-extrabold text-gray-900 mt-2"><?php echo $data['total_products']; ?></div>
            <a href="products_crud.php" class="text-sm text-red-600 hover:text-red-800 mt-2 block font-medium">View Inventory →</a>
        </div>
        
        <div class="bg-white p-6 rounded-xl shadow-lg border-l-4 border-blue-600 transition duration-150 hover:shadow-xl">
            <div class="text-sm font-medium text-gray-500 flex items-center">
                <svg class="w-5 h-5 mr-2 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20v-2a3 3 0 015.356-1.857M7 20h4m-4 0v-2c0-.656.126-1.283.356-1.857M12 20v-2c0-.656-.126-1.283-.356-1.857M12 20h4m-4 0v-2c0-.656.126-1.283.356-1.857m-4-10a4 4 0 014 4 4 4 0 01-4 4 4 4 0 01-4-4 4 4 0 014-4z"></path></svg>
                B2B User Accounts
            </div>
            <div class="text-4xl font-extrabold text-gray-900 mt-2"><?php echo $data['total_users']; ?></div>
            <a href="users.php" class="text-sm text-blue-600 hover:text-blue-800 mt-2 block font-medium">Manage Users →</a>
        </div>

        <div class="bg-white p-6 rounded-xl shadow-lg border-l-4 border-yellow-600 transition duration-150 hover:shadow-xl">
            <div class="text-sm font-medium text-gray-500 flex items-center">
                <svg class="w-5 h-5 mr-2 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M10 15h.01"></path></svg>
                Pending Orders
            </div>
            <div class="text-4xl font-extrabold text-yellow-800 mt-2"><?php echo $data['pending_orders']; ?></div>
            <a href="orders.php?status=Pending" class="text-sm text-yellow-600 hover:text-yellow-800 mt-2 block font-medium">Review Orders →</a>
        </div>
        
    </div>

    <!-- Quick Access Cards -->
    <div class="bg-white p-4 md:p-6 rounded-xl shadow-lg border border-gray-100">
        <h2 class="text-2xl font-bold text-gray-800 mb-6 border-b pb-2">Quick Access</h2>
        
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            <?php foreach ($actions as $action): ?>
                <a href="<?php echo $action['link']; ?>" class="block p-4 border border-gray-200 rounded-xl hover:bg-gray-50 transition duration-150 group flex items-start space-x-4 shadow-sm">
                    <span class="text-3xl flex-shrink-0"><?php echo $action['icon']; ?></span>
                    <div>
                        <h3 class="text-lg font-semibold text-gray-800 group-hover:text-red-600 leading-tight"><?php echo $action['title']; ?></h3>
                        <p class="text-sm text-gray-500 mt-1"><?php echo $action['description']; ?></p>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<?php 
// close_db_connection($conn); // Assuming this function is correctly defined and takes $conn
render_admin_footer(); 
?>