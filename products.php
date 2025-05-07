<?php
session_start();
$isLoggedIn = isset($_SESSION['user_id']);
$isAdmin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';

require_once 'config.php';
$conn = getPDO();

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 12; // Number of products per page
$offset = ($page - 1) * $perPage;

// Search and filter functionality
$search = isset($_GET['search']) ? $_GET['search'] : '';
$category = isset($_GET['category']) ? $_GET['category'] : '';

$searchQuery = "%$search%";

// Fetch all categories
$categoryStmt = $conn->query("SELECT DISTINCT category FROM produit ORDER BY category");
$categories = $categoryStmt->fetchAll(PDO::FETCH_COLUMN);

// Sorting
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'name';
$order = isset($_GET['order']) ? $_GET['order'] : 'ASC';
$allowedSorts = ['name', 'prix', 'category'];
$sort = in_array($sort, $allowedSorts) ? $sort : 'name';
$order = $order === 'DESC' ? 'DESC' : 'ASC';

// Base SQL query
$sql = "SELECT id, name, prix, old_price, description, lien, category, stock_quantity FROM produit WHERE 1=1";
    
if (!empty($search)) {
    $sql .= " AND (name LIKE :search OR category LIKE :search)";
}
    
if (!empty($category)) {
    $sql .= " AND category = :category";
}
    
$sql .= " ORDER BY $sort $order 
          LIMIT :limit OFFSET :offset";

$stmt = $conn->prepare($sql);
    
if (!empty($search)) {
    $stmt->bindValue(':search', $searchQuery, PDO::PARAM_STR);
}
    
if (!empty($category)) {
    $stmt->bindValue(':category', $category, PDO::PARAM_STR);
}
    
$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();

$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get total number of products for pagination
$countSql = "SELECT COUNT(*) FROM produit WHERE 1=1";
    
if (!empty($search)) {
    $countSql .= " AND (name LIKE :search OR category LIKE :search)";
}
    
if (!empty($category)) {
    $countSql .= " AND category = :category";
}
    
$countStmt = $conn->prepare($countSql);
    
if (!empty($search)) {
    $countStmt->bindValue(':search', $searchQuery, PDO::PARAM_STR);
}
    
if (!empty($category)) {
    $countStmt->bindValue(':category', $category, PDO::PARAM_STR);
}
    
$countStmt->execute();
$totalProducts = $countStmt->fetchColumn();
$totalPages = ceil($totalProducts / $perPage);

