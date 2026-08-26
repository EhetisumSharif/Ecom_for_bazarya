<?php
require_once 'includes/functions.php';
require_once 'includes/db.php';

// REQUIREMENT B: Secure login required
if (!is_b2b_logged_in()) {
    http_response_code(401); // Unauthorized
    echo json_encode(['success' => false, 'message' => 'Authentication required.']);
    exit;
}

// Ensure the request is POST and contains JSON data
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_SERVER['CONTENT_TYPE']) || strpos($_SERVER['CONTENT_TYPE'], 'application/json') === false) {
    http_response_code(400); // Bad Request
    echo json_encode(['success' => false, 'message' => 'Invalid request format.']);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);
$user_id = $_SESSION['user_id'];

// Check required fields
if (!isset($data['action'], $data['product_id'], $data['qty']) || $data['action'] !== 'add') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing action, product_id, or quantity.']);
    exit;
}

$product_id = (int)$data['product_id'];
$quantity = (int)$data['qty'];

// --- Get the update function from cart.php ---
/**
 * Gets product details from the database by ID.
 * @param int $id Product ID.
 * @param mysqli $conn Database connection object.
 * @return array|null Product data array or null.
 */
function get_product_for_cart_handler($id, $conn) {
    // Assuming the DB connection $conn is available globally or passed
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
 */
function update_session_cart($user_id, $product_id, $quantity, $conn) {
    $product = get_product_for_cart_handler($product_id, $conn);
    $error = '';

    if (!$product) {
        return ['success' => false, 'message' => 'Product not found.'];
    }

    // Initialize user cart if it doesn't exist
    if (!isset($_SESSION['cart'][$user_id])) {
        $_SESSION['cart'][$user_id] = [];
    }
    
    // Server-side validation (Stock check)
    if ($quantity > $product['stock']) {
        $quantity = $product['stock']; // Cap the quantity at available stock
        if ($quantity <= 0) {
            return ['success' => false, 'message' => "Product is out of stock."];
        }
        // Don't overwrite error if one happened first
        $error = "Stock limit reached. Only {$product['stock']} units of {$product['name']} available. Quantity reset.";
    }

    // If item already exists, use the quantity passed, which should be the validated amount
    $_SESSION['cart'][$user_id][$product_id] = [
        'id' => $product['id'],
        'name' => $product['name'],
        'sku' => $product['sku'],
        'price' => $product['price'],
        'qty' => $quantity,
        'image_url' => $product['image_url'],
        'stock' => $product['stock'],
    ];
    
    // Return the final quantity if update was successful
    return [
        'success' => true, 
        'message' => empty($error) ? "Product added/updated successfully." : $error,
        'final_qty' => $quantity
    ];
}
// --- End Get the update function from cart.php ---


// Process the 'add' action
$result = update_session_cart($user_id, $product_id, $quantity, $conn);

// --- NEW CODE: Get the updated count for the real-time update ---
$updated_count = get_cart_item_count();
// --- END NEW CODE ---


// Set appropriate HTTP status code
if (!$result['success']) {
    http_response_code(422); // Unprocessable Entity (Validation Error)
    $response = ['success' => false, 'message' => $result['message']];
} else {
    // Return the final quantity if update was successful
    $response = [
        'success' => true, 
        'message' => $result['message'],
        'final_qty' => $result['final_qty'],
        // --- ADD THE COUNT TO THE RESPONSE ---
        'new_item_count' => $updated_count
    ];
}

echo json_encode($response);
exit;