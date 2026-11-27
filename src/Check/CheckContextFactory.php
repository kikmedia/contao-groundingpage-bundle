<?php

declare(strict_types=1);

namespace Kikmedia\GroundingPageBundle\Check;

use Contao\FilesModel;
use Contao\PageModel;
use Contao\StringUtil;
use Doctrine\DBAL\Connection;
use Kikmedia\GroundingPageBundle\Review\EntryContentLoader;
use Kikmedia\GroundingPageBundle\Review\ReviewService;

class CheckContextFactory
{
    public function __construct(
        private readonly Connection $connection,
        private readonly EntryContentLoader $loader,
        private readonly ReviewService $reviews,
    ) {
    }

    public function create(int $entryId): CheckContext
    {
        $content = $this->loader->load($entryId);
        $entry = $content['entry'];
        $archiveId = (int) $entry['pid'];
        $page = $this->page($archiveId);

        return new CheckContext(
            $entry,
            $content['elements'],
            $this->reviews->state($entryId),
            $page,
            $this->isImageAltMissing($entry, $page),
            $this->isArchiveListed($archiveId),
            time(),
        );
    }

    private function page(int $archiveId): array|null
    {
        $pageId = (int) $this->connection->fetchOne('SELECT jumpTo FROM tl_grounding_page_archive WHERE id = ?', [$archiveId]);
        $page = $pageId ? PageModel::findById($pageId)?->loadDetails() : null;

        if (null === $page) {
            return null;
        }

        $now = time();

        return [
            'published' => $page->published
                && ('' === (string) $page->start || (int) $page->start <= $now)
                && ('' === (string) $page->stop || (int) $page->stop > $now),
            'protected' => (bool) $page->protected,
            'noindex'   => str_starts_with((string) $page->robots, 'noindex'),
            'language'  => (string) $page->language,
        ];
    }

    private function isImageAltMissing(array $entry, array|null $page): bool
    {
        if (!$entry['addImage'] || !$entry['singleSRC'] || null === $page) {
            return false;
        }

        $language = str_replace('-', '_', $page['language']);
        $metadata = FilesModel::findByUuid($entry['singleSRC'])?->getMetadata($language, substr($language, 0, 2));

        return '' === trim((string) $metadata?->getAlt());
    }

    private function isArchiveListed(int $archiveId): bool
    {
        $selections = $this->connection->fetchFirstColumn("SELECT grounding_page_archives FROM tl_module WHERE type = 'grounding_page_list'");

        foreach ($selections as $selection) {
            if (\in_array($archiveId, array_map('\intval', StringUtil::deserialize($selection, true)), true)) {
                return true;
            }
        }

        return false;
    }
}
