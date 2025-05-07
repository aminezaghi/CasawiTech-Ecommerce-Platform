<?php
session_start();
$isLoggedIn = isset($_SESSION['user_id']);
$isAdmin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
require_once 'config.php';
$conn = getPDO();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact - CasawiTech</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
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
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>
    <main class="max-w-4xl mx-auto px-4 py-16">
        <h1 class="text-4xl font-extrabold text-primary-accent mb-8 text-center">Contact Us</h1>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-12 mb-16">
            <form class="bg-primary-light rounded-xl p-8 shadow-lg space-y-6">
                <div>
                    <label class="block text-primary-gray mb-2 font-semibold" for="name">Name</label>
                    <input type="text" id="name" name="name" class="w-full px-4 py-3 rounded-lg border border-primary-gray/20 bg-white text-primary-text focus:outline-none focus:border-primary-accent" required>
                </div>
                <div>
                    <label class="block text-primary-gray mb-2 font-semibold" for="email">Email</label>
                    <input type="email" id="email" name="email" class="w-full px-4 py-3 rounded-lg border border-primary-gray/20 bg-white text-primary-text focus:outline-none focus:border-primary-accent" required>
                </div>
                <div>
                    <label class="block text-primary-gray mb-2 font-semibold" for="message">Message</label>
                    <textarea id="message" name="message" rows="5" class="w-full px-4 py-3 rounded-lg border border-primary-gray/20 bg-white text-primary-text focus:outline-none focus:border-primary-accent" required></textarea>
                </div>
                <button type="submit" class="w-full bg-primary-accent hover:bg-opacity-90 text-white py-3 px-8 rounded-lg font-bold transition-colors duration-250">Send Message</button>
            </form>
            <div class="flex flex-col justify-center space-y-8">
                <div>
                    <h2 class="text-xl font-bold mb-2">Contact Information</h2>
                    <p class="text-primary-gray mb-1"><i class="fas fa-map-marker-alt mr-2 text-primary-accent"></i>123 Gaming St, Casablanca, Morocco</p>
                    <p class="text-primary-gray mb-1"><i class="fas fa-envelope mr-2 text-primary-accent"></i>support@casawitech.com</p>
                    <p class="text-primary-gray"><i class="fas fa-phone mr-2 text-primary-accent"></i>+212 600-000000</p>
                </div>
                <div>
                    <h2 class="text-xl font-bold mb-2">Find Us</h2>
                    <div class="w-full h-48 bg-primary-gray/10 rounded-lg flex items-center justify-center text-primary-gray overflow-hidden">
                        <iframe
                            src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3329.567073857019!2d-7.632537684800001!3d33.57311098073209!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0xda7cdcfb3b0b1b1%3A0x7e6e8e8e8e8e8e8e!2sCasablanca%2C%20Morocco!5e0!3m2!1sen!2sma!4v1680000000000!5m2!1sen!2sma"
                            width="100%" height="100%" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
                    </div>
                </div>
            </div>
        </div>
    </main>
    <?php include 'footer.php'; ?>
</body>
</html> 