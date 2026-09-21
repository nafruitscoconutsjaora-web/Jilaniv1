<?php
/**
 * SMM Panel - Currency Converter & Manager
 * Production-ready numerical conversion & pricing snapshots
 */

require_once __DIR__ . '/../db.php';

class Currency {
    private static ?array $currencyCache = null;

    /**
     * Get All Active Currencies
     */
    public static function getAllActive(): array {
        if (self::$currencyCache === null) {
            self::$currencyCache = DB::fetchAll("SELECT * FROM currencies WHERE status = 'active' ORDER BY is_default DESC, code ASC");
        }
        return self::$currencyCache;
    }

    /**
     * Alias for getAllActive()
     */
    public static function getActive(): array {
        return self::getAllActive();
    }

    /**
     * Get Specific Currency by Code
     */
    public static function get(string $code): ?array {
        $currencies = self::getAllActive();
        foreach ($currencies as $c) {
            if ($c['code'] === $code) {
                return $c;
            }
        }
        return DB::fetch("SELECT * FROM currencies WHERE code = ? LIMIT 1", [$code]);
    }

    /**
     * Get Base Currency (USD, rate = 1.0)
     */
    public static function getBaseCurrency(): array {
        $base = DB::fetch("SELECT * FROM currencies WHERE rate = 1.000000 LIMIT 1");
        return $base ?: [
            'code' => 'USD',
            'name' => 'US Dollar',
            'symbol' => '$',
            'rate' => 1.000000
        ];
    }

    /**
     * Get Currency for Logged-In User
     */
    public static function getUserCurrency(?array $user = null): array {
        if ($user === null && class_exists('Auth')) {
            $user = Auth::user();
        }

        if ($user && !empty($user['currency_code'])) {
            $curr = self::get($user['currency_code']);
            if ($curr) {
                return $curr;
            }
        }

        // Default currency if user has not selected or guest
        $default = DB::fetch("SELECT * FROM currencies WHERE is_default = 1 AND status = 'active' LIMIT 1");
        return $default ?: self::getBaseCurrency();
    }

    /**
     * Convert USD Amount to Specified Target Rate (Actual Numerical Conversion)
     */
    public static function convert(float $amountUsd, float $exchangeRate): float {
        return round($amountUsd * $exchangeRate, 4);
    }

    /**
     * Convert Amount in Target Currency back to Base USD
     */
    public static function convertToUsd(float $amountTarget, float $exchangeRate): float {
        if ($exchangeRate <= 0) {
            return $amountTarget;
        }
        return round($amountTarget / $exchangeRate, 4);
    }

    /**
     * Format an Amount with Symbol and Converted Number
     * e.g., $1.00 USD converted at rate 90 = "₹90.00"
     */
    public static function format(float $amountUsd, ?array $currency = null, int $decimals = 2): string {
        if ($currency === null) {
            $currency = self::getUserCurrency();
        }

        $rate = (float)($currency['rate'] ?? 1.0);
        $symbol = $currency['symbol'] ?? '$';
        $code = $currency['code'] ?? 'USD';

        $converted = round($amountUsd * $rate, 4);
        
        // Use 2 decimals normally, or 3-4 if fraction is smaller than 0.01
        if ($converted > 0 && $converted < 0.01) {
            $decimals = 4;
        }

        return $symbol . number_format($converted, $decimals);
    }

    /**
     * Format balance helper supporting string code or array
     */
    public static function formatBalance(float $amountUsd, string|array|null $currency = null, int $decimals = 2): string {
        if (is_string($currency)) {
            $currency = self::get($currency);
        }
        return self::format($amountUsd, $currency, $decimals);
    }

    /**
     * Format Raw Converted Currency Amount (when already converted)
     */
    public static function formatRaw(float $amount, string $symbol, int $decimals = 2): string {
        if ($amount > 0 && $amount < 0.01) {
            $decimals = 4;
        }
        return $symbol . number_format($amount, $decimals);
    }

    /**
     * Create Immutable Order Pricing Snapshot
     * Ensures orders maintain exact historical pricing even if admin updates exchange rates later
     */
    public static function createPricingSnapshot(float $serviceRateUsdPer1000, int $quantity, array $userCurrency): array {
        // Base cost in USD
        $costUsd = round(($serviceRateUsdPer1000 / 1000) * $quantity, 4);
        $rate = (float)($userCurrency['rate'] ?? 1.0);
        $currencyCode = $userCurrency['code'] ?? 'USD';
        
        // Converted cost in user's currency at the time of purchase
        $userCost = round($costUsd * $rate, 4);

        return [
            'charge'          => $costUsd,        // Base USD deducted from ledger
            'charge_currency' => $currencyCode,   // e.g. INR
            'currency_rate'   => $rate,           // Snapshot rate (e.g. 90.000000)
            'user_charge'     => $userCost        // Snapshot price in user currency (e.g. ₹90.00)
        ];
    }
}
