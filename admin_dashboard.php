<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

require_once 'config.php';
$conn = getPDO();

// Fetch total revenue
$stmt = $conn->query("SELECT SUM(total_amount) as total_revenue FROM orders");
$totalRevenue = $stmt->fetchColumn();

// Calculate profit (assuming 100 DHs profit per product sold)
$stmt = $conn->query("SELECT SUM(quantity) as total_products_sold FROM order_items");
$totalProductsSold = $stmt->fetchColumn();
$totalProfit = $totalProductsSold * 100;

// Fetch total orders
$stmt = $conn->query("SELECT COUNT(*) as total_orders FROM orders");
$totalOrders = $stmt->fetchColumn();

// Fetch total users
$stmt = $conn->query("SELECT COUNT(*) as total_users FROM users");
$totalUsers = $stmt->fetchColumn();

// Fetch users who made purchases
$stmt = $conn->query("SELECT COUNT(DISTINCT user_id) as users_with_orders FROM orders");
$usersWithOrders = $stmt->fetchColumn();

// Calculate conversion rate
$conversionRate = ($totalUsers > 0) ? ($usersWithOrders / $totalUsers) * 100 : 0;

// Fetch data for the last 30 days sales graph
$stmt = $conn->query("
    SELECT DATE(created_at) as date, SUM(total_amount) as revenue, COUNT(*) * 100 as profit FROM orders
    WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
    GROUP BY DATE(created_at)
    ORDER BY date
");
$salesData = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch top 3 bestsellers
$stmt = $conn->query("
    SELECT p.id, p.name, p.prix, p.lien, SUM(oi.quantity) as total_sold, SUM(oi.quantity) * 100 as profit
    FROM order_items oi
    JOIN produit p ON oi.product_id = p.id
    GROUP BY p.id
    ORDER BY total_sold DESC
    LIMIT 3
");
$bestsellers = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch latest 10 orders
$stmt = $conn->query("
    SELECT o.id, o.total_amount as revenue, o.created_at, o.status,
           p.name as product_name, p.lien as product_image, oi.quantity,
           (oi.quantity * 100) as net_profit
    FROM orders o
    JOIN order_items oi ON o.id = oi.order_id
    JOIN produit p ON oi.product_id = p.id
    ORDER BY o.created_at DESC
    LIMIT 10
");
$latestOrders = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - CasawiTech</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/ScrollTrigger.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.7.0/chart.min.js"></script>
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
        .stat-card {
            opacity: 0;
            transform: translateY(20px);
            transition: all 0.3s ease;
        }
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        .chart-card {
            opacity: 0;
            transform: scale(0.95);
        }
        .recent-order {
            opacity: 0;
            transform: translateX(-20px);
            transition: all 0.3s ease;
        }
        .recent-order:hover {
            transform: translateX(5px);
            background-color: rgba(255, 0, 51, 0.05);
        }
        .bestseller-item {
            opacity: 0;
            transform: translateY(10px);
            transition: all 0.3s ease;
        }
        .bestseller-item:hover {
            transform: scale(1.02);
        }
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }
        .trend-up {
            color: #10b981;
        }
        .trend-down {
            color: #ef4444;
        }
    </style>
</head>
<body>
    <?php include 'admin_navbar.php'; ?>

    <main class="container mx-auto px-4 py-8">
        <!-- Quick Stats -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div class="stat-card bg-primary-bg rounded-lg shadow-lg p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="text-primary-accent">
                        <i class="fas fa-chart-line text-2xl"></i>
                    </div>
                    <div class="text-xs font-medium text-primary-gray">vs last month</div>
                </div>
                <h3 class="text-2xl font-bold text-primary-text mb-1"><?= number_format($totalRevenue, 2) ?> DHs</h3>
                <p class="text-primary-gray">Total Revenue</p>
                <div class="mt-2 flex items-center">
                    <?php $revenueGrowth = 15; // Calculate actual growth ?>
                    <i class="fas <?= $revenueGrowth >= 0 ? 'fa-arrow-up trend-up' : 'fa-arrow-down trend-down' ?> mr-1"></i>
                    <span class="<?= $revenueGrowth >= 0 ? 'text-green-500' : 'text-red-500' ?>"><?= abs($revenueGrowth) ?>%</span>
                </div>
            </div>

            <div class="stat-card bg-primary-bg rounded-lg shadow-lg p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="text-primary-accent">
                        <i class="fas fa-shopping-cart text-2xl"></i>
                    </div>
                    <div class="text-xs font-medium text-primary-gray">vs last month</div>
                </div>
                <h3 class="text-2xl font-bold text-primary-text mb-1"><?= $totalOrders ?></h3>
                <p class="text-primary-gray">Total Orders</p>
                <div class="mt-2 flex items-center">
                    <?php $orderGrowth = 8; // Calculate actual growth ?>
                    <i class="fas <?= $orderGrowth >= 0 ? 'fa-arrow-up trend-up' : 'fa-arrow-down trend-down' ?> mr-1"></i>
                    <span class="<?= $orderGrowth >= 0 ? 'text-green-500' : 'text-red-500' ?>"><?= abs($orderGrowth) ?>%</span>
                </div>
            </div>

            <div class="stat-card bg-primary-bg rounded-lg shadow-lg p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="text-primary-accent">
                        <i class="fas fa-users text-2xl"></i>
                    </div>
                    <div class="text-xs font-medium text-primary-gray">vs last month</div>
                </div>
                <h3 class="text-2xl font-bold text-primary-text mb-1"><?= $totalUsers ?></h3>
                <p class="text-primary-gray">Total Users</p>
                <div class="mt-2 flex items-center">
                    <?php $userGrowth = 12; // Calculate actual growth ?>
                    <i class="fas <?= $userGrowth >= 0 ? 'fa-arrow-up trend-up' : 'fa-arrow-down trend-down' ?> mr-1"></i>
                    <span class="<?= $userGrowth >= 0 ? 'text-green-500' : 'text-red-500' ?>"><?= abs($userGrowth) ?>%</span>
                </div>
            </div>

            <div class="stat-card bg-primary-bg rounded-lg shadow-lg p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="text-primary-accent">
                        <i class="fas fa-percentage text-2xl"></i>
                    </div>
                    <div class="text-xs font-medium text-primary-gray">vs last month</div>
                </div>
                <h3 class="text-2xl font-bold text-primary-text mb-1"><?= number_format($conversionRate, 1) ?>%</h3>
                <p class="text-primary-gray">Conversion Rate</p>
                <div class="mt-2 flex items-center">
                    <?php $conversionGrowth = 5; // Calculate actual growth ?>
                    <i class="fas <?= $conversionGrowth >= 0 ? 'fa-arrow-up trend-up' : 'fa-arrow-down trend-down' ?> mr-1"></i>
                    <span class="<?= $conversionGrowth >= 0 ? 'text-green-500' : 'text-red-500' ?>"><?= abs($conversionGrowth) ?>%</span>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Sales Chart -->
            <div class="lg:col-span-2">
                <div class="chart-card bg-primary-bg rounded-lg shadow-lg p-6">
                    <div class="flex items-center justify-between mb-6">
                        <h2 class="text-lg font-bold text-primary-text">Sales Overview</h2>
                        <div class="flex items-center space-x-2">
                            <span class="inline-block w-3 h-3 rounded-full bg-primary-accent"></span>
                            <span class="text-sm text-primary-gray">Revenue</span>
                            <span class="inline-block w-3 h-3 rounded-full bg-primary-light ml-4"></span>
                            <span class="text-sm text-primary-gray">Profit</span>
                        </div>
                    </div>
                    <div class="h-80">
                        <canvas id="salesChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Bestsellers -->
            <div class="lg:col-span-1">
                <div class="chart-card bg-primary-bg rounded-lg shadow-lg p-6">
                    <h2 class="text-lg font-bold text-primary-text mb-6">Bestsellers</h2>
                    <div class="space-y-6">
                        <?php foreach ($bestsellers as $product): ?>
                            <div class="bestseller-item flex items-center space-x-4">
                                <img src="<?= htmlspecialchars($product['lien']) ?>" 
                                     alt="<?= htmlspecialchars($product['name']) ?>" 
                                     class="w-16 h-16 rounded-lg object-cover">
                                <div class="flex-1">
                                    <h3 class="font-medium text-primary-text"><?= htmlspecialchars($product['name']) ?></h3>
                                    <p class="text-sm text-primary-gray"><?= $product['total_sold'] ?> units sold</p>
                                    <p class="text-sm text-primary-accent"><?= number_format($product['profit'], 2) ?> DHs profit</p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Orders -->
        <div class="mt-8">
            <div class="chart-card bg-primary-bg rounded-lg shadow-lg overflow-hidden">
                <div class="p-6 border-b border-primary-light">
                    <div class="flex items-center justify-between">
                        <h2 class="text-lg font-bold text-primary-text">Recent Orders</h2>
                        <a href="admin_orders.php" 
                           class="text-primary-accent hover:text-primary-accent/80 transition-colors duration-250">
                            View All
                        </a>
                    </div>
                </div>
                <div class="p-6">
                    <div class="space-y-4">
                        <?php foreach ($latestOrders as $order): ?>
                            <div class="recent-order bg-primary-light rounded-lg p-4">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-sm text-primary-gray">Order #<?= $order['id'] ?></p>
                                        <p class="font-medium text-primary-text"><?= htmlspecialchars($order['product_name']) ?></p>
                                        <p class="text-sm text-primary-gray"><?= date('F j, Y g:i A', strtotime($order['created_at'])) ?></p>
                                        <!-- View Customer Link -->
                                        <a href="view_customer.php?id=<?= $order['user_id'] ?? '' ?>" class="text-primary-accent hover:underline text-xs mt-1 inline-block">
                                            <i class="fas fa-user mr-1"></i>View Customer
                                        </a>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-primary-accent font-medium"><?= number_format($order['revenue'], 2) ?> DHs</p>
                                        <p class="text-sm text-primary-gray">Profit: <?= number_format($order['net_profit'], 2) ?> DHs</p>
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
                                        <span class="inline-block mt-2 px-3 py-1 rounded-full text-xs <?= $colors['bg'] ?> <?= $colors['text'] ?>">
                                            <?= ucfirst($order['status']) ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <?php include 'footer.php'; ?>

    <script>
        // Initialize GSAP
        gsap.registerPlugin(ScrollTrigger);

        // Stat cards animation
        gsap.to('.stat-card', {
            opacity: 1,
            y: 0,
            duration: 0.8,
            stagger: 0.2,
            ease: 'power3.out'
        });

        // Chart cards animation
        gsap.to('.chart-card', {
            opacity: 1,
            scale: 1,
            duration: 0.8,
            stagger: 0.2,
            ease: 'power3.out',
            delay: 0.4
        });

        // Recent orders animation
        gsap.to('.recent-order', {
            opacity: 1,
            x: 0,
            duration: 0.6,
            stagger: 0.1,
            ease: 'power3.out',
            scrollTrigger: {
                trigger: '.recent-order',
                start: 'top bottom-=100',
                toggleActions: 'play none none reverse'
            }
        });

        // Bestseller items animation
        gsap.to('.bestseller-item', {
            opacity: 1,
            y: 0,
            duration: 0.6,
            stagger: 0.1,
            ease: 'power3.out'
        });

        // Initialize sales chart
        const ctx = document.getElementById('salesChart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?= json_encode(array_column($salesData, 'date')) ?>,
                datasets: [{
                    label: 'Revenue',
                    data: <?= json_encode(array_column($salesData, 'revenue')) ?>,
                    borderColor: '#ff0033',
                    backgroundColor: 'rgba(255, 0, 51, 0.1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.4
                }, {
                    label: 'Profit',
                    data: <?= json_encode(array_column($salesData, 'profit')) ?>,
                    borderColor: '#f5f5f5',
                    backgroundColor: 'rgba(245, 245, 245, 0.1)',
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
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(0, 0, 0, 0.1)'
                        },
                        ticks: {
                            callback: function(value) {
                                return value + ' DHs';
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
