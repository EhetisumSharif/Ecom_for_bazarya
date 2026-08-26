<?php
require_once '../includes/functions.php';
require_once '../includes/db.php';

// --- Admin Authentication Check (Requirement H) ---
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    redirect('login.php');
}

$message = '';
$error = '';

// --- Admin Navigation Header/Footer with Responsive Toggle (Updated) ---
function render_admin_header($page_title) {
    $current_page = basename($_SERVER['PHP_SELF']);
    
    // Define base classes for all links
    $base_classes = "block px-4 py-2 text-sm font-medium rounded-lg transition duration-150";
    
    // Define active and inactive class groups
    $active_classes = "bg-red-700 text-white";
    $inactive_classes = "text-red-100 hover:bg-red-600";
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0, shrink-to-fit=no">
		<link rel="icon" href="../logo.jpeg" type="image/jpeg">
        <link rel="apple-touch-icon" href="../logo.jpeg">
        <title>Admin | <?php echo htmlspecialchars($page_title); ?></title>
        <script src="https://cdn.tailwindcss.com"></script>
        <style>
            @import url('https://fonts.googleapis.com/css2?family=Inter:wght@100..900&display=swap');
            body { font-family: 'Inter', sans-serif; background-color: #f3f4f6; overflow-x: hidden; }
            .clear-both { clear: both; }
        </style>
    </head>
    <body>
    <div class="flex min-h-screen">
        
        <!-- Responsive Sidebar -->
        <aside id="adminSidebar" class="w-64 bg-red-800 text-white p-6 fixed z-30 top-0 left-0 h-screen transition-transform duration-300 transform -translate-x-full md:relative md:translate-x-0 md:block">
            <h1 class="text-2xl font-bold mb-8 border-b border-red-700 pb-4">B2B Admin</h1>
            <nav class="space-y-2">
                <a href="index.php" class="<?php echo $base_classes; ?> <?php echo $current_page == 'index.php' ? $active_classes : $inactive_classes; ?>">
                    Dashboard
                </a>
                <a href="products_crud.php" class="<?php echo $base_classes; ?> <?php echo $current_page == 'products_crud.php' ? $active_classes : $inactive_classes; ?>">
                    Product Management
                </a>
                <a href="orders.php" class="<?php echo $base_classes; ?> <?php echo $current_page == 'orders.php' ? $active_classes : $inactive_classes; ?>">
                    Order Management
                </a>
                <a href="users.php" class="<?php echo $base_classes; ?> <?php echo $current_page == 'users.php' ? $active_classes : $inactive_classes; ?>">
                    B2B User Management
                </a>
                <a href="settings.php" class="<?php echo $base_classes; ?> <?php echo $current_page == 'settings.php' ? $active_classes : $inactive_classes; ?>">
                    Settings & Backups
                </a>
            </nav>
            <div class="mt-8 pt-4 border-t border-red-700">
			    <a href="logout.php" class="block px-4 py-2 text-sm font-medium rounded-lg transition duration-150 bg-red-600 hover:bg-red-500 text-white">
                    Logout
                </a>
            </div>
        </aside>
        
        <div id="sidebarOverlay" class="fixed inset-0 bg-black opacity-0 z-20 transition-opacity duration-300 pointer-events-none md:hidden"></div>

        <main class="flex-grow p-4 md:p-8">
            
            <!-- Mobile Toggle Button -->
            <button id="sidebarToggle" class="mb-4 p-2 bg-red-800 text-white rounded-lg shadow-lg md:hidden float-left mr-3">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
            </button>
            
            <h1 class="text-3xl font-bold text-gray-800 mb-6 block md:inline-block">
                <?php echo htmlspecialchars($page_title); ?>
            </h1>
            <div class="clear-both md:hidden"></div> 
    <?php
}

function render_admin_footer() {
    ?>
        <footer class="mt-8 pt-4 border-t border-gray-300 text-center text-sm text-gray-500">
            <p>&copy; <?php echo date("Y"); ?> Bazarya Trading AFZ. All rights reserved.</p>
            <p>Powerd by: Aelyth IT solution</p>
        </footer>
        </main>
    </div>

    <script>
        // --- Sidebar Toggle Logic (Copied from orders.php) ---
        const sidebar = document.getElementById('adminSidebar');
        const toggleButton = document.getElementById('sidebarToggle');
        const overlay = document.getElementById('sidebarOverlay');

        function toggleSidebar() {
            const isHidden = sidebar.classList.contains('-translate-x-full');

            if (isHidden) {
                // Show sidebar
                sidebar.classList.remove('-translate-x-full');
                sidebar.classList.add('translate-x-0');
                overlay.classList.remove('opacity-0', 'pointer-events-none');
                overlay.classList.add('opacity-50');
            } else {
                // Hide sidebar
                sidebar.classList.remove('translate-x-0');
                sidebar.classList.add('-translate-x-full');
                overlay.classList.remove('opacity-50');
                overlay.classList.add('opacity-0', 'pointer-events-none');
            }
        }

        toggleButton.addEventListener('click', toggleSidebar);
        overlay.addEventListener('click', toggleSidebar); // Close when clicking outside

        // Optionally, close sidebar on link click (important for mobile UX)
        sidebar.querySelectorAll('a').forEach(link => {
            link.addEventListener('click', () => {
                // Check if on a mobile screen (Tailwind's md breakpoint is 768px)
                if (window.innerWidth < 768) { 
                    toggleSidebar();
                }
            });
        });
        
        // --- End Sidebar Toggle Logic ---
        
        // The rest of the product CRUD script follows...
        const productModal = document.getElementById('productModal');
        const modalTitle = document.getElementById('modalTitle');
        const productForm = document.getElementById('productForm');
        const modalAction = document.getElementById('modalAction');
        const modalProductId = document.getElementById('modalProductId');
        const existingImageUrl = document.getElementById('existing_image_url');
        const imagePreviewDiv = document.getElementById('image_preview');
        const previewImgTag = document.getElementById('preview_img_tag');


        // --- Modal Control ---
        function openModal(actionType) {
            if (actionType === 'addModal') {
                modalTitle.textContent = 'Add New Product';
                modalAction.value = 'add';
                productForm.reset();
                document.getElementById('is_visible').checked = true; // Default to visible
                modalProductId.value = '';
                
                // Clear default values for new fields when adding
                document.getElementById('origin').value = 'N/A';
                document.getElementById('unit').value = 'kg';
                document.getElementById('weight_kg').value = '1.00';
                
                // Clear image fields
                document.getElementById('image_file').value = '';
                existingImageUrl.value = 'https://placehold.co/150x150/e5e7eb/374151?text=No+Image'; // Set default placeholder
                imagePreviewDiv.style.display = 'none';

            }
            // Note: 'editModal' logic is handled by editProduct()
            productModal.classList.remove('hidden');
        }

        function closeModal() {
            productModal.classList.add('hidden');
        }
        
        // Close modal on outside click
        productModal.addEventListener('click', (e) => {
            if (e.target === productModal) {
                closeModal();
            }
        });

        // --- Edit Functionality (UPDATED for File Upload) ---
        function editProduct(product) {
            modalTitle.textContent = `Edit Product: ${product.name}`;
            modalAction.value = 'edit';
            modalProductId.value = product.id;
            
            // Populate form fields
            document.getElementById('name').value = product.name;
            document.getElementById('sku').value = product.sku;
            document.getElementById('price').value = product.price;
            document.getElementById('stock').value = product.stock;
            document.getElementById('moq').value = product.moq;
            document.getElementById('category_id').value = product.category_id;
            document.getElementById('description').value = product.description;
            document.getElementById('features').value = product.features;
            document.getElementById('is_visible').checked = product.is_visible == 1;
            
            // NEW FIELD POPULATION
            document.getElementById('origin').value = product.origin;
            document.getElementById('unit').value = product.unit;
            document.getElementById('weight_kg').value = product.weight_kg;
            
            // IMAGE FILE HANDLING
            const currentImageUrl = product.image_url;
            existingImageUrl.value = currentImageUrl; // Store current URL for PHP to use if no new file is uploaded
            document.getElementById('image_file').value = ''; // Clear file input field

            if (currentImageUrl && currentImageUrl !== 'https://placehold.co/150x150/e5e7eb/374151?text=No+Image') {
                // Show the image preview (path is relative to the web root, so we prepend '../' to access it from the 'admin' folder)
                imagePreviewDiv.style.display = 'block';
                previewImgTag.src = '../' + currentImageUrl; 
            } else {
                imagePreviewDiv.style.display = 'none';
                previewImgTag.src = '';
            }
            
            openModal('editModal');
        }

        // --- Delete Functionality ---
        function confirmDelete(productId) {
            if (confirm(`Are you sure you want to permanently delete Product ID ${productId}? This action cannot be undone.`)) {
                document.getElementById('deleteProductId').value = productId;
                document.getElementById('deleteForm').submit();
            }
        }
    </script>
    
    </body>
    </html>
    <?php
}
// --- End Admin Header/Footer ---

// --- Data Fetching ---
$products = [];
$categories = [];

// Fetch all categories for the dropdowns
$cat_result = $conn->query("SELECT id, name FROM categories ORDER BY name");
if ($cat_result) {
    while($row = $cat_result->fetch_assoc()) {
        $categories[] = $row;
    }
}

// Fetch all products (including disabled ones for Admin view)
$prod_sql = "
    SELECT 
        p.id, p.name, p.sku, p.price, p.stock, p.moq, p.is_visible, p.origin, p.unit, p.weight_kg, p.image_url, p.description, p.features, c.name AS category_name, c.id AS category_id
    FROM products p
    JOIN categories c ON p.category_id = c.id
    ORDER BY p.id DESC
";
$prod_result = $conn->query($prod_sql);
if ($prod_result) {
    while($row = $prod_result->fetch_assoc()) {
        $products[] = $row;
    }
}

// --- CRUD Operations (Requirement F.1) ---

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    // Sanitize common fields
    $id = (int)($_POST['product_id'] ?? 0);
    $name = sanitize_input($_POST['name'] ?? '');
    $sku = sanitize_input($_POST['sku'] ?? '');
    $category_id = (int)($_POST['category_id'] ?? 0);
    $description = sanitize_input($_POST['description'] ?? '');
    $features = sanitize_input($_POST['features'] ?? '');
    
    // Use existing image URL as default if no new file is uploaded
    $image_url = sanitize_input($_POST['existing_image_url'] ?? 'https://placehold.co/150x150/e5e7eb/374151?text=No+Image');
    
    $price = (float)($_POST['price'] ?? 0.00);
    $stock = (int)($_POST['stock'] ?? 0);
    $moq = (int)($_POST['moq'] ?? 1);
    $is_visible = isset($_POST['is_visible']) ? 1 : 0;
    
    // NEW FIELDS TO SANITIZE
    $origin = sanitize_input($_POST['origin'] ?? 'N/A');
    $unit = sanitize_input($_POST['unit'] ?? 'unit'); // Default to 'unit' instead of 'kg'
    $weight_kg = (float)($_POST['weight_kg'] ?? 1.00);

    // --- Image Upload Logic (NEW) ---
    // Check if a file was uploaded and there were no errors
    if (isset($_FILES['image_file']) && $_FILES['image_file']['error'] === UPLOAD_ERR_OK) {
        $file_tmp_name = $_FILES['image_file']['tmp_name'];
        $file_name = basename($_FILES['image_file']['name']);
        
        // Generate a unique filename to prevent overwriting
        $file_ext = pathinfo($file_name, PATHINFO_EXTENSION);
        $new_file_name = uniqid('prod_', true) . '.' . strtolower($file_ext);

        // Define the target directory and path. Directory is relative to products_crud.php
        $upload_dir = '../uploads/images/'; 
        $target_file = $upload_dir . $new_file_name;

        // Ensure upload directory exists
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        // Move the uploaded file
        if (move_uploaded_file($file_tmp_name, $target_file)) {
            // On successful upload, update $image_url to the new path (relative to web root)
            $image_url = 'uploads/images/' . $new_file_name; 
        } else {
            $error = "Error uploading file. Check folder permissions and size limits.";
        }
    }
    // --- End Image Upload Logic ---
    
    // Basic validation
    if (empty($name) || empty($sku) || $price <= 0 || $category_id <= 0) {
        $error = "Product name, SKU, price, and category are required.";
    } else if (!empty($error)) {
        // Error already set from file upload, stop DB operation
    } else {
        
        // --- 1. Add/Edit Product ---
        if ($action === 'add' || $action === 'edit') {
            if ($action === 'add') {
                // UPDATED: image_url is now the file path
                $sql = "INSERT INTO products (name, sku, category_id, description, features, image_url, price, stock, moq, is_visible, origin, unit, weight_kg) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ssisssddiissd", $name, $sku, $category_id, $description, $features, $image_url, $price, $stock, $moq, $is_visible, $origin, $unit, $weight_kg);
            } else { // 'edit'
                // UPDATED: image_url is now the file path
                $sql = "UPDATE products SET name=?, sku=?, category_id=?, description=?, features=?, image_url=?, price=?, stock=?, moq=?, is_visible=?, origin=?, unit=?, weight_kg=? WHERE id=?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ssisssddiissdi", $name, $sku, $category_id, $description, $features, $image_url, $price, $stock, $moq, $is_visible, $origin, $unit, $weight_kg, $id);
            }

            if ($stmt->execute()) {
                $message = "Product " . ($action === 'add' ? 'added' : 'updated') . " successfully.";
            } else {
                $error = "Error saving product: " . $conn->error;
            }
            $stmt->close();
            
            // Redirect to refresh page and clear POST data
            redirect('products_crud.php');
        }
        
        // --- 2. Delete Product ---
        if ($action === 'delete' && $id > 0) {
            // Optional: Add logic here to delete the physical image file from the server

            $sql = "DELETE FROM products WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $id);

            if ($stmt->execute()) {
                $message = "Product ID {$id} deleted successfully.";
            } else {
                $error = "Error deleting product: " . $conn->error;
            }
            $stmt->close();
            
            // Redirect to refresh page
            redirect('products_crud.php');
        }
    }
}


