<?php

require __DIR__ . '/../vendor/autoload.php';

use Reelz222z\CryptoExchange\Database;
use Reelz222z\CryptoExchange\User;
use Reelz222z\CryptoExchange\Wallet;

class PredefinedUsers
{
    public static function insertPredefinedUsers(): void
    {
        $users = [
            [
                'name' => 'alice',
                'email' => 'alice@example.com',
                'wallet_balance' => 1000.0,
                'password' => 'password123'
            ],
            [
                'name' => 'bob',
                'email' => 'bob@example.com',
                'wallet_balance' => 1500.0,
                'password' => 'password456'
            ],
            [
                'name' => 'charlie',
                'email' => 'charlie@example.com',
                'wallet_balance' => 2000.0,
                'password' => 'password789'
            ]
        ];

        foreach ($users as $userData) {
            $hashedPassword = md5(trim($userData['password']));
            $user = new User(
                $userData['name'],
                new Wallet(0, $userData['wallet_balance']), // Initialize with default balance
                $userData['email'],
                $hashedPassword
            );
            User::saveUser($user);
            $wallet = new Wallet($user->getId(), $userData['wallet_balance']);
            Wallet::saveWallet($wallet);
            echo "Inserted user: " . $userData['name'] . "\n";
        }

        echo "Predefined users inserted successfully.\n";
    }
}

PredefinedUsers::insertPredefinedUsers();
