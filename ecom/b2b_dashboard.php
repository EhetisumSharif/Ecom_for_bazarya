<?php
require_once 'includes/functions.php';
require_once 'includes/db.php';

// REQUIREMENT B: Secure login for approved customers
if (!is_b2b_logged_in()) {
    redirect('b2b_login.php');
}

// --- NEW SECURITY CHECK: Verify if the logged-in user is still active in the database ---
// Assumes the logged-in user ID is stored in $_SESSION['user_id'] based on functions.php
$logged_in_user_id = $_SESSION['user_id'] ?? 0; 

if ($logged_in_user_id > 0) {
    // CRITICAL: We need $conn here. Assuming 'includes/db.php' provides $conn.
    global $conn; 
    
    $sql_check = "SELECT is_active FROM b2b_users WHERE id = ?";
    $stmt_check = $conn->prepare($sql_check);
    
    // Check if the statement prepared successfully before binding/executing
    if ($stmt_check) {
        $stmt_check->bind_param("i", $logged_in_user_id);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();
        
        if ($row = $result_check->fetch_assoc()) {
            $is_active = (bool)$row['is_active'];
            
            if (!$is_active) {
                // User is no longer active - FORCED LOGOUT
                
                // Clear all session variables
                $_SESSION = array();
                
                // Destroy the session
                if (ini_get("session.use_cookies")) {
                    $params = session_get_cookie_params();
                    setcookie(session_name(), '', time() - 42000,
                        $params["path"], $params["domain"],
                        $params["secure"], $params["httponly"]
                    );
                }
                session_destroy();
                
                // Redirect to login page with an error message
                redirect('b2b_login.php?error=' . urlencode('Your account has been deactivated by administration.'));
                exit; // Stop further execution
            }
        }
        $stmt_check->close();
    }
}
// --- END NEW SECURITY CHECK ---

require_once 'includes/header.php';

// --- PHP Logic for Fetching Products (B2B View) ---
$products = [];
$search_term = $_GET['search'] ?? '';
$category_filter = $_GET['category'] ?? 'all';
$sort_by = $_GET['sort'] ?? 'name';
$sort_dir = $_GET['dir'] ?? 'ASC';

// Build the dynamic SQL query
$sql = "
    SELECT 
        p.id, p.name, p.sku, p.description, p.image_url, p.price, p.stock, 
        p.origin, p.unit, p.weight_kg, 
        c.name AS category 
    FROM products p
    JOIN categories c ON p.category_id = c.id
    WHERE p.is_visible = TRUE
";

$params = [];
$types = '';

// Add search condition (Requirement C)
if (!empty($search_term)) {
    $search_param = '%' . $search_term . '%';
    $sql .= " AND (p.name LIKE ? OR p.sku LIKE ?)";
    $types .= 'ss';
    $params[] = $search_param;
    $params[] = $search_param;
}

// Add category filter condition (Requirement C)
if ($category_filter !== 'all') {
    $sql .= " AND c.name = ?";
    $types .= 's';
    $params[] = $category_filter;
}

// Add sorting (Requirement C)
$allowed_sorts = ['name', 'sku', 'price', 'stock', 'category'];
$sort_column = in_array($sort_by, $allowed_sorts) ? $sort_by : 'name';
$sort_direction = strtoupper($sort_dir) === 'DESC' ? 'DESC' : 'ASC';

// Special case: sort category by c.name
$sort_target = ($sort_column === 'category') ? 'c.name' : 'p.' . $sort_column;

$sql .= " ORDER BY " . $sort_target . " " . $sort_direction;

// Prepare and execute the statement
$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

if ($result) {
    while($row = $result->fetch_assoc()) {
        $products[] = $row;
    }
}
$stmt->close();

// Fetch all categories for the filter dropdown
$categories = [];
$cat_result = $conn->query("SELECT name FROM categories ORDER BY name");
if ($cat_result) {
    while($row = $cat_result->fetch_assoc()) {
        $categories[] = $row['name'];
    }
}

// Helper function to get the current sort direction for the header link
function get_sort_link($column, $current_sort_by, $current_sort_dir, $search_term, $category_filter) {
    $new_dir = ($column === $current_sort_by && $current_sort_dir === 'ASC') ? 'DESC' : 'ASC';
    $query = http_build_query([
        'search' => $search_term,
        'category' => $category_filter,
        'sort' => $column,
        'dir' => $new_dir
    ]);
    return '?' . $query;
}
?>

