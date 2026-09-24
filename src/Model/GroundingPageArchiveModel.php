<?php

declare(strict_types=1);

namespace Grounding\PageBundle\Model;

use Contao\Model;

class GroundingPageArchiveModel extends Model
{
    protected static $strTable = 'tl_grounding_page_archive';

    public static function findAllSorted(): \Contao\Model\Collection|null
    {
        $t = static::$strTable;

        return static::findBy([], null, ['order' => "$t.title"]);
    }

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
