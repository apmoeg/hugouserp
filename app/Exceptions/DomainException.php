<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;

class DomainException extends Exception
{
    public function __construct(string $message = 'Domain error', public int $status = 422, ?\Throwable $previous = null)
    {
        parent::__construct($message, $status, $previous);
    }

    public function render($request): JsonResponse
    {
        $payload = [
            'success' => false,
            'error' => class_basename(static::class),
            'message' => $this->getMessage(),
        ];

        return response()->json($payload, $this->status);
    }
}
