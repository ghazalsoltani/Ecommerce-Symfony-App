# 🛍️ La Boutique Française

> A modern e-commerce platform built with Symfony 7, fully Dockerized for seamless development and deployment.

[![CI](https://github.com/ghazalsoltani/Ecommerce-Symfony-App/actions/workflows/ci.yml/badge.svg)](https://github.com/ghazalsoltani/laboutiquefrancaise/actions/workflows/ci.yml)
[![Symfony](https://img.shields.io/badge/Symfony-7.x-000000?style=flat&logo=symfony)](https://symfony.com)
[![PHP](https://img.shields.io/badge/PHP-8.2+-777BB4?style=flat&logo=php&logoColor=white)](https://php.net)
[![MySQL](https://img.shields.io/badge/MySQL-8.0-4479A1?style=flat&logo=mysql&logoColor=white)](https://mysql.com)
[![Docker](https://img.shields.io/badge/Docker-Ready-2496ED?style=flat&logo=docker&logoColor=white)](https://docker.com)
[![Stripe](https://img.shields.io/badge/Stripe-Integrated-008CDD?style=flat&logo=stripe&logoColor=white)](https://stripe.com)

---

## 📋 Table of Contents

- [Overview](#-overview)
- [Features](#-features)
- [Tech Stack](#-tech-stack)
- [Architecture](#-architecture)
- [Installation](#-installation)
  - [Docker Setup (Recommended)](#-docker-setup-recommended)
  - [Traditional Setup](#-traditional-setup)
- [Project Structure](#-project-structure)
- [Configuration](#-configuration)
- [Testing](#-testing)
- [CI/CD](#-cicd)
- [Author](#-author)

---

## 🎯 Overview

La Boutique Française is a full-featured e-commerce platform implementing the complete online store lifecycle:

- **Product Management** – Catalog browsing with categories and featured products
- **User Experience** – Authentication, account management, wishlists
- **Shopping Flow** – Cart, multi-step checkout, secure payments
- **Order Processing** – Tracking, PDF invoices, email notifications
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
| **Emails** | Transactional emails via Mailjet |

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
- Regular security audits via Composer

---

## 🛠 Tech Stack

| Category | Technology |
|----------|------------|
| **Backend** | Symfony 7.1, PHP 8.2 |
| **Database** | MySQL 8.0 |
| **Frontend** | Twig, Bootstrap 5 |
| **Admin** | EasyAdmin 4 |
| **Payments** | Stripe API |
| **Emailing** | Mailjet |
| **PDF** | DomPDF |
| **Build Tools** | Webpack Encore |
| **Containerization** | Docker, Docker Compose |
| **Web Server** | Nginx + PHP-FPM |
| **CI/CD** | GitHub Actions |
| **Testing** | PHPUnit 9.6 |

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
git clone https://github.com/ghazalsoltani/laboutiquefrancaise.git
cd laboutiquefrancaise

# Start all services
docker compose up -d

# Wait for database to be healthy, then run migrations
docker compose exec php php bin/console doctrine:migrations:migrate --no-interaction

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
┌─────────────────────────────────────────────────────────┐
│                    Docker Network                        │
│                                                          │
│  ┌──────────┐    ┌──────────┐    ┌──────────────────┐  │
│  │  Nginx   │───▶│   PHP    │───▶│     MySQL        │  │
│  │  :8080   │    │  (FPM)   │    │     :3306        │  │
│  └──────────┘    └──────────┘    └──────────────────┘  │
│                        │                                 │
│                        ▼                                 │
│                  ┌──────────┐                           │
│                  │ Mailpit  │                           │
│                  │  :8025   │                           │
│                  └──────────┘                           │
└─────────────────────────────────────────────────────────┘
```

#### Docker Services

| Service | Image | Description |
|---------|-------|-------------|
| `nginx` | nginx:alpine | Web server, reverse proxy to PHP-FPM |
| `php` | Custom (PHP 8.2-FPM) | Application runtime with Composer |
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
docker compose logs -f nginx

# Execute commands in PHP container
docker compose exec php php bin/console cache:clear
docker compose exec php composer require package-name

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
cd laboutiquefrancaise

# Install PHP dependencies
composer install

# Configure environment
cp .env .env.local
# Edit .env.local with your database, Stripe, and Mailjet credentials

# Create database and run migrations
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate

# Install and build frontend assets
npm install
npm run dev      # Development
npm run build    # Production

# Start development server
symfony serve
```

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
MAILJET_API_KEY=xxx
MAILJET_SECRET_KEY=xxx
```

> **Note:** When using Docker, `DATABASE_URL` is automatically set via `compose.yaml`.

---

## 🧪 Testing

The project includes a comprehensive test suite with unit and functional tests.

### Test Structure

```
tests/
├── ProductTest.php        # Unit tests for Product entity
├── CartTest.php           # Unit tests for Cart service (6 tests)
├── HomeControllerTest.php # Functional tests for homepage
└── RegisterUserTest.php   # Functional tests for user registration
```

### Running Tests

```bash
# Run all tests
php bin/phpunit

# Run with verbose output
php bin/phpunit --testdox

# Run specific test file
php bin/phpunit tests/CartTest.php

# With Docker
docker compose exec php php bin/phpunit
```

### Test Database Setup

Tests use a separate database to avoid affecting development data:

```bash
# Create test database
php bin/console doctrine:database:create --env=test
php bin/console doctrine:schema:create --env=test
```

### Current Test Coverage

| Test Suite | Tests | Assertions | Status |
|------------|-------|------------|--------|
| ProductTest | 2 | 2 | ✅ Pass |
| CartTest | 6 | 12 | ✅ Pass |
| HomeControllerTest | 9 | 15 | ✅ Pass |
| RegisterUserTest | 1 | 1 | ✅ Pass |
| **Total** | **18** | **30** | ✅ **All Pass** |

---

## 🔄 CI/CD

The project uses GitHub Actions for continuous integration and deployment verification.

### Pipeline Overview

The CI pipeline runs on every push to `main` and on pull requests, executing three parallel jobs:

```
┌─────────────────────────────────────────────────────────┐
│                   GitHub Actions CI                      │
│                                                          │
│  ┌──────────┐    ┌──────────────┐    ┌──────────────┐  │
│  │  Tests   │    │ Code Quality │    │ Docker Build │  │
│  │          │    │              │    │              │  │
│  │ PHPUnit  │    │  Validate    │    │  Build PHP   │  │
│  │ + MySQL  │    │  Security    │    │    Image     │  │
│  └──────────┘    └──────────────┘    └──────────────┘  │
└─────────────────────────────────────────────────────────┘
```

### Jobs Description

| Job | Purpose | Key Steps |
|-----|---------|-----------|
| **tests** | Run PHPUnit test suite | Setup PHP 8.2, MySQL service, install deps, run tests |
| **code-quality** | Validate code integrity | Composer validate, Symfony requirements check |
| **docker-build** | Verify Docker configuration | Build PHP image, validate compose config |

### Workflow File

Located at `.github/workflows/ci.yml`

### Badge

Add this badge to track CI status:

```markdown
[![CI](https://github.com/ghazalsoltani/laboutiquefrancaise/actions/workflows/ci.yml/badge.svg)](https://github.com/ghazalsoltani/laboutiquefrancaise/actions/workflows/ci.yml)
```

---

## 🔑 Key Implementations

### Stripe Payment Integration

- PaymentIntent workflow for secure transactions
- Client-side tokenization
- Webhook-ready architecture

### Mailjet Emailing

- HTML email templates
- Welcome emails on registration
- Order confirmation notifications
- Password reset flow

### Cart System

- Session-based persistence
- Dynamic price calculation
- Stock validation on checkout

### PDF Invoice Generation

- Twig template rendering
- DomPDF integration
- Automatic generation on order completion

---

## 👤 Author

**Ghazal Soltani**

[![GitHub](https://img.shields.io/badge/GitHub-ghazalsoltani-181717?style=flat&logo=github)](https://github.com/ghazalsoltani)
[![LinkedIn](https://img.shields.io/badge/LinkedIn-Connect-0A66C2?style=flat&logo=linkedin)](https://linkedin.com/in/ghazalsoltani)
