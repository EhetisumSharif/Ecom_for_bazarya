<?php
require_once '../includes/functions.php';
require_once '../includes/db.php';

// Set header to JSON
header('Content-Type: application/json');

// --- Admin Authentication Check (Crucial for security) ---
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized access.']);
    exit();
}

// Get and sanitize order ID
$order_id = (int)($_GET['id'] ?? 0);

if ($order_id <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid Order ID provided.']);
    exit();
}

try {
    // 1. Fetch main order details
    $order_sql = "
        SELECT 
            o.delivery_address, o.contact_number, o.total_amount, 
            u.contact_name, u.company_name
        FROM orders o
        JOIN b2b_users u ON o.b2b_user_id = u.id
        WHERE o.id = ?
    ";
    $stmt = $conn->prepare($order_sql);
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    $order_result = $stmt->get_result();
    $order_data = $order_result->fetch_assoc();
    $stmt->close();

    if (!$order_data) {
        http_response_code(404);
        echo json_encode(['error' => 'Order not found.']);
        close_db_connection($conn);
        exit();
    }

    // 2. Fetch order items (joining with products to get the name)
    $items_sql = "
        SELECT 
            oi.quantity, oi.unit_price, p.name AS product_name
        FROM order_items oi
        JOIN products p ON oi.product_id = p.id
        WHERE oi.order_id = ?
    ";
    $stmt = $conn->prepare($items_sql);
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    $items_result = $stmt->get_result();
    $order_items = [];
    while($row = $items_result->fetch_assoc()) {
        $order_items[] = $row;
    }
    $stmt->close();

    // 3. Combine and return data
    $response = [
        'id' => $order_id,
        'address' => htmlspecialchars($order_data['delivery_address']),
        'contact' => htmlspecialchars($order_data['contact_number']),
        'total' => (float)$order_data['total_amount'],
        'items' => $order_items
    ];

    echo json_encode($response);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}

close_db_connection($conn);
?>