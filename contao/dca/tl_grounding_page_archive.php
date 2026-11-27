<?php

declare(strict_types=1);

use Contao\DataContainer;
use Contao\DC_Table;

$GLOBALS['TL_DCA']['tl_grounding_page_archive'] = [
    'config' => [
        'dataContainer'    => DC_Table::class,
        'ctable'           => ['tl_grounding_page'],
        'enableVersioning' => true,
        'onload_callback' => [
            ['grounding_page.listener', 'restrictArchives'],
        ],
        'sql' => [
            'keys' => [
                'id' => 'primary',
            ],
        ],
    ],
    'list' => [
        'sorting' => [
            'mode'        => DataContainer::MODE_SORTED,
            'fields'      => ['title'],
            'flag'        => DataContainer::SORT_INITIAL_LETTER_ASC,
            'panelLayout' => 'filter;search,limit',
        ],
        'label' => [
            'fields' => ['title'],
            'format' => '%s',
        ],
        'operations' => [
            'edit',
            'children',
            'copy',
            'delete',
            'show',
        ],
    ],
    'palettes' => [
        'default' => '{title_legend},title;{jumpTo_legend},jumpTo;{review_legend},reviewerType,reviewerName;{expert_legend:hide},cssClass',
    ],
    'fields' => [
        'id' => [
            'sql' => ['type' => 'integer', 'unsigned' => true, 'autoincrement' => true],
        ],
        'tstamp' => [
            'sql' => ['type' => 'integer', 'unsigned' => true, 'default' => 0],
        ],
        'title' => [
            'inputType' => 'text',
            'exclude'   => true,
            'search'    => true,
            'sorting'   => true,
            'flag'      => DataContainer::SORT_INITIAL_LETTER_ASC,
            'eval'      => ['mandatory' => true, 'maxlength' => 255, 'tl_class' => 'w50'],
            'sql'       => ['type' => 'string', 'length' => 255, 'default' => ''],
        ],
        'jumpTo' => [
            'inputType'      => 'pageTree',
            'exclude'        => true,
            'eval'           => ['fieldType' => 'radio', 'tl_class' => 'clr'],
            'foreignKey'     => 'tl_page.title',
            'sql'            => ['type' => 'integer', 'unsigned' => true, 'default' => 0],
            'relation'       => ['type' => 'hasOne', 'load' => 'lazy'],
        ],
        'reviewerType' => [
            'inputType' => 'select',
            'exclude'   => true,
            'options'   => ['Person', 'Organization'],
            'reference' => &$GLOBALS['TL_LANG']['tl_grounding_page_archive']['reviewerTypes'],
            'eval'      => ['includeBlankOption' => true, 'tl_class' => 'w50'],
            'sql'       => ['type' => 'string', 'length' => 16, 'default' => ''],
        ],
        'reviewerName' => [
            'inputType' => 'text',
            'exclude'   => true,
            'eval'      => ['maxlength' => 255, 'tl_class' => 'w50'],
            'sql'       => ['type' => 'string', 'length' => 255, 'default' => ''],
        ],
        'cssClass' => [
            'inputType' => 'text',
            'exclude'   => true,
            'eval'      => ['tl_class' => 'w50'],
            'sql'       => ['type' => 'string', 'length' => 255, 'default' => ''],
        ],
    ],
];
