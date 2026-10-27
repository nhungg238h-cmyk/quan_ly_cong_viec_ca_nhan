<?php

namespace Tests\Support;

use App\Domain\User\Services\PasswordHasherInterface;

final class FakePasswordHasher implements PasswordHasherInterface
{
    public function hash(string $plainPassword): string
    {
        return 'hashed:'.$plainPassword;
    }

    public function check(string $plainPassword, string $hash): bool
    {
        return $hash === 'hashed:'.$plainPassword;
    }
}
