<?php

namespace Reelz222z\CryptoExchange;

use PDO;

class Login
{
    public static function authenticate(string $username, string $password): ?User
    {
        $pdo = Database::getInstance()->getConnection();
        $normalizedUsername = trim(strtolower($username));
        echo "Authenticating user: " . $normalizedUsername . "\n";

        $stmt = $pdo->prepare("SELECT * FROM users WHERE LOWER(name) = :name");
        $stmt->execute([':name' => $normalizedUsername]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            echo "User not found in database.\n";
            return null;
        }
        echo "User found in database: " . print_r($user, true) . "\n";


        $hashedPassword = md5(trim($password));
        echo "Provided password hash: " . $hashedPassword . "\n";
        echo "Stored password hash: " . $user['password'] . "\n";

        if ($hashedPassword === $user['password']) {
            $wallet = Wallet::loadWallet($user['id']);
            echo "Loaded wallet balance for user " . $normalizedUsername . ": " . $wallet->getBalance() . "\n";
            return new User($user['name'], $wallet, $user['email'], $user['password'], (int)$user['id']);
        }

        echo "Password mismatch for user: " . $normalizedUsername . "\n";
        return null;
    }
}
