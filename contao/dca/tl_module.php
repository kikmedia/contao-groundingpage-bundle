<?php

declare(strict_types=1);

use Contao\CoreBundle\DataContainer\PaletteManipulator;

// Palette für die beiden neuen Frontend-Module erweitern
PaletteManipulator::create()
    ->addLegend('grounding_page_legend', 'config_legend', PaletteManipulator::POSITION_AFTER)
    ->addField('grounding_page_archives', 'grounding_page_legend', PaletteManipulator::POSITION_APPEND)
    ->applyToPalette('grounding_page_list', 'tl_module');

PaletteManipulator::create()
    ->addLegend('grounding_page_legend', 'config_legend', PaletteManipulator::POSITION_AFTER)
    ->addField('grounding_page_archives', 'grounding_page_legend', PaletteManipulator::POSITION_APPEND)
    ->applyToPalette('grounding_page_reader', 'tl_module');

$GLOBALS['TL_DCA']['tl_module']['fields']['grounding_page_archives'] = [
    'inputType'  => 'checkbox',
    'foreignKey' => 'tl_grounding_page_archive.title',
    'eval'       => ['mandatory' => true, 'multiple' => true],
    'sql'        => ['type' => 'blob', 'notnull' => false],
];
