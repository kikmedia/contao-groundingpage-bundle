<?php

declare(strict_types=1);

namespace Grounding\PageBundle\Model;

use Contao\Model;
use Contao\Model\Collection;

/**
 * Reads and writes Grounding-Page entries (tl_grounding_page).
 */
class GroundingPageModel extends Model
{
    protected static $strTable = 'tl_grounding_page';

    /**
     * Find a published entry by its ID or alias.
     */
    public static function findPublishedByIdOrAlias(string $idOrAlias): self|null
    {
        $t = static::$strTable;
        $arrColumns = is_numeric($idOrAlias) ? ["$t.id=?"] : ["$t.alias=?"];

        if (BE_USER_LOGGED_IN !== true) {
            $arrColumns[] = "$t.published='1'";
        }

        return static::findOneBy($arrColumns, $idOrAlias);
    }

    /**
     * Find all published entries of one or more archives.
     */
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

    /**
     * Find all published entries (used by the sitemap listener).
     */
    public static function findAllPublished(): Collection|null
    {
        $t = static::$strTable;

        return static::findBy(["$t.published='1'"], null, ['order' => "$t.sorting"]);
    }

    /**
     * Find multiple (published) entries by their IDs, in the given order –
     * used to resolve "related pages".
     */
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

    /**
     * Count published entries of one or more archives.
     */
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
