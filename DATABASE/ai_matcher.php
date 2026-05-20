<?php
// ============================================================
//  ai_matcher.php
//  Uses Groq AI to calculate match scores between students and unis
// ============================================================
require_once __DIR__ . '/db.php';

// Load ENV for API key
if (file_exists(__DIR__ . '/../.env')) {
    $lines = file(__DIR__ . '/../.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        list($name, $value) = explode('=', $line, 2);
        $_ENV[trim($name)] = trim($value);
    }
}

$groq_api_key = $_ENV['GROQ_API_KEY'] ?? '';

if (!$groq_api_key) {
    fail('GROQ_API_KEY not configured.', 500);
}

$auth = require_auth();
$uid  = $auth['id'];

if ($_SERVER['REQUEST_METHOD'] !== 'GET') fail('Method not allowed', 405);

// 1. Get student profile
$stmt = $conn->prepare('SELECT * FROM student_profiles WHERE user_id = ?');
$stmt->bind_param('i', $uid);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$student) fail('Student profile not found. Please complete your profile first.', 404);

// 2. Get universities
$result = $conn->query('SELECT u.id, v.uni_name, v.country, v.programs, v.intake_periods, v.description FROM users u JOIN university_profiles v ON u.id = v.user_id WHERE u.role = "university" AND v.verified = "verified" LIMIT 10');
$universities = [];
while ($row = $result->fetch_assoc()) {
    $universities[] = $row;
}

if (empty($universities)) {
    // If no verified unis, use mock data for demo purposes or return empty
    respond(['success' => true, 'matches' => [], 'message' => 'No verified universities found.']);
}

// 3. Prepare prompt for Groq
$prompt = "You are an expert academic advisor. Match this student to the following universities.
Student Profile:
- Name: {$student['full_name']}
- Major: {$student['major']}
- GPA: {$student['gpa']}
- Budget: {$student['budget']}
- Nationality: {$student['nationality']}
- Interests: {$student['description']}

Universities:
";

foreach ($universities as $uni) {
    $prompt .= "- ID: {$uni['id']}, Name: {$uni['uni_name']}, Country: {$uni['country']}, Programs: {$uni['programs']}, Intakes: {$uni['intake_periods']}, Description: {$uni['description']}\n";
}

$prompt .= "\nReturn a JSON object with a 'matches' array. Each match should have 'university_id', 'score' (0-100), and 'reason' (short explanation). Only return valid JSON.";

// 4. Call Groq API
$ch = curl_init('https://api.groq.com/openai/v1/chat/completions');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $groq_api_key,
    'Content-Type: application/json'
]);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    'model' => 'llama3-8b-8192',
    'messages' => [
        ['role' => 'system', 'content' => 'You are a helpful assistant that returns only JSON.'],
        ['role' => 'user', 'content' => $prompt]
    ],
    'response_format' => ['type' => 'json_object']
]));

$response = curl_exec($ch);
if (curl_errno($ch)) {
    fail('AI matching service unavailable: ' . curl_error($ch), 500);
}
curl_close($ch);

$ai_data = json_decode($response, true);
$content = $ai_data['choices'][0]['message']['content'] ?? '{}';
$matches_result = json_decode($content, true);

respond([
    'success' => true,
    'matches' => $matches_result['matches'] ?? [],
    'raw_ai_response' => $content
]);
