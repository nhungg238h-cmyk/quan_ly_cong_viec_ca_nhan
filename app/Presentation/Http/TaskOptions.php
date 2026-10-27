<?php

namespace App\Presentation\Http;

/**
 * Nhãn tiếng Việt + tên icon SVG (resources/views/partials/icons.blade.php) cho danh mục và mức ưu tiên - dùng chung cho Blade và JS.
 * Khóa phải khớp với TaskCategory / TaskPriority của Domain (có test kiểm tra).
 */
final class TaskOptions
{
    public const CATEGORIES = [
        'work' => ['label' => 'Công việc', 'icon' => 'briefcase'],
        'personal' => ['label' => 'Cá nhân', 'icon' => 'home'],
        'study' => ['label' => 'Học tập', 'icon' => 'book'],
        'shopping' => ['label' => 'Mua sắm', 'icon' => 'cart'],
        'health' => ['label' => 'Sức khỏe', 'icon' => 'heart'],
        'other' => ['label' => 'Khác', 'icon' => 'tag'],
    ];

    public const PRIORITIES = [
        'high' => ['label' => 'Cao'],
        'medium' => ['label' => 'Trung bình'],
        'low' => ['label' => 'Thấp'],
    ];

    public static function toArray(): array
    {
        return ['categories' => self::CATEGORIES, 'priorities' => self::PRIORITIES];
    }
}
