<?php

namespace App\Helpers;

class CurrencyHelper
{
    public static function getCurrencies()
    {
        return [
            'USD' => ['name' => 'US Dollar', 'symbol' => '$', 'flag' => '🇺🇸'],
            'EUR' => ['name' => 'Euro', 'symbol' => '€', 'flag' => '🇪🇺'],
            'GBP' => ['name' => 'British Pound', 'symbol' => '£', 'flag' => '🇬🇧'],
            'JPY' => ['name' => 'Japanese Yen', 'symbol' => '¥', 'flag' => '🇯🇵'],
            'CAD' => ['name' => 'Canadian Dollar', 'symbol' => 'C$', 'flag' => '🇨🇦'],
            'AUD' => ['name' => 'Australian Dollar', 'symbol' => 'A$', 'flag' => '🇦🇺'],
            'CHF' => ['name' => 'Swiss Franc', 'symbol' => 'CHF', 'flag' => '🇨🇭'],
            'CNY' => ['name' => 'Chinese Yuan', 'symbol' => '¥', 'flag' => '🇨🇳'],
            'INR' => ['name' => 'Indian Rupee', 'symbol' => '₹', 'flag' => '🇮🇳'],
            'BRL' => ['name' => 'Brazilian Real', 'symbol' => 'R$', 'flag' => '🇧🇷'],
            'RUB' => ['name' => 'Russian Ruble', 'symbol' => '₽', 'flag' => '🇷🇺'],
            'MXN' => ['name' => 'Mexican Peso', 'symbol' => '$', 'flag' => '🇲🇽'],
            'TZS' => ['name' => 'Tanzanian Shilling', 'symbol' => 'TSh', 'flag' => '🇹🇿'],
            'KES' => ['name' => 'Kenyan Shilling', 'symbol' => 'KSh', 'flag' => '🇰🇪'],
            'ZAR' => ['name' => 'South African Rand', 'symbol' => 'R', 'flag' => '🇿🇦'],
            'NGN' => ['name' => 'Nigerian Naira', 'symbol' => '₦', 'flag' => '🇳🇬'],
            'EGP' => ['name' => 'Egyptian Pound', 'symbol' => 'E£', 'flag' => '🇪🇬'],
            'GHS' => ['name' => 'Ghanaian Cedi', 'symbol' => 'GH₵', 'flag' => '🇬🇭'],
            'AED' => ['name' => 'UAE Dirham', 'symbol' => 'د.إ', 'flag' => '🇦🇪'],
            'SAR' => ['name' => 'Saudi Riyal', 'symbol' => '﷼', 'flag' => '🇸🇦'],
            'QAR' => ['name' => 'Qatari Riyal', 'symbol' => 'ر.ق', 'flag' => '🇶🇦'],
            'TRY' => ['name' => 'Turkish Lira', 'symbol' => '₺', 'flag' => '🇹🇷'],
            'KRW' => ['name' => 'South Korean Won', 'symbol' => '₩', 'flag' => '🇰🇷'],
            'SGD' => ['name' => 'Singapore Dollar', 'symbol' => 'S$', 'flag' => '🇸🇬'],
            'NZD' => ['name' => 'New Zealand Dollar', 'symbol' => 'NZ$', 'flag' => '🇳🇿'],
            'HKD' => ['name' => 'Hong Kong Dollar', 'symbol' => 'HK$', 'flag' => '🇭🇰'],
            'SEK' => ['name' => 'Swedish Krona', 'symbol' => 'kr', 'flag' => '🇸🇪'],
            'NOK' => ['name' => 'Norwegian Krone', 'symbol' => 'kr', 'flag' => '🇳🇴'],
            'DKK' => ['name' => 'Danish Krone', 'symbol' => 'kr', 'flag' => '🇩🇰'],
            'PLN' => ['name' => 'Polish Złoty', 'symbol' => 'zł', 'flag' => '🇵🇱'],
            'THB' => ['name' => 'Thai Baht', 'symbol' => '฿', 'flag' => '🇹🇭'],
            'IDR' => ['name' => 'Indonesian Rupiah', 'symbol' => 'Rp', 'flag' => '🇮🇩'],
            'MYR' => ['name' => 'Malaysian Ringgit', 'symbol' => 'RM', 'flag' => '🇲🇾'],
            'PHP' => ['name' => 'Philippine Peso', 'symbol' => '₱', 'flag' => '🇵🇭'],
            'VND' => ['name' => 'Vietnamese Đồng', 'symbol' => '₫', 'flag' => '🇻🇳'],
            'BDT' => ['name' => 'Bangladeshi Taka', 'symbol' => '৳', 'flag' => '🇧🇩'],
            'PKR' => ['name' => 'Pakistani Rupee', 'symbol' => '₨', 'flag' => '🇵🇰'],
            'ILS' => ['name' => 'Israeli Shekel', 'symbol' => '₪', 'flag' => '🇮🇱'],
            'CLP' => ['name' => 'Chilean Peso', 'symbol' => 'CLP$', 'flag' => '🇨🇱'],
            'COP' => ['name' => 'Colombian Peso', 'symbol' => 'COL$', 'flag' => '🇨🇴'],
            'PEN' => ['name' => 'Peruvian Sol', 'symbol' => 'S/', 'flag' => '🇵🇪'],
            'CZK' => ['name' => 'Czech Koruna', 'symbol' => 'Kč', 'flag' => '🇨🇿'],
            'HUF' => ['name' => 'Hungarian Forint', 'symbol' => 'Ft', 'flag' => '🇭🇺'],
            'RON' => ['name' => 'Romanian Leu', 'symbol' => 'lei', 'flag' => '🇷🇴'],
            'UAH' => ['name' => 'Ukrainian Hryvnia', 'symbol' => '₴', 'flag' => '🇺🇦'],
            'KZT' => ['name' => 'Kazakhstani Tenge', 'symbol' => '₸', 'flag' => '🇰🇿'],
        ];
    }

    public static function getValidationRules()
    {
        return 'in:' . implode(',', array_keys(self::getCurrencies()));
    }

    public static function getCurrencyName($code)
    {
        $currencies = self::getCurrencies();
        return $currencies[$code]['name'] ?? $code;
    }

    public static function getCurrencySymbol($code)
    {
        $currencies = self::getCurrencies();
        return $currencies[$code]['symbol'] ?? '$';
    }

    public static function getCurrencyFlag($code)
    {
        $currencies = self::getCurrencies();
        return $currencies[$code]['flag'] ?? '💰';
    }
}