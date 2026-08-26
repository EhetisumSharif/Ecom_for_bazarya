<?php
require_once '../includes/functions.php';
require_once '../includes/db.php';

// --- Admin Authentication Check (Requirement H) ---
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    redirect('login.php');
}

$message = '';
$error = '';
$status_options = ['Pending', 'Confirmed', 'Delivered', 'Cancelled'];
// Default filter to Pending orders, or 'all' if explicitly set in the URL
$filter_status = $_GET['status'] ?? 'Pending'; 
// Ensure 'all' is a valid option for the filter
if (!in_array($filter_status, $status_options) && $filter_status !== 'all') {
    $filter_status = 'Pending';
}

// --- Admin Navigation Header/Footer with Responsive Toggle ---

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
    global $filter_status;
    ?>
        <footer class="mt-8 pt-4 border-t border-gray-300 text-center text-sm text-gray-500">
            <p>&copy; <?php echo date("Y"); ?> Bazarya Trading AFZ. All rights reserved.</p>
            <p>Powerd by: Aelyth IT solution</p>
        </footer>
        </main>
    </div>

    <script>
        // --- Sidebar Toggle Logic ---
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
        // Removed simulateExport function
    </script>
    
    </body>
    </html>
    <?php
}
// --- End Admin Header/Footer ---

// --- 1. CRUD Operations: Update Status (Requirement F.2) ---

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    $order_id = (int)($_POST['order_id'] ?? 0);
    $new_status = sanitize_input($_POST['new_status'] ?? '');
    $redirect_status = sanitize_input($_POST['redirect_status'] ?? $filter_status); // Preserve current filter

    if ($order_id > 0 && in_array($new_status, $status_options)) {
        // Use prepared statements to prevent SQL injection
        $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $new_status, $order_id);

        if ($stmt->execute()) {
            $message = "Order #{$order_id} status updated to '{$new_status}'.";
            // log_activity(0, "Updated Order #{$order_id} status to {$new_status}"); // Assumed function
        } else {
            $error = "Error updating order status: " . $conn->error;
        }
        $stmt->close();
        
        // Redirect to preserve filter and show message/error
        redirect("orders.php?status=" . urlencode($redirect_status) . "&msg=" . urlencode($message) . (empty($error) ? '' : "&error=" . urlencode($error)));
        exit; // Important to stop script execution after redirect
    } else {
        $error = "Invalid status or order ID.";
        // Redirect with error
        redirect("orders.php?status=" . urlencode($redirect_status) . "&error=" . urlencode($error));
        exit;
    }
}

// Check for successful redirection message
if (isset($_GET['msg'])) {
    $message = sanitize_input($_GET['msg']);
}
if (isset($_GET['error'])) {
    $error = sanitize_input($_GET['error']);
}


// --- 2. Data Fetching: Orders (Requirement F.2) ---

$orders = [];

// Prepare filter condition
$where_clause = '';
$params = [];
$types = '';

if ($filter_status !== 'all') {
    $where_clause = " WHERE o.status = ?";
    $params[] = $filter_status;
    $types .= 's';
}

$sql = "
    SELECT 
        o.id, o.order_date, o.status, o.total_amount, o.contact_number, o.company_name,
        u.email, u.contact_name
    FROM orders o
    JOIN b2b_users u ON o.b2b_user_id = u.id
    {$where_clause}
    ORDER BY o.order_date DESC
";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    // Note: The splat operator (...) is used to pass array elements as separate arguments
    $stmt->bind_param($types, ...$params); 
}
$stmt->execute();
$result = $stmt->get_result();

if ($result) {
    while($row = $result->fetch_assoc()) {
        $orders[] = $row;
    }
}
$stmt->close();

// --- 3. Helper Functions (Assumes format_aed() is in functions.php) ---
function get_status_badge_class($status) {
    switch ($status) {
        case 'Confirmed': return 'bg-blue-100 text-blue-800';
        case 'Delivered': return 'bg-green-100 text-green-800';
        case 'Cancelled': return 'bg-gray-100 text-gray-800';
        case 'Pending': 
        default: return 'bg-yellow-100 text-yellow-800';
    }
}

render_admin_header('Order Management');
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

<div class="bg-white p-4 rounded-xl shadow-lg mb-6 border border-gray-100 flex flex-col md:flex-row justify-between items-center gap-4">
    
    <form method="GET" action="orders.php" class="flex items-center space-x-3 w-full md:w-auto">
        <label for="status_filter" class="text-sm font-medium text-gray-700 whitespace-nowrap">Filter Status:</label>
        <select name="status" id="status_filter" onchange="this.form.submit()" 
                class="px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:ring-red-500 focus:border-red-500 w-full md:w-auto">
            <option value="all" <?php echo $filter_status === 'all' ? 'selected' : ''; ?>>All Orders</option>
            <?php foreach ($status_options as $status): ?>
                <option value="<?php echo htmlspecialchars($status); ?>" 
                    <?php echo $filter_status === $status ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($status); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </form>
    
    <form method="GET" action="export_orders.php" class="w-full md:w-auto">
        <input type="hidden" name="status" value="<?php echo htmlspecialchars($filter_status); ?>">
        <button type="submit" class="bg-gray-700 hover:bg-gray-800 text-white font-semibold py-2 px-4 rounded-lg shadow-md transition duration-150 text-sm w-full md:w-auto">
            <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
            Export Current Filter (Excel)
        </button>
    </form>
</div>

