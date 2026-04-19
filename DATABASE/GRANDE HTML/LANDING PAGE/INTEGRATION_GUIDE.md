# V1test.html Integration Guide

## Overview
V1test.html is now fully integrated with the BetterAbroad backend APIs. The page now provides a complete user journey: **Landing Page → Signup → Registration → Profile → Marketplace**.

---

## Complete User Flow

### 1. **Landing Page (Initial Load)**
- Users see the hero section with signup form
- Form captures: Name, Email, Phone, and Role (Student/University)
- Click "Submit Free Application ✓" button

### 2. **Signup to Register** 
When user submits the hero form:
- `heroSubmit()` → calls `/DATABASE/register.php`
- Creates user account with email/password
- Shows success message with confetti animation
- **Auto-redirects to registration form after 2 seconds**

### 3. **Registration Form**
- User fills in detailed profile information:
  - Full Name
  - Date of Birth
  - Nationality
  - GPA / Grade Average
  - Major / Field of Study
  - Annual Budget (USD)
  - About You (optional description)
- `submitRegistration()` → calls `/DATABASE/save.php`
- Saves profile to database
- **Auto-redirects to Profile Page**

### 4. **Profile Page**
- Displays user information (name, email, account type, status)
- Shows document upload section
- User can upload academic documents (transcripts, certifications, etc.)
- `uploadDocument()` → calls `/DATABASE/upload.php`
- After document upload, "📄 Upload Documents" button enables "🎓 Access Marketplace" button

### 5. **Marketplace**
- Browse all 12 verified partner universities
- View details: Country, Programs, Tuition, Intake dates
- Click "Apply Now →" to apply to universities
- Option to logout and return to landing page

---

## Key Backend API Integration

### 1. **Register API** (`/DATABASE/register.php`)
**Called by:** `heroSubmit()`
```javascript
POST /DATABASE/register.php
{
  "full_name": "John Doe",
  "email": "john@email.com",
  "phone": "+237690123456",
  "role": "student",
  "password": "auto-generated"
}

Returns:
{
  "success": true,
  "user_id": 123,
  "role": "student"
}
```

### 2. **Save Profile API** (`/DATABASE/save.php`)
**Called by:** `submitRegistration()`
```javascript
POST /DATABASE/save.php
{
  "full_name": "John Doe",
  "dob": "1998-05-15",
  "nationality": "Cameroonian",
  "gpa": "3.85",
  "major": "Computer Science",
  "budget": "35000",
  "description": "Interested in engineering programs..."
}

Returns:
{
  "success": true,
  "message": "Profile saved"
}
```

### 3. **Get Current User** (`/DATABASE/me.php`)
**Called by:** `checkSession()` and `loadProfilePage()`
```javascript
GET /DATABASE/me.php

Returns:
{
  "success": true,
  "user": {
    "id": 123,
    "email": "john@email.com",
    "full_name": "John Doe",
    "role": "student",
    "gpa": "3.85",
    ...
  }
}
```

### 4. **Upload Document** (`/DATABASE/upload.php`)
**Called by:** `uploadDocument()`
```javascript
POST /DATABASE/upload.php (FormData)
- file: <document file>
- doc_type: "transcript" | "passport" | "logo" | etc.

Returns:
{
  "success": true,
  "file_path": "/uploads/students/123/transcript_2026.pdf"
}
```

### 5. **Logout** (`/DATABASE/logout.php`)
**Called by:** `logout()`
```javascript
POST /DATABASE/logout.php

Returns:
{
  "success": true,
  "message": "Logged out"
}
```

---

## Session Management

The page uses **PHP sessions** for authentication. Key points:

- **Cookies are sent automatically** with `credentials: 'include'` in all fetch requests
- **Session persists** across page refreshes as long as the browser hasn't cleared cookies
- **`checkSession()` is called on page load** to determine the user's current state:
  - If logged in → Show Profile Page
  - If not logged in → Show Landing Page / Signup Form

---

## Features Added

### ✅ Automatic Session Detection
- When page loads, `checkSession()` checks `/me.php`
- Determines which section to display:
  - **No session** → Landing page with hero form
  - **Session exists** → Profile page

### ✅ Form Validation
- All required fields are validated before submission
- Visual feedback (red borders) on validation errors
- Loading states on buttons during API calls

### ✅ Error Handling
- Network errors show user-friendly alerts
- Backend errors are captured and displayed
- Failed requests restore button states

### ✅ Data Flow
```
Hero Form → Register API → Registration Form → Save API → Profile Page → Marketplace
```

### ✅ Document Upload
- Files uploaded to `/uploads/students/{userid}/`
- Supports: PDF, JPG, JPEG, PNG
- Enables marketplace access after upload

---

## Testing the Flow

