<?php
// ============================================================
//  mailer.php - Email helper
//  Uses PHPMailer when available, otherwise falls back to PHP mail()
// ============================================================

$mailer_files = [
    __DIR__ . '/../vendor/phpmailer/PHPMailer.php',
    __DIR__ . '/../vendor/phpmailer/SMTP.php',
    __DIR__ . '/../vendor/phpmailer/Exception.php',
];

$can_use_phpmailer = true;
foreach ($mailer_files as $mailer_file) {
    if (!is_file($mailer_file)) {
        $can_use_phpmailer = false;
        break;
    }
}

if ($can_use_phpmailer) {
    require_once $mailer_files[0];
    require_once $mailer_files[1];
    require_once $mailer_files[2];
}

// Load .env values if available (reuse backend loader)
$env_loader = __DIR__ . '/../backend/config/env.php';
if (is_file($env_loader)) {
    require_once $env_loader;
    $envPath = dirname(__DIR__) . '/.env';
    loadEnv($envPath);
}

use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;

$envHost     = getenv('MAIL_HOST') ?: 'smtp.gmail.com';
$envPort     = getenv('MAIL_PORT') ?: 587;
$envUser     = getenv('MAIL_USERNAME') ?: '';
$envPass     = getenv('MAIL_PASSWORD') ?: '';
$envFrom     = getenv('MAIL_FROM') ?: ($envUser ?: 'noreply@betterabroad.com');
$envFromName = getenv('MAIL_FROM_NAME') ?: 'BetterAbroad';

define('MAIL_HOST', $envHost);
define('MAIL_PORT', (int) $envPort);
define('MAIL_USERNAME', $envUser);
define('MAIL_PASSWORD', $envPass);
define('MAIL_FROM', $envFrom);
define('MAIL_FROM_NAME', $envFromName);

function email_template(string $title, string $body, string $cta_text = '', string $cta_url = ''): string {
    $cta_block = '';
    if ($cta_text && $cta_url) {
        $cta_block = "
        <div style='text-align:center;margin:32px 0;'>
            <a href='{$cta_url}'
               style='background:linear-gradient(135deg,#1a56db,#06b6d4);
                      color:#ffffff;text-decoration:none;
                      padding:14px 32px;border-radius:10px;
                      font-weight:700;font-size:15px;
                      display:inline-block;'>
                {$cta_text}
            </a>
        </div>";
    }

    return "
<!DOCTYPE html>
<html>
<head><meta charset='utf-8'><meta name='viewport' content='width=device-width,initial-scale=1'></head>
<body style='margin:0;padding:0;background:#07111f;font-family:Inter,sans-serif;'>
  <div style='max-width:600px;margin:0 auto;padding:40px 20px;'>

    <div style='text-align:center;margin-bottom:32px;'>
      <span style='font-size:22px;font-weight:800;color:#f0f4ff;'>
        Better<span style='background:linear-gradient(90deg,#3b82f6,#06b6d4);
                           -webkit-background-clip:text;-webkit-text-fill-color:transparent;
                           background-clip:text;'>Abroad</span>
      </span>
    </div>

    <div style='background:#0a1830;border:1px solid rgba(255,255,255,0.08);
                border-radius:18px;padding:36px;'>
      <h2 style='color:#f0f4ff;font-size:22px;font-weight:700;margin:0 0 16px;'>{$title}</h2>
      <div style='color:#94a3b8;font-size:15px;line-height:1.8;'>{$body}</div>
      {$cta_block}
    </div>

    <div style='text-align:center;margin-top:24px;color:#3d5a80;font-size:12px;'>
      &copy; " . date('Y') . " BetterAbroad | You're receiving this because you have an account.<br>
      <a href='http://localhost/betterabroad' style='color:#3d5a80;'>Manage notifications</a>
    </div>
  </div>
</body>
</html>";
}

function send_email_with_phpmailer(string $to, string $to_name, string $subject, string $html): bool {
    if (!class_exists(PHPMailer::class)) return false;

    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = MAIL_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = MAIL_USERNAME;
        $mail->Password   = MAIL_PASSWORD;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = MAIL_PORT;

        $mail->setFrom(MAIL_FROM, MAIL_FROM_NAME);
        $mail->addAddress($to, $to_name);
        $mail->isHTML(true);
        $mail->CharSet = 'UTF-8';
        $mail->Subject = $subject;
        $mail->Body    = $html;
        $mail->AltBody = trim(strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $html)));

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log('[BetterAbroad Mailer] PHPMailer failed: ' . $e->getMessage());
        return false;
    }
}

