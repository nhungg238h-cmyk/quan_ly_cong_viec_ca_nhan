<?php

namespace Tests\Feature;

use App\Data\User\Models\UserModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    private UserModel $user;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        $this->user = UserModel::factory()->create(['name' => 'Nguyễn An', 'password' => 'matkhau123']);
        $this->actingAs($this->user);
    }

    public function test_profile_page_shows_user_info_and_stats(): void
    {
        $this->postJson('/api/tasks', ['title' => 'Việc 1']);

        $this->get('/profile')
            ->assertOk()
            ->assertSee('Nguyễn An')
            ->assertSee($this->user->email)
            ->assertSee('Đổi mật khẩu')
            ->assertViewHas('stats', fn (array $stats) => $stats[0]['value'] === 1);
    }

    public function test_can_rename_and_upload_avatar(): void
    {
        $this->post('/profile', [
            'name' => 'An Nguyễn',
            'avatar' => UploadedFile::fake()->image('me.png', 200, 200),
        ])->assertRedirect('/profile');

        $user = $this->user->fresh();
        $this->assertSame('An Nguyễn', $user->name);
        Storage::disk('local')->assertExists($user->avatar_path);

        $this->get('/')->assertSee('/profile/avatar?v=', false);
        $this->get('/profile/avatar')->assertOk()->assertHeader('Content-Type', 'image/png');
    }

    public function test_can_remove_avatar(): void
    {
        $this->post('/profile', ['name' => 'An', 'avatar' => UploadedFile::fake()->image('me.png')]);
        $path = $this->user->fresh()->avatar_path;

        $this->post('/profile', ['name' => 'An', 'remove_avatar' => '1'])->assertRedirect('/profile');

        $this->assertNull($this->user->fresh()->avatar_path);
        Storage::disk('local')->assertMissing($path);
        $this->get('/profile/avatar')->assertNotFound();
    }

    public function test_profile_validation(): void
    {
        $this->post('/profile', [
            'name' => '',
            'avatar' => UploadedFile::fake()->create('cv.pdf', 10, 'application/pdf'),
        ])->assertSessionHasErrors(['name', 'avatar']);
    }

    public function test_can_change_password(): void
    {
        $this->put('/profile/password', [
            'current_password' => 'matkhau123',
            'password' => 'matkhaumoi1',
            'password_confirmation' => 'matkhaumoi1',
        ])->assertRedirect('/profile')->assertSessionHas('status', 'Đã đổi mật khẩu.');

        $this->assertTrue(Hash::check('matkhaumoi1', $this->user->fresh()->password));
    }

    public function test_change_password_rejects_wrong_current_password(): void
    {
        $this->from('/profile')->put('/profile/password', [
            'current_password' => 'sai',
            'password' => 'matkhaumoi1',
            'password_confirmation' => 'matkhaumoi1',
        ])->assertRedirect('/profile')->assertSessionHasErrorsIn('password', ['current_password']);

        $this->assertTrue(Hash::check('matkhau123', $this->user->fresh()->password));
    }

    public function test_avatar_is_private(): void
    {
        $this->post('/profile', ['name' => 'An', 'avatar' => UploadedFile::fake()->image('me.png')]);

        $this->actingAs(UserModel::factory()->create());
        $this->get('/profile/avatar')->assertNotFound();

        auth()->logout();
        $this->get('/profile/avatar')->assertRedirect('/login');
        $this->get('/profile')->assertRedirect('/login');
    }
}
