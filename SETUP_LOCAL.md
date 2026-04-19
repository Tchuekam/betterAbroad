# Local Testing Setup Guide

## Step 1: Database Setup (MySQL/XAMPP)

### Option A: Using XAMPP (Recommended)
1. Open XAMPP Control Panel
2. Start **Apache** and **MySQL**
3. Click **Admin** next to MySQL (opens phpMyAdmin)
4. In phpMyAdmin:
   - Click **SQL** tab
   - Copy-paste entire contents of `DATABASE/schema.sql`
   - Click **Go** to execute

### Option B: Command Line
```bash
# From the workspace root
mysql -u root -p < BETTERABROAD/DATABASE/schema.sql
# (Press Enter, no password for default XAMPP)
```

## Step 2: Start Local PHP Server

```powershell
# Open PowerShell in the BETTERABROAD folder
cd c:\Users\CLINIC\Desktop\BetterAbroam\BETTERABROAD

# Start PHP built-in server
php -S localhost:8000
```

Output should show:
```
Development Server running at http://localhost:8000
```

## Step 3: Test in Browser

Open this URL in your browser:
```
http://localhost:8000/GRANDE%20HTML/LANDING%20PAGE/V1test.html
```

## Step 4: Test the Flow

### Hero Form (Landing Page)
1. Fill in:
   - Role: Student (or University)
   - Full Name: e.g., "John Doe"
   - Email: e.g., "john@example.com"
   - Phone: e.g., "+237690380798"
2. Click **Submit Free Application ✓**
3. Should see success message with confetti

### Registration Form
4. Fill in all fields:
   - Full Name
   - Date of Birth
   - Nationality
   - GPA
   - Major
   - Annual Budget
5. Click **Complete Registration →**
6. Should navigate to Profile page

### Profile Page
7. See your profile information
8. Click **Access Marketplace →** (or upload documents first)

### Marketplace
9. Should see list of universities to apply to

## Troubleshooting

### Error: "Connection error"
- **Check**: Is PHP server running? (Should show in terminal)
- **Fix**: Run `php -S localhost:8000` again

### Error: "Database connection failed"
- **Check**: Is MySQL running? (XAMPP MySQL service active)
- **Check**: Did you run schema.sql?
- **Fix**: 
  ```powershell
  mysql -u root < BETTERABROAD/DATABASE/schema.sql
  ```

### Error: "Session error" / User not logged in
- **Check**: Are cookies enabled in browser?
- **Check**: Is PHP session working?
- **Fix**: Open browser DevTools (F12) → **Network** tab, reload, check if calls to `me.php` return JSON with `success: true`

### Can't see database tables
1. Open phpMyAdmin: `http://localhost/phpmyadmin`
2. Left sidebar → **betterabroad** database
3. Should see tables: `users`, `student_profiles`, `university_profiles`, `documents`, etc.
4. If missing, re-run the SQL setup

## Quick Debug Checklist

- [ ] PHP server running on `localhost:8000`
- [ ] MySQL running (XAMPP)
- [ ] Database `betterabroad` exists
- [ ] Tables exist in database
- [ ] Browser console (F12) shows no JavaScript errors
- [ ] Network tab (F12) shows successful requests to `register.php`, `save.php`, etc.

## File Paths Overview

```
BETTERABROAD/
├── DATABASE/              ← All PHP API files
│   ├── db.php             ← Database config & helpers
│   ├── register.php       ← POST /register (hero form)
│   ├── save.php           ← POST /save (registration form)
│   ├── me.php             ← GET /me (session & profile)
│   ├── logout.php         ← POST /logout
│   ├── upload.php         ← POST /upload (documents)
│   └── schema.sql         ← Database setup
│
├── GRANDE HTML/
│   └── LANDING PAGE/
│       └── V1test.html    ← Main file (http://localhost:8000/GRANDE%20HTML/LANDING%20PAGE/V1test.html)
```

## After Testing - Deploy Notes

When deploying to production:
1. Update database host/credentials in `DATABASE/db.php` (currently `localhost:root` with no password)
2. Update whitelist origins in `DATABASE/db.php` (currently allows only localhost)
3. Change session name from `BA_SESSION` if desired
4. Store uploaded files securely outside webroot
