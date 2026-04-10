<?php
declare(strict_types=1);

/**
 * Setup Test User Script
 * Creates a test seller user with Bakong credentials
 *
 * Usage: php setup_test_user.php
 */

require_once __DIR__ . '/config/db.php';

echo "\n";
echo "\033[1;34m========================================\n";
echo "  Milk App - Test User Setup\n";
echo "========================================\033[0m\n\n";

// Get Bakong credentials from user
echo "Enter your Bakong Sandbox Credentials:\n";
echo "----------------------------------------\n";

$merchantId = readline("Merchant ID: ");
$appId = readline("App ID: ");
$apiSecret = readline("API Secret: ");

if (empty($merchantId) || empty($appId) || empty($apiSecret)) {
    echo "\033[0;31mError: All fields are required!\033[0m\n";
    exit(1);
}

// Create database connection
try {
    $db = (new Database())->connect();
    echo "\033[0;32m✓ Connected to database\033[0m\n";
} catch (Throwable $e) {
    echo "\033[0;31m✗ DB connection failed: " . $e->getMessage() . "\033[0m\n";
    exit(1);
}

// Check if user already exists
$stmt = $db->prepare("SELECT id FROM users WHERE username = 'test_seller'");
$stmt->execute();
$existing = $stmt->fetch();

if ($existing) {
    echo "\033[1;33m⚠ User 'test_seller' already exists (ID: {$existing['id']})\033[0m\n";
    echo "Updating existing user with new Bakong credentials...\n\n";

    $stmt = $db->prepare("
        UPDATE users
        SET merchant_id = :merchant_id,
            app_id = :app_id,
            api_secret = :api_secret,
            role = 'seller'
        WHERE username = 'test_seller'
    ");

    $updated = $stmt->execute([
        ':merchant_id' => $merchantId,
        ':app_id' => $appId,
        ':api_secret' => password_hash($apiSecret, PASSWORD_BCRYPT) // Store hashed for security
    ]);

    if ($updated) {
        echo "\033[0;32m✓ Test seller user updated successfully!\033[0m\n";
        echo "\nLogin credentials:\n";
        echo "  Username: test_seller\n";
        echo "  Password: password123\n\n";
    } else {
        echo "\033[0;31m✗ Failed to update user\033[0m\n";
        exit(1);
    }

} else {
    // Create new test seller
    echo "Creating new test seller user...\n\n";

    $password = 'password123';
    $passwordHash = password_hash($password, PASSWORD_BCRYPT);

    $stmt = $db->prepare("
        INSERT INTO users
        (username, password, email, role, merchant_id, app_id, api_secret)
        VALUES (:username, :password, :email, :role, :merchant_id, :app_id, :api_secret)
    ");

    $created = $stmt->execute([
        ':username' => 'test_seller',
        ':password' => $passwordHash,
        ':email' => 'test_seller@example.com',
        ':role' => 'seller',
        ':merchant_id' => $merchantId,
        ':app_id' => $appId,
        ':api_secret' => password_hash($apiSecret, PASSWORD_BCRYPT)
    ]);

    if ($created) {
        $userId = (int)$db->lastInsertId();
        echo "\033[0;32m✓ Test seller user created! (ID: $userId)\033[0m\n";
        echo "\n\033[1;36mLogin credentials:\033[0m\n";
        echo "  \033[1mUsername:\033[0m test_seller\n";
        echo "  \033[1mPassword:\033[0m password123\n\n";
        echo "\033[1;33m⚠  IMPORTANT: Use these credentials to login before testing QR payments\033[0m\n";
    } else {
        echo "\033[0;31m✗ Failed to create user\033[0m\n";
        exit(1);
    }
}

echo "\n";
echo "\033[1;34mNext steps:\033[0m\n";
echo "  1. Login via your frontend with:\n";
echo "     Username: test_seller\n";
echo "     Password: password123\n";
echo "\n";
echo "  2. Run Bakong test: php test_bakong.php\n";
echo "\n";
echo "  3. Test QR flow from the UI:\n";
echo "     - Click 'Generate QR'\n";
echo "     - Scan the QR with your Bakong app\n";
echo "     - Click 'Confirm Payment'\n";
echo "\n";
