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
    <title>Support - CasawiTech</title>
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
        <h1 class="text-4xl font-extrabold text-primary-accent mb-8 text-center">Support Center</h1>
        <section class="mb-16">
            <h2 class="text-2xl font-bold mb-6">Frequently Asked Questions</h2>
            <div class="space-y-6">
                <div class="bg-primary-light rounded-lg p-6">
                    <h3 class="font-semibold text-primary-text mb-2">How do I track my order?</h3>
                    <p class="text-primary-gray">You can track your order status from your account dashboard or by visiting the <a href="track_order.php" class="text-primary-accent underline">Track Order</a> page.</p>
                </div>
                <div class="bg-primary-light rounded-lg p-6">
                    <h3 class="font-semibold text-primary-text mb-2">What is your return policy?</h3>
                    <p class="text-primary-gray">We offer a 14-day return policy for most products. Please see our <a href="#" class="text-primary-accent underline">Return Policy</a> for details.</p>
                </div>
                <div class="bg-primary-light rounded-lg p-6">
                    <h3 class="font-semibold text-primary-text mb-2">How can I contact support?</h3>
                    <p class="text-primary-gray">You can use the form below, email us at <a href="mailto:support@casawitech.com" class="text-primary-accent underline">support@casawitech.com</a>, or call us at +212 600-000000.</p>
                </div>
            </div>
        </section>
        <section class="mb-16">
            <h2 class="text-2xl font-bold mb-6">Contact Support</h2>
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
                    <label class="block text-primary-gray mb-2 font-semibold" for="message">How can we help?</label>
                    <textarea id="message" name="message" rows="5" class="w-full px-4 py-3 rounded-lg border border-primary-gray/20 bg-white text-primary-text focus:outline-none focus:border-primary-accent" required></textarea>
                </div>
                <button type="submit" class="w-full bg-primary-accent hover:bg-opacity-90 text-white py-3 px-8 rounded-lg font-bold transition-colors duration-250">Submit Ticket</button>
            </form>
        </section>
        <section>
            <h2 class="text-2xl font-bold mb-6">Live Chat & Ticketing</h2>
            <div class="bg-primary-light rounded-lg p-6 flex items-center justify-between">
                <div>
                    <h3 class="font-semibold text-primary-text mb-2">Live Chat</h3>
                    <p class="text-primary-gray">Our live chat is available from 9am to 6pm, Monday to Friday. <span class="text-primary-accent">[Live Chat Placeholder]</span></p>
                </div>
                <div>
                    <h3 class="font-semibold text-primary-text mb-2">Ticket Status</h3>
                    <p class="text-primary-gray">Check your ticket status in your <a href="user_dashboard.php" class="text-primary-accent underline">dashboard</a>.</p>
                </div>
            </div>
        </section>
    </main>
    <?php include 'footer.php'; ?>
</body>
</html> 