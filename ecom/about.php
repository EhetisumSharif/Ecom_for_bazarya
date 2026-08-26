<?php
require_once 'includes/functions.php';
require_once 'includes/db.php';
require_once 'includes/header.php';
?>

<div class="pb-8 px-4 sm:px-6 lg:px-8">
    <div class="bg-white p-6 sm:p-8 rounded-xl shadow-2xl border border-gray-100">
        <h2 class="text-2xl sm:text-3xl font-bold text-gray-800 mb-6 border-b pb-2">About Bazarya General Trading AFZ</h2>

        <section class="mb-8">
            <h3 class="text-xl sm:text-2xl font-semibold text-green-700 mb-3">Our Background</h3>
            <p class="text-gray-700 leading-relaxed text-sm sm:text-base">
                Established in 2013, Bazarya General Trading AFZ is a leading importer, wholesaler, and distributor based in the Ras Al Khor Central Fruits & Vegetables Market, Dubai, UAE. With over 12 years of experience (as of 2025), we specialize in importing and distributing high-quality fresh produce and food products from 6+ countries across Asia and the Middle East, including India, Bangladesh, Pakistan, Vietnam, the Philippines, and Egypt. Our strong sales and collection team ensures quality, reliability, and timely delivery across the UAE market.
            </p>
        </section>

        <section class="mb-8">
            <h3 class="text-xl sm:text-2xl font-semibold text-green-700 mb-3">Our Core Services</h3>
            <p class="text-gray-700 leading-relaxed mb-4 text-sm sm:text-base">
                Our Focus: We bridge the gap between international farmers/exporters and UAE buyers, providing a seamless and trusted supply chain for fresh goods. Our roles include:
            </p>
            <ul class="list-disc list-inside text-gray-700 pl-4 space-y-2 text-sm sm:text-base">
                <li>Import & Export: Reliable sourcing and logistics management across Asia & the Middle East.</li>
                <li>Wholesale Distribution: Direct supply to UAE supermarkets, restaurants, and local markets.</li>
                <li>Commissioning Agent: Acting as trusted middlemen ensuring smooth trade and secure payments between partners.</li>
                <li>Market Supply Chain: Daily delivery and collection services directly from the Ras Al Khor Central Market.</li>
            </ul>
        </section>
        
        <div class="mt-8 flex justify-center">
            <img class="h-24 w-auto object-contain rounded-xl shadow-lg" 
                 src="logo.jpeg" 
                 alt="Bazarya General Trading Logo">
        </div>
    </div>
</div>

<?php
require_once 'includes/footer.php';
close_db_connection($conn);
?>