<?php

declare(strict_types=1);

namespace Kikmedia\GroundingPageBundle\Review;

use Doctrine\DBAL\Connection;

class EntryContentLoader
{
    public function __construct(private readonly Connection $connection)
    {
    }

    public function load(int $entryId): array
    {
        $entry = $this->connection->fetchAssociative('SELECT * FROM tl_grounding_page WHERE id = ?', [$entryId]);

        if (false === $entry) {
            throw new \InvalidArgumentException(sprintf('Grounding page entry %d does not exist.', $entryId));
        }

        return ['entry' => $entry, 'elements' => $this->children('tl_grounding_page', $entryId)];
    }

    private function children(string $table, int $parentId): array
    {
        $rows = $this->connection->fetchAllAssociative(
            'SELECT * FROM tl_content WHERE ptable = ? AND pid = ? ORDER BY sorting, id',
            [$table, $parentId]
        );

        foreach ($rows as $index => $row) {
            $rows[$index]['children'] = $this->children('tl_content', (int) $row['id']);
        }

        return $rows;
    }
}
