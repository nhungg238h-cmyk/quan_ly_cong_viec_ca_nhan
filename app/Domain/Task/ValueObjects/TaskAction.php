<?php

namespace App\Domain\Task\ValueObjects;

/**
 * Các loại thao tác được ghi vào lịch sử.
 */
enum TaskAction: string
{
    case Created = 'created';
    case Updated = 'updated';
    case Completed = 'completed';
    case Reopened = 'reopened';
    case ImageUpdated = 'image_updated';
    case ImageRemoved = 'image_removed';
    case Deleted = 'deleted';
}
