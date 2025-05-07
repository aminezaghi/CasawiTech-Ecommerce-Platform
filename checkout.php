<?php
session_start();
$isLoggedIn = isset($_SESSION['user_id']);
if (!$isLoggedIn) {
  header("Location: login.php");
  exit;
}

require_once 'config.php';
$conn = getPDO();

// Fetch all categories for the navigation
$categoryStmt = $conn->query("SELECT DISTINCT category FROM produit ORDER BY category");
$categories = $categoryStmt->fetchAll(PDO::FETCH_COLUMN);

$cart = $_SESSION['cart'] ?? [];
$cartItems = [];
$total = 0;

if (empty($cart)) {
    header("Location: cart.php");
    exit;
}

// Fetch cart items
if (!empty($cart)) {
    $placeholders = str_repeat('?,', count($cart) - 1) . '?';
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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Process the order
    $conn->beginTransaction();

    try {
        // Validate shipping information
        $name = trim($_POST['name']);
        $email = trim($_POST['email']);
        $address = trim($_POST['address']);
        $city = trim($_POST['city']);
        $postal_code = trim($_POST['postal_code']);
        $country = trim($_POST['country']);

        if (empty($name) || empty($email) || empty($address) || empty($city) || empty($postal_code) || empty($country)) {
            throw new Exception("All shipping information fields are required.");
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Invalid email address.");
        }

        // Insert into orders table
        $stmt = $conn->prepare("INSERT INTO orders (user_id, total_amount, status, created_at) VALUES (?, ?, 'pending', NOW())");
        $stmt->execute([$_SESSION['user_id'], $total]);
        $orderId = $conn->lastInsertId();

        // Insert order items and update stock
        $stmt = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
        $updateStockStmt = $conn->prepare("UPDATE produit SET stock_quantity = stock_quantity - ? WHERE id = ?");
        
        foreach ($cartItems as $item) {
            // Check stock availability
            $stockStmt = $conn->prepare("SELECT stock_quantity FROM produit WHERE id = ?");
            $stockStmt->execute([$item['id']]);
            $currentStock = $stockStmt->fetchColumn();

            if ($currentStock < $item['quantity']) {
                throw new Exception("Not enough stock for product: " . $item['name']);
            }

            // Insert order item
            $stmt->execute([$orderId, $item['id'], $item['quantity'], $item['price']]);
            
            // Update stock
            $updateStockStmt->execute([$item['quantity'], $item['id']]);
        }

        // Insert shipping information
        $stmt = $conn->prepare("INSERT INTO delivery_info (order_id, name, email, address, city, postal_code, country) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$orderId, $name, $email, $address, $city, $postal_code, $country]);

        // Clear the cart
        unset($_SESSION['cart']);

        $conn->commit();
        header("Location: thank_you.php?order_id=" . $orderId);
        exit;
    } catch (Exception $e) {
        $conn->rollback();
        $error = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - CasawiTech</title>
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
        .checkout-section {
            opacity: 0;
            transform: translateY(20px);
        }
        .order-item {
            opacity: 0;
            transform: translateX(-20px);
            transition: all 0.3s ease;
        }
        .order-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        .form-input {
            transition: all 0.3s ease;
        }
        .form-input:focus {
            transform: scale(1.02);
        }
        .place-order-button {
            position: relative;
            overflow: hidden;
        }
        .place-order-button::after {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: 0.5s;
        }
        .place-order-button:hover::after {
            left: 100%;
        }
        .step-indicator {
            z-index: 10;
        }
        .step-label {
            display: block;
            margin-top: 0.5rem;
            font-size: 0.95rem;
            font-weight: 600;
            color: #111111;
            text-align: center;
            z-index: 20;
        }
        .step-label.active {
            color: #ff0033;
        }
        .step-label.inactive {
            color: #888888;
        }
        .progress-bar {
            z-index: 1;
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
            <a href="cart.php" class="hover:text-primary-accent transition-colors duration-250">Cart</a>
            <span>/</span>
            <span class="text-primary-text">Checkout</span>
        </div>

        <!-- Progress Steps -->
        <div class="mb-12 flex justify-center">
            <div class="relative w-full max-w-3xl">
                <!-- Progress Bar -->
                <div class="absolute top-1/2 left-0 right-0 h-1 bg-primary-light -translate-y-1/2">
                    <div class="h-full w-2/3 bg-primary-accent rounded-full progress-bar"></div>
                </div>
                <div class="flex justify-between items-center relative z-10">
                    <!-- Cart Step -->
                    <div class="step-indicator flex flex-col items-center">
                        <div class="w-10 h-10 rounded-full bg-primary-accent text-white flex items-center justify-center">
                            <i class="fas fa-shopping-cart"></i>
                        </div>
                        <span class="step-label active">Cart</span>
                    </div>
                    <!-- Checkout Step -->
                    <div class="step-indicator flex flex-col items-center">
                        <div class="w-10 h-10 rounded-full bg-primary-accent text-white flex items-center justify-center">
                            <i class="fas fa-credit-card"></i>
                        </div>
                        <span class="step-label active">Checkout</span>
                    </div>
                    <!-- Confirmation Step -->
                    <div class="step-indicator flex flex-col items-center">
                        <div class="w-10 h-10 rounded-full bg-primary-light text-primary-gray flex items-center justify-center">
                            <i class="fas fa-check"></i>
                        </div>
                        <span class="step-label inactive">Confirmation</span>
                    </div>
                </div>
            </div>
        </div>

        <?php if (isset($error)): ?>
            <div class="mb-8 p-4 bg-red-100 text-red-700 rounded-lg">
                <?= $error ?>
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Order Summary -->
            <div class="lg:col-span-2">
                <div class="checkout-section bg-primary-bg rounded-lg shadow-lg overflow-hidden mb-8">
                    <div class="p-6 border-b border-primary-light">
                        <h2 class="text-2xl font-bold text-primary-text">Order Summary</h2>
                    </div>
                    <div class="divide-y divide-primary-light">
                        <?php foreach ($cartItems as $item): ?>
                            <div class="order-item p-6">
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
                                        <div class="mt-2 text-primary-gray">
                                            <?= $item['quantity'] ?> × <?= number_format($item['price'], 2) ?> DHs
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Shipping Information -->
                <div class="checkout-section bg-primary-bg rounded-lg shadow-lg overflow-hidden">
                    <div class="p-6 border-b border-primary-light">
                        <h2 class="text-2xl font-bold text-primary-text">Shipping Information</h2>
                    </div>
                    <form method="POST" class="p-6 space-y-6" id="checkoutForm">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="name" class="block text-sm font-medium text-primary-gray mb-2">Full Name</label>
                                <input type="text" id="name" name="name" required 
                                    class="form-input w-full px-4 py-2 bg-primary-light border border-primary-gray/20 rounded text-primary-text placeholder-primary-gray focus:outline-none focus:border-primary-accent transition-colors duration-250"
                                    placeholder="Enter your full name">
                            </div>
                            <div>
                                <label for="email" class="block text-sm font-medium text-primary-gray mb-2">Email Address</label>
                                <input type="email" id="email" name="email" required 
                                    class="form-input w-full px-4 py-2 bg-primary-light border border-primary-gray/20 rounded text-primary-text placeholder-primary-gray focus:outline-none focus:border-primary-accent transition-colors duration-250"
                                    placeholder="Enter your email">
                            </div>
                        </div>
                        <div>
                            <label for="address" class="block text-sm font-medium text-primary-gray mb-2">Address</label>
                            <input type="text" id="address" name="address" required 
                                class="form-input w-full px-4 py-2 bg-primary-light border border-primary-gray/20 rounded text-primary-text placeholder-primary-gray focus:outline-none focus:border-primary-accent transition-colors duration-250"
                                placeholder="Enter your shipping address">
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div>
                                <label for="city" class="block text-sm font-medium text-primary-gray mb-2">City</label>
                                <input type="text" id="city" name="city" required 
                                    class="form-input w-full px-4 py-2 bg-primary-light border border-primary-gray/20 rounded text-primary-text placeholder-primary-gray focus:outline-none focus:border-primary-accent transition-colors duration-250"
                                    placeholder="Enter your city">
                            </div>
                            <div>
                                <label for="postal_code" class="block text-sm font-medium text-primary-gray mb-2">Postal Code</label>
                                <input type="text" id="postal_code" name="postal_code" required 
                                    class="form-input w-full px-4 py-2 bg-primary-light border border-primary-gray/20 rounded text-primary-text placeholder-primary-gray focus:outline-none focus:border-primary-accent transition-colors duration-250"
                                    placeholder="Enter postal code">
                            </div>
                            <div>
                                <label for="country" class="block text-sm font-medium text-primary-gray mb-2">Country</label>
                                <input type="text" id="country" name="country" required 
                                    class="form-input w-full px-4 py-2 bg-primary-light border border-primary-gray/20 rounded text-primary-text placeholder-primary-gray focus:outline-none focus:border-primary-accent transition-colors duration-250"
                                    placeholder="Enter your country">
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Order Total -->
            <div class="lg:col-span-1">
                <div class="checkout-section bg-primary-bg rounded-lg shadow-lg p-6 sticky top-24">
                    <h2 class="text-xl font-bold text-primary-text mb-6">Order Total</h2>
                    <div class="space-y-4">
                        <div class="flex justify-between text-primary-gray">
                            <span>Subtotal</span>
                            <span><?= number_format($total, 2) ?> DHs</span>
                        </div>
                        <div class="flex justify-between text-primary-gray">
                            <span>Shipping</span>
                            <span>Free</span>
                        </div>
                        <div class="border-t border-primary-light pt-4">
                            <div class="flex justify-between text-xl font-bold">
                                <span class="text-primary-text">Total</span>
                                <span class="text-primary-accent"><?= number_format($total, 2) ?> DHs</span>
                            </div>
                            <p class="text-sm text-primary-gray mt-2">Including VAT</p>
                        </div>
                    </div>
                    <button type="submit" form="checkoutForm"
                            class="place-order-button w-full bg-primary-accent hover:bg-opacity-90 text-white py-4 px-6 rounded-lg font-semibold transition-colors duration-250 mt-6">
                        <i class="fas fa-lock mr-2"></i>
                        Place Order
                    </button>
                    <a href="cart.php" 
                       class="block w-full text-center mt-4 text-primary-gray hover:text-primary-accent transition-colors duration-250">
                        Return to Cart
                    </a>
                </div>
            </div>
        </div>
    </main>

    <?php include 'footer.php'; ?>

    <script>
        // Initialize GSAP
        gsap.registerPlugin(ScrollTrigger);

        // Checkout sections animation
        gsap.to('.checkout-section', {
            opacity: 1,
            y: 0,
            duration: 0.8,
            stagger: 0.2,
            ease: 'power3.out'
        });

        // Order items animation
        gsap.to('.order-item', {
            opacity: 1,
            x: 0,
            duration: 0.6,
            stagger: 0.1,
            ease: 'power3.out',
            delay: 0.4
        });

        // Form validation and submission
        document.getElementById('checkoutForm').addEventListener('submit', function(e) {
            const button = document.querySelector('.place-order-button');
            const originalText = button.innerHTML;
            button.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Processing...';
            button.disabled = true;
        });
    </script>
</body>
</html>
