<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Branch;
use App\Models\PosSession;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * VerifyPosOpen
 *
 * - Ensures an open POS session exists for the current branch/user before allowing
 *   checkout/collection/close-day sensitive operations.
 * - Looks for a PosSession with (branch_id, user_id, closed_at = null).
 *
 * Usage alias: 'verify.pos.open'
 */
class VerifyPosOpen
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        /** @var Branch|null $branch */
        $branch = $request->attributes->get('branch');

        if (! $user) {
            return $this->error('Unauthenticated.', 401);
        }
        if (! $branch instanceof Branch) {
            return $this->error('Branch context missing.', 422);
        }

        $ok = class_exists(PosSession::class)
            ? PosSession::query()
                ->where('branch_id', $branch->getKey())
                ->where('user_id', $user->getKey())
                ->whereNull('closed_at')
                ->exists()
            : true; // if schema not ready, don't block dev flows

        if (! $ok) {
            return $this->error('No open POS session. Please open a session first.', 409);
        }

        return $next($request);
    }

    protected function error(string $message, int $status): Response
    {
        return response()->json(['success' => false, 'message' => $message], $status);
    }
}
