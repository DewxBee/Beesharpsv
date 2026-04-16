# Bee Sharp SV — Deployment Guide (IONOS)

## Prerequisites
- IONOS Shared Hosting with PHP 8.1+
- MySQL/MariaDB database (create one in IONOS control panel)
- SFTP access (FileZilla or IONOS File Manager)

---

## Step 1 — Create Database

1. Log into **IONOS Control Panel** → **Hosting** → **Databases**
2. Create a new MySQL database
3. Note the **host**, **database name**, **username**, and **password**

---

## Step 2 — Set PHP Version

1. In IONOS Control Panel → **Hosting** → **PHP**
2. Select **PHP 8.1** or higher

---

## Step 3 — Upload Files

Upload all files to your web root (usually `public_html/` or `htdocs/`):

```
index.php
admin.php
config.php          ← template only, credentials written by installer
install.php
api.js
.htaccess
api/
db/
*.jpeg  *.png  *.webp  (all image assets)
```

> **Do NOT upload** `config.local.php` or `.installed` — these are generated on the server.

---

## Step 4 — Run the Installer

1. Open your browser: `https://beesharpsv.com/install.php`
2. Enter your IONOS database credentials
3. Set your admin username, email, and password (min 8 chars)
4. Click **Run Installation**
5. The installer will:
   - Create all database tables
   - Seed default settings
   - Create your admin account
   - Write `config.local.php` with your credentials
   - Create `.installed` lock file

---

## Step 5 — Delete install.php

**CRITICAL — Do this immediately after installation:**

Using SFTP or IONOS File Manager, delete:
- `install.php`
- `db/schema.sql` (optional but recommended)

---

## Step 6 — Test the Site

Work through this checklist:

- [ ] Visit `https://beesharpsv.com` — site loads
- [ ] EN/ES language toggle works
- [ ] WhatsApp floating button opens correct number
- [ ] Order form: add items, verify discount appears for Bitcoin payment
- [ ] Schedule page: calendar opens, time slots load
- [ ] Register a customer account
- [ ] Login as customer → "My Orders" shows (empty)
- [ ] Place an order as logged-in customer → WhatsApp link opens
- [ ] Visit `https://beesharpsv.com/admin` → login form appears
- [ ] Login to admin with credentials set in Step 4
- [ ] Admin dashboard shows stats
- [ ] Admin Orders page shows the test order
- [ ] Admin can change order status
- [ ] Visit `https://beesharpsv.com/config.php` → should get 403 Forbidden
- [ ] Visit `https://beesharpsv.com/install.php` → should get 404 (file deleted)

---

## Updating Settings

After deployment, update business settings in the admin panel:

- **Admin → Settings**: Bitcoin address (Lightning + on-chain), delivery fee, prices
- **Admin → Settings**: Social media handles (Instagram, Telegram, etc.)

---

## Security Notes

- `config.local.php` is gitignored and contains real DB credentials — never commit it
- `.installed` lock file prevents re-running the installer
- `display_errors` is OFF in production (set in `.htaccess`)
- All API endpoints use prepared statements — SQL injection protected
- Admin panel requires active session — brute force limited to 5 attempts / 15 min lockout
- CSRF tokens required on all POST endpoints

---

## Regenerate Secret Key

If you suspect the secret key is compromised:

1. Delete `config.local.php` from the server via SFTP
2. Delete `.installed` lock file
3. Re-run `install.php` with same DB credentials + new admin password
4. A new `SECRET_KEY` will be generated automatically

---

## File Structure on Server

```
/public_html/
├── .htaccess
├── .installed              ← created by installer (gitignored)
├── index.php               ← customer-facing site
├── admin.php               ← admin panel (PHP auth gate)
├── config.php              ← default constants
├── config.local.php        ← real credentials (created by installer, gitignored)
├── api.js                  ← frontend-to-API bridge
├── api/
│   ├── auth.php
│   ├── orders.php
│   ├── customers.php
│   ├── schedule.php
│   ├── notifications.php
│   └── settings.php
├── db/
│   └── schema.sql          ← delete from server after install
└── (image assets)
```
