# 🛍️ La Boutique Française – Symfony 7 E‑commerce Platform
[![Symfony](https://img.shields.io/badge/Symfony-7.0-000000?style=for-the-badge&logo=symfony&logoColor=white)](https://symfony.com/)
[![PHP](https://img.shields.io/badge/PHP-8.2-777BB4?style=for-the-badge&logo=php&logoColor=white)](https://www.php.net/)
[![MySQL](https://img.shields.io/badge/MySQL-8.0-4479A1?style=for-the-badge&logo=mysql&logoColor=white)](https://www.mysql.com/)
[![Bootstrap](https://img.shields.io/badge/Bootstrap-5.3-7952B3?style=for-the-badge&logo=bootstrap&logoColor=white)](https://getbootstrap.com/)
[![Stripe](https://img.shields.io/badge/Stripe-API-008CDD?style=for-the-badge&logo=stripe&logoColor=white)](https://stripe.com/)
[![Mailjet](https://img.shields.io/badge/Mailjet-API-FFCD00?style=for-the-badge&logo=mailjet&logoColor=black)](https://www.mailjet.com/)

A modern e‑commerce platform built with **Symfony 7**, designed with clean architecture principles, robust security, and real‑world integrations such as **Stripe**, **Mailjet**, **EasyAdmin**, and **DomPDF**. This project demonstrates concrete backend engineering skills through a production‑oriented workflow.

---

## 🚀 Overview

This application implements the full lifecycle of an online store:

* Product browsing and filtering
* User authentication & account management
* Shopping cart and checkout workflow
* Secure Stripe payments
* Order tracking and PDF invoices
* Transactional emails with Mailjet
* Complete backoffice powered by EasyAdmin

The codebase follows **Symfony best practices**: service-oriented architecture, dependency injection, reusable Twig components, separation of concerns, and well‑structured domain logic.

---

## 🌟 Main Features

### 🛒 Customer‑Facing

* User registration, authentication, logout, password reset
* Personal account: profile, addresses, order history
* Product catalog with categories and featured products
* Product detail pages
* Wishlist functionality
* Session‑based shopping cart (add/remove/decrease)
* Multi-step checkout: address → carrier → summary → payment
* Secure payments via Stripe
* PDF invoice generation (DomPDF)
* Email notifications (order confirmation, password reset)

### 🛠 Admin Backoffice (EasyAdmin)

* Product, category, header, and user management
* Order workflow & status updates
* Custom admin views for order details
* Carrier management
* Homepage header configuration
* Featured product selection

### 🔐 Security

* Form login with password hashing
* Role‑based authorization (USER / ADMIN)
* CSRF protection on all forms
* Token-based password reset flow
* Secure session handling

---

## 🧩 Architecture Overview

### Application Layers

* **Controllers** → Handle HTTP requests, minimal logic
* **Services** → Cart, Mail, Stripe, Order, etc.
* **Repositories** → Database queries encapsulated
* **Forms + Validators** → Input handling
* **Event Subscribers** → Cross‑cutting logic
* **Twig layer** → Presentation with reusable components

### High‑Level Flow

```
Request → Controller → Service → Repository → Database
                     ↓
                   Mailer
                     ↓
                 Stripe API
```

### Data Model (Simplified)

```
User 1---* Address
User 1---* Order 1---* OrderDetails *---1 Product
Product *---1 Category
Order 1---1 Carrier
User 1---* Wishlist *---1 Product
Header (Homepage banners)
```

---

## 🛠 Technology Stack

**Backend:** Symfony 7, PHP 8.2
**Frontend:** Twig, Bootstrap 5
**Database:** MySQL 8
**Admin:** EasyAdmin 4
**Emailing:** Mailjet
**Payment:** Stripe
**PDF:** DomPDF
**Build tools:** Webpack Encore

---

## 📁 Project Structure

```
project/
├── assets/              # Frontend assets
├── config/              # Symfony configuration
├── migrations/          # Doctrine migrations
├── public/              # Entry point + built assets
├── src/
│   ├── Controller/      # Front + admin controllers
│   ├── Entity/          # Doctrine entities
│   ├── Repository/      # Data layer
│   ├── Form/            # Symfony forms
│   ├── Service/         # Business logic
│   ├── Security/        # Authentication & voters
│   ├── EventSubscriber/ # Event listeners
│   └── Twig/            # Custom Twig extensions
├── templates/           # Views
├── tests/               # Unit + functional tests
└── webpack.config.js
```

---

## ⚙️ Installation

### Requirements

* PHP 8.2+
* Composer
* Symfony CLI
* MySQL 8
* Node.js 18+

### Setup

```bash
git clone https://github.com/ghazalsoltani/Ecommerce-Symfony-App.git
cd la-boutique-francaise
composer install
cp .env .env.local
```

Configure database, Stripe, and Mailjet keys in `.env.local`.

Create database & run migrations:

```bash
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
```

Install assets:

```bash
npm install
npm run dev      # or npm run build
```

Start the server:

```bash
symfony serve
```

---

## 🧪 Testing

```bash
php bin/phpunit                       # Run all tests
php bin/phpunit --testsuite=unit
php bin/phpunit --testsuite=functional
```

---

## 🧱 Key Implementations

### Stripe Payment

* PaymentIntent workflow
* Secure tokenization
* Webhook‑ready logic

### Mailjet Emailing

* HTML templates
* Welcome emails
* Order confirmations
* Password reset notifications

### Cart System

* Session‑based design
* Dynamic price calculation
* Validation on checkout

### PDF Invoices

* Twig → PDF rendering pipeline
* DomPDF integration

---

## 👤 Author

### **Ghazal Soltani**

<div align="center">

[![GitHub](https://img.shields.io/badge/GitHub-ghazalsoltani-181717?style=for-the-badge&logo=github&logoColor=white)](https://github.com/ghazalsoltani)


[![LinkedIn](https://img.shields.io/badge/LinkedIn-Ghazal_Soltani-0077B5?style=for-the-badge&logo=linkedin&logoColor=white)](https://www.linkedin.com/in/ghazal-soltani/)


</div>


