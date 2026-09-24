<?php

declare(strict_types=1);

use Kikmedia\GroundingPageBundle\Model\GroundingPageArchiveModel;
use Kikmedia\GroundingPageBundle\Model\GroundingPageModel;

$GLOBALS['BE_MOD']['content']['grounding_page'] = [
    'tables' => ['tl_grounding_page_archive', 'tl_grounding_page', 'tl_content'],
];

$GLOBALS['TL_MODELS']['tl_grounding_page_archive'] = GroundingPageArchiveModel::class;
$GLOBALS['TL_MODELS']['tl_grounding_page'] = GroundingPageModel::class;
