<?php

declare(strict_types=1);

namespace Grounding\PageBundle\Model;

use Contao\Model;
use Contao\Model\Collection;

class GroundingPageModel extends Model
{
    protected static $strTable = 'tl_grounding_page';

    public static function findPublishedByIdOrAlias(string $idOrAlias): self|null
    {
        $t = static::$strTable;
        $arrColumns = is_numeric($idOrAlias) ? ["$t.id=?"] : ["$t.alias=?"];

        if (BE_USER_LOGGED_IN !== true) {
            $arrColumns[] = "$t.published='1'";
        }

        return static::findOneBy($arrColumns, $idOrAlias);
    }

    public static function findPublishedByPids(array $archiveIds, array $options = []): Collection|null
    {
        if (empty($archiveIds)) {
            return null;
        }

        $t = static::$strTable;
        $arrColumns = ["$t.pid IN (" . implode(',', array_map('\intval', $archiveIds)) . ')'];

        if (BE_USER_LOGGED_IN !== true) {
            $arrColumns[] = "$t.published='1'";
        }

        if (!isset($options['order'])) {
            $options['order'] = "$t.sorting";
        }

        return static::findBy($arrColumns, null, $options);
    }

    public static function findAllPublished(): Collection|null
    {
        $t = static::$strTable;

        return static::findBy(["$t.published='1'"], null, ['order' => "$t.sorting"]);
    }

    public static function findMultipleByIds(array $ids, bool $publishedOnly = true): Collection|null
    {
        if (empty($ids)) {
            return null;
        }

        $t = static::$strTable;
        $arrColumns = ["$t.id IN (" . implode(',', array_map('\intval', $ids)) . ')'];

        if ($publishedOnly && BE_USER_LOGGED_IN !== true) {
            $arrColumns[] = "$t.published='1'";
        }

        return static::findBy(
            $arrColumns,
            null,
            ['order' => \Database::getInstance()->findInSet("$t.id", $ids)]
        );
    }

    public static function countPublishedByPids(array $archiveIds): int
    {
        if (empty($archiveIds)) {
            return 0;
        }

        $t = static::$strTable;
        $arrColumns = ["$t.pid IN (" . implode(',', array_map('\intval', $archiveIds)) . ')'];

        if (BE_USER_LOGGED_IN !== true) {
            $arrColumns[] = "$t.published='1'";
        }

        return static::countBy($arrColumns);
    }
}
