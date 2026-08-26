<?php
// b2b_orders.php - Order History for B2B Users
require_once 'includes/functions.php';
require_once 'includes/db.php';

// REQUIREMENT B: Secure login for approved customers
if (!is_b2b_logged_in()) {
    redirect('b2b_login.php');
}

require_once 'includes/header.php';

$user_id = $_SESSION['user_id'];
$orders = [];

// --- PHP Logic for Fetching Order History ---
$sql = "
    SELECT
        id, order_date, total_amount, status, payment_method
    FROM orders
    WHERE b2b_user_id = ?
    ORDER BY order_date DESC
";

// Prepare and execute the statement
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result) {
    while($row = $result->fetch_assoc()) {
        $orders[] = $row;
    }
}
$stmt->close();
close_db_connection($conn);
?>

<div class="pb-8">
    <h1 class="text-3xl font-extrabold text-gray-900 mb-6">📦 Your Order History</h1>

    <div class="hidden sm:block bg-white rounded-xl shadow-lg border border-gray-100 overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 text-sm text-left text-gray-500">
            <thead class="bg-gray-100 text-xs text-gray-700 uppercase tracking-wider">
                <tr>
                    <th scope="col" class="px-6 py-3">Order ID</th>
                    <th scope="col" class="px-6 py-3">Date</th>
                    <th scope="col" class="px-6 py-3 text-right">Total Amount</th>
                    <th scope="col" class="px-6 py-3">Payment Method</th>
                    <th scope="col" class="px-6 py-3">Status</th>
                    <th scope="col" class="px-6 py-3">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php if (!empty($orders)): ?>
                    <?php foreach ($orders as $order): ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 font-bold text-gray-900">#<?php echo htmlspecialchars($order['id']); ?></td>
                        <td class="px-6 py-4"><?php echo date('d M Y', strtotime($order['order_date'])); ?></td>
                        <td class="px-6 py-4 text-right font-extrabold text-indigo-700"><?php echo format_aed($order['total_amount']); ?></td>
                        <td class="px-6 py-4 text-gray-700"><?php echo htmlspecialchars($order['payment_method']); ?></td>
                        <td class="px-6 py-4">
                            <?php 
                                $status_class = '';
                                if ($order['status'] === 'Completed') {
                                    $status_class = 'bg-green-100 text-green-800';
                                } elseif ($order['status'] === 'Pending') {
                                    $status_class = 'bg-yellow-100 text-yellow-800';
                                } else {
                                    $status_class = 'bg-blue-100 text-blue-800';
                                }
                            ?>
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $status_class; ?>">
                                <?php echo htmlspecialchars($order['status']); ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <a href="invoice_generator.php?order_id=<?php echo $order['id']; ?>" target="_blank"
                               class="text-indigo-600 hover:text-indigo-900 transition duration-150">
                                View Invoice
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" class="text-center py-10 text-gray-500">
                            You have not placed any orders yet.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    
    <div class="sm:hidden space-y-4">
        <?php if (!empty($orders)): ?>
            <?php foreach ($orders as $order): ?>
                <div class="bg-white rounded-xl shadow-md border border-gray-100 p-4 text-sm">
                    <div class="flex justify-between items-center pb-2 border-b border-gray-100">
                        <div class="text-base font-bold text-gray-900">
                            Order #<?php echo htmlspecialchars($order['id']); ?>
                        </div>
                        <?php 
                            $status_class = '';
                            if ($order['status'] === 'Completed') {
                                $status_class = 'bg-green-100 text-green-800';
                            } elseif ($order['status'] === 'Pending') {
                                $status_class = 'bg-yellow-100 text-yellow-800';
                            } else {
                                $status_class = 'bg-blue-100 text-blue-800';
                            }
                        ?>
                        <span class="px-2 py-0.5 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $status_class; ?>">
                            <?php echo htmlspecialchars($order['status']); ?>
                        </span>
                    </div>

                    <div class="mt-2 space-y-1 text-gray-700">
                        <p class="flex justify-between">
                            <span class="font-medium text-gray-500">Date:</span>
                            <span><?php echo date('d M Y', strtotime($order['order_date'])); ?></span>
                        </p>
                        <p class="flex justify-between">
                            <span class="font-medium text-gray-500">Payment Method:</span>
                            <span><?php echo htmlspecialchars($order['payment_method']); ?></span>
                        </p>
                        <p class="flex justify-between items-end pt-2 border-t border-gray-100 mt-2">
                            <span class="font-extrabold text-lg text-gray-900">Total:</span>
                            <span class="font-extrabold text-xl text-indigo-700"><?php echo format_aed($order['total_amount']); ?></span>
                        </p>
                    </div>

                    <div class="mt-4 text-center">
                        <a href="invoice_generator.php?order_id=<?php echo $order['id']; ?>" target="_blank"
                           class="inline-flex justify-center w-full rounded-md border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:text-sm transition duration-150">
                            View Invoice
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="bg-white rounded-xl shadow-lg border border-gray-100 text-center py-10 text-gray-500">
                You have not placed any orders yet.
            </div>
        <?php endif; ?>
    </div>

</div>

<?php
require_once 'includes/footer.php';
?>