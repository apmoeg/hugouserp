<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/**
 * Data Validation Service
 *
 * Provides centralized validation methods for common data types
 * to ensure consistency and data integrity across the application.
 */
class DataValidationService
{
    /**
     * Validate email address with strict rules
     */
    public function validateEmail(string $email, bool $checkDNS = false): bool
    {
        $rules = $checkDNS ? 'email:rfc,dns' : 'email:rfc';

        $validator = Validator::make(
            ['email' => $email],
            ['email' => [$rules]]
        );

        return ! $validator->fails();
    }

    /**
     * Validate phone number format
     */
    public function validatePhone(string $phone): bool
    {
        // Allow international format with +, digits, spaces, dashes, parentheses
        $pattern = '/^[\+]?[\d\s\-\(\)]{7,20}$/';

        return preg_match($pattern, $phone) === 1;
    }

    /**
     * Validate and sanitize phone number
     */
    public function sanitizePhone(string $phone): string
    {
        // Remove all non-digit characters except leading +
        if (str_starts_with($phone, '+')) {
            return '+' . preg_replace('/[^\d]/', '', substr($phone, 1));
        }

        return preg_replace('/[^\d]/', '', $phone);
    }

    /**
     * Validate tax number format (generic)
     */
    public function validateTaxNumber(string $taxNumber, string $country = 'EG'): bool
    {
        // Country-specific validation can be added here
        return match ($country) {
            'EG' => $this->validateEgyptianTaxNumber($taxNumber),
            'SA' => $this->validateSaudiTaxNumber($taxNumber),
            'AE' => $this->validateUAETaxNumber($taxNumber),
            default => strlen($taxNumber) >= 5 && strlen($taxNumber) <= 50,
        };
    }

    /**
     * Validate Egyptian tax number
     */
    private function validateEgyptianTaxNumber(string $taxNumber): bool
    {
        // Egyptian tax numbers are typically 9 digits
        return preg_match('/^\d{9}$/', $taxNumber) === 1;
    }

    /**
     * Validate Saudi tax number (VAT)
     */
    private function validateSaudiTaxNumber(string $taxNumber): bool
    {
        // Saudi VAT numbers are 15 digits
        return preg_match('/^\d{15}$/', $taxNumber) === 1;
    }

    /**
     * Validate UAE tax number (TRN)
     */
    private function validateUAETaxNumber(string $taxNumber): bool
    {
        // UAE TRN is 15 digits
        return preg_match('/^\d{15}$/', $taxNumber) === 1;
    }

    /**
     * Validate currency code (ISO 4217)
     */
    public function validateCurrencyCode(string $code): bool
    {
        return preg_match('/^[A-Z]{3}$/', $code) === 1;
    }

    /**
     * Validate money amount
     */
    public function validateMoneyAmount(mixed $amount, float $min = 0, float $max = 999999999.9999): bool
    {
        if (! is_numeric($amount)) {
            return false;
        }

        $amount = (float) $amount;

        return $amount >= $min && $amount <= $max;
    }

    /**
     * Validate percentage
     */
    public function validatePercentage(mixed $percentage, float $min = 0, float $max = 100): bool
    {
        if (! is_numeric($percentage)) {
            return false;
        }

        $percentage = (float) $percentage;

        return $percentage >= $min && $percentage <= $max;
    }

    /**
     * Validate SKU format
     */
    public function validateSKU(string $sku): bool
    {
        // SKU should be alphanumeric with dashes and underscores, 2-100 chars
        return preg_match('/^[a-zA-Z0-9_\-]{2,100}$/', $sku) === 1;
    }

    /**
     * Validate barcode format
     */
    public function validateBarcode(string $barcode): bool
    {
        // Common barcode formats: EAN-13, EAN-8, UPC-A, etc.
        $length = strlen($barcode);

        return in_array($length, [8, 12, 13, 14], true) && ctype_digit($barcode);
    }

    /**
     * Validate Arabic text
     */
    public function validateArabicText(string $text): bool
    {
        // Check if text contains Arabic characters
        return preg_match('/[\x{0600}-\x{06FF}]/u', $text) === 1;
    }

    /**
     * Sanitize HTML input (for notes, descriptions)
     */
    public function sanitizeHTML(string $html, bool $allowBasicTags = true): string
    {
        if (! $allowBasicTags) {
            return strip_tags($html);
        }

        // Allow only safe HTML tags
        $allowedTags = '<p><br><strong><em><u><ul><ol><li><a><h1><h2><h3><h4>';

        return strip_tags($html, $allowedTags);
    }

    /**
     * Validate date range
     */
    public function validateDateRange(string $startDate, string $endDate): bool
    {
        try {
            $start = new \DateTime($startDate);
            $end = new \DateTime($endDate);

            return $start <= $end;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Validate credit card number using Luhn algorithm
     */
    public function validateCreditCard(string $number): bool
    {
        $number = preg_replace('/\D/', '', $number);

        if (strlen($number) < 13 || strlen($number) > 19) {
            return false;
        }

        return $this->luhnCheck($number);
    }

    /**
     * Luhn algorithm for credit card validation
     */
    private function luhnCheck(string $number): bool
    {
        $sum = 0;
        $numDigits = strlen($number);
        $parity = $numDigits % 2;

        for ($i = 0; $i < $numDigits; $i++) {
            $digit = (int) $number[$i];

            if ($i % 2 == $parity) {
                $digit *= 2;
            }

            if ($digit > 9) {
                $digit -= 9;
            }

            $sum += $digit;
        }

        return ($sum % 10) === 0;
    }

    /**
     * Validate IBAN (International Bank Account Number)
     */
    public function validateIBAN(string $iban): bool
    {
        // Remove spaces and convert to uppercase
        $iban = strtoupper(str_replace(' ', '', $iban));

        // Check length (15-34 characters)
        if (strlen($iban) < 15 || strlen($iban) > 34) {
            return false;
        }

        // Check format (2 letters + 2 digits + alphanumeric)
        if (! preg_match('/^[A-Z]{2}\d{2}[A-Z0-9]+$/', $iban)) {
            return false;
        }

        // Move first 4 characters to end
        $iban = substr($iban, 4) . substr($iban, 0, 4);

        // Replace letters with numbers (A=10, B=11, ..., Z=35)
        $iban = preg_replace_callback('/[A-Z]/', function ($matches) {
            return ord($matches[0]) - 55;
        }, $iban);

        // Check if mod 97 equals 1
        return bcmod($iban, '97') === '1';
    }

    /**
     * Validate batch of data against rules
     */
    public function validateBatch(array $data, array $rules): array
    {
        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        return $validator->validated();
    }
}
