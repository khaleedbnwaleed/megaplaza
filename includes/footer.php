<?php
/**
 * Common Footer
 * Mega School Plaza Management System
 */
?>
    <!-- JavaScript Files -->
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <script src="<?php echo BASE_URL; ?>assets/js/main.js"></script>
    
    <?php if (isset($additional_js)): ?>
        <?php foreach ($additional_js as $js_file): ?>
            <script src="<?php echo BASE_URL . $js_file; ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>
</body>
</html>
