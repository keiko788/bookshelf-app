<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegisterTest extends TestCase
{
    use RefreshDatabase;

    private function validData(array $overrides = []): array
    {
        return array_merge([
            'name' => 'テストユーザー',
            'email' => 'name@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ], $overrides);
    }

    public function test_正しい情報を入力した場合_会員登録ができる(): void
    {
        $response = $this->post(route('register'),
            $this->validData());

        $response->assertRedirect(route('books.index'));
        $this->assertDatabaseHas('users', [
            'name' => 'テストユーザー',
            'email' => 'name@example.com',
        ]);
        $this->assertAuthenticated();
    }

    public function test_ユーザー名が未入力の場合_バリデーションメッセージが表示される(): void
    {
        $response = $this->post(route('register'),
            $this->validData([
                'name' => '',
            ]));

        $response->assertSessionHasErrors([
            'name' => 'ユーザー名を入力してください。',
        ]);
        $this->assertGuest();
    }

    public function test_ユーザー名を254文字で登録できる(): void
    {
        $name = str_repeat('あ', 254);

        $response = $this->post(route('register'),
            $this->validData([
                'name' => $name,
            ]));

        $response->assertRedirect(route('books.index'));

        $this->assertDatabaseHas('users', [
            'name' => $name,
            'email' => 'name@example.com',
        ]);

        $this->assertAuthenticated();
    }

    public function test_ユーザー名を255文字で登録できる(): void
    {
        $name = str_repeat('あ', 255);

        $response = $this->post(route('register'),
            $this->validData([
                'name' => $name,
            ]));

        $response->assertRedirect(route('books.index'));

        $this->assertDatabaseHas('users', [
            'name' => $name,
            'email' => 'name@example.com',
        ]);

        $this->assertAuthenticated();
    }

    public function test_ユーザー名が256文字の場合_バリデーションメッセージが表示される(): void
    {
        $name = str_repeat('あ', 256);

        $response = $this->post(
            route('register'),
            $this->validData([
                'name' => $name,
            ])
        );

        $response->assertSessionHasErrors([
            'name' => 'ユーザー名は255文字以内で入力してください。',
        ]);
        $this->assertGuest();
    }

    public function test_メールアドレスが未入力の場合_バリデーションメッセージが表示される(): void
    {
        $response = $this->post(
            route('register'),
            $this->validData([
                'email' => '',
            ])
        );

        $response->assertSessionHasErrors([
            'email' => 'メールアドレスを入力してください。',
        ]);
        $this->assertGuest();
    }

    public function test_無効なメールアドレスを入力した場合_バリデーションメッセージが表示される(): void
    {
        $response = $this->post(
            route('register'),
            $this->validData([
                'email' => 'aaa',
            ])
        );

        $response->assertSessionHasErrors([
            'email' => 'メールアドレス形式で入力してください。',
        ]);
        $this->assertGuest();
    }

    public function test_登録済みのメールアドレスを入力した場合_バリデーションメッセージが表示される(): void
    {
        User::factory()->create([
            'email' => 'name@example.com',
        ]);

        $response = $this->post(
            route('register'),
            $this->validData()
        );

        $response->assertSessionHasErrors([
            'email' => 'このメールアドレスは既に登録されています。',
        ]);
        $this->assertGuest();
    }

    public function test_パスワードが未入力の場合_バリデーションメッセージが表示される(): void
    {
        $response = $this->post(
            route('register'),
            $this->validData([
                'password' => '',
            ])
        );

        $response->assertSessionHasErrors([
            'password' => 'パスワードを入力してください。',
        ]);
        $this->assertGuest();
    }

    public function test_パスワードが7文字の場合_バリデーションメッセージが表示される(): void
    {
        $response = $this->post(
            route('register'),
            $this->validData([
                'password' => '1234567',
            ])
        );

        $response->assertSessionHasErrors([
            'password' => 'パスワードは8文字以上で入力してください。',
        ]);
        $this->assertGuest();
    }

    public function test_パスワードが8文字の場合会員登録ができる(): void
    {
        $response = $this->post(
            route('register'),
            $this->validData([
                'password' => '12345678',
                'password_confirmation' => '12345678',
            ])
        );

        $response->assertRedirect(route('books.index'));

        $this->assertDatabaseHas('users', [
            'name' => 'テストユーザー',
            'email' => 'name@example.com',
        ]);

        $this->assertAuthenticated();
    }

    public function test_パスワードが9文字の場合会員登録ができる(): void
    {
        $response = $this->post(
            route('register'),
            $this->validData([
                'password' => '123456789',
                'password_confirmation' => '123456789',
            ])
        );

        $response->assertRedirect(route('books.index'));

        $this->assertDatabaseHas('users', [
            'name' => 'テストユーザー',
            'email' => 'name@example.com',
        ]);

        $this->assertAuthenticated();
    }

    public function test_パスワードと確認用パスワードが一致しない場合_バリデーションメッセージが表示される(): void
    {
        $response = $this->post(
            route('register'),
            $this->validData([
                'password_confirmation' => 'different-password',
            ])
        );

        $response->assertSessionHasErrors([
            'password' => 'パスワードが一致しません。',
        ]);
        $this->assertGuest();
    }

    public function test_確認用パスワードが未入力の場合_バリデーションメッセージが表示される(): void
    {
        $response = $this->post(
            route('register'),
            $this->validData([
                'password_confirmation' => '',
            ])
        );

        $response->assertSessionHasErrors([
            'password_confirmation' => '確認用パスワードを入力してください。',
        ]);
        $this->assertGuest();
    }
}
