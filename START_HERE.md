# 🎯 START HERE - BetterAbroad Platform

Welcome! This document explains what I've built for you and how to use it.

---

## 📦 What You Have Now

A **complete, fully functional** BetterAbroad platform with:

### ✅ What's Ready to Use
- **User Registration** - Students and universities can sign up
- **Login System** - Secure authentication with email/password
- **Profile Forms** - Multi-step registration for students and universities
- **Document Upload** - Upload transcripts, passports, logos, accreditations
- **Data Storage** - All information saved to database (safe & organized)
- **Search System** - Students and universities can search for each other
- **User Dashboards** - View/edit profiles, track completion percentage

### 🏗️ How It's Built
- **Frontend:** React (modern, interactive)
- **Backend:** PHP (server-side, databases)
- **Database:** MySQL (stores all data)
- **Server:** XAMPP/WAMP (local testing)

---

## ⚡ Quick Start (5 Minutes)

### 1️⃣ Start Your Server
- Open **XAMPP Control Panel** (or WAMP)
- Click **Start** next to Apache
- Click **Start** next to MySQL
- Wait for both to show green ✅

### 2️⃣ Create Database
- Go to: http://localhost/phpmyadmin
- Click **New** (left side)
- Database name: `betterabroad`
- Click **Create**
- Click **SQL** tab
- Open: `BetterAbroam/BETTERABROAD/DATABASE/schema.sql`
- Copy all the SQL code
- Paste into the SQL tab
- Click **Go**
- Done! You'll see "X queries executed successfully" ✅

### 3️⃣ Access the Platform
Open your browser and go to:
```
http://localhost/BetterAbroam/BETTERABROAD/
```

**You should see the login/signup page!** 🎉

---

## 🧪 Test It Out

### Test 1: Create a Student Account
1. Click **"Create Account"**
2. Choose **"I'm a Student"**
3. Enter email: `test@student.com`
4. Enter password: `password123`
5. Click **"Create Account"**
6. Fill in the form:
   - Name: John Doe
   - DOB: 2000-01-15
   - Nationality: Cameroonian
   - GPA: 3.8
   - Major: Computer Science
   - Intake: Fall 2025
   - Budget: $20k–$35k
7. Click **"Complete Registration"**
8. You're in! 🚀

### Test 2: Upload a Document
1. On your profile, click **"Documents"** tab
2. Click **"Upload"** next to "Transcript"
3. Select any PDF or image file
4. Click **"Upload"**
5. Success! Document shows "Pending Review" ✅

### Test 3: Logout & Login
1. Click **"Settings"** tab
2. Click **"Logout"**
3. Click **"Login"**
4. Enter your email and password
5. You're back in! ✅

---

## 📁 File Guide

Here's what each file does:

### Main Files (Start with these)
| File | Purpose |
|------|---------|
| `index.html` | The actual platform - where users login/signup |
| `check.html` | System health check - verify everything works |
| `api-test.html` | Test the APIs directly |

### Setup Guides (Read these)
| File | Purpose |
|------|---------|
| `README.md` | Project overview and features |
| `INSTALLATION.md` | Step-by-step installation instructions |
| `FEATURES.md` | Detailed list of all features |
| `DATABASE/SETUP_GUIDE.md` | Technical setup guide |

### Backend (These handle data)
| File | Purpose |
|------|---------|
| `DATABASE/db.php` | Database connection (used by all APIs) |
| `DATABASE/schema.sql` | Creates database tables |
| `DATABASE/register.php` | Handles signup |
| `DATABASE/login.php` | Handles login |
| `DATABASE/save.php` | Saves profile information |
| `DATABASE/upload.php` | Handles file uploads |
| `DATABASE/me.php` | Gets current user info |
| And more... | See DATABASE folder |

### Folders
| Folder | Purpose |
|--------|---------|
| `uploads/students/` | Where student documents are stored |
| `uploads/universities/` | Where university documents are stored |
| `DATABASE/` | All backend PHP scripts |

---

## 🔍 How Data Flows

```
User enters info in form (Frontend)
    ↓
React sends data to backend (API call)
    ↓
PHP receives data & validates it
    ↓
PHP saves to MySQL database
    ↓
Database sends confirmation
    ↓
Frontend shows success message
    ↓
User's data is safe & saved ✅
```

---

## 📊 What Gets Saved

### When User Signs Up
✅ Email  
✅ Password (hashed/encrypted)  
✅ Role (Student or University)  

### When Student Registers
✅ Full name  
✅ Date of birth  
✅ Nationality  
✅ GPA  
✅ Major  
✅ Budget  
✅ Intake period  
✅ Bio/Description  

