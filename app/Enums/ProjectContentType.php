<?php

namespace App\Enums;

enum ProjectContentType: string
{
    case Ebook = 'Ebook';
    case BlogPost = 'Blog post';
    case Newsletter = 'Newsletter';
    case SocialPost = 'Social post';

    public function label(): string
    {
        return ucfirst($this->value);
    }

    /**
     * Get all valid content type values.
     *
     * @return array<string>
     */
    public static function values(): array
    {
        return array_map(fn (self $type) => $type->value, self::cases());
    }
}
