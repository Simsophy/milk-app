<?php
declare(strict_types=1);

require_once __DIR__ . '/../models/User.php';

class AuthController
{
    private User $userModel;

    public function __construct(PDO $db)
    {
        $this->userModel = new User($db);
    }

    public function login(array $payload): array
    {
        $username = trim((string) ($payload['username'] ?? ''));
        $password = (string) ($payload['password'] ?? '');

        if ($username === '' || $password === '') {
            return [
                'success' => false,
                'message' => 'Username and password are required.',
            ];
        }

        $user = $this->userModel->findByUsername($username);
        if (!$user) {
            return [
                'success' => false,
                'message' => 'Invalid username or password.',
            ];
        }

        $isValidPassword = password_verify($password, $user['password']) || $password === $user['password'];
        if (!$isValidPassword) {
            return [
                'success' => false,
                'message' => 'Invalid username or password.',
            ];
        }

        $_SESSION['user'] = [
            'id'             => (int) $user['id'],
            'username'       => $user['username'],
            'role'           => $user['role'],
            'email'          => $user['email'] ?? null,
            'phone'          => $user['phone'] ?? null,
            'bakong_wallet'  => $user['bakong_wallet'] ?? null,
            'merchant_id'    => $user['merchant_id'] ?? null,
            'app_id'         => $user['app_id'] ?? null,
            'api_secret'     => $user['api_secret'] ?? null,
        ];

        return [
            'success' => true,
            'message' => 'Login successful.',
            'user'    => $_SESSION['user'],
        ];
    }

    public function register(array $payload): array
    {
        $username     = trim((string) ($payload['username'] ?? ''));
        $password     = (string) ($payload['password'] ?? '');
        $email        = trim((string) ($payload['email'] ?? ''));
        $phone        = trim((string) ($payload['phone'] ?? ''));
        $bakongWallet = trim((string) ($payload['bakong_wallet'] ?? ''));
        $role         = in_array($payload['role'] ?? 'customer', ['customer', 'seller', 'admin'])
                            ? $payload['role']
                            : 'customer';

        if ($username === '' || $password === '') {
            return ['success' => false, 'message' => 'Username and password are required.'];
        }

        if (strlen($password) < 4) {
            return ['success' => false, 'message' => 'Password must be at least 4 characters.'];
        }

        if ($email && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => 'Invalid email address.'];
        }

        if ($this->userModel->findByUsername($username)) {
            return ['success' => false, 'message' => 'Username already exists.'];
        }

        if ($email && $this->userModel->findByEmail($email)) {
            return ['success' => false, 'message' => 'Email already registered.'];
        }

        if ($role === 'seller') {
            $merchantId = trim((string) ($payload['merchant_id'] ?? ''));
            $appId      = trim((string) ($payload['app_id'] ?? ''));
            $apiSecret  = trim((string) ($payload['api_secret'] ?? ''));

            if ($merchantId === '' || $appId === '' || $apiSecret === '') {
                return [
                    'success' => false,
                    'message' => 'Merchant registration requires Bakong Merchant ID, App ID, and API Secret.',
                ];
            }
        }

        $passwordHash = password_hash($password, PASSWORD_BCRYPT);
        $insertData = [
            'username'      => $username,
            'password'      => $passwordHash ?: $password,
            'email'         => $email ?: null,
            'phone'         => $phone ?: null,
            'bakong_wallet' => $bakongWallet ?: null,
            'role'          => $role,
        ];

        if ($role === 'seller') {
            $insertData['merchant_id'] = $merchantId;
            $insertData['app_id']      = $appId;
            $insertData['api_secret']  = $apiSecret;
        }

        $created = $this->userModel->create($insertData);

        if (!$created) {
            return ['success' => false, 'message' => 'Unable to register user.'];
        }

        $user = $this->userModel->findByUsername($username);
        if ($user) {
            $_SESSION['user'] = [
                'id'            => (int) $user['id'],
                'username'      => $user['username'],
                'role'          => $user['role'],
                'email'         => $user['email'] ?? null,
                'phone'         => $user['phone'] ?? null,
                'bakong_wallet' => $user['bakong_wallet'] ?? null,
                'merchant_id'   => $user['merchant_id'] ?? null,
                'app_id'        => $user['app_id'] ?? null,
                'api_secret'    => $user['api_secret'] ?? null,
            ];
        }

        return [
            'success' => true,
            'message' => 'Registration successful! Welcome to Milk App.',
            'user'    => $_SESSION['user'] ?? null,
        ];
    }

    public function logout(): array
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(), '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }

        session_destroy();

        return ['success' => true, 'message' => 'Logged out successfully.'];
    }

    public function me(): array
    {
        if (!isset($_SESSION['user'])) {
            return ['success' => false, 'message' => 'Unauthorized'];
        }

        return ['success' => true, 'user' => $_SESSION['user']];
    }
}