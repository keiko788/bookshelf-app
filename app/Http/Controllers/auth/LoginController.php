<?php

namespace App\Http\Controllers\auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    // ログイン画面を表示する
    public function create()
    {
        return view('auth.login');
    }

    // ログイン認証を行い、認証成功時は書籍一覧画面へ遷移する
    public function store(LoginRequest $request)
    {
        $credentials = $request->only('email', 'password');

        if (! Auth::attempt($credentials)) {
            throw ValidationException::withMessages([
                'email' => 'ログイン情報が登録されていません',
            ]);
        }

        // ログイン成功後にセッションIDを再生成し、セッション固定攻撃を防ぐ
        $request->session()->regenerate();

        return redirect()->intended('/books');
    }
}
