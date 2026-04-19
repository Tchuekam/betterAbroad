# BetterAbroad Platform - System Overview & Features

Complete documentation of all implemented features, APIs, and functionality.

---

## 🎯 Core Features

### ✅ User Authentication System
- **Sign Up** - Create new student or university accounts
- **Login** - Secure email/password authentication
- **Session Management** - PHP session-based authentication
- **Logout** - Destroy session securely
- **Account Recovery** - Ready for future implementation

### ✅ Student Features
- **Profile Registration** - Multi-step form (4 steps)
  1. Personal Information (name, DOB, nationality)
  2. Academic Information (GPA, major)
  3. Financial Information (budget, intake period)
  4. Document Upload (transcript, passport)
- **Profile Completion Tracking** - Visual progress indicator (0-100%)
- **Document Upload** - Transcript and passport in PDF/JPG/PNG
- **Profile Dashboard** - View and edit information
- **Document Management** - Upload, track, and view document status

### ✅ University Features
- **Institution Registration** - Multi-step form (3 steps)
  1. Institution Details (name, country, website)
  2. Programs Offered (list of programs, intake periods)
  3. Document Upload (logo, accreditation)
- **Program Management** - Add and manage programs offered
- **Document Upload** - Logo and accreditation in PDF/JPG/PNG
- **Institution Dashboard** - View and edit information

### ✅ Document Management
- **File Upload System** - Secure file upload with validation
- **File Storage** - Organized by user ID and role
- **Metadata Tracking** - File name, size, type, upload date
- **Status Management** - Pending, Approved, Rejected
- **Size Limits** - 5 MB max per file
- **Format Support** - PDF, JPG, PNG

### ✅ Search & Discovery
- **Student Search** - Universities can search verified students
- **University Search** - Students can search verified universities
- **Advanced Filters** - Search by location, programs, GPA, etc.
- **Public Profiles** - View other users' information
- **Save Favorites** - Ready for implementation

---

## 🔧 API Endpoints

### Authentication Endpoints

#### POST /register.php
Create a new user account
```
Request:
{
  "email": "user@example.com",
  "password": "password123",
  "role": "student" | "university"
}

Response:
{
  "success": true,
  "userId": 1,
  "email": "user@example.com",
  "role": "student"
}
```

#### POST /login.php
Authenticate and create session
```
Request:
{
  "email": "user@example.com",
  "password": "password123"
}

Response:
{
  "success": true,
  "userId": 1,
  "email": "user@example.com",
  "role": "student",
  "profile": { ... }
}
```

#### POST /logout.php
Destroy user session
```
Response:
{
  "success": true,
  "message": "Logged out successfully"
}
```

#### GET /me.php
Get current authenticated user
```
Response:
{
  "success": true,
  "userId": 1,
  "email": "user@example.com",
  "role": "student",
  "profile": { ... },
  "unread_count": 0
}
```

---

### Profile Endpoints

#### POST /save.php
Save or update user profile
```
Request (Student):
{
  "fullName": "John Doe",
  "dob": "2000-01-15",
  "nationality": "Cameroonian",
  "gpa": 3.8,
  "major": "Computer Science",
  "intake": "Fall 2025",
  "budget": "$20k–$35k",
  "description": "Optional bio"
}

Request (University):
{
  "uniName": "Test University",
  "country": "Canada",
  "website": "www.test.com",
  "programs": "CS, Engineering",
  "intakePeriods": "Sept, Jan",
  "description": "About university"
}

Response:
{
  "success": true,
  "profile": { ... },
  "completion_pct": 85
}
```

#### GET /student.php?id={id}
Get public student profile
```
Response:
{
  "success": true,
  "student": {
    "id": 1,
    "full_name": "John Doe",
    "nationality": "Cameroonian",
    "gpa": 3.8,
    "major": "Computer Science",
    "completion_pct": 85,
    "verified": "verified"
  }
}
```

#### GET /university.php?id={id}
Get public university profile
```
Response:
{
  "success": true,
  "university": {
    "id": 2,
    "uni_name": "Test University",
    "country": "Canada",
    "programs": "CS, Engineering",
    "verified": "verified"
  }
}
```

---

### Document Endpoints

