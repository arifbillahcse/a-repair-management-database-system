# Repair Management System

**Version 1.3.0** — A full-featured repair shop management system built with raw PHP 8.1+ and MySQL.
No frameworks. Dark theme. PDO prepared statements throughout.

---

## Features

### Repair Tickets
- Create and track repairs through a 7-stage status lifecycle
- Status flow: `In Progress → On Hold / Waiting for Parts → Completed → Ready for Pickup → Collected`
- Upload and delete device photos per ticket
- Printable repair receipt / job sheet
- QR code lookup (`/api/repairs/qr`) — scan to open ticket
- Overdue day highlighting (>7 days warning, >14 days red)

### Client Management
- Three client types with distinct icon badges:
  - **Individual** — person icon, gray badge
  - **Company** — building icon, red badge
  - **Colleague** — star icon, purple badge
- Live autocomplete search — debounced, keyboard navigable dropdown
- Filter tabs by client type on the list page
- CSV export of the full client list
- **Customer profile with 4 tabs**: Overview, Repairs, Invoices, Timeline
- Timeline merges repairs and invoices chronologically per customer

### Invoicing
- Generate invoices directly from repair tickets
- **Multi-business support** — select which business profile issues each invoice
- Line items with quantity, unit price, discount %, and per-line tax
- Status lifecycle: Draft → Sent → Paid / Partially Paid / Overdue / Cancelled
- Mark as Paid or Sent with one click
- Printable invoice view with issuing business details

### Credit Notes
- Standalone credit notes with fully customisable company header per note
- Fill company details from saved business profiles with one click
- Line items with basic amount, VAT, and net amount
- Printable credit note view
- Reference original invoice number and date

### Multi-Business Profiles
- Manage up to N separate business entities (name, address, phone, email, VAT, tax ID, bank details)
- Select the issuing business per invoice or credit note
- Set a default business used as pre-fill
- Admin page to add, edit, reorder, and deactivate businesses
- Auto-seeded from existing company settings on first run

### Dashboard
- **Individual Revenue** card — revenue from individual clients this month
- **Colleague Revenue** card — revenue from colleague clients this month
- Repair status breakdown and recent activity

### Staff Management
- Full CRUD for technician and staff profiles
- Each staff member can have a linked user login account

### User & Access Control
- 4 roles: **Admin**, **Manager**, **Technician**, **Staff**
- Role hierarchy enforced on every route
- CSRF tokens on all forms, session timeout
- bcrypt password hashing (cost 12)

### Reports
- Summary report dashboard with key metrics and charts

### Data Import
- Upload CSV files to bulk-import clients or repairs
- Import summary page with row-level result feedback
- Downloadable CSV templates

### Admin — Company Settings
- Company name, address, phone, email, VAT number, tax ID
- Invoice prefix and default tax percentage
- Three signature blocks

### Admin — Business Profiles
- Full management UI for multiple issuing businesses
- Accessible at `/admin/businesses`

### Admin — System Information
- PHP runtime details (version, SAPI, memory, extensions, OPcache)
- Database info (MySQL version, charset, collation, table sizes)
- Disk space and upload path writability

### Developer & DevOps
- GitHub Actions CI/CD pipeline: PHP syntax lint → SSH deploy to production
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
APP_URL=http://localhost
APP_ENV=production
APP_DEBUG=false

DB_HOST=localhost
DB_NAME=repair_system
DB_USER=root
DB_PASS=
DB_PORT=3306

SESSION_TIMEOUT=1800
```

### 3. Create the database

```bash
mysql -u root -p -e "CREATE DATABASE repair_system CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -u root -p repair_system < schema.sql
```

### 4. Configure your web server

**Apache** — set `DocumentRoot` to the `public/` folder and ensure `AllowOverride All` is set:

```apache
DocumentRoot "/path/to/project/public"
<Directory "/path/to/project/public">
    Options Indexes FollowSymLinks
    AllowOverride All
    Require all granted
</Directory>
```

**Nginx:**

```nginx
root /path/to/project/public;
index index.php;

location / {
    try_files $uri $uri/ /index.php?$query_string;
}
```

### 5. Install Composer dependencies (optional)

```bash
composer install
```

Installs `endroid/qr-code` for QR generation. Without it the system falls back to Google Charts API.

### 6. Set permissions

```bash
mkdir -p public/uploads logs
chmod 755 public/uploads/ logs/
```

---

## Local Setup with Laragon (Windows)

1. Copy all project files directly into `C:\laragon\www\`
2. Open `C:\laragon\www\config\.env` and set `APP_URL=http://localhost`
3. In Laragon's `httpd.conf` change `DocumentRoot` to `C:/laragon/www/public`
4. Open HeidiSQL → create database → import `schema.sql`
5. Start All in Laragon → open `http://localhost`

## cPanel Setup

1. Upload all files to your domain folder (e.g. `app.yourdomain.com/`)
2. Place a `.htaccess` in the domain root with:
   ```apache
   RewriteEngine On
   RewriteRule ^$ public/ [L]
   RewriteRule ^((?!public/).*)$ public/$1 [L,NC]
   ```
