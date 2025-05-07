<?php
session_start();
$isLoggedIn = isset($_SESSION['user_id']);
$isAdmin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';

require_once 'config.php';
$conn = getPDO();

// Fetch categories
// Predefined categories with icons
$categories = [
    ['name' => 'Chair and Desk', 'icon' => 'fa-chair'],
    ['name' => 'Components', 'icon' => 'fa-microchip'],
    ['name' => 'Full Setup', 'icon' => 'fa-desktop'],
    ['name' => 'Laptops', 'icon' => 'fa-laptop'],
    ['name' => 'PC Gamers', 'icon' => 'fa-gamepad'],
    ['name' => 'Peripherals', 'icon' => 'fa-keyboard'],
];

// Fetch featured products (products with highest price)
$featuredStmt = $conn->query("
    SELECT * FROM produit 
    WHERE stock_quantity > 0 and old_price is null and length(name) > 10
    ORDER BY prix DESC 
    LIMIT 9
");
$featuredProducts = $featuredStmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch products by category for the setup section
$setupProducts = [];
foreach ($categories as $category) {
    $stmt = $conn->prepare("SELECT * FROM produit WHERE category = ? LIMIT 4");
    $stmt->execute([$category['name']]);
    $setupProducts[$category['name']] = $stmt->fetchAll(PDO::FETCH_ASSOC);
}



?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CasawiTech - Premium Gaming Electronics</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/ScrollTrigger.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/animejs/3.2.1/anime.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: {
                            bg: '#fff8f3',
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
        body {
            font-family: 'Inter', sans-serif;
            background-color: #fff8f3;
            color: #111111;
            background-image: radial-gradient(#ff003311 1px, transparent 1px);
            background-size: 40px 40px;
            background-position: -19px -19px;
        }
        nav.bg-white, nav.bg-primary-bg {
            background-color: #fff8f3 !important;
        }
        footer.bg-primary-text {
            background-color: #222 !important;
        }
        .product-card {
            opacity: 1;
            transform: none;
            transition: transform 0.5s ease;
            background: #fff !important;
        }
        .product-card:hover {
            transform: translateY(-10px);
        }
        
        .product-card .text-primary-text {
            color: #111111 !important;
        }
        .product-card .text-primary-gray {
            color: #888888 !important;
        }
        .product-card .text-primary-accent {
            color: #ff0033 !important;
        }
        .hero-particles {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
        }
        .parallax-section {
            overflow: hidden;
            position: relative;
        }
        .cta-button {
            position: relative;
            overflow: hidden;
        }
        .cta-button::after {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: 0.5s;
        }
        .cta-button:hover::after {
            left: 100%;
        }
    </style>
</head>
<body class="bg-white">
<?php
try {
    include 'navbar.php';
} catch (Throwable $e) {
    echo "Error in navbar: " . $e->getMessage();
}
?>

        
    <!-- Hero Section -->
    <section class="w-full bg-white text-primary-text py-20 flex flex-col md:flex-row items-center justify-between px-4 md:px-16 border-b border-primary-light">
        <div class="max-w-7xl w-full mx-auto flex flex-col md:flex-row items-center justify-between">
            <div class="flex-1 flex flex-col justify-center items-start md:items-start">
                <span class="text-lg font-semibold text-primary-accent mb-2">Gaming Zone</span>
                <h1 class="text-5xl md:text-6xl font-extrabold leading-tight mb-6">
                    Immerse Yourself In The <span class="text-primary-accent">Gaming Experience</span>
                </h1>
                <p class="text-lg text-primary-gray mb-8 max-w-xl">
                    Premium gaming gear for the ultimate competitive advantage. Shop the best electronics and accessories for gamers.
                </p>
                <a href="products.php" class="bg-primary-accent hover:bg-opacity-90 text-white px-8 py-4 rounded-lg inline-flex items-center transition-colors duration-250">
                    <span class="mr-2">Shop Now</span>
                    <i class="fas fa-arrow-right"></i>
                </a>
            </div>
            <div class="flex-1 flex justify-center items-center mt-12 md:mt-0">
                <img src="https://gamerx-demo.myshopify.com/cdn/shop/files/hero.png?v=1725524604" 
                     alt="Gaming Setup" 
                     class="w-full max-w-lg h-auto object-contain rounded-xl transform hover:scale-105 transition-transform duration-500">
            </div>
        </div>
    </section>

    <!-- Categories Section -->
    <section class="w-full py-16 bg-white">
        <div class="max-w-7xl w-full mx-auto px-4 md:px-16">
            <h2 class="text-3xl font-bold text-primary-text text-center mb-12">Shop by Category</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-8">
                <?php 
                $categoryImages = [
                    'PC Gamers' => 'https://cdn-icons-png.flaticon.com/512/1687/1687497.png',
                    'Laptops' => 'https://cdn-icons-png.flaticon.com/512/2888/2888704.png',
                    'Peripherals' => 'https://cdn-icons-png.flaticon.com/512/11152/11152942.png',
                    'Components' => 'https://cdn-icons-png.flaticon.com/512/5708/5708095.png',
                    'Full Setup' => 'https://cdn-icons-png.flaticon.com/512/1055/1055666.png',
                ];
                ?>
                <?php foreach ($categories as $category): ?>
                    <a href="products.php?category=<?= urlencode($category['name']) ?>" 
                       class="bg-primary-bg rounded-xl shadow-lg p-6 flex flex-col items-center text-center space-y-4 w-full">
                        <img src="<?= $categoryImages[$category['name']] ?? 'https://cdn-icons-png.flaticon.com/512/2736/2736259.png' ?>" alt="<?= htmlspecialchars($category['name']) ?>" class="w-20 h-20 object-cover rounded-full mb-2 border-4 border-primary-light">
                        <h3 class="text-xl font-semibold text-primary-text"><?= htmlspecialchars($category['name']) ?></h3>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Featured Products Section -->
    <section class="w-full py-16 bg-white">
        <div class="max-w-7xl w-full mx-auto px-4 md:px-16">
            <div class="flex items-center justify-between mb-10">
                <h2 class="text-3xl font-bold text-primary-text">Featured Products</h2>
                <div class="flex space-x-4">
                    <button id="prevSlide" class="bg-primary-accent hover:bg-primary-light text-white w-10 h-10 rounded-full flex items-center justify-center transition-colors duration-250" aria-label="Previous">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" class="w-5 h-5">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                        </svg>
                    </button>
                    <button id="nextSlide" class="bg-primary-accent hover:bg-primary-light text-white w-10 h-10 rounded-full flex items-center justify-center transition-colors duration-250" aria-label="Next">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" class="w-5 h-5">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                    </button>
                </div>
            </div>
            <div class="relative overflow-hidden rounded-xl bg-primary p-6">
                <div id="productsSlider" class="flex transition-transform duration-500 ease-in-out">
                    <?php foreach ($featuredProducts as $index => $product): ?>
                        <div class="product-card w-1/3 flex-shrink-0 px-3">
                            <div class="bg-primary-bg rounded-lg shadow-lg overflow-hidden h-full flex flex-col">
                                <a href="product_details.php?id=<?= $product['id'] ?>" class="block flex-grow">
                                    <div class="aspect-square overflow-hidden">
                                        <img src="<?= htmlspecialchars($product['lien']) ?>" 
                                             alt="<?= htmlspecialchars($product['name']) ?>" 
                                             class="w-full h-full object-cover transition-transform duration-500 hover:scale-110">
                                    </div>
                                    <div class="p-6 flex flex-col h-44 justify-between">
                                        <h3 class="text-xl font-bold mb-2 text-primary-text line-clamp-2 min-h-[48px] flex items-center">
                                            <?= htmlspecialchars($product['name']) ?>
                                        </h3>
                                        <p class="text-primary-gray mb-2 line-clamp-1 min-h-[20px] flex items-center"><?= htmlspecialchars($product['category']) ?></p>
                                        <div class="flex items-end justify-between flex-wrap gap-2 mt-auto">
                                            <div class="flex flex-col">
                                                <span class="text-2xl font-bold text-primary-accent">
                                                    <?= number_format($product['prix'], 2) ?> DHs
                                                </span>
                                                <span class="text-sm text-primary-gray line-through" style="min-height:18px;display:block;">
                                                    <?php if (!empty($product['old_price'])): ?>
                                                        <?= number_format($product['old_price'], 2) ?> DHs
                                                    <?php endif; ?>
                                                </span>
                                            </div>
                                            <?php if (!empty($product['old_price']) && $product['prix'] < $product['old_price']): ?>
                                                <?php $discountPercentage = round(($product['old_price'] - $product['prix']) / $product['old_price'] * 100); ?>
                                                <span class="bg-green-100 text-green-800 text-xs font-semibold px-2.5 py-0.5 rounded">
                                                    -<?= $discountPercentage ?>%
                                                </span>
                                            <?php else: ?>
                                                <span style="min-width:40px;display:inline-block;"></span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <!-- Slide Indicators -->
                <div class="flex justify-center mt-8 space-x-2">
                    <button class="slide-indicator w-2 h-2 rounded-full bg-primary-light transition-colors duration-250" data-slide="0"></button>
                    <button class="slide-indicator w-2 h-2 rounded-full bg-primary-light transition-colors duration-250" data-slide="1"></button>
                    <button class="slide-indicator w-2 h-2 rounded-full bg-primary-light transition-colors duration-250" data-slide="2"></button>
                </div>
            </div>
        </div>
    </section>

    <!-- Stats Section -->
    <section class="container mx-auto px-4 py-16 bg-white">
        <div class="max-w-5xl mx-auto">
            <div class="bg-primary-bg rounded-xl shadow-lg p-8">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
                    <div class="text-center transform transition-transform duration-300 hover:-translate-y-2">
                        <img src="https://cdn-icons-png.flaticon.com/512/1077/1077012.png" alt="Happy Customers" class="w-16 h-16 mx-auto mb-4">
                        <h3 class="text-3xl font-bold text-primary-text mb-2">5000+</h3>
                        <p class="text-primary-gray">Happy Customers</p>
                    </div>
                    
                    <div class="text-center transform transition-transform duration-300 hover:-translate-y-2">
                        <img src="https://cdn-icons-png.flaticon.com/512/1041/1041916.png" alt="Products" class="w-16 h-16 mx-auto mb-4">
                        <h3 class="text-3xl font-bold text-primary-text mb-2">1000+</h3>
                        <p class="text-primary-gray">Products Available</p>
                    </div>
                    
                    <div class="text-center transform transition-transform duration-300 hover:-translate-y-2">
                        <img src="https://cdn-icons-png.flaticon.com/512/1670/1670865.png" alt="Delivery" class="w-16 h-16 mx-auto mb-4">
                        <h3 class="text-3xl font-bold text-primary-text mb-2">24/48h</h3>
                        <p class="text-primary-gray">Fast Delivery</p>
                    </div>
                    
                    <div class="text-center transform transition-transform duration-300 hover:-translate-y-2">
                        <img src="https://cdn-icons-png.flaticon.com/512/616/616489.png" alt="Rating" class="w-16 h-16 mx-auto mb-4">
                        <h3 class="text-3xl font-bold text-primary-text mb-2">4.8/5</h3>
                        <p class="text-primary-gray">Customer Rating</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Testimonials Section -->
    <section class="container mx-auto px-4 py-16 bg-white">
        <div class="max-w-5xl mx-auto">
            <h2 class="text-3xl font-bold text-primary-text text-center mb-12">What Our Customers Say</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div class="bg-primary-bg rounded-xl p-6 shadow-lg transform transition-transform duration-300 hover:-translate-y-2">
                    <div class="flex flex-col items-center text-center">
                        <img src="https://randomuser.me/api/portraits/men/32.jpg" alt="John Doe" class="w-16 h-16 rounded-full mb-4">
                        <p class="text-primary-gray mb-4">"Amazing selection of gaming gear! The quality is top-notch and the prices are very competitive."</p>
                        <h4 class="font-semibold text-primary-text">John Doe</h4>
                        <span class="text-primary-gray text-sm">Pro Gamer</span>
                        <div class="flex items-center mt-2 text-yellow-400">
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                        </div>
                    </div>
                </div>
                <div class="bg-primary-bg rounded-xl p-6 shadow-lg transform transition-transform duration-300 hover:-translate-y-2">
                    <div class="flex flex-col items-center text-center">
                        <img src="https://randomuser.me/api/portraits/women/44.jpg" alt="Jane Smith" class="w-16 h-16 rounded-full mb-4">
                        <p class="text-primary-gray mb-4">"Fast shipping and excellent customer service. The products exceeded my expectations!"</p>
                        <h4 class="font-semibold text-primary-text">Jane Smith</h4>
                        <span class="text-primary-gray text-sm">Tech Enthusiast</span>
                        <div class="flex items-center mt-2 text-yellow-400">
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star-half-alt"></i>
                        </div>
                    </div>
                </div>
                <div class="bg-primary-bg rounded-xl p-6 shadow-lg transform transition-transform duration-300 hover:-translate-y-2">
                    <div class="flex flex-col items-center text-center">
                        <img src="https://randomuser.me/api/portraits/men/54.jpg" alt="Mike Johnson" class="w-16 h-16 rounded-full mb-4">
                        <p class="text-primary-gray mb-4">"The best gaming store I've found. Great deals and an awesome selection of products!"</p>
                        <h4 class="font-semibold text-primary-text">Mike Johnson</h4>
                        <span class="text-primary-gray text-sm">Streamer</span>
                        <div class="flex items-center mt-2 text-yellow-400">
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <?php include 'footer.php'; ?>

    <script>
        // Featured Products Slider Logic
        const slider = document.getElementById('productsSlider');
        const prevButton = document.getElementById('prevSlide');
        const nextButton = document.getElementById('nextSlide');
        const indicators = document.querySelectorAll('.slide-indicator');
        let currentSlide = 0;
        const totalSlides = 3; // 9 products / 3 per slide = 3 slides

        function updateSlider() {
            const translateX = currentSlide * -(100 / totalSlides);
            slider.style.transform = `translateX(${translateX}%)`;
            indicators.forEach((indicator, index) => {
                if (index === currentSlide) {
                    indicator.classList.add('bg-primary-accent');
                    indicator.classList.remove('bg-primary-light');
                } else {
                    indicator.classList.remove('bg-primary-accent');
                    indicator.classList.add('bg-primary-light');
                }
            });
        }

        function nextSlideFn() {
            currentSlide = (currentSlide + 1) % totalSlides;
            updateSlider();
        }

        function prevSlideFn() {
            currentSlide = (currentSlide - 1 + totalSlides) % totalSlides;
            updateSlider();
        }

        nextButton.addEventListener('click', nextSlideFn);
        prevButton.addEventListener('click', prevSlideFn);
        indicators.forEach((indicator, index) => {
            indicator.addEventListener('click', () => {
                currentSlide = index;
                updateSlider();
            });
        });
        setInterval(nextSlideFn, 5000);
        indicators[0].classList.add('bg-primary-accent');
        indicators[0].classList.remove('bg-primary-light');

        // Hero Section Animation
        gsap.from(".hero-section h1", {opacity: 0, y: 50, duration: 1, delay: 0.2});
        gsap.from(".hero-section p", {opacity: 0, y: 30, duration: 1, delay: 0.4});
        gsap.from(".hero-section .cta-button", {opacity: 0, scale: 0.8, duration: 0.8, delay: 0.6});
        gsap.from(".hero-section img", {opacity: 0, x: 100, duration: 1, delay: 0.5});

        // Category Cards Animation
        gsap.from(".category-card", {
            opacity: 0,
            y: 40,
            duration: 0.8,
            stagger: 0.1,
            ease: "power3.out",
            scrollTrigger: {
                trigger: ".category-card",
                start: "top bottom-=100",
                toggleActions: "play none none reverse"
            }
        });

        // Product Cards Animation
        gsap.from(".product-card", {
            opacity: 0,
            y: 40,
            duration: 0.8,
            stagger: 0.1,
            ease: "power3.out",
            scrollTrigger: {
                trigger: ".product-card",
                start: "top bottom-=100",
                toggleActions: "play none none reverse"
            }
        });
    </script>
</body>
</html>

