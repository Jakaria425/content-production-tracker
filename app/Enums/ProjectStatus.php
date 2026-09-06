<?php

namespace App\Enums;

enum ProjectStatus: string
{
    case Draft = 'Draft';
    case InProgress = 'In progress';
    case Review = 'Review';
    case Complete = 'Complete';

    public function label(): string
    {
        return ucfirst($this->value);
    }

    /**
     * Get all valid status values.
     *
     * @return array<string>
     */
    public static function values(): array
    {
        return array_map(fn (self $status) => $status->value, self::cases());
    }
}
