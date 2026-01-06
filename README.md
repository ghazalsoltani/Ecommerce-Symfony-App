<img width="108" height="27" alt="logo" src="https://github.com/user-attachments/assets/093e2dc3-1a58-4863-ba72-c3fe81442572" />

## Backend

<div align="center">

### E-commerce REST API built with Symfony 7 | Deployed on Railway

  ![Ghazalea Demo](./demo/Ghazalea-demo.gif)

🌐 **[Live](https://ghazalea.com)** · 📡 **[API Endpoint](https://ghazalea-backend-production.up.railway.app/api/products)** · 🎨 **[Frontend Repo](https://github.com/ghazalsoltani/ghazalea-frontend)**



[![CI/CD](https://github.com/ghazalsoltani/ghazalea-backend/actions/workflows/ci.yml/badge.svg)](https://github.com/ghazalsoltani/ghazalea-backend/actions/workflows/ci.yml)
[![Symfony](https://img.shields.io/badge/Symfony-7.x-000000?style=flat&logo=symfony)](https://symfony.com)
[![PHP](https://img.shields.io/badge/PHP-8.2-777BB4?style=flat&logo=php&logoColor=white)](https://php.net)
[![MySQL](https://img.shields.io/badge/MySQL-8.0-4479A1?style=flat&logo=mysql&logoColor=white)](https://mysql.com)
[![Docker](https://img.shields.io/badge/Docker-Ready-2496ED?style=flat&logo=docker&logoColor=white)](https://docker.com)
[![Railway](https://img.shields.io/badge/Railway-Deployed-0B0D0E?style=flat&logo=railway)](https://railway.app)

</div>

---

## ⚡ Quick Overview

> A **production-ready** e-commerce API powering [ghazalea.com](https://ghazalea.com) - a French artisanal accessories boutique.

| 🎯 What I Built | 🛠️ How I Built It |
|-----------------|-------------------|
| REST API with 15+ endpoints | **API Platform** + Symfony 7 |
| JWT Authentication | **LexikJWT** + bcrypt |
| Async Email Processing | **Symfony Messenger** + Doctrine Transport |
| Stripe Payment Integration | **PaymentIntent** workflow |
| Admin Dashboard | **EasyAdmin 4** |
| CI/CD Pipeline | **GitHub Actions** (tests, security, docker) |
| Production Deployment | **Railway** + MySQL |

---

## 🏆 Key Technical Achievements

### 1️⃣ Async Processing with Symfony Messenger
```
Customer pays → Response in 1s (vs 4s before)
                    ↓
              Background worker sends email via Mailjet
```

### 2️⃣ Full CI/CD Pipeline
```yaml
✅ Code Quality  →  ✅ PHPUnit Tests  →  ✅ Docker Build  →  ✅ Auto-Deploy
```

### 3️⃣ Production Architecture
```
React (Vercel)  ──→  Symfony API (Railway)  ──→  MySQL (Railway)
                            ↓
                    Stripe + Mailjet + Worker
```

---

## 🚀 Quick Start

```bash
# Clone & Start (Docker)
git clone https://github.com/ghazalsoltani/ghazalea-backend.git
cd ghazalea-backend
docker compose up -d

# Access
# App:     http://localhost:8080
# API:     http://localhost:8080/api/products
# Admin:   http://localhost:8080/admin
# Mailpit: http://localhost:8025
```

---

## 📡 API Endpoints

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| `GET` | `/api/products` | ❌ | List products (paginated) |
| `GET` | `/api/categories` | ❌ | List categories |
| `POST` | `/api/login_check` | ❌ | JWT authentication |
| `GET` | `/api/wishlist` | ✅ | User's favorites |
| `POST` | `/api/checkout/create-session` | ✅ | Stripe checkout |
| `GET` | `/api/orders` | ✅ | Order history |

****[Test API →](https://ghazalea-backend-production.up.railway.app/api/products)**

---

## 🛠️ Tech Stack

<table>
<tr>
<td align="center" width="150">

**Backend**<br>
Symfony 7<br>
PHP 8.2<br>
API Platform

</td>
<td align="center" width="150">

**Database**<br>
MySQL 8.0<br>
Doctrine ORM<br>
Migrations

</td>
<td align="center" width="150">

**DevOps**<br>
Docker<br>
GitHub Actions<br>
Railway

</td>
<td align="center" width="150">

**Integrations**<br>
Stripe<br>
Mailjet<br>
JWT Auth

</td>
</tr>
</table>

---

## 🏗️ Architecture

```
┌─────────────────────────────────────────────────────────────┐
│                    PRODUCTION STACK                          │
├─────────────────────────────────────────────────────────────┤
│                                                              │
│   [Vercel]           [Railway]              [Railway]        │
│   React App    ───▶   Symfony API    ───▶   MySQL DB        │
│   ghazalea.com        + Nginx/PHP-FPM       + Backups       │
│                            │                                 │
│                            ├──▶ Stripe API (Payments)        │
│                            ├──▶ Mailjet (Emails)             │
│                            └──▶ Messenger Worker (Async)     │
│                                                              │
└─────────────────────────────────────────────────────────────┘
```

---

## ✨ Features

<details>
<summary><strong>🛒 E-Commerce Features</strong></summary>

- Product catalog with categories
- Shopping cart & wishlist
- Multi-step checkout flow
- Stripe payment integration
- Order tracking & history
- PDF invoice generation

</details>

<details>
<summary><strong>🔐 Security</strong></summary>

- JWT authentication (LexikJWT)
- Role-based access (ROLE_USER, ROLE_ADMIN)
- CSRF protection
- Password hashing (bcrypt)
- CORS configuration

</details>

<details>
<summary><strong>⚡ Async Processing</strong></summary>

- Symfony Messenger with Doctrine transport
- Background email sending
- Automatic retry (3 attempts)
- Failed message queue
- Dedicated worker container

</details>

<details>
<summary><strong>🛠️ Admin Dashboard</strong></summary>

- EasyAdmin 4 integration
- Product/Category management
- Order workflow management
- User administration
- Homepage configuration

</details>

<details>
<summary><strong>🔄 CI/CD Pipeline</strong></summary>

- GitHub Actions workflow
- PHPUnit tests with MySQL
- Security vulnerability scanning
- Docker image building
- Auto-deploy on push to main

</details>

---

## 📊 Test Results

```
PHPUnit 9.6 Tests
─────────────────────────────
✅ ProductTest          2 tests
✅ CartTest             6 tests  
✅ HomeControllerTest   9 tests
✅ RegisterUserTest     1 test
─────────────────────────────
Total: 18 tests, 30 assertions
```


## 🔗 Links

| Resource | URL |
|----------|-----|
| 🌐 Live Site | [ghazalea.com](https://ghazalea.com) |
| 📡 API | [ghazalea-backend-production.up.railway.app/api](https://ghazalea-backend-production.up.railway.app/api/products) |
| 🎨 Frontend Repo | [github.com/ghazalsoltani/ghazalea-frontend](https://github.com/ghazalsoltani/ghazalea-frontend) |

---

## 👤 Author

<div align="center">

**Ghazal Soltani** - Full Stack Developer

[![GitHub](https://img.shields.io/badge/GitHub-ghazalsoltani-181717?style=for-the-badge&logo=github)](https://github.com/ghazalsoltani)
[![LinkedIn](https://img.shields.io/badge/LinkedIn-Connect-0A66C2?style=for-the-badge&logo=linkedin)](https://linkedin.com/in/ghazal-soltani)
[![Email](https://img.shields.io/badge/Email-Contact-EA4335?style=for-the-badge&logo=gmail)](mailto:ghazal.soltaninasab@gmail.com)

</div>

---

<div align="center">

⭐ **Star this repo if you find it helpful!** ⭐

</div>
