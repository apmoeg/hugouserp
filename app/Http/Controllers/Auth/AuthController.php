<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\AuthLoginRequest;
use App\Models\User;
use App\Services\Contracts\AuthServiceInterface as AuthService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function __construct(protected AuthService $auth) {}

    public function login(AuthLoginRequest $request)
    {
        $user = User::query()->where('email', $request->input('email'))->first();
        if (! $user || ! Hash::check($request->input('password'), $user->password)) {
            return $this->fail(__('Invalid credentials'), 422);
        }

        if (method_exists($user, 'is_active') && ! $user->is_active) {
            return $this->fail(__('User disabled'), 403);
        }

        $abilities = $request->input('abilities', ['*']);
        $token = $this->auth->issueToken($user, $abilities);

        return $this->ok([
            'token' => $token->plainTextToken,
            'user' => $user,
        ], __('Logged in successfully'));
    }

    public function me(Request $request)
    {
        return $this->ok(['user' => $request->user()]);
    }

    public function logout(Request $request)
    {
        $user = $request->user();
        if (method_exists($user, 'currentAccessToken')) {
            $user->currentAccessToken()?->delete();
        } else {
            $this->auth->revokeAllTokens($user);
        }

        return $this->ok(null, __('Logged out'));
    }

    public function impersonate(Request $request)
    {
        $this->validate($request, [
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'abilities' => ['sometimes', 'array'],
        ]);

        $token = $this->auth->enableImpersonation((int) $request->input('user_id'), $request->input('abilities', ['*']));

        return $this->ok([
            'token' => $token?->plainTextToken,
            'impersonating' => true,
        ], __('Impersonation token issued'));
    }
}
