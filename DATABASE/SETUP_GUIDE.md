# BetterAbroad Platform - Complete Setup Guide

## 🚀 Quick Start

This guide will help you set up the complete BetterAbroad platform with all database connections, user authentication, file uploads, and more.

---

## 1. DATABASE SETUP

### Using XAMPP/WAMP

1. **Start your PHP server** (XAMPP Control Panel)
   - Start Apache
   - Start MySQL

2. **Create Database**
   - Open phpMyAdmin: http://localhost/phpmyadmin
   - Click "New" or paste SQL from `schema.sql`
   - Select all SQL in `DATABASE/schema.sql` and execute it

3. **Verify Database Connection**
   - Edit `DATABASE/db.php` if your credentials are different:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_USER', 'root');
   define('DB_PASS', '');              // XAMPP default is empty
   define('DB_NAME', 'betterabroad');
   ```

---

## 2. FILE STRUCTURE

Your project should look like this:

```
BETTERABROAD/
├── index.html                 ← Main entry point (START HERE)
├── DATABASE/
│   ├── db.php                ← Database connection center
│   ├── register.php          ← User signup API
│   ├── login.php             ← User login API
│   ├── logout.php            ← User logout API
│   ├── me.php                ← Get current user
│   ├── save.php              ← Save profile to database
│   ├── upload.php            ← Handle document uploads
│   ├── student.php           ← Get student profile
│   ├── university.php        ← Get university profile
│   ├── documents.php         ← List user documents
│   ├── search_students.php   ← Search students
│   ├── search_universities.php ← Search universities
│   ├── schema.sql            ← Database schema
│   └── SETUP.md              ← This file
├── uploads/
│   ├── students/             ← Student documents (auto-created)
│   └── universities/         ← University documents (auto-created)
├── GRANDE HTML/
│   ├── LANDING PAGE/
│   │   ├── V1test.html
│   │   └── market.html
│   └── ...
└── ...
```

---

## 3. ACCESS THE PLATFORM

### For Local Development:

1. **Place the project in your web server root:**
   - **XAMPP**: `C:/xampp/htdocs/BetterAbroam/BETTERABROAD/`
   - **WAMP**: `C:/wamp/www/BetterAbroam/BETTERABROAD/`

2. **Access the main page:**
   ```
   http://localhost/BetterAbroam/BETTERABROAD/
   ```

3. **Create an account:**
   - Click "Create Account"
   - Choose role (Student or University)
   - Enter email and password
   - Complete the registration form

4. **Upload Documents:**
   - After registration, go to "Documents" tab
   - Upload transcript (Student) or accreditation (University)
   - Supported formats: PDF, JPG, PNG

---

## 4. USER FLOW DIAGRAM

```
Landing Page
    ↓
[Sign Up] → Enter Email/Password → Choose Role (Student/University)
    ↓
Registration Form (Multi-step)
    ├─ Student: Name, DOB, Nationality, GPA, Major, Intake, Budget
    └─ University: Name, Country, Website, Programs, Intake Periods
    ↓
Document Upload (Optional)
    ├─ Student: Transcript, Passport
    └─ University: Logo, Accreditation
    ↓
Profile Dashboard
    ├─ View/Edit Information
    ├─ Upload Documents
    ├─ View Marketplace
    └─ Manage Settings
