# Payroll Management System

PHP + MySQL admin panel for employee payroll, attendance import, and salary slip email delivery.

## Requirements

- PHP 8.0+ (mysqli, session, file uploads)
- MySQL / MariaDB
- Web server (XAMPP, Apache, etc.)

## Setup

1. Create database `payroll_db` and import `database.sql` (optional — schema auto-migrates on first load).
2. Edit `admin/config.php` with your DB credentials.
3. Run `admin/setup.php` once to create admin user (only when `PAYROLL_ALLOW_SETUP_TOOLS` is `true`).
4. Log in at `admin/index.php`.

**Production:** set in `admin/config.php`:

```php
define('PAYROLL_ALLOW_SETUP_TOOLS', false);
```

This disables `setup.php` and `seed_demo_data.php`.

## Features

- **Dashboard** — stats, monthly payroll table, selective salary slip email (PDF), slip send status
- **Employees** — CRUD, PAN/bank fields, filters, pagination, active/inactive
- **Employee profile** — salary breakdown (HRA, PF, etc.), calendar, manual attendance, PDF preview
- **Upload attendance** — CSV/Excel (list or monthly grid), preview before import
- **Reports** — department summary, CSV export
- **Slip history** — log of sent/failed emails
- **Settings** — SMTP, password, payroll rules, salary % split, admin users, payslip signature

## Salary logic

- **Paid days** = Present + (Half day × credit) + (Leave × credit)
- **Gross period** = (Base ÷ working days) × paid days
- **Net** = gross component split minus PF, professional tax, ESI (if applicable)
- Configure percentages under **Settings → Payroll Rules**

## Default login

Created via `setup.php` — typically `admin` / password you choose.