// Delete product functionality
if ($isAdmin && isset($_POST['delete_product'])) {
    $productId = $_POST['product_id'];
    try {
        $deleteSql = "DELETE FROM produit WHERE id = :id";
        $deleteStmt = $conn->prepare($deleteSql);
        $deleteStmt->bindValue(':id', $productId, PDO::PARAM_INT);
        $deleteStmt->execute();
        $_SESSION['message'] = "Product deleted successfully.";
        header("Location: products.php");
        exit;
    } catch(PDOException $e) {
        $_SESSION['error'] = "Failed to delete product: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Products - CasawiTech</title>
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
                            bg: '#fff8f3',
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
        body {
            font-family: 'Inter', sans-serif;
            background-color: #fff8f3;
            color: #111111;
        }
        .product-card {
            opacity: 0;
            transform: translateY(30px);
            transition: box-shadow 0.3s, transform 0.3s;
            background: #fff;
            color: #111111;
        }
        .product-card:hover {
            transform: translateY(-8px) scale(1.02);
            box-shadow: 0 8px 32px rgba(0,0,0,0.08);
        }
        .wishlist-btn {
            transition: color 0.2s;
            color: #888888;
        }
        .wishlist-btn:hover {
            color: #ff0033;
        }
        .badge-new {
            background: #2563eb;
            color: #fff;
        }
        .badge-sale {
            background: #22c55e;
            color: #fff;
        }
        .badge-soldout {
            background: #fff;
            color: #22c55e;
        }
        .add-to-cart-btn[disabled] {
            opacity: 0.5;
            cursor: not-allowed;
        }
        .product-card .bg-primary-bg,
        .product-card .bg-primary-light {
            background: #fff !important;
        }
        .product-card .text-primary-text {
            color: #111111 !important;
        }
        .product-card .text-primary-gray {
            color: #888888 !important;
        }
        .product-card .text-primary-accent {
            color: #ff0033 !important;
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>

    <!-- Hero Section -->
    <section class="w-full bg-primary-bg text-primary-text py-16 border-b border-primary-light">
        <div class="max-w-7xl mx-auto px-4 flex flex-col items-center justify-center">
            <h1 class="text-5xl font-extrabold mb-4">Products</h1>
            <nav class="text-primary-gray text-sm flex items-center space-x-2">
                <a href="/" class="hover:text-primary-accent">Home</a>
                <span>/</span>
                <span class="text-primary-text">Products</span>
            </nav>
        </div>
    </section>

    <main class="max-w-7xl mx-auto px-4 py-12 bg-primary-bg">
        <!-- Filter & Sort Bar -->
        <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-8 gap-4">
            <div class="flex items-center gap-4">
                <button class="border border-primary-accent text-primary-accent px-4 py-2 rounded-lg flex items-center hover:bg-primary-accent hover:text-white transition-colors duration-250">
                    <i class="fas fa-filter mr-2"></i> Filter
                </button>
                <form id="filterForm" action="" method="GET" class="flex items-center gap-2">
                    <input type="text" name="search" placeholder="Search..." value="<?= htmlspecialchars($search) ?>" class="px-3 py-2 rounded bg-primary-light text-primary-text border border-primary-gray/20 focus:outline-none focus:border-primary-accent" />
                    <select name="category" class="px-3 py-2 rounded bg-primary-light text-primary-text border border-primary-gray/20 focus:outline-none focus:border-primary-accent">
                        <option value="">All Categories</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= htmlspecialchars($cat) ?>" <?= $category === $cat ? 'selected' : '' ?>><?= htmlspecialchars($cat) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" class="bg-primary-accent text-white px-4 py-2 rounded hover:bg-opacity-90 transition-colors duration-250"><i class="fas fa-search"></i></button>
                </form>
            </div>
            <div class="flex items-center gap-2">
                <span class="text-primary-gray">Sort by:</span>
                <form method="GET" class="flex items-center gap-2">
                    <input type="hidden" name="search" value="<?= htmlspecialchars($search) ?>">
                    <input type="hidden" name="category" value="<?= htmlspecialchars($category) ?>">
                    <select name="sort" class="px-3 py-2 rounded bg-primary-light text-primary-text border border-primary-gray/20 focus:outline-none focus:border-primary-accent">
                        <option value="name" <?= $sort === 'name' ? 'selected' : '' ?>>Name</option>
                        <option value="prix" <?= $sort === 'prix' ? 'selected' : '' ?>>Price</option>
                        <option value="category" <?= $sort === 'category' ? 'selected' : '' ?>>Category</option>
                    </select>
                    <select name="order" class="px-3 py-2 rounded bg-primary-light text-primary-text border border-primary-gray/20 focus:outline-none focus:border-primary-accent">
                        <option value="ASC" <?= $order === 'ASC' ? 'selected' : '' ?>>Asc</option>
                        <option value="DESC" <?= $order === 'DESC' ? 'selected' : '' ?>>Desc</option>
                    </select>
                    <button type="submit" class="bg-primary-accent text-white px-4 py-2 rounded hover:bg-opacity-90 transition-colors duration-250"><i class="fas fa-sort"></i></button>
                </form>
            </div>
        </div>

        <!-- Product Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-8">
            <?php foreach ($products as $product): ?>
                <div class="product-card bg-white rounded-xl shadow-lg overflow-hidden flex flex-col relative">
                    <!-- Badges -->
                    <div class="absolute top-4 left-4 flex flex-col gap-2 z-10">
                        <?php if (!empty($product['old_price']) && $product['prix'] < $product['old_price']): ?>
                            <span class="badge-sale px-3 py-1 rounded-full text-xs font-bold">Sale</span>
                        <?php endif; ?>
                        <?php if ($product['id'] > (intval($totalProducts) - 12)): ?>
                            <span class="badge-new px-3 py-1 rounded-full text-xs font-bold">New</span>
                        <?php endif; ?>
                        <?php if ($product['stock_quantity'] == 0): ?>
                            <span class="badge-soldout px-3 py-1 rounded-full text-xs font-bold">Sold out</span>
                        <?php endif; ?>
                    </div>
                    <!-- Wishlist Icon -->
                    <button class="absolute top-4 right-4 wishlist-btn text-primary-gray text-xl"><i class="far fa-heart"></i></button>
                    <a href="product_details.php?id=<?= $product['id'] ?>" class="block flex-1">
                        <div class="aspect-square overflow-hidden bg-primary-bg">
                            <img src="<?= htmlspecialchars($product['lien']) ?>" alt="<?= htmlspecialchars($product['name']) ?>" class="w-full h-full object-cover transition-transform duration-500 hover:scale-105" />
                        </div>
                        <div class="p-6 flex flex-col gap-2">
                            <h3 class="text-lg font-bold text-primary-text line-clamp-2 min-h-[48px]"> <?= htmlspecialchars($product['name']) ?> </h3>
                            <p class="text-primary-gray text-sm mb-2"> <?= htmlspecialchars($product['category']) ?> </p>
                            <div class="flex items-end justify-between flex-wrap gap-2 mt-auto">
                                <div class="flex flex-col">
                                    <span class="text-2xl font-bold text-primary-accent">
                                        <?= number_format($product['prix'], 2) ?> DHs
                                    </span>
                                    <?php if (!empty($product['old_price']) && $product['prix'] < $product['old_price']): ?>
                                        <span class="text-sm text-primary-gray line-through">
                                            <?= number_format($product['old_price'], 2) ?> DHs
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <span class="text-xs text-primary-gray">Stock: <?= $product['stock_quantity'] > 0 ? $product['stock_quantity'] : 'Out of stock' ?></span>
                            </div>
                        </div>
                    </a>
                    <div class="p-6 bg-primary-bg border-t border-primary-light flex items-center gap-4">
                        <form action="<?= $isLoggedIn ? 'cart.php' : 'login.php' ?>" method="POST" class="flex-1 flex gap-2">
                            <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                            <input type="number" name="quantity" value="1" min="1" max="<?= $product['stock_quantity'] ?>" class="w-16 px-3 py-2 bg-primary-light border border-primary-gray/20 rounded text-primary-text focus:outline-none focus:border-primary-accent transition-colors duration-250" <?= $product['stock_quantity'] == 0 ? 'disabled' : '' ?> />
                            <button type="submit" class="add-to-cart-btn flex-1 bg-primary-accent hover:bg-opacity-90 text-white py-2 px-4 rounded transition-colors duration-250 flex items-center justify-center gap-2" <?= $product['stock_quantity'] == 0 ? 'disabled' : '' ?>>
                                <i class="fas fa-cart-plus"></i>
                                <span><?= $product['stock_quantity'] == 0 ? 'Sold Out' : 'Add To Cart' ?></span>
                            </button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Pagination -->
        <div class="mt-12 flex justify-center space-x-2">
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&category=<?= urlencode($category) ?>&sort=<?= $sort ?>&order=<?= $order ?>" class="px-4 py-2 rounded-lg <?= $page === $i ? 'bg-primary-accent text-white' : 'bg-primary-light text-primary-text hover:bg-primary-accent hover:text-white' ?> transition-colors duration-250 font-semibold">
                    <?= $i ?>
                </a>
            <?php endfor; ?>
        </div>
    </main>

    <?php include 'footer.php'; ?>

    <script>
        // GSAP Animations
        gsap.registerPlugin(ScrollTrigger);
        gsap.to('.product-card', {
            y: 0,
            opacity: 1,
            duration: 0.7,
            stagger: 0.1,
            ease: 'power3.out',
            scrollTrigger: {
                trigger: '.product-card',
                start: 'top bottom-=100',
                toggleActions: 'play none none reverse'
            }
        });
    </script>
</body>
</html>