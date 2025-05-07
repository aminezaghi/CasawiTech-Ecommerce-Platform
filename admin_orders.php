<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

require_once 'config.php';
$conn = getPDO();

// Update order status
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_id']) && isset($_POST['new_status'])) {
    $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
    $stmt->execute([$_POST['new_status'], $_POST['order_id']]);
    $message = "Order status updated successfully.";
}

// Fetch all orders with user information
$stmt = $conn->query("SELECT o.*, u.username FROM orders o JOIN users u ON o.user_id = u.id ORDER BY o.created_at DESC");
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch order details for each order
foreach ($orders as &$order) {
    $stmt = $conn->prepare("SELECT oi.*, p.name, p.prix AS unit_price FROM order_items oi JOIN produit p ON oi.product_id = p.id WHERE oi.order_id = ?");
    $stmt->execute([$order['id']]);
    $order['items'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Calculate profit (assuming a 20% profit margin)
    $order['profit'] = $order['total_amount'] * 0.2;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Orders - CasawiTech</title>
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
        .filter-button {
            opacity: 0;
            transform: translateX(-20px);
        }
        .search-bar {
            opacity: 0;
            transform: translateY(-20px);
        }
        .status-badge {
            transition: all 0.3s ease;
        }
        .status-badge:hover {
            transform: scale(1.1);
        }
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }
        .urgent-order {
            animation: pulse 2s infinite;
        }
        .modal {
            opacity: 0;
            transform: scale(0.95);
            transition: all 0.3s ease;
        }
        .modal.active {
            opacity: 1;
            transform: scale(1);
        }
    </style>
</head>
<body>
    <?php include 'admin_navbar.php'; ?>

    <main class="container mx-auto px-4 py-8">
        <!-- Action Bar -->
        <div class="flex flex-col md:flex-row justify-between items-center mb-8 space-y-4 md:space-y-0">
            <!-- Filters -->
            <div class="flex items-center space-x-4">
                <button class="filter-button bg-primary-light hover:bg-primary-accent hover:text-white text-primary-text px-4 py-2 rounded-lg transition-colors duration-250">
                    <i class="fas fa-filter mr-2"></i>
                    All Orders
                </button>
                <button class="filter-button bg-yellow-100 hover:bg-yellow-200 text-yellow-700 px-4 py-2 rounded-lg transition-colors duration-250">
                    <i class="fas fa-clock mr-2"></i>
                    Pending
                </button>
                <button class="filter-button bg-blue-100 hover:bg-blue-200 text-blue-700 px-4 py-2 rounded-lg transition-colors duration-250">
                    <i class="fas fa-shipping-fast mr-2"></i>
                    Shipped
                </button>
                <button class="filter-button bg-green-100 hover:bg-green-200 text-green-700 px-4 py-2 rounded-lg transition-colors duration-250">
                    <i class="fas fa-check-circle mr-2"></i>
                    Delivered
                </button>
            </div>

            <!-- Search -->
            <div class="search-bar relative">
                <input type="text" 
                       placeholder="Search orders..." 
                       class="pl-10 pr-4 py-2 w-64 bg-primary-light border border-primary-gray/20 rounded-lg text-primary-text placeholder-primary-gray focus:outline-none focus:border-primary-accent transition-colors duration-250">
                <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-primary-gray"></i>
            </div>
        </div>

        <!-- Orders Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($orders as $order): ?>
                <div class="order-card bg-primary-bg rounded-lg shadow-lg overflow-hidden">
                    <div class="p-6">
                        <div class="flex items-center justify-between mb-4">
                            <div>
                                <h3 class="text-lg font-semibold text-primary-text">Order #<?= $order['id'] ?></h3>
                                <p class="text-primary-gray"><?= $order['username'] ?></p>
                            </div>
                            <?php
                            $statusColors = [
                                'pending' => ['bg' => 'bg-yellow-100', 'text' => 'text-yellow-700', 'icon' => 'clock'],
                                'shipped' => ['bg' => 'bg-blue-100', 'text' => 'text-blue-700', 'icon' => 'shipping-fast'],
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

                        <div class="space-y-2 mb-4">
                            <div class="flex justify-between items-center">
                                <span class="text-primary-gray">Total Amount</span>
                                <span class="text-lg font-bold text-primary-accent"><?= number_format($order['total_amount'], 2) ?> DHs</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-primary-gray">Items</span>
                                <span class="text-primary-text"><?= count($order['items']) ?> products</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-primary-gray">Date</span>
                                <span class="text-primary-text"><?= date('M j, Y', strtotime($order['created_at'])) ?></span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-primary-gray">Profit</span>
                                <span class="text-primary-accent"><?= number_format($order['profit'], 2) ?> DHs</span>
                            </div>
                        </div>

                        <div class="flex items-center justify-between pt-4 border-t border-primary-light">
                            <form method="POST" class="flex-1 mr-2">
                                <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                                <select name="new_status" 
                                        onchange="this.form.submit()"
                                        class="w-full px-3 py-2 bg-primary-light border border-primary-gray/20 rounded-lg text-primary-text focus:outline-none focus:border-primary-accent transition-colors duration-250">
                                    <option value="pending" <?= $order['status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
                                    <option value="shipped" <?= $order['status'] === 'shipped' ? 'selected' : '' ?>>Shipped</option>
                                    <option value="delivered" <?= $order['status'] === 'delivered' ? 'selected' : '' ?>>Delivered</option>
                                    <option value="cancelled" <?= $order['status'] === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                                </select>
                            </form>
                            <button onclick="showOrderDetails(<?= htmlspecialchars(json_encode($order)) ?>)"
                                    class="ml-2 bg-primary-light hover:bg-primary-accent hover:text-white text-primary-text px-4 py-2 rounded-lg transition-colors duration-250">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </main>

    <!-- Order Details Modal -->
    <div id="orderModal" class="modal fixed inset-0 bg-black bg-opacity-50 z-50 hidden">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-primary-bg rounded-lg shadow-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
                <div class="p-6 border-b border-primary-light">
                    <div class="flex items-center justify-between">
                        <h2 class="text-xl font-bold text-primary-text">Order Details</h2>
                        <button onclick="closeModal()" class="text-primary-gray hover:text-primary-accent">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
                <div class="p-6" id="modalContent">
                    <!-- Content will be dynamically inserted here -->
                </div>
            </div>
        </div>
    </div>

    <?php include 'footer.php'; ?>

    <script>
        // Initialize GSAP
        gsap.registerPlugin(ScrollTrigger);

        // Filter buttons animation
        gsap.to('.filter-button', {
            opacity: 1,
            x: 0,
            duration: 0.6,
            stagger: 0.1,
            ease: 'power3.out'
        });

        // Search bar animation
        gsap.to('.search-bar', {
            opacity: 1,
            y: 0,
            duration: 0.8,
            ease: 'power3.out'
        });

        // Order cards staggered animation
        gsap.to('.order-card', {
            opacity: 1,
            y: 0,
            duration: 0.6,
            stagger: 0.1,
            ease: 'power3.out',
            scrollTrigger: {
                trigger: '.order-card',
                start: 'top bottom-=100',
                toggleActions: 'play none none reverse'
            }
        });

        // Modal functionality
        function showOrderDetails(order) {
            const modal = document.getElementById('orderModal');
            const content = document.getElementById('modalContent');
            
            // Generate order details HTML
            let html = `
                <div class="space-y-6">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <h3 class="text-sm font-medium text-primary-gray">Customer</h3>
                            <p class="text-primary-text">${order.username}</p>
                        </div>
                        <div>
                            <h3 class="text-sm font-medium text-primary-gray">Order Date</h3>
                            <p class="text-primary-text">${new Date(order.created_at).toLocaleDateString()}</p>
                        </div>
                    </div>

                    <div>
                        <h3 class="text-sm font-medium text-primary-gray mb-2">Order Items</h3>
                        <div class="bg-primary-light rounded-lg overflow-hidden">
                            <table class="w-full">
                                <thead>
                                    <tr class="border-b border-primary-gray/10">
                                        <th class="px-4 py-2 text-left text-xs font-medium text-primary-gray">Product</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-primary-gray">Quantity</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-primary-gray">Price</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-primary-gray">Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${order.items.map(item => `
                                        <tr class="border-b border-primary-gray/10">
                                            <td class="px-4 py-2">${item.name}</td>
                                            <td class="px-4 py-2">${item.quantity}</td>
                                            <td class="px-4 py-2">${item.unit_price} DHs</td>
                                            <td class="px-4 py-2">${(item.quantity * item.unit_price).toFixed(2)} DHs</td>
                                        </tr>
                                    `).join('')}
                                </tbody>
                                <tfoot>
                                    <tr class="bg-primary-bg">
                                        <td colspan="3" class="px-4 py-2 text-right font-medium text-primary-text">Total:</td>
                                        <td class="px-4 py-2 font-bold text-primary-accent">${order.total_amount} DHs</td>
                                    </tr>
                                    <tr class="bg-primary-bg">
                                        <td colspan="3" class="px-4 py-2 text-right font-medium text-primary-text">Profit:</td>
                                        <td class="px-4 py-2 font-bold text-primary-accent">${order.profit.toFixed(2)} DHs</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
            `;
            
            content.innerHTML = html;
            modal.classList.remove('hidden');
            
            // Animate modal
            gsap.fromTo('.modal', 
                { opacity: 0, scale: 0.95 },
                { opacity: 1, scale: 1, duration: 0.3, ease: 'power3.out' }
            );
        }

        function closeModal() {
            const modal = document.getElementById('orderModal');
            
            // Animate modal close
            gsap.to('.modal', {
                opacity: 0,
                scale: 0.95,
                duration: 0.2,
                ease: 'power3.in',
                onComplete: () => {
                    modal.classList.add('hidden');
                }
            });
        }

        // Close modal when clicking outside
        document.getElementById('orderModal').addEventListener('click', (e) => {
            if (e.target === document.getElementById('orderModal')) {
                closeModal();
            }
        });
    </script>
</body>
</html>

