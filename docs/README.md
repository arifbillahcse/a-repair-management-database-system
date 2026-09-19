# Live Demo — Repair Management System

A front-end prototype of the PHP/MySQL application in the repository root.
No server, no database: it runs entirely in the browser and is served free
from GitHub Pages.

**Live:** https://arifbillahcse.github.io/a-repair-management-database-system/

![Dashboard](assets/screenshots/01-dashboard.png)

Screenshots of every module are in the
[main README](../README.md#screenshots).

---

## What it demonstrates

| Module | What works |
|---|---|
| **Dashboard** | KPI cards, 12-month revenue chart, recent repairs, overdue-pickup alerts, technician workload, activity log |
| **Clients** | List with search / filter / sort / pagination, detail view with lifetime stats, create, edit, delete (FK-guarded), CSV export |
| **Repairs** | Full job lifecycle, status pipeline with legal-transition enforcement, technician assignment, photo attachments, locally generated QR tags, printable job sheet |
| **Invoices** | Create from a repair or from scratch, line-item editor with live VAT maths, mark sent, record full or partial payment, overdue detection, printable invoice |
| **Reports** | Revenue by month, jobs received, repairs by status, technician output, top clients, most-serviced brands — every chart backed by a table |
| **Staff** | Directory with per-technician workload and revenue, create, edit, remove |
| **Import** | Real CSV upload, parsed in-browser, per-row validation and a preview before anything is written |
| **Admin** | Company settings that feed the invoices, user accounts, storage/system info |

Plus: role switching (Admin / Manager / Technician / Staff) to show the
permission gates live, global search, light & dark themes, and full
responsive layout down to phone width.

## How the data works

Everything you add or change is written to **`localStorage`** and survives a
page refresh. The signed-in user sits in **`sessionStorage`**, so closing the
tab logs you out and the demo always opens clean.

Nothing is sent anywhere. **"Reset demo data"** in the top bar restores the
original dataset at any time.

Seed data: 57 clients, 71 repairs across all seven statuses, 34 invoices,
22 catalogue items, 6 staff — localised for Bangladesh (৳ BDT, 15% VAT,
`dd/mm/yyyy`, local names, cities and devices).

## Sign in

Any password is accepted. Use the role buttons, or `admin` / `manager` /
`tanvir` / `sadia`.

## How it maps to the PHP application

The structure is a deliberate one-to-one translation, not a rewrite:

| PHP | Demo |
|---|---|
| `src/Router.php` | `js/router.js` — hash routing, same `:id` matching |
| `models/BaseModel.php` | `js/db.js` — same `findById` / `findAll` / `create` / `update` / `delete` / `paginate` API over localStorage |
| `models/*.php` | `js/models/*.js` — SQL `WHERE`/`ORDER BY`/`GROUP BY` become filter/sort/reduce |
| `controllers/*.php` + `views/*.php` | `js/modules/*.js` |
| `src/Auth.php` | `js/auth.js` — the real role hierarchy and `can()` gates |
| `src/Utils.php` | `js/utils.js` |
| `config/constants.php` | `js/constants.js` |
| `public/css/style.css` | `assets/css/style.css` — **copied unchanged** |

Hash routing (`#/repairs/42`) is required because GitHub Pages serves static
files only; a path route would 404 on refresh or deep link.

The five PHP list views repeated the same table markup roughly five times;
`js/components/table.js` replaces all of it with one configurable component.

## Running it locally

```bash
cd docs
python3 -m http.server 8000
# then open http://localhost:8000
```

A server is needed because the seed data is loaded with `fetch()`, which
browsers block on `file://` URLs.

## Third-party code

Two optional CDN libraries, both degrading gracefully if blocked:

- **Chart.js 4.4** — dashboard and report charts
- **qrcode-generator 1.4** — QR tags, rendered locally as SVG so no repair
  data is ever sent to an image service

## Notes

- Repair photos are stored as base64 in `localStorage`, capped at 4 per job
  and 2 MB per image, to stay inside the ~5 MB browser quota.
- Passwords are never stored or checked — this is a UI prototype.
- The production application (PHP 8.1 + MySQL, 8 tables, session auth,
  server-side uploads and import) lives in the repository root.
