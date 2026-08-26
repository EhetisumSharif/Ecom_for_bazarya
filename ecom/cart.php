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
$error = '';

// --- 1. Cart Management Functions (Session-based) ---

/**
 * Gets product details from the database by ID.
 * @param int $id Product ID.
 * @param mysqli $conn Database connection object.
 * @return array|null Product data array or null.
 */
function get_product_for_cart($id, $conn) {
    // MOQ REMOVED from the SELECT statement
    $stmt = $conn->prepare("SELECT id, name, sku, price, stock, image_url FROM products WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $product = $result->fetch_assoc();
    $stmt->close();
    return $product;
}

/**
 * Updates the session cart for the current user.
 * NOTE: In a production system, cart state should persist in the database.
 * This session approach simplifies the demo.
 * MOQ validation logic is removed.
 */
function update_session_cart($user_id, $product_id, $quantity, $conn) {
    global $error;
    $product_id = (int)$product_id;
    $quantity = (int)$quantity;

    // Remove if quantity is invalid or 0 (enforcing a minimum of 1)
    if ($quantity <= 0) {
        unset($_SESSION['cart'][$user_id][$product_id]);
        return true;
    }

    $product = get_product_for_cart($product_id, $conn);

    if (!$product) {
        $error = "Product not found.";
        return false;
    }

    // Check minimum quantity (now 1)
    if ($quantity < 1) {
        $quantity = 1; // Reset to 1
        $error = "Quantity must be at least 1 for {$product['name']}. Quantity reset.";
    }

    // Check Stock
    if ($quantity > $product['stock']) {
        $quantity = $product['stock']; // Reset to max stock
        $error = "Stock limit reached. Only {$product['stock']} units of {$product['name']} available.";
    }

    $_SESSION['cart'][$user_id][$product_id] = [
        'id' => $product['id'],
        'name' => $product['name'],
        'sku' => $product['sku'],
        'price' => $product['price'],
        'qty' => $quantity,
        'image_url' => $product['image_url'],
        'stock' => $product['stock'],
        // 'moq' key removed
    ];
    return true;
}

// --- 2. Handle Cart Actions (POST Requests) - MUST RUN BEFORE header.php ---

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_cart'])) {
        // Handle bulk updates from the form
        foreach ($_POST['qty'] as $product_id => $quantity) {
            // Note: We cast quantity to int here before passing to the function
            update_session_cart($user_id, $product_id, (int)$quantity, $conn); 
        }
        // Redirect to clear POST data and show updated cart/error message
        redirect('cart.php'); // Fixed: This now executes before any output
    }

    // Handle single item removal (e.g., via AJAX or direct link)
    if (isset($_POST['remove_item']) && isset($_POST['product_id'])) {
        $product_id = (int)$_POST['product_id'];
        unset($_SESSION['cart'][$user_id][$product_id]);
        redirect('cart.php'); // Fixed: This now executes before any output
    }
}

// --- 3. Recalculate Cart Display (BEFORE header) ---

// Refresh cart data from session
$cart = $_SESSION['cart'][$user_id] ?? []; 

if (!empty($cart)) {
    // Re-verify prices and quantities against current database state for security
    // and calculate total (Requirement G)
    foreach ($cart as $product_id => $item) {
        $db_product = get_product_for_cart($product_id, $conn);

        if ($db_product) {
            // Update item price and stock to current database values
            $cart[$product_id]['price'] = $db_product['price'];
            $cart[$product_id]['stock'] = $db_product['stock'];
            // MOQ update removed
            
            // Check stock validity again
            if ($item['qty'] > $db_product['stock']) {
                $cart[$product_id]['qty'] = $db_product['stock'];
                $error = "Stock for {$db_product['name']} was reduced. Cart updated to max stock of {$db_product['stock']}.";
            }
            
            // Calculate subtotal for the item and running total
            $subtotal = $cart[$product_id]['qty'] * $cart[$product_id]['price'];
            $cart_total += $subtotal;
        } else {
            // Product no longer exists, remove from cart
            unset($cart[$product_id]);
            $error = "One or more products in your cart are no longer available and were removed.";
        }
    }
    
    // Save verified/updated cart back to session
    $_SESSION['cart'][$user_id] = $cart;
}

// --- INCLUDE HEADER HERE, AFTER ALL REDIRECTS AND LOGIC ---
require_once 'includes/header.php';

?>

