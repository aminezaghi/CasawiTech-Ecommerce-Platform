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
    <title>About Us - CasawiTech</title>
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
    <main class="max-w-5xl mx-auto px-4 py-16">
        <h1 class="text-4xl font-extrabold text-primary-accent mb-8 text-center">About CasawiTech</h1>
        <section class="mb-16">
            <h2 class="text-2xl font-bold mb-4">Our Story</h2>
            <p class="text-primary-gray text-lg mb-4">Founded in 2023, CasawiTech was born from a passion for gaming and technology. Our mission is to empower gamers and tech enthusiasts with the best products, expert advice, and a vibrant community. We believe in quality, innovation, and customer satisfaction above all else.</p>
        </section>
        <section class="mb-16">
            <h2 class="text-2xl font-bold mb-4">Our Mission & Values</h2>
            <ul class="list-disc pl-6 text-primary-gray space-y-2">
                <li>Deliver premium gaming and tech products at competitive prices.</li>
                <li>Provide exceptional customer service and support.</li>
                <li>Foster a welcoming and inclusive gaming community.</li>
                <li>Continuously innovate and stay ahead of industry trends.</li>
            </ul>
        </section>
        <section>
            <h2 class="text-2xl font-bold mb-8">Meet the Team</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-8">
                <div class="bg-primary-light rounded-xl p-6 flex flex-col items-center">
                <span class="text-3xl font-bold text-primary-accent">
                                    A
                                </span><br>
                    <h3 class="font-bold text-lg text-primary-text">Amine Zaghi</h3>
                    <p class="text-primary-gray">Founder & CEO</p>
                </div>
            </div>
        </section>
    </main>
    <?php include 'footer.php'; ?>
</body>
</html> 