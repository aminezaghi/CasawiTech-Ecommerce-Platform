<?php
session_start();
$isLoggedIn = isset($_SESSION['user_id']);
$isAdmin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';

require_once 'config.php';
$conn = getPDO();

$product_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Fetch product details
$stmt = $conn->prepare("SELECT * FROM produit WHERE id = :id");
$stmt->execute(['id' => $product_id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    header("Location: products.php");
    exit();
}

// Fetch similar products
$stmt = $conn->prepare("SELECT id, name, prix, old_price, lien, stock_quantity FROM produit WHERE category = :category AND id != :id ORDER BY RAND() LIMIT 4");
$stmt->execute(['category' => $product['category'], 'id' => $product_id]);
$similar_products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch all categories
$stmt = $conn->query("SELECT DISTINCT category FROM produit ORDER BY category");
$categories = $stmt->fetchAll(PDO::FETCH_COLUMN);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($product['name']) ?> - CasawiTech</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/ScrollTrigger.min.js"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: {
                            bg: '#ffffff',
                            text: '#111111',
                            accent: '#ff0033',
                            light: '#f5f5f5',
                            gray: '#888888'
                        }
                    },
                    fontFamily: {
                        sans: ['Inter', 'system-ui', 'sans-serif'],
                    }
                }
            }
        }
    </script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #fff8f3; color: #111111; }
        .product-image {
            opacity: 0;
            transform: scale(0.95);
            transition: transform 0.5s ease;
        }
        .product-image:hover {
            transform: scale(1.05);
        }
        .product-info {
            opacity: 0;
            transform: translateY(20px);
        }
        .spec-item {
            opacity: 0;
            transform: translateX(-20px);
            transition: all 0.3s ease;
        }
        .spec-item:hover {
            transform: translateX(5px);
            background-color: rgba(255, 0, 51, 0.05);
        }
        .quantity-input {
            transition: all 0.3s ease;
        }
        .quantity-input:focus {
            transform: scale(1.05);
        }
        .add-to-cart-button {
            position: relative;
            overflow: hidden;
        }
        .add-to-cart-button::after {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: 0.5s;
        }
        .add-to-cart-button:hover::after {
            left: 100%;
        }
        .similar-product {
            opacity: 1;
            transform: translateY(0);
            transition: all 0.5s ease;
            background: #fff !important;
            color: #111111 !important;
        }
        .similar-product:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
        }
        .similar-product .text-primary-text {
            color: #111111 !important;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <?php include 'navbar.php'; ?>

    <main class="container mx-auto px-4 py-12">
        <!-- Breadcrumb -->
        <div class="flex items-center space-x-2 mb-8 text-primary-gray">
            <a href="/" class="hover:text-primary-accent transition-colors duration-250">Home</a>
            <span>/</span>
            <a href="products.php" class="hover:text-primary-accent transition-colors duration-250">Products</a>
            <span>/</span>
            <a href="products.php?category=<?= urlencode($product['category']) ?>" 
               class="hover:text-primary-accent transition-colors duration-250">
                <?= htmlspecialchars($product['category']) ?>
            </a>
            <span>/</span>
            <span class="text-primary-text"><?= htmlspecialchars($product['name']) ?></span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-12">
            <!-- Product Image -->
            <div class="relative group">
                <div class="aspect-square rounded-lg overflow-hidden bg-primary-light">
                    <img src="<?= htmlspecialchars($product['lien']) ?>" 
                         alt="<?= htmlspecialchars($product['name']) ?>" 
                         class="w-full h-full object-cover product-image">
                </div>
            </div>

            <!-- Product Details -->
            <div class="space-y-6 product-info">
                <div>
                    <h1 class="text-3xl font-bold mb-2 text-primary-text"><?= htmlspecialchars($product['name']) ?></h1>
                    <p class="text-primary-gray"><?= htmlspecialchars($product['category']) ?></p>
                </div>

                <div class="flex items-center space-x-4">
                    <span class="text-3xl font-bold text-primary-accent"><?= number_format($product['prix'], 2) ?> DHs</span>
                    <?php if (!empty($product['old_price']) && $product['old_price'] > $product['prix']): ?>
                        <span class="text-lg text-primary-gray line-through"><?= number_format($product['old_price'], 2) ?> DHs</span>
                        <span class="bg-primary-accent/10 text-primary-accent px-3 py-1 rounded-full text-sm">
                            <?= round((($product['old_price'] - $product['prix']) / $product['old_price']) * 100) ?>% OFF
                        </span>
                    <?php endif; ?>
                </div>

                <div class="border-t border-primary-light pt-6">
                    <h2 class="text-lg font-semibold mb-4 text-primary-text">
                        Product Description
                    </h2>
                    <p class="text-primary-gray leading-relaxed">
                        <?= nl2br(htmlspecialchars($product['description'])) ?>
                    </p>
                </div>

                <div class="border-t border-primary-light pt-6">
                    <h2 class="text-lg font-semibold mb-4 text-primary-text">
                        Product Details
                    </h2>
                    <div class="grid grid-cols-2 gap-4">
                        <div class="spec-item p-4 rounded-lg bg-primary-light/50">
                            <p class="text-sm text-primary-gray mb-1">Category</p>
                            <p class="font-medium text-primary-text"><?= htmlspecialchars($product['category']) ?></p>
                        </div>
                        <div class="spec-item p-4 rounded-lg bg-primary-light/50">
                            <p class="text-sm text-primary-gray mb-1">Stock</p>
                            <p class="font-medium text-primary-text">
                                <?= $product['stock_quantity'] > 0 ? $product['stock_quantity'] . ' units' : 'Out of stock' ?>
                            </p>
                        </div>
                    </div>
                </div>

                <?php if ($product['stock_quantity'] > 0): ?>
                    <div class="border-t border-primary-light pt-6">
                        <form action="cart.php" method="POST" class="space-y-4">
                            <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                            
                            <div>
                                <label for="quantity" class="block text-sm font-medium text-primary-gray mb-2">
                                    Quantity
                                </label>
                                <div class="flex items-center space-x-4">
                                    <select name="quantity" id="quantity" 
                                            class="quantity-input bg-primary-light border border-primary-gray/20 text-primary-text rounded px-4 py-2 focus:border-primary-accent focus:ring-1 focus:ring-primary-accent outline-none">
                                        <?php for ($i = 1; $i <= min(10, $product['stock_quantity']); $i++): ?>
                                            <option value="<?= $i ?>"><?= $i ?></option>
                                        <?php endfor; ?>
                                    </select>
                                    <span class="text-primary-gray"><?= $product['stock_quantity'] ?> units available</span>
                                </div>
                            </div>

                            <button type="submit" name="add_to_cart" 
                                    class="add-to-cart-button w-full bg-primary-accent hover:bg-opacity-90 text-white py-3 px-8 rounded transition-colors duration-250 flex items-center justify-center space-x-2">
                                <i class="fas fa-cart-plus"></i>
                                <span>Add to Cart</span>
                            </button>
                        </form>
                    </div>
                <?php else: ?>
                    <div class="border-t border-primary-light pt-6">
                        <p class="text-primary-accent bg-primary-accent/10 px-4 py-3 rounded">
                            This product is currently out of stock.
                        </p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Similar Products -->
        <?php if (!empty($similar_products)): ?>
            <div class="mt-24">
                <h2 class="text-2xl font-bold text-primary-text mb-8">Similar Products</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                    <?php foreach ($similar_products as $similar): ?>
                        <a href="product_details.php?id=<?= $similar['id'] ?>" 
                           class="similar-product bg-primary-bg rounded-lg overflow-hidden shadow-lg">
                            <div class="aspect-square overflow-hidden">
                                <img src="<?= htmlspecialchars($similar['lien']) ?>" 
                                     alt="<?= htmlspecialchars($similar['name']) ?>" 
                                     class="w-full h-full object-cover transition-transform duration-500 hover:scale-105">
                            </div>
                            <div class="p-4">
                                <h3 class="text-lg font-bold mb-2 text-primary-text line-clamp-2">
                                    <?= htmlspecialchars($similar['name']) ?>
                                </h3>
                                <div class="flex items-center justify-between">
                                    <span class="text-lg font-bold text-primary-accent">
                                        <?= number_format($similar['prix'], 2) ?> DHs
                                    </span>
                                    <?php if (!empty($similar['old_price'])): ?>
                                        <span class="text-sm text-primary-gray line-through">
                                            <?= number_format($similar['old_price'], 2) ?> DHs
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </main>

    <?php include 'footer.php'; ?>

    <script>
        // Initialize GSAP
        gsap.registerPlugin(ScrollTrigger);

        // Product image animation
        gsap.to('.product-image', {
            opacity: 1,
            scale: 1,
            duration: 1,
            ease: 'power3.out'
        });

        // Product info animation
        gsap.to('.product-info', {
            opacity: 1,
            y: 0,
            duration: 1,
            delay: 0.3,
            ease: 'power3.out'
        });

        // Spec items staggered animation
        gsap.to('.spec-item', {
            opacity: 1,
            x: 0,
            duration: 0.6,
            stagger: 0.2,
            ease: 'power3.out',
            delay: 0.6
        });

        // Remove GSAP animation for similar products and ensure always visible
        document.querySelectorAll('.similar-product').forEach(el => {
            el.style.opacity = '1';
            el.style.transform = 'none';
        });
    </script>
</body>
</html>