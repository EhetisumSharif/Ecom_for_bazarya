<?php
// includes/header.php - Reusable Header and Navigation

// Note: Requires includes/functions.php to be loaded first
// NOTE: I am assuming the following functions exist: is_b2b_logged_in(), get_cart_item_count()
$is_logged_in = is_b2b_logged_in();
$current_page = basename($_SERVER['PHP_SELF']);

// Retrieve the cart item count using the function from functions.php
$cart_item_count = $is_logged_in ? get_cart_item_count() : 0; 

$page_title = $is_logged_in ? "B2B Dashboard" : "Fresh Produce Catalog";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bazarya Trading AFZ | <?php echo htmlspecialchars($page_title); ?></title>
	<link rel="icon" type="image/jpeg" href="logo.jpeg">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@100..900&display=swap');
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">

<nav class="bg-white shadow-md sticky top-0 z-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex">
                <div class="flex-shrink-0 flex items-center">
                    <a href="index.php" class="text-xl font-semibold sm:text-2xl sm:font-bold text-green-700">
                        Bazarya Trading AFZ
                    </a>
                </div>
                <div class="hidden sm:ml-6 sm:flex sm:space-x-8">
                    <?php
                        function nav_link_classes($page, $current_page) {
                            return $page == $current_page
                                ? 'border-green-500 text-gray-900 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium transition duration-150'
                                : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium transition duration-150';
                        }
                    ?>
                    <a href="index.php" class="<?php echo nav_link_classes('index.php', $current_page); ?>">
                        Home
                    </a>
                    <a href="products.php" class="<?php echo nav_link_classes('products.php', $current_page); ?>">
                        Product Catalog
                    </a>
                    <a href="about.php" class="<?php echo nav_link_classes('about.php', $current_page); ?>">
                        About
                    </a>
                    <a href="contact.php" class="<?php echo nav_link_classes('contact.php', $current_page); ?>">
                        Contact
                    </a>
                </div>
            </div>
            
            <div class="flex items-center">
                <?php if ($is_logged_in): ?>
                    <a href="b2b_dashboard.php" class="hidden sm:block text-sm font-medium text-indigo-600 hover:text-indigo-800 mr-4">
                        <svg class="w-5 h-5 inline-block mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 8v8m-4-4h4m-4 0v4m-4 4h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path></svg>
                        <span class="hidden md:inline">B2B Dashboard</span>
                    </a>
                    
                    <a href="cart.php" class="flex items-center text-sm font-medium text-gray-700 hover:text-gray-900 mr-4">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                        <span class="ml-1 sm:ml-2 <?php echo $is_logged_in ? 'text-gray-900 font-semibold' : ''; ?>">
                            <span class="hidden sm:inline">Cart </span>
                            (<span id="cart-count-display"><?php echo $cart_item_count; ?></span>)
                        </span>
                    </a>
                    
                    <a href="logout.php" class="ml-3 hidden sm:inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-white bg-red-600 hover:bg-red-700 transition duration-150">
                        Logout
                    </a>
                <?php else: ?>
                    <a href="b2b_login.php" class="ml-3 inline-flex items-center px-2 py-1 text-xs sm:px-3 sm:py-2 sm:text-sm border border-transparent leading-4 font-medium rounded-md text-white bg-green-600 hover:bg-green-700 transition duration-150">
						B2B Login
					</a>
                <?php endif; ?>

                <div class="-mr-2 flex items-center sm:hidden">
                    <button type="button" id="mobile-menu-button" class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-green-500" aria-controls="mobile-menu" aria-expanded="false">
                        <span class="sr-only">Open main menu</span>
                        
                        <svg class="block h-6 w-6" id="menu-icon-closed" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                        
                        <svg class="hidden h-6 w-6" id="menu-icon-open" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="sm:hidden hidden" id="mobile-menu">
        <div class="pt-2 pb-3 space-y-1">
            <a href="index.php" class="block pl-3 pr-4 py-2 border-l-4 <?php echo $current_page == 'index.php' ? 'border-green-500 bg-green-50 text-green-700' : 'border-transparent text-gray-600 hover:bg-gray-50 hover:border-gray-300 hover:text-gray-800'; ?> text-base font-medium">Home</a>
            <a href="products.php" class="block pl-3 pr-4 py-2 border-l-4 <?php echo $current_page == 'products.php' ? 'border-green-500 bg-green-50 text-green-700' : 'border-transparent text-gray-600 hover:bg-gray-50 hover:border-gray-300 hover:text-gray-800'; ?> text-base font-medium">Product Catalog</a>
            <a href="about.php" class="block pl-3 pr-4 py-2 border-l-4 border-transparent text-gray-600 hover:bg-gray-50 hover:border-gray-300 hover:text-gray-800 text-base font-medium">About</a>
            <a href="contact.php" class="block pl-3 pr-4 py-2 border-l-4 border-transparent text-gray-600 hover:bg-gray-50 hover:border-gray-300 hover:text-gray-800 text-base font-medium">Contact</a>

            <?php if ($is_logged_in): ?>
                <a href="b2b_dashboard.php" class="block pl-3 pr-4 py-2 border-l-4 border-transparent text-indigo-600 hover:bg-gray-50 hover:border-gray-300 hover:text-indigo-800 text-base font-medium mt-1">
                    <svg class="w-5 h-5 inline-block mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 8v8m-4-4h4m-4 0v4m-4 4h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path></svg>
                    B2B Dashboard
                </a>
                
                <a href="logout.php" class="mt-2 block w-full px-4 py-2 text-center border-l-4 border-transparent text-sm font-medium rounded-md text-white bg-red-600 hover:bg-red-700 transition duration-150">
                    Logout
                </a>
            <?php endif; ?>
        </div>
    </div>
</nav>

<main class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">