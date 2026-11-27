<?php

declare(strict_types=1);

namespace Kikmedia\GroundingPageBundle\Review;

use Doctrine\DBAL\Connection;

class ReviewRepository
{
    private const TABLE = 'tl_grounding_page_review';

    public function __construct(private readonly Connection $connection)
    {
    }

    public function findByEntry(int $entryId): array
    {
        return $this->connection->fetchAllAssociative('SELECT * FROM ' . self::TABLE . ' WHERE pid = ? ORDER BY id DESC', [$entryId]);
    }

    public function insert(array $record): int
    {
        $this->connection->insert(self::TABLE, $record);

        return (int) $this->connection->lastInsertId();
    }

    public function delete(int $id, int $entryId): void
    {
        $this->connection->delete(self::TABLE, ['id' => $id, 'pid' => $entryId]);
    }
}
