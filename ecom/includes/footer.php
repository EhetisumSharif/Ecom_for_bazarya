<?php
// includes/footer.php - Reusable Footer and closing tags
// Note: This file must be included at the very end of your main PHP files.
?>
</main>

<footer class="mt-12 bg-gray-100 border-t border-gray-300 py-6 ">
    <div class="container mx-auto px-4 flex flex-col justify-center items-center text-sm text-gray-600">
        
        <p>&copy; <?php echo date("Y"); ?> Bazarya Trading AFZ. All rights reserved. </p>
        <p> Powerd by: Aelyth IT solution</p>
        
    </div>
</footer>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const button = document.getElementById('mobile-menu-button');
        const menu = document.getElementById('mobile-menu');
        const menuIconClosed = document.getElementById('menu-icon-closed');
        const menuIconOpen = document.getElementById('menu-icon-open');
        
        if (button && menu) {
            button.addEventListener('click', function() {
                // 1. Toggle visibility of the menu using the 'hidden' class
                menu.classList.toggle('hidden');

                // 2. Toggle icons
                if (menuIconClosed && menuIconOpen) {
                    menuIconClosed.classList.toggle('hidden');
                    menuIconOpen.classList.toggle('hidden');
                }
                
                // 3. Update ARIA state
                const isExpanded = !menu.classList.contains('hidden');
                this.setAttribute('aria-expanded', isExpanded);
            });
        }
    });
</script>

</body>
</html>