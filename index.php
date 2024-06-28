<?php

require 'vendor/autoload.php';

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Reelz222z\CryptoExchange\User;
use Reelz222z\CryptoExchange\CoinMarketCapApiClient;
use Reelz222z\CryptoExchange\TransactionHistory;
use Reelz222z\CryptoExchange\Login;

$client = new Client();
$apiUrl = 'https://pro-api.coinmarketcap.com/v1/cryptocurrency/listings/latest';
$apiKey = 'a1c0390e-141d-4ddc-8766-6eb831f7b848';

function login(): User
{
    while (true) {
        $username = readline("Enter your username: ");
        $password = readline("Enter your password: ");
        $user = Login::authenticate($username, $password);

        if (!$user) {
            echo "Invalid username or password.\n";
            exit(1);
        }
        return $user;
    }
}
function getUserHoldings(int $userId): array
{
    $transactions = TransactionHistory::getTransactions($userId);
    $holdings = [];

    foreach ($transactions as $transaction) {
        $symbol = $transaction['asset'];
        $amount = $transaction['amount'];
        $type = $transaction['transaction_type'];

        if (!isset($holdings[$symbol])) {
            $holdings[$symbol] = 0;
        }

        if ($type === 'buy') {
            $holdings[$symbol] += $amount;
        } elseif ($type === 'sell') {
            $holdings[$symbol] -= $amount;
        }
    }

    return $holdings;
}
try {
    $user = login();

    echo "User found: " . $user->getName() .
        " with wallet balance: " .
        $user->getWallet()->getBalance() . " USD\n";

    $cryptoData = new CoinMarketCapApiClient($client, $apiUrl, $apiKey);
    $topCryptos = $cryptoData->fetchTopCryptocurrencies();
    echo "Top cryptocurrencies fetched successfully.\n";

    function displayMenu(): void
    {
        echo "Choose an option:\n";
        echo "1. List top cryptocurrencies\n";
        echo "2. Search cryptocurrency by symbol\n";
        echo "3. Buy cryptocurrency\n";
        echo "4. Sell cryptocurrency\n";
        echo "5. Display wallet state\n";
        echo "6. Display transaction history\n";
        echo "7. Exit\n";
    }

    while (true) {
        displayMenu();
        $choice = (int) readline("Enter your choice: ");

        switch ($choice) {
            case 1:
                echo "Available Cryptocurrencies:\n";
                foreach ($topCryptos as $crypto) {
                    echo "Name: " .
                        $crypto->getName() . " - Symbol: " .
                        $crypto->getSymbol() . "\n";
                    echo "Market Cap Dominance: "
                        . $crypto->getQuote()->getMarketCapDominance() . "\n";
                    echo "Price: $"
                        . $crypto->getQuote()->getPrice() . "\n";
                }
                break;

            case 2:
                $symbol = readline("Enter the cryptocurrency symbol: ");
                $crypto = $cryptoData->getCryptocurrencyBySymbol($symbol);
                if ($crypto === null) {
                    echo "Cryptocurrency not found.\n";
                    break;
                }
                echo "Name: " . $crypto->getName() . "\n";
                echo "Symbol: " . $crypto->getSymbol() . "\n";
                echo "Market Cap: $" . $crypto->getQuote()->getMarketCap() . "\n";
                echo "Price: $" . $crypto->getQuote()->getPrice() . "\n";
                echo "Market Cap Dominance: " . $crypto->getQuote()->getMarketCapDominance() . "\n";
                break;

            case 3:
                $symbol = readline("Enter the cryptocurrency symbol to buy: ");
                $crypto = $cryptoData->getCryptocurrencyBySymbol($symbol);
                if ($crypto === null) {
                    echo "Cryptocurrency not found.\n";
                    break;
                }
                echo "Name: " . $crypto->getName() . "\n";
                echo "Symbol: " . $crypto->getSymbol() . "\n";
                echo "Price: $" . $crypto->getQuote()->getPrice() . "\n";
                $choice = readline("Do you want to purchase this value? (yes/no): ");
                if (strtolower($choice) === 'yes') {
                    $amount = (float) readline("Enter the amount to buy: ");
                    try {
                        $user->getWallet()->deduct($crypto->getQuote()->getPrice() * $amount);
                        TransactionHistory::addTransaction(
                            $user->getId(),
                            $crypto->getSymbol(),
                            $amount,
                            'buy',
                            $crypto->getQuote()->getPrice()
                        );
                        echo "Bought $amount of " . $crypto->getName() . "\n";
                    } catch (Exception $e) {
                        echo "Error: " . $e->getMessage() . "\n";
                    }
                }
                break;

            case 4:
                $symbol = readline("Enter the cryptocurrency symbol to sell: ");
                $crypto = $cryptoData->getCryptocurrencyBySymbol($symbol);
                if ($crypto === null) {
                    echo "Cryptocurrency not found.\n";
                    break;
                }

                // Get user's current holdings
                $holdings = getUserHoldings($user->getId());
                $currentQuantity = $holdings[$symbol] ?? 0;

                echo "Name: " . $crypto->getName() . "\n";
                echo "Symbol: " . $crypto->getSymbol() . "\n";
                echo "Price: $" . $crypto->getQuote()->getPrice() . "\n";
                echo "You currently own: $currentQuantity of " . $crypto->getSymbol() . "\n";

                $amount = (float) readline("Enter the amount to sell: ");

                if ($amount > $currentQuantity) {
                    echo "You do not have enough of this cryptocurrency to sell.\n";
                    break;
                }

                $totalEarnings = $crypto->getQuote()->getPrice() * $amount;
                $user->getWallet()->add($totalEarnings);
                TransactionHistory::addTransaction(
                    $user->getId(),
                    $crypto->getSymbol(),
                    $amount,
                    'sell',
                    $crypto->getQuote()->getPrice()
                );
                echo "Sold $amount of "
                    . $crypto->getName()
                    . " for $" . $totalEarnings . "\n";
                break;

            case 5:
                echo "Current Wallet State:\n";
                echo "Balance: $" . $user->getWallet()->getBalance() . " USD\n";

                // Display the user's current cryptocurrency holdings
                $holdings = getUserHoldings($user->getId());
                if (empty($holdings)) {
                    echo "No cryptocurrency holdings.\n";
                    break;
                }
                echo "Cryptocurrency Holdings:\n";
                foreach ($holdings as $symbol => $quantity) {
                    echo "$symbol: $quantity\n";
                }
                break;

            case 6:
                echo "Transaction History:\n";
                $transactions = TransactionHistory::getTransactions($user->getId());
                foreach ($transactions as $transaction) {
                    echo $transaction['date'] . ": "
                        . $transaction['transaction_type'] . " "
                        . $transaction['amount']
                        . " of " . $transaction['asset']
                        . " at $" . $transaction['price']
                        . " each. Total: $" . $transaction['total'] . "\n";
                }
                break;

            case 7:
                exit;

            default:
                echo "Invalid choice. Please try again.\n";
                break;
        }
    }
} catch (GuzzleException $e) {
    echo "GuzzleException: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "Exception: " . $e->getMessage() . "\n";
}
