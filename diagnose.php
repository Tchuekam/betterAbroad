<?php
/**
 * Diagnostic script to test backend setup
 * Access: http://localhost:8000/diagnose.php
 */

echo "<h1>BetterAbroad Backend Diagnostics</h1>";
echo "<style>body{font-family:monospace;margin:20px;background:#f5f5f5}
.ok{color:green;font-weight:bold}.bad{color:red;font-weight:bold}
.section{margin:20px 0;padding:15px;background:white;border-radius:5px}
h2{border-bottom:2px solid #ddd;padding-bottom:10px}</style>";

// ─── 1. PHP Version ───────────────────────────────────────
echo "<div class='section'><h2>PHP & Session</h2>";
echo "PHP Version: " . PHP_VERSION . "<br>";
echo "Session Name: " . ini_get('session.name') . "<br>";
echo "Session Save Path: " . session_save_path() . "<br>";
session_start();
$_SESSION['test'] = time();
echo "Session Test: <span class='ok'>✓ Sessions working</span><br>";
session_destroy();
echo "</div>";

// ─── 2. MySQL Connection ──────────────────────────────────
echo "<div class='section'><h2>MySQL Connection</h2>";
$host = 'localhost';
$user = 'root';
$pass = '';
$db   = 'betterabroad';

$conn = @new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    echo "<span class='bad'>✗ Connection Failed:</span> " . $conn->connect_error . "<br>";
    echo "<strong>Fix:</strong><br>";
    echo "1. Start MySQL in XAMPP<br>";
    echo "2. Run: <code>mysql -u root < schema.sql</code>";
} else {
    echo "<span class='ok'>✓ Connected to '$db' database</span><br>";
    
    // Check tables
    $tables = ['users', 'student_profiles', 'university_profiles', 'documents'];
    echo "<strong>Tables:</strong><br>";
    foreach ($tables as $t) {
        $result = $conn->query("SHOW TABLES LIKE '$t'");
        if ($result && $result->num_rows > 0) {
            echo "  ✓ $t<br>";
        } else {
            echo "  <span class='bad'>✗ $t (missing)</span><br>";
        }
    }
    
    $conn->close();
}
echo "</div>";

// ─── 3. Directory Structure ────────────────────────────────
echo "<div class='section'><h2>Directory Structure</h2>";
$dirs = [
    'DATABASE' => __DIR__ . '/DATABASE',
    'uploads/students' => __DIR__ . '/uploads/students',
    'uploads/universities' => __DIR__ . '/uploads/universities',
];
foreach ($dirs as $name => $path) {
    $exists = is_dir($path);
    $status = $exists ? "<span class='ok'>✓</span>" : "<span class='bad'>✗</span>";
    $writable = $exists && is_writable($path) ? " (writable)" : "";
    echo "$status $name {$writable}<br>";
}
echo "</div>";

// ─── 4. PHP Files ─────────────────────────────────────────
echo "<div class='section'><h2>PHP API Files</h2>";
$files = ['db.php', 'register.php', 'save.php', 'me.php', 'logout.php', 'upload.php'];
foreach ($files as $f) {
    $exists = file_exists(__DIR__ . "/DATABASE/$f");
    echo ($exists ? "<span class='ok'>✓</span>" : "<span class='bad'>✗</span>") . " DATABASE/$f<br>";
}
echo "</div>";

// ─── 5. HTML Files ────────────────────────────────────────
echo "<div class='section'><h2>Frontend Files</h2>";
$frontends = [
    'GRANDE HTML/LANDING PAGE/V1test.html',
    'GRANDE HTML/LANDING PAGE/admin.html',
    'GRANDE HTML/LANDING PAGE/market.html',
];
foreach ($frontends as $f) {
    $path = __DIR__ . '/' . $f;
    $exists = file_exists($path);
    echo ($exists ? "<span class='ok'>✓</span>" : "<span class='bad'>✗</span>") . " $f<br>";
}
echo "</div>";

// ─── 6. Test API Call ─────────────────────────────────────
echo "<div class='section'><h2>API Test (register endpoint)</h2>";
echo "Try this curl command:<br>";
echo "<code>curl -X POST http://localhost:8000/DATABASE/register.php \\<br>";
echo "-H 'Content-Type: application/json' \\<br>";
echo "-d '{\"email\":\"test@example.com\",\"password\":\"test1234\",\"role\":\"student\",\"full_name\":\"Test User\",\"phone\":\"+237690380798\"}'</code><br><br>";
echo "Or test in browser console:<br>";
echo "<code>fetch('http://localhost:8000/DATABASE/register.php', {<br>";
echo "&nbsp;&nbsp;method: 'POST',<br>";
echo "&nbsp;&nbsp;headers: {'Content-Type': 'application/json'},<br>";
echo "&nbsp;&nbsp;body: JSON.stringify({email:'test@test.com', password:'test1234', role:'student', full_name:'Test', phone:'+237690380798'})<br>";
echo "}).then(r=&gt;r.json()).then(d=&gt;console.log(d))</code>";
echo "</div>";

// ─── 7. Next Steps ────────────────────────────────────────
echo "<div class='section'><h2>Next Steps</h2>";
echo "1. ✓ Verify all items above are green<br>";
echo "2. Open <strong><a href='GRANDE%20HTML/LANDING%20PAGE/V1test.html'>V1test.html</a></strong><br>";
echo "3. Fill the hero form and test the flow<br>";
echo "4. Check browser console (F12) for errors";
echo "</div>";
?>
