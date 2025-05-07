<?php
session_start();
$isLoggedIn = isset($_SESSION['user_id']);
if (!$isLoggedIn) {
  header("Location: login.php");
  exit;
}

require_once 'config.php';
$conn = getPDO();

// Fetch all categories
$categoryStmt = $conn->query("SELECT DISTINCT category FROM produit ORDER BY category");
$categories = $categoryStmt->fetchAll(PDO::FETCH_COLUMN);

// Handle AJAX requests for quantity updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update') {
    $productId = $_POST['product_id'];
    $quantity = $_POST['quantity'];

    // Update session cart
    $_SESSION['cart'][$productId] = $quantity;

    // Calculate new subtotal and total
    $stmt = $conn->prepare("SELECT prix FROM produit WHERE id = :id");
    $stmt->execute([':id' => $productId]);
    $price = $stmt->fetchColumn();

    $subtotal = $price * $quantity;
    $total = 0;
    foreach ($_SESSION['cart'] as $id => $qty) {
        $stmt->execute([':id' => $id]);
        $itemPrice = $stmt->fetchColumn();
        $total += $itemPrice * $qty;
    }

    echo json_encode([
        'success' => true,
        'subtotal' => number_format($subtotal, 2),
        'total' => number_format($total, 2)
    ]);
    exit;
}

// Handle regular form submissions for add/remove
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // If no action is specified but product_id exists, default to add
    $action = isset($_POST['action']) ? $_POST['action'] : 'add';
    $productId = isset($_POST['product_id']) ? $_POST['product_id'] : null;
    $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;

    if ($productId) {
        if (!isset($_SESSION['cart'])) {
            $_SESSION['cart'] = [];
        }

        if ($action === 'add') {
            // Check if product exists
            $stmt = $conn->prepare("SELECT id FROM produit WHERE id = :id");
            $stmt->execute([':id' => $productId]);
            if ($stmt->rowCount() > 0) {
                if (isset($_SESSION['cart'][$productId])) {
                    $_SESSION['cart'][$productId] += $quantity;
                } else {
                    $_SESSION['cart'][$productId] = $quantity;
                }
                $_SESSION['message'] = "Product added to cart successfully!";
            }
        } elseif ($action === 'remove') {
            unset($_SESSION['cart'][$productId]);
            $_SESSION['message'] = "Product removed from cart.";
        }
    }
}

$cart = $_SESSION['cart'] ?? [];
$cartItems = [];
$total = 0;

