<?php

declare(strict_types=1);

namespace Kikmedia\GroundingPageBundle\Routing;

use Contao\PageModel;
use Kikmedia\GroundingPageBundle\Model\GroundingPageArchiveModel;
use Kikmedia\GroundingPageBundle\Model\GroundingPageModel;

final class EntryUrl
{
    public static function readerPage(GroundingPageModel $entry): PageModel|null
    {
        $archive = GroundingPageArchiveModel::findById($entry->pid);

        if (null === $archive || !$archive->jumpTo) {
            return null;
        }

        return PageModel::findPublishedById($archive->jumpTo);
    }

    public static function path(GroundingPageModel $entry): string|null
    {
        return self::readerPage($entry)?->getFrontendUrl(self::parameters($entry));
    }

    public static function parameters(GroundingPageModel $entry): string
    {
        return '/' . ($entry->alias ?: $entry->id);
    }
}
