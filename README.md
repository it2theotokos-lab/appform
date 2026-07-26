# AppForm - Custom Dynamic Form & User Portal Engine

AppForm is a lightweight, high-performance web platform built in Vanilla OOP PHP and MySQL. It features a customizable form builder, dynamic form renderer, role-based access control, file attachments, and reporting analytics.

---

## 🚀 Installation & Setup

### 1. Web Server Configuration
Point your web server's document root to the `public/` directory.

- **Apache**: The provided `public/.htaccess` routes requests through `public/index.php`.
- **NGINX / IIS**: Ensure rewrite rules forward all requests to `index.php`.

### 2. Database Installation
Configure your credentials in `config/config.php` (copy from `config/config.example.php`).
Run the database installer to create schemas:
```bash
php db/install.php
```

### 3. Run Migrations
Apply Phase 2, 3, and 4 incremental schema changes:
```bash
php db/migrate.php
```

---

## 🧪 Running Automated Unit Tests
To run all tests (Phase 1 through 4) sequentially:
```bash
php tests/run.php
```

---

## 🔒 Production Security Hardening
- Change the encryption keys in `config/config.php`.
- Set `display_errors` to `Off` in your production php.ini.
- Make sure `storage/` directory is writable but located outside your web server public root directory.
- Configure HTTPS only.
