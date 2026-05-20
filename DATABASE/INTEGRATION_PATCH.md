# market.html — Minimal Integration Patch
# Zero new features added to market.html.
# Only wiring the new external files.

## FILES YOU NOW HAVE

student-profile.css    → student profile stylesheet
student-profile.js     → student profile React component  (exports window.StudentProfilePage)
university-profile.css → university profile stylesheet
university-profile.js  → university profile React component (exports window.UniversityProfilePage)

## STEP 1 — Add to <head> of market.html

After the existing Font Awesome <link>, add these 4 lines:

<link rel="stylesheet" href="student-profile.css">
<link rel="stylesheet" href="university-profile.css">

## STEP 2 — Add before closing </body> of market.html

Before the existing <script type="text/babel"> tag, add:

<script src="student-profile.js" type="text/babel"></script>
<script src="university-profile.js" type="text/babel"></script>

## STEP 3 — Replace StudentProfile component reference in App

Find this line in market.html (around line 2544):
  {path === '/profile/student' &&
    <StudentProfile user={user} setUser={setUser} onNavigate={navigate}/>}

Replace with:
  {path === '/profile/student' &&
    <StudentProfilePage user={user} setUser={setUser} onNavigate={navigate}/>}

## STEP 4 — Replace UniversityProfile component reference in App

Find this line (around line 2547):
  {path === '/profile/university' &&
    <UniversityProfile user={user} setUser={setUser} onNavigate={navigate}/>}

Replace with:
  {path === '/profile/university' &&
    <UniversityProfilePage user={user} setUser={setUser} onNavigate={navigate}/>}

## THAT IS ALL. 4 changes. Nothing else in market.html is touched.

## WHAT YOU CAN DELETE FROM market.html (optional, after testing)

Once the new pages work, you can safely delete these components
from inside market.html to keep it clean:
- The entire StudentProfile = () => { ... }; component definition
- The entire UniversityProfile = () => { ... }; component definition
- The VideoMock component (replaced by real VideoUpload in student-profile.js)
- The InfoTile component (redefined inside student-profile.js)

Do NOT delete:
- SignupView
- RegisterStudent
- RegisterUniversity
- MarketplaceView
- App
- Any CSS in <style>
- Any CDN <script> tags
