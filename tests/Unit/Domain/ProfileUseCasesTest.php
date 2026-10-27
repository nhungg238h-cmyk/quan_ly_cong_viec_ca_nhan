<?php

namespace Tests\Unit\Domain;

use App\Domain\Shared\ValueObjects\ImageUpload;
use App\Domain\User\Exceptions\InvalidUserException;
use App\Domain\User\Exceptions\UserNotFoundException;
use App\Domain\User\UseCases\ChangePasswordUseCase;
use App\Domain\User\UseCases\GetUserUseCase;
use App\Domain\User\UseCases\UpdateProfileUseCase;
use PHPUnit\Framework\TestCase;
use Tests\Support\FakeImageStorage;
use Tests\Support\FakePasswordHasher;
use Tests\Support\InMemoryUserRepository;

class ProfileUseCasesTest extends TestCase
{
    private InMemoryUserRepository $users;
    private FakeImageStorage $images;
    private int $userId;

    protected function setUp(): void
    {
        $this->users = new InMemoryUserRepository();
        $this->images = new FakeImageStorage();
        $this->userId = $this->users->create('An', 'an@example.com', 'hashed:matkhau123')->id();
    }

    private function updateProfile(): UpdateProfileUseCase
    {
        return new UpdateProfileUseCase($this->users, $this->images);
    }

    private function avatar(): ImageUpload
    {
        return new ImageUpload('/tmp/avatar', 'png');
    }

    public function test_rename_and_set_avatar(): void
    {
        $user = $this->updateProfile()->execute($this->userId, '  Nguyễn An ', $this->avatar());

        $this->assertSame('Nguyễn An', $user->name());
        $this->assertStringStartsWith('avatars/', $user->avatarPath());
        $this->assertSame($user->avatarPath(), (new GetUserUseCase($this->users))->execute($this->userId)->avatarPath());
    }

    public function test_replacing_avatar_deletes_old_file(): void
    {
        $first = $this->updateProfile()->execute($this->userId, 'An', $this->avatar())->avatarPath();
        $second = $this->updateProfile()->execute($this->userId, 'An', $this->avatar())->avatarPath();

        $this->assertSame([$second => true], $this->images->stored);
        $this->assertNotSame($first, $second);
    }

    public function test_remove_avatar(): void
    {
        $this->updateProfile()->execute($this->userId, 'An', $this->avatar());

        $user = $this->updateProfile()->execute($this->userId, 'An', null, removeAvatar: true);

        $this->assertFalse($user->hasAvatar());
        $this->assertSame([], $this->images->stored);
    }

    public function test_blank_name_is_rejected_and_nothing_is_stored(): void
    {
        try {
            $this->updateProfile()->execute($this->userId, '  ', $this->avatar());
            $this->fail('Phải ném InvalidUserException');
        } catch (InvalidUserException) {
        }

        $this->assertSame('An', $this->users->find($this->userId)->name());
        $this->assertSame([], $this->images->stored);
    }

    public function test_change_password_requires_current_password(): void
    {
        $change = new ChangePasswordUseCase($this->users, new FakePasswordHasher());

        try {
            $change->execute($this->userId, 'sai-mat-khau', 'matkhaumoi1');
            $this->fail('Phải ném InvalidUserException');
        } catch (InvalidUserException $e) {
            $this->assertSame('Mật khẩu hiện tại không đúng.', $e->getMessage());
        }

        $change->execute($this->userId, 'matkhau123', 'matkhaumoi1');
        $this->assertSame('hashed:matkhaumoi1', $this->users->passwordHash($this->userId));
    }

    public function test_new_password_must_be_long_enough(): void
    {
        $this->expectException(InvalidUserException::class);

        (new ChangePasswordUseCase($this->users, new FakePasswordHasher()))->execute($this->userId, 'matkhau123', 'ngan');
    }

    public function test_missing_user_throws(): void
    {
        $this->expectException(UserNotFoundException::class);

        (new GetUserUseCase($this->users))->execute(999);
    }
}
