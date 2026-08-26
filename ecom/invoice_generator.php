<?php
// invoice_generator.php
require_once 'includes/functions.php';
require_once 'includes/db.php';

// Define VAT Rate for UAE (currently 0% for basic goods, assuming this context)
// This can be adjusted if the tax rules change or specific products are taxed.
const VAT_RATE = 0.00; // 0%

// --- MODIFICATION START: Combined Admin/B2B Authentication ---
$is_admin_logged_in = isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true;

// Require EITHER B2B User login OR Admin login to view the invoice
if (!is_b2b_logged_in() && !$is_admin_logged_in) {
    redirect('b2b_login.php');
}

$user_id = null;
if (is_b2b_logged_in()) {
    $user_id = $_SESSION['user_id'];
} 
// If admin is logged in, $user_id remains null, allowing them to view any order (handled in SQL).
// --- MODIFICATION END: Combined Admin/B2B Authentication ---


$order_id = sanitize_input($_GET['order_id'] ?? 0);
$order_data = [];
$order_items = [];
$subtotal = 0.00; // Use a clearer name for the calculated total

// --- 1. Fetch Order and Item Data (UPDATED QUERY to include u.email) ---
if ($order_id) {
    
    // Fetch main order details, including contact_name AND email from b2b_users table
    $sql_select = "
        SELECT o.*, u.contact_name, u.email 
        FROM orders o
        JOIN b2b_users u ON o.b2b_user_id = u.id
        WHERE o.id = ?
    ";
    
    // --- Dynamic SQL and Binding Setup ---
    if (!$is_admin_logged_in) {
        // B2B User viewing: Restrict to their own user ID
        $sql_select .= " AND o.b2b_user_id = ?";
        
        $stmt = $conn->prepare($sql_select);
        // Bind order ID (int) and user ID (int)
        $stmt->bind_param("ii", $order_id, $user_id);
    } else {
        // Admin viewing: No user ID restriction needed
        $stmt = $conn->prepare($sql_select);
        // Bind only order ID (int)
        $stmt->bind_param("i", $order_id);
    }

    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $order_data = $result->fetch_assoc();
        
        // Fetch order items and product names
        $stmt_items = $conn->prepare("
            SELECT oi.*, p.name AS product_name 
            FROM order_items oi
            JOIN products p ON oi.product_id = p.id
            WHERE oi.order_id = ?
        ");
        $stmt_items->bind_param("i", $order_id);
        $stmt_items->execute();
        $result_items = $stmt_items->get_result();
        
        while ($item = $result_items->fetch_assoc()) {
            $order_items[] = $item;
            // Recalculate total for security/verification (This is the Subtotal)
            $subtotal += $item['quantity'] * $item['unit_price'];
        }
    }
    // Check if the first statement was executed before closing
    if (isset($stmt)) {
        $stmt->close();
    }
}

close_db_connection($conn);

// Calculate final figures
$vat_amount = $subtotal * VAT_RATE;
$shipping_handling = 0.00; // Assuming 0 for this context
$grand_total = $subtotal + $vat_amount + $shipping_handling;

// Check if order data was found
if (empty($order_data)) {
    die("Error: Invoice not found or unauthorized access.");
}

// OPTIONAL: Security check - compare calculated total with database total
// In a real-world scenario, you might display a warning if the two totals don't match.
if (abs($grand_total - $order_data['total_amount']) > 0.01) {
    // Log a warning: Calculated total does not match database total!
}


// --- 2. Invoice HTML Generation (Print-friendly format) ---
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice #<?php echo htmlspecialchars($order_id); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @media print {
            /* Hide unnecessary elements for printing */
            body { 
                margin: 0; 
                padding: 0; 
                font-size: 11pt; /* Smaller font for paper */
            }
            .no-print {
                display: none !important;
            }
            .invoice-container {
                box-shadow: none !important;
                border: none !important;
                width: 100%;
                padding: 0 !important; /* Remove padding on print */
            }
            /* Ensure tables and borders are clean */
            table, th, td {
                border-color: #000 !important;
            }
        }
    </style>
</head>
<body class="bg-gray-100 p-4 sm:p-8">

