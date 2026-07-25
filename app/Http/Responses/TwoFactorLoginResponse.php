<?php

namespace App\Http\Responses;

use App\Concerns\RedirectsUsersByRole;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Laravel\Fortify\Contracts\TwoFactorLoginResponse as TwoFactorLoginResponseContract;

/**
 * Fortify resolves a separate contract after a two-factor challenge, so the
 * role-aware redirect has to be bound here too or admins with 2FA enabled would
 * fall back to the default home path.
 */
final class TwoFactorLoginResponse implements TwoFactorLoginResponseContract
{
    use RedirectsUsersByRole;

    /**
     * @param  Request  $request
     */
    public function toResponse($request): JsonResponse|RedirectResponse
    {
        if ($request->wantsJson()) {
            return new JsonResponse('', 204);
        }

        return redirect()->intended($this->homeFor($request->user()));
    }
}