#### POST /upload.php
Upload user document
```
Request (multipart/form-data):
- doc_type: "transcript" | "passport" | "logo" | "accreditation"
- file: <binary file data>

Response:
{
  "success": true,
  "doc_type": "transcript",
  "file_path": "uploads/students/1/transcript_1.pdf",
  "file_name": "transcript_1.pdf"
}
```

#### GET /documents.php
List all user documents
```
Response:
{
  "success": true,
  "documents": [
    {
      "id": 1,
      "doc_type": "transcript",
      "file_name": "transcript_1.pdf",
      "file_path": "uploads/students/1/transcript_1.pdf",
      "status": "pending",
      "uploaded_at": "2025-10-20 14:32:10"
    }
  ]
}
```

---

### Search Endpoints

#### GET /search_students.php?q={query}&limit={limit}&offset={offset}
Search verified students
```
Response:
{
  "success": true,
  "students": [
    {
      "id": 1,
      "full_name": "John Doe",
      "nationality": "Cameroonian",
      "gpa": 3.8,
      "major": "Computer Science",
      "completion_pct": 85,
      "verified": "verified"
    }
  ]
}
```

#### GET /search_universities.php?q={query}&limit={limit}&offset={offset}
Search verified universities
```
Response:
{
  "success": true,
  "universities": [
    {
      "id": 2,
      "uni_name": "Test University",
      "country": "Canada",
      "programs": "CS, Engineering",
      "verified": "verified"
    }
  ]
}
```

---

## 📊 Database Schema

### users
- id (INT PRIMARY KEY)
- email (VARCHAR UNIQUE)
- password (VARCHAR HASHED)
- role (ENUM: student, university, admin)
- is_active (BOOLEAN)
- last_login (DATETIME)
- created_at (TIMESTAMP)

### student_profiles
- id (INT PRIMARY KEY)
- user_id (INT FOREIGN KEY)
- full_name (VARCHAR)
- dob (DATE)
- nationality (VARCHAR)
- gpa (DECIMAL)
- major (VARCHAR)
- intake (VARCHAR)
- budget (VARCHAR)
- description (TEXT)
- completion_pct (INT)
- verified (ENUM: pending, verified, rejected)
- created_at (TIMESTAMP)
- updated_at (TIMESTAMP)

### university_profiles
- id (INT PRIMARY KEY)
- user_id (INT FOREIGN KEY)
- uni_name (VARCHAR)
- country (VARCHAR)
- website (VARCHAR)
- programs (TEXT)
- intake_periods (VARCHAR)
- description (TEXT)
- verified (ENUM: pending, verified, rejected)
- created_at (TIMESTAMP)
- updated_at (TIMESTAMP)

### documents
- id (INT PRIMARY KEY)
- user_id (INT FOREIGN KEY)
- doc_type (VARCHAR)
- file_name (VARCHAR)
- file_path (VARCHAR)
- file_size (INT)
- mime_type (VARCHAR)
- status (ENUM: pending, approved, rejected)
- uploaded_at (TIMESTAMP)

### messages
- id (INT PRIMARY KEY)
- from_user_id (INT)
- to_user_id (INT)
- body (TEXT)
- is_read (BOOLEAN)
- read_at (DATETIME)
- created_at (TIMESTAMP)

### applications
- id (INT PRIMARY KEY)
- student_id (INT)
- university_id (INT)
- status (ENUM: new, review, interview, offer, rejected, withdrawn)
- personal_stmt (TEXT)
- admin_note (TEXT)
- applied_at (TIMESTAMP)

### Admin tables
- admin_log (Activity tracking)
- notifications (User notifications)
- saved_profiles (Bookmarks)

---

## 🎨 Frontend Architecture

### Single Page Application (SPA)
- React-based frontend
- Tailwind CSS styling
- Responsive design (mobile, tablet, desktop)
- No build step required (uses CDN)

### Pages & Components

1. **Login View** - Email/password login
2. **Signup View** - Choose role, create account
3. **Register Student** - Multi-step profile form
4. **Register University** - Multi-step institution form
5. **Student Profile** - Dashboard with tabs:
   - Overview (edit profile info)
   - Documents (upload and manage)
   - Settings (logout, preferences)
