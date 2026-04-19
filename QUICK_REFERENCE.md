# 🚀 Quick Reference Card

## 3-Minute Setup

### 1. Start Servers
- Open XAMPP Control Panel
- Start Apache ✓
- Start MySQL ✓

### 2. Create Database
- Go: http://localhost/phpmyadmin
- New database: `betterabroad`
- SQL tab → Paste `schema.sql`
- Execute ✓

### 3. Open Platform
```
http://localhost/BetterAbroam/BETTERABROAD/
```

---

## Default Account

```
Email:    admin@betterabroad.com
Password: admin123
```

---

## Key Endpoints

- `/register.php` - Sign up
- `/login.php` - Login
- `/logout.php` - Logout
- `/save.php` - Save profile
- `/upload.php` - Upload file
- `/me.php` - Current user
- `/documents.php` - List docs
- `/search_students.php` - Search
- `/search_universities.php` - Search

All at: `DATABASE/`

---

## Document Types Supported

**Students:** transcript, passport  
**Universities:** logo, accreditation  
**Max:** 5 MB each  
**Formats:** PDF, JPG, PNG

---

## Key Files

| File | Purpose |
|------|---------|
| `index.html` | Main platform |
| `check.html` | System check |
| `api-test.html` | API testing |
| `DATABASE/db.php` | Core connection |
| `DATABASE/schema.sql` | Database setup |
| `START_HERE.md` | Getting started |
| `INSTALLATION.md` | Setup guide |
| `FEATURES.md` | Feature list |

---

## Troubleshooting

| Problem | Solution |
|---------|----------|
| Can't access site | Check Apache running |
| Database error | Check MySQL running, check db.php |
| Upload fails | Check file < 5MB, type is PDF/JPG/PNG |
| Session lost | Clear cookies, restart browser |
| Blank page | Check XAMPP error logs |

---

## File Locations

```
uploads/students/{id}/
uploads/universities/{id}/
DATABASE/*.php
DATABASE/schema.sql
```

---

## Test Credentials

```
Email: test@student.com
Pass: password123

OR

Create your own account!
```

---

## User Completion %

- Base: 20%
- +15% per field filled
- Max: 100%

---

## Navigation

1. Sign up → register.php
2. Complete form → save.php
3. Upload docs → upload.php
4. View profile → me.php
5. Search others → search_*.php
6. Logout → logout.php

---

## Security Notes

✓ Passwords hashed  
✓ SQL injection protected  
✓ File upload validated  
✓ CORS configured  

⚠️ Before production:  
- Change admin password
- Enable HTTPS
- Update CORS domain
- Check file permissions

---

## Need Help?

1. **Quick help:** START_HERE.md
2. **Setup:** INSTALLATION.md
3. **Features:** FEATURES.md
4. **Technical:** DATABASE/SETUP_GUIDE.md
5. **Test:** api-test.html or check.html

---

## Key Database Tables

- `users` - Accounts
- `student_profiles` - Student info
- `university_profiles` - University info
- `documents` - File tracking
- `messages` - Messages
- `applications` - Applications

---

## Folder Structure

```
BETTERABROAD/
├── index.html ← START HERE
├── START_HERE.md ← READ FIRST
├── INSTALLATION.md ← Full setup
├── DATABASE/
│   ├── db.php (connection)
│   ├── register.php (signup)
│   ├── login.php (login)
│   └── schema.sql (database)
└── uploads/
    ├── students/
    └── universities/
```

---

**Version:** 1.0.0  
**Status:** Ready to Use ✅  
**Created:** March 2026
