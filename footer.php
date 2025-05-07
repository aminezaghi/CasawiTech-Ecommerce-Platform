<?php
require_once 'config.php';
if (!isset($_SESSION)) session_start();
if (!isset($isLoggedIn)) $isLoggedIn = isset($_SESSION['user_id']);
if (!isset($isAdmin)) $isAdmin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
$conn = getPDO();

// Fetch categories for footer, but only if $conn is valid
if ($conn) {
    try {
        $categoryStmt = $conn->query("SELECT DISTINCT category FROM produit ORDER BY category");
        $categories = $categoryStmt->fetchAll(PDO::FETCH_COLUMN);
    } catch (Exception $e) {
        $categories = [];
    }
} else {
    $categories = [];
}
?>

<style>
footer {
    background-color: #fff8f3 !important;
}
</style>

<footer class="bg-primary-bg text-primary-text py-16">
    <div class="container mx-auto px-4">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-12">
            <!-- Company Info -->
            <div>
                <h3 class="text-xl font-bold text-primary-accent mb-6">CasawiTech</h3>
                <p class="text-primary-gray">Your ultimate destination for gaming excellence and tech innovation.</p>
                <div class="mt-6 flex space-x-4">
                    <a href="#" class="text-primary-gray hover:text-primary-accent transition-colors duration-250">
                        <i class="fab fa-facebook-f"></i>
                    </a>
                    <a href="#" class="text-primary-gray hover:text-primary-accent transition-colors duration-250">
                        <i class="fab fa-twitter"></i>
                    </a>
                    <a href="#" class="text-primary-gray hover:text-primary-accent transition-colors duration-250">
                        <i class="fab fa-instagram"></i>
                    </a>
                </div>
            </div>

            <!-- Categories -->
            <div>
                <h4 class="text-lg font-bold mb-6">Categories</h4>
                <ul class="space-y-4">
                    <?php foreach ($categories as $cat): ?>
                        <li>
                            <a href="products.php?category=<?= urlencode($cat) ?>" 
                               class="text-primary-gray hover:text-primary-accent transition-colors duration-250">
                                <?= htmlspecialchars($cat) ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <!-- Quick Links -->
            <div>
                <h4 class="text-lg font-bold mb-6">Quick Links</h4>
                <ul class="space-y-4">
                    <li>
                        <a href="about_us.php" class="text-primary-gray hover:text-primary-accent transition-colors duration-250">
                            About Us
                        </a>
                    </li>
                    <li>
                        <a href="contact.php" class="text-primary-gray hover:text-primary-accent transition-colors duration-250">
                            Contact
                        </a>
                    </li>
                    <li>
                        <a href="support.php" class="text-primary-gray hover:text-primary-accent transition-colors duration-250">
                            Support
                        </a>
                    </li>
                    <?php if ($isLoggedIn): ?>
                        <li>
                            <a href="track_order.php" class="text-primary-gray hover:text-primary-accent transition-colors duration-250">
                                Track Order
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>

            <!-- Contact Info -->
            <div>
                <h4 class="text-lg font-bold mb-6">Contact Us</h4>
                <ul class="space-y-4">
                    <li class="flex items-center text-primary-gray">
                        <i class="fas fa-map-marker-alt w-6"></i>
                        <span>123 Gaming Street, Casablanca, Morocco</span>
                    </li>
                    <li class="flex items-center text-primary-gray">
                        <i class="fas fa-phone w-6"></i>
                        <span>+212 123-456789</span>
                    </li>
                    <li class="flex items-center text-primary-gray">
                        <i class="fas fa-envelope w-6"></i>
                        <span>contact@casawitech.com</span>
                    </li>
                </ul>
            </div>
        </div>

        <!-- Bottom Bar -->
        <div class="mt-16 pt-8 border-t border-primary-light/20 text-center">
            <p class="text-primary-gray">&copy; <?= date('Y') ?> CasawiTech. All rights reserved.</p>
            <?php if (!$isLoggedIn): ?>
                <div class="mt-4">
                    <a href="login.php" class="text-primary-gray hover:text-primary-accent transition-colors duration-250 mr-4">
                        Sign In
                    </a>
                    <a href="register.php" class="text-primary-gray hover:text-primary-accent transition-colors duration-250">
                        Create Account
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</footer> 