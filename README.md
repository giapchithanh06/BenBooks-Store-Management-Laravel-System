# 📚 BENBOOKS - Book & Comic E-commerce and Store Management System

## 📌 Description

**BenBooks** is an online bookstore for books and comics (manga, manhwa, manhua, Western comics and Vietnamese comics). It lets customers browse products, search and filter items, manage their shopping cart, apply voucher codes, join flash sales, choose a delivery address, pay online through a sandbox payment gateway or by Cash on Delivery (COD), and track their orders.

The project was built with **PHP 7.4, Laravel 8, MySQL, Blade and Bootstrap 5**. It runs on **XAMPP** and follows the Laravel 8 project structure.

The website also includes an **Admin Dashboard** for order, product, inventory, voucher, flash sale, return, review and user management, plus revenue reports, a mobile app for shippers, and a set of **AI modules** written in pure PHP that run offline (recommendations, semantic search, chatbot, sentiment analysis, customer segmentation, forecasting and fraud detection).

## 📸 Screenshots

### 🛒 Customer Website

| Home page | Book listing & filters |
|---|---|
| ![Home page](docs/screenshots/home.png) | ![Book listing](docs/screenshots/books.png) |

| Book detail | Natural-language search |
|---|---|
| ![Book detail](docs/screenshots/book-detail.png) | ![Smart search](docs/screenshots/smart-search.png) |

| Shopping cart | Checkout |
|---|---|
| ![Cart](docs/screenshots/cart.png) | ![Checkout](docs/screenshots/checkout.png) |

| Payment gateway | Order tracking |
|---|---|
| ![Payment gateway](docs/screenshots/payment-gateway.png) | ![Order tracking](docs/screenshots/order-tracking.png) |

| AI chatbot |  |
|---|---|
| ![Chatbot](docs/screenshots/chatbot.png) |  |

### 👨‍💼 Admin Dashboard

| Dashboard | Product management |
|---|---|
| ![Admin dashboard](docs/screenshots/admin-dashboard.png) | ![Admin books](docs/screenshots/admin-books.png) |

| Order management | Customer segmentation |
|---|---|
| ![Admin orders](docs/screenshots/admin-orders.png) | ![Segments](docs/screenshots/ai-segments.png) |

| Revenue & demand forecast | Fraud detection |
|---|---|
| ![Forecast](docs/screenshots/ai-forecast.png) | ![Anomalies](docs/screenshots/ai-anomalies.png) |

| Sentiment analysis | Knowledge graph |
|---|---|
| ![Sentiment](docs/screenshots/ai-sentiment.png) | ![Knowledge graph](docs/screenshots/ai-graph.png) |

### 🛵 Shipper Mobile App

<p align="center"><img src="docs/screenshots/shipper-app.png" alt="Shipper app" width="300"></p>

## 🔑 Demo Accounts

All demo accounts use the password **`123456`**.

| Role | Email | Access |
|---|---|---|
| Admin | admin@motsach.vn | `/admin` - full access, including AI & analytics |
| Staff | staff@motsach.vn | `/admin` - books, inventory, orders, returns, reviews |
| Shipper | shipper@motsach.vn | `/shipper` - mobile delivery app |
| Customer | khach@motsach.vn (also khach1 … khach40) | Storefront |

**Voucher codes:** `MOTSACH10`, `SACHHAY50K`, `MANGA15`, `FREESHIP`, `BLACKFRIDAY` (starts in 25 days, for testing)

## 🚀 Features

### 👤 Customer Features

**Authentication**
- User Registration
- User Login
- Remember Me
- Logout
- Passwords hashed before being saved
- Locked accounts cannot sign in
- Guest cart is kept after login

**Product Browsing**
- Home page with featured books, flash sale, personalized suggestions, bestsellers, new arrivals and categories
- 37 sample books, including manga, manhwa, comics, literature, business, self-help and children's books
- Search with instant suggestions and accent-insensitive Vietnamese matching
- Advanced search by ISBN, author, category, publisher, year, price range, rating and stock
- Filter by category and price; sort by price, rating, best-selling or newest
- Category, author and publisher pages
- Product detail page with variants, stock status, specifications, reviews and similar books

**Shopping Cart**
- Add to cart with AJAX, without reloading the page
- Update quantity (checked against stock)
- Remove products from cart
- Automatic subtotal calculation
- Apply and remove voucher codes
- Free shipping from 300,000 VND

**Checkout & Orders**
- Saved or new delivery address
- Automatic shipping fee by province
- Payment by COD or online through a sandbox gateway
- Retry payment for unpaid orders
- Order success page
- Order history ("My Orders") with status timeline
- Customers can cancel orders while they are still pending
- Reorder from a past order
- Guest order tracking with order code and phone number
- Return requests per item

**Account & Notifications**
- Edit profile and change password
- Address book with default address
- Wishlist
- Notification bell with realtime polling every 10 seconds

**AI Features**
- Personalized book recommendations on the home page
- Similar books and "frequently bought together"
- Natural-language search, for example "comic action under 50k"
- Chatbot that answers questions about books, orders, vouchers and policies, with sources

