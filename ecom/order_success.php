<?php
require_once 'includes/functions.php';
require_once 'includes/db.php';

// REQUIREMENT B: Secure login required
if (!is_b2b_logged_in()) {
    redirect('b2b_login.php');
}

require_once 'includes/header.php';

$user_id = $_SESSION['user_id'];
$order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;
$order_details = null;
$order_items = [];

if ($order_id > 0) {
    // 1. Fetch Order Header Details
    $stmt = $conn->prepare("
        SELECT 
            o.id, o.order_date, o.status, o.total_amount, o.delivery_address, o.contact_number, o.company_name,
            u.email
        FROM orders o
        JOIN b2b_users u ON o.b2b_user_id = u.id
        WHERE o.id = ? AND o.b2b_user_id = ? 
    ");
    $stmt->bind_param("ii", $order_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $order_details = $result->fetch_assoc();
    $stmt->close();
    
    // 2. Fetch Order Items if header is found
    if ($order_details) {
        $stmt_items = $conn->prepare("
            SELECT 
                oi.quantity, oi.unit_price, p.name, p.sku, p.image_url
            FROM order_items oi
            JOIN products p ON oi.product_id = p.id
            WHERE oi.order_id = ?
        ");
        $stmt_items->bind_param("i", $order_id);
        $stmt_items->execute();
        $result_items = $stmt_items->get_result();

        while ($row = $result_items->fetch_assoc()) {
            $order_items[] = $row;
        }
        $stmt_items->close();
    }
}
?>

<div class="pb-8 px-4 sm:px-6 lg:px-8">
    <?php if ($order_details): ?>
    
    <div class="bg-white p-4 sm:p-8 rounded-xl shadow-2xl border border-green-200">
        
        <div class="text-center mb-8">
            <svg class="w-16 h-16 mx-auto text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            <h1 class="text-2xl sm:text-3xl font-extrabold text-gray-900 mt-4 mb-2">Order Placed Successfully!</h1>
            <p class="text-lg text-green-600 font-semibold">Order ID: #<?php echo htmlspecialchars($order_details['id']); ?></p>
            <p class="text-gray-500 mt-2 text-sm">A confirmation email has been sent to <?php echo htmlspecialchars($order_details['email']); ?>.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6 mb-8 border-t pt-6">
            <div class="bg-gray-50 p-4 rounded-lg">
                <h3 class="font-bold text-base sm:text-lg text-gray-800 mb-2">Delivery Details</h3>
                <p class="text-sm text-gray-600">
                    <strong>Contact:</strong> <?php echo htmlspecialchars($order_details['contact_number']); ?><br>
                    <strong>Company:</strong> <?php echo htmlspecialchars($order_details['company_name'] ?? 'N/A'); ?><br>
                    <strong>Address:</strong> <?php echo nl2br(htmlspecialchars($order_details['delivery_address'])); ?>
                </p>
            </div>
            
            <div class="bg-indigo-50 p-4 rounded-lg">
                <h3 class="font-bold text-base sm:text-lg text-indigo-700 mb-2">Payment Summary</h3>
                <p class="text-sm text-gray-600">
                    <strong>Payment Method:</strong> Cash on Delivery (COD)<br>
                    <strong>Total Amount:</strong> <span class="text-xl font-extrabold text-green-600"><?php echo format_aed($order_details['total_amount']); ?></span><br>
                    <span class="text-xs text-red-500 italic">Only product price charged, no extra fees. (Requirement E)</span>
                </p>
            </div>
            
            <div class="bg-yellow-50 p-4 rounded-lg md:col-span-2 lg:col-span-1">
                <h3 class="font-bold text-base sm:text-lg text-gray-800 mb-2">Order Status</h3>
                <p class="text-sm text-gray-600">
                    <strong>Current Status:</strong> <span class="font-semibold text-yellow-700"><?php echo htmlspecialchars($order_details['status']); ?></span><br>
                    <strong>Order Date:</strong> <?php echo date('d M, Y', strtotime($order_details['order_date'])); ?><br>
                    <span class="text-xs text-gray-500">We will notify you when the status changes.</span>
                </p>
            </div>
        </div>

        <h3 class="text-xl sm:text-2xl font-bold text-gray-800 mb-4 border-b pb-2">Items Ordered</h3>
        
        <div class="overflow-x-auto border border-gray-200 rounded-lg">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-3 py-3 text-left font-medium text-gray-500 uppercase">Product</th>
                        <th class="px-3 py-3 hidden sm:table-cell text-left font-medium text-gray-500 uppercase">SKU</th>
                        <th class="px-3 py-3 text-center font-medium text-gray-500 uppercase">Qty</th>
                        <th class="px-3 py-3 hidden sm:table-cell text-right font-medium text-gray-500 uppercase">Unit Price</th>
                        <th class="px-3 py-3 text-right font-medium text-gray-500 uppercase">Subtotal</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($order_items as $item): ?>
                    <tr>
                        <td class="px-3 py-3 font-medium text-gray-900">
                            <?php echo htmlspecialchars($item['name']); ?>
                            <span class="block text-xs text-gray-500 sm:hidden">@ <?php echo format_aed($item['unit_price']); ?></span>
                        </td>
                        <td class="px-3 py-3 hidden sm:table-cell text-gray-500"><?php echo htmlspecialchars($item['sku']); ?></td>
                        <td class="px-3 py-3 text-center font-bold text-gray-700"><?php echo $item['quantity']; ?></td>
                        <td class="px-3 py-3 hidden sm:table-cell text-right text-gray-700"><?php echo format_aed($item['unit_price']); ?></td>
                        <td class="px-3 py-3 text-right font-extrabold text-gray-900 whitespace-nowrap"><?php echo format_aed($item['quantity'] * $item['unit_price']); ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <tr class="bg-gray-100 font-bold">
                        <td colspan="3" class="px-3 py-3 text-right text-base sm:text-lg text-gray-800 sm:hidden">TOTAL:</td>
                        <td colspan="4" class="px-3 py-3 text-right text-base sm:text-lg text-gray-800 hidden sm:table-cell">TOTAL AMOUNT:</td>
                        <td class="px-3 py-3 text-right text-base sm:text-lg text-green-600 whitespace-nowrap"><?php echo format_aed($order_details['total_amount']); ?></td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div class="mt-8 flex flex-col sm:flex-row justify-center space-y-3 sm:space-y-0 sm:space-x-4">
             <a href="b2b_dashboard.php" class="inline-flex items-center justify-center px-6 py-3 border border-transparent text-base font-medium rounded-lg text-white bg-indigo-600 hover:bg-indigo-700 shadow-md transition duration-150">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7"></path></svg>
                Continue Shopping
            </a>
            
            <a href="invoice_generator.php?order_id=<?php echo htmlspecialchars($order_id); ?>" target="_blank" class="inline-flex items-center justify-center px-6 py-3 border border-indigo-600 text-base font-medium rounded-lg text-indigo-600 bg-white hover:bg-indigo-50 shadow-md transition duration-150">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                View/Print Invoice
            </a>
        </div>
        
    </div>

    <?php else: ?>
        <div class="bg-white p-12 rounded-xl shadow-lg border border-red-100 text-center">
            <h2 class="text-3xl font-bold text-red-500 mb-4">Order Not Found</h2>
            <p class="text-gray-600">The order ID provided is invalid or does not belong to your account.</p>
            <a href="b2b_dashboard.php" class="mt-6 inline-block text-indigo-600 hover:text-indigo-800 font-medium">
                ← Return to Dashboard
            </a>
        </div>
    <?php endif; ?>
</div>

<?php
require_once 'includes/footer.php';
close_db_connection($conn);
?>