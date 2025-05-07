<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

require_once 'config.php';
$conn = getPDO();

// Handle product deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_product'])) {
    $productId = $_POST['product_id'];
    try {
        $stmt = $conn->prepare("DELETE FROM produit WHERE id = ?");
        $stmt->execute([$productId]);
        $_SESSION['message'] = "Product deleted successfully.";
        header("Location: admin_products.php");
        exit;
    } catch(PDOException $e) {
        $_SESSION['error'] = "Failed to delete product: " . $e->getMessage();
    }
}

// Get search and filter parameters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$categoryFilter = isset($_GET['category']) ? trim($_GET['category']) : '';

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 20;
$offset = ($page - 1) * $perPage;

// Build the base query
$baseQuery = "FROM produit WHERE 1=1";
$params = [];

// Add search condition if search term exists
if (!empty($search)) {
    $baseQuery .= " AND (name LIKE :search OR description LIKE :search)";
    $params[':search'] = "%$search%";
}

// Add category filter if selected
if (!empty($categoryFilter)) {
    $baseQuery .= " AND category = :category";
    $params[':category'] = $categoryFilter;
}

// Get total count for pagination
$countQuery = "SELECT COUNT(*) " . $baseQuery;
$stmt = $conn->prepare($countQuery);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->execute();
$totalProducts = $stmt->fetchColumn();
$totalPages = ceil($totalProducts / $perPage);

// Get products with pagination
$query = "SELECT * " . $baseQuery . " ORDER BY id DESC LIMIT :offset, :perPage";
$stmt = $conn->prepare($query);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->bindValue(':perPage', $perPage, PDO::PARAM_INT);
$stmt->execute();
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch all unique categories for the filter dropdown
$stmt = $conn->query("SELECT DISTINCT category FROM produit ORDER BY category");
$categories = $stmt->fetchAll(PDO::FETCH_COLUMN);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Products - CasawiTech</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/ScrollTrigger.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
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
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #fff8f3; color: #111111; }
        .product-card {
            opacity: 0;
            transform: translateY(20px);
            transition: all 0.3s ease;
        }
        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        .add-button {
            opacity: 0;
            transform: translateX(-20px);
        }
        .search-bar {
            opacity: 0;
            transform: translateY(-20px);
        }
        .pagination-item {
            opacity: 0;
            transform: scale(0.8);
            transition: all 0.3s ease;
        }
        .pagination-item:hover {
            transform: scale(1.1);
        }
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }
        .stock-indicator {
            transition: all 0.3s ease;
        }
        .stock-indicator.low {
            color: #ef4444;
            animation: pulse 2s infinite;
        }
        .stock-indicator.medium {
            color: #f59e0b;
        }
        .stock-indicator.high {
            color: #10b981;
        }
    </style>
