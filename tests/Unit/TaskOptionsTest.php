<?php

namespace Tests\Unit;

use App\Domain\Task\ValueObjects\TaskCategory;
use App\Domain\Task\ValueObjects\TaskPriority;
use App\Presentation\Http\TaskOptions;
use PHPUnit\Framework\TestCase;

class TaskOptionsTest extends TestCase
{
    public function test_every_category_and_priority_has_a_label(): void
    {
        $values = fn (array $cases) => array_map(fn ($case) => $case->value, $cases);

        $this->assertEqualsCanonicalizing($values(TaskCategory::cases()), array_keys(TaskOptions::CATEGORIES));
        $this->assertEqualsCanonicalizing($values(TaskPriority::cases()), array_keys(TaskOptions::PRIORITIES));
    }
}
