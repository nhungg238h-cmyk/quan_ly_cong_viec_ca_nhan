<?php

namespace App\Domain\Task\ValueObjects;

enum TaskPriority: string
{
    case Low = 'low';
    case Medium = 'medium';
    case High = 'high';
}
