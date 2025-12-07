<?php

declare(strict_types=1);

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Carbon;

class ValidContractDates implements ValidationRule
{
    public function __construct(
        protected string $endField,
        protected bool $allowEqual = false
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $start = $this->asDate($value);
        $end = $this->asDate(request()->input($this->endField));

        if (! $start || ! $end) {
            return;
        }

        if ($this->allowEqual ? $start->gt($end) : $start->gte($end)) {
            $fail('Start date must be before end date.');
        }
    }

    protected function asDate(mixed $v): ?Carbon
    {
        try {
            if ($v instanceof Carbon) {
                return $v;
            }
            if (empty($v)) {
                return null;
            }

            return Carbon::parse($v);
        } catch (\Throwable) {
            return null;
        }
    }
}
