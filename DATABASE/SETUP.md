# BetterAbroad — XAMPP Setup Guide
## From zero to fully running in 10 minutes

---

## 1. Start XAMPP
Open the XAMPP Control Panel and **Start** both:
- ✅ Apache
- ✅ MySQL

---

## 2. Copy files to htdocs

Copy the entire `betterabroad/` folder into:
```
C:/xampp/htdocs/betterabroad/
```

Your structure should look like:
```
C:/xampp/htdocs/betterabroad/
├── index.html          ← Main app
├── admin.html          ← Admin panel
├── schema.sql          ← Database schema
├── uploads/            ← Create this folder manually!
│   ├── students/
│   └── universities/
└── api/
    ├── db.php
    ├── mailer.php
    ├── auth/
    │   ├── register.php
    │   ├── login.php
    │   ├── logout.php
    │   └── me.php
    ├── profiles/
    │   ├── save.php
    │   ├── upload.php
    │   └── applications.php
    ├── messages/
    │   ├── send.php
    │   ├── conversations.php
    │   ├── thread.php
    │   └── unread.php
    ├── marketplace/
    │   ├── students.php
    │   └── universities.php
    └── admin/
        ├── users.php
        └── verify.php
```

---

## 3. Create the database

1. Open your browser → go to **http://localhost/phpmyadmin**
2. Click **"New"** on the left sidebar
3. Type database name: `betterabroad` → click **Create**
4. Click the `betterabroad` database → click the **SQL** tab
5. Paste the entire contents of `schema.sql` → click **Go**

You should see all tables created ✓

---

## 4. Create the uploads folder

In Windows Explorer, navigate to:
```
C:/xampp/htdocs/betterabroad/
```
Create a folder called `uploads/` with subfolders:
```
uploads/
  students/
  universities/
```
Right-click `uploads/` → Properties → make sure it has write permissions.

---

## 5. Install PHPMailer (for email notifications)

**Option A — Composer (recommended):**
```bash
cd C:/xampp/htdocs/betterabroad
composer require phpmailer/phpmailer
```

**Option B — Manual download:**
1. Go to https://github.com/PHPMailer/PHPMailer
2. Download ZIP → extract
3. Copy `PHPMailer.php`, `SMTP.php`, `Exception.php` into:
   ```
   C:/xampp/htdocs/betterabroad/vendor/phpmailer/
   ```

**Configure Gmail SMTP** (edit `api/mailer.php`):
```php
define('MAIL_USERNAME', 'your.gmail@gmail.com');
define('MAIL_PASSWORD', 'your-16-char-app-password');
```
To get a Gmail App Password:
1. Enable 2FA at myaccount.google.com
2. Go to Security → App Passwords
3. Create one for "Mail" → copy the 16-char password

---

## 6. Test the app

Open your browser:
- **Main app:** http://localhost/betterabroad/
- **Admin panel:** http://localhost/betterabroad/admin.html
  - Login: `admin@betterabroad.com` / `admin123`

---

## 7. Troubleshooting

| Problem | Fix |
|---------|-----|
| "Cannot connect to server" | Make sure Apache AND MySQL are running in XAMPP |
| "Access denied for user root" | In `api/db.php`, set `DB_PASS = ''` (XAMPP default is empty) |
| "Table doesn't exist" | Re-run the SQL from `schema.sql` in phpMyAdmin |
| CORS error in browser | Make sure you're accessing via `http://localhost/` not file:// |
| File upload fails | Check that `uploads/` folder exists and has write permissions |
| Emails not sending | Verify Gmail App Password; check spam folder; test SMTP settings |

---

## 8. API Reference

All endpoints require `credentials: 'include'` for session cookies.

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/auth/register.php` | Create account |
| POST | `/api/auth/login.php` | Login |
| POST | `/api/auth/logout.php` | Logout |
| GET  | `/api/auth/me.php` | Get current user + profile |
| POST | `/api/profiles/save.php` | Save profile data |
| POST | `/api/profiles/upload.php` | Upload document |
| GET  | `/api/marketplace/students.php` | Browse students (university only) |
| GET  | `/api/marketplace/universities.php` | Browse universities (student only) |
| GET  | `/api/messages/conversations.php` | List conversations |
| GET  | `/api/messages/thread.php?with=ID` | Get message thread |
| POST | `/api/messages/thread.php` | Send message |
| GET  | `/api/messages/unread.php` | Get unread count |
| POST | `/api/profiles/applications.php` | Submit application |
| GET  | `/api/profiles/applications.php` | Get my applications |
| POST | `/api/admin/verify.php` | Verify/reject profile (admin only) |
| GET  | `/api/admin/users.php` | Get all users (admin only) |

---

## 9. Offline / Demo Mode

The app works **without** XAMPP running — it falls back to mock data automatically.
This means you can always demo the UI even before the database is connected.

When XAMPP is running and the DB is set up, all real data will flow automatically.

---

*BetterAbroad — Built with React 18 + PHP 8 + MySQL*
