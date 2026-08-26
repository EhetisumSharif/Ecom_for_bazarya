<?php
require_once 'includes/functions.php';
require_once 'includes/db.php';

// REQUIREMENT B: Secure login required
if (!is_b2b_logged_in()) {
    redirect('b2b_login.php');
}

$user_id = $_SESSION['user_id'];
$cart = $_SESSION['cart'][$user_id] ?? [];
$cart_total = 0.00;
$checkout_error = '';

// Check if cart is empty before proceeding
if (empty($cart)) {
    redirect('cart.php');
}

// --- 1. Pre-validation and Total Calculation ---

// We recalculate the total to ensure consistency and prevent tampering
foreach ($cart as $product_id => $item) {
    // Total calculation: Total = Σ (Product Price × Quantity) (Requirement G)
    $cart_total += $item['qty'] * $item['price'];
}

// --- 2. Fetch User Details for Pre-filling (Requirement D.4) ---
$buyer_info = [];
// UPDATED QUERY: Fetch contact_name, company_name, phone, and email for email content
$stmt = $conn->prepare("SELECT contact_name, company_name, phone, email FROM b2b_users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $buyer_info = $result->fetch_assoc();
}
$stmt->close();

// Default form data, prioritized by POST, then existing user info
$default_company_name = $_POST['company_name'] ?? ($buyer_info['company_name'] ?? '');
// NEW: Add logic for default email address
$default_email = $_POST['email'] ?? ($buyer_info['email'] ?? '');
$default_contact_number = $_POST['contact_number'] ?? ($buyer_info['phone'] ?? '');
$default_delivery_address = $_POST['delivery_address'] ?? '';

// --- CONFIGURATION DEFAULTS (Added for email function arguments) ---
// Set the site name and admin email which are needed by sendOrderEmail()
$site_name = 'B2B Trading Platform';
$admin_email = 'orders@yourcompany.com'; // CRITICAL: Use your actual admin notification email
// -------------------------------------------------------------------


// --- 3. Handle Order Submission (POST Request) ---

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
    
    // a. Collect and Sanitize Details (Requirement D.4)
    $company_name = sanitize_input($default_company_name); // uses value from pre-fill logic above
    $contact_number = sanitize_input($default_contact_number); // uses value from pre-fill logic above
    // NEW: Collect and Sanitize Email
    $email = sanitize_input($default_email);
    
    $delivery_address = sanitize_input($_POST['delivery_address']);
    
    // b. Basic Validation (Delivery Address, Contact Number, and Email are MANDATORY)
    if (empty($delivery_address) || empty($contact_number) || empty($email)) {
        // UPDATED validation error message to include email
        $checkout_error = 'Please provide a complete delivery address, a contact number, and a contact email.';
    }

    // c. Final Stock Check (CRITICAL)
    if (empty($checkout_error)) {
        $all_in_stock = true;
        foreach ($cart as $product_id => $item) {
            $stmt = $conn->prepare("SELECT stock FROM products WHERE id = ?");
            $stmt->bind_param("i", $product_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $db_stock = $result->fetch_assoc()['stock'] ?? 0;
            $stmt->close();

            if ($item['qty'] > $db_stock) {
                $checkout_error = "Stock error: Quantity requested for {$item['name']} exceeds current available stock ({$db_stock}). Please return to cart and adjust.";
                $all_in_stock = false;
                break;
            }
        }
    }

    // d. Place Order in Database
    if (empty($checkout_error) && $all_in_stock) {
        $conn->begin_transaction();

        try {
            // i. Insert into orders table (Requirement E)
            $status = 'Pending';
            $payment_method = 'COD'; // Requirement D.3
            
            // NOTE: The 'orders' table does not have an 'email' column, so we do not insert it here.
            $stmt = $conn->prepare("INSERT INTO orders (b2b_user_id, status, delivery_address, contact_number, company_name, total_amount, payment_method) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("issssds", $user_id, $status, $delivery_address, $contact_number, $company_name, $cart_total, $payment_method);
            $stmt->execute();
            $order_id = $conn->insert_id;
            $stmt->close();

            // ii. Insert into order_items table and update stock (Requirement D)
            $stock_update_stmt = $conn->prepare("UPDATE products SET stock = stock - ? WHERE id = ? AND stock >= ?");
            $item_insert_stmt = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, unit_price) VALUES (?, ?, ?, ?)");

            foreach ($cart as $item) {
                // Insert item
                $item_insert_stmt->bind_param("iidd", $order_id, $item['id'], $item['qty'], $item['price']);
                $item_insert_stmt->execute();
                
                // Update stock (critical)
                $stock_update_stmt->bind_param("iii", $item['qty'], $item['id'], $item['qty']);
                $stock_update_stmt->execute();

                if ($stock_update_stmt->affected_rows === 0) {
                     throw new Exception("Stock depletion failure for product ID: {$item['id']}. Transaction aborted.");
                }
            }
            $item_insert_stmt->close();
            $stock_update_stmt->close();
            
            // iii. Commit transaction, clear cart, and send confirmation
            $conn->commit();
            unset($_SESSION['cart'][$user_id]); // Clear cart after successful order
            
            // NEW: Prepare array for email function using data fetched from DB and form
            $order_data_for_email = [
                'total_amount' => $cart_total,
                'delivery_address' => $delivery_address,
                'contact_number' => $contact_number,
                'company_name' => $company_name,
                'contact_name' => $buyer_info['contact_name'] ?? $buyer_info['email'], // Ensure we have a contact name
                'items' => $cart
            ];
            
            // ACTIVATE: Send order confirmation emails (Requirement E)
            // Call the function created in functions.php
            // FIX: The wrapper function send_order_confirmation_email() is assumed to require more args
            send_order_confirmation_email($order_id, $order_data_for_email, $email, $admin_email, $site_name); 
            
            redirect("order_success.php?order_id={$order_id}");

        } catch (Exception $e) {
            $conn->rollback();
            $checkout_error = "An error occurred during order processing. Please try again. Code: " . $e->getMessage();
            // Log the error: log_activity($user_id, 'Order placement failed: ' . $e->getMessage());
        }
    }
}

