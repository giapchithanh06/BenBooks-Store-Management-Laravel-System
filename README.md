# 📚 BENBOOKS - Book & Comic E-commerce Website

## 📌 Description

**BenBooks** is an online bookstore for books and comics (manga, manhwa, manhua, Western comics and Vietnamese comics). It gives readers a convenient way to browse books, search and filter items, manage their shopping cart, apply voucher codes, join flash sales, choose a delivery address, pay online or by Cash on Delivery (COD), and track their orders.

The project was built with **PHP 7.4, Laravel 8, MySQL, Blade and Bootstrap 5**, and runs on **XAMPP**. It uses the standard Laravel 8 project structure and includes an **Admin Dashboard** for managing products, orders, inventory, vouchers, flash sales, returns, reviews, users and reports.

It also includes a mobile app for shippers and a set of **AI modules written in pure PHP** that run offline: recommendations, semantic search, a shopping chatbot, sentiment analysis, customer segmentation, sales forecasting and fraud detection.

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

## 🚀 Features

### 📖 Book Browsing

**Home Page**
- Hero banner slider with featured books
- Flash sale block with countdown timer
- Personalized suggestions ("Recommended for you")
- Bestsellers, new arrivals and comics sections
- Category shortcuts and store statistics

**Books**
- Book listing with pagination
- Filter by category, author, publisher, price range, rating and stock
- Sort by price, rating, best-selling or newest
- Detail page with variants (paperback, hardcover, special edition), price, stock, specifications, reviews and similar books
- 37 sample books, including manga, manhwa, comics, literature, business, self-help and children's books

**Search**
- Search from the header with instant suggestions
- Vietnamese accent-insensitive search (e.g. "nha gia kim" finds "Nhà giả kim")
- Natural-language search (e.g. "comic action under 50k")

### 🛒 Cart, Checkout & Orders

**Shopping Cart**
- Add to cart with AJAX, without reloading the page
- Update quantity, checked against stock
- Remove products and see the subtotal update automatically
- Apply and remove voucher codes
- Free shipping from 300,000 VND

**Checkout**
- Choose a saved address or enter a new one
- Automatic shipping fee by province
- Payment by COD or online through a sandbox gateway
- Retry payment for unpaid orders
- Confirmation page with the order code

**My Orders**
- Order history by status with a timeline
- Cancel orders while they are still pending
- Reorder from a past order
- Request a return for delivered items
- Track an order without an account, using the order code and phone number

### 🎁 Promotions

- **Vouchers:** percent or fixed-amount codes with max discount, minimum order value, usage limits and validity period
- **Flash sales:** several campaigns with limited quantity, countdown timer and a sold-progress bar; sale prices apply automatically in the cart
- **Voucher page:** lists the active codes and their conditions

### 👤 Account

- Register and log in with "remember me"
- Edit profile and change password
- Address book with default address
- Wishlist
- Notification bell with realtime updates every 10 seconds
- Locked accounts cannot sign in

### 📄 Support Pages

- Shipping and return policies
- Custom 404 and error pages

### 👨‍💼 Admin Features

**Dashboard**
- Statistic cards for revenue, orders, new customers and best sellers
- Revenue and order chart for the last 30 days
- Order status chart, top books and alerts

**Order Management**
- Filter by status, search by order code, customer name or phone number
- Status workflow: Pending → Confirmed → Shipping → Delivered
- Assign shipper, enter tracking number, print invoice
- Cancelling an order returns stock automatically

**Product & Catalogue Management**
- Create, edit and delete books with cover image, ISBN, publish year, page count, authors, tags and variants
- Two-level categories, authors and publishers

**Inventory**
- Stock in, stock out and adjustments
- Movement history and low-stock alerts

**Vouchers, Flash Sales & Users**
- Create and manage vouchers and flash sale campaigns
- Create, edit and lock accounts; assign roles (Admin, Staff, Shipper, Customer)

**Returns & Reviews**
- Approve or reject return requests; stock is restored automatically
- Hide or delete reviews

**Reports**
- Sales report by date range, book, category and payment method
- Export to CSV that opens in Excel

**AI & Analytics**
- Customer behaviour: conversion funnel, top keywords, peak hours
- Customer segmentation with RFM and K-Means
- Revenue and demand forecasting
- Fraud and anomaly detection with a risk score
- Sentiment analysis of reviews, including aspect-based results
- Knowledge graph of books, authors, categories and publishers
- Chatbot logs, knowledge base and a search lab
- Job queue monitor

