# Repair Management System

**Version 1.2.0** — A full-featured repair shop management system built with raw PHP 8.1+ and MySQL.
No frameworks. Dark theme. PDO prepared statements throughout.

---

## Features

### Repair Tickets
- Create and track repairs through a 7-stage status lifecycle
- Status flow: `In Progress → On Hold / Waiting for Parts → Completed → Ready for Pickup → Collected`
- Upload and delete device photos per ticket
- Assign repairs to technicians (staff)
- Printable repair receipt / job sheet
- QR code lookup (`/api/repairs/qr`) — scan to open ticket
- Overdue day highlighting (>7 days warning, >14 days red)

### Customer Management
- Three client types with distinct icon badges:
  - **Individual** — person icon, gray badge
  - **Company** — building icon, red badge
  - **Colleague** — star icon, purple badge
- Live autocomplete search — start typing a name and a dropdown appears instantly (debounced, keyboard navigable)
- Filter tabs by client type on the list page
- CSV export of the full customer list
- Full repair history per customer profile

### Invoicing
- Generate invoices directly from repair tickets
- Line items with quantity, unit price, and tax calculation (configurable %)
- Status lifecycle: Draft → Sent → Paid / Partially Paid / Overdue / Cancelled
- Mark as Paid or Sent with one click
- Printable invoice view

### Staff Management
- Full CRUD for technician and staff profiles
- Each staff member can have a linked user login account

### User & Access Control
- 4 roles: **Admin**, **Manager**, **Technician**, **Staff**
- Role hierarchy enforced on every route
- Admin panel: enable/disable accounts, reset passwords
- bcrypt password hashing (cost 12), CSRF tokens on all forms, session timeout

### Reports
- Summary report dashboard with key metrics

### Data Import
- Upload CSV files to bulk-import customers or repairs
- Import summary page with row-level result feedback
- Downloadable CSV templates

### Admin — Company Settings
- Company name, address, phone, email, VAT number, tax ID
- Invoice prefix and default tax percentage

### Admin — System Information
- PHP runtime details (version, SAPI, memory limit, upload limits, extensions, OPcache)
- Database info (MySQL version, database name, charset, collation, total size)
- Per-table row counts and sizes
- Disk free / total space and upload path writability

### Developer & DevOps
- GitHub Actions CI/CD pipeline: PHP syntax lint → SSH deploy to production
- Detailed [CI-CD-Setup.md](CI-CD-Setup.md) covering GitHub Actions + SSH and CyberPanel + Webhook
- Activity audit log: every create / update / delete / login action recorded

---

## Tech Stack

| Layer | Technology |
|---|---|
| Language | PHP 8.1+ |
| Database | MySQL 5.7+ / MariaDB 10.3+ |
| Frontend | Vanilla JS, CSS custom properties |
| Charts | Chart.js 4 (CDN, dashboard/reports only) |
| QR Codes | endroid/qr-code ^5 (Composer) · Google Charts fallback |
| Auth | bcrypt + PHP sessions + CSRF |
| CI/CD | GitHub Actions → SSH (appleboy/ssh-action) |

---

## Requirements

- PHP 8.1+ with extensions: `pdo`, `pdo_mysql`, `json`, `mbstring`, `fileinfo`
- MySQL 5.7+ or MariaDB 10.3+
- Apache with `mod_rewrite` enabled (`AllowOverride All`) **or** Nginx with `try_files`
- Composer (optional — needed only for QR code generation via endroid)

---

## Installation

### 1. Clone the repository

```bash
git clone https://github.com/arifbillahcse/a-repair-management-database-system.git
cd a-repair-management-database-system
```

### 2. Configure the environment

```bash
cp config/.env.example config/.env
```

Edit `config/.env` with your database credentials and base URL:

```ini
APP_NAME=Repair Management System
APP_URL=https://yourdomain.com/public
APP_ENV=production
APP_DEBUG=false

DB_HOST=localhost
DB_NAME=repair_system
DB_USER=root
DB_PASS=secret
DB_PORT=3306

SESSION_TIMEOUT=1800
```

### 3. Create the database

```bash
mysql -u root -p -e "CREATE DATABASE repair_system CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -u root -p repair_system < schema.sql
```

### 4. Configure your web server

**Apache** — set `DocumentRoot` to the `public/` folder and ensure `AllowOverride All` is set.

**Nginx** — add a `try_files` rule pointing to `public/index.php`:

```nginx
root /path/to/a-repair-management-database-system/public;
index index.php;

location / {
    try_files $uri $uri/ /index.php?$query_string;
}
```

### 5. Install Composer dependencies (optional)

```bash
composer install
```

This installs `endroid/qr-code` for on-device QR code generation.
Without it, the system falls back to the Google Charts API (requires internet access).

### 6. Set permissions

```bash
chmod 755 public/uploads/
chmod -R 755 public/uploads/
mkdir -p logs && chmod 755 logs/
```

### 7. Create the admin account

Navigate to `https://yourdomain.com/public/` and log in. Then generate a real bcrypt hash:

```bash
php -r "echo password_hash('YourPassword1!', PASSWORD_BCRYPT, ['cost'=>12]);"
```

Update it in MySQL:

```sql
UPDATE users SET password_hash = '<output>' WHERE username = 'admin';
```

---

## Directory Structure

```
/
├── config/
│   ├── constants.php       App-wide constants (version, statuses, roles, types)
│   ├── database.php        PDO connection config
│   └── .env                Environment variables (not committed)
│
├── src/
│   ├── Auth.php            Session auth, role checks, CSRF
│   ├── Database.php        PDO wrapper (fetchAll, fetchOne, insert, update…)
│   ├── Router.php          Lightweight HTTP router
│   ├── Utils.php           Flash messages, redirects, sanitisation, escaping
│   ├── Logger.php          Activity audit log writer
│   └── QRCode.php          QR generation (endroid or Google Charts fallback)
│
├── controllers/
│   ├── AuthController.php
│   ├── DashboardController.php
│   ├── RepairController.php
│   ├── CustomerController.php
│   ├── InvoiceController.php
│   ├── StaffController.php
│   ├── ReportController.php
│   ├── ImportController.php
│   └── AdminController.php
│
├── models/
│   ├── BaseModel.php
│   ├── Customer.php
│   ├── Repair.php
│   ├── Invoice.php
│   ├── Staff.php
│   └── User.php
│
├── views/
│   ├── layouts/            header.php, sidebar.php, footer.php
│   ├── auth/               login.php, register.php
│   ├── dashboard/          index.php
│   ├── repairs/            list, create, edit, view, print
│   ├── customers/          list, create, edit, view
│   ├── invoices/           list, create, view, print
│   ├── staff/              list, create, edit, view
│   ├── reports/            index.php
│   ├── imports/            index.php, summary.php
│   ├── admin/              settings.php, users.php, sysinfo.php
│   └── errors/             403.php, 404.php
│
├── public/                 Web root — only this directory is publicly accessible
│   ├── index.php           Application entry point + route definitions
│   ├── css/style.css       Dark theme stylesheet (CSS custom properties)
│   ├── js/
│   │   ├── main.js         Sidebar, flash messages, autocomplete, delete confirm
│   │   └── form-validation.js
│   └── uploads/            Device photos and documents
│
├── schema.sql              Complete DB schema (9 tables) + sample data
├── composer.json
├── CI-CD-Setup.md          Deployment guide (GitHub Actions + CyberPanel)
└── .github/
    └── workflows/
        └── deploy.yml      PHP lint → SSH deploy pipeline
```

---

## Database Schema

| Table | Description |
|---|---|
| `company_settings` | Business info, invoice prefix, tax rate |
| `staff` | Technician / staff profiles |
| `users` | Login accounts linked to staff (bcrypt) |
| `customers` | Customer records (Individual / Company / Colleague) |
| `products` | Parts and service items for invoice line items |
| `repairs` | Repair tickets with status, device info, photos |
| `invoices` | Invoices linked to repairs or customers |
| `invoice_items` | Line items with qty, unit price, tax |
| `activity_log` | Audit trail for all actions |

---

## Repair Status Lifecycle

```
in_progress ──► on_hold
            ──► waiting_for_parts
            ──► completed ──► ready_for_pickup ──► collected
            ──► cancelled

on_hold     ──► in_progress
            ──► waiting_for_parts
            ──► cancelled

waiting_for_parts ──► in_progress
                  ──► on_hold
                  ──► cancelled

ready_for_pickup  ──► collected
                  ──► on_hold
```

---

## User Roles

| Role | Level | Permissions |
|---|---|---|
| Admin | 4 | Full access including settings, users, system info |
| Manager | 3 | All modules except admin settings |
| Technician | 1 | Repairs and customers |
| Staff | 2 | Repairs, customers, invoices |

---

## CI/CD

A GitHub Actions pipeline runs on every push to `main`:

1. **Lint** — checks PHP syntax across all `.php` files
2. **Deploy** — SSHes into the production server and runs `git pull origin main`

See [CI-CD-Setup.md](CI-CD-Setup.md) for full setup instructions including GitHub Secrets configuration and the CyberPanel webhook alternative.

---

## Security

- Passwords hashed with bcrypt (cost 12)
- CSRF tokens validated on every `POST` request
- All user input sanitised with `htmlspecialchars`
- PDO prepared statements — no raw SQL interpolation
- Security headers on every response: `X-Frame-Options`, `X-Content-Type-Options`, `X-XSS-Protection`, `Referrer-Policy`, `HSTS` (when HTTPS)
- Role-based access control enforced at controller level
- Session timeout after 30 minutes of inactivity (configurable)

---

## Changelog

### v1.2.0
- Live autocomplete customer search on both the Customers and Repairs list pages
- Customer type icon badges: Individual (person/gray), Company (building/red), Colleague (star/purple)
- Type column moved adjacent to Name/Customer in both list pages
- Colleague client type added; Freelancer type removed
- System Information page under Settings (PHP runtime, DB stats, disk usage)
- GitHub Actions CI/CD pipeline (`deploy.yml`)
- Autocomplete dropdown uses `position: fixed` + body injection to avoid z-index conflicts with sticky table headers

### v1.0.0
- Initial release: repairs, customers, invoices, staff, reports, import, admin, RBAC, dark theme

---

## License

MIT — free to use, modify, and distribute.