3. Update `config/.env` with cPanel MySQL credentials (note: cPanel prefixes db names and usernames with your account name)
4. Import `schema.sql` via phpMyAdmin

---

## Directory Structure

```
/
├── config/
│   ├── constants.php       App-wide constants (statuses, roles, types)
│   ├── database.php        PDO connection config
│   └── .env                Environment variables (not committed)
│
├── src/
│   ├── Auth.php            Session auth, role checks, CSRF
│   ├── Database.php        PDO wrapper (fetchAll, fetchOne, insert, update…)
│   ├── Router.php          Lightweight HTTP router
│   ├── Utils.php           Flash messages, redirects, sanitisation
│   ├── Logger.php          Activity audit log writer
│   └── QRCode.php          QR generation (endroid or Google Charts fallback)
│
├── controllers/
│   ├── AuthController.php
│   ├── DashboardController.php
│   ├── RepairController.php
│   ├── CustomerController.php
│   ├── InvoiceController.php
│   ├── CreditNoteController.php
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
│   ├── CreditNote.php
│   ├── Business.php
│   ├── Staff.php
│   └── User.php
│
├── views/
│   ├── layouts/            header.php, sidebar.php, footer.php
│   ├── dashboard/          index.php
│   ├── repairs/            list, create, edit, view, print
│   ├── customers/          list, create, edit, view (with tabs)
│   ├── invoices/           list, create, view, print
│   ├── credit-notes/       list, create, edit, view, print
│   ├── staff/              list, create, edit, view
│   ├── reports/            index.php
│   ├── imports/            index.php, summary.php
│   ├── admin/              settings.php, businesses.php, sysinfo.php
│   └── errors/             403.php, 404.php
│
├── public/                 Web root — only this is publicly accessible
│   ├── index.php           Entry point + route definitions
│   ├── css/style.css       Dark theme stylesheet
│   ├── js/
│   │   ├── main.js
│   │   └── form-validation.js
│   └── uploads/            Device photos and documents
│
├── schema.sql              Complete DB schema (13 tables) + sample data
├── composer.json
├── CI-CD-Setup.md
└── .github/
    └── workflows/
        └── deploy.yml
```

---

## Database Schema

| Table | Description |
|---|---|
| `company_settings` | Business info, invoice prefix, tax rate, signatures |
| `businesses` | Multiple issuing-business profiles |
| `staff` | Technician / staff profiles |
| `users` | Login accounts linked to staff (bcrypt) |
| `customers` | Client records (Individual / Company / Colleague) |
| `products` | Parts and service items for invoice line items |
| `repairs` | Repair tickets with status, device info, photos |
| `invoices` | Invoices linked to repairs or clients, with business |
| `invoice_items` | Line items with qty, unit price, discount, tax |
| `credit_notes` | Standalone credit notes with per-note company header |
| `credit_note_items` | Line items for credit notes |
| `personal_notes` | Per-user notepad / to-do items |
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
| Admin | 4 | Full access including settings, businesses, system info |
| Manager | 3 | All modules + delete access |
| Staff | 2 | Repairs, clients, invoices, credit notes |
| Technician | 1 | Repairs and clients |

---

## CI/CD

A GitHub Actions pipeline runs on every push to `main`:

1. **Lint** — checks PHP syntax across all `.php` files
2. **Deploy** — SSHes into the production server and runs `git pull origin main`

See [CI-CD-Setup.md](CI-CD-Setup.md) for full setup instructions.

---

## Security

- Passwords hashed with bcrypt (cost 12)
- CSRF tokens validated on every `POST` request
- All user input sanitised with `htmlspecialchars`
- PDO prepared statements — no raw SQL interpolation
- Security headers: `X-Frame-Options`, `X-Content-Type-Options`, `X-XSS-Protection`, `Referrer-Policy`, `HSTS`
- Role-based access control enforced at controller level
- Session timeout after 30 minutes of inactivity (configurable)

---

## Changelog

### v1.3.0
- Multi-business profiles — manage N business entities, select per invoice/credit note
- Credit Notes module — standalone credit notes with per-note company header and business fill
- Customer profile redesigned with Overview / Repairs / Invoices / Timeline tabs
- Dashboard revenue cards replaced with Individual Revenue and Colleague Revenue
- Postal code validation relaxed to accept 4 or 5 digits
- Businesses admin page (`/admin/businesses`) for full CRUD management
- Staff link hidden from sidebar navigation
- Assignment section hidden on repair create/edit forms
- schema.sql updated to reflect all new tables and columns

### v1.2.0
- Live autocomplete client search on Customers and Repairs list pages
- Client type icon badges: Individual (person/gray), Company (building/red), Colleague (star/purple)
- Colleague client type added; Freelancer type removed
- System Information page under Settings
- GitHub Actions CI/CD pipeline

### v1.0.0
- Initial release: repairs, clients, invoices, staff, reports, import, admin, RBAC, dark theme

---

## License

MIT — free to use, modify, and distribute.