<div class="pb-8">
    <h1 class="text-3xl font-extrabold text-gray-900 mb-6 border-b pb-2">Your Shopping Cart</h1>

    <?php if ($error): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-6" role="alert">
            <p class="text-sm font-semibold">Cart Update Warning:</p>
            <p class="text-sm"><?php echo htmlspecialchars($error); ?></p>
        </div>
    <?php endif; ?>

    <?php if (empty($cart)): ?>
        <div class="bg-white p-10 rounded-xl shadow-lg text-center border border-gray-100">
            <svg class="w-16 h-16 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
            <h2 class="text-xl font-semibold text-gray-800">Your cart is empty.</h2>
            <p class="text-gray-500 mt-2">Add products from the <a href="b2b_dashboard.php" class="text-indigo-600 hover:text-indigo-800 underline">B2B Dashboard</a>.</p>
        </div>
    <?php else: ?>
    
        <form method="POST" action="cart.php">
            <input type="hidden" name="update_cart" value="1">
            
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                
                <div class="lg:col-span-2 space-y-4">
                    <?php foreach ($cart as $item): 
                        $item_subtotal = $item['qty'] * $item['price'];
                        $is_low_stock = $item['qty'] > $item['stock'];
                    ?>
                    <div class="bg-white p-4 rounded-xl shadow-lg border border-gray-100 relative flex flex-col sm:flex-row sm:items-center sm:justify-between space-y-3 sm:space-y-0 sm:space-x-4 <?php echo $is_low_stock ? 'border-red-400' : ''; ?>">
                        
                        <div class="flex items-start w-full sm:w-1/2">
                            <img src="<?php echo htmlspecialchars($item['image_url']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" 
                                 class="w-16 h-16 object-cover rounded-lg flex-shrink-0 mr-4"
                                 onerror="this.onerror=null; this.src='https://placehold.co/80x80/e5e7eb/374151?text=P';">
                            
                            <div class="flex-grow">
                                <h3 class="text-lg font-semibold text-gray-800"><?php echo htmlspecialchars($item['name']); ?></h3>
                                <p class="text-sm text-gray-500 hidden sm:block">SKU: <?php echo htmlspecialchars($item['sku']); ?></p>
                                <p class="text-md font-medium text-indigo-700 mt-1">
                                    <?php echo format_aed($item['price']); ?> / unit
                                </p>
                            </div>
                        </div>
                        
                        <div class="w-full sm:w-2/5 flex items-center justify-between border-t border-gray-100 pt-3 sm:pt-0 sm:border-t-0 sm:space-x-4">
                            
                            <div class="w-1/2 sm:w-32 text-center">
                                <label for="qty_<?php echo $item['id']; ?>" class="block text-xs font-medium text-gray-500 mb-1 sm:hidden">Quantity</label>
                                
                                <div class="flex items-center border border-gray-300 rounded-lg shadow-sm w-32 mx-auto">
                                    <button type="button" onclick="changeQuantity(<?php echo $item['id']; ?>, -1, <?php echo $item['stock']; ?>)" 
                                            class="p-1 text-gray-600 hover:text-gray-900 focus:outline-none transition duration-150">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"></path></svg>
                                    </button>
                                    <input type="number" 
                                           name="qty[<?php echo $item['id']; ?>]" 
                                           id="qty_<?php echo $item['id']; ?>" 
                                           min="1" 
                                           max="<?php echo $item['stock']; ?>"
                                           value="<?php echo $item['qty']; ?>" 
                                           class="w-full p-1 text-center text-sm focus:border-indigo-500 focus:ring-indigo-500 bg-white border-none focus:outline-none focus:ring-0 quantity-input"
                                           onchange="validateCartQuantity(this)">
                                    <button type="button" onclick="changeQuantity(<?php echo $item['id']; ?>, 1, <?php echo $item['stock']; ?>)" 
                                            class="p-1 text-gray-600 hover:text-gray-900 focus:outline-none transition duration-150">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                                    </button>
                                </div>
                            </div>
                            <div class="w-1/2 sm:w-32 text-right">
                                <label class="block text-xs font-medium text-gray-500 mb-1 sm:hidden">Item Total</label>
                                <p class="text-xl font-bold text-gray-900 whitespace-nowrap"><?php echo format_aed($item_subtotal); ?></p>
                            </div>
                        </div>

                        <div class="absolute top-2 right-2 sm:hidden">
                            <button type="button" onclick="removeItem(<?php echo $item['id']; ?>)"
                                    class="text-red-500 hover:text-red-700 p-1 rounded-full hover:bg-red-50 transition duration-150"
                                    title="Remove Item">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                            </button>
                        </div>
                        
                        <div class="w-8 flex-shrink-0 hidden sm:flex justify-end">
                            <button type="button" onclick="removeItem(<?php echo $item['id']; ?>)"
                                    class="text-red-500 hover:text-red-700 p-1 rounded-full hover:bg-red-50 transition duration-150"
                                    title="Remove Item">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                            </button>
                        </div>

                        <?php if ($is_low_stock): ?>
                            <p class="text-xs text-red-500 font-medium mt-2 pt-2 border-t border-red-100 w-full sm:hidden">
                                ! Stock: <?php echo $item['stock']; ?>. Please adjust quantity.
                            </p>
                        <?php endif; ?>

                    </div>
                    <?php endforeach; ?>
                    
                    <button type="submit" 
                            class="w-full lg:w-auto px-6 py-3 bg-indigo-500 text-white font-semibold rounded-lg shadow-md hover:bg-indigo-600 transition duration-150">
                        Update Cart Quantities
                    </button>
                </div>

                <div class="lg:col-span-1 bg-white p-6 rounded-xl shadow-2xl border border-gray-100 h-fit lg:sticky lg:top-20">
                    <h3 class="text-2xl font-bold text-gray-800 mb-4 border-b pb-2">Order Summary</h3>

                    <div class="space-y-3">
                        <div class="flex justify-between text-gray-600">
                            <span>Subtotal:</span>
                            <span><?php echo format_aed($cart_total); ?></span>
                        </div>
                        <div class="flex justify-between text-gray-600">
                            <span>VAT (0%):</span>
                            <span><?php echo format_aed(0); ?></span> </div>
                        <div class="flex justify-between text-gray-600">
                            <span>Delivery Fee:</span>
                            <span><?php echo format_aed(0); ?></span> </div>
                    </div>
                    
                    <hr class="my-4 border-gray-300">

                    <div class="flex justify-between text-2xl font-bold text-gray-900">
                        <span>TOTAL:</span>
                        <span class="text-green-600"><?php echo format_aed($cart_total); ?></span>
                    </div>
                    <p class="text-xs text-gray-500 mt-1">
                        *Total includes product price only, no extra fees applied.
                    </p>

                    <a href="checkout.php" 
                       class="mt-6 block w-full py-3 bg-green-600 text-white font-semibold rounded-lg shadow-lg hover:bg-green-700 text-center transition duration-150">
                        Proceed to Checkout
                    </a>
                    
                    <p class="text-xs text-center text-gray-500 mt-3">Payment Method: Cash on Delivery (COD) Only.</p>

                </div>

            </div>
        </form>
    <?php endif; ?>