<div class="max-w-4xl mx-auto bg-white p-4 sm:p-8 invoice-container shadow-2xl border border-gray-200">
    
    <div class="flex flex-col sm:flex-row justify-between items-start border-b pb-4 sm:pb-6 mb-6">
        <div>
            <h1 class="text-3xl sm:text-4xl font-extrabold text-red-700 mb-2">INVOICE</h1>
            <p class="text-sm text-gray-500">Invoice Date: <?php echo date('Y-m-d', strtotime($order_data['order_date'])); ?></p>
        </div>
        
        <div class="text-left sm:text-right mt-4 sm:mt-0 flex flex-col items-start sm:items-end">
            <div class="mb-2">
                <img src="logo.jpeg" alt="Bazarya Trading AFZ Logo" class="h-12 w-auto object-contain">
            </div>
            <h2 class="text-xl sm:text-2xl font-semibold text-gray-800">Bazarya Trading AFZ</h2>
            <p class="text-sm text-gray-600">Sharjah, UAE</p>
            <p class="text-sm text-gray-600">Phone/WhatsApp: +971551687755</p>
            <p class="text-sm text-gray-600">Email: bazaryatradingafz@gmail.com</p>
        </div>
        </div>

    <div class="flex flex-col sm:flex-row justify-between mb-8 text-sm space-y-6 sm:space-y-0">
        <div class="w-full sm:w-1/2">
            <h3 class="font-bold text-gray-700 mb-2 border-b">Billed To</h3>
            <p class="font-semibold"><?php echo htmlspecialchars($order_data['company_name']); ?></p>
            <p><?php echo htmlspecialchars($order_data['contact_name'] ?? 'B2B Customer'); ?></p> 
            <p class="whitespace-pre-wrap"><?php echo nl2br(htmlspecialchars($order_data['delivery_address'])); ?></p>
            <p>Tel: <?php echo htmlspecialchars($order_data['contact_number']); ?></p>
            <p>Email: <?php echo htmlspecialchars($order_data['email'] ?? 'N/A'); ?></p>
        </div>
        <div class="w-full sm:w-auto text-left sm:text-right">
            <h3 class="font-bold text-gray-700 mb-2 border-b">Invoice Summary</h3>
            <p><strong>Invoice # ID:</strong> <span class="text-red-600 font-bold"><?php echo htmlspecialchars($order_data['id']); ?></span></p>
            <p><strong>Order Status:</strong> <?php echo htmlspecialchars($order_data['status']); ?></p>
            <p><strong>Payment Term:</strong> <?php echo htmlspecialchars($order_data['payment_method']); ?></p>
        </div>
    </div>

    <div class="overflow-x-auto">
        <table class="min-w-full border-collapse table-auto mb-8">
            <thead>
                <tr class="bg-gray-100 border-b border-t">
                    <th class="p-3 text-left text-xs sm:text-sm font-semibold text-gray-600">ITEM DESCRIPTION</th>
                    <th class="p-3 text-center text-xs sm:text-sm font-semibold text-gray-600 w-16 sm:w-20">QTY</th>
                    <th class="p-3 text-right text-xs sm:text-sm font-semibold text-gray-600 w-24 sm:w-32">UNIT PRICE</th>
                    <th class="p-3 text-right text-xs sm:text-sm font-semibold text-gray-600 w-24 sm:w-32">LINE TOTAL</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($order_items as $item): ?>
                <tr class="border-b">
                    <td class="p-3 text-left text-gray-800"><?php echo htmlspecialchars($item['product_name']); ?></td>
                    <td class="p-3 text-center text-gray-800"><?php echo htmlspecialchars($item['quantity']); ?></td>
                    <td class="p-3 text-right text-gray-800 whitespace-nowrap"><?php echo format_aed($item['unit_price']); ?></td>
                    <td class="p-3 text-right font-semibold text-gray-800 whitespace-nowrap"><?php echo format_aed($item['quantity'] * $item['unit_price']); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="flex justify-end">
        <div class="w-full sm:w-1/2"> 
            <div class="space-y-2 border-t pt-4">
                <div class="flex justify-between">
                    <span class="text-gray-700">Subtotal:</span>
                    <span class="font-semibold"><?php echo format_aed($subtotal); ?></span>
                </div>
                <div class="flex justify-between text-sm text-gray-500">
                    <span>Shipping/Handling:</span>
                    <span><?php echo format_aed($shipping_handling); ?></span>
                </div>
                <div class="flex justify-between text-sm text-gray-500">
                    <span>VAT (<?php echo (VAT_RATE * 100); ?>%):</span>
                    <span><?php echo format_aed($vat_amount); ?></span>
                </div>
            </div>
            
            <div class="flex justify-between border-t border-b py-3 mt-3">
                <span class="text-xl font-bold text-gray-900">GRAND TOTAL:</span>
                <span class="text-xl font-bold text-red-700"><?php echo format_aed($grand_total); ?></span>
            </div>
        </div>
    </div>
    
    <div class="mt-6 sm:mt-8 pt-4 border-t text-xs sm:text-sm text-gray-600">
        <p class="font-bold mb-1">Terms & Conditions:</p>
        <p>This is a formal Invoice. Payment is due upon delivery via Cash on Delivery (COD). Please contact us immediately if you have any issues with your order.</p>
    </div>

</div>

<div class="no-print max-w-4xl mx-auto mt-4 flex justify-center sm:justify-end">
    <button onclick="window.print()" class="px-6 py-3 bg-red-600 text-white font-semibold rounded-lg shadow-md hover:bg-red-700 transition duration-150">
        <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2v-2h-2m0 4h2v2h-2m-3-11v11h-2v-11h2zM7 7h10v10h-10z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 17l4-4m-4 4l4 4"></path></svg>
        Print / Save as PDF
    </button>
</div>

</body>
</html>