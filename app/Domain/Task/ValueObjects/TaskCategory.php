<?php

namespace App\Domain\Task\ValueObjects;

enum TaskCategory: string
{
    case Work = 'work';
    case Personal = 'personal';
    case Study = 'study';
    case Shopping = 'shopping';
    case Health = 'health';
    case Other = 'other';
}
