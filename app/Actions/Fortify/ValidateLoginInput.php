<?php

namespace App\Actions\Fortify;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Laravel\Fortify\Fortify;

class ValidateLoginInput
{
    public function handle(Request $request, $next)
    {
        Validator::make($request->all(), [
            Fortify::username() => ['required', 'email'],
            'password' => ['required'],
        ], [
            Fortify::username().'.required' => 'メールアドレスを入力してください',
            Fortify::username().'.email' => 'メールアドレスはメール形式で入力してください',
            'password.required' => 'パスワードを入力してください',
        ])->validate();

        return $next($request);
    }
}
