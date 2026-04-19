# 🚀 BetterAbroad Installation Guide

Complete step-by-step instructions to get BetterAbroad running on your local machine.

---

## ✅ Prerequisites

- XAMPP / WAMP / MAMP (Apache + MySQL + PHP)
- Windows / macOS / Linux
- Modern web browser (Chrome, Firefox, Safari, Edge)
- No coding experience needed!

---

## 📋 Installation Steps

### Step 1: Extract Files to Web Root

**On Windows (XAMPP):**
```
C:\xampp\htdocs\BetterAbroam\BETTERABROAD\
```

**On macOS (XAMPP):**
```
/Applications/XAMPP/htdocs/BetterAbroam/BETTERABROAD/
```

**On Linux (XAMPP):**
```
/opt/lampp/htdocs/BetterAbroam/BETTERABROAD/
```

**Alternative: If using WAMP:**
```
C:\wamp\www\BetterAbroam\BETTERABROAD\
```

---

### Step 2: Start XAMPP/WAMP

**On Windows:**
1. Open XAMPP Control Panel
2. Click "Start" next to Apache
3. Click "Start" next to MySQL
4. Wait for both to show as running (green)

**On macOS:**
1. Open XAMPP Manager
2. Start Apache Web Server
3. Start MySQL Database

**On Linux:**
1. Open terminal
2. Type: `sudo /opt/lampp/lampp start`

---

### Step 3: Create Database

1. **Open phpMyAdmin:**
   ```
   http://localhost/phpmyadmin
   ```

2. **Create new database:**
   - Click "New" button (left sidebar)
   - Database name: `betterabroad`
   - Collation: `utf8mb4_unicode_ci`
   - Click "Create"

3. **Import database schema:**
   - Select the `betterabroad` database
   - Go to "SQL" tab
   - Open file: `DATABASE/schema.sql`
   - Copy all the SQL code
   - Paste into SQL tab
   - Click "Go" or "Execute"
   - You should see "X queries executed successfully"

---

### Step 4: Verify Database Connection

1. **Check database was created:**
   - In phpMyAdmin left sidebar
   - You should see `betterabroad` database
   - Expand it, see tables like `users`, `student_profiles`, etc.

2. **Check default admin account was created:**
   - Click on `users` table
   - You should see one row with email: `admin@betterabroad.com`

---

### Step 5: Access the Platform

Open your web browser and go to:

```
http://localhost/BetterAbroam/BETTERABROAD/
```

You should see the BetterAbroad login/signup page.

---

## 🧪 Testing the System

### Test 1: Sign Up as Student

1. On the main page, click "Create Account"
2. Choose "I'm a Student"
3. Enter email: `teststudent@example.com`
4. Enter password: `password123` (must be 8+ chars)
5. Click "Create Account"
6. Complete the registration form:
   - Full Name: John Doe
   - DOB: 2000-01-15
   - Nationality: Cameroonian
   - GPA: 3.8
   - Major: Computer Science
   - Intake: Fall 2025
   - Budget: $20k–$35k
7. Click "Complete Registration"
8. You should see your student profile dashboard

### Test 2: Upload a Document

1. On your profile page, click "Documents" tab
2. Click "Upload" next to "Transcript"
3. Select any PDF or image file (max 5MB)
4. Click "Upload"
5. You should see success message
6. Document status should show "Pending Review"

### Test 3: Logout & Login

1. On profile page, click "Settings" tab
2. Click "Logout"
3. You should be back on login page
4. Click "Login"
5. Enter your email and password
6. You should be back in your profile

### Test 4: Test Sign Up as University

1. Click "Back to Home" or go to http://localhost/BetterAbroam/BETTERABROAD/
2. Sign out first if needed
3. Click "Create Account"
4. Choose "I'm a University"
5. Enter email: `testuniversity@example.com`
6. Enter password: `password123`
7. Click "Create Account"
8. Complete university registration:
   - University Name: Test University
   - Country: Canada
   - Website: www.testuniversity.com
   - Programs: Computer Science, Engineering
   - Intake Periods: Sept, Jan
9. Click "Complete Registration"

---

## 🔍 Verification Checklist

After installation, verify everything works:

- [ ] Can access http://localhost/BetterAbroam/BETTERABROAD/
- [ ] Can see login/signup page
- [ ] Can create student account
- [ ] Can complete registration form
- [ ] Can upload document
- [ ] Can logout
- [ ] Can login again
- [ ] Can view profile
- [ ] Can create university account
- [ ] Check System Health: http://localhost/BetterAbroam/BETTERABROAD/check.html

---

## ❌ Troubleshooting

### Problem: "Cannot reach server" or "Connection refused"

