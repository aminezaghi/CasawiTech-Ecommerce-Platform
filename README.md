# CasawiTech

A modern, visually stunning e-commerce platform for gaming electronics and accessories. Built with PHP, MySQL, and Tailwind CSS, CasawiTech delivers a premium shopping experience for gamers and tech enthusiasts.

---

## 🚀 Project Overview
CasawiTech is a full-featured online store designed for the gaming community. It features a clean, card-based UI, responsive design, and a robust admin dashboard for managing products, orders, and customers.

---

## ✨ Features
- Beautiful, modern homepage with hero banner, categories, featured products, stats, and testimonials
- User authentication (register, login, logout)
- Product catalog with search, filter, and sort
- Product details with similar product suggestions
- Shopping cart and checkout with order tracking
- Admin dashboard for managing products, orders, and customers
- Responsive design for desktop and mobile
- Animated UI with GSAP and smooth transitions

---

## 🎨 Color Scheme
| Name                | Hex Code    | Usage/Description                                 |
|---------------------|-------------|---------------------------------------------------|
| Cream Background    | `#fff8f3`   | Main background for body, navbar, footer, cards   |
| Primary Text        | `#111111`   | Main text color                                   |
| Accent Red          | `#ff0033`   | Buttons, highlights, links, accents               |
| Light Gray          | `#f5f5f5`   | Card backgrounds, light UI elements               |
| Medium Gray         | `#888888`   | Secondary text, icons, muted info                 |

---

## 🛠️ Tech Stack
- **Backend:** PHP 7+, MySQL
- **Frontend:** Tailwind CSS, HTML5, JavaScript (GSAP, Anime.js)
- **Icons:** Font Awesome
- **Other:** Google Fonts (Inter)

---

## 📦 Folder Structure
```
/ (root)
├── index.php
├── products.php
├── cart.php
├── checkout.php
├── contact.php
├── about_us.php
├── register.php
├── login.php
├── support.php
├── user_dashboard.php
├── admin_dashboard.php
├── admin_products.php
├── admin_orders.php
├── admin_customers.php
├── add_product.php
├── modify_product.php
├── product_details.php
├── track_order.php
├── thank_you.php
├── navbar.php
├── footer.php
├── config.php
├── tailwind.config.js
├── README.md
└── ...
```

---

## ⚡ Setup Instructions
1. **Clone the repository:**
   ```bash
   git clone https://github.com/aminezaghi/CasawiTech-Ecommerce-Platform.git
   cd CasawiTech-Ecommerce-Platform
   ```
2. **Set up the database:**
   - Import the provided SQL file (`ecommerce sql.txt`) into your MySQL server.
   - Update `config.php` with your database credentials if needed.
3. **Run locally:**
   - Use XAMPP, MAMP, or your preferred PHP server.
   - Access the site at `http://localhost/CasawiTech-Ecommerce-Platform` (or your configured path).
4. **Admin Access:**
   - Register a user, then manually set their `role` to `admin` in the database for admin access.

---

## 🙏 Credits
- UI inspired by modern gaming brands and e-commerce best practices
- Built by Amine Zaghi
- Icons by [Font Awesome](https://fontawesome.com/)
- Animations by [GSAP](https://greensock.com/gsap/) and [Anime.js](https://animejs.com/)

---

## 📄 License
This project is open source and available under the [MIT License](LICENSE). 