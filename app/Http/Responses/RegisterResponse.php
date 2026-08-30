<?php

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;
use Laravel\Fortify\Contracts\RegisterResponse as RegisterResponseContract;
use Laravel\Fortify\Fortify;

class RegisterResponse implements RegisterResponseContract
{
    public function toResponse($request)
    {
        return $request->wantsJson()
            ? new JsonResponse('', 201)
            : redirect()
                ->intended(Fortify::redirects('register'))
                ->with('success', '会員登録をしました');
    }
}
