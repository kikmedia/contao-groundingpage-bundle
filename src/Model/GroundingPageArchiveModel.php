<?php

declare(strict_types=1);

namespace Kikmedia\GroundingPageBundle\Model;

use Contao\Model;

class GroundingPageArchiveModel extends Model
{
    protected static $strTable = 'tl_grounding_page_archive';

    public static function findAllSorted(): \Contao\Model\Collection|null
    {
        $t = static::$strTable;

        return static::findBy([], null, ['order' => "$t.title"]);
    }
}
