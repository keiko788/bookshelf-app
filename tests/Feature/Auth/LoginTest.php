<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_正しい認証情報でログインできる(): void
    {
        $user = User::factory()->create([
            'password' => bcrypt('password123'),
        ]);

        $response = $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'password123',
        ]);

        $response->assertRedirect(route('books.index'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_間違ったパスワードではログインできない(): void
    {
        $user = User::factory()->create([
            'password' => bcrypt('password123'),
        ]);

        $response = $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_存在しないメールアドレスではログインできない(): void
    {
        $response = $this->post(route('login'), [
            'email' => 'nonexistent@example.com',
            'password' => 'password123',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_メールアドレスが未入力の場合、バリデーションメッセージが表示される(): void
    {
        $response = $this->post(route('login'), [
            'email' => '',
            'password' => 'password123',
        ]);

        $response->assertSessionHasErrors([
            'email' => 'メールアドレスを入力してください。',
        ]);
        $this->assertGuest();
    }

    public function test_無効なメールアドレス形式の場合、バリデーションメッセージが表示される(): void
    {
        $response = $this->post(route('login'), [
            'email' => 'aaa',
            'password' => 'password123',
        ]);

        $response->assertSessionHasErrors([
            'email' => 'メールアドレス形式で入力してください。',
        ]);
        $this->assertGuest();
    }

    public function test_パスワードが未入力の場合、バリデーションメッセージが表示される(): void
    {
        $response = $this->post(route('login'), [
            'email' => 'test@example.com',
            'password' => '',
        ]);

        $response->assertSessionHasErrors([
            'password' => 'パスワードを入力してください。',
        ]);
        $this->assertGuest();
    }
}
