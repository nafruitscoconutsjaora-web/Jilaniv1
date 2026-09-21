# Rose SMM Panel - Production-Ready SMM Platform

A completely custom, secure, and production-ready Social Media Marketing (SMM) Panel engineered from scratch using **Core PHP 8.2**, **MariaDB/MySQL**, **Pure CSS / SCSS**, **Vanilla JavaScript**, and **Semantic HTML5**.

Built strictly with zero third-party frameworks (no Laravel, no React, no Tailwind, no Bootstrap) and adhering strictly to a zero-fake-data policy.

---

## 🌹 Design Identity & Architecture

- **Theme Palette**: Elegant Rose + White palette (`#e11d48` primary rose, `#fff1f2` light tints, `#881337` deep rose, neutral white and crisp light backgrounds).
- **Component Architecture**: 100% card-based UI. Strictly zero `<table>` elements across the entire application.
- **Independent Layouts**:
  1. **Public Landing Page**: Public navigation bar, interactive hero, catalog preview, login/register gates, and public footer.
  2. **User Dashboard Panel**: Dedicated user sidebar navigation, wallet balance indicator, currency selector, new order calculator, order history cards, add funds module (Razorpay), ticket conversation threads, profile security, and API documentation.
  3. **Super Admin Console**: Completely separate admin sidebar and header with administrative badge, global platform statistics, user ledger adjustments, order management & refunds, category and service management, external SMM provider integration with automatic profit margin importing, payment ledger tracking, currency management, support ticket replies, and system settings.

---

## 🚀 Default Administrator Credentials

- **URL**: `/login` (or directly `/admin`)
- **Username**: `admin`
- **Password**: `admin12345`
- **API Key**: `smm_adm_9f3b145a8e23f0c18d4512e7`

---

## 📦 Installation & Setup

### Requirements
- PHP 8.1 or PHP 8.2+
- MySQL 5.7+ or MariaDB 10.3+
- PDO MySQL extension (`pdo_mysql`)
- cURL extension (`curl`)
- OpenSSL extension (`openssl`)
- Apache with `mod_rewrite` (or Nginx)

### 1. Database Setup
1. Create a MySQL database (e.g. `smm_panel`).
2. Import `schema.sql`:
   ```bash
   mysql -u username -p smm_panel < schema.sql
   ```

### 2. Configuration
Copy `.env.example` to `.env` or edit `config.php`:
```ini
DB_HOST=localhost
DB_PORT=3306
DB_NAME=smm_panel
DB_USER=smm_user
DB_PASS=smm_pass_2026
APP_URL=https://yourdomain.com
```

### 3. cPanel Deployment
1. Upload the project zip or files to your cPanel `public_html` directory.
2. In cPanel **MySQL Databases**, create a database and database user with all privileges.
3. Open **phpMyAdmin**, select the created database, and import `schema.sql`.
4. Update the DB credentials in `config.php`.
5. Ensure `.htaccess` is present in `public_html` (enable "Show Hidden Files" in File Manager settings).

### 4. Automated Cron Job Configuration
To automatically synchronize order statuses with external SMM API providers, set up a cron job in cPanel:
```bash
*/5 * * * * curl -s "https://yourdomain.com/cron.php?key=cron_smm_secure_key_2026" >/dev/null 2>&1
```

---

## 💳 Payment Gateway Integration (Razorpay)

1. Log in to the Super Admin Console (`/admin/settings`).
2. Enter your Razorpay **Key ID**, **Key Secret**, and optional **Webhook Secret**.
3. Toggle Razorpay Gateway Status to **Enabled**.
4. Transactions are mathematically converted from USD to the user's selected display currency (e.g. INR) and verified via HMAC-SHA256 signature verification before wallet credit.

---

## 🔌 Standard SMM API (v2)

Our panel supports the industry standard SMM API v2 specification at endpoint `/api`:

### 1. Check User Balance
```bash
curl -X POST https://yourdomain.com/api \
  -d "key=YOUR_API_KEY" \
  -d "action=balance"
```

### 2. Retrieve Active Services
```bash
curl -X POST https://yourdomain.com/api \
  -d "key=YOUR_API_KEY" \
  -d "action=services"
```

### 3. Place Automated Order
```bash
curl -X POST https://yourdomain.com/api \
  -d "key=YOUR_API_KEY" \
  -d "action=add" \
  -d "service=1" \
  -d "link=https://example.com/target" \
  -d "quantity=1000"
```

### 4. Query Order Status
```bash
curl -X POST https://yourdomain.com/api \
  -d "key=YOUR_API_KEY" \
  -d "action=status" \
  -d "order=123"
```
