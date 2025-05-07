<?php
session_start();
$isLoggedIn = isset($_SESSION['user_id']);
if (!$isLoggedIn) {
    header("Location: login.php");
    exit;
}

require_once 'config.php';
$conn = getPDO();

try {
    // Fetch all orders for the current user
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
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($orders)) {
        $error = "No orders found for your account.";
    }
} catch (PDOException $e) {
    $error = "Connection failed: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Track Orders - CasawiTech</title>
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
        .order-card {
            opacity: 0;
            transform: translateY(20px);
            transition: all 0.3s ease;
        }
        .order-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        .tracking-step {
            opacity: 0;
            transform: translateX(-20px);
        }
        .tracking-line {
            height: 0;
            transition: height 1s ease;
        }
        .tracking-dot {
            transform: scale(0);
        }
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }
        .status-badge {
            transition: all 0.3s ease;
        }
        .status-badge:hover {
            transform: scale(1.1);
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>

    <main class="container mx-auto px-4 py-12">
        <?php if (isset($error)): ?>
            <div class="bg-red-100 text-red-700 p-4 rounded-lg mb-8">
                <?= $error ?>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 gap-6">
                <?php foreach ($orders as $order): ?>
                    <div class="order-card bg-primary-bg rounded-lg shadow-lg overflow-hidden">
                        <div class="p-6">
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
                                <span class="status-badge inline-flex items-center px-3 py-1 rounded-full <?= $colors['bg'] ?> <?= $colors['text'] ?>">
                                    <i class="fas fa-<?= $colors['icon'] ?> mr-1"></i>
                                    <?= ucfirst($order['status']) ?>
                                </span>
                            </div>

                            <div class="space-y-4">
                                <div>
                                    <h4 class="text-sm font-medium text-primary-gray">Items</h4>
                                    <p class="text-primary-text mt-1"><?= htmlspecialchars($order['items_list']) ?></p>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-primary-gray">Total Amount</span>
                                    <span class="text-xl font-bold text-primary-accent"><?= number_format($order['total_amount'], 2) ?> DHs</span>
                                </div>
                            </div>

                            <!-- Tracking Timeline -->
                            <div class="mt-8 relative">
                                <div class="absolute left-8 top-0 bottom-0 w-px bg-primary-light tracking-line"></div>
                                <?php
                                $steps = [
                                    ['status' => 'pending', 'icon' => 'shopping-cart', 'title' => 'Order Placed', 'date' => $order['created_at']],
                                    ['status' => 'processing', 'icon' => 'cog', 'title' => 'Processing', 'date' => null],
                                    ['status' => 'shipped', 'icon' => 'truck', 'title' => 'Shipped', 'date' => null],
                                    ['status' => 'delivered', 'icon' => 'check-circle', 'title' => 'Delivered', 'date' => null]
                                ];

                                $currentStepIndex = array_search($order['status'], array_column($steps, 'status'));
                                foreach ($steps as $index => $step):
                                ?>
                                    <div class="tracking-step relative flex items-start mb-8 last:mb-0">
                                        <div class="absolute left-8 top-2 w-4 h-4 -ml-2 rounded-full border-2 
                                            <?= $index <= $currentStepIndex ? 
                                                'bg-primary-accent border-primary-accent' : 
                                                'bg-primary-light border-primary-gray/20' ?> tracking-dot">
                                        </div>
                                        <div class="ml-16">
                                            <div class="flex items-center">
                                                <div class="w-10 h-10 rounded-lg 
                                                    <?= $index <= $currentStepIndex ? 
                                                        'bg-primary-accent text-white' : 
                                                        'bg-primary-light text-primary-gray' ?> 
                                                    flex items-center justify-center">
                                                    <i class="fas fa-<?= $step['icon'] ?>"></i>
                                                </div>
                                                <div class="ml-4">
                                                    <h4 class="text-lg font-semibold text-primary-text"><?= $step['title'] ?></h4>
                                                    <p class="text-primary-gray">
                                                        <?= $step['date'] ? date('F j, Y g:i A', strtotime($step['date'])) : 'Pending' ?>
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>

    <?php include 'footer.php'; ?>

    <script>
        // Initialize GSAP
        gsap.registerPlugin(ScrollTrigger);

        // Order cards animation
        gsap.to('.order-card', {
            opacity: 1,
            y: 0,
            duration: 0.6,
            stagger: 0.2,
            ease: 'power3.out'
        });

        // Tracking steps animation
        gsap.to('.tracking-step', {
            opacity: 1,
            x: 0,
            duration: 0.6,
            stagger: 0.2,
            ease: 'power3.out',
            delay: 0.4
        });

        // Tracking line animation
        gsap.to('.tracking-line', {
            height: '100%',
            duration: 1,
            ease: 'power3.inOut',
            delay: 0.6
        });

        // Tracking dots animation
        gsap.to('.tracking-dot', {
            scale: 1,
            duration: 0.4,
            stagger: 0.2,
            ease: 'back.out(1.7)',
            delay: 0.8
        });
    </script>
</body>
</html>