**Support Pages**
- Flash sale page
- Voucher page
- Policies page (shipping, returns, privacy)
- Custom 404 and error pages

### 👨‍💼 Admin Features

**Dashboard**
- Statistic cards (revenue, orders, new customers, best sellers)
- Revenue and order chart for the last 30 days
- Order status chart
- Top books and alerts
- Recent orders

**Order Management**
- Filter orders by status
- Search by order code, customer name or phone number
- Status workflow: Pending → Confirmed → Shipping → Delivered
- Assign shipper and enter tracking number
- Printable invoice
- Cancel orders and return stock automatically

**Product Management**
- Create, edit and soft-delete books with cover image, ISBN, publish year, page count, authors, tags and variants
- Variants (paperback, hardcover, special edition) with their own SKU, price and stock
- Manage categories with two levels, authors and publishers

**Inventory Management**
- Stock in, stock out and adjustment
- Movement history
- Low-stock alerts

**Voucher & Flash Sale Management**
- Percent or fixed-amount vouchers with max discount, minimum order, usage limits and validity period
- Multiple flash sale campaigns with limited quantity and countdown timer

**Returns & Reviews**
- Approve or reject return requests; stock is restored automatically
- Hide or delete reviews

**User Management**
- Create, edit and lock accounts
- Assign roles: Admin, Staff, Shipper, Customer

**Reports & Statistics**
- Sales report by date range, book, category, payment method and stock
- Export to CSV (opens in Excel)

**AI & Analytics (Admin)**
- Customer behaviour analytics: conversion funnel, top keywords, peak hours
- Customer segmentation with RFM and K-Means
- Revenue and demand forecasting
- Fraud and anomaly detection with a risk score
- Sentiment analysis of reviews, including aspect-based results
- Knowledge graph of books, authors, categories and publishers
- Chatbot logs and knowledge base management
- Search lab to compare normal and semantic search
- Job queue monitor

### 🛵 Shipper Features

- Mobile-first page at `/shipper`
- View assigned orders
- Pick up an order
- Call the customer and open the address in a map
- Confirm delivery and COD collection
- Report failed delivery with a reason

## 📋 Business Rules

**Shipping fee**
- Ho Chi Minh City: 20,000 VND
- Nearby provinces (Bình Dương, Đồng Nai, Long An, Bà Rịa, Tây Ninh): 25,000 VND
- Other provinces: 35,000 VND
- **Free shipping** for orders from 300,000 VND

**Voucher codes**
- Checked for: active status, start and end date, usage limit, per-user limit and minimum order value
- Percentage vouchers have a maximum discount amount

**Stock**
- Placing an order reduces stock, and the stock is locked during checkout to prevent overselling
- Cancelling an order or completing a return restores stock

**Order status**

```
Pending → Confirmed → Shipping → Delivered
   ↓                      ↓
Cancelled             Returned (after return request is approved)
```

- Orders can be cancelled while Pending or Confirmed
- COD orders are marked as paid when delivered
- Every status change is recorded in the order history and sent to the customer as a notification

## 🛠️ Technologies Used

- PHP 7.4
- Laravel 8
- MySQL / MariaDB
- Blade templates
- Bootstrap 5.3
- Font Awesome 5
- Chart.js
- vis-network
- Laravel Queue (database driver)
- Laravel Task Scheduler
- Composer
- Git

## ✨ Modern Techniques Applied

### 📱 Responsive Web Design
- Bootstrap Grid System
- Mobile-first approach for the shipper app
- Adaptive layouts for Mobile, Tablet and Desktop

### 🧠 AI Algorithms Written in Pure PHP
- **Recommendation:** hybrid of content-based (TF-IDF with cosine similarity) and item-based collaborative filtering
- **Semantic search:** TF-IDF vectors with bigrams, accent removal, stop words and synonym expansion
- **Chatbot:** intent detection and RAG over a knowledge base, with optional LLM support through `LLM_API_KEY`
- **Customer segmentation:** RFM scoring with K-Means (k-means++, k = 4)
- **Forecasting:** linear regression and moving averages
- **Fraud detection:** z-score on order value plus a rule set, producing a 0–100 risk score
- **Sentiment analysis:** Vietnamese sentiment lexicon with negation and intensifiers

### ⚙️ Background Jobs
- `ProcessNewOrder` job: sends the confirmation e-mail and refreshes customer segments after each order
- `RefreshAiData` job: rebuilds AI data in the background
- Monitoring page at `/admin/ai/queue`

### 🔔 Realtime Notifications
- AJAX polling every 10 seconds
- Toast pop-ups and unread counter in the header

### 💳 Payment Gateway (Sandbox)
- Follows the VNPay payment flow
- Callbacks are signed with **HMAC-SHA512**
- Transaction history is stored in the database

## 🗂️ Project Structure

```
MotSach/
│
├── app/
│   ├── Console/Commands/            AiRefresh (php artisan motsach:ai-refresh)
│   ├── Http/
│   │   ├── Controllers/             Auth, Home, Book, Cart, Checkout, Order, Account,
│   │   │                            Notification, Chatbot, Payment, Shipper
│   │   ├── Controllers/Admin/       Dashboard, Book, Category, Author, Publisher, Inventory,
│   │   │                            Order, Return, Review, User, Voucher, FlashSale, Report, Ai, Knowledge
│   │   └── Middleware/RoleMiddleware.php
│   ├── Jobs/                        ProcessNewOrder, RefreshAiData
│   ├── Models/                      25 Eloquent models
│   ├── Services/                    Cart, Order, Payment, TextAnalyzer, SemanticSearch,
│   │                                Recommendation, Chatbot, Sentiment, CustomerSegmentation,
│   │                                Forecast, FraudDetection, Analytics
│   └── helpers.php                  money(), vn_slug(), vn_unaccent(), stars()
│
├── bootstrap/                       Application bootstrap
├── config/                          Application configuration
├── database/
│   ├── factories/                   UserFactory
│   ├── migrations/                  Users, catalog, shop and AI tables
│   └── seeders/                     Catalog, User, Shop, Order + data/books.json
├── docs/
│   └── screenshots/                 Screenshots used in this README
├── public/
│   ├── assets/                      CSS, JS, fonts, Chart.js, vis-network (offline)
│   ├── images/books/                Book covers
│   └── index.php
├── resources/
│   ├── lang/                        en, vi
│   └── views/                       layouts (shop, admin, shipper), shop, admin, shipper, auth, errors
├── routes/
│   ├── web.php                      Customer, shipper and admin routes
│   └── api.php                      JSON API for books
├── storage/
├── tests/
├── .env.example
├── artisan
└── composer.json
```

## ▶️ How to Run

### Requirements

- XAMPP with **PHP 7.4** and MySQL
- Composer
- Git (optional)

### 1. Clone the Repository

```bash
git clone https://github.com/giapchithanh06/BenBooks-Store-Management-Laravel-System.git
cd BenBooks-Store-Management-Laravel-System
```

Or download the ZIP from GitHub and extract it to `C:\xampp\htdocs\MotSach`.

### 2. Install Dependencies

```bash
composer install
```

### 3. Create the Environment File

```bash
copy .env.example .env        # Windows
php artisan key:generate
```

### 4. Create the Database

1. Start **Apache** and **MySQL** in XAMPP.
2. Open http://localhost:8081/phpmyadmin.
3. Create a database named **`motsach`** with collation `utf8mb4_unicode_ci`.

Check the database settings in `.env`:

```env
DB_DATABASE=motsach
DB_USERNAME=root
DB_PASSWORD=
```

### 5. Create Tables and Sample Data

```bash
php artisan migrate:fresh --seed
php artisan motsach:ai-refresh
```

### 6. Run the Website

```bash
php artisan serve
```

Open **http://127.0.0.1:8000**.

- Admin dashboard: **http://127.0.0.1:8000/admin**
- Shipper app: **http://127.0.0.1:8000/shipper**

### 7. (Optional) Start the Queue Worker

Open a second terminal and run:

```bash
php artisan queue:work
```

### Run Tests

```bash
php artisan test
```

## 🔧 Quick Customization

| To change | Edit |
|---|---|
| Shipping fee, free-shipping threshold | `app/Services/CartService.php` → `FREE_SHIP_FROM` and `shippingFee()` |
| Voucher codes and sample data | `database/seeders/ShopSeeder.php` |
| Sample books | `database/seeders/data/books.json` |
| Colors and fonts | `public/assets/css/site.css` |
| Admin colors | `public/assets/css/admin.css` |
| Header and menu | `resources/views/layouts/shop.blade.php` |
| Admin menu | `resources/views/layouts/admin.blade.php` |
| Email content | `app/Jobs/ProcessNewOrder.php` |

## 🐛 Troubleshooting

| Problem | Fix |
|---|---|
| `vendor/autoload.php` not found | Run `composer install` |
| `Could not open input file: artisan` | `cd` into the project folder that contains `artisan` |
| `Specified key was too long` | Already handled with `Schema::defaultStringLength(191)` |
| Page shows an old error after editing a view | Run `php artisan view:clear` |
| Old settings still in use | Run `php artisan config:clear` |
| Reset all data | Run `php artisan migrate:fresh --seed` |

## 🎯 Purpose

This project was developed for educational purposes to practice:
- Back-end Web Development with Laravel
- Database Design with MySQL
- Responsive Web Design with Bootstrap
- E-commerce Website Development
- Admin Dashboard Development
- Practical Applications of Artificial Intelligence in E-commerce
- Git and GitHub workflow

## 🚀 Future Improvements

- Real payment gateway integration (VNPay / MoMo)
- Automatic payment confirmation through webhooks
- Email and SMS notifications through SMTP
- Product comparison
- REST API with authentication (Laravel Sanctum)
- Deployment with Docker
- Advanced analytics dashboard
- Larger dataset and a real book catalogue

## 👨‍💻 Authors

**Giáp Chí Thành**

Student Project – BenBooks Book & Comic E-commerce Website.

GitHub: [@giapchithanh06](https://github.com/giapchithanh06)
E-mail: chithanhgiap0409@gmail.com

## 📄 License

This project is intended for educational and learning purposes only.

Book titles, descriptions and covers are used only as sample data.
