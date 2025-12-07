<?php

declare(strict_types=1);

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidNotificationType implements ValidationRule
{
    protected array $allowed;

    public function __construct(array $allowed = ['in_app', 'email', 'sms', 'push'])
    {
        $this->allowed = $allowed;
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || ! in_array($value, $this->allowed, true)) {
            $fail('Invalid notification type.');
        }
    }
}
