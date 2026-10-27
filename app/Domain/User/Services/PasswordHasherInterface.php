<?php

namespace App\Domain\User\Services;

interface PasswordHasherInterface
{
    public function hash(string $plainPassword): string;

    public function check(string $plainPassword, string $hash): bool;
}
