<?php

namespace Tests\Unit\Domain;

use App\Domain\User\Exceptions\InvalidUserException;
use App\Domain\User\UseCases\RegisterUserUseCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Tests\Support\FakePasswordHasher;
use Tests\Support\InMemoryUserRepository;

class RegisterUserUseCaseTest extends TestCase
{
    private InMemoryUserRepository $users;
    private RegisterUserUseCase $register;

    protected function setUp(): void
    {
        $this->users = new InMemoryUserRepository();
        $this->register = new RegisterUserUseCase($this->users, new FakePasswordHasher());
    }

    public function test_registers_user_with_normalized_email_and_hashed_password(): void
    {
        $user = $this->register->execute('  Nguyễn Văn A ', '  An@Example.COM ', 'matkhau123');

        $this->assertSame('Nguyễn Văn A', $user->name());
        $this->assertSame('an@example.com', $user->email());
        $this->assertFalse($user->hasAvatar());
        $this->assertSame('hashed:matkhau123', $this->users->passwordHash($user->id()));
    }

    public function test_duplicate_email_is_rejected_case_insensitively(): void
    {
        $this->register->execute('A', 'an@example.com', 'matkhau123');

        $this->expectException(InvalidUserException::class);
        $this->expectExceptionMessage('Email này đã được đăng ký.');

        $this->register->execute('B', 'AN@example.com', 'matkhau456');
    }

    public static function invalidInput(): array
    {
        return [
            'tên rỗng' => ['  ', 'a@example.com', 'matkhau123'],
            'tên quá dài' => [str_repeat('a', 101), 'a@example.com', 'matkhau123'],
            'email sai' => ['A', 'khong-phai-email', 'matkhau123'],
            'mật khẩu ngắn' => ['A', 'a@example.com', '1234567'],
        ];
    }

    #[DataProvider('invalidInput')]
    public function test_invalid_input_is_rejected(string $name, string $email, string $password): void
    {
        $this->expectException(InvalidUserException::class);

        $this->register->execute($name, $email, $password);
    }
}
