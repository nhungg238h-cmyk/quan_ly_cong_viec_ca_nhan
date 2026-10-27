<?php

namespace App\Data\User\Services;

use App\Domain\User\Services\PasswordHasherInterface;
use Illuminate\Support\Facades\Hash;

final class LaravelPasswordHasher implements PasswordHasherInterface
{
    public function hash(string $plainPassword): string
    {
        return Hash::make($plainPassword);
    }

    public function check(string $plainPassword, string $hash): bool
    {
        return Hash::check($plainPassword, $hash);
    }
}
