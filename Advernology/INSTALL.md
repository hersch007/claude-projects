# Advernology Service — Installation Guide

## Prerequisites
- PHP 8.2+
- Composer 2.x
- MySQL 8.0+
- Node.js 18+ & npm
- A server with an existing WordPress install (shared hosting OK)

---

## 1. Shared Hosting Setup (alongside WordPress)

WordPress typically lives at `public_html/`. We install Laravel in a **sibling directory**
and point a subdomain (e.g. `app.advernologyservice.com`) at `public_html/laravel/public/`.

```
/home/youraccount/
├── public_html/          ← WordPress root
└── laravel/              ← Laravel root  ← you install here
    └── public/           ← point subdomain document root here
```

### Steps on cPanel / Plesk
1. SSH into your server.
2. `cd /home/youraccount`
3. Follow Section 2 below.
4. In cPanel → **Subdomains** → create `app.advernologyservice.com`
   pointing Document Root to `/home/youraccount/laravel/public`

---

## 2. Installation Commands

```bash
# 1. Clone / upload project
cd /home/youraccount
composer create-project laravel/laravel laravel --prefer-dist "^11.0"
cd laravel

# 2. Copy all application files from this package into the laravel/ directory
#    (overwrite existing files when prompted)

# 3. Install PHP dependencies
composer require filament/filament:"^3.2" -W
composer require maatwebsite/excel:"^3.1"
composer require anthropic-php/client
composer require barryvdh/laravel-dompdf

# 4. Install Node dependencies & build assets
npm install
npm run build

# 5. Environment setup
cp .env.example .env
php artisan key:generate

# 6. Edit .env — set DB, mail, Anthropic, Stripe/SwipePay credentials
#    (see Section 3)

# 7. Run migrations & seed
php artisan migrate --seed

# 8. Create Filament admin user
php artisan make:filament-user

# 9. Create storage symlink
php artisan storage:link

# 10. Set permissions (Linux)
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

---

## 3. .env Key Variables

```ini
APP_NAME="Advernology Service"
APP_URL=https://app.advernologyservice.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=advernology
DB_USERNAME=advernology_user
DB_PASSWORD=your_secure_password

ANTHROPIC_API_KEY=sk-ant-...
ANTHROPIC_MODEL=claude-sonnet-4-6

# SwipePay / payment gateway
SWIPEPAY_API_KEY=
SWIPEPAY_MERCHANT_ID=

MAIL_MAILER=smtp
MAIL_HOST=smtp.yourhost.com
MAIL_PORT=587
MAIL_USERNAME=
MAIL_PASSWORD=
MAIL_FROM_ADDRESS=noreply@advernologyservice.com
MAIL_FROM_NAME="Advernology Service"
```

---

## 4. Upgrade Notes
- Run `php artisan migrate` after any pull that adds new migrations.
- Run `npm run build` after any frontend asset change.
- Clear caches: `php artisan optimize:clear`
