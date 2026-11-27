<?php

declare(strict_types=1);

namespace Kikmedia\GroundingPageBundle\Model;

use Contao\Database;
use Contao\Date;
use Contao\Model;
use Contao\Model\Collection;

class GroundingPageModel extends Model
{
    protected static $strTable = 'tl_grounding_page';

    public static function findPublishedByIdOrAlias(string $idOrAlias, array $archiveIds): self|null
    {
        if (empty($archiveIds)) {
            return null;
        }

        $t = static::$strTable;
        $columns = [
            preg_match('/^[1-9]\d*$/', $idOrAlias) ? "$t.id=?" : "$t.alias=?",
            self::inArchives($archiveIds),
        ];

        return static::findOneBy(self::visibleOnly($columns), [$idOrAlias]);
    }

    public static function findPublishedByPids(array $archiveIds, array $options = []): Collection|null
    {
        if (empty($archiveIds)) {
            return null;
        }

        $options['order'] ??= static::$strTable . '.sorting';

        return static::findBy(self::visibleOnly([self::inArchives($archiveIds)]), null, $options);
    }

    public static function findAllPublished(): Collection|null
    {
        return static::findBy([self::publicCondition()], null, ['order' => static::$strTable . '.sorting']);
    }

    public static function findPublishedMultipleByIds(array $ids): Collection|null
    {
        if (empty($ids)) {
            return null;
        }

        $t = static::$strTable;
        $columns = ["$t.id IN (" . implode(',', array_map('\intval', $ids)) . ')'];

        return static::findBy(
            self::visibleOnly($columns),
            null,
            ['order' => Database::getInstance()->findInSet("$t.id", $ids)]
        );
    }

    private static function inArchives(array $archiveIds): string
    {
        return static::$strTable . '.pid IN (' . implode(',', array_map('\intval', $archiveIds)) . ')';
    }

    private static function visibleOnly(array $columns): array
    {
        if (!static::isPreviewMode([])) {
            $columns[] = self::publicCondition();
        }

        return $columns;
    }

    private static function publicCondition(): string
    {
        $t = static::$strTable;
        $time = Date::floorToMinute();

        return "$t.published=1 AND ($t.start='' OR $t.start<=$time) AND ($t.stop='' OR $t.stop>$time)";
    }
}
