<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

require_once 'config.php';
$conn = getPDO();

try {
    // Fetch user information
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // Fetch recent orders
    $stmt = $conn->prepare("
        SELECT o.*, 
               COUNT(oi.id) as total_items,
               GROUP_CONCAT(CONCAT(p.name, ' (', oi.quantity, ')') SEPARATOR ', ') as items_list
        FROM orders o
        LEFT JOIN order_items oi ON o.id = oi.order_id
        LEFT JOIN produit p ON oi.product_id = p.id
        WHERE o.user_id = ?
        GROUP BY o.id
        ORDER BY o.created_at DESC
        LIMIT 5
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $recent_orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Calculate total spent
    $stmt = $conn->prepare("SELECT SUM(total_amount) as total_spent FROM orders WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $total_spent = $stmt->fetchColumn() ?: 0;

    // Get order count
    $stmt = $conn->prepare("SELECT COUNT(*) FROM orders WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $order_count = $stmt->fetchColumn();

} catch(PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Dashboard - CasawiTech</title>
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
        .dashboard-card {
            opacity: 0;
            transform: translateY(20px);
            transition: all 0.3s ease;
        }
        .dashboard-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        .stat-card {
            opacity: 0;
            transform: translateX(-20px);
            transition: all 0.3s ease;
        }
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        .order-item {
            opacity: 0;
            transform: translateY(10px);
            transition: all 0.3s ease;
        }
        .order-item:hover {
            transform: translateX(5px);
            background-color: rgba(255, 0, 51, 0.05);
        }
        @keyframes float {
            0% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
            100% { transform: translateY(0px); }
        }
        .floating-icon {
            animation: float 3s ease-in-out infinite;
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
                <h1 class="text-2xl font-bold text-primary-text">My Dashboard</h1>
                <div class="flex items-center space-x-4">
                    <a href="products.php" class="text-primary-gray hover:text-primary-accent transition-colors duration-250">
                        <i class="fas fa-store"></i>
                        <span class="ml-2">Store</span>
                    </a>
                    <a href="cart.php" class="text-primary-gray hover:text-primary-accent transition-colors duration-250">
                        <i class="fas fa-shopping-cart"></i>
                        <span class="ml-2">Cart</span>
                    </a>
                    <a href="logout.php" class="text-primary-gray hover:text-primary-accent transition-colors duration-250">
                        <i class="fas fa-sign-out-alt"></i>
                        <span class="ml-2">Logout</span>
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <main class="container mx-auto px-4 py-12">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- User Profile -->
            <div class="lg:col-span-1">
                <div class="dashboard-card bg-primary-bg rounded-2xl shadow-lg overflow-hidden">
                    <div class="p-8">
                        <div class="text-center">
                            <div class="w-32 h-32 mx-auto bg-primary-light rounded-full flex items-center justify-center mb-4">
                                <i class="fas fa-user-circle text-6xl text-primary-accent"></i>
                            </div>
                            <h2 class="text-2xl font-bold text-primary-text"><?= htmlspecialchars($user['username']) ?></h2>
                            <p class="text-primary-gray mt-2"><?= htmlspecialchars($user['email']) ?></p>
                            <p class="text-primary-gray mt-1">Member since <?= date('F Y', strtotime($user['created_at'])) ?></p>
                        </div>

                        <div class="mt-8 space-y-4">
                            <div class="flex items-center justify-between p-4 bg-primary-light rounded-lg">
                                <span class="text-primary-gray">Total Orders</span>
                                <span class="text-xl font-bold text-primary-text"><?= $order_count ?></span>
                            </div>
                            <div class="flex items-center justify-between p-4 bg-primary-light rounded-lg">
                                <span class="text-primary-gray">Total Spent</span>
                                <span class="text-xl font-bold text-primary-accent"><?= number_format($total_spent, 2) ?> DHs</span>
                            </div>
                        </div>

                        <div class="mt-8">
                            <a href="track_order.php" 
                               class="block w-full bg-primary-accent hover:bg-opacity-90 text-white text-center py-3 px-4 rounded-lg transition-colors duration-250">
                                <i class="fas fa-truck mr-2"></i>
                                Track Orders
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main Content -->
            <div class="lg:col-span-2">
                <!-- Quick Stats -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                    <div class="stat-card bg-primary-bg rounded-xl shadow-lg p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-primary-gray">Active Orders</p>
                                <h3 class="text-2xl font-bold text-primary-text mt-2">
                                    <?= array_reduce($recent_orders, function($count, $order) {
                                        return $count + ($order['status'] !== 'delivered' ? 1 : 0);
                                    }, 0) ?>
                                </h3>
                            </div>
                            <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center">
                                <i class="fas fa-shipping-fast text-blue-500"></i>
                            </div>
                        </div>
                    </div>
                    <div class="stat-card bg-primary-bg rounded-xl shadow-lg p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-primary-gray">Completed Orders</p>
                                <h3 class="text-2xl font-bold text-primary-text mt-2">
                                    <?= array_reduce($recent_orders, function($count, $order) {
                                        return $count + ($order['status'] === 'delivered' ? 1 : 0);
                                    }, 0) ?>
                                </h3>
                            </div>
                            <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center">
                                <i class="fas fa-check-circle text-green-500"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Orders -->
                <div class="dashboard-card bg-primary-bg rounded-xl shadow-lg overflow-hidden">
                    <div class="p-6 border-b border-primary-light">
                        <div class="flex items-center justify-between">
                            <h2 class="text-xl font-bold text-primary-text">Recent Orders</h2>
                            <a href="track_order.php" class="text-primary-accent hover:text-primary-accent/80 transition-colors duration-250">
                                View All
                            </a>
                        </div>
                    </div>
                    <div class="p-6">
                        <?php if (empty($recent_orders)): ?>
                            <div class="text-center py-8">
                                <div class="w-16 h-16 mx-auto bg-primary-light rounded-full flex items-center justify-center mb-4">
                                    <i class="fas fa-shopping-bag text-2xl text-primary-accent"></i>
                                </div>
                                <p class="text-primary-gray">No orders yet</p>
                                <a href="products.php" class="text-primary-accent hover:text-primary-accent/80 transition-colors duration-250 mt-2 inline-block">
                                    Start Shopping
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="space-y-4">
                                <?php foreach ($recent_orders as $order): ?>
                                    <div class="order-item bg-primary-light rounded-lg p-6">
                                        <div class="flex items-center justify-between mb-4">
                                            <div>
                                                <h3 class="text-lg font-semibold text-primary-text">Order #<?= $order['id'] ?></h3>
                                                <p class="text-primary-gray mt-1"><?= date('F j, Y g:i A', strtotime($order['created_at'])) ?></p>
                                            </div>
                                            <?php
                                            $statusColors = [
                                                'pending' => ['bg' => 'bg-yellow-100', 'text' => 'text-yellow-700', 'icon' => 'clock'],
                                                'processing' => ['bg' => 'bg-blue-100', 'text' => 'text-blue-700', 'icon' => 'cog'],
                                                'shipped' => ['bg' => 'bg-indigo-100', 'text' => 'text-indigo-700', 'icon' => 'shipping-fast'],
                                                'delivered' => ['bg' => 'bg-green-100', 'text' => 'text-green-700', 'icon' => 'check-circle'],
                                                'cancelled' => ['bg' => 'bg-red-100', 'text' => 'text-red-700', 'icon' => 'times-circle']
                                            ];
                                            $colors = $statusColors[$order['status']] ?? ['bg' => 'bg-primary-light', 'text' => 'text-primary-gray', 'icon' => 'circle'];
                                            ?>
                                            <span class="inline-flex items-center px-3 py-1 rounded-full <?= $colors['bg'] ?> <?= $colors['text'] ?>">
                                                <i class="fas fa-<?= $colors['icon'] ?> mr-1"></i>
                                                <?= ucfirst($order['status']) ?>
                                            </span>
                                        </div>
                                        <div class="space-y-2">
                                            <div>
                                                <p class="text-sm text-primary-gray">Items</p>
                                                <p class="text-primary-text"><?= htmlspecialchars($order['items_list']) ?></p>
                                            </div>
                                            <div class="flex justify-between items-center pt-2">
                                                <span class="text-primary-gray">Total</span>
                                                <span class="text-lg font-bold text-primary-accent"><?= number_format($order['total_amount'], 2) ?> DHs</span>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script>
        // Initialize GSAP
        gsap.registerPlugin(ScrollTrigger);

        // Dashboard cards animation
        gsap.to('.dashboard-card', {
            opacity: 1,
            y: 0,
            duration: 0.6,
            stagger: 0.2,
            ease: 'power3.out'
        });

        // Stat cards animation
        gsap.to('.stat-card', {
            opacity: 1,
            x: 0,
            duration: 0.6,
            stagger: 0.2,
            ease: 'power3.out',
            delay: 0.4
        });

        // Order items animation
        gsap.to('.order-item', {
            opacity: 1,
            y: 0,
            duration: 0.6,
            stagger: 0.1,
            ease: 'power3.out',
            delay: 0.6
        });
    </script>
</body>
</html>