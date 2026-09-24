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
            ['grounding_page.listener', 'checkArchivePermission'],
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
        'global_operations' => [
            'all' => [
                'href'            => 'act=select',
                'class'           => 'header_edit_all',
                'attributes'      => 'onclick="Backend.getScrollOffset()"',
            ],
        ],
        'operations' => [
            'edit' => [
                'href' => 'table=tl_grounding_page',
                'icon' => 'edit.svg',
            ],
            'editheader' => [
                'href' => 'act=edit',
                'icon' => 'header.svg',
            ],
            'copy' => [
                'href' => 'act=copy',
                'icon' => 'copy.svg',
            ],
            'delete' => [
                'href'       => 'act=delete',
                'icon'       => 'delete.svg',
                'attributes' => 'onclick="if(!confirm(\'' . '%s' . '\'))return false;Backend.getScrollOffset()"',
            ],
            'show' => [
                'href' => 'act=show',
                'icon' => 'show.svg',
            ],
        ],
    ],
    'palettes' => [
        'default' => '{title_legend},title;{jumpTo_legend},jumpTo;{expert_legend:hide},cssClass',
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
        'cssClass' => [
            'inputType' => 'text',
            'exclude'   => true,
            'eval'      => ['tl_class' => 'w50'],
            'sql'       => ['type' => 'string', 'length' => 255, 'default' => ''],
        ],
    ],
];
