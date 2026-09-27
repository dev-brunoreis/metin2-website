<?php

declare(strict_types=1);

namespace Metin2Website\Admin;

final class NewsFormTabs
{
    public static function normalize(string $tab, bool $isEdit, bool $allowCommentsTab): string
    {
        $allowed = ['data', 'seo'];

        if ($isEdit && $allowCommentsTab) {
            $allowed[] = 'comments';
        }

        return in_array($tab, $allowed, true) ? $tab : 'data';
    }
}
