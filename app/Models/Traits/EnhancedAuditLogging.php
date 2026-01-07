<?php

declare(strict_types=1);

namespace App\Models\Traits;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;

/**
 * Enhanced Audit Logging Trait
 *
 * Provides comprehensive audit logging for sensitive operations
 * with automatic tracking of user actions, IP addresses, and changes.
 */
trait EnhancedAuditLogging
{
    /**
     * Boot the enhanced audit logging trait
     */
    protected static function bootEnhancedAuditLogging(): void
    {
        // Log creation
        static::created(function ($model) {
            $model->logAuditEvent('created', null, $model->getAuditableAttributes());
        });

        // Log updates
        static::updated(function ($model) {
            $original = $model->getOriginal();
            $changes = $model->getChanges();

            // Remove timestamps from audit log
            unset($changes['updated_at'], $changes['created_at']);

            if (! empty($changes)) {
                $model->logAuditEvent('updated', $original, $changes);
            }
        });

        // Log deletion
        static::deleted(function ($model) {
            $model->logAuditEvent('deleted', $model->getAuditableAttributes(), null);
        });

        // Log restoration (if using soft deletes)
        if (method_exists(static::class, 'restored')) {
            static::restored(function ($model) {
                $model->logAuditEvent('restored', null, $model->getAuditableAttributes());
            });
        }
    }

    /**
     * Log an audit event
     */
    protected function logAuditEvent(string $action, ?array $oldValues, ?array $newValues): void
    {
        try {
            AuditLog::create([
                'user_id' => Auth::id(),
                'auditable_type' => get_class($this),
                'auditable_id' => $this->getKey(),
                'action' => $action,
                'old_values' => $this->sanitizeAuditValues($oldValues),
                'new_values' => $this->sanitizeAuditValues($newValues),
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'url' => request()->fullUrl(),
                'branch_id' => $this->branch_id ?? Auth::user()?->branch_id,
            ]);
        } catch (\Exception $e) {
            // Log error but don't fail the operation
            logger()->error('Failed to create audit log: ' . $e->getMessage(), [
                'model' => get_class($this),
                'id' => $this->getKey(),
                'action' => $action,
            ]);
        }
    }

    /**
     * Get auditable attributes (excluding sensitive data)
     */
    protected function getAuditableAttributes(): array
    {
        $attributes = $this->attributesToArray();

        // Remove sensitive fields from audit logs
        $sensitiveFields = $this->getSensitiveFields();

        foreach ($sensitiveFields as $field) {
            if (isset($attributes[$field])) {
                $attributes[$field] = '[REDACTED]';
            }
        }

        return $attributes;
    }

    /**
     * Get list of sensitive fields that should be redacted in audit logs
     */
    protected function getSensitiveFields(): array
    {
        return [
            'password',
            'remember_token',
            'two_factor_secret',
            'two_factor_recovery_codes',
            'api_token',
            'secret_key',
            'private_key',
            'access_token',
            'refresh_token',
        ];
    }

    /**
     * Sanitize audit values
     */
    protected function sanitizeAuditValues(?array $values): ?array
    {
        if ($values === null) {
            return null;
        }

        $sensitiveFields = $this->getSensitiveFields();

        foreach ($sensitiveFields as $field) {
            if (isset($values[$field])) {
                $values[$field] = '[REDACTED]';
            }
        }

        return $values;
    }

    /**
     * Get audit logs for this model
     */
    public function auditLogs()
    {
        return $this->morphMany(AuditLog::class, 'auditable')
            ->latest()
            ->with('user:id,name,email');
    }

    /**
     * Get recent audit history
     */
    public function getRecentAuditHistory(int $limit = 10): \Illuminate\Database\Eloquent\Collection
    {
        return $this->auditLogs()
            ->limit($limit)
            ->get();
    }

    /**
     * Check if model has been modified by specific user
     */
    public function wasModifiedBy(int $userId): bool
    {
        return $this->auditLogs()
            ->where('user_id', $userId)
            ->whereIn('action', ['created', 'updated'])
            ->exists();
    }

    /**
     * Get last modification details
     */
    public function getLastModification(): ?AuditLog
    {
        return $this->auditLogs()
            ->whereIn('action', ['created', 'updated'])
            ->first();
    }
}
