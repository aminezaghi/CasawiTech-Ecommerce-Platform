<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit;
}

require_once 'config.php';
$conn = getPDO();

// Fetch all categories
$stmt = $conn->query("SELECT DISTINCT category FROM produit ORDER BY category");
$categories = $stmt->fetchAll(PDO::FETCH_COLUMN);

$message = "";
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    unset($_SESSION['message']);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Product - CasawiTech</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js"></script>
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
        .form-section {
            opacity: 1;
            transform: none;
        }
        .preview-section {
            opacity: 1;
            transform: none;
        }
        .form-input {
            transition: all 0.3s ease;
        }
        .form-input:focus {
            transform: scale(1.02);
        }
        .add-button {
            position: relative;
            overflow: hidden;
        }
        .add-button::after {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: 0.5s;
        }
        .add-button:hover::after {
            left: 100%;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="bg-primary-bg border-b border-primary-light sticky top-0 z-50">
        <div class="container mx-auto px-4 py-4">
            <div class="flex items-center justify-between">
                <a href="/" class="text-3xl font-bold text-primary-accent">
                    CasawiTech
                </a>
                <div class="flex items-center space-x-8">
                    <a href="admin_dashboard.php" class="text-primary-gray hover:text-primary-accent font-semibold transition-colors duration-250">
                        <i class="fas fa-chart-line mr-1"></i> Dashboard
                    </a>
                    <a href="admin_products.php" class="text-primary-gray hover:text-primary-accent font-semibold transition-colors duration-250">
                        <i class="fas fa-box mr-1"></i> Products
                    </a>
                    <a href="admin_orders.php" class="text-primary-gray hover:text-primary-accent font-semibold transition-colors duration-250">
                        <i class="fas fa-shopping-cart mr-1"></i> Orders
                    </a>
                    <a href="admin_customers.php" class="text-primary-gray hover:text-primary-accent font-semibold transition-colors duration-250">
                        <i class="fas fa-users mr-1"></i> Customers
                    </a>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="products.php" class="text-primary-gray hover:text-primary-accent transition-colors duration-250">
                        <i class="fas fa-store"></i>
                        <span class="ml-2">View Store</span>
                    </a>
                    <a href="logout.php" class="text-primary-gray hover:text-primary-accent transition-colors duration-250">
                        <i class="fas fa-sign-out-alt"></i>
                        <span class="ml-2">Logout</span>
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <main class="container mx-auto px-4 py-8">
        <?php if ($message): ?>
            <div class="mb-8 p-4 bg-green-100 text-green-700 rounded-lg">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- Add Product Form -->
            <div class="form-section bg-primary-bg rounded-lg shadow-lg p-6">
                <h2 class="text-2xl font-bold text-primary-text mb-6">Add New Product</h2>
                <form action="ajouter.php" method="POST" class="space-y-6">
                    <div>
                        <label class="block text-sm font-medium text-primary-gray mb-2" for="name">
                            Product Name
                        </label>
                        <input type="text" 
                               id="name" 
                               name="name" 
                               required
                               class="form-input w-full px-4 py-2 bg-primary-light border border-primary-gray/20 rounded-lg text-primary-text placeholder-primary-gray focus:outline-none focus:border-primary-accent transition-colors duration-250"
                               placeholder="Enter product name">
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-primary-gray mb-2" for="prix">
                                Price (DHs)
                            </label>
                            <input type="number" 
                                   id="prix" 
                                   name="prix" 
                                   step="0.01"
                                   required
                                   class="form-input w-full px-4 py-2 bg-primary-light border border-primary-gray/20 rounded-lg text-primary-text placeholder-primary-gray focus:outline-none focus:border-primary-accent transition-colors duration-250"
                                   placeholder="Enter price">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-primary-gray mb-2" for="old_price">
                                Old Price (Optional)
                            </label>
                            <input type="number" 
                                   id="old_price" 
                                   name="old_price" 
                                   step="0.01"
                                   class="form-input w-full px-4 py-2 bg-primary-light border border-primary-gray/20 rounded-lg text-primary-text placeholder-primary-gray focus:outline-none focus:border-primary-accent transition-colors duration-250"
                                   placeholder="Enter old price">
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-primary-gray mb-2" for="category">
                            Category
                        </label>
                        <select id="category" 
                                name="category" 
                                required
                                class="form-input w-full px-4 py-2 bg-primary-light border border-primary-gray/20 rounded-lg text-primary-text focus:outline-none focus:border-primary-accent transition-colors duration-250">
                            <option value="">Select a category</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?= htmlspecialchars($category) ?>"><?= htmlspecialchars($category) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-primary-gray mb-2" for="description">
                            Description
                        </label>
                        <textarea id="description" 
                                  name="description" 
                                  required
                                  rows="4"
                                  class="form-input w-full px-4 py-2 bg-primary-light border border-primary-gray/20 rounded-lg text-primary-text placeholder-primary-gray focus:outline-none focus:border-primary-accent transition-colors duration-250"
                                  placeholder="Enter product description"></textarea>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-primary-gray mb-2" for="image">
                            Image URL
                        </label>
                        <input type="url" 
                               id="image" 
                               name="image" 
                               required
                               class="form-input w-full px-4 py-2 bg-primary-light border border-primary-gray/20 rounded-lg text-primary-text placeholder-primary-gray focus:outline-none focus:border-primary-accent transition-colors duration-250"
                               placeholder="Enter image URL">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-primary-gray mb-2" for="stock_quantity">
                            Stock Quantity
                        </label>
                        <input type="number" 
                               id="stock_quantity" 
                               name="stock_quantity" 
                               required
                               min="0"
                               class="form-input w-full px-4 py-2 bg-primary-light border border-primary-gray/20 rounded-lg text-primary-text placeholder-primary-gray focus:outline-none focus:border-primary-accent transition-colors duration-250"
                               placeholder="Enter stock quantity">
                    </div>

                    <div class="flex items-center justify-between pt-6">
                        <button type="submit" 
                                class="add-button bg-primary-accent hover:bg-opacity-90 text-white py-3 px-8 rounded-lg transition-colors duration-250">
                            <i class="fas fa-plus-circle mr-2"></i>
                            Add Product
                        </button>
                        <a href="admin_products.php" 
                           class="bg-primary-light text-primary-text py-3 px-8 rounded-lg hover:bg-primary-light/70 transition-colors duration-250">
                            Cancel
                        </a>
                    </div>
                </form>
            </div>

            <!-- Preview Section -->
            <div class="preview-section bg-primary-bg rounded-lg shadow-lg p-6">
                <h2 class="text-2xl font-bold text-primary-text mb-6">Product Preview</h2>
                <div class="bg-primary-light rounded-lg p-6">
                    <div id="imagePreview" class="aspect-square rounded-lg overflow-hidden bg-primary-gray/10 flex items-center justify-center mb-6">
                        <i class="fas fa-image text-4xl text-primary-gray"></i>
                    </div>
                    <div class="space-y-4">
                        <h3 id="namePreview" class="text-xl font-bold text-primary-text">Product Name</h3>
                        <p id="descriptionPreview" class="text-primary-gray">Product description will appear here...</p>
                        <div class="flex items-center space-x-4">
                            <span id="pricePreview" class="text-2xl font-bold text-primary-accent">0.00 DHs</span>
                            <span id="oldPricePreview" class="text-lg text-primary-gray line-through hidden">0.00 DHs</span>
                        </div>
                        <div class="flex items-center space-x-4">
                            <span id="categoryPreview" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-primary-light text-primary-text">
                                Category
                            </span>
                            <span id="stockPreview" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700">
                                In Stock
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script>
        // Live preview functionality
        document.getElementById('name').addEventListener('input', function(e) {
            document.getElementById('namePreview').textContent = e.target.value || 'Product Name';
        });

        document.getElementById('description').addEventListener('input', function(e) {
            document.getElementById('descriptionPreview').textContent = e.target.value || 'Product description will appear here...';
        });

        document.getElementById('prix').addEventListener('input', function(e) {
            document.getElementById('pricePreview').textContent = `${parseFloat(e.target.value || 0).toFixed(2)} DHs`;
        });

        document.getElementById('old_price').addEventListener('input', function(e) {
            const oldPricePreview = document.getElementById('oldPricePreview');
            if (e.target.value) {
                oldPricePreview.textContent = `${parseFloat(e.target.value).toFixed(2)} DHs`;
                oldPricePreview.classList.remove('hidden');
            } else {
                oldPricePreview.classList.add('hidden');
            }
        });

        document.getElementById('category').addEventListener('change', function(e) {
            document.getElementById('categoryPreview').textContent = e.target.value || 'Category';
        });

        document.getElementById('stock_quantity').addEventListener('input', function(e) {
            const quantity = parseInt(e.target.value || 0);
            const stockPreview = document.getElementById('stockPreview');
            
            if (quantity > 0) {
                stockPreview.className = 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700';
                stockPreview.textContent = 'In Stock';
            } else {
                stockPreview.className = 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-700';
                stockPreview.textContent = 'Out of Stock';
            }
        });

        document.getElementById('image').addEventListener('input', function(e) {
            const imageUrl = e.target.value;
            const imagePreview = document.getElementById('imagePreview');
            
            if (imageUrl) {
                imagePreview.innerHTML = `<img src="${imageUrl}" alt="Product Preview" class="w-full h-full object-cover">`;
            } else {
                imagePreview.innerHTML = '<i class="fas fa-image text-4xl text-primary-gray"></i>';
            }
        });
    </script>
</body>
</html>
