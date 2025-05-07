<?php
require_once 'config.php';
if (!isset($_SESSION)) session_start();
if (!isset($isAdmin)) $isAdmin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';

// Redirect if not admin
if (!$isAdmin) {
    header("Location: login.php");
    exit;
}

// Get current page for active state
$current_page = basename($_SERVER['PHP_SELF']);
?>

<nav class="bg-primary-bg border-b border-primary-light sticky top-0 z-50">
    <div class="container mx-auto px-4 py-4">
        <div class="flex items-center justify-between">
            <!-- Logo and Brand -->
            <div class="flex items-center">
                <a href="/" class="text-3xl font-bold text-primary-accent mr-8">
                    CasawiTech
                </a>
                <span class="text-primary-gray">Admin Panel</span>
            </div>

            <!-- Admin Navigation Links -->
            <div class="flex items-center space-x-8">
                <a href="admin_dashboard.php" 
                   class="flex items-center <?= $current_page === 'admin_dashboard.php' ? 'text-primary-accent' : 'text-primary-gray' ?> hover:text-primary-accent transition-colors duration-250">
                    <i class="fas fa-chart-line mr-2"></i>
                    Dashboard
                </a>
                <a href="admin_products.php" 
                   class="flex items-center <?= $current_page === 'admin_products.php' ? 'text-primary-accent' : 'text-primary-gray' ?> hover:text-primary-accent transition-colors duration-250">
                    <i class="fas fa-box mr-2"></i>
                    Products
                </a>
                <a href="admin_orders.php" 
                   class="flex items-center <?= $current_page === 'admin_orders.php' ? 'text-primary-accent' : 'text-primary-gray' ?> hover:text-primary-accent transition-colors duration-250">
                    <i class="fas fa-shopping-cart mr-2"></i>
                    Orders
                </a>
                <a href="admin_customers.php" 
                   class="flex items-center <?= $current_page === 'admin_customers.php' ? 'text-primary-accent' : 'text-primary-gray' ?> hover:text-primary-accent transition-colors duration-250">
                    <i class="fas fa-users mr-2"></i>
                    Customers
                </a>
            </div>

            <!-- Right Side Actions -->
            <div class="flex items-center space-x-6">
                <a href="products.php" class="text-primary-gray hover:text-primary-accent transition-colors duration-250">
                    <i class="fas fa-store mr-2"></i>
                    View Store
                </a>
                <a href="logout.php" class="text-primary-gray hover:text-primary-accent transition-colors duration-250">
                    <i class="fas fa-sign-out-alt mr-2"></i>
                    Logout
                </a>
            </div>
        </div>
    </div>
</nav> 