<?php

declare(strict_types=1);

use Kikmedia\GroundingPageBundle\Controller\Backend\ReviewController;
use Kikmedia\GroundingPageBundle\Model\GroundingPageArchiveModel;
use Kikmedia\GroundingPageBundle\Model\GroundingPageModel;

$GLOBALS['BE_MOD']['content']['grounding_page'] = [
    'tables'  => ['tl_grounding_page_archive', 'tl_grounding_page', 'tl_content'],
    'reviews' => [ReviewController::class, 'run'],
];

$GLOBALS['TL_MODELS']['tl_grounding_page_archive'] = GroundingPageArchiveModel::class;
$GLOBALS['TL_MODELS']['tl_grounding_page'] = GroundingPageModel::class;

$GLOBALS['TL_PERMISSIONS'][] = 'groundingPages';
$GLOBALS['TL_PERMISSIONS'][] = 'groundingPagep';
