<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\SystemSetting;
use App\Traits\HandlesServiceErrors;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;

class SettingsService
{
    use HandlesServiceErrors;

    protected const CACHE_KEY = 'system_settings';

    protected const CACHE_TTL = 3600;

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->handleServiceOperation(
            callback: function () use ($key, $default) {
                $settings = $this->all();

                return $settings[$key] ?? $default;
            },
            operation: 'get',
            context: ['key' => $key],
            defaultValue: $default
        );
    }

    public function getDecrypted(string $key, mixed $default = null): mixed
    {
        return $this->handleServiceOperation(
            callback: function () use ($key, $default) {
                $setting = SystemSetting::where('key', $key)->first();

                if (! $setting) {
                    return $default;
                }

                $value = $setting->value;

                if ($setting->is_encrypted && $value) {
                    try {
                        return Crypt::decryptString(is_array($value) ? ($value[0] ?? '') : $value);
                    } catch (\Exception $e) {
                        Log::error('Failed to decrypt setting', ['key' => $key, 'error' => $e->getMessage()]);

                        return $default;
                    }
                }

                return is_array($value) ? ($value[0] ?? $value) : $value;
            },
            operation: 'getDecrypted',
            context: ['key' => $key],
            defaultValue: $default
        );
    }

    public function set(string $key, mixed $value, array $options = []): bool
    {
        return $this->handleServiceOperation(
            callback: function () use ($key, $value, $options) {
                $data = [
                    'key' => $key,
                    'value' => is_array($value) ? $value : [$value],
                    'type' => $options['type'] ?? 'string',
                    'group' => $options['group'] ?? 'general',
                    'category' => $options['category'] ?? null,
                    'is_public' => $options['is_public'] ?? false,
                    'is_encrypted' => $options['is_encrypted'] ?? false,
                    'description' => $options['description'] ?? null,
                    'sort_order' => $options['sort_order'] ?? 0,
                ];

                if ($data['is_encrypted'] && $value) {
                    $data['value'] = [Crypt::encryptString(is_array($value) ? json_encode($value) : (string) $value)];
                }

                SystemSetting::updateOrCreate(['key' => $key], $data);
                $this->clearCache();

                return true;
            },
            operation: 'set',
            context: ['key' => $key],
            defaultValue: false
        );
    }

    public function setMany(array $settings): bool
    {
        return $this->handleServiceOperation(
            callback: function () use ($settings) {
                foreach ($settings as $key => $value) {
                    if (is_array($value) && isset($value['value'])) {
                        $this->set($key, $value['value'], $value);
                    } else {
                        $this->set($key, $value);
                    }
                }

                return true;
            },
            operation: 'setMany',
            context: [],
            defaultValue: false
        );
    }

    public function all(): array
    {
        return $this->handleServiceOperation(
            callback: function () {
                return Cache::remember(self::CACHE_KEY, self::CACHE_TTL, function () {
                    return SystemSetting::pluck('value', 'key')
                        ->map(fn ($v) => is_array($v) ? ($v[0] ?? $v) : $v)
                        ->toArray();
                });
            },
            operation: 'all',
            context: [],
            defaultValue: []
        );
    }

    public function getByGroup(string $group): array
    {
        return $this->handleServiceOperation(
            callback: function () use ($group) {
                return SystemSetting::where('group', $group)
                    ->orderBy('sort_order')
                    ->get()
                    ->mapWithKeys(function ($setting) {
                        $value = $setting->value;
                        if ($setting->is_encrypted && $value) {
                            try {
                                $decrypted = Crypt::decryptString(is_array($value) ? ($value[0] ?? '') : $value);

                                return [$setting->key => $decrypted];
                            } catch (\Exception $e) {
                                return [$setting->key => null];
                            }
                        }

                        return [$setting->key => is_array($value) ? ($value[0] ?? $value) : $value];
                    })
                    ->toArray();
            },
            operation: 'getByGroup',
            context: ['group' => $group],
            defaultValue: []
        );
    }

    public function getByCategory(string $category): array
    {
        return $this->handleServiceOperation(
            callback: function () use ($category) {
                return SystemSetting::where('category', $category)
                    ->orderBy('sort_order')
                    ->get()
                    ->toArray();
            },
            operation: 'getByCategory',
            context: ['category' => $category],
            defaultValue: []
        );
    }

    public function delete(string $key): bool
    {
        return $this->handleServiceOperation(
            callback: function () use ($key) {
                SystemSetting::where('key', $key)->delete();
                $this->clearCache();

                return true;
            },
            operation: 'delete',
            context: ['key' => $key],
            defaultValue: false
        );
    }

    public function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    public function getSmsProvider(): string
    {
        return $this->handleServiceOperation(
            callback: fn () => $this->get('sms.provider', 'none'),
            operation: 'getSmsProvider',
            context: [],
            defaultValue: 'none'
        );
    }

    public function getSmsConfig(string $provider): array
    {
        return $this->handleServiceOperation(
            callback: fn () => [
                'enabled' => (bool) $this->get("sms.{$provider}.enabled", false),
                'appkey' => $this->getDecrypted("sms.{$provider}.appkey"),
                'authkey' => $this->getDecrypted("sms.{$provider}.authkey"),
                'username' => $this->getDecrypted("sms.{$provider}.username"),
                'password' => $this->getDecrypted("sms.{$provider}.password"),
                'sender_id' => $this->get("sms.{$provider}.sender_id"),
                'sandbox' => (bool) $this->get("sms.{$provider}.sandbox", false),
            ],
            operation: 'getSmsConfig',
            context: ['provider' => $provider],
            defaultValue: []
        );
    }

    public function getSecurityConfig(): array
    {
        return $this->handleServiceOperation(
            callback: fn () => [
                '2fa_enabled' => (bool) $this->get('security.2fa_enabled', false),
                '2fa_required' => (bool) $this->get('security.2fa_required', false),
                'recaptcha_enabled' => (bool) $this->get('security.recaptcha_enabled', false),
                'recaptcha_site_key' => $this->get('security.recaptcha_site_key'),
                'recaptcha_secret_key' => $this->getDecrypted('security.recaptcha_secret_key'),
                'max_sessions' => (int) $this->get('security.max_sessions', 3),
                'session_lifetime' => (int) $this->get('security.session_lifetime', 480),
                'password_expiry_days' => (int) $this->get('security.password_expiry_days', 0),
            ],
            operation: 'getSecurityConfig',
            context: [],
            defaultValue: []
        );
    }

    public function getBackupConfig(): array
    {
        return $this->handleServiceOperation(
            callback: fn () => [
                'enabled' => (bool) $this->get('backup.enabled', false),
                'frequency' => $this->get('backup.frequency', 'daily'),
                'time' => $this->get('backup.time', '02:00'),
                'retention_days' => (int) $this->get('backup.retention_days', 7),
                'include_uploads' => (bool) $this->get('backup.include_uploads', true),
            ],
            operation: 'getBackupConfig',
            context: [],
            defaultValue: []
        );
    }
}
