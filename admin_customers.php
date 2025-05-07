<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

require_once 'config.php';
$conn = getPDO();

// Pagination setup
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 10;
$offset = ($page - 1) * $perPage;

// Get total number of customers
$stmt = $conn->query("SELECT COUNT(*) FROM users");
$totalCustomers = $stmt->fetchColumn();
$totalPages = ceil($totalCustomers / $perPage);

// Fetch customers with order count
$stmt = $conn->prepare("
    SELECT 
        u.id,
        u.username,
        u.email,
        u.role,
        u.created_at,
        COUNT(DISTINCT o.id) as total_orders,
        COALESCE(SUM(o.total_amount), 0) as total_spent,
        MAX(lh.login_time) as last_login,
        IF(MAX(lh.login_time) > DATE_SUB(NOW(), INTERVAL 30 DAY), 1, 0) as is_active
    FROM users u
    LEFT JOIN orders o ON u.id = o.user_id
    LEFT JOIN login_history lh ON u.id = lh.user_id
    GROUP BY u.id
    ORDER BY u.created_at DESC
    LIMIT :offset, :perPage
");
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->bindValue(':perPage', $perPage, PDO::PARAM_INT);
$stmt->execute();
$customers = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch login history
$stmt = $conn->query("SELECT u.username, lh.login_time, lh.ip_address FROM login_history lh JOIN users u ON lh.user_id = u.id ORDER BY lh.login_time DESC LIMIT 100");
$loginHistory = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch user statistics
$stmt = $conn->query("SELECT SUM(total_amount) as total_revenue FROM orders");
$total_revenue = $stmt->fetchColumn() ?: 0;

$stmt = $conn->query("SELECT COUNT(*) as total_users FROM users");
$totalUsers = $stmt->fetchColumn();

$stmt = $conn->query("SELECT COUNT(DISTINCT user_id) as active_users FROM login_history WHERE login_time > DATE_SUB(NOW(), INTERVAL 30 DAY)");
$activeUsers = $stmt->fetchColumn();

$inactiveUsers = $totalUsers - $activeUsers;
$activePercentage = ($activeUsers / $totalUsers) * 100;

$stmt = $conn->query("SELECT COUNT(*) as new_users FROM users WHERE created_at > DATE_SUB(NOW(), INTERVAL 7 DAY)");
$newUsers = $stmt->fetchColumn();

// Fetch top users
$stmt = $conn->query("SELECT u.username, COUNT(*) as login_count FROM login_history lh JOIN users u ON lh.user_id = u.id GROUP BY lh.user_id ORDER BY login_count DESC LIMIT 5");
$topUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $userId = $_POST['user_id'];
        
        if ($_POST['action'] === 'change_role') {
            $newRole = $_POST['new_role'];
            $stmt = $conn->prepare("UPDATE users SET role = ? WHERE id = ?");
            $stmt->execute([$newRole, $userId]);
        } 
         elseif ($_POST['action'] === 'delete_account') {
            $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$userId]);
        }
        
        // Refresh the page to show updated data
        header("Location: admin_customers.php");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Customers - CasawiTech</title>
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
        .customer-card {
            opacity: 0;
            transform: translateY(20px);
            transition: all 0.3s ease;
        }
        .customer-card:hover {
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
        .stat-card {
            opacity: 0;
            transform: scale(0.95);
        }
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }
        .badge {
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
        <!-- Statistics Overview -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="stat-card bg-primary-bg rounded-xl shadow-lg p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-primary-gray">Total Customers</p>
                        <h3 class="text-2xl font-bold text-primary-text mt-2"><?php echo $totalUsers ?></h3>
                    </div>
                    <div class="w-12 h-12 bg-primary-light rounded-full flex items-center justify-center">
                        <i class="fas fa-users text-primary-accent"></i>
                    </div>
                </div>
            </div>
            <div class="stat-card bg-primary-bg rounded-xl shadow-lg p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-primary-gray">Active Today</p>
                        <h3 class="text-2xl font-bold text-primary-text mt-2"><?php echo $activeUsers ?></h3>
                    </div>
                    <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center">
                        <i class="fas fa-user-clock text-green-500"></i>
                    </div>
                </div>
            </div>
            <div class="stat-card bg-primary-bg rounded-xl shadow-lg p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-primary-gray">New This Month</p>
                        <h3 class="text-2xl font-bold text-primary-text mt-2"><?php echo $newUsers ?></h3>
                    </div>
                    <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center">
                        <i class="fas fa-user-plus text-blue-500"></i>
                    </div>
                </div>
            </div>
            <div class="stat-card bg-primary-bg rounded-xl shadow-lg p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-primary-gray">Total Revenue</p>
                        <h3 class="text-2xl font-bold text-primary-accent mt-2"><?php echo number_format($total_revenue, 2); ?> DHs</h3>
                    </div>
                    <div class="w-12 h-12 bg-yellow-100 rounded-full flex items-center justify-center">
                        <i class="fas fa-coins text-yellow-500"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Action Bar -->
        <div class="flex flex-col md:flex-row justify-between items-center mb-8 space-y-4 md:space-y-0">
            <!-- Filters -->
            <div class="flex items-center space-x-4">
                <button class="filter-button bg-primary-light hover:bg-primary-accent hover:text-white text-primary-text px-4 py-2 rounded-lg transition-colors duration-250">
                    <i class="fas fa-filter mr-2"></i>
                    All Customers
                </button>
                <button class="filter-button bg-green-100 hover:bg-green-200 text-green-700 px-4 py-2 rounded-lg transition-colors duration-250">
                    <i class="fas fa-user-check mr-2"></i>
                    Active
                </button>
                <button class="filter-button bg-red-100 hover:bg-red-200 text-red-700 px-4 py-2 rounded-lg transition-colors duration-250">
                    <i class="fas fa-user-times mr-2"></i>
                    Inactive
                </button>
            </div>

            <!-- Search -->
            <div class="search-bar relative">
                <input type="text" 
                       placeholder="Search customers..." 
                       class="pl-10 pr-4 py-2 w-64 bg-primary-light border border-primary-gray/20 rounded-lg text-primary-text placeholder-primary-gray focus:outline-none focus:border-primary-accent transition-colors duration-250">
                <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-primary-gray"></i>
            </div>
        </div>

        <!-- Customers Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($customers as $customer): ?>
                <div class="customer-card bg-primary-bg rounded-lg shadow-lg overflow-hidden">
                    <div class="p-6">
                        <div class="flex items-center space-x-4 mb-4">
                            <div class="w-12 h-12 bg-primary-light rounded-full flex items-center justify-center">
                                <span class="text-xl font-bold text-primary-accent">
                                    <?php echo strtoupper(substr($customer['username'], 0, 1)); ?>
                                </span>
                            </div>
                            <div>
                                <h3 class="text-lg font-semibold text-primary-text"><?php echo htmlspecialchars($customer['username']); ?></h3>
                                <p class="text-primary-gray"><?php echo htmlspecialchars($customer['email']); ?></p>
                            </div>
                        </div>

                        <div class="space-y-3 mb-6">
                            <div class="flex justify-between items-center">
                                <span class="text-primary-gray">Member Since</span>
                                <span class="text-primary-text"><?php echo date('M j, Y', strtotime($customer['created_at'])); ?></span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-primary-gray">Total Orders</span>
                                <span class="text-primary-text"><?php echo $customer['total_orders']; ?></span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-primary-gray">Total Spent</span>
                                <span class="text-primary-accent font-medium"><?php echo number_format($customer['total_spent'] ?? 0, 2); ?> DHs</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-primary-gray">Last Active</span>
                                <span class="text-primary-text"><?php echo $customer['last_login'] ? date('M j, Y', strtotime($customer['last_login'])) : 'Never'; ?></span>
                            </div>
                        </div>

                        <div class="flex items-center justify-between pt-4 border-t border-primary-light">
                            <div class="flex space-x-2">
                                <button onclick="location.href='view_customer.php?id=<?php echo $customer['id']; ?>'"
                                        class="bg-primary-light hover:bg-primary-accent hover:text-white text-primary-text px-3 py-2 rounded-lg transition-colors duration-250">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button onclick="showCustomerDetails(<?php echo htmlspecialchars(json_encode($customer)); ?>)"
                                        class="bg-primary-light hover:bg-primary-accent hover:text-white text-primary-text px-3 py-2 rounded-lg transition-colors duration-250">
                                    <i class="fas fa-chart-pie"></i>
                                </button>
                            </div>
                            <div class="flex items-center">
                                <?php if ($customer['is_active']): ?>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700">
                                        <i class="fas fa-circle text-xs mr-1"></i>
                                        Active
                                    </span>
                                <?php else: ?>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-700">
                                        <i class="fas fa-circle text-xs mr-1"></i>
                                        Inactive
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
            <div class="flex justify-center mt-8 space-x-2">
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <a href="?page=<?= $i ?>" 
                       class="inline-flex items-center justify-center w-10 h-10 rounded-lg <?= $page === $i 
                            ? 'bg-primary-accent text-white' 
                            : 'bg-primary-light text-primary-text hover:bg-primary-accent hover:text-white' ?> transition-colors duration-250">
                        <?= $i ?>
                    </a>
                <?php endfor; ?>
            </div>
        <?php endif; ?>
    </main>

    <?php include 'footer.php'; ?>

    <!-- Customer Details Modal -->
    <div id="customerModal" class="modal fixed inset-0 bg-black bg-opacity-50 z-50 hidden">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-primary-bg rounded-lg shadow-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
                <div class="p-6 border-b border-primary-light">
                    <div class="flex items-center justify-between">
                        <h2 class="text-xl font-bold text-primary-text">Customer Analytics</h2>
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

        // Stat cards animation
        gsap.to('.stat-card', {
            opacity: 1,
            scale: 1,
            duration: 0.6,
            stagger: 0.1,
            ease: 'power3.out'
        });

        // Customer cards staggered animation
        gsap.to('.customer-card', {
            opacity: 1,
            y: 0,
            duration: 0.6,
            stagger: 0.1,
            ease: 'power3.out',
            scrollTrigger: {
                trigger: '.customer-card',
                start: 'top bottom-=100',
                toggleActions: 'play none none reverse'
            }
        });

        // Search functionality
        document.querySelector('.search-bar input').addEventListener('input', function(e) {
            const searchTerm = e.target.value.toLowerCase();
            document.querySelectorAll('.customer-card').forEach(card => {
                const username = card.querySelector('h3').textContent.toLowerCase();
                const email = card.querySelector('p').textContent.toLowerCase();
                if (username.includes(searchTerm) || email.includes(searchTerm)) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        });

        // Filter functionality
        document.querySelectorAll('.filter-button').forEach(button => {
            button.addEventListener('click', function() {
                const filter = this.textContent.trim().toLowerCase();
                document.querySelectorAll('.customer-card').forEach(card => {
                    const status = card.querySelector('.inline-flex').textContent.trim().toLowerCase();
                    if (filter === 'all customers' || status === filter) {
                        card.style.display = 'block';
                    } else {
                        card.style.display = 'none';
                    }
                });
            });
        });

        // Modal functionality
        function showCustomerDetails(customer) {
            const modal = document.getElementById('customerModal');
            const content = document.getElementById('modalContent');
            
            // Generate customer analytics HTML
            let html = `
                <div class="space-y-6">
                    <div class="grid grid-cols-2 gap-4">
                        <div class="bg-primary-light rounded-lg p-4">
                            <h3 class="text-sm font-medium text-primary-gray">Average Order Value</h3>
                            <p class="text-xl font-bold text-primary-accent mt-1">
                                ${(customer.total_spent / customer.total_orders || 0).toFixed(2)} DHs
                            </p>
                        </div>
                        <div class="bg-primary-light rounded-lg p-4">
                            <h3 class="text-sm font-medium text-primary-gray">Purchase Frequency</h3>
                            <p class="text-xl font-bold text-primary-text mt-1">
                                ${(customer.total_orders / (((new Date()) - new Date(customer.created_at)) / (1000 * 60 * 60 * 24 * 30)) || 0).toFixed(1)}/month
                            </p>
                        </div>
                    </div>

                    <div>
                        <h3 class="text-sm font-medium text-primary-gray mb-2">Order History</h3>
                        <div class="bg-primary-light rounded-lg h-48">
                            <canvas id="orderChart"></canvas>
                        </div>
                    </div>

                    <div>
                        <h3 class="text-sm font-medium text-primary-gray mb-2">Category Preferences</h3>
                        <div class="bg-primary-light rounded-lg h-48">
                            <canvas id="categoryChart"></canvas>
                        </div>
                    </div>
                </div>
            `;
            
            content.innerHTML = html;
            modal.classList.remove('hidden');
            
            // Initialize charts
            const orderCtx = document.getElementById('orderChart').getContext('2d');
            new Chart(orderCtx, {
                type: 'line',
                data: {
                    labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
                    datasets: [{
                        label: 'Orders',
                        data: [3, 5, 2, 4, 6, 4],
                        borderColor: '#ff0033',
                        backgroundColor: 'rgba(255, 0, 51, 0.1)',
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
                                color: 'rgba(136, 136, 136, 0.1)'
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            }
                        }
                    }
                }
            });

            const categoryCtx = document.getElementById('categoryChart').getContext('2d');
            new Chart(categoryCtx, {
                type: 'doughnut',
                data: {
                    labels: ['Gaming PCs', 'Peripherals', 'Components', 'Accessories'],
                    datasets: [{
                        data: [40, 25, 20, 15],
                        backgroundColor: ['#ff0033', '#111111', '#888888', '#f5f5f5']
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'right'
                        }
                    }
                }
            });
            
            // Animate modal
            gsap.fromTo('.modal', 
                { opacity: 0, scale: 0.95 },
                { opacity: 1, scale: 1, duration: 0.3, ease: 'power3.out' }
            );
        }

        function closeModal() {
            const modal = document.getElementById('customerModal');
            
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
        document.getElementById('customerModal').addEventListener('click', (e) => {
            if (e.target === document.getElementById('customerModal')) {
                closeModal();
            }
        });
    </script>
</body>
</html>