```

---

## 5. API ENDPOINTS REFERENCE

All endpoints use JSON and require `Content-Type: application/json`

### Authentication

| Method | Endpoint | Body | Response |
|--------|----------|------|----------|
| POST | `/register.php` | `{email, password, role}` | `{success, userId, email, role}` |
| POST | `/login.php` | `{email, password}` | `{success, userId, email, role, profile}` |
| POST | `/logout.php` | none | `{success}` |
| GET | `/me.php` | none | `{success, userId, email, role, profile}` |

### Profiles

| Method | Endpoint | Body | Notes |
|--------|----------|------|-------|
| POST | `/save.php` | Profile fields | Saves student or university profile |
| GET | `/student.php?id=X` | none | Get public student profile |
| GET | `/university.php?id=X` | none | Get public university profile |

### Files & Documents

| Method | Endpoint | Body | Notes |
|--------|----------|------|-------|
| POST | `/upload.php` | FormData: `{doc_type, file}` | doc_type: transcript, passport, logo, accreditation |
| GET | `/documents.php` | none | List all documents for user |

### Search & Marketplace

| Method | Endpoint | Query | Notes |
|--------|----------|-------|-------|
| GET | `/search_students.php` | `?q=search&limit=20&offset=0` | Search verified students |
| GET | `/search_universities.php` | `?q=search&limit=20&offset=0` | Search verified universities |

---

## 6. DEFAULT ADMIN ACCOUNT

After running `schema.sql`, you'll have an admin account:

**Email:** `admin@betterabroad.com`  
**Password:** `admin123`

(Only use this in development!)

---

## 7. TROUBLESHOOTING

### "Database connection failed"
- Check MySQL is running
- Verify credentials in `db.php`
- Run schema.sql in phpMyAdmin

### "CORS error" or "Blocked by CORS"
- Check `db.php` has correct origin:
  ```php
  header('Access-Control-Allow-Origin: http://localhost');
  ```
- Make sure frontend is on same origin as API

### "File upload failed"
- Check `uploads/` folder exists and is writable
- Check file size < 5MB
- Check file type is PDF, JPG, or PNG

### "Session not working"
- Clear browser cookies
- Check `session.save_path` in php.ini is writable
- Verify `session_start()` is called in db.php

---

## 8. SECURITY NOTES

⚠️ **Before going to production:**

1. Change `DB_PASS` in `db.php`
2. Change default admin password
3. Set `Define('ENVIRONMENT', 'production')`
4. Add HTTPS
5. Update CORS to specific domains
6. Add rate limiting
7. Validate all file uploads properly
8. Use environment variables for secrets

---

## 9. FEATURES IMPLEMENTED

✅ User Registration (Student & University)  
✅ User Authentication & Sessions  
✅ Profile Management (Multi-step forms)  
✅ Document Upload (with validation)  
✅ File Storage (organized by user)  
✅ Search & Discovery  
✅ Public Profiles  
✅ Dashboard with Completion Tracking  

---

## 10. FILE UPLOAD DETAILS

### Allowed Document Types:

**Students:**
- `transcript` - Academic transcripts (PDF, Image)
- `passport` - Passport copy (PDF, Image)

**Universities:**
- `logo` - University logo (PNG, JPG)
- `accreditation` - Accreditation certificate (PDF)

### File Limits:
- Maximum size: 5 MB
- Formats: PDF, JPG, PNG
- Stored in: `uploads/{role}/{user_id}/`

### Database Storage:
Files are tracked in `documents` table with:
- User ID
- Document Type
- File path
- Upload date
- Approval status

---

## 11. TESTING THE SYSTEM

### Test Registration:
1. Go to http://localhost/BetterAbroam/BETTERABROAD/
2. Click "Create Account"
3. Student: test@student.com / password123
4. Fill in all required fields
5. Submit

### Test Document Upload:
1. After registration, go to "Documents" tab
2. Select a PDF or image file
3. Click "Upload"
4. File appears with "Pending Review" status

### Test Login:
1. Log out (Settings tab)
2. Go to http://localhost/BetterAbroam/BETTERABROAD/
3. Click "Login"
4. Enter credentials
5. You're back in dashboard!

---

## 12. NEXT STEPS

- ✨ Admin panel for document verification
- 🔔 Email notifications
- 📧 Messaging system between users
- 📊 Application tracking pipeline
- 🌐 Deployment to live server
- 🔐 Two-factor authentication

---

**Need help?** Check the comments in each PHP file for detailed explanations!