require_once 'includes/header.php';
?>

<div class="pb-8 px-4 sm:px-6 lg:px-8">
    <h1 class="text-3xl font-extrabold text-gray-900 mb-6 border-b pb-2">Checkout: Place Your COD Order</h1>

    <?php if ($checkout_error): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-6" role="alert">
            <p class="font-semibold">Order Error:</p>
            <p class="text-sm"><?php echo htmlspecialchars($checkout_error); ?></p>
        </div>
    <?php endif; ?>

    <form method="POST" action="checkout.php">
        <input type="hidden" name="place_order" value="1">
        
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            
            <div class="lg:col-span-2 bg-white p-6 sm:p-8 rounded-xl shadow-2xl border border-gray-100 h-fit">
                <h2 class="text-2xl font-bold text-gray-800 mb-6 border-b pb-2">1. Buyer Details & Delivery</h2>
                
                <div class="space-y-5">
                    <div>
                        <label for="company_name" class="block text-sm font-medium text-gray-700">Company Name (Optional)</label>
                        <input type="text" id="company_name" name="company_name"
                               class="w-full mt-1 px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                               value="<?php echo htmlspecialchars($default_company_name); ?>">
                    </div>

                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700">Contact Email <span class="text-red-500">*</span></label>
                        <input type="email" id="email" name="email" required
                               class="w-full mt-1 px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                               value="<?php echo htmlspecialchars($default_email); ?>">
                    </div>
                    <div>
                        <label for="contact_number" class="block text-sm font-medium text-gray-700">Contact Number <span class="text-red-500">*</span></label>
                        <input type="tel" id="contact_number" name="contact_number" required
                               class="w-full mt-1 px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                               value="<?php echo htmlspecialchars($default_contact_number); ?>">
                    </div>

                    <div>
                        <label for="delivery_address" class="block text-sm font-medium text-gray-700">Delivery Address <span class="text-red-500">*</span></label>
                        <textarea id="delivery_address" name="delivery_address" rows="4" required
                                  class="w-full mt-1 px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500"><?php echo htmlspecialchars($default_delivery_address); ?></textarea>
                    </div>
                </div>
            </div>

            <div class="lg:col-span-1 bg-white p-6 rounded-xl shadow-2xl border border-gray-100 h-fit lg:sticky lg:top-20">
                <h2 class="text-2xl font-bold text-gray-800 mb-4 border-b pb-2">2. Order Summary</h2>
                
                <div class="max-h-60 overflow-y-auto mb-4 border-b pb-4">
                    <p class="font-semibold text-gray-700 mb-2">Items (<?php echo count($cart); ?>):</p>
                    <ul class="space-y-2 text-sm">
                        <?php foreach ($cart as $item): ?>
                            <li class="flex justify-between items-center text-gray-600">
                                <span class="truncate pr-2"><?php echo htmlspecialchars($item['name']); ?></span>
                                <span class="text-gray-900 font-medium whitespace-nowrap"><?php echo $item['qty']; ?> x <?php echo format_aed($item['price']); ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>

                <div class="space-y-2 mb-4">
                    <div class="flex justify-between text-gray-600">
                        <span>Subtotal:</span>
                        <span><?php echo format_aed($cart_total); ?></span>
                    </div>
                    <div class="flex justify-between text-gray-600">
                        <span>Delivery Fee:</span>
                        <span><?php echo format_aed(0); ?></span> </div>
                    <div class="flex justify-between text-gray-600">
                        <span>VAT/Taxes:</span>
                        <span><?php echo format_aed(0); ?></span> </div>
                </div>
                
                <hr class="my-4 border-gray-300">

                <div class="flex justify-between text-2xl font-bold text-gray-900">
                    <span>TOTAL DUE:</span>
                    <span class="text-green-600"><?php echo format_aed($cart_total); ?></span>
                </div>

                <div class="mt-6 p-4 bg-indigo-50 rounded-lg border border-indigo-200">
                    <h3 class="font-bold text-lg text-indigo-700 flex items-center mb-1">
                        <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2l-2 2v-2h10a2 2 0 002-2V9z"></path></svg>
                        Cash on Delivery (COD)
                    </h3>
                    <p class="text-sm text-gray-700">Payment will be collected at the time of delivery.</p>
                </div>
                
                <button type="submit" 
                        class="mt-6 block w-full py-3 bg-indigo-600 text-white font-semibold rounded-lg shadow-lg hover:bg-indigo-700 text-center transition duration-150 transform hover:scale-[1.01]">
                    Place Order (<?php echo format_aed($cart_total); ?>)
                </button>
            </div>
        </div>
    </form>
</div>

<?php
require_once 'includes/footer.php';
close_db_connection($conn);
?>