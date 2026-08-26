<?php
require_once 'includes/functions.php';
require_once 'includes/db.php'; // Includes DB connection
require_once 'includes/header.php';

// Fetch a few featured products (limit 3)
$featured_products = [];
// Using prepared statements is essential for security
$sql = "SELECT id, name, sku, description, image_url FROM products WHERE is_visible = TRUE ORDER BY id DESC LIMIT 3";
$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $featured_products[] = $row;
    }
}
// Close the result set if it exists
if (isset($result) && is_object($result)) {
    $result->close();
}
?>

<div class="pb-8 px-4 sm:px-6 lg:px-8">
    <div class="relative bg-green-700 text-white rounded-xl shadow-2xl overflow-hidden mb-12 h-48 sm:h-64 flex items-center justify-center">
        <img class="absolute inset-0 h-full w-full object-cover opacity-30" src="pic.jpg" alt="Fresh fruits and vegetables market">
        <div class="relative z-10 text-center p-6">
            <h2 class="text-3xl lg:text-5xl font-extrabold mb-2">Sourcing the World's Freshest Produce</h2>
            <p class="text-sm sm:text-xl font-light">Direct importer and wholesaler at Ras Al Khor Central Market, Dubai.</p>
        </div>
    </div>

    <h3 class="text-2xl font-bold text-gray-800 mb-6 border-b pb-2">Main Sourcing Countries</h3>
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4 sm:gap-6 mb-12">
        <?php 
            // Using actual flag SVGs for better visual representation
            $categories_data = [
			    ['name' => 'Bangladesh', 'svg' => '<svg viewBox="0 0 1000 600" xmlns="http://www.w3.org/2000/svg" class="w-10 h-10 mx-auto mb-3"><rect width="1000" height="600" fill="#006a4e" /><circle cx="450" cy="300" r="120" fill="#f42a41" /></svg>'],
                ['name' => 'India', 'svg' => '<svg viewBox="0 0 900 600" xmlns="http://www.w3.org/2000/svg" class="w-10 h-10 mx-auto mb-3"><rect width="900" height="600" fill="#f93" /><rect width="900" height="400" fill="#fff" /><rect width="900" height="200" fill="#128807" /><circle cx="450" cy="300" r="90" fill="#000080" /><circle cx="450" cy="300" r="80" fill="#fff" /><circle cx="450" cy="300" r="12" fill="#000080" /><g fill="#000080"><circle cx="450" cy="215" r="3" /><circle cx="450" cy="385" r="3" /><circle cx="365" cy="300" r="3" /><circle cx="535" cy="300" r="3" /></g><path fill="#000080" d="M450 300l0-85M450 300l0 85M450 300l-85 0M450 300l85 0" stroke="#000080" stroke-width="2" /></svg>'],
                ['name' => 'Pakistan', 'svg' => '<svg viewBox="0 0 900 600" xmlns="http://www.w3.org/2000/svg" class="w-10 h-10 mx-auto mb-3"><rect width="900" height="600" fill="#006600"/><rect width="225" height="600" fill="#fff"/><g transform="translate(480 300) scale(1.1)"><path d="M 0 100 A 100 100 0 0 0 0 -100 L 20 0 Z" fill="#fff" transform="translate(100 0)"/><path d="M 0 50 L 20 50 L 10 -10 Z" fill="#fff" transform="translate(205 0) scale(0.6) rotate(-30)"/><path d="M 0 50 L 20 50 L 10 -10 Z" fill="#fff" transform="translate(210 0) scale(0.6) rotate(30)"/><path d="M 0 50 L 20 50 L 10 -10 Z" fill="#fff" transform="translate(205 0) scale(0.6) rotate(90)"/><path d="M 0 50 L 20 50 L 10 -10 Z" fill="#fff" transform="translate(210 0) scale(0.6) rotate(150)"/><path d="M 0 50 L 20 50 L 10 -10 Z" fill="#fff" transform="translate(205 0) scale(0.6) rotate(210)"/></g></svg>'],
                ['name' => 'Egypt', 'svg' => '<svg viewBox="0 0 900 600" xmlns="http://www.w3.org/2000/svg" class="w-10 h-10 mx-auto mb-3"><rect width="900" height="600" fill="#c8102e" /><rect width="900" height="400" fill="#fff" /><rect width="900" height="200" fill="#000" /><path d="M492.3 328.6a30 30 0 0 0-14.7 4.1a30 30 0 0 0-17.7 25.4a30 30 0 0 0 57.1 11.2a30 30 0 0 0-24.7-40.7" fill="#ffcd00" /><path d="M492.3 328.6a30 30 0 0 0-14.7 4.1a30 30 0 0 0-17.7 25.4a30 30 0 0 0 57.1 11.2a30 30 0 0 0-24.7-40.7" fill="#ffcd00" transform="translate(14, -28)" /><path d="M449.6 300l-20-40l40 0zM449.6 300l-20 40l40 0z" fill="#000" transform="translate(0, 50)" /></svg>'],
                ['name' => 'Philippines', 'svg' => '<svg viewBox="0 0 1200 600" xmlns="http://www.w3.org/2000/svg" class="w-10 h-10 mx-auto mb-3"><rect width="1200" height="600" fill="#fff" /><rect width="1200" height="300" fill="#0038a8" /><rect width="1200" height="300" y="300" fill="#ce1126" /><path d="M0 0l600 300L0 600z" fill="#fff" /><circle cx="200" cy="300" r="60" fill="#fcd116" /><path d="M200 300l40-100m-40 100l-40-100m40 100l0-100" stroke="#fcd116" stroke-width="12" transform="rotate(22.5,200,300)" /><circle cx="200" cy="180" r="15" fill="#fcd116" /><circle cx="80" cy="300" r="15" fill="#fcd116" /><circle cx="200" cy="420" r="15" fill="#fcd116" /><circle cx="320" cy="300" r="15" fill="#fcd116" /></svg>'],
            ];
            
            foreach ($categories_data as $cat):
        ?>
        <a href="products.php?country=<?php echo urlencode($cat['name']); ?>" class="bg-white p-6 rounded-xl shadow-lg hover:shadow-xl transition duration-300 transform hover:-translate-y-1 text-center">
            <?php echo $cat['svg']; ?>
            <p class="font-semibold text-gray-700 text-sm sm:text-base"><?php echo $cat['name']; ?></p>
        </a>
        <?php endforeach; ?>
    </div>

    <h3 class="text-2xl font-bold text-gray-800 mb-6 border-b pb-2">Featured Fresh Produce</h3>
    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-8">
        <?php foreach ($featured_products as $product): ?>
        <div class="bg-white rounded-xl shadow-lg overflow-hidden border border-gray-100 flex flex-col">
            <img class="w-full h-48 object-cover" src="<?php echo htmlspecialchars($product['image_url']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" onerror="this.onerror=null; this.src='https://placehold.co/400x300/e5e7eb/374151?text=Image+Unavailable';" >
            <div class="p-6 flex-grow flex flex-col">
                <h4 class="text-xl font-semibold text-gray-800 mb-2"><?php echo htmlspecialchars($product['name']); ?></h4>
                <p class="text-sm text-gray-500 mb-4 flex-grow"><?php echo htmlspecialchars(substr($product['description'], 0, 100)) . '...'; ?></p>
                <div class="text-center mt-4">
                    <a href="products.php" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-green-600 hover:bg-green-700 transition duration-150">
                        View Details
                    </a>
                </div>
            </div>
        </div>
        <?php endforeach; ?>

        <?php if (empty($featured_products)): ?>
            <p class="col-span-3 text-center text-gray-500">No featured products currently available.</p>
        <?php endif; ?>
    </div>
</div>

<?php
require_once 'includes/footer.php';
close_db_connection($conn);
?>