<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit;
}

require_once 'config.php';
$conn = getPDO();

$message = '';
$messageType = '';
$product = null;

function validateInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

if ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET['id'])) {
    $id = validateInput($_GET['id']);
    $stmt = $conn->prepare("SELECT * FROM produit WHERE id = :id");
    $stmt->execute(['id' => $id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$product) {
        $message = "Product not found.";
        $messageType = "error";
    }
} elseif ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['update_product'])) {
        // Validate inputs
        $id = validateInput($_POST['id']);
        $name = validateInput($_POST['name']);
        $prix = floatval($_POST['prix']);
        $category = validateInput($_POST['category']);
        $description = validateInput($_POST['description']);
        $lien = validateInput($_POST['lien']); // Changed from 'image' to 'lien' to match form field
        $stock_quantity = intval($_POST['stock_quantity']);
        $old_price = !empty($_POST['old_price']) ? floatval($_POST['old_price']) : null;

        // Validation
        if (empty($name)) {
            $message = "Product name cannot be empty.";
            $messageType = "error";
        } elseif ($prix <= 0) {
            $message = "Price must be greater than zero.";
            $messageType = "error";
        } elseif ($stock_quantity < 0) {
            $message = "Stock quantity cannot be negative.";
            $messageType = "error";
        } elseif (!filter_var($lien, FILTER_VALIDATE_URL)) {
            $message = "Invalid image URL.";
            $messageType = "error";
        } else {
            try {
                // Check if price has changed
                $stmt = $conn->prepare("SELECT prix FROM produit WHERE id = :id");
                $stmt->execute(['id' => $id]);
                $current_product = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($current_product['prix'] != $prix) {
                    // If price changed, update with old price
                    $stmt = $conn->prepare("
                        UPDATE produit 
                        SET name = :name, 
                            prix = :prix, 
                            old_price = :old_price, 
                            description = :description, 
                            lien = :lien, 
                            category = :category, 
                            stock_quantity = :stock_quantity 
                        WHERE id = :id
                    ");
                    $params = [
                        'id' => $id,
                        'name' => $name,
                        'prix' => $prix,
                        'old_price' => $current_product['prix'],
                        'description' => $description,
                        'lien' => $lien,
                        'category' => $category,
                        'stock_quantity' => $stock_quantity
                    ];
                } else {
                    // If price hasn't changed
                    $stmt = $conn->prepare("
                        UPDATE produit 
                        SET name = :name, 
                            prix = :prix, 
                            description = :description, 
                            lien = :lien, 
                            category = :category, 
                            stock_quantity = :stock_quantity 
                        WHERE id = :id
                    ");
                    $params = [
                        'id' => $id,
                        'name' => $name,
                        'prix' => $prix,
                        'description' => $description,
                        'lien' => $lien,
                        'category' => $category,
                        'stock_quantity' => $stock_quantity
                    ];
                }

                if ($stmt->execute($params)) {
                    $message = "Product updated successfully.";
                    $messageType = "success";
                    
                    // Fetch the updated product data
                    $stmt = $conn->prepare("SELECT * FROM produit WHERE id = :id");
                    $stmt->execute(['id' => $id]);
                    $product = $stmt->fetch(PDO::FETCH_ASSOC);
                } else {
                    $message = "Failed to update product.";
                    $messageType = "error";
                }
            } catch (PDOException $e) {
                $message = "Database error: " . $e->getMessage();
                $messageType = "error";
            }
        }
    }
}

// Fetch all unique categories
$stmt = $conn->query("SELECT DISTINCT category FROM produit ORDER BY category");
$categories = $stmt->fetchAll(PDO::FETCH_COLUMN);

$stmt = $conn->query("SELECT id, name FROM produit");
$all_products = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modify Product - CasawiTech</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/animejs/3.2.1/anime.min.js"></script>
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
        .form-card {
            opacity: 0;
            transform: translateY(20px);
        }
        .preview-card {
            opacity: 0;
            transform: translateX(20px);
        }
        .form-input {
            transition: all 0.3s ease;
        }
        .form-input:focus {
            transform: scale(1.02);
        }
        .image-preview {
            opacity: 0;
            transform: scale(0.95);
        }
        @keyframes float {
            0% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
            100% { transform: translateY(0px); }
        }
        .floating-icon {
            animation: float 3s ease-in-out infinite;
        }
        .save-button {
            position: relative;
            overflow: hidden;
        }
        .save-button::after {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: 0.5s;
        }
        .save-button:hover::after {
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
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- Product Form -->
            <div class="form-card bg-primary-bg rounded-2xl shadow-lg overflow-hidden">
                <div class="p-8">
                    <form method="post" action="<?php echo isset($product) ? 'modify_product.php?id='.$product['id'] : 'add_product.php'; ?>" 
                          enctype="multipart/form-data" 
                          class="space-y-6">
                        
                        <?php if (isset($product)): ?>
                            <input type="hidden" name="id" value="<?= $product['id'] ?>">
                            <input type="hidden" name="update_product" value="1">
                        <?php endif; ?>

                        <div>
                            <label class="block text-sm font-medium text-primary-gray mb-2" for="name">
                                Product Name
                            </label>
                            <input type="text" 
                                   id="name" 
                                   name="name" 
                                   value="<?php echo isset($product) ? htmlspecialchars($product['name']) : ''; ?>"
                                   class="form-input w-full px-4 py-3 bg-primary-light border border-primary-gray/20 rounded-lg text-primary-text placeholder-primary-gray focus:outline-none focus:border-primary-accent transition-colors duration-250"
                                   required>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-primary-gray mb-2" for="description">
                                Description
                            </label>
                            <textarea id="description" 
                                      name="description" 
                                      rows="4"
                                      class="form-input w-full px-4 py-3 bg-primary-light border border-primary-gray/20 rounded-lg text-primary-text placeholder-primary-gray focus:outline-none focus:border-primary-accent transition-colors duration-250"
                                      required><?php echo isset($product) ? htmlspecialchars($product['description']) : ''; ?></textarea>
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
                                       value="<?php echo isset($product) ? htmlspecialchars($product['prix']) : ''; ?>"
                                       class="form-input w-full px-4 py-3 bg-primary-light border border-primary-gray/20 rounded-lg text-primary-text placeholder-primary-gray focus:outline-none focus:border-primary-accent transition-colors duration-250"
                                       required>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-primary-gray mb-2" for="old_price">
                                    Old Price (Optional)
                                </label>
                                <input type="number" 
                                       id="old_price" 
                                       name="old_price" 
                                       step="0.01"
                                       value="<?php echo isset($product) ? htmlspecialchars($product['old_price']) : ''; ?>"
                                       class="form-input w-full px-4 py-3 bg-primary-light border border-primary-gray/20 rounded-lg text-primary-text placeholder-primary-gray focus:outline-none focus:border-primary-accent transition-colors duration-250">
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-primary-gray mb-2" for="category">
                                Category
                            </label>
                            <select id="category" 
                                    name="category"
                                    class="form-input w-full px-4 py-3 bg-primary-light border border-primary-gray/20 rounded-lg text-primary-text focus:outline-none focus:border-primary-accent transition-colors duration-250"
                                    required>
                                <option value="">Select Category</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?= htmlspecialchars($cat) ?>" <?= (isset($product) && $product['category'] === $cat) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($cat) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-primary-gray mb-2" for="stock_quantity">
                                Stock Quantity
                            </label>
                            <input type="number" 
                                   id="stock_quantity" 
                                   name="stock_quantity" 
                                   value="<?php echo isset($product) ? htmlspecialchars($product['stock_quantity']) : '0'; ?>"
                                   class="form-input w-full px-4 py-3 bg-primary-light border border-primary-gray/20 rounded-lg text-primary-text placeholder-primary-gray focus:outline-none focus:border-primary-accent transition-colors duration-250"
                                   required>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-primary-gray mb-2" for="lien">
                                Product Image URL
                            </label>
                            <input type="url" 
                                   id="lien" 
                                   name="lien" 
                                   value="<?php echo isset($product) ? htmlspecialchars($product['lien']) : ''; ?>"
                                   class="form-input w-full px-4 py-3 bg-primary-light border border-primary-gray/20 rounded-lg text-primary-text placeholder-primary-gray focus:outline-none focus:border-primary-accent transition-colors duration-250"
                                   required>
                        </div>

                        <div class="pt-4">
                            <button type="submit" 
                                    class="save-button w-full bg-primary-accent hover:bg-opacity-90 text-white py-3 px-4 rounded-lg transition-colors duration-250 font-medium flex items-center justify-center">
                                <i class="fas fa-save mr-2"></i>
                                <?php echo isset($product) ? 'Update Product' : 'Add Product'; ?>
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Preview Card -->
            <div class="preview-card bg-primary-bg rounded-2xl shadow-lg overflow-hidden">
                <div class="p-8">
                    <h2 class="text-xl font-bold text-primary-text mb-6">Product Preview</h2>
                    
                    <div class="bg-primary-light rounded-xl p-6">
                        <div class="image-preview mb-6 aspect-video rounded-lg overflow-hidden bg-primary-gray/10 flex items-center justify-center">
                            <?php if (isset($product) && $product['lien']): ?>
                                <img src="<?php echo htmlspecialchars($product['lien']); ?>" 
                                     alt="Product Preview" 
                                     class="w-full h-full object-cover">
                            <?php else: ?>
                                <div class="text-primary-gray">
                                    <i class="fas fa-image text-4xl mb-2"></i>
                                    <p>No image uploaded</p>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="space-y-4">
                            <div>
                                <h3 class="text-2xl font-bold text-primary-text product-name">
                                    <?php echo isset($product) ? htmlspecialchars($product['name']) : 'Product Name'; ?>
                                </h3>
                                <p class="text-primary-gray mt-2 product-description">
                                    <?php echo isset($product) ? htmlspecialchars($product['description']) : 'Product description will appear here...'; ?>
                                </p>
                            </div>

                            <div class="flex items-end space-x-2">
                                <span class="text-2xl font-bold text-primary-accent product-price">
                                    <?php echo isset($product) ? number_format($product['prix'], 2) : '0.00'; ?> DHs
                                </span>
                                <?php if (isset($product) && $product['old_price']): ?>
                                    <span class="text-lg text-primary-gray line-through product-old-price">
                                        <?php echo number_format($product['old_price'], 2); ?> DHs
                                    </span>
                                <?php endif; ?>
                            </div>

                            <div class="flex items-center space-x-4">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-primary-light text-primary-text product-category">
                                    <?php echo isset($product) ? htmlspecialchars($product['category']) : 'Category'; ?>
                                </span>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo (isset($product) && $product['stock_quantity'] > 0) ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'; ?> product-stock">
                                    <?php 
                                        if (isset($product)) {
                                            echo $product['stock_quantity'] > 0 ? 'In Stock' : 'Out of Stock';
                                        } else {
                                            echo 'Stock Status';
                                        }
                                    ?>
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="mt-8">
                        <h3 class="text-lg font-semibold text-primary-text mb-4">Preview Tips</h3>
                        <ul class="space-y-3 text-primary-gray">
                            <li class="flex items-center">
                                <i class="fas fa-check-circle text-primary-accent mr-2"></i>
                                Fill in the form to see live preview
                            </li>
                            <li class="flex items-center">
                                <i class="fas fa-check-circle text-primary-accent mr-2"></i>
                                Upload an image for best presentation
                            </li>
                            <li class="flex items-center">
                                <i class="fas fa-check-circle text-primary-accent mr-2"></i>
                                Set an old price to show discounts
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script>
        // Initialize GSAP
        gsap.timeline()
            .to('.form-card', {
                opacity: 1,
                y: 0,
                duration: 0.8,
                ease: 'power3.out'
            })
            .to('.preview-card', {
                opacity: 1,
                x: 0,
                duration: 0.8,
                ease: 'power3.out'
            }, '-=0.6')
            .to('.image-preview', {
                opacity: 1,
                scale: 1,
                duration: 0.6,
                ease: 'power3.out'
            }, '-=0.4');

        // Live preview functionality
        document.getElementById('name').addEventListener('input', function(e) {
            document.querySelector('.product-name').textContent = e.target.value || 'Product Name';
        });

        document.getElementById('description').addEventListener('input', function(e) {
            document.querySelector('.product-description').textContent = e.target.value || 'Product description will appear here...';
        });

        document.getElementById('prix').addEventListener('input', function(e) {
            document.querySelector('.product-price').textContent = `${parseFloat(e.target.value || 0).toFixed(2)} DHs`;
        });

        document.getElementById('category').addEventListener('change', function(e) {
            document.querySelector('.product-category').textContent = e.target.value || 'Category';
        });

        document.getElementById('stock_quantity').addEventListener('input', function(e) {
            const stockElement = document.querySelector('.product-stock');
            const quantity = parseInt(e.target.value || 0);
            
            if (quantity > 0) {
                stockElement.className = 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700';
                stockElement.textContent = 'In Stock';
            } else {
                stockElement.className = 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-700';
                stockElement.textContent = 'Out of Stock';
            }
        });

        // Image preview
        document.getElementById('lien').addEventListener('input', function(e) {
            const url = e.target.value;
            if (url) {
                document.querySelector('.image-preview').innerHTML = `<img src="${url}" alt="Product Preview" class="w-full h-full object-cover">`;
                
                // Animate new image
                gsap.from('.image-preview img', {
                    opacity: 0,
                    scale: 0.8,
                    duration: 0.6,
                    ease: 'power3.out'
                });
            }
        });

        // Drag and drop functionality
        const dropZone = document.querySelector('.border-dashed');
        
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            dropZone.addEventListener(eventName, preventDefaults, false);
        });

        function preventDefaults (e) {
            e.preventDefault();
            e.stopPropagation();
        }

        ['dragenter', 'dragover'].forEach(eventName => {
            dropZone.addEventListener(eventName, highlight, false);
        });

        ['dragleave', 'drop'].forEach(eventName => {
            dropZone.addEventListener(eventName, unhighlight, false);
        });

        function highlight(e) {
            dropZone.classList.add('border-primary-accent');
        }

        function unhighlight(e) {
            dropZone.classList.remove('border-primary-accent');
        }

        dropZone.addEventListener('drop', handleDrop, false);

        function handleDrop(e) {
            const dt = e.dataTransfer;
            const file = dt.files[0];
            
            if (file && file.type.startsWith('image/')) {
                const url = URL.createObjectURL(file);
                document.getElementById('lien').value = url;
                const event = new Event('input');
                document.getElementById('lien').dispatchEvent(event);
            }
        }

        // Form input animations
        document.querySelectorAll('.form-input').forEach(input => {
            input.addEventListener('focus', function() {
                gsap.to(this, {
                    scale: 1.02,
                    duration: 0.3,
                    ease: 'power2.out'
                });
            });

            input.addEventListener('blur', function() {
                gsap.to(this, {
                    scale: 1,
                    duration: 0.3,
                    ease: 'power2.out'
                });
            });
        });

        // Save button animation
        document.querySelector('.save-button').addEventListener('click', function(e) {
            if (!document.querySelector('form').checkValidity()) return;

            const button = this;
            const rect = button.getBoundingClientRect();
            const x = e.clientX - rect.left;
            const y = e.clientY - rect.top;

            for (let i = 0; i < 8; i++) {
                const particle = document.createElement('div');
                particle.className = 'absolute w-1 h-1 bg-white rounded-full';
                button.appendChild(particle);

                const destinationX = x + (Math.random() - 0.5) * 100;
                const destinationY = y + (Math.random() - 0.5) * 100;

                anime({
                    targets: particle,
                    left: [x, destinationX],
                    top: [y, destinationY],
                    opacity: [1, 0],
                    easing: 'easeOutExpo',
                    duration: 1000,
                    complete: function() {
                        particle.remove();
                    }
                });
            }
        });
    </script>
</body>
</html>