</div>

<form id="remove-form" method="POST" action="cart.php" style="display: none;">
    <input type="hidden" name="remove_item" value="1">
    <input type="hidden" id="remove-product-id" name="product_id">
</form>

<script>
    // --- CLIENT-SIDE JS LOGIC ---
    
    // Function to show custom notifications (copied from dashboard for consistency)
    function showNotification(message, isSuccess = true, duration = 3000) {
        const box = document.getElementById('notification-box');
        // If box doesn't exist (because header.php didn't include it), skip notification
        if (!box) return; 
        
        document.getElementById('notification-message').textContent = message;
        
        box.className = 'fixed bottom-4 right-4 p-4 rounded-lg shadow-xl opacity-0 transition-opacity duration-300 z-50';
        box.classList.add(isSuccess ? 'bg-green-600' : 'bg-red-600');
        box.classList.remove('hidden');
        
        setTimeout(() => box.classList.add('opacity-100'), 10);

        setTimeout(() => {
            box.classList.remove('opacity-100');
            box.classList.remove('bg-green-600', 'bg-red-600'); 
            setTimeout(() => box.classList.add('hidden'), 300);
        }, duration);
    }
    
    // 1. Quantity Validation (Respect Stock and minimum 1 order)
    function validateCartQuantity(input) {
        let value = parseInt(input.value);
        const stock = parseInt(input.max); // Use the max attribute for stock limit
        const min_qty = 1;

        if (isNaN(value) || value < min_qty) {
            input.value = min_qty;
            showNotification(`Minimum order is ${min_qty}. Quantity for ${input.name} reset.`, false, 4000);
            return false;
        } else if (value > stock) {
            input.value = stock;
            showNotification(`Stock limit reached. Only ${stock} available. Quantity for ${input.name} reset.`, false, 4000);
            return false; 
        } 
        // Note: No immediate form submission, the user must click 'Update Cart Quantities'
        return true; 
    }
    
    // 2. Function to change quantity via plus/minus buttons
    function changeQuantity(productId, delta, maxStock) {
        const input = document.getElementById(`qty_${productId}`);
        
        let currentValue = parseInt(input.value);
        if (isNaN(currentValue)) currentValue = 1;

        let newValue = currentValue + delta;
        
        // Enforce minimum of 1
        if (newValue < 1) {
            newValue = 1;
            showNotification(`Minimum order quantity is 1.`, false, 2000);
        }
        
        // Enforce maximum stock
        if (newValue > maxStock) {
            newValue = maxStock;
            showNotification(`Stock limit reached. Only ${maxStock} available.`, false, 2000);
        }

        input.value = newValue; 
    }

    // 3. Handles removing an item using a hidden form submission
    function removeItem(productId) {
        if (confirm("Are you sure you want to remove this item from your cart?")) {
            document.getElementById('remove-product-id').value = productId;
            document.getElementById('remove-form').submit();
        }
    }
</script>

<?php
require_once 'includes/footer.php';
close_db_connection($conn);
?>