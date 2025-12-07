<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use App\Traits\HandlesServiceErrors;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;
use PragmaRX\Google2FA\Google2FA;

class TwoFactorAuthService
{
    use HandlesServiceErrors;

    protected Google2FA $google2fa;

    public function __construct()
    {
        $this->google2fa = new Google2FA;
    }

    public function generateSecretKey(): string
    {
        return $this->handleServiceOperation(
            callback: fn () => $this->google2fa->generateSecretKey(),
            operation: 'generateSecretKey',
            context: []
        );
    }

    public function getQRCodeUrl(User $user, string $secret): string
    {
        return $this->handleServiceOperation(
            callback: function () use ($user, $secret) {
                $appName = config('app.name', 'GhanemERP');
                $appName = str_replace(' ', '', $appName);

                return $this->google2fa->getQRCodeUrl(
                    $appName,
                    $user->email,
                    $secret
                );
            },
            operation: 'getQRCodeUrl',
            context: ['user_id' => $user->id]
        );
    }

    public function verify(string $secret, string $code): bool
    {
        return $this->handleServiceOperation(
            callback: fn () => $this->google2fa->verifyKey($secret, $code),
            operation: 'verify',
            context: [],
            defaultValue: false
        );
    }

    public function enableTwoFactor(User $user, string $secret): bool
    {
        return $this->handleServiceOperation(
            callback: function () use ($user, $secret) {
                $user->two_factor_secret = Crypt::encryptString($secret);
                $user->two_factor_recovery_codes = Crypt::encryptString(
                    json_encode($this->generateRecoveryCodes())
                );
                $user->two_factor_enabled = true;
                $user->two_factor_confirmed_at = now();

                return $user->save();
            },
            operation: 'enableTwoFactor',
            context: ['user_id' => $user->id],
            defaultValue: false
        );
    }

    public function disableTwoFactor(User $user): bool
    {
        return $this->handleServiceOperation(
            callback: function () use ($user) {
                $user->two_factor_secret = null;
                $user->two_factor_recovery_codes = null;
                $user->two_factor_enabled = false;
                $user->two_factor_confirmed_at = null;

                return $user->save();
            },
            operation: 'disableTwoFactor',
            context: ['user_id' => $user->id],
            defaultValue: false
        );
    }

    public function getDecryptedSecret(User $user): ?string
    {
        return $this->handleServiceOperation(
            callback: function () use ($user) {
                if (! $user->two_factor_secret) {
                    return null;
                }

                return Crypt::decryptString($user->two_factor_secret);
            },
            operation: 'getDecryptedSecret',
            context: ['user_id' => $user->id],
            defaultValue: null
        );
    }

    public function getRecoveryCodes(User $user): array
    {
        return $this->handleServiceOperation(
            callback: function () use ($user) {
                if (! $user->two_factor_recovery_codes) {
                    return [];
                }

                return json_decode(
                    Crypt::decryptString($user->two_factor_recovery_codes),
                    true
                ) ?? [];
            },
            operation: 'getRecoveryCodes',
            context: ['user_id' => $user->id],
            defaultValue: []
        );
    }

    public function regenerateRecoveryCodes(User $user): array
    {
        return $this->handleServiceOperation(
            callback: function () use ($user) {
                $codes = $this->generateRecoveryCodes();

                $user->two_factor_recovery_codes = Crypt::encryptString(json_encode($codes));
                $user->save();

                return $codes;
            },
            operation: 'regenerateRecoveryCodes',
            context: ['user_id' => $user->id],
            defaultValue: []
        );
    }

    public function verifyRecoveryCode(User $user, string $code): bool
    {
        return $this->handleServiceOperation(
            callback: function () use ($user, $code) {
                $codes = $this->getRecoveryCodes($user);

                if (! in_array($code, $codes)) {
                    return false;
                }

                $codes = array_values(array_filter($codes, fn ($c) => $c !== $code));

                $user->two_factor_recovery_codes = Crypt::encryptString(json_encode($codes));
                $user->save();

                return true;
            },
            operation: 'verifyRecoveryCode',
            context: ['user_id' => $user->id],
            defaultValue: false
        );
    }

    protected function generateRecoveryCodes(int $count = 8): array
    {
        return Collection::times($count, function () {
            return Str::random(10).'-'.Str::random(10);
        })->all();
    }

    public function isEnabled(User $user): bool
    {
        return $user->two_factor_enabled && $user->two_factor_confirmed_at !== null;
    }
}
