<?php

namespace Tests\Feature;

use App\Data\User\Models\UserModel;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get('/')->assertRedirect('/login');
        $this->get('/history')->assertRedirect('/login');
        $this->getJson('/api/tasks')->assertUnauthorized();
        $this->postJson('/api/tasks', ['title' => 'x'])->assertUnauthorized();
    }

    public function test_login_page_shows_demo_accounts(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertSee('Đăng nhập')
            ->assertSee('Tài khoản dùng thử')
            ->assertSee('demo@example.com');
    }

    public function test_user_can_register_and_is_logged_in(): void
    {
        $this->post('/register', [
            'name' => 'Nguyễn Văn A',
            'email' => 'An@Example.com',
            'password' => 'matkhau123',
            'password_confirmation' => 'matkhau123',
        ])->assertRedirect('/');

        $user = UserModel::where('email', 'an@example.com')->firstOrFail();
        $this->assertAuthenticatedAs($user);
        $this->assertTrue(Hash::check('matkhau123', $user->password));

        $this->get('/')->assertSee('Chào mừng Nguyễn Văn A');
    }

    public function test_register_validates_input(): void
    {
        $this->post('/register', [
            'name' => '',
            'email' => 'khong-phai-email',
            'password' => '123',
            'password_confirmation' => '456',
        ])->assertSessionHasErrors(['name', 'email', 'password']);

        $this->assertGuest();
    }

    public function test_register_rejects_existing_email(): void
    {
        UserModel::factory()->create(['email' => 'an@example.com']);

        $this->from('/register')->post('/register', [
            'name' => 'A',
            'email' => 'AN@example.com',
            'password' => 'matkhau123',
            'password_confirmation' => 'matkhau123',
        ])->assertRedirect('/register')->assertSessionHasErrors(['email' => 'Email này đã được đăng ký.']);

        $this->assertGuest();
    }

    public function test_user_can_login_with_any_email_case_and_logout(): void
    {
        $user = UserModel::factory()->create(['email' => 'an@example.com', 'password' => 'matkhau123']);

        $this->post('/login', ['email' => ' AN@example.com ', 'password' => 'matkhau123'])->assertRedirect('/');
        $this->assertAuthenticatedAs($user);

        $this->post('/logout')->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_login_fails_with_wrong_password(): void
    {
        UserModel::factory()->create(['email' => 'an@example.com', 'password' => 'matkhau123']);

        $this->from('/login')->post('/login', ['email' => 'an@example.com', 'password' => 'sai-mat-khau'])
            ->assertRedirect('/login')
            ->assertSessionHasErrors(['email' => 'Email hoặc mật khẩu không đúng.']);

        $this->assertGuest();
    }

    public function test_logged_in_user_is_sent_away_from_login_page(): void
    {
        $this->actingAs(UserModel::factory()->create());

        $this->get('/login')->assertRedirect('/');
        $this->get('/register')->assertRedirect('/');
    }

    public function test_seeded_demo_accounts_can_log_in_and_have_sample_tasks(): void
    {
        $this->seed(DatabaseSeeder::class);

        foreach (config('demo.accounts') as $account) {
            $this->post('/login', ['email' => $account['email'], 'password' => $account['password']])->assertRedirect('/');
            $this->assertAuthenticated();

            $this->getJson('/api/tasks')->assertOk()->assertJsonCount(count($account['tasks']), 'data');

            $this->post('/logout');
        }
    }

    public function test_login_is_rate_limited(): void
    {
        for ($i = 0; $i < 10; $i++) {
            $this->post('/login', ['email' => 'x@example.com', 'password' => 'sai']);
        }

        $this->post('/login', ['email' => 'x@example.com', 'password' => 'sai'])->assertTooManyRequests();
    }
}