### Prerequisites
1. XAMPP running with Apache + MySQL
2. Database created: `betterabroad`
3. All PHP files in `/DATABASE/` directory
4. V1test.html in `/GRANDE HTML/LANDING PAGE/`

### Step-by-Step Test

#### Test 1: Complete Signup Flow
1. Open `V1test.html` in browser
2. Fill signup form:
   - Name: `John Doe`
   - Email: `john@example.com`
   - Phone: `+237690000000`
   - Role: `Student`
3. Click "Submit Free Application ✓"
4. ✅ Should see success message and auto-redirect to registration

#### Test 2: Complete Registration
1. Fill registration form with all fields
2. Click "Complete Registration →"
3. ✅ Should redirect to profile page

#### Test 3: Profile Access
1. Profile shows name, email, account type
2. Click "📄 Upload Documents"
3. Select a PDF/image file
4. ✅ "🎓 Access Marketplace" button should appear

#### Test 4: Marketplace
1. Click "🎓 Access Marketplace"
2. ✅ Should see 12 universities listed
3. Each university shows: name, country, subject, tuition, intake date
4. Click "Apply Now →" for any university

#### Test 5: Session Persistence
1. Complete signup and registration
2. Refresh the page
3. ✅ User should still be on profile page (session preserved)

#### Test 6: Logout
1. Click "🚪 Logout" button
2. ✅ Should return to landing page with empty signup form

---

## File Structure

```
BETTERABROAD/
  GRANDE HTML/LANDING PAGE/
    V1test.html                    ← Main integrated file (2318 lines)
    INTEGRATION_GUIDE.md           ← This file
  DATABASE/
    register.php                   ← User registration
    save.php                       ← Profile save
    me.php                         ← Get current user
    upload.php                     ← Document upload
    logout.php                     ← Session logout
    db.php                         ← Database connection
    ... (other APIs)
```

---

## API Endpoints Reference

| Endpoint | Method | Purpose | Called By |
|----------|--------|---------|-----------|
| `/register.php` | POST | Create new user account | `heroSubmit()` |
| `/save.php` | POST | Save/update user profile | `submitRegistration()` |
| `/me.php` | GET | Get current user info | `checkSession()`, `loadProfilePage()` |
| `/upload.php` | POST | Upload user documents | `uploadDocument()` |
| `/logout.php` | POST | Destroy session | `logout()` |
| `/student.php` | GET | Get single student profile | Future use |
| `/university.php` | GET | Get university profile | Future use |
| `/documents.php` | GET | List user documents | Future use |

---

## Troubleshooting

### Issue: "Connection error" on signup
**Solution:** 
- Verify XAMPP is running
- Check that `/DATABASE/` folder exists and PHP files are there
- Open browser console (F12) to see actual error

### Issue: Form doesn't redirect after signup
**Solution:**
- Check browser console for errors
- Verify `/DATABASE/register.php` is returning valid JSON
- Check that cookies are enabled

### Issue: Profile page shows "Session expired"
**Solution:**
- Reopen V1test.html in new tab
- Clear browser cookies and try again
- Verify XAMPP MySQL is running

### Issue: Document upload fails
**Solution:**
- Check `/uploads/students/` folder exists
- Verify file permissions (should be writable)
- Ensure file size is reasonable (< 10MB)

---

## Customization

### Change API Base Path
If your APIs are in a different location, update the fetch URLs:
```javascript
// Current:
fetch('../../../DATABASE/register.php', {

// Change to:
fetch('/your-api-path/register.php', {
```

### Add More Form Fields
To add fields to registration form:
1. Add input in HTML (registration-form-wrapper)
2. Add `getElementById()` in `submitRegistration()`
3. Add field to JSON payload sent to `/save.php`

### Customize University List
Edit the `UNIVERSITIES` constant in JavaScript to add/modify universities:
```javascript
const UNIVERSITIES = [
  { id:1, name:"University Name", country:"UK", ... },
  ...
];
```

---

## Security Notes

⚠️ **Important:**
- Passwords are auto-generated and should be improved for production
- All API calls include `credentials: 'include'` for session handling
- Backend PHP files handle password hashing with bcrypt
- CORS is configured in `/DATABASE/db.php` for localhost

---

## Next Steps

1. **Test the complete flow** using the step-by-step guide above
2. **Monitor browser console** (F12) for any errors
3. **Check database** to verify records are being created:
   ```sql
   SELECT * FROM users;
   SELECT * FROM student_profiles;
   SELECT * FROM documents;
   ```
4. **Customize branding** and contact details in footer
5. **Deploy to production** with proper HTTPS and environment variables

---

## Contact

For issues or questions:
- 📧 Email: tchuekrostand@gmail.com
- 💬 WhatsApp: +237 690 380 798
- 📍 Location: Yaoundé, Cameroon

---

**Last Updated:** March 2026
**Version:** 1.0 - Full Integration Complete
