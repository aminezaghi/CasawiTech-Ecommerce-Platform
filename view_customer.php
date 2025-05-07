<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

require_once 'config.php';
$conn = getPDO();

$userId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$userId) {
    throw new Exception("Invalid user ID");
}

// Fetch user information
$stmt = $conn->prepare("
    SELECT u.*, 
    COUNT(DISTINCT o.id) as total_orders,
    SUM(o.total_amount) as total_spent,
    MAX(o.created_at) as last_order_date,
    MAX(lh.login_time) as last_login,
    CASE 
        WHEN MAX(lh.login_time) > DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 
        ELSE 0 
    END as active
    FROM users u
    LEFT JOIN orders o ON u.id = o.user_id
    LEFT JOIN login_history lh ON u.id = lh.user_id
    WHERE u.id = ?
    GROUP BY u.id
");
$stmt->execute([$userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    throw new Exception("User not found");
}

// Set default values if not present
$user['active'] = $user['active'] ?? 0;
$user['last_login'] = $user['last_login'] ?? null;
$user['total_spent'] = $user['total_spent'] ?? 0;
$user['total_orders'] = $user['total_orders'] ?? 0;

// Fetch user's orders
$stmt = $conn->prepare("
    SELECT o.*, 
           COUNT(DISTINCT oi.id) as item_count,
           GROUP_CONCAT(CONCAT(p.name, ' (', oi.quantity, ')') SEPARATOR ', ') as items_list
    FROM orders o
    LEFT JOIN order_items oi ON o.id = oi.order_id
    LEFT JOIN produit p ON oi.product_id = p.id
    WHERE o.user_id = ?
    GROUP BY o.id
    ORDER BY o.created_at DESC
");
$stmt->execute([$userId]);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch purchase history for the chart (last 6 months)
$stmt = $conn->prepare("
    SELECT 
        DATE_FORMAT(o.created_at, '%Y-%m') as month,
        SUM(o.total_amount) as amount
    FROM orders o
    WHERE o.user_id = ?
        AND o.created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(o.created_at, '%Y-%m')
    ORDER BY month ASC
");
$stmt->execute([$userId]);
$purchase_history = [];

// Initialize all months with 0
for ($i = 5; $i >= 0; $i--) {
    $month = date('Y-m', strtotime("-$i months"));
    $purchase_history[$month] = 0;
}

// Fill in actual data
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $purchase_history[$row['month']] = floatval($row['amount']);
}

// Format month labels for display
$formatted_history = [
    'labels' => array_map(function($month) {
        return date('M Y', strtotime($month));
    }, array_keys($purchase_history)),
    'data' => array_values($purchase_history)
];

// For each order, fetch detailed items
foreach ($orders as &$order) {
    $stmt = $conn->prepare("
        SELECT oi.*, p.name, p.prix as unit_price, p.lien as product_image 
        FROM order_items oi 
        JOIN produit p ON oi.product_id = p.id 
        WHERE oi.order_id = ?
    ");
    $stmt->execute([$order['id']]);
    $order['items'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Fetch user's recent activity (login history)
$stmt = $conn->prepare("
    SELECT login_time, ip_address 
    FROM login_history 
    WHERE user_id = ? 
    ORDER BY login_time DESC 
    LIMIT 5
");
$stmt->execute([$userId]);
$loginHistory = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Ensure $purchase_history is always defined
if (!isset($purchase_history)) $purchase_history = [];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Customer - CasawiTech</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/ScrollTrigger.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.7.0/chart.min.js"></script>
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
        .info-card {
            opacity: 0;
            transform: translateY(20px);
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
        .chart-container {
            opacity: 0;
            transform: scale(0.95);
        }
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }
        .activity-dot {
            animation: pulse 2s infinite;
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
                <h1 class="text-2xl font-bold text-primary-text">Customer Profile</h1>
                <a href="admin_customers.php" 
                   class="bg-primary-light text-primary-text px-4 py-2 rounded-lg hover:bg-primary-accent hover:text-white transition-colors duration-250">
                    <i class="fas fa-arrow-left mr-2"></i>
                    Back to Customers
                </a>
            </div>
        </div>
    </nav>

    <main class="container mx-auto px-4 py-12">
        <?php if (isset($error)): ?>
            <div class="info-card mb-8 p-4 bg-red-100 text-red-700 rounded-lg">
                <?php echo $error; ?>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Customer Information -->
                <div class="lg:col-span-1">
                    <div class="info-card bg-primary-bg rounded-lg shadow-lg overflow-hidden">
                        <div class="p-6 text-center border-b border-primary-light">
                            <div class="w-24 h-24 mx-auto bg-primary-light rounded-full flex items-center justify-center mb-4">
                                <span class="text-3xl font-bold text-primary-accent">
                                    <?= strtoupper(substr($user['username'], 0, 1)) ?>
                                </span>
                            </div>
                            <h2 class="text-xl font-bold text-primary-text"><?= htmlspecialchars($user['username']) ?></h2>
                            <p class="text-primary-gray"><?= htmlspecialchars($user['email']) ?></p>
                            <div class="mt-4 flex items-center justify-center">
                                <span class="inline-flex items-center px-3 py-1 rounded-full <?= $user['active'] ? 'bg-green-100 text-green-700' : 'bg-primary-light text-primary-gray' ?>">
                                    <span class="activity-dot w-2 h-2 rounded-full <?= $user['active'] ? 'bg-green-500' : 'bg-primary-gray' ?> mr-2"></span>
                                    <?= $user['active'] ? 'Active' : 'Inactive' ?>
                                </span>
                            </div>
                        </div>
                        <div class="p-6">
                            <div class="space-y-4">
                                <div>
                                    <p class="text-sm text-primary-gray">Member Since</p>
                                    <p class="font-medium text-primary-text">
                                        <?= date('F j, Y', strtotime($user['created_at'])) ?>
                                    </p>
                                </div>
                                <div>
                                    <p class="text-sm text-primary-gray">Last Login</p>
                                    <p class="font-medium text-primary-text">
                                        <?= $user['last_login'] ? date('F j, Y g:i A', strtotime($user['last_login'])) : 'Never' ?>
                                    </p>
                                </div>
                                <div>
                                    <p class="text-sm text-primary-gray">Account Type</p>
                                    <p class="font-medium text-primary-text">
                                        <?= ucfirst(htmlspecialchars($user['role'])) ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Stats -->
                    <div class="grid grid-cols-2 gap-4 mt-8">
                        <div class="stat-card bg-primary-bg rounded-lg shadow-lg p-6">
                            <div class="text-primary-accent mb-2">
                                <i class="fas fa-shopping-cart text-2xl"></i>
                            </div>
                            <p class="text-2xl font-bold text-primary-text"><?= $user['total_orders'] ?></p>
                            <p class="text-sm text-primary-gray">Total Orders</p>
                        </div>
                        <div class="stat-card bg-primary-bg rounded-lg shadow-lg p-6">
                            <div class="text-primary-accent mb-2">
                                <i class="fas fa-coins text-2xl"></i>
                            </div>
                            <p class="text-2xl font-bold text-primary-text"><?= number_format($user['total_spent'], 2) ?> DHs</p>
                            <p class="text-sm text-primary-gray">Total Spent</p>
                        </div>
                    </div>
                </div>

                <!-- Order History -->
                <div class="lg:col-span-2">
                    <div class="info-card bg-primary-bg rounded-lg shadow-lg overflow-hidden">
                        <div class="p-6 border-b border-primary-light">
                            <h2 class="text-xl font-bold text-primary-text">Order History</h2>
                        </div>
                        <div class="p-6">
                            <?php if (empty($orders)): ?>
                                <p class="text-primary-gray text-center py-8">No orders found for this customer.</p>
                            <?php else: ?>
                                <div class="space-y-6">
                                    <?php foreach ($orders as $order): ?>
                                        <div class="order-item bg-primary-light rounded-lg p-6">
                                            <div class="flex items-center justify-between mb-4">
                                                <div>
                                                    <p class="text-sm text-primary-gray">Order #<?= $order['id'] ?></p>
                                                    <p class="text-lg font-bold text-primary-accent"><?= number_format($order['total_amount'], 2) ?> DHs</p>
                                                    <p class="text-sm text-primary-gray"><?= date('F j, Y g:i A', strtotime($order['created_at'])) ?></p>
                                                </div>
                                                <?php
                                                $statusColors = [
                                                    'pending' => ['bg' => 'bg-yellow-100', 'text' => 'text-yellow-700'],
                                                    'processing' => ['bg' => 'bg-blue-100', 'text' => 'text-blue-700'],
                                                    'shipped' => ['bg' => 'bg-indigo-100', 'text' => 'text-indigo-700'],
                                                    'delivered' => ['bg' => 'bg-green-100', 'text' => 'text-green-700'],
                                                    'cancelled' => ['bg' => 'bg-red-100', 'text' => 'text-red-700']
                                                ];
                                                $colors = $statusColors[$order['status']] ?? ['bg' => 'bg-primary-light', 'text' => 'text-primary-gray'];
                                                ?>
                                                <span class="inline-flex items-center px-3 py-1 rounded-full <?= $colors['bg'] ?> <?= $colors['text'] ?>">
                                                    <?= ucfirst($order['status']) ?>
                                                </span>
                                            </div>

                                            <!-- Order Items -->
                                            <div class="mt-4 space-y-4">
                                                <h4 class="text-sm font-medium text-primary-gray">Order Items:</h4>
                                                <div class="grid gap-4">
                                                    <?php foreach ($order['items'] as $item): ?>
                                                        <div class="flex items-center space-x-4 bg-white p-3 rounded-lg">
                                                            <img src="<?= htmlspecialchars($item['product_image']) ?>" 
                                                                 alt="<?= htmlspecialchars($item['name']) ?>" 
                                                                 class="w-16 h-16 object-cover rounded-lg">
                                                            <div class="flex-1">
                                                                <h5 class="font-medium text-primary-text"><?= htmlspecialchars($item['name']) ?></h5>
                                                                <div class="flex items-center justify-between mt-1">
                                                                    <p class="text-sm text-primary-gray">
                                                                        <?= $item['quantity'] ?> × <?= number_format($item['unit_price'], 2) ?> DHs
                                                                    </p>
                                                                    <p class="font-medium text-primary-accent">
                                                                        <?= number_format($item['quantity'] * $item['unit_price'], 2) ?> DHs
                                                                    </p>
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
                        </div>
                    </div>

                    <!-- Purchase History Chart -->
                    <div class="info-card bg-primary-bg rounded-lg shadow-lg overflow-hidden mt-8">
                        <div class="p-6 border-b border-primary-light">
                            <h2 class="text-xl font-bold text-primary-text">Purchase History</h2>
                        </div>
                        <div class="p-6">
                            <div class="chart-container h-64">
                                <canvas id="spendingChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </main>

    <script>
        // Initialize GSAP
        gsap.registerPlugin(ScrollTrigger);

        // Info cards animation
        gsap.to('.info-card', {
            opacity: 1,
            y: 0,
            duration: 0.8,
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
            scrollTrigger: {
                trigger: '.order-item',
                start: 'top bottom-=100',
                toggleActions: 'play none none reverse'
            }
        });

        // Chart animation
        gsap.to('.chart-container', {
            opacity: 1,
            scale: 1,
            duration: 0.8,
            ease: 'power3.out',
            delay: 0.6
        });

        // Initialize spending chart
        const ctx = document.getElementById('spendingChart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?= json_encode($formatted_history['labels']) ?>,
                datasets: [{
                    label: 'Monthly Spending',
                    data: <?= json_encode($formatted_history['data']) ?>,
                    borderColor: '#ff0033',
                    backgroundColor: 'rgba(255, 0, 51, 0.1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.parsed.y.toFixed(2) + ' DHs';
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(136, 136, 136, 0.1)'
                        },
                        ticks: {
                            callback: function(value) {
                                return value.toFixed(2) + ' DHs';
                            },
                            color: '#888888'
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            color: '#888888'
                        }
                    }
                }
            }
        });
    </script>
</body>
</html> 