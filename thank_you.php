<?php
session_start();
$isLoggedIn = isset($_SESSION['user_id']);
if (!$isLoggedIn) {
  header("Location: login.php");
  exit;
}

require_once 'config.php';
$conn = getPDO();

$orderId = $_GET['order_id'] ?? null;
if (!$orderId) {
    header("Location: products.php");
    exit;
}

// Fetch order details
$stmt = $conn->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ?");
$stmt->execute([$orderId, $_SESSION['user_id']]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    header("Location: products.php");
    exit;
}

// Fetch order items
$stmt = $conn->prepare("SELECT oi.*, p.name FROM order_items oi JOIN produit p ON oi.product_id = p.id WHERE oi.order_id = ?");
$stmt->execute([$orderId]);
$orderItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmation - CasawiTech</title>
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
        .success-icon {
            opacity: 0;
            transform: scale(0.5);
        }
        .success-message {
            opacity: 0;
            transform: translateY(20px);
        }
        .order-details {
            opacity: 0;
            transform: translateX(-20px);
        }
        .order-item {
            opacity: 0;
            transform: translateY(10px);
            transition: all 0.3s ease;
        }
        .order-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        .action-button {
            position: relative;
            overflow: hidden;
        }
        .action-button::after {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: 0.5s;
        }
        .action-button:hover::after {
            left: 100%;
        }
        @keyframes checkmark {
            0% {
                stroke-dashoffset: 100;
            }
            100% {
                stroke-dashoffset: 0;
            }
        }
        .checkmark {
            stroke-dasharray: 100;
            stroke-dashoffset: 100;
            animation: checkmark 1s ease-in-out forwards;
            animation-delay: 0.5s;
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
            <a href="checkout.php" class="hover:text-primary-accent transition-colors duration-250">Checkout</a>
            <span>/</span>
            <span class="text-primary-text">Confirmation</span>
        </div>

        <div class="max-w-4xl mx-auto">
            <div class="bg-primary-bg rounded-lg shadow-lg overflow-hidden">
                <div class="p-8 text-center border-b border-primary-light">
                    <div class="success-icon inline-flex items-center justify-center w-20 h-20 rounded-full bg-green-100 mb-6">
                        <svg class="w-10 h-10 text-green-500" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path class="checkmark" d="M5 13l4 4L19 7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </div>
                    <div class="success-message">
                        <h2 class="text-2xl font-bold text-primary-text mb-4">Thank You for Your Order!</h2>
                        <p class="text-primary-gray mb-4">Your order has been successfully placed and will be processed shortly.</p>
                        <div class="inline-flex items-center space-x-2 text-primary-accent">
                            <span>Order ID:</span>
                            <span class="font-mono font-bold"><?= htmlspecialchars($order['id']) ?></span>
                        </div>
                    </div>
                </div>

                <div class="p-6 space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="order-details">
                            <h3 class="text-lg font-semibold text-primary-text mb-4">
                                Order Details
                            </h3>
                            <div class="space-y-2 text-primary-gray">
                                <p>Order Date: <span class="text-primary-text"><?= htmlspecialchars($order['created_at']) ?></span></p>
                                <p>Status: <span class="text-primary-text"><?= ucfirst(htmlspecialchars($order['status'])) ?></span></p>
                                <p>Payment Method: <span class="text-primary-text">Cash on Delivery</span></p>
                            </div>
                        </div>
                        <div class="order-details">
                            <h3 class="text-lg font-semibold text-primary-text mb-4">
                                Shipping Information
                            </h3>
                            <p class="text-primary-gray">Your order will be delivered to the address you provided during checkout.</p>
                        </div>
                    </div>

                    <div class="border-t border-primary-light pt-6">
                        <h3 class="text-lg font-semibold text-primary-text mb-4">
                            Order Summary
                        </h3>
                        <div class="bg-primary-light rounded-lg overflow-hidden">
                            <table class="w-full">
                                <thead>
                                    <tr class="border-b border-primary-gray/10">
                                        <th class="px-6 py-4 text-left text-sm font-medium text-primary-gray">Product</th>
                                        <th class="px-6 py-4 text-left text-sm font-medium text-primary-gray">Quantity</th>
                                        <th class="px-6 py-4 text-left text-sm font-medium text-primary-gray">Price</th>
                                        <th class="px-6 py-4 text-left text-sm font-medium text-primary-gray">Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($orderItems as $item): ?>
                                        <tr class="order-item border-b border-primary-gray/10">
                                            <td class="px-6 py-4"><?= htmlspecialchars($item['name']) ?></td>
                                            <td class="px-6 py-4"><?= htmlspecialchars($item['quantity']) ?></td>
                                            <td class="px-6 py-4"><?= number_format($item['price'], 2) ?> DHs</td>
                                            <td class="px-6 py-4"><?= number_format($item['price'] * $item['quantity'], 2) ?> DHs</td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot>
                                    <tr class="bg-primary-bg">
                                        <td colspan="3" class="px-6 py-4 text-right font-bold text-primary-text">Total:</td>
                                        <td class="px-6 py-4 font-bold text-primary-accent"><?= number_format($order['total_amount'], 2) ?> DHs</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>

                    <div class="flex flex-col sm:flex-row items-center justify-center gap-4 pt-8 border-t border-primary-light">
                        <a href="track_order.php" 
                           class="action-button w-full sm:w-auto bg-primary-accent hover:bg-opacity-90 text-white text-center py-3 px-8 rounded transition-colors duration-250">
                            Track Your Order
                        </a>
                        <a href="products.php" 
                           class="w-full sm:w-auto bg-primary-light text-primary-text text-center py-3 px-8 rounded transition-colors duration-250 hover:bg-primary-light/70">
                            Continue Shopping
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <?php include 'footer.php'; ?>

    <script>
        // Initialize GSAP
        gsap.registerPlugin(ScrollTrigger);

        // Success icon animation
        gsap.to('.success-icon', {
            opacity: 1,
            scale: 1,
            duration: 0.6,
            ease: 'back.out(1.7)'
        });

        // Success message animation
        gsap.to('.success-message', {
            opacity: 1,
            y: 0,
            duration: 0.8,
            delay: 0.3,
            ease: 'power3.out'
        });

        // Order details sections animation
        gsap.to('.order-details', {
            opacity: 1,
            x: 0,
            duration: 0.8,
            stagger: 0.2,
            delay: 0.6,
            ease: 'power3.out'
        });

        // Order items staggered animation
        gsap.to('.order-item', {
            opacity: 1,
            y: 0,
            duration: 0.6,
            stagger: 0.1,
            delay: 0.8,
            ease: 'power3.out'
        });
    </script>
</body>
</html>

