<?php
require_once 'includes/functions.php';
require_once 'includes/db.php';
require_once 'includes/header.php';

// Check if user is logged in
$is_logged_in = is_b2b_logged_in();

// Fetch all products (publicly visible ones)
$products = [];
$sql = "SELECT p.id, p.name, p.sku, p.description, p.image_url, c.name AS category 
        FROM products p
        JOIN categories c ON p.category_id = c.id
        WHERE p.is_visible = TRUE
        ORDER BY p.name";

$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $products[] = $row;
    }
}
?>

<div class="pb-8 px-4 sm:px-6 lg:px-8">
    <h2 class="text-3xl font-bold text-gray-800 mb-6">Product Catalog</h2>

    <div class="bg-white p-4 rounded-xl shadow-md mb-6 flex flex-col md:flex-row gap-4 items-center">
        <input type="text" id="public-search" placeholder="Search by Product Name or SKU..."
               class="w-full p-3 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 shadow-sm">
    </div>

    <div id="product-grid" class="grid grid-cols-2 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
        <?php if (!empty($products)): ?>
            <?php foreach ($products as $product): ?>
            <div class="bg-white rounded-xl shadow-lg hover:shadow-xl transition duration-300 border border-gray-100 product-card" 
                 data-name="<?php echo htmlspecialchars(strtolower($product['name'])); ?>" 
                 data-sku="<?php echo htmlspecialchars(strtolower($product['sku'])); ?>"
                 data-category="<?php echo htmlspecialchars(strtolower($product['category'])); ?>">
                
                <img class="w-full h-32 sm:h-48 object-cover rounded-t-xl" src="<?php echo htmlspecialchars($product['image_url']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" onerror="this.onerror=null; this.src='https://placehold.co/400x300/e5e7eb/374151?text=Image+Unavailable';">
                
                <div class="p-3 sm:p-4">
                    <span class="text-xs font-medium text-indigo-600 bg-indigo-50 py-1 px-2 rounded-full"><?php echo htmlspecialchars($product['category']); ?></span>
                    <h4 class="text-base sm:text-lg font-semibold text-gray-800 mt-2 mb-1"><?php echo htmlspecialchars($product['name']); ?></h4>
                    <p class="text-xs text-gray-500 mb-4">SKU: <?php echo htmlspecialchars($product['sku']); ?></p>
                    
                    
                    <a href="product.php?id=<?php echo $product['id']; ?>" class="block py-2 border border-indigo-600 text-indigo-600 font-semibold rounded-lg hover:bg-indigo-50 transition duration-150 text-center text-sm">
                        View Details
                    </a>
                </div>
            </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p class="col-span-full text-center py-10 text-gray-500">No products match your criteria. The public catalog is empty.</p>
        <?php endif; ?>
    </div>
</div>

<script>
    // Client-side filtering for public catalog
    document.getElementById('public-search').addEventListener('input', function(e) {
        const searchTerm = e.target.value.toLowerCase();
        const cards = document.querySelectorAll('.product-card');

        cards.forEach(card => {
            const name = card.dataset.name;
            const sku = card.dataset.sku;
            
            if (name.includes(searchTerm) || sku.includes(searchTerm)) {
                card.style.display = 'block';
            } else {
                card.style.display = 'none';
            }
        });
    });
</script>

<?php
require_once 'includes/footer.php';
close_db_connection($conn);
?>