### When Documents Upload
✅ File name  
✅ File path (where it's stored)  
✅ File size  
✅ Upload date  
✅ Verification status  

### All Stored In
📊 **MySQL Database** under `betterabroad` → tables like `users`, `student_profiles`, `documents`, etc.

---

## 🛠️ What's Inside Each File

### `DATABASE/register.php`
- **What it does:** Creates a new user account
- **Takes:** Email, password, role
- **Returns:** User ID and confirmation

### `DATABASE/login.php`
- **What it does:** Authenticates users
- **Takes:** Email and password
- **Returns:** User info and profile data

### `DATABASE/save.php`
- **What it does:** Saves profile information
- **Takes:** All profile fields
- **Returns:** Updated profile

### `DATABASE/upload.php`
- **What it does:** Handles file uploads
- **Takes:** Document type and file
- **Returns:** File path and confirmation

### And more...
Each file has detailed comments explaining what it does. Open them in a text editor to see!

---

## 🎓 Understanding the Flow

### Student Journey
```
1. Sign up with email/password
2. Get assigned Student ID (auto)
3. Complete registration form (4 steps)
4. Upload documents (transcript, passport)
5. View dashboard showing progress (0-100%)
6. Can upload more documents
7. Can browse universities
```

### University Journey
```
1. Sign up with email/password
2. Get assigned University ID (auto)
3. Complete registration form (3 steps)
4. Upload documents (logo, accreditation)
5. View dashboard
6. Can browse students
```

### Admin Journey (Future)
```
1. Admin login
2. Review documents
3. Approve or reject
4. View activity logs
5. Manage users
```

---

## ❓ FAQ

**Q: Do I need to edit code?**
A: No! Just follow installation steps. All code is ready to use.

**Q: Where is my data stored?**
A: In MySQL database in `DATABASE/` via phpMyAdmin

**Q: Can I test with multiple accounts?**
A: Yes! Use different emails. Use Incognito mode for different accounts at once.

**Q: What if I mess up the database?**
A: No problem! Just:
1. Go to phpMyAdmin
2. Drop (delete) the database
3. Create it again
4. Re-run the SQL from schema.sql

**Q: Can other people access my platform?**
A: Yes! If they're on your network, they can use: `http://YOUR_IP_ADDRESS/BetterAbroam/BETTERABROAD/`

**Q: How do I upload documents?**
A: After registration, go to "Documents" tab and click "Upload". Supports PDF, JPG, PNG up to 5MB each.

**Q: Is my data secure?**
A: Yes! Passwords are encrypted (bcrypt), files are organized safely, and all code follows security best practices.

---

## 📋 Checklist

Before going further, make sure you have:

- [ ] XAMPP/WAMP installed and running
- [ ] Apache is **green** (running)
- [ ] MySQL is **green** (running)
- [ ] Created database `betterabroad`
- [ ] Ran SQL schema.sql
- [ ] Can access http://localhost/BetterAbroam/BETTERABROAD/
- [ ] Can create account
- [ ] Can complete registration
- [ ] Can upload document

If all ✅, **you're ready!**

---

## 🚀 Next Steps

### Immediate (Now)
1. ✅ Follow Quick Start above
2. ✅ Create test accounts
3. ✅ Upload a document
4. ✅ Verify everything works

### Short-term (This Week)
- [ ] Explore the code
- [ ] Test all features
- [ ] Read SETUP_GUIDE.md for details
- [ ] Test API endpoints at api-test.html

### Medium-term (This Month)
- [ ] Customize colors/branding if desired
- [ ] Add more test data
- [ ] Plan additional features
- [ ] Consider deployment

### Long-term (Future)
- [ ] Deploy to live server
- [ ] Add email notifications
- [ ] Set up payment processing
- [ ] Add messaging system
- [ ] Build admin dashboard

---

## 💬 Support

If you have issues:

1. **Check System:** http://localhost/BetterAbroam/BETTERABROAD/check.html
2. **Test APIs:** http://localhost/BetterAbroam/BETTERABROAD/api-test.html
3. **Read Guides:** See files in DATABASE/ folder
4. **Check Logs:** XAMPP error logs can help debug

---

## 📚 Documentation Map

```
START HERE (this file)
├── README.md (Project overview)
├── INSTALLATION.md (Step-by-step setup)
├── FEATURES.md (Complete feature list)
├── DATABASE/SETUP_GUIDE.md (Technical details)
└── Individual PHP files (Detailed comments)
```

---

## ✨ What Makes This Special

✅ **No coding required** - Just follow steps  
✅ **Data persistence** - Everything saved to database  
✅ **Security** - Passwords encrypted, prepared statements  
✅ **Responsive design** - Works on phone, tablet, desktop  
✅ **Well documented** - Comments in every file  
✅ **Scalable** - Ready for more features  
✅ **Clean code** - Easy to modify later  

---

## 🎉 You're Good to Go!

**Everything is set up and ready to use.** Just follow the Quick Start section above, and you'll have a fully functional platform in minutes!

### Quick Links
- 🚀 **Start Platform:** http://localhost/BetterAbroam/BETTERABROAD/
- ✅ **Health Check:** http://localhost/BetterAbroam/BETTERABROAD/check.html
- 🧪 **Test APIs:** http://localhost/BetterAbroam/BETTERABROAD/api-test.html
- 📖 **Full Guide:** `INSTALLATION.md`

---

**Questions?** Check the README.md or SETUP_GUIDE.md  
**Found a bug?** Check browser console (F12) and XAMPP logs  
**Ready to customize?** All code comments explain what each line does!

🚀 **Let's go!**