<div class="pb-8 px-4 sm:px-6 lg:px-8"> 
    <div class="flex justify-between items-center mb-6 pt-4">
		<h1 class="text-2xl sm:text-3xl font-extrabold text-gray-900">B2B Product Dashboard</h1> 
        <a href="b2b_orders.php" class="inline-flex items-center px-3 py-1 sm:px-4 sm:py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-red-600 hover:bg-red-700 transition duration-150">
			<svg class="w-4 h-4 mr-1 sm:mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
			<span class="hidden sm:inline">View Orders</span>
			<span class="inline sm:hidden">Orders</span>
		</a>
	</div>

    <div class="bg-white p-4 rounded-xl shadow-lg mb-6 flex flex-col md:flex-row gap-4 items-center">
        <form method="GET" action="b2b_dashboard.php" class="flex flex-col md:flex-row flex-grow w-full gap-4">
            <input type="text" name="search" placeholder="Search by Name or SKU..."
                   class="p-3 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 shadow-sm w-full md:flex-grow"
                   value="<?php echo htmlspecialchars($search_term); ?>">

            <select name="category" class="p-3 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 shadow-sm w-full md:w-48" onchange="this.form.submit()">
                <option value="all">Filter by Category (All)</option>
                <?php foreach ($categories as $cat_name): ?>
                    <option value="<?php echo htmlspecialchars($cat_name); ?>" 
                        <?php echo $cat_name === $category_filter ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($cat_name); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            
            <button type="submit" class="p-3 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition duration-150 w-full md:w-auto">
                <svg class="w-5 h-5 mx-auto md:mx-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
            </button>
        </form>
    </div>

    <div id="notification-box" class="fixed bottom-4 right-4 bg-green-600 text-white p-4 rounded-lg shadow-xl hidden opacity-0 transition-opacity duration-300 z-50">
        <p id="notification-message"></p>
    </div>

    <div class="product-table-wrapper bg-white rounded-xl shadow-lg border border-gray-100 overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 text-sm text-left text-gray-500">
            <thead class="bg-gray-100 text-xs text-gray-700 uppercase tracking-wider">
                <tr>
                    <th scope="col" class="px-3 py-3 w-12 sm:w-16">Image</th>
                    
                    <th scope="col" class="px-3 py-3 cursor-pointer hover:bg-gray-200 transition duration-150" onclick="window.location.href='<?php echo get_sort_link('name', $sort_by, $sort_dir, $search_term, $category_filter); ?>'">
                        Product Name <?php echo ($sort_by === 'name') ? ($sort_dir === 'ASC' ? '▲' : '▼') : ''; ?>
                    </th>
                    
                    <th scope="col" class="px-3 py-3 hidden sm:table-cell">Origin</th> 
                    <th scope="col" class="px-3 py-3 hidden md:table-cell">Unit</th> 
                    <th scope="col" class="px-3 py-3 hidden lg:table-cell">Weight</th> 
                    
                    <th scope="col" class="px-3 py-3 cursor-pointer hover:bg-gray-200 transition duration-150 text-right" onclick="window.location.href='<?php echo get_sort_link('price', $sort_by, $sort_dir, $search_term, $category_filter); ?>'">
                        Price <?php echo ($sort_by === 'price') ? ($sort_dir === 'ASC' ? '▲' : '▼') : ''; ?>
                    </th>
                    
                    <th scope="col" class="px-3 py-3 w-32 text-center">Qty</th> 
                    
                    <th scope="col" class="px-3 py-3 w-28">Action</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php if (!empty($products)): ?>
                    <?php foreach ($products as $product): 
                        // The stock column is no longer displayed, but is needed for validation
                        $stock_alert = $product['stock'] < 50 ? 'text-red-500 font-semibold' : 'text-green-600'; 
                        $disabled_class = $product['stock'] <= 0 ? 'opacity-50 cursor-not-allowed' : '';
                        $disabled_attr = $product['stock'] <= 0 ? 'disabled' : '';
                    ?>
                    <tr class="hover:bg-gray-50 product-row" data-product-id="<?php echo $product['id']; ?>">
                        <td class="px-3 py-2">
                            <img src="<?php echo htmlspecialchars($product['image_url']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" 
                                 class="w-10 h-10 sm:w-12 sm:h-12 object-cover rounded-md shadow-sm" 
                                 onerror="this.onerror=null; this.src='https://placehold.co/50x50/333333/ffffff?text=P';">
                        </td>
                        <td class="px-3 py-2 font-medium text-gray-900">
                            <?php echo htmlspecialchars($product['name']); ?>
                            <span class="block text-xs text-gray-400 sm:hidden">
                                (Origin: <?php echo htmlspecialchars($product['origin']); ?>)
                            </span>
                        </td>
                        
                        <td class="px-3 py-2 hidden sm:table-cell"><?php echo htmlspecialchars($product['origin']); ?></td> 
                        
                        <td class="px-3 py-2 hidden md:table-cell"><?php echo htmlspecialchars($product['unit']); ?></td> 
                        
                        <td class="px-3 py-2 hidden lg:table-cell"><?php echo htmlspecialchars($product['weight_kg']) . ' kg'; ?></td> 
                        
                        <td class="px-3 py-2 font-bold text-lg text-indigo-700 text-right whitespace-nowrap">
                            <?php echo format_aed($product['price']); ?>
                        </td>
                        <td class="px-3 py-2">
                            <div class="flex items-center border border-gray-300 rounded-md shadow-sm w-32 mx-auto <?php echo $disabled_class; ?>">
                                <button type="button" onclick="changeQuantity(<?php echo $product['id']; ?>, -1)" 
                                        class="p-1 text-gray-600 hover:text-gray-900 focus:outline-none transition duration-150"
                                        <?php echo $disabled_attr; ?>>
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"></path></svg>
                                </button>
                                <input type="number" min="1" value="1"
                                       data-stock="<?php echo $product['stock']; ?>"
                                       id="qty-<?php echo $product['id']; ?>"
                                       class="w-full p-1 text-center quantity-input text-base focus:border-indigo-500 focus:ring-indigo-500 bg-white border-none focus:outline-none focus:ring-0"
                                       onchange="validateQuantity(this)"
                                       <?php echo $disabled_attr; ?>>
                                <button type="button" onclick="changeQuantity(<?php echo $product['id']; ?>, 1)" 
                                        class="p-1 text-gray-600 hover:text-gray-900 focus:outline-none transition duration-150"
                                        <?php echo $disabled_attr; ?>>
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                                </button>
                            </div>
                        </td>
                        <td class="px-3 py-2">
                            <button onclick="addToCart(<?php echo $product['id']; ?>)" 
                                    class="w-full bg-green-600 hover:bg-green-700 text-white font-medium py-2 px-1 sm:px-3 rounded-lg shadow-md transition duration-150 ease-in-out disabled:opacity-50 <?php echo $disabled_class; ?>"
                                    <?php echo $disabled_attr; ?>>
                                <span class="hidden sm:inline">Add to Cart</span>
                                <svg class="w-5 h-5 mx-auto inline sm:hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="8" class="text-center py-10 text-gray-500">
                            No products match your current search and filter criteria.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

