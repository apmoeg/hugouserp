<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use App\Models\UserSession;
use App\Traits\HandlesServiceErrors;
use Illuminate\Support\Facades\DB;
use Jenssegers\Agent\Agent;

class SessionManagementService
{
    use HandlesServiceErrors;

    public function trackSession(User $user, string $sessionId, ?string $ip = null, ?string $userAgent = null): UserSession
    {
        return $this->handleServiceOperation(
            callback: function () use ($user, $sessionId, $ip, $userAgent) {
                $agent = new Agent;
                $agent->setUserAgent($userAgent ?? '');

                UserSession::where('user_id', $user->id)
                    ->where('is_current', true)
                    ->update(['is_current' => false]);

                return UserSession::updateOrCreate(
                    ['session_id' => $sessionId],
                    [
                        'user_id' => $user->id,
                        'ip_address' => $ip,
                        'user_agent' => $userAgent,
                        'device_type' => $this->getDeviceType($agent),
                        'browser' => $agent->browser() ?: 'Unknown',
                        'platform' => $agent->platform() ?: 'Unknown',
                        'is_current' => true,
                        'last_activity' => now(),
                    ]
                );
            },
            operation: 'trackSession',
            context: ['user_id' => $user->id, 'session_id' => $sessionId]
        );
    }

    public function updateActivity(string $sessionId): void
    {
        $this->handleServiceOperation(
            callback: fn () => UserSession::where('session_id', $sessionId)
                ->update(['last_activity' => now()]),
            operation: 'updateActivity',
            context: ['session_id' => $sessionId]
        );
    }

    public function getUserSessions(User $user): \Illuminate\Database\Eloquent\Collection
    {
        return $this->handleServiceOperation(
            callback: fn () => UserSession::where('user_id', $user->id)
                ->orderByDesc('last_activity')
                ->get(),
            operation: 'getUserSessions',
            context: ['user_id' => $user->id]
        );
    }

    public function getActiveSessionCount(User $user): int
    {
        return $this->handleServiceOperation(
            callback: fn () => UserSession::where('user_id', $user->id)
                ->where('last_activity', '>=', now()->subMinutes(config('session.lifetime', 120)))
                ->count(),
            operation: 'getActiveSessionCount',
            context: ['user_id' => $user->id],
            defaultValue: 0
        );
    }

    public function terminateSession(User $user, string $sessionId): bool
    {
        return $this->handleServiceOperation(
            callback: function () use ($user, $sessionId) {
                $session = UserSession::where('user_id', $user->id)
                    ->where('session_id', $sessionId)
                    ->first();

                if (! $session) {
                    return false;
                }

                DB::table('sessions')->where('id', $sessionId)->delete();

                $session->delete();

                $this->logServiceInfo('terminateSession', 'Session terminated', [
                    'user_id' => $user->id,
                    'session_id' => $sessionId,
                ]);

                return true;
            },
            operation: 'terminateSession',
            context: ['user_id' => $user->id, 'session_id' => $sessionId],
            defaultValue: false
        );
    }

    public function terminateAllOtherSessions(User $user, string $currentSessionId): int
    {
        return $this->handleServiceOperation(
            callback: function () use ($user, $currentSessionId) {
                $sessions = UserSession::where('user_id', $user->id)
                    ->where('session_id', '!=', $currentSessionId)
                    ->get();

                $count = 0;
                foreach ($sessions as $session) {
                    DB::table('sessions')->where('id', $session->session_id)->delete();
                    $session->delete();
                    $count++;
                }

                $this->logServiceInfo('terminateAllOtherSessions', 'All other sessions terminated', [
                    'user_id' => $user->id,
                    'count' => $count,
                ]);

                return $count;
            },
            operation: 'terminateAllOtherSessions',
            context: ['user_id' => $user->id, 'current_session_id' => $currentSessionId],
            defaultValue: 0
        );
    }

    public function terminateAllSessions(User $user): int
    {
        return $this->handleServiceOperation(
            callback: function () use ($user) {
                $count = UserSession::where('user_id', $user->id)->count();

                DB::table('sessions')->where('user_id', $user->id)->delete();

                UserSession::where('user_id', $user->id)->delete();

                $user->tokens()->delete();

                $this->logServiceInfo('terminateAllSessions', 'All sessions terminated for user', [
                    'user_id' => $user->id,
                    'count' => $count,
                ]);

                return $count;
            },
            operation: 'terminateAllSessions',
            context: ['user_id' => $user->id],
            defaultValue: 0
        );
    }

    public function enforceSessionLimit(User $user, int $maxSessions): void
    {
        $this->handleServiceOperation(
            callback: function () use ($user, $maxSessions) {
                $sessions = UserSession::where('user_id', $user->id)
                    ->orderByDesc('last_activity')
                    ->get();

                if ($sessions->count() <= $maxSessions) {
                    return;
                }

                $sessionsToRemove = $sessions->slice($maxSessions);

                foreach ($sessionsToRemove as $session) {
                    DB::table('sessions')->where('id', $session->session_id)->delete();
                    $session->delete();
                }

                $this->logServiceInfo('enforceSessionLimit', 'Session limit enforced', [
                    'user_id' => $user->id,
                    'removed' => $sessionsToRemove->count(),
                ]);
            },
            operation: 'enforceSessionLimit',
            context: ['user_id' => $user->id, 'max_sessions' => $maxSessions]
        );
    }

    public function cleanupExpiredSessions(): int
    {
        return $this->handleServiceOperation(
            callback: function () {
                $lifetime = config('session.lifetime', 120);
                $cutoff = now()->subMinutes($lifetime);

                $count = UserSession::where('last_activity', '<', $cutoff)->delete();

                $this->logServiceInfo('cleanupExpiredSessions', 'Expired sessions cleaned up', ['count' => $count]);

                return $count;
            },
            operation: 'cleanupExpiredSessions',
            context: [],
            defaultValue: 0
        );
    }

    protected function getDeviceType(Agent $agent): string
    {
        if ($agent->isTablet()) {
            return 'tablet';
        }
        if ($agent->isMobile()) {
            return 'mobile';
        }
        if ($agent->isDesktop()) {
            return 'desktop';
        }

        return 'unknown';
    }
}