render_admin_header('Product Management'); 
?>

<?php if ($message): ?>
    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
        <p class="text-sm"><?php echo htmlspecialchars($message); ?></p>
    </div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
        <p class="text-sm"><?php echo htmlspecialchars($error); ?></p>
    </div>
<?php endif; ?>

<div class="bg-white p-4 md:p-6 rounded-xl shadow-lg mb-8 border border-gray-100">
    <h2 class="text-2xl font-bold text-gray-800 mb-4 border-b pb-2 flex justify-between items-center">
        All Products 
        <button onclick="openModal('addModal')" class="bg-red-600 hover:bg-red-700 text-white font-semibold py-2 px-4 rounded-lg shadow-md transition duration-150 text-sm whitespace-nowrap">
            + Add New Product
        </button>
    </h2>

    <!-- Desktop Table View -->
    <div class="overflow-x-auto hidden md:block">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
                    <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name (SKU)</th>
                    <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase">Category / Origin</th>
                    <th class="px-3 py-3 text-right text-xs font-medium text-gray-500 uppercase">Price</th>
                    <th class="px-3 py-3 text-center text-xs font-medium text-gray-500 uppercase">Stock</th>
                    <th class="px-3 py-3 text-center text-xs font-medium text-gray-500 uppercase">MOQ / Unit / Weight</th>
                    <th class="px-3 py-3 text-center text-xs font-medium text-gray-500 uppercase">Visible</th>
                    <th class="px-3 py-3 text-center text-xs font-medium text-gray-500 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php if (!empty($products)): ?>
                    <?php foreach ($products as $product): ?>
                    <tr class="<?php echo $product['is_visible'] ? '' : 'bg-red-50 text-gray-500'; ?>">
                        <td class="px-3 py-2 font-medium text-gray-900"><?php echo $product['id']; ?></td>
                        <td class="px-3 py-2">
                            <?php echo htmlspecialchars($product['name']); ?><br>
                            <span class="text-xs text-gray-500 font-mono"><?php echo htmlspecialchars($product['sku']); ?></span>
                        </td>
                        <td class="px-3 py-2">
                            <?php echo htmlspecialchars($product['category_name']); ?><br>
                            <span class="text-xs text-gray-500">Origin: <?php echo htmlspecialchars($product['origin']); ?></span>
                        </td>
                        <td class="px-3 py-2 text-right font-bold text-red-600"><?php echo format_aed($product['price']); ?></td>
                        <td class="px-3 py-2 text-center"><?php echo $product['stock']; ?></td>
                        <td class="px-3 py-2 text-center text-xs">
                            MOQ: <?php echo $product['moq']; ?><br>
                            Unit: <?php echo htmlspecialchars($product['unit']); ?><br>
                            Weight: <?php echo $product['weight_kg']; ?> kg
                        </td>
                        <td class="px-3 py-2 text-center">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                <?php echo $product['is_visible'] ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'; ?>">
                                <?php echo $product['is_visible'] ? 'Yes' : 'No'; ?>
                            </span>
                        </td>
                        <td class="px-3 py-2 text-center whitespace-nowrap">
                            <button onclick="editProduct(<?php echo htmlspecialchars(json_encode($product)); ?>)" class="text-indigo-600 hover:text-indigo-900 text-sm font-medium mr-2">Edit</button>
                            <button onclick="confirmDelete(<?php echo $product['id']; ?>)" class="text-red-600 hover:text-red-900 text-sm font-medium">Delete</button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="8" class="text-center py-4 text-gray-500">No products found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    
    <!-- Mobile Card View (New, adapted from orders.php) -->
    <div class="md:hidden space-y-4">
        <?php if (!empty($products)): ?>
            <?php foreach ($products as $product): ?>
                <div class="bg-gray-50 p-4 rounded-lg shadow-sm border border-gray-200">
                    
                    <!-- Product Header: Name/SKU and Visible Badge -->
                    <div class="flex justify-between items-start mb-3 border-b pb-3">
                        <div>
                            <p class="text-xs text-gray-500 font-medium">Product ID: #<?php echo $product['id']; ?></p>
                            <p class="font-bold text-gray-900"><?php echo htmlspecialchars($product['name']); ?></p>
                            <p class="text-sm text-gray-500 font-mono">SKU: <?php echo htmlspecialchars($product['sku']); ?></p>
                        </div>
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium 
                            <?php echo $product['is_visible'] ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'; ?>">
                            <?php echo $product['is_visible'] ? 'Visible' : 'Hidden'; ?>
                        </span>
                    </div>

                    <!-- Details Grid -->
                    <div class="grid grid-cols-2 gap-3 mb-4">
                        <div>
                            <p class="text-xs text-gray-500 font-medium">Price (AED)</p>
                            <p class="text-lg font-bold text-red-600"><?php echo format_aed($product['price']); ?></p>
                        </div>
                        <div class="text-right">
                            <p class="text-xs text-gray-500 font-medium">Stock / MOQ</p>
                            <p class="font-medium text-gray-900"><?php echo $product['stock']; ?> in stock</p>
                            <p class="text-sm text-gray-500">MOQ: <?php echo $product['moq']; ?></p>
                        </div>
                        <div class="col-span-1">
                            <p class="text-xs text-gray-500 font-medium">Category</p>
                            <p class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($product['category_name']); ?></p>
                        </div>
                        <div class="col-span-1 text-right">
                            <p class="text-xs text-gray-500 font-medium">Origin / Unit</p>
                            <p class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($product['origin']); ?></p>
                            <p class="text-sm text-gray-500"><?php echo $product['weight_kg']; ?> kg / <?php echo htmlspecialchars($product['unit']); ?></p>
                        </div>
                    </div>
                    
                    <!-- Actions -->
                    <div class="pt-3 border-t border-gray-200 flex space-x-2">
                        <button onclick="editProduct(<?php echo htmlspecialchars(json_encode($product)); ?>)" class="flex-1 bg-indigo-50 hover:bg-indigo-100 text-indigo-600 text-sm font-medium py-2 rounded-lg transition duration-150">
                            Edit Product
                        </button>
                        <button onclick="confirmDelete(<?php echo $product['id']; ?>)" class="flex-1 bg-red-50 hover:bg-red-100 text-red-600 text-sm font-medium py-2 rounded-lg transition duration-150">
                            Delete
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="text-center py-8 text-gray-500">
                No products found.
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Product Modal (remains the same) -->
<div id="productModal" class="fixed inset-0 bg-gray-600 bg-opacity-75 z-50 hidden overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-2xl p-6 relative">
            
            <h3 id="modalTitle" class="text-2xl font-bold text-gray-800 mb-4 border-b pb-2">Add New Product</h3>
            
            <form id="productForm" method="POST" action="products_crud.php" class="space-y-4" enctype="multipart/form-data">
                <input type="hidden" name="product_id" id="modalProductId">
                <input type="hidden" name="action" id="modalAction" value="add">
                <input type="hidden" name="existing_image_url" id="existing_image_url">
                
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700">Product Name</label>
                        <input type="text" name="name" id="name" required class="w-full mt-1 px-3 py-2 border border-gray-300 rounded-lg shadow-sm">
                    </div>
                    <div>
                        <label for="sku" class="block text-sm font-medium text-gray-700">SKU</label>
                        <input type="text" name="sku" id="sku" required class="w-full mt-1 px-3 py-2 border border-gray-300 rounded-lg shadow-sm">
                    </div>
                </div>

                <div class="grid grid-cols-3 gap-4">
                    <div>
                        <label for="price" class="block text-sm font-medium text-gray-700">Price (AED)</label>
                        <input type="number" step="0.01" name="price" id="price" required class="w-full mt-1 px-3 py-2 border border-gray-300 rounded-lg shadow-sm">
                    </div>
                    <div>
                        <label for="stock" class="block text-sm font-medium text-gray-700">Stock Quantity</label>
                        <input type="number" name="stock" id="stock" required class="w-full mt-1 px-3 py-2 border border-gray-300 rounded-lg shadow-sm">
                    </div>
                    <div>
                        <label for="moq" class="block text-sm font-medium text-gray-700">MOQ</label>
                        <input type="number" name="moq" id="moq" required class="w-full mt-1 px-3 py-2 border border-gray-300 rounded-lg shadow-sm">
                    </div>
                </div>
                
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <label for="origin" class="block text-sm font-medium text-gray-700">Origin Country</label>
                        <input type="text" name="origin" id="origin" value="N/A" required class="w-full mt-1 px-3 py-2 border border-gray-300 rounded-lg shadow-sm">
                    </div>
                    <div>
                        <label for="unit" class="block text-sm font-medium text-gray-700">Unit (e.g. kg, ton, box)</label>
                        <input type="text" name="unit" id="unit" value="kg" required class="w-full mt-1 px-3 py-2 border border-gray-300 rounded-lg shadow-sm">
                    </div>
                    <div>
                        <label for="weight_kg" class="block text-sm font-medium text-gray-700">Weight per Unit (kg)</label>
                        <input type="number" step="0.01" name="weight_kg" id="weight_kg" value="1.00" required class="w-full mt-1 px-3 py-2 border border-gray-300 rounded-lg shadow-sm">
                    </div>
                </div>
                <div>
                    <label for="category_id" class="block text-sm font-medium text-gray-700">Category</label>
                    <select name="category_id" id="category_id" required class="w-full mt-1 px-3 py-2 border border-gray-300 rounded-lg shadow-sm">
                        <option value="">Select Category</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label for="description" class="block text-sm font-medium text-gray-700">Description</label>
                    <textarea name="description" id="description" rows="3" class="w-full mt-1 px-3 py-2 border border-gray-300 rounded-lg shadow-sm"></textarea>
                </div>

                <div>
                    <label for="features" class="block text-sm font-medium text-gray-700">Features (Semicolon separated, e.g. Feature 1; Feature 2)</label>
                    <textarea name="features" id="features" rows="2" class="w-full mt-1 px-3 py-2 border border-gray-300 rounded-lg shadow-sm"></textarea>
                </div>

                <div>
                    <label for="image_file" class="block text-sm font-medium text-gray-700">Product Image (Upload File)</label>
                    <input type="file" name="image_file" id="image_file" accept="image/*" class="w-full mt-1 px-3 py-2 border border-gray-300 rounded-lg shadow-sm">
                    <p class="text-xs text-gray-500 mt-1">Select a new file to replace the current image. Leave empty to keep existing image.</p>
                    
                    <div id="image_preview" class="mt-2" style="display:none;">
                         <p class="text-xs font-medium text-gray-600 mb-1">Current Image:</p>
                         <img id="preview_img_tag" src="" alt="Current Product Image" class="w-24 h-24 object-cover border rounded-lg">
                    </div>
                </div>

                <div class="flex items-center pt-2">
                    <input id="is_visible" name="is_visible" type="checkbox" checked class="h-4 w-4 text-red-600 border-gray-300 rounded focus:ring-red-500">
                    <label for="is_visible" class="ml-2 block text-sm font-medium text-gray-700">
                        Enable Public/B2B Visibility (Requirement F.1)
                    </label>
                </div>

                <div class="pt-4 flex justify-end space-x-3">
                    <button type="button" onclick="closeModal()" class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-200 rounded-lg hover:bg-gray-300 transition duration-150">
                        Cancel
                    </button>
                    <button type="submit" id="modalSubmitButton" class="px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700 transition duration-150">
                        Save Product
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<form id="deleteForm" method="POST" action="products_crud.php" style="display: none;">
    <input type="hidden" name="action" value="delete">
    <input type="hidden" name="product_id" id="deleteProductId">
</form>

<?php 
close_db_connection($conn);
render_admin_footer(); 
?>