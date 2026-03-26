# Repair Management System

A full-featured repair shop management system built with raw PHP 8.1+ and MySQL.
Dark theme, no frameworks, PDO prepared statements throughout.

## Features

- **Repair Tickets** — full lifecycle tracking with QR codes
- **Customer Management** — 19k+ record support with search
- **Invoicing** — generate from repairs, PDF export
- **Staff & User Management** — RBAC with 4 roles
- **Activity Audit Log** — every action tracked
- **Dark Theme** — #1a1a1a background, #10b981 green accent

## Tech Stack

| Layer | Technology |
|-------|-----------|
| Language | PHP 8.1+ |
| Database | MySQL 5.7+ / MariaDB 10.3+ |
| Frontend | Vanilla JS, CSS custom properties |
| Charts | Chart.js (CDN, dashboard only) |
| QR Codes | endroid/qr-code (Composer) |
| Auth | bcrypt + sessions + CSRF |

## Installation

### 1. Requirements

- PHP 8.1+ with extensions: pdo, pdo_mysql, json, mbstring, fileinfo
- MySQL 5.7+ or MariaDB 10.3+
- Apache with mod_rewrite (or Nginx equivalent)
- Composer (optional, for QR code library)

### 2. Clone & configure

```bash
git clone https://github.com/arifbillahcse/a-repair-management-database-system.git
cd a-repair-management-database-system/repair-system
cp config/.env.example config/.env
```

Edit `config/.env` with your database credentials and `APP_URL`.

### 3. Create the database

```bash
mysql -u root -p -e "CREATE DATABASE repair_system CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -u root -p repair_system < ../schema.sql
```

### 4. Configure web server

**Apache** — set `DocumentRoot` to `repair-system/public/` and ensure `AllowOverride All`.

**Nginx** — add a `try_files` rule:
```nginx
location / {
    try_files $uri $uri/ /index.php?$query_string;
}
```

### 5. Install Composer dependencies (optional but recommended)

```bash
composer install
```

This installs `endroid/qr-code` for on-device QR code generation.
Without it, the system falls back to Google Charts API (requires internet).

### 6. Set permissions

```bash
chmod 755 public/uploads/
chmod 755 public/uploads/photos/
chmod 755 public/uploads/documents/
mkdir -p logs && chmod 755 logs/
```

### 7. Login

Navigate to `http://localhost/repair-system/public/` and log in with the
credentials from the sample data in `schema.sql`:

| Username | Password | Role |
|----------|----------|------|
| admin    | *(set via PHP)* | Admin |

> **Important:** The `schema.sql` sample passwords are placeholder hashes.
> Use the registration page (`/register`) while logged in as admin to create
> your first real account, then update the admin password hash directly in MySQL.

### Generating a real bcrypt hash

```php
php -r "echo password_hash('YourPassword1!', PASSWORD_BCRYPT, ['cost'=>12]);"
```

Then:
```sql
UPDATE users SET password_hash = '<output>' WHERE username = 'admin';
```

## Directory Structure

```
repair-system/
├── config/           Database config, constants, .env
├── src/              Core classes (Database, Auth, Router, Utils…)
├── controllers/      Request handlers
├── models/           Database models (BaseModel + entities)
├── views/            PHP templates (dark theme)
│   ├── layouts/      header, sidebar, footer
│   ├── auth/         login, register
│   ├── dashboard/    main dashboard
│   ├── repairs/      repair CRUD (Prompt 4)
│   ├── customers/    customer CRUD (Prompt 3)
│   ├── invoices/     invoice CRUD (Prompt 5)
│   └── errors/       404, 403
├── public/           Web root — only this is publicly accessible
│   ├── index.php     Application entry point + router
│   ├── css/          Dark theme stylesheet
│   ├── js/           main.js, form-validation.js
│   └── uploads/      Photos and documents
└── schema.sql        Complete database schema + sample data
```

## Build Status

| Prompt | Module | Status |
|--------|--------|--------|
| 1 | Database Schema | ✅ Complete |
| 2 | Core Structure & Auth | ✅ Complete |
| 3 | Customer Management | 🔄 Next |
| 4 | Repair Management | ⏳ Pending |
| 5 | Invoicing & Reports | ⏳ Pending |
| 6 | Admin & Polish | ⏳ Pending |

## License

MIT — free to use, modify, and distribute.
