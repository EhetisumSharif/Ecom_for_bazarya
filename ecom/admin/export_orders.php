<?php
require_once '../includes/functions.php';
require_once '../includes/db.php';

// --- Admin Authentication Check (Similar to orders.php) ---
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    // If not logged in, redirect them back to login or deny access
    exit('Unauthorized access.');
}

// 1. Get the filter status from the URL
$filter_status = $_GET['status'] ?? 'all';

// --- 2. Data Fetching (Use the same logic as orders.php) ---

$orders = [];
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
close_db_connection($conn); // Close connection after fetching data

// --- 3. CSV/Excel Export Logic ---

// Set headers to force a file download (CSV format)
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="orders_' . strtolower($filter_status) . '_' . date('Ymd_His') . '.csv"');
header('Pragma: no-cache');
header('Expires: 0');

// Open PHP output stream
$output = fopen('php://output', 'w');

// Define CSV column headers
$headers = [
    'Order ID', 'Date', 'Status', 'Total Amount (AED)', 
    'Buyer Contact Name', 'Company Name', 'Contact Email', 
    'Contact Phone'
];
fputcsv($output, $headers);

// Write data rows
if (!empty($orders)) {
    foreach ($orders as $order) {
        $row = [
            $order['id'],
            date('Y-m-d H:i:s', strtotime($order['order_date'])),
            $order['status'],
            $order['total_amount'],
            $order['contact_name'],
            $order['company_name'],
            $order['email'],
            $order['contact_number']
        ];
        fputcsv($output, $row);
    }
}

// Close output stream
fclose($output);
exit;

?>