6. **University Profile** - Dashboard with tabs
7. **Marketplace** - Search and browse (ready for implementation)

---

## 🔐 Security Features

### Implemented
- ✅ Password hashing (bcrypt, cost 12)
- ✅ Session-based authentication
- ✅ CSRF protection via cookies
- ✅ Prepared statements (SQL injection prevention)
- ✅ File upload validation
- ✅ MIME type checking
- ✅ File size limits
- ✅ Input sanitization
- ✅ CORS headers
- ✅ HTTP-only sessions

### Recommended for Production
- 🔄 HTTPS/SSL certificates
- 🔄 Rate limiting
- 🔄 Two-factor authentication
- 🔄 Email verification
- 🔄 Password reset flow
- 🔄 Admin audit logs
- 🔄 File antivirus scanning
- 🔄 DDoS protection

---

## 📁 File Structure

```
BETTERABROAD/
├── index.html                    ← Main frontend (SPA)
├── api-test.html                ← API testing console
├── check.html                    ← System health check
├── README.md                     ← Project overview
├── INSTALLATION.md               ← Setup guide
├── FEATURES.md                   ← This file
│
├── DATABASE/
│   ├── db.php                   ← Core (all APIs include this)
│   ├── schema.sql               ← Database tables definition
│   ├── SETUP_GUIDE.md          ← Detailed technical guide
│   │
│   ├── register.php             ← POST: Signup
│   ├── login.php                ← POST: Login
│   ├── logout.php               ← POST: Logout
│   ├── me.php                   ← GET: Current user
│   │
│   ├── save.php                 ← POST: Save profile
│   ├── upload.php               ← POST: Upload document
│   ├── documents.php            ← GET: List documents
│   │
│   ├── student.php              ← GET: Get student profile
│   ├── university.php           ← GET: Get university profile
│   ├── search_students.php      ← GET: Search students
│   └── search_universities.php  ← GET: Search universities
│
└── uploads/                      ← Document storage
    ├── students/
    │   ├── 1/
    │   ├── 2/
    │   └── ...
    └── universities/
        ├── 1/
        ├── 2/
        └── ...
```

---

## 📈 Metrics & Performance

### Completion Tracking (Students)
- Base: 20%
- Full name: +15%
- GPA: +15%
- Major: +10%
- Intake: +10%
- Budget: +10%
- Nationality: +10%
- DOB: +10%
- **Max: 100%**

### Document Status
- Pending - Awaiting admin review
- Approved - Document verified
- Rejected - Requires reupload

### File Size Limits
- Maximum: 5 MB per file
- Formats: PDF, JPG, PNG

---

## 🚀 Deployment Checklist

### Pre-deployment
- [ ] Change admin password
- [ ] Update database password
- [ ] Enable HTTPS
- [ ] Configure CORS for production domain
- [ ] Set up file backups
- [ ] Review security headers
- [ ] Test all features
- [ ] Set up email notifications
- [ ] Configure logging

### Post-deployment
- [ ] Monitor error logs
- [ ] Track user registrations
- [ ] Review document uploads
- [ ] Check database performance
- [ ] Update DNS records
- [ ] Set up SSL certificates
- [ ] Configure firewall rules

---

## 🔮 Planned Features

### Phase 2
- [ ] Email notifications
- [ ] Messaging system between users
- [ ] Application tracking pipeline
- [ ] Admin panel for verification
- [ ] Saved profiles / Wishlist
- [ ] Performance metrics

### Phase 3
- [ ] Video interviews
- [ ] Payment processing
- [ ] Two-factor authentication
- [ ] Advanced analytics
- [ ] API documentation (Swagger)
- [ ] Mobile app

### Phase 4
- [ ] AI matching algorithm
- [ ] Scholarship matching
- [ ] Multiple languages
- [ ] Advanced reporting
- [ ] White-label option

---

## 🆘 Support Resources

- **Setup Guide:** `DATABASE/SETUP_GUIDE.md`
- **Installation:** `INSTALLATION.md`
- **API Testing:** `api-test.html`
- **System Check:** `check.html`
- **Code Comments:** Check each PHP file for detailed explanations

---

**Last Updated:** March 2026  
**Version:** 1.0.0  
**Status:** ✅ Production Ready for Testing