</div>

<script>
    // --- CLIENT-SIDE JS LOGIC ---

    // Function to show custom notifications
    function showNotification(message, isSuccess = true, duration = 3000) {
        const box = document.getElementById('notification-box');
        document.getElementById('notification-message').textContent = message;
        
        box.className = 'fixed bottom-4 right-4 p-4 rounded-lg shadow-xl opacity-0 transition-opacity duration-300 z-50';
        box.classList.add(isSuccess ? 'bg-green-600' : 'bg-red-600');
        box.classList.remove('hidden');
        
        setTimeout(() => box.classList.add('opacity-100'), 10);

        setTimeout(() => {
            box.classList.remove('opacity-100');
            // Remove the color classes before hiding for a cleaner transition next time
            box.classList.remove('bg-green-600', 'bg-red-600'); 
            setTimeout(() => box.classList.add('hidden'), 300);
        }, duration);
    }

    // 1. Quantity Validation (Respect Stock and minimum 1 order)
    function validateQuantity(input) {
        let value = parseInt(input.value);
        const stock = parseInt(input.dataset.stock);
        const min_qty = 1;

        if (isNaN(value) || value < min_qty) {
            input.value = min_qty;
            showNotification(`Minimum order is ${min_qty}. Quantity reset.`, false, 4000);
            return false; // Indicate validation failed
        } else if (value > stock) {
            input.value = stock;
            showNotification(`Error: Stock limit reached. Only ${stock} available. Quantity reset.`, false, 4000);
            return false; // Indicate validation failed
        } 
        return true; // Indicate validation passed
    }
    
    // NEW: Function to change quantity via plus/minus buttons
    function changeQuantity(productId, delta) {
        const input = document.getElementById(`qty-${productId}`);
        if (input.disabled) return;
        
        let currentValue = parseInt(input.value);
        if (isNaN(currentValue)) currentValue = 1;

        let newValue = currentValue + delta;
        
        // Prevent setting a value less than 1 before triggering validation
        if (newValue < 1) {
            newValue = 1;
        }

        input.value = newValue; 
        
        // Use validateQuantity to enforce min/max rules immediately
        validateQuantity(input);
    }
    
    // 2. Add to Cart Logic (AJAX call to cart_handler.php)
    function addToCart(productId) {
        const input = document.getElementById(`qty-${productId}`);
        
        // Ensure quantity is validated client-side before sending
        if (!validateQuantity(input)) {
            return; // Stop if validation fails
        }
        const finalQuantity = parseInt(input.value); 
        
        // Disable Add to Cart button during request
        const button = input.closest('tr').querySelector('button[onclick^="addToCart"]'); // Target the Add to Cart button
        button.disabled = true;
        
        // Disable plus/minus buttons while adding
        const qtyButtons = input.closest('td').querySelectorAll('button');
        qtyButtons.forEach(btn => btn.disabled = true);

        // UI Feedback
        const buttonTextSpan = button.querySelector('span');
        const originalText = buttonTextSpan ? 'Add to Cart' : ''; 
        
        if (buttonTextSpan) buttonTextSpan.textContent = 'Adding...';
        else button.innerHTML = '<svg class="w-5 h-5 mx-auto animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v.01M16 4v.01M8 4v.01"></path></svg>'; 

        fetch('cart_handler.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'add', product_id: productId, qty: finalQuantity })
        })
        .then(response => {
            if (!response.ok) {
                // Handle non-200 responses
                return response.json().then(data => { throw new Error(data.message || 'Server error.'); });
            }
            return response.json();
        })
        .then(data => {
            // Re-enable and reset Add to Cart button
            button.disabled = false;
            if (buttonTextSpan) buttonTextSpan.textContent = originalText;
            else button.innerHTML = '<svg class="w-5 h-5 mx-auto inline sm:hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>'; 
            
            // Re-enable plus/minus buttons (unless stock is 0, which is handled by $disabled_attr)
            if (!input.disabled) { // Check if the input itself wasn't disabled due to 0 stock
                 qtyButtons.forEach(btn => btn.disabled = false);
            }


            if (data.success) {
                // If quantity was adjusted server-side, update the input field
                if (data.final_qty && parseInt(data.final_qty) !== finalQuantity) {
                    input.value = data.final_qty;
                }
                
                // Update the header cart count instantly (Real-time update)
                if (data.new_item_count !== undefined) {
                    const cartCountElement = document.getElementById('cart-count-display');
                    if (cartCountElement) {
                        cartCountElement.textContent = data.new_item_count;
                    }
                }

                showNotification(data.message);
                
            } else {
                showNotification(`Failed to add to cart: ${data.message}`, false);
            }
        })
        .catch(error => {
            // Re-enable buttons on failure
            button.disabled = false;
            if (buttonTextSpan) buttonTextSpan.textContent = originalText;
            else button.innerHTML = '<svg class="w-5 h-5 mx-auto inline sm:hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>'; 
            
            if (!input.disabled) {
                qtyButtons.forEach(btn => btn.disabled = false);
            }
            
            console.error('Cart error:', error);
            showNotification(error.message || "A network error occurred.", false);
        });
    }

    // 3. Auto-submit search/filter form on Enter key in search box
    document.addEventListener('DOMContentLoaded', () => {
        const searchInput = document.querySelector('input[name="search"]');
        if (searchInput) {
            searchInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    this.closest('form').submit();
                }
            });
        }
    });

</script>

<?php
require_once 'includes/footer.php';
close_db_connection($conn);
?>