### 🛵 Shipper App

- Mobile-first page at `/shipper`
- View assigned orders and pick them up
- Call the customer and open the address in a map
- Confirm delivery and COD collection
- Report a failed delivery with a reason

## 🛠️ Technologies Used

- PHP 7.4
- Laravel 8 (Blade, Eloquent, Migrations, Seeders, Queue, Task Scheduler)
- MySQL / MariaDB
- Bootstrap 5.3
- Font Awesome 5
- Chart.js
- vis-network
- Composer
- Git

## ✨ Modern Techniques Applied

### 📱 Responsive Web Design
- Bootstrap Grid System
- Mobile-first layout for the shipper app
- Adaptive layouts for Mobile, Tablet and Desktop

### 🧠 AI Algorithms Written in Pure PHP
- **Recommendation:** content-based (TF-IDF with cosine similarity) combined with item-based collaborative filtering
- **Semantic search:** TF-IDF vectors with bigrams, accent removal, stop words and synonym expansion
- **Chatbot:** intent detection and retrieval over a knowledge base, with optional LLM support through `LLM_API_KEY`
- **Customer segmentation:** RFM scores with K-Means (k-means++, k = 4)
- **Forecasting:** linear regression and moving averages
- **Fraud detection:** z-score on order value plus rules, producing a 0–100 risk score
- **Sentiment analysis:** Vietnamese sentiment lexicon with negation and intensifiers

### ⚙️ Background Jobs & Realtime
- `ProcessNewOrder` job sends the confirmation e-mail and refreshes customer segments
- `RefreshAiData` job rebuilds AI data in the background
- Notifications use AJAX polling every 10 seconds, with toast pop-ups and an unread counter

### 💳 Sandbox Payment Gateway
- Follows the VNPay payment flow
- Callbacks are signed with **HMAC-SHA512**
- Every transaction is stored in the database

### 🗂️ Standard Laravel Structure
- Routes grouped by customer, shipper and admin
- Role-based access through a `role:` middleware
- Shared layouts and reusable Blade partials for book cards, pagination and status badges

## 🎨 User Interface

The website interface was designed with:
- A bookstore look with a dark navy header and orange price highlights
- Responsive layout for phones, tablets and desktops
- Hero banner slider
- Book cards with discount and "New" labels
- Toast notifications and a live cart counter
- Admin dashboard with a sidebar menu

## 📂 Project Structure

```
MotSach/
│
├── app/
│   ├── Console/Commands/          AiRefresh (php artisan motsach:ai-refresh)
│   ├── Http/
│   │   ├── Controllers/           Auth, Home, Book, Cart, Checkout, Order, Account,
│   │   │                          Notification, Chatbot, Payment, Shipper
│   │   ├── Controllers/Admin/     Dashboard, Book, Category, Author, Publisher, Inventory,
│   │   │                          Order, Return, Review, User, Voucher, FlashSale, Report, Ai, Knowledge
│   │   └── Middleware/            RoleMiddleware
│   ├── Jobs/                      ProcessNewOrder, RefreshAiData
│   ├── Models/                    25 Eloquent models
│   ├── Providers/                 AppServiceProvider, RouteServiceProvider
│   ├── Services/                  Cart, Order, Payment, TextAnalyzer, SemanticSearch,
│   │                              Recommendation, Chatbot, Sentiment, CustomerSegmentation,
│   │                              Forecast, FraudDetection, Analytics
│   └── helpers.php                money(), vn_slug(), vn_unaccent(), stars()
│
├── bootstrap/                     Application bootstrap
├── config/                        Application configuration
├── database/
│   ├── factories/                 UserFactory
│   ├── migrations/                Users, catalog, shop and AI tables
│   └── seeders/                   Catalog, User, Shop, Order + data/books.json
├── docs/
│   └── screenshots/               Screenshots used in this README
├── public/
│   ├── assets/                    CSS, JS, fonts, Chart.js, vis-network (works offline)
│   ├── images/books/              Book covers
│   └── index.php
├── resources/
│   ├── lang/                      en, vi
│   └── views/                     layouts, shop, admin, shipper, auth, errors
├── routes/
│   ├── web.php                    Customer, shipper and admin routes
│   └── api.php                    JSON API for books
├── storage/
├── tests/
├── .env.example
├── artisan
└── composer.json
```

## 🔄 Order Flow

```
Browse books / search
        ↓
  Add to cart (voucher optional)
        ↓
  Choose address & shipping
        ↓
  Pay online or COD
        ↓
  Staff confirms → Shipper delivers
        ↓
  Order completed (or returned)
```

## ▶️ How to Run

**1. Clone the Repository**

```bash
git clone https://github.com/giapchithanh06/BenBooks-Store-Management-Laravel-System.git
```

**2. Install Dependencies**

```bash
cd BenBooks-Store-Management-Laravel-System
composer install
```

**3. Set Up the Environment**

```bash
copy .env.example .env        # Windows
php artisan key:generate
```

**4. Create the Database**

Start **Apache** and **MySQL** in XAMPP, then create a database named **`motsach`** (collation `utf8mb4_unicode_ci`) in phpMyAdmin. Check `.env`:

```env
DB_DATABASE=motsach
DB_USERNAME=root
DB_PASSWORD=
```

**5. Create Tables and Sample Data**

```bash
php artisan migrate:fresh --seed
php artisan motsach:ai-refresh
```

**6. Run the Website**

```bash
php artisan serve
```

Open **http://127.0.0.1:8000**. The admin dashboard is at `/admin` and the shipper app at `/shipper`.

**7. (Optional) Start the Queue Worker**

```bash
php artisan queue:work
```

### 🔑 Demo Accounts

All demo accounts use the password **`123456`**.

| Role | Email | Access |
|---|---|---|
| Admin | admin@motsach.vn | `/admin`: full access, including AI & analytics |
| Staff | staff@motsach.vn | `/admin`: books, inventory, orders, returns, reviews |
| Shipper | shipper@motsach.vn | `/shipper`: delivery app |
| Customer | khach@motsach.vn (also khach1 … khach40) | Storefront |

**Voucher codes:** `MOTSACH10`, `SACHHAY50K`, `MANGA15`, `FREESHIP`, `BLACKFRIDAY` (starts in 25 days)

### 🧪 Run Tests

```bash
php artisan test
```

### 🚚 Business Rules

- **Shipping fee:** Ho Chi Minh City 20,000 VND · nearby provinces 25,000 VND · other provinces 35,000 VND · **free** from 300,000 VND
- **Stock** is reduced when an order is placed and restored when it is cancelled or returned
- **COD** orders are marked as paid when delivered
- The sandbox payment gateway simulates a successful or failed payment

## 🔧 Quick Customization

| To change | Edit |
|---|---|
| Shipping fee, free-shipping threshold | `app/Services/CartService.php` (`FREE_SHIP_FROM`, `shippingFee()`) |
| Voucher codes and sample data | `database/seeders/ShopSeeder.php` |
| Sample books | `database/seeders/data/books.json` |
| Colors and fonts | `public/assets/css/site.css` |
| Admin colors | `public/assets/css/admin.css` |
| Customer menu and header | `resources/views/layouts/shop.blade.php` |
| Admin menu | `resources/views/layouts/admin.blade.php` |

## 🐛 Troubleshooting

| Problem | Fix |
|---|---|
| `vendor/autoload.php` not found | Run `composer install` |
| `Could not open input file: artisan` | `cd` into the folder that contains `artisan` |
| `Specified key was too long` | Already handled with `Schema::defaultStringLength(191)` |
| Page still shows an old error after editing a view | `php artisan view:clear` |
| Old settings still in use | `php artisan config:clear` |
| Reset all data | `php artisan migrate:fresh --seed` |

## 🎯 Purpose

This project was developed for educational purposes to practice:
- Back-end Web Development with Laravel
- Database Design with MySQL
- Responsive Web Design with Bootstrap
- E-commerce Website Development
- Admin Dashboard Development
- Applying AI algorithms in an e-commerce system
- Git & GitHub

## 🚀 Future Improvements

- Real payment gateway integration (VNPay / MoMo)
- Automatic payment confirmation through webhooks
- Email and SMS notifications through SMTP
- Product reviews with photos
- REST API with authentication (Laravel Sanctum)
- Deployment with Docker
- Advanced analytics dashboard

## 👨‍💻 Authors

Giáp Chí Thành

Student Project – BenBooks Book & Comic E-commerce Website

GitHub: [@giapchithanh06](https://github.com/giapchithanh06)
E-mail: chithanhgiap0409@gmail.com

## 📄 License

This project is intended for educational and learning purposes only.

Book titles, descriptions and covers are used only as sample data.
