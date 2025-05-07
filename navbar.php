<?php
require_once 'config.php';
if (!isset($_SESSION)) session_start();
if (!isset($isLoggedIn)) $isLoggedIn = isset($_SESSION['user_id']);
if (!isset($isAdmin)) $isAdmin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
$conn = getPDO();

// Fetch categories for navigation
$menuStmt       = $conn->query("SELECT DISTINCT category FROM produit ORDER BY category");
$navCategories  = $menuStmt->fetchAll(PDO::FETCH_COLUMN);
?>

<style>
nav {
    background-color: #fff8f3 !important;
}
</style>

<nav class="bg-primary-bg border-b border-primary-light sticky top-0 z-50">
    <div class="container mx-auto px-4 py-4">
        <div class="flex items-center justify-between">
            <a href="/" class="text-3xl font-bold text-primary-accent">
                CasawiTech
            </a>
            <div class="hidden md:flex space-x-8">
                <?php foreach($navCategories as $cat): ?>
                    <a href="products.php?category=<?= urlencode($cat) ?>"
                        class="text-primary-text hover:text-primary-accent">
                        <?= htmlspecialchars($cat) ?>
                    </a>
                <?php endforeach; ?>
            </div>
            <div class="flex items-center space-x-6">
                <?php if ($isLoggedIn): ?>
                    <a href="<?= $isAdmin ? 'admin_dashboard.php' : 'user_dashboard.php' ?>" 
                       class="text-primary-gray hover:text-primary-accent transition-colors duration-250">
                        <i class="fas fa-user"></i>
                        <span class="ml-2">My Account</span>
                    </a>
                    <a href="logout.php" class="text-primary-gray hover:text-primary-accent transition-colors duration-250">
                        <i class="fas fa-sign-out-alt"></i>
                        <span class="ml-2">Logout</span>
                    </a>
                <?php else: ?>
                    <a href="login.php" class="text-primary-gray hover:text-primary-accent transition-colors duration-250">
                        <i class="fas fa-sign-in-alt"></i>
                        <span class="ml-2">Login</span>
                    </a>
                    <a href="register.php" class="text-primary-gray hover:text-primary-accent transition-colors duration-250">
                        <i class="fas fa-user-plus"></i>
                        <span class="ml-2">Register</span>
                    </a>
                <?php endif; ?>
                <a href="cart.php" class="text-primary-gray hover:text-primary-accent transition-colors duration-250">
                    <i class="fas fa-shopping-cart"></i>
                    <span class="ml-2">Cart</span>
                </a>
            </div>
        </div>
    </div>
</nav> 