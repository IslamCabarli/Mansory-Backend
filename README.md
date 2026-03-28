<div align="center">

# ⚙️ Mansory Backend

**RESTful API for Mansory Frontend — built with Laravel**

[![Laravel](https://img.shields.io/badge/Laravel-10+-FF2D20?style=flat-square&logo=laravel&logoColor=white)](https://laravel.com/)
[![PHP](https://img.shields.io/badge/PHP-8.1+-777BB4?style=flat-square&logo=php&logoColor=white)](https://www.php.net/)
[![MySQL](https://img.shields.io/badge/MySQL-8+-4479A1?style=flat-square&logo=mysql&logoColor=white)](https://www.mysql.com/)
[![Railway](https://img.shields.io/badge/Deployed%20on-Railway-0B0D0E?style=flat-square&logo=railway)](https://mansory-backend-production.up.railway.app)

[🌐 API Base URL](https://mansory-backend-production.up.railway.app/) · [🐛 Report a Bug](../../issues) · [💻 Frontend Repo](https://github.com/IslamCabarli/mansory-frontend)

</div>

---

## Table of Contents

- [About](#about)
- [API Endpoints](#api-endpoints)
- [Tech Stack](#tech-stack)
- [Getting Started](#getting-started)
- [Environment Variables](#environment-variables)
- [Database](#database)
- [Deployment](#deployment)

---

## About

**Mansory Backend** is the Laravel REST API that serves car, brand, and image data to the Mansory Frontend application. It runs on Railway with a MySQL database.

**Base URL:** `https://mansory-backend-production.up.railway.app/`

---

## API Endpoints

### Cars

| Method | Endpoint | Description | Auth |
|---|---|---|---|
| `GET` | `/cars` | List all cars | - |
| `GET` | `/cars/{id}` | Get a single car | - |
| `POST` | `/cars` | Create a new car | ✅ |
| `PUT` | `/cars/{id}` | Update a car | ✅ |
| `DELETE` | `/cars/{id}` | Delete a car | ✅ |

### Brands

| Method | Endpoint | Description | Auth |
|---|---|---|---|
| `GET` | `/brands` | List all brands | - |
| `GET` | `/brands/{id}` | Get a single brand | - |
| `POST` | `/brands` | Create a new brand | ✅ |
| `DELETE` | `/brands/{id}` | Delete a brand | ✅ |

### Images

| Method | Endpoint | Description | Auth |
|---|---|---|---|
| `POST` | `/cars/{id}/images` | Upload an image for a car | ✅ |
| `DELETE` | `/cars/{id}/images/{imageId}` | Delete an image | ✅ |

---

## Tech Stack

| Technology | Version | Purpose |
|---|---|---|
| [Laravel](https://laravel.com/) | 10+ | Core framework |
| [PHP](https://www.php.net/) | 8.1+ | Programming language |
| [MySQL](https://www.mysql.com/) | 8+ | Database |
| [Laravel Sanctum](https://laravel.com/docs/sanctum) | — | API authentication |

---

## Getting Started

### Prerequisites

- **PHP** ≥ 8.1
- **Composer** → [getcomposer.org](https://getcomposer.org)
- **MySQL** ≥ 8.0

### Installation

```bash
# 1. Clone the repository
git clone https://github.com/IslamCabarli/mansory-backend.git
cd mansory-backend

# 2. Install dependencies
composer install

# 3. Create the environment file
cp .env.example .env

# 4. Generate the application key
php artisan key:generate

# 5. Run database migrations
php artisan migrate

# 6. (Optional) Seed with sample data
php artisan db:seed

# 7. Start the local server
php artisan serve
```

API is ready at: **http://localhost:8000/api**

---

## Environment Variables

Fill in the following variables in your `.env` file:

```env
APP_NAME=MansoryBackend
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=mansory
DB_USERNAME=root
DB_PASSWORD=

FILESYSTEM_DISK=public
```

> ⚠️ Never commit your `.env` file to git.

---

## Database

### Main Tables

```
brands          cars                  car_images
──────          ────                  ──────────
id              id                    id
name            brand_id (FK)         car_id (FK)
created_at      name                  path
updated_at      engine                created_at
                power
                acceleration
                top_speed
                price
                description
                created_at
                updated_at
```

### Migrations

```bash
php artisan migrate           # Create tables
php artisan migrate:fresh     # Drop and recreate all tables
php artisan db:seed           # Insert sample data
```

---

## Deployment

The project is deployed on **Railway**. Every push to the `main` branch triggers an automatic deployment.

Add the following environment variables in your Railway project settings:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://mansory-backend-production.up.railway.app

DB_CONNECTION=mysql
DB_HOST=<railway-mysql-host>
DB_PORT=3306
DB_DATABASE=<db-name>
DB_USERNAME=<db-user>
DB_PASSWORD=<db-password>
```

---

<div align="center">
  <sub>Built with ❤️ for Mansory</sub>
</div>