<div class="bg-white p-6 rounded-xl shadow-lg border border-gray-100">
    <h2 class="text-2xl font-bold text-gray-800 mb-4 border-b pb-2">Orders Found: (<?php echo count($orders); ?>)</h2>

    <div class="overflow-x-auto hidden md:block">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase">Order ID</th>
                    <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                    <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase">Buyer</th>
                    <th class="px-3 py-3 text-right text-xs font-medium text-gray-500 uppercase">Total (AED)</th>
                    <th class="px-3 py-3 text-center text-xs font-medium text-gray-500 uppercase">Status</th>
                    <th class="px-3 py-3 text-center text-xs font-medium text-gray-500 uppercase w-32">Update Status</th>
                    <th class="px-3 py-3 text-center text-xs font-medium text-gray-500 uppercase">Details</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php if (!empty($orders)): ?>
                    <?php foreach ($orders as $order): ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-3 py-3 font-bold text-red-600">#<?php echo $order['id']; ?></td>
                        <td class="px-3 py-3 text-gray-700"><?php echo date('d M Y', strtotime($order['order_date'])); ?></td>
                        <td class="px-3 py-3">
                            <span class="font-medium text-gray-900"><?php echo htmlspecialchars($order['contact_name']); ?></span><br>
                            <span class="text-xs text-gray-500"><?php echo htmlspecialchars($order['company_name'] ?? $order['email']); ?></span>
                        </td>
                        <td class="px-3 py-3 text-right font-extrabold text-green-600"><?php echo format_aed($order['total_amount']); ?></td>
                        <td class="px-3 py-3 text-center">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                <?php echo get_status_badge_class($order['status']); ?>">
                                <?php echo htmlspecialchars($order['status']); ?>
                            </span>
                        </td>
                        <td class="px-3 py-3 text-center">
                            <form method="POST" action="orders.php" class="inline-flex">
                                <input type="hidden" name="action" value="update_status">
                                <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                <input type="hidden" name="redirect_status" value="<?php echo htmlspecialchars($filter_status); ?>">

                                <select name="new_status" class="px-2 py-1 text-xs border border-gray-300 rounded-lg shadow-sm focus:ring-red-500 focus:border-red-500 mr-2">
                                    <?php foreach ($status_options as $status): ?>
                                        <option value="<?php echo htmlspecialchars($status); ?>"
                                            <?php echo $order['status'] === $status ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($status); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <button type="submit" class="bg-red-500 hover:bg-red-600 text-white px-3 py-1 rounded-lg text-xs font-semibold transition duration-150">
                                    Update
                                </button>
                            </form>
                        </td>
                        <td class="px-3 py-3 text-center">
                             <a href="../invoice_generator.php?order_id=<?php echo $order['id']; ?>" target="_blank" class="text-indigo-600 hover:text-indigo-900 text-sm font-medium">View Invoice</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" class="text-center py-8 text-gray-500">
                            No <?php echo $filter_status === 'all' ? 'orders' : strtolower($filter_status) . ' orders'; ?> found.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    
    <div class="md:hidden space-y-4">
        <?php if (!empty($orders)): ?>
            <?php foreach ($orders as $order): ?>
                <div class="bg-gray-50 p-4 rounded-lg shadow-sm border border-gray-200">
                    <div class="flex justify-between items-start mb-2 border-b pb-2">
                        <div>
                            <p class="text-xs text-gray-500 font-medium">Order ID / Date</p>
                            <p class="font-bold text-red-600">#<?php echo $order['id']; ?> <span class="text-gray-500 font-normal ml-2"> (<?php echo date('d M Y', strtotime($order['order_date'])); ?>)</span></p>
                        </div>
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium 
                            <?php echo get_status_badge_class($order['status']); ?>">
                            <?php echo htmlspecialchars($order['status']); ?>
                        </span>
                    </div>
                    
                    <div class="mb-3">
                        <p class="text-xs text-gray-500 font-medium">Buyer / Company</p>
                        <p class="font-medium text-gray-900"><?php echo htmlspecialchars($order['contact_name']); ?></p>
                        <p class="text-sm text-gray-500"><?php echo htmlspecialchars($order['company_name'] ?? $order['email']); ?></p>
                    </div>

                    <div class="flex justify-between items-center mb-4">
                         <p class="text-xs text-gray-500 font-medium">Total Amount:</p>
                         <p class="text-lg font-extrabold text-green-600"><?php echo format_aed($order['total_amount']); ?></p>
                    </div>
                    
                    <div class="pt-3 border-t border-gray-200 space-y-3">
                        <a href="../invoice_generator.php?order_id=<?php echo $order['id']; ?>" target="_blank" class="block text-center py-2 bg-indigo-50 hover:bg-indigo-100 text-indigo-600 text-sm font-medium rounded-lg">
                            View Invoice Details
                        </a>
                        
                        <form method="POST" action="orders.php" class="flex justify-between items-center">
                            <input type="hidden" name="action" value="update_status">
                            <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                            <input type="hidden" name="redirect_status" value="<?php echo htmlspecialchars($filter_status); ?>">

                            <select name="new_status" class="px-2 py-2 text-sm border border-gray-300 rounded-lg shadow-sm focus:ring-red-500 focus:border-red-500 flex-grow mr-2">
                                <?php foreach ($status_options as $status): ?>
                                    <option value="<?php echo htmlspecialchars($status); ?>"
                                        <?php echo $order['status'] === $status ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($status); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <button type="submit" class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-lg text-sm font-semibold transition duration-150">
                                Update
                            </button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="text-center py-8 text-gray-500">
                No <?php echo $filter_status === 'all' ? 'orders' : strtolower($filter_status) . ' orders'; ?> found.
            </div>
        <?php endif; ?>
    </div>
</div>

<?php 
close_db_connection($conn);
render_admin_footer(); 
?>