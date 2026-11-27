<?php

declare(strict_types=1);

use Contao\CoreBundle\DataContainer\PaletteManipulator;

PaletteManipulator::create()
    ->addLegend('grounding_page_legend', 'amg_legend', PaletteManipulator::POSITION_BEFORE)
    ->addField(['groundingPages', 'groundingPagep'], 'grounding_page_legend', PaletteManipulator::POSITION_APPEND)
    ->applyToPalette('default', 'tl_user_group');

$GLOBALS['TL_DCA']['tl_user_group']['fields']['groundingPages'] = [
    'inputType'  => 'checkbox',
    'foreignKey' => 'tl_grounding_page_archive.title',
    'eval'       => ['multiple' => true],
    'sql'        => ['type' => 'blob', 'notnull' => false],
];

$GLOBALS['TL_DCA']['tl_user_group']['fields']['groundingPagep'] = [
    'inputType' => 'checkbox',
    'options'   => ['create', 'delete', 'review'],
    'reference' => &$GLOBALS['TL_LANG']['tl_user_group']['groundingPagepRef'],
    'eval'      => ['multiple' => true],
    'sql'       => ['type' => 'blob', 'notnull' => false],
];