**Solution:**
1. Check Apache is running (green in XAMPP Control Panel)
2. Check port 80 is not blocked (try http://localhost in browser)
3. Check file path: `C:\xampp\htdocs\BetterAbroam\BETTERABROAD\`
4. Restart XAMPP

---

### Problem: "Database connection failed"

**Solution:**
1. Check MySQL is running (green in XAMPP Control Panel)
2. Check database `betterabroad` exists in phpMyAdmin
3. Check you ran all SQL from schema.sql
4. Check credentials in `DATABASE/db.php`:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_USER', 'root');
   define('DB_PASS', '');
   define('DB_NAME', 'betterabroad');
   ```
5. Verify in phpMyAdmin you can access the database

---

### Problem: "Sign up returns error: 'Email already exists'"

**Solution:**
1. Use a different email address
2. Check that email in signup form hasn't been used before
3. Option: Check database and delete test accounts if needed

---

### Problem: "File upload fails or 'File too large'"

**Solution:**
1. Check file size < 5 MB
2. Check file is PDF, JPG, or PNG
3. Check `uploads/` folder exists
4. Check folder is writable (permissions)

---

### Problem: "Profile not saving" or "Session issues"

**Solution:**
1. Clear browser cookies (Ctrl+Shift+Delete)
2. Try Private/Incognito window
3. Check browser console (F12 → Console tab) for errors
4. Verify `session_start()` in `db.php`

---

### Problem: "Blank page or 500 error"

**Solution:**
1. Check XAMPP error log: `C:\xampp\apache\logs\error.log`
2. Check PHP error log: `C:\xampp\php\logs\php_error.log`
3. Verify all database tables were created
4. Check file permissions (uploads folder)

---

## 🧰 Quick Fixes

### Reset Everything
If something is broken, you can reset:

1. **Reset Database (DELETE ALL DATA):**
   - Go to phpMyAdmin
   - Select `betterabroad` database
   - Click "Drop" button
   - Create new database
   - Re-import schema.sql

2. **Clear Sessions:**
   - Delete all files in: `C:\xampp\tmp\` (Windows)
   - Or: `/tmp` (Linux/macOS)

3. **Clear Browser Cache:**
   - Press Ctrl+Shift+Delete
   - Select "All time"
   - Clear cookies and cache

---

## 📁 File Locations

After uploading documents, files appear in:
```
BETTERABROAD/uploads/
├── students/
│   ├── 1/
│   │   ├── transcript_1.pdf
│   │   └── passport_1.jpg
│   └── 2/
└── universities/
    └── 1/
        └── logo_1.png
```

You can access uploaded files at:
```
http://localhost/BetterAbroam/BETTERABROAD/uploads/students/1/transcript_1.pdf
```

---

## 🔐 Testing Default Accounts

**Admin Account:**
- Email: `admin@betterabroad.com`
- Password: `admin123`

---

## ✨ Next Steps

1. ✅ Installation complete!
2. ✅ Try creating accounts and uploading documents
3. → Explore the codebase in `DATABASE/`
4. → Check `DATABASE/SETUP_GUIDE.md` for advanced setup
5. → Test APIs at: `http://localhost/BetterAbroam/BETTERABROAD/api-test.html`

---

## 🆘 Still Having Issues?

1. **Read:** [Setup Guide](./DATABASE/SETUP_GUIDE.md)
2. **Test:** [API Console](./api-test.html)
3. **Check:** [System Health](./check.html)
4. **Check XAMPP Logs:**
   - Apache: `C:\xampp\apache\logs\error.log`
   - PHP: `C:\xampp\php\logs\php_error.log`

---

## 💡 Pro Tips

- Use **Incognito/Private window** to test multiple accounts
- Use **F12 DevTools** to debug API calls
- Check **Network tab** to see all requests/responses
- Check **Application tab** for cookies and storage
- Use **phpMyAdmin** to view database directly

---

## 📞 Common Questions

**Q: Do I need to code anything?**
A: No! Just follow these installation steps. All code is already included.

**Q: Can I use a different database name?**
A: Yes, but change it in `DATABASE/db.php` too.

**Q: What if I delete all data by accident?**
A: Just re-run the schema.sql file in phpMyAdmin. No problem!

**Q: Can multiple people access it?**
A: Yes, if they're on the same network. Change:
```php
// In DATABASE/db.php
header('Access-Control-Allow-Origin: http://192.168.1.100');  // Your IP
```

---

**Installation Time:** 10-15 minutes  
**Difficulty:** Beginner-friendly  
**Support:** See troubleshooting section above

🎉 **You're all set! Enjoy BetterAbroad!**
