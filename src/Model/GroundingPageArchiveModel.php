<?php

declare(strict_types=1);

namespace Grounding\PageBundle\Model;

use Contao\Model;

/**
 * Reads and writes Grounding-Page archives (tl_grounding_page_archive).
 */
class GroundingPageArchiveModel extends Model
{
    protected static $strTable = 'tl_grounding_page_archive';

    /**
     * Find all archives that are not marked as "hide from menu" (if used).
     */
    public static function findAllSorted(): \Contao\Model\Collection|null
    {
        $t = static::$strTable;

        return static::findBy([], null, ['order' => "$t.title"]);
    }

    /**
     * Find published archives by a list of IDs.
     */
    public static function findMultipleByIds(array $ids): \Contao\Model\Collection|null
    {
        if (empty($ids)) {
            return null;
        }

        $t = static::$strTable;

        return static::findBy(
            ["$t.id IN (" . implode(',', array_map('\intval', $ids)) . ')'],
            null,
            ['order' => \Database::getInstance()->findInSet("$t.id", $ids)]
        );
    }
}