if (!empty($cart)) {
    $placeholders = implode(',', array_fill(0, count(array_keys($cart)), '?'));
    $sql = "SELECT id, name, prix, lien FROM produit WHERE id IN ($placeholders)";
    $stmt = $conn->prepare($sql);
    $stmt->execute(array_keys($cart));
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($products as $product) {
        $quantity = $cart[$product['id']];
        $subtotal = $product['prix'] * $quantity;
        $total += $subtotal;
        $cartItems[] = [
            'id' => $product['id'],
            'name' => $product['name'],
            'price' => $product['prix'],
            'quantity' => $quantity,
            'subtotal' => $subtotal,
            'image' => $product['lien']
        ];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart - CasawiTech</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/ScrollTrigger.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
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
        .cart-item {
            opacity: 0;
            transform: translateX(-20px);
            transition: all 0.3s ease;
        }
        .cart-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        .quantity-input {
            transition: all 0.3s ease;
        }
        .quantity-input:focus {
            transform: scale(1.05);
        }
        .remove-button {
            transition: all 0.3s ease;
        }
        .remove-button:hover {
            background-color: rgba(255, 0, 51, 0.1);
            color: #ff0033;
        }
        .checkout-button {
            position: relative;
            overflow: hidden;
        }
        .checkout-button::after {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: 0.5s;
        }
        .checkout-button:hover::after {
            left: 100%;
        }
        .summary-card {
            opacity: 0;
            transform: translateY(20px);
        }
        .empty-cart {
            opacity: 0;
            transform: scale(0.95);
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>

    <main class="container mx-auto px-4 py-12">
        <!-- Breadcrumb -->
        <div class="flex items-center space-x-2 mb-8 text-primary-gray">
            <a href="/" class="hover:text-primary-accent transition-colors duration-250">Home</a>
            <span>/</span>
            <span class="text-primary-text">Shopping Cart</span>
        </div>

        <?php if (isset($_SESSION['message'])): ?>
            <div class="mb-8 p-4 bg-green-100 text-green-700 rounded-lg">
                <?= $_SESSION['message'] ?>
                <?php unset($_SESSION['message']); ?>
            </div>
        <?php endif; ?>

        <?php if (empty($cartItems)): ?>
            <div class="empty-cart bg-primary-light rounded-lg shadow-lg p-12 text-center">
                <i class="fas fa-shopping-cart text-6xl text-primary-accent mb-6"></i>
                <h2 class="text-2xl font-bold text-primary-text mb-4">Your cart is empty</h2>
                <p class="text-primary-gray mb-8">Looks like you haven't added any items to your cart yet.</p>
                <a href="products.php" 
                   class="inline-block bg-primary-accent hover:bg-opacity-90 text-white py-3 px-8 rounded transition-colors duration-250">
                    Start Shopping
                </a>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <div class="lg:col-span-2">
                    <div class="bg-primary-bg rounded-lg shadow-lg overflow-hidden">
                        <div class="p-6 border-b border-primary-light">
                            <h2 class="text-2xl font-bold text-primary-text">Shopping Cart</h2>
                        </div>
                        <div class="divide-y divide-primary-light">
                            <?php foreach ($cartItems as $item): ?>
                                <div class="cart-item p-6 bg-primary-bg">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 w-24 h-24">
                                            <img src="<?= htmlspecialchars($item['image']) ?>" 
                                                 alt="<?= htmlspecialchars($item['name']) ?>" 
                                                 class="w-full h-full object-cover rounded-lg">
                                        </div>
                                        <div class="ml-6 flex-1">
                                            <div class="flex items-center justify-between">
                                                <h3 class="text-lg font-semibold text-primary-text">
                                                    <?= htmlspecialchars($item['name']) ?>
                                                </h3>
                                                <span class="text-lg font-bold text-primary-accent">
                                                    <?= number_format($item['subtotal'], 2) ?> DHs
                                                </span>
                                            </div>
                                            <div class="mt-2 flex items-center justify-between">
                                                <div class="flex items-center space-x-4">
                                                    <input type="number" 
                                                           value="<?= $item['quantity'] ?>" 
                                                           min="1" 
                                                           max="99" 
                                                           class="quantity-input w-20 px-3 py-2 bg-primary-light border border-primary-gray/20 rounded text-primary-text focus:outline-none focus:border-primary-accent transition-colors duration-250" 
                                                           data-product-id="<?= $item['id'] ?>">
                                                    <span class="text-primary-gray">× <?= number_format($item['price'], 2) ?> DHs</span>
                                                </div>
                                                <form action="cart.php" method="POST" class="ml-4">
                                                    <input type="hidden" name="product_id" value="<?= $item['id'] ?>">
                                                    <input type="hidden" name="action" value="remove">
                                                    <button type="submit" 
                                                            class="remove-button p-2 rounded-full text-primary-gray hover:text-primary-accent transition-colors duration-250">
                                                        <i class="fas fa-trash-alt"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <div class="lg:col-span-1">
                    <div class="summary-card bg-primary-bg rounded-lg shadow-lg p-6">
                        <h2 class="text-xl font-bold text-primary-text mb-6">Order Summary</h2>
                        <div class="space-y-4">
                            <div class="flex justify-between text-primary-gray">
                                <span>Subtotal</span>
                                <span id="cartTotal"><?= number_format($total, 2) ?> DHs</span>
                            </div>
                            <div class="flex justify-between text-primary-gray">
                                <span>Shipping</span>
                                <span>Free</span>
                            </div>
                            <div class="border-t border-primary-light pt-4">
                                <div class="flex justify-between text-xl font-bold">
                                    <span class="text-primary-text">Total</span>
                                    <span id="finalTotal" class="text-primary-accent"><?= number_format($total, 2) ?> DHs</span>
                                </div>
                            </div>
                        </div>
                        <div class="mt-8 space-y-4">
                            <a href="checkout.php" 
                               class="checkout-button block w-full bg-primary-accent hover:bg-opacity-90 text-white text-center py-3 px-4 rounded transition-colors duration-250">
                                Proceed to Checkout
                            </a>
                            <a href="products.php" 
                               class="block w-full bg-primary-light text-primary-text text-center py-3 px-4 rounded transition-colors duration-250 hover:bg-primary-light/70">
                                Continue Shopping
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </main>

    <?php include 'footer.php'; ?>

    <script>
        // Initialize GSAP
        gsap.registerPlugin(ScrollTrigger);

        // Empty cart animation
        if (document.querySelector('.empty-cart')) {
            gsap.to('.empty-cart', {
                opacity: 1,
                scale: 1,
                duration: 0.8,
                ease: 'power3.out'
            });
        }

        // Cart items staggered animation
        gsap.to('.cart-item', {
            opacity: 1,
            x: 0,
            duration: 0.6,
            stagger: 0.2,
            ease: 'power3.out'
        });

        // Summary card animation
        gsap.to('.summary-card', {
            opacity: 1,
            y: 0,
            duration: 0.8,
            delay: 0.4,
            ease: 'power3.out'
        });

        // Quantity update with AJAX
        $('.quantity-input').on('change', function() {
            const productId = $(this).data('product-id');
            const quantity = $(this).val();
            const $button = $(this);
            
            // Add loading state
            $button.prop('disabled', true);
            
            $.ajax({
                url: 'cart.php',
                method: 'POST',
                data: {
                    action: 'update',
                    product_id: productId,
                    quantity: quantity
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        // Animate the price update
                        const $subtotal = $(`td.subtotal[data-product-id="${productId}"]`);
                        const $total = $('#cartTotal, #finalTotal');
                        
                        gsap.to($subtotal, {
                            opacity: 0,
                            y: -10,
                            duration: 0.3,
                            onComplete: function() {
                                $subtotal.text(response.subtotal + ' DHs');
                                gsap.to($subtotal, {
                                    opacity: 1,
                                    y: 0,
                                    duration: 0.3
                                });
                            }
                        });

                        gsap.to($total, {
                            opacity: 0,
                            scale: 0.95,
                            duration: 0.3,
                            onComplete: function() {
                                $total.text(response.total + ' DHs');
                                gsap.to($total, {
                                    opacity: 1,
                                    scale: 1,
                                    duration: 0.3
                                });
                            }
                        });
                    }
                },
                error: function() {
                    alert('An error occurred while updating the cart.');
                },
                complete: function() {
                    // Remove loading state
                    $button.prop('disabled', false);
                }
            });
        });
    </script>
</body>
</html>