function send_email_with_mail(string $to, string $subject, string $html): bool {
    $headers = [
        'MIME-Version: 1.0',
        'Content-type: text/html; charset=UTF-8',
        'From: ' . MAIL_FROM_NAME . ' <' . MAIL_FROM . '>',
        'Reply-To: ' . MAIL_FROM,
    ];

    $sent = @mail($to, $subject, $html, implode("\r\n", $headers));
    if (!$sent) {
        error_log('[BetterAbroad Mailer] PHP mail() failed.');
    }
    return $sent;
}

function send_email(string $to, string $to_name, string $subject, string $html): bool {
    $has_smtp_credentials =
        MAIL_HOST !== '' &&
        MAIL_USERNAME !== '' &&
        MAIL_PASSWORD !== '' &&
        MAIL_USERNAME !== 'your@gmail.com' &&
        MAIL_PASSWORD !== 'your-app-password-here';

    if (class_exists(PHPMailer::class) && $has_smtp_credentials) {
        $sent = send_email_with_phpmailer($to, $to_name, $subject, $html);
        if ($sent) return true;
    }

    return send_email_with_mail($to, $subject, $html);
}

function email_welcome(string $to, string $name, string $role): bool {
    $role_text = $role === 'student' ? 'student' : 'university partner';
    $html = email_template(
        "Welcome to BetterAbroad, {$name}!",
        "Your account has been created as a <strong style='color:#3b82f6;'>{$role_text}</strong>.<br><br>
         Complete your profile to appear on the marketplace and start connecting with " .
        ($role === 'student' ? 'partner universities.' : 'verified students.'),
        'Complete Your Profile',
        'http://localhost/betterabroad/index.html'
    );
    return send_email($to, $name, 'Welcome to BetterAbroad!', $html);
}

function email_verified(string $to, string $name, string $role): bool {
    $msg = $role === 'student'
        ? 'Your profile is now <strong style="color:#4ade80;">verified</strong> and live on the marketplace. Universities can now view and contact you.'
        : 'Your institution has been <strong style="color:#4ade80;">verified</strong>. You now have full access to browse and contact verified students.';

    $html = email_template(
        'Your profile has been verified!',
        $msg,
        'View My Profile',
        'http://localhost/betterabroad/index.html'
    );
    return send_email($to, $name, 'Your BetterAbroad profile is verified', $html);
}

function email_rejected(string $to, string $name, string $note = ''): bool {
    $note_block = $note
        ? "<div style='margin-top:16px;padding:14px 18px;background:rgba(239,68,68,.08);
                       border-left:3px solid #ef4444;border-radius:6px;color:#f87171;
                       font-size:13px;'><strong>Admin note:</strong> " . htmlspecialchars($note, ENT_QUOTES, 'UTF-8') . '</div>'
        : '';

    $html = email_template(
        'Action required on your profile',
        "We were not able to verify your profile at this time. Please review your documents and resubmit.{$note_block}<br>
         If you have questions, reply to this email or contact our support team.",
        'Update My Profile',
        'http://localhost/betterabroad/index.html'
    );
    return send_email($to, $name, 'Action required - BetterAbroad profile review', $html);
}

function email_new_message(string $to, string $to_name, string $from_name, string $preview): bool {
    $html = email_template(
        "New message from {$from_name}",
        "You have a new message waiting:<br><br>
         <div style='background:rgba(59,130,246,.08);border:1px solid rgba(59,130,246,.2);
                     border-radius:10px;padding:14px 18px;color:#94a3b8;font-style:italic;'>" .
            nl2br(htmlspecialchars(substr($preview, 0, 200), ENT_QUOTES, 'UTF-8')) .
         '</div>',
        'Read and Reply',
        'http://localhost/betterabroad/index.html#messages'
    );
    return send_email($to, $to_name, "New message from {$from_name} - BetterAbroad", $html);
}

function email_application_update(string $to, string $name, string $uni_name, string $status): bool {
    $status_labels = [
        'review'    => ['Under Review', '#f59e0b', 'Your application is being reviewed by the admissions team.'],
        'interview' => ['Interview Invited', '#8b5cf6', 'You have been invited to an interview. Check your inbox for further instructions.'],
        'offer'     => ['Offer Made', '#22c55e', 'An offer has been extended to you. Log in to view the details and next steps.'],
        'rejected'  => ['Application Closed', '#ef4444', 'Unfortunately your application was not successful this time. There are many more opportunities available on the marketplace.'],
    ];

    [$label, $color, $msg] = $status_labels[$status] ?? ['Updated', '#3b82f6', 'Your application status has been updated.'];
    $html = email_template(
        "Application update: {$uni_name}",
        "Your application status is now:
         <span style='color:{$color};font-weight:700;font-size:18px;display:block;margin:12px 0;'>
           {$label}
         </span>
         {$msg}",
        'View Application',
        'http://localhost/betterabroad/index.html#applications'
    );
    return send_email($to, $name, "Application update - {$uni_name}", $html);
}