</head>
<body>
    <?php include 'admin_navbar.php'; ?>

    <main class="container mx-auto px-4 py-8">
        <!-- Action Bar -->
        <div class="flex flex-col md:flex-row justify-between items-center mb-8 space-y-4 md:space-y-0">
            <a href="add_product.php" class="add-button bg-primary-accent hover:bg-opacity-90 text-white px-6 py-3 rounded-lg inline-flex items-center transition-colors duration-250">
                <i class="fas fa-plus-circle mr-2"></i>
                Add New Product
            </a>

            <!-- Search and Filter -->
            <div class="search-bar flex items-center space-x-4">
                <form action="" method="GET" class="flex items-center space-x-4">
                    <div class="relative">
                        <input type="text" 
                               name="search" 
                               value="<?= htmlspecialchars($search) ?>"
                               placeholder="Search products..." 
                               class="pl-10 pr-4 py-2 w-64 bg-primary-light border border-primary-gray/20 rounded-lg text-primary-text placeholder-primary-gray focus:outline-none focus:border-primary-accent transition-colors duration-250">
                        <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-primary-gray"></i>
                    </div>
                    <select name="category" 
                            class="px-4 py-2 bg-primary-light border border-primary-gray/20 rounded-lg text-primary-text focus:outline-none focus:border-primary-accent transition-colors duration-250">
                        <option value="">All Categories</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= htmlspecialchars($cat) ?>" <?= $categoryFilter === $cat ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cat) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" 
                            class="bg-primary-accent hover:bg-opacity-90 text-white px-6 py-2 rounded-lg transition-colors duration-250">
                        <i class="fas fa-filter mr-2"></i>
                        Filter
                    </button>
                    <?php if (!empty($search) || !empty($categoryFilter)): ?>
                        <a href="admin_products.php" 
                           class="bg-primary-light text-primary-text px-4 py-2 rounded-lg hover:bg-primary-light/70 transition-colors duration-250">
                            <i class="fas fa-times mr-1"></i>
                            Clear
                        </a>
                    <?php endif; ?>
                </form>
            </div>
        </div>

        <!-- Products Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
            <?php foreach ($products as $product): ?>
                <div class="product-card bg-primary-bg rounded-lg shadow-lg overflow-hidden">
                    <div class="aspect-square overflow-hidden">
                        <img src="<?= htmlspecialchars($product['lien']) ?>" 
                             alt="<?= htmlspecialchars($product['name']) ?>" 
                             class="w-full h-full object-cover transition-transform duration-500 hover:scale-105">
                    </div>
                    <div class="p-4">
                        <h3 class="text-lg font-semibold text-primary-text line-clamp-2">
                            <?= htmlspecialchars($product['name']) ?>
                        </h3>
                        <p class="text-primary-gray mt-1"><?= htmlspecialchars($product['category']) ?></p>
                        <div class="flex items-center justify-between mt-2">
                            <div>
                                <p class="text-lg font-bold text-primary-accent"><?= number_format($product['prix'], 2) ?> DHs</p>
                                <?php if (!empty($product['old_price'])): ?>
                                    <p class="text-sm text-primary-gray line-through"><?= number_format($product['old_price'], 2) ?> DHs</p>
                                <?php endif; ?>
                            </div>
                            <?php
                            $stockClass = '';
                            if ($product['stock_quantity'] <= 5) {
                                $stockClass = 'low';
                            } elseif ($product['stock_quantity'] <= 20) {
                                $stockClass = 'medium';
                            } else {
                                $stockClass = 'high';
                            }
                            ?>
                            <div class="stock-indicator <?= $stockClass ?> text-sm">
                                <i class="fas fa-cube mr-1"></i>
                                <?= $product['stock_quantity'] ?> in stock
                            </div>
                        </div>
                        <div class="flex items-center justify-between mt-4 pt-4 border-t border-primary-light">
                            <a href="modify_product.php?id=<?= $product['id'] ?>" 
                               class="text-primary-accent hover:text-primary-accent/80 transition-colors duration-250">
                                <i class="fas fa-edit mr-1"></i>
                                Edit
                            </a>
                            <form method="POST" action="admin_products.php" class="inline" onsubmit="return confirm('Are you sure you want to delete this product?');">
                                <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                                <button type="submit" name="delete_product" 
                                        class="text-red-500 hover:text-red-600 transition-colors duration-250">
                                    <i class="fas fa-trash-alt mr-1"></i>
                                    Delete
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
            <div class="flex justify-center mt-8 space-x-2">
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <a href="?page=<?= $i ?><?= !empty($search) ? '&search='.urlencode($search) : '' ?><?= !empty($categoryFilter) ? '&category='.urlencode($categoryFilter) : '' ?>" 
                       class="pagination-item inline-flex items-center justify-center w-10 h-10 rounded-lg <?= $page === $i 
                            ? 'bg-primary-accent text-white' 
                            : 'bg-primary-light text-primary-text hover:bg-primary-accent hover:text-white' ?> transition-colors duration-250">
                        <?= $i ?>
                    </a>
                <?php endfor; ?>
            </div>
        <?php endif; ?>
    </main>

    <?php include 'footer.php'; ?>

    <script>
        // Initialize GSAP
        gsap.registerPlugin(ScrollTrigger);

        // Add button animation
        gsap.to('.add-button', {
            opacity: 1,
            x: 0,
            duration: 0.8,
            ease: 'power3.out'
        });

        // Search bar animation
        gsap.to('.search-bar', {
            opacity: 1,
            y: 0,
            duration: 0.8,
            ease: 'power3.out'
        });

        // Product cards staggered animation
        gsap.to('.product-card', {
            opacity: 1,
            y: 0,
            duration: 0.6,
            stagger: 0.1,
            ease: 'power3.out',
            scrollTrigger: {
                trigger: '.product-card',
                start: 'top bottom-=100',
                toggleActions: 'play none none reverse'
            }
        });

        // Pagination items animation
        gsap.to('.pagination-item', {
            opacity: 1,
            scale: 1,
            duration: 0.6,
            stagger: 0.1,
            ease: 'back.out(1.7)',
            delay: 0.4
        });

        // Stock indicator animation
        document.querySelectorAll('.stock-indicator.low').forEach(indicator => {
            gsap.to(indicator, {
                scale: 1.1,
                duration: 1,
                repeat: -1,
                yoyo: true,
                ease: 'power1.inOut'
            });
        });
    </script>
</body>
</html>

