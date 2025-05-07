<?php
session_start();

if (isset ($_SESSION['role'])) {
    header("Location: index.php");
    exit;
}

require_once 'config.php';
$pdo = getPDO();

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];

    try {
        // Query the users table
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            // Login successful
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];

            // Log the login event in the login_history table
            $ip_address = $_SERVER['REMOTE_ADDR']; // Get the user's IP address
            $login_time = date("Y-m-d H:i:s"); // Current timestamp

            $historyStmt = $pdo->prepare("INSERT INTO login_history (user_id, login_time, ip_address) VALUES (?, ?, ?)");
            $historyStmt->execute([$user['id'], $login_time, $ip_address]);

            // Redirect based on role
            if ($user['role'] === 'admin') {
                header("Location: admin_dashboard.php");
            } else {
                header("Location: index.php");
            }
            exit();
        } else {
            // Invalid credentials
            $message = "Invalid username or password.";
        }
    } catch (PDOException $e) {
        $message = "Login failed: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - CasawiTech</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/animejs/3.2.1/anime.min.js"></script>
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
                            gray: '#888888',
                            bcc: '#fff8f3'
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
        body {
            font-family: 'Inter', sans-serif;
            background-color: #fff8f3 !important;
            color: #111111;
        }
        .login-card {
            opacity: 0;
            transform: translateY(20px);
        }
        .form-input {
            transition: all 0.3s ease;
        }
        .form-input:focus {
            transform: scale(1.02);
        }
        .login-button {
            position: relative;
            overflow: hidden;
        }
        .login-button::after {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: 0.5s;
        }
        .login-button:hover::after {
            left: 100%;
        }
        .particle {
            position: absolute;
            pointer-events: none;
            background: #ff0033;
            border-radius: 50%;
        }
        @keyframes float {
            0% { transform: translateY(0px); }
            50% { transform: translateY(-20px); }
            100% { transform: translateY(0px); }
        }
        .floating-icon {
            animation: float 6s ease-in-out infinite;
            opacity: 0;
            transform: scale(0.8);
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="bg-primary-bcc border-b border-primary-light sticky top-0 z-50">
        <div class="container mx-auto px-4 py-4">
            <div class="flex items-center justify-between">
                <a href="/" class="text-3xl font-bold text-primary-accent">
                    CasawiTech
                </a>
                <h1 class="text-2xl font-bold text-primary-text">Sign In</h1>
                <div class="w-32"></div>
            </div>
        </div>
    </nav>

    <main class="min-h-screen flex items-center justify-center py-12 px-4">
        <div class="w-full max-w-md space-y-8">
            <!-- Modern Animated Gaming Icon -->
            <div class="flex justify-center mb-8">
                <div class="relative w-24 h-24 flex items-center justify-center">
                    <svg class="animate-pulse drop-shadow-lg" width="96" height="96" viewBox="0 0 96 96" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <circle cx="48" cy="48" r="44" fill="#fff" fill-opacity="0.7"/>
                        <circle cx="48" cy="48" r="36" fill="#ff0033" fill-opacity="0.15"/>
                        <rect x="28" y="40" width="40" height="16" rx="8" fill="#ff0033"/>
                        <rect x="44" y="32" width="8" height="32" rx="4" fill="#ff0033"/>
                        <circle cx="36" cy="48" r="4" fill="#fff"/>
                        <circle cx="60" cy="48" r="4" fill="#fff"/>
                    </svg>
                    <div class="absolute inset-0 rounded-full bg-primary-accent opacity-20 blur-2xl animate-pulse"></div>
                </div>
            </div>

            <?php if ($message): ?>
                <div class="login-card mb-6 p-4 bg-red-100 text-red-700 rounded-lg">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <div class="login-card bg-white/70 backdrop-blur-lg rounded-2xl shadow-2xl overflow-hidden transition-transform duration-300 hover:scale-105 hover:shadow-3xl">
                <div class="p-8">
                    <div class="text-center mb-8">
                        <h2 class="text-2xl font-bold text-primary-text">Sign In</h2>
                        <p class="mt-2 text-primary-gray">Access your gaming universe</p>
                    </div>

                    <form method="post" action="login.php" class="space-y-6">
                        <div>
                            <label class="block text-sm font-medium text-primary-gray mb-2" for="username">
                                Username
                            </label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="fas fa-user text-primary-gray"></i>
                                </div>
                                <input
                                    class="form-input w-full pl-10 pr-4 py-3 bg-primary-light border border-primary-gray/20 rounded-lg text-primary-text placeholder-primary-gray focus:outline-none focus:border-primary-accent transition-colors duration-250"
                                    placeholder="Enter your username"
                                    type="text"
                                    name="username"
                                    id="username"
                                    required
                                />
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-primary-gray mb-2" for="password">
                                Password
                            </label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="fas fa-lock text-primary-gray"></i>
                                </div>
                                <input
                                    class="form-input w-full pl-10 pr-4 py-3 bg-primary-light border border-primary-gray/20 rounded-lg text-primary-text placeholder-primary-gray focus:outline-none focus:border-primary-accent transition-colors duration-250"
                                    placeholder="Enter your password"
                                    type="password"
                                    name="password"
                                    id="password"
                                    required
                                />
                            </div>
                        </div>
                        <div class="flex items-center justify-between">
                            <div class="flex items-center">
                                <input type="checkbox" id="remember" name="remember"
                                    class="h-4 w-4 text-primary-accent border-primary-gray/20 rounded focus:ring-primary-accent">
                                <label for="remember" class="ml-2 block text-sm text-primary-gray">
                                    Remember me
                                </label>
                            </div>
                            <a href="#" class="text-sm text-primary-accent hover:text-primary-accent/80 transition-colors duration-250">
                                Forgot password?
                            </a>
                        </div>
                        <button
                            type="submit"
                            class="login-button w-full bg-primary-accent hover:bg-opacity-90 text-white py-3 px-4 rounded-lg transition-all duration-250 font-medium flex items-center justify-center shadow-lg hover:shadow-2xl hover:scale-105"
                        >
                            <i class="fas fa-sign-in-alt mr-2"></i>
                            Sign In
                        </button>
                    </form>
                </div>
            </div>

            <div class="login-card text-center">
                <p class="text-primary-gray">Don't have an account?</p>
                <a href="register.php" 
                   class="mt-4 inline-block w-full bg-primary-light text-primary-text py-3 px-8 rounded-lg transition-colors duration-250 hover:bg-primary-light/70">
                    Create Account
                </a>
            </div>
        </div>
    </main>

    <?php include 'footer.php'; ?>

    <script>
        // Initialize GSAP
        gsap.timeline()
            .to('.floating-icon', {
                opacity: 1,
                scale: 1,
                duration: 0.6,
                stagger: 0.2,
                ease: 'back.out(1.7)'
            })
            .to('.login-card', {
                opacity: 1,
                y: 0,
                duration: 0.8,
                stagger: 0.2,
                ease: 'power3.out'
            });

        // Particle animation on button click
        document.querySelector('.login-button').addEventListener('click', function(e) {
            const button = this;
            const rect = button.getBoundingClientRect();
            const x = e.clientX - rect.left;
            const y = e.clientY - rect.top;

            for (let i = 0; i < 8; i++) {
                const particle = document.createElement('div');
                particle.className = 'particle';
                button.appendChild(particle);

                const size = Math.random() * 3 + 2;
                const destinationX = x + (Math.random() - 0.5) * 100;
                const destinationY = y + (Math.random() - 0.5) * 100;

                particle.style.width = `${size}px`;
                particle.style.height = `${size}px`;
                particle.style.left = `${x}px`;
                particle.style.top = `${y}px`;

                anime({
                    targets: particle,
                    left: destinationX,
                    top: destinationY,
                    opacity: [1, 0],
                    easing: 'easeOutExpo',
                    duration: 1000,
                    complete: function() {
                        particle.remove();
                    }
                });
            }
        });

        // Form input animations
        document.querySelectorAll('.form-input').forEach(input => {
            input.addEventListener('focus', function() {
                gsap.to(this, {
                    scale: 1.02,
                    duration: 0.3,
                    ease: 'power2.out'
                });
            });

            input.addEventListener('blur', function() {
                gsap.to(this, {
                    scale: 1,
                    duration: 0.3,
                    ease: 'power2.out'
                });
            });
        });
    </script>
</body>
</html>