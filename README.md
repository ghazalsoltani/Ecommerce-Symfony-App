# 🛍️ La Boutique Française

> A modern e-commerce platform built with Symfony 7, fully Dockerized for seamless development and deployment.

[![CI](https://github.com/ghazalsoltani/Ecommerce-Symfony-App/actions/workflows/ci.yml/badge.svg)](https://github.com/ghazalsoltani/Ecommerce-Symfony-App/actions/workflows/ci.yml)
[![Symfony](https://img.shields.io/badge/Symfony-7.x-000000?style=flat&logo=symfony)](https://symfony.com)
[![PHP](https://img.shields.io/badge/PHP-8.2+-777BB4?style=flat&logo=php&logoColor=white)](https://php.net)
[![MySQL](https://img.shields.io/badge/MySQL-8.0-4479A1?style=flat&logo=mysql&logoColor=white)](https://mysql.com)
[![Docker](https://img.shields.io/badge/Docker-Ready-2496ED?style=flat&logo=docker&logoColor=white)](https://docker.com)
[![Stripe](https://img.shields.io/badge/Stripe-Integrated-008CDD?style=flat&logo=stripe&logoColor=white)](https://stripe.com)
[![License](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE)

---

## 📋 Table of Contents

- [Overview](#-overview)
- [Features](#-features)
- [Tech Stack](#-tech-stack)
- [Architecture](#-architecture)
- [Installation](#-installation)
  - [Docker Setup (Recommended)](#-docker-setup-recommended)
  - [Traditional Setup](#-traditional-setup)
- [Async Processing](#-async-processing)
- [CI/CD Pipeline](#-cicd-pipeline)
- [Project Structure](#-project-structure)
- [Configuration](#-configuration)
- [Testing](#-testing)
- [Author](#-author)

---

## 🎯 Overview

La Boutique Française is a full-featured e-commerce platform implementing the complete online store lifecycle:

- **Product Management** – Catalog browsing with categories and featured products
- **User Experience** – Authentication, account management, wishlists
- **Shopping Flow** – Cart, multi-step checkout, secure payments
- **Order Processing** – Tracking, PDF invoices, email notifications
- **Async Processing** – Background job handling with Symfony Messenger
- **Administration** – Complete backoffice powered by EasyAdmin

The codebase follows Symfony best practices: service-oriented architecture, dependency injection, reusable Twig components, and well-structured domain logic.

---

## ✨ Features

### 🛒 Customer-Facing

| Feature | Description |
|---------|-------------|
| **Authentication** | Registration, login, logout, password reset with token verification |
| **User Account** | Profile management, multiple addresses, order history |
| **Product Catalog** | Categories, featured products, detail pages |
| **Wishlist** | Save products for later |
| **Shopping Cart** | Session-based cart with add/remove/update quantities |
| **Checkout** | Multi-step flow: Address → Carrier → Summary → Payment |
| **Payments** | Secure Stripe integration with PaymentIntent workflow |
| **Invoices** | PDF generation with DomPDF |
| **Emails** | Transactional emails via Mailjet (async processing) |

### 🛠 Admin Backoffice (EasyAdmin)

- Product, category, and user management
- Order workflow and status updates
- Custom admin views for order details
- Carrier management
- Homepage header configuration
- Featured product selection

### 🔐 Security

- Form login with password hashing (bcrypt)
- Role-based authorization (`ROLE_USER` / `ROLE_ADMIN`)
- CSRF protection on all forms
- Token-based password reset flow
- Secure session handling

---

## 🛠 Tech Stack

| Category | Technology |
|----------|------------|
| **Backend** | Symfony 7, PHP 8.2 |
| **Database** | MySQL 8.0 |
| **Frontend** | Twig, Bootstrap 5 |
| **Admin** | EasyAdmin 4 |
| **Payments** | Stripe API |
| **Emailing** | Mailjet |
| **PDF** | DomPDF |
| **Async Processing** | Symfony Messenger (Doctrine Transport) |
| **Build Tools** | Webpack Encore |
| **Containerization** | Docker, Docker Compose |
| **Web Server** | Nginx + PHP-FPM |
| **CI/CD** | GitHub Actions |
| **Testing** | PHPUnit 9.6 |

---

## 🏗 Architecture

### Application Layers

```
Request → Controller → Service → Repository → Database
              │
              ├──→ MessageBus ──→ Queue ──→ Worker
              │
              ├──→ Mailer ←→ Mailjet
              │
              └──→ Stripe API
```

| Layer | Responsibility |
|-------|----------------|
| **Controllers** | Handle HTTP requests, dispatch messages |
| **Services** | Business logic (Cart, Mail, Stripe, Order) |
| **Messages** | DTOs for async tasks (SendOrderConfirmationEmail) |
| **Handlers** | Process messages from queue |
| **Repositories** | Encapsulated database queries |
| **Forms + Validators** | Input handling and validation |
| **Event Subscribers** | Cross-cutting concerns |
| **Twig** | Presentation layer with reusable components |

### Data Model

```
User 1───* Address
User 1───* Order 1───* OrderDetails *───1 Product
Product *───1 Category
Order *───1 Carrier
User 1───* Wishlist *───1 Product
Header (Homepage banners)
```

---

## 🚀 Installation

### 🐳 Docker Setup (Recommended)

Docker provides an isolated, reproducible environment with all dependencies pre-configured.

#### Prerequisites

- [Docker Desktop](https://www.docker.com/products/docker-desktop/)
- Git

#### Quick Start

```bash
# Clone the repository
git clone https://github.com/ghazalsoltani/Ecommerce-Symfony-App.git
cd Ecommerce-Symfony-App

# Start all services (includes async worker)
docker compose up -d

# Wait for database to be healthy, then run migrations
docker compose exec php php bin/console doctrine:migrations:migrate --no-interaction

# Setup message queue table
docker compose exec php php bin/console messenger:setup-transports

# Build frontend assets
docker compose exec php npm install
docker compose exec php npm run build
```

#### Access Points

| Service | URL |
|---------|-----|
| **Application** | http://localhost:8080 |
| **Mailpit (Email Testing)** | http://localhost:8025 |
| **MySQL** | localhost:3307 |

#### Docker Architecture

```
┌─────────────────────────────────────────────────────────────────┐
│                       Docker Network                             │
│                                                                  │
│  ┌──────────┐    ┌──────────┐    ┌──────────────────┐          │
│  │  Nginx   │───▶│   PHP    │───▶│     MySQL        │          │
│  │  :8080   │    │  (FPM)   │    │     :3306        │          │
│  └──────────┘    └──────────┘    └──────────────────┘          │
│                        │                   ▲                    │
│                        │                   │                    │
│                        ▼                   │                    │
│                  ┌──────────┐              │                    │
│                  │  Worker  │──────────────┘                    │
│                  │ (Messenger)              (reads queue)       │
│                  └──────────┘                                   │
│                        │                                        │
│                        ▼                                        │
│                  ┌──────────┐                                   │
│                  │ Mailpit  │                                   │
│                  │  :8025   │                                   │
│                  └──────────┘                                   │
└─────────────────────────────────────────────────────────────────┘
```

#### Docker Services

| Service | Image | Description |
|---------|-------|-------------|
| `nginx` | nginx:alpine | Web server, reverse proxy to PHP-FPM |
| `php` | Custom (PHP 8.2-FPM) | Application runtime with Composer |
| `worker` | Custom (PHP 8.2-FPM) | Async message processor (Messenger) |
| `database` | mysql:8.0 | Data persistence |
| `mailer` | axllent/mailpit | Email testing interface |

#### Useful Docker Commands

```bash
# Start services
docker compose up -d

# Stop services
docker compose down

# Rebuild after Dockerfile changes
docker compose up --build -d

# View logs
docker compose logs -f php
docker compose logs -f worker
docker compose logs -f nginx

# Execute commands in PHP container
docker compose exec php php bin/console cache:clear
docker compose exec php composer require package-name

# Check worker status
docker compose logs worker
docker compose exec php php bin/console messenger:stats

# Access MySQL CLI
docker compose exec database mysql -u root laboutiquefrancaise

# Import database dump
docker cp backup.sql laboutiquefrancaise-database-1:/backup.sql
docker compose exec database sh -c "mysql -u root laboutiquefrancaise < /backup.sql"
```

---

### 💻 Traditional Setup

#### Prerequisites

- PHP 8.2+
- Composer
- Symfony CLI
- MySQL 8.0
- Node.js 18+

#### Installation Steps

```bash
# Clone repository
git clone https://github.com/ghazalsoltani/Ecommerce-Symfony-App.git
cd Ecommerce-Symfony-App

# Install PHP dependencies
composer install

# Configure environment
cp .env .env.local
# Edit .env.local with your database, Stripe, and Mailjet credentials

# Create database and run migrations
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate

# Setup message queue
php bin/console messenger:setup-transports

# Install and build frontend assets
npm install
npm run dev      # Development
npm run build    # Production

# Start development server
symfony serve

# In a separate terminal, start the message worker
php bin/console messenger:consume async -vv
```

---

## ⚡ Async Processing

This application uses **Symfony Messenger** for asynchronous task processing, improving response times and user experience.

### Why Async?

When a customer completes a payment, multiple tasks need to happen:

| Task | Sync (Before) | Async (Now) |
|------|---------------|-------------|
| Update order status | ✅ Immediate | ✅ Immediate |
| Send confirmation email | ❌ Customer waits 1-3s | ✅ Background |
| Response to customer | ~4 seconds | ~1 second |

The customer sees the success page immediately while heavy tasks (email, PDF) process in the background.

### Architecture

```
┌─────────────────────────────────────────────────────────────────┐
│                     Payment Success Flow                         │
├─────────────────────────────────────────────────────────────────┤
│                                                                  │
│   Customer pays                                                  │
│        │                                                         │
│        ▼                                                         │
│   PaymentController::success()                                   │
│        │                                                         │
│        ├── 1. Update order state (sync)                         │
│        ├── 2. Clear cart (sync)                                 │
│        └── 3. Dispatch SendOrderConfirmationEmail (async)       │
│                         │                                        │
│                         ▼                                        │
│        ┌────────────────────────────────┐                       │
│        │   messenger_messages (MySQL)   │                       │
│        │   ┌──────────────────────────┐ │                       │
│        │   │ orderId: 42              │ │                       │
│        │   │ status: pending          │ │                       │
│        │   └──────────────────────────┘ │                       │
│        └────────────────────────────────┘                       │
│                         │                                        │
│   ════════════════════════════════════════                      │
│   Customer sees "Thank you" page instantly                       │
│   ════════════════════════════════════════                      │
│                         │                                        │
│                         ▼                                        │
│        ┌────────────────────────────────┐                       │
│        │      Worker Container          │                       │
│        │  messenger:consume async       │                       │
│        └────────────────────────────────┘                       │
│                         │                                        │
│                         ▼                                        │
│        SendOrderConfirmationEmailHandler                         │
│                         │                                        │
│                         ▼                                        │
│              Email sent via Mailjet                              │
│                                                                  │
└─────────────────────────────────────────────────────────────────┘
```

### Components

| Component | Location | Purpose |
|-----------|----------|---------|
| Message | `src/Message/SendOrderConfirmationEmail.php` | DTO containing order ID |
| Handler | `src/MessageHandler/SendOrderConfirmationEmailHandler.php` | Fetches order, sends email |
| Template | `src/Mail/order_confirmation.html` | Email content |
| Config | `config/packages/messenger.yaml` | Transport and routing |

### Message Flow

```php
// In PaymentController - dispatch message to queue
$messageBus->dispatch(new SendOrderConfirmationEmail($order->getId()));

// Handler receives and processes (in background)
#[AsMessageHandler]
class SendOrderConfirmationEmailHandler
{
    public function __invoke(SendOrderConfirmationEmail $message): void
    {
        $order = $this->orderRepository->find($message->getOrderId());
        $this->mail->send(...);  // Send via Mailjet
    }
}
```

### Monitoring

```bash
# Check queue status
docker compose exec php php bin/console messenger:stats

# View worker logs
docker compose logs -f worker

# Process messages manually (if worker stopped)
docker compose exec php php bin/console messenger:consume async -vv

# View failed messages
docker compose exec php php bin/console messenger:failed:show
```

### Configuration

```yaml
# config/packages/messenger.yaml
framework:
    messenger:
        transports:
            async:
                dsn: '%env(MESSENGER_TRANSPORT_DSN)%'  # doctrine://default
                retry_strategy:
                    max_retries: 3
                    multiplier: 2
            failed: 'doctrine://default?queue_name=failed'
        routing:
            App\Message\SendOrderConfirmationEmail: async
```

---

## 🔄 CI/CD Pipeline

Automated testing and validation on every push using GitHub Actions.

### Pipeline Overview

```
┌─────────────────────────────────────────────────────────────────┐
│                     GitHub Actions CI                            │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────────────┐  │
│  │    Tests     │  │ Code Quality │  │    Docker Build      │  │
│  │   PHPUnit    │  │   Validate   │  │   Build PHP Image    │  │
│  │  + MySQL     │  │   Security   │  │                      │  │
│  └──────────────┘  └──────────────┘  └──────────────────────┘  │
└─────────────────────────────────────────────────────────────────┘
```

### Jobs

| Job | Description |
|-----|-------------|
| **tests** | Runs PHPUnit test suite with MySQL 8.0 service |
| **code-quality** | Validates composer.json, checks Symfony requirements |
| **docker-build** | Builds Docker image to verify Dockerfile |

### Test Results

| Test Suite | Tests | Assertions |
|------------|-------|------------|
| ProductTest | 2 | 2 |
| CartTest | 6 | 12 |
| HomeControllerTest | 9 | 15 |
| RegisterUserTest | 1 | 1 |
| **Total** | **18** | **30** |

---

## ⚙️ Configuration

### Environment Variables

Create `.env.local` and configure:

```env
# Database
DATABASE_URL="mysql://root:@127.0.0.1:3306/laboutiquefrancaise?serverVersion=8.0"

# Stripe
STRIPE_SECRET_KEY=sk_test_xxx
STRIPE_PUBLIC_KEY=pk_test_xxx

# Mailjet
MJ_APIKEY_PUBLIC=xxx
MJ_APIKEY_PRIVATE=xxx

# Messenger (async processing)
MESSENGER_TRANSPORT_DSN=doctrine://default?auto_setup=0

# Domain for Stripe redirects
DOMAIN="http://localhost:8080"
```

> **Note:** When using Docker, `DATABASE_URL` is automatically set via `compose.yaml`.

---

## 🧪 Testing

```bash
# Run all tests
php bin/phpunit

# Run specific test suites
php bin/phpunit --testsuite=unit
php bin/phpunit --testsuite=functional

# With Docker
docker compose exec php php bin/phpunit
```

---

## 🔑 Key Implementations

### Stripe Payment Integration

- PaymentIntent workflow for secure transactions
- Client-side tokenization
- Webhook-ready architecture

### Mailjet Emailing (Async)

- HTML email templates
- Welcome emails on registration
- Order confirmation notifications (async via Messenger)
- Password reset flow

### Cart System

- Session-based persistence
- Dynamic price calculation
- Stock validation on checkout

### PDF Invoice Generation

- Twig template rendering
- DomPDF integration
- On-demand generation for customers and admins

### Async Processing

- Symfony Messenger with Doctrine transport
- Dedicated worker container
- Automatic retry on failure (3 attempts)
- Failed message queue for inspection

---

## 👤 Author

**Ghazal Soltani**

[![GitHub](https://img.shields.io/badge/GitHub-ghazalsoltani-181717?style=flat&logo=github)](https://github.com/ghazalsoltani)
[![LinkedIn](https://img.shields.io/badge/LinkedIn-Connect-0A66C2?style=flat&logo=linkedin)](https://linkedin.com/in/ghazalsoltani)

---
