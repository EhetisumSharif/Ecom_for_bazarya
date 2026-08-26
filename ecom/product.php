<?php
require_once 'includes/functions.php';
require_once 'includes/db.php';
require_once 'includes/header.php';

$product = null;
$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$is_logged_in = is_b2b_logged_in();

if ($product_id > 0) {
    // Fetch product details, including category name
    $stmt = $conn->prepare("
        SELECT 
            p.id, p.name, p.sku, p.description, p.features, p.image_url, p.price, p.stock, p.moq, 
            c.name AS category 
        FROM products p
        JOIN categories c ON p.category_id = c.id
        WHERE p.id = ? AND p.is_visible = TRUE
    ");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $product = $result->fetch_assoc();
    }
    $stmt->close();
}
?>

<div class="pb-8 px-4 sm:px-6 lg:px-8">
    <?php if ($product): ?>
    <div class="bg-white p-6 sm:p-8 rounded-xl shadow-2xl border border-gray-100">
        <h1 class="text-3xl sm:text-4xl font-extrabold text-gray-900 mb-6"><?php echo htmlspecialchars($product['name']); ?></h1>
        
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            
            <div class="lg:col-span-1">
                <img class="w-full h-auto object-cover rounded-xl shadow-lg border border-gray-200" 
                     src="<?php echo htmlspecialchars($product['image_url']); ?>" 
                     alt="<?php echo htmlspecialchars($product['name']); ?>" 
                     onerror="this.onerror=null; this.src='https://placehold.co/600x600/e5e7eb/374151?text=Image+Unavailable';">
            </div>
            
            <div class="lg:col-span-2">
                
                <p class="text-sm font-semibold text-indigo-600 mb-2">Category: <?php echo htmlspecialchars($product['category']); ?></p>
                <p class="text-sm text-gray-500 mb-4">SKU/Product Code: <span class="font-mono font-bold text-gray-700"><?php echo htmlspecialchars($product['sku']); ?></span></p>

                <hr class="my-4">

                <h3 class="text-xl sm:text-2xl font-semibold text-gray-800 mb-2">Description</h3>
                <p class="text-gray-700 mb-6 leading-relaxed text-sm sm:text-base"><?php echo nl2br(htmlspecialchars($product['description'])); ?></p>

                <h3 class="text-xl sm:text-2xl font-semibold text-gray-800 mb-2">Features</h3>
                <div class="text-gray-700 mb-6 leading-relaxed text-sm sm:text-base">
                    <?php 
                    // Simple formatting for features list
                    $features = explode(';', $product['features']);
                    echo '<ul class="list-disc list-inside pl-4 space-y-1">';
                    foreach ($features as $feature) {
                        $feature = trim($feature);
                        if (!empty($feature)) {
                            echo '<li>' . htmlspecialchars($feature) . '</li>';
                        }
                    }
                    echo '</ul>';
                    ?>
                </div>

                <div class="mt-8 p-6 bg-green-50 rounded-xl border border-green-200 shadow-inner">
                    <h3 class="text-2xl font-bold text-green-800 mb-3">
                        <?php echo $is_logged_in ? 'B2B Wholesale Access' : 'Wholesale Pricing Access'; ?>
                    </h3>

                    <?php if ($is_logged_in): ?>
                        <div class="mb-4">
                            <p class="text-xl font-extrabold text-green-700">Price: <?php echo format_aed($product['price']); ?> / unit</p>
                        </div>
                        <a href="b2b_dashboard.php" 
                           class="w-full sm:w-auto block py-3 px-6 bg-indigo-600 text-white font-semibold rounded-lg shadow-md hover:bg-indigo-700 text-center transition duration-150">
                            Go to Dashboard to Order
                        </a>
                    <?php else: ?>
                        <p class="text-gray-700 mb-4">
                            Wholesale prices are only visible to approved B2B customers.
                        </p>
                        <a href="b2b_login.php" 
                           class="w-full sm:w-auto block py-3 px-6 bg-indigo-600 text-white font-semibold rounded-lg shadow-md hover:bg-indigo-700 text-center transition duration-150">
                            Login to See Pricing
                        </a>
                        <p class="text-xs text-gray-500 mt-2">New user? Contact us to apply for a B2B account.</p>
                    <?php endif; ?>
                </div>

            </div>
        </div>
        
    </div>
    <?php else: ?>
        <div class="bg-white p-12 rounded-xl shadow-lg border border-gray-100 text-center">
            <h2 class="text-3xl font-bold text-red-500 mb-4">Product Not Found</h2>
            <p class="text-gray-600">The product you are looking for does not exist or is currently unavailable.</p>
            <a href="products.php" class="mt-6 inline-block text-indigo-600 hover:text-indigo-800 font-medium">
                ← Back to Catalog
            </a>
        </div>
    <?php endif; ?>
</div>

<?php
require_once 'includes/footer.php';
close_db_connection($conn);
?>