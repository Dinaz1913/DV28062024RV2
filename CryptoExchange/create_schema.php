<?php

require 'vendor/autoload.php';

use Reelz222z\CryptoExchange\Database;

$pdo = Database::getInstance()->getConnection();

echo "Connecting to database at: " . __DIR__ . "/../../crypto_exchange.sqlite\n";

// Drop existing tables
$pdo->exec("DROP TABLE IF EXISTS users");
$pdo->exec("DROP TABLE IF EXISTS wallets");
$pdo->exec("DROP TABLE IF EXISTS transactions");

echo "Tables dropped.\n";

// Create users table
$pdo->exec("CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    email TEXT NOT NULL,
    password TEXT NOT NULL
)");

echo "Users table created.\n";

// Create wallets table
$pdo->exec("CREATE TABLE IF NOT EXISTS wallets (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    balance REAL NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users (id)
)");

echo "Wallets table created.\n";

// Create transactions table
$pdo->exec("CREATE TABLE IF NOT EXISTS transactions (
    id TEXT PRIMARY KEY,
    user_id INTEGER NOT NULL,
    asset TEXT NOT NULL,
    amount REAL NOT NULL,
    transaction_type TEXT NOT NULL,
    date TEXT NOT NULL,
    price REAL NOT NULL,
    total REAL NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users (id)
)");

echo "Transactions table created.\n";
echo "Database schema created successfully.\n";
