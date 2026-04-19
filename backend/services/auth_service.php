<?php
declare(strict_types=1);

require_once __DIR__ . '/../models/user_model.php';
require_once __DIR__ . '/../models/student_profile_model.php';
require_once __DIR__ . '/../models/university_profile_model.php';
require_once __DIR__ . '/../utils/response.php';
require_once __DIR__ . '/../utils/validation.php';

final class AuthService
{
    private UserModel $users;
    private StudentProfileModel $students;
    private UniversityProfileModel $universities;

    public function __construct()
    {
        $this->users         = new UserModel();
        $this->students      = new StudentProfileModel();
        $this->universities  = new UniversityProfileModel();
    }

    public function login(array $payload): void
    {
        [$email, $password] = $this->validateLoginPayload($payload);
        $user               = $this->verifyCredentials($email, $password);

        $this->users->updateLastLogin((int) $user['id']);

        $profile = $this->loadProfile((int) $user['id'], (string) $user['role']);
        $this->setSession((int) $user['id'], $user['email'], $user['role']);

        Response::success([
            'userId'  => (int) $user['id'],
            'email'   => $user['email'],
            'role'    => $user['role'],
            'profile' => $profile,
        ]);
    }

    public function register(array $payload): void
    {
        [$email, $password, $role, $fullName, $phone] = $this->validateRegisterPayload($payload);

        $this->assertEmailAvailable($email);

        $hashed = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
        $userId = $this->users->create($email, $hashed, $role);

        $this->createProfile($userId, $role, $fullName, $phone);
        $this->setSession($userId, $email, $role);

        Response::success([
            'userId' => $userId,
            'email'  => $email,
            'role'   => $role,
        ], 201);
    }

    private function validateLoginPayload(array $payload): array
    {
        $missing = Validation::required($payload, ['email', 'password']);
        if (!empty($missing)) {
            Response::error('Email and password are required.', 422, ['missing' => $missing]);
        }

        $email    = strtolower(Validation::sanitizeString($payload['email'], 150));
        $password = (string) $payload['password'];

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Response::error('Invalid email address.', 422);
        }

        return [$email, $password];
    }

    private function verifyCredentials(string $email, string $password): array
    {
        $user = $this->users->findByEmail($email);

        if (!$user) {
            Response::error('No account found with that email.', 401);
        }
        if ((int) $user['is_active'] !== 1) {
            Response::error('This account has been suspended.', 403);
        }
        if (!password_verify($password, (string) $user['password'])) {
            Response::error('Incorrect password.', 401);
        }

        return $user;
    }

    private function loadProfile(int $userId, string $role): array
    {
        if ($role === 'student') {
            return $this->students->findByUserId($userId) ?? [];
        }
        if ($role === 'university') {
            return $this->universities->findByUserId($userId) ?? [];
        }

        return [];
    }

    private function setSession(int $userId, string $email, string $role): void
    {
        $_SESSION['user_id'] = $userId;
        $_SESSION['email']   = $email;
        $_SESSION['role']    = $role;
    }

    private function validateRegisterPayload(array $payload): array
    {
        $missing = Validation::required($payload, ['email', 'password']);
        if (!empty($missing)) {
            Response::error('Email and password are required.', 422, ['missing' => $missing]);
        }

        $email    = strtolower(Validation::sanitizeString($payload['email'], 150));
        $password = (string) $payload['password'];
        $roleRaw  = $payload['role'] ?? 'student';
        $role     = Validation::sanitizeString($roleRaw, 50) ?: 'student';
        $fullName = Validation::sanitizeString($payload['full_name'] ?? '', 150);
        $phone    = Validation::sanitizeString($payload['phone'] ?? '', 50);

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Response::error('Invalid email address.', 422);
        }
        if (strlen($password) < 8) {
            Response::error('Password must be at least 8 characters.', 422);
        }
        if (!in_array($role, ['student', 'university'], true)) {
            Response::error('Invalid role.', 422);
        }

        return [$email, $password, $role, $fullName, $phone];
    }

    private function assertEmailAvailable(string $email): void
    {
        $existing = $this->users->findByEmail($email);
        if ($existing) {
            Response::error('An account with this email already exists.', 409);
        }
    }

    private function createProfile(int $userId, string $role, string $fullName, string $phone): void
    {
        if ($role === 'student') {
            $this->students->create($userId, $fullName, $phone);
            return;
        }

        $this->universities->create($userId);
    }
}
