<?php

declare(strict_types=1);

use Contao\DC_Table;

$GLOBALS['TL_DCA']['tl_grounding_page_review'] = [
    'config' => [
        'dataContainer'    => DC_Table::class,
        'ptable'           => 'tl_grounding_page',
        'closed'           => true,
        'notEditable'      => true,
        'notDeletable'     => true,
        'notCopyable'      => true,
        'notSortable'      => true,
        'doNotCopyRecords' => true,
        'sql' => [
            'keys' => [
                'id'  => 'primary',
                'pid' => 'index',
            ],
        ],
    ],
    'fields' => [
        'id' => [
            'sql' => ['type' => 'integer', 'unsigned' => true, 'autoincrement' => true],
        ],
        'pid' => [
            'sql' => ['type' => 'integer', 'unsigned' => true, 'default' => 0],
        ],
        'tstamp' => [
            'sql' => ['type' => 'integer', 'unsigned' => true, 'default' => 0],
        ],
        'kind' => [
            'sql' => ['type' => 'string', 'length' => 8, 'default' => 'review'],
        ],
        'reference' => [
            'sql' => ['type' => 'integer', 'unsigned' => true, 'default' => 0],
        ],
        'reviewDate' => [
            'sql' => ['type' => 'string', 'length' => 10, 'default' => ''],
        ],
        'reviewerType' => [
            'sql' => ['type' => 'string', 'length' => 16, 'default' => ''],
        ],
        'reviewerName' => [
            'sql' => ['type' => 'string', 'length' => 255, 'default' => ''],
        ],
        'approved' => [
            'sql' => ['type' => 'boolean', 'default' => false],
        ],
        'checks' => [
            'sql' => ['type' => 'string', 'length' => 128, 'default' => ''],
        ],
        'note' => [
            'sql' => ['type' => 'text', 'notnull' => false],
        ],
        'userId' => [
            'sql' => ['type' => 'integer', 'unsigned' => true, 'default' => 0],
        ],
        'userName' => [
            'sql' => ['type' => 'string', 'length' => 255, 'default' => ''],
        ],
        'standardVersion' => [
            'sql' => ['type' => 'string', 'length' => 8, 'default' => ''],
        ],
        'fingerprint' => [
            'sql' => ['type' => 'string', 'length' => 80, 'default' => ''],
        ],
    ],
];
