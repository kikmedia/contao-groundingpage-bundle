<?php

declare(strict_types=1);

use Contao\DataContainer;
use Contao\DC_Table;

/**
 * tl_grounding_page
 *
 * Ein Eintrag entspricht einer "Grounding Page" nach dem Grounding Page
 * Standard (https://groundingpage.com/spec/): eine faktenbasierte,
 * maschinenlesbar gespiegelte Referenzseite zu genau einer Entität.
 *
 * Pflichtblöcke des Standards und ihre Entsprechung hier:
 *   - H1 mit Entitätsname                → title
 *   - Eindeutige Lead-Definition          → definition, placement, distinction
 *   - Schema.org-Entitätstyp              → entityType / entityTypeCustom
 *   - Sichtbare, strukturierte Fakten     → coreFacts (Attribut/Wert-Paare)
 *   - Quellen & Aktualität                → sources, lastReviewed
 *   - Selbstständige H2-Abschnitte        → Content-Elemente (ctable tl_content)
 *   - Interne Verlinkung verwandter       → relatedPages
 *     Entitäten
 *   - JSON-LD-Spiegelung                  → wird zur Laufzeit im
 *                                            GroundingPageReaderController erzeugt
 */
$GLOBALS['TL_DCA']['tl_grounding_page'] = [
    'config' => [
        'dataContainer'    => DC_Table::class,
        'ptable'           => 'tl_grounding_page_archive',
        'ctable'           => ['tl_content'],
        'enableVersioning' => true,
        'markAsCopy'       => 'title',
        'onload_callback' => [
            ['grounding_page.listener', 'checkPermission'],
        ],
        'oncopy_callback' => [
            ['grounding_page.listener', 'resetOnCopy'],
        ],
        'sql' => [
            'keys' => [
                'id'    => 'primary',
                'alias' => 'index',
                'pid'   => 'index',
            ],
        ],
    ],
    'list' => [
        'sorting' => [
            'mode'                  => DataContainer::MODE_PARENT,
            'fields'                => ['sorting'],
            'headerFields'          => ['title', 'entityType', 'jumpTo'],
            'child_record_callback' => ['grounding_page.listener', 'listEntries'],
            'panelLayout'           => 'filter;search,limit',
        ],
        'label' => [
            'fields' => ['title'],
        ],
        'global_operations' => [
            'all' => [
                'href'       => 'act=select',
                'class'      => 'header_edit_all',
                'attributes' => 'onclick="Backend.getScrollOffset()"',
            ],
        ],
        'operations' => [
            'edit' => [
                'href' => 'act=edit',
                'icon' => 'edit.svg',
            ],
            'editheader' => [
                'href' => 'table=tl_grounding_page_archive&amp;act=edit',
                'icon' => 'header.svg',
            ],
            'copy' => [
                'href' => 'act=copy',
                'icon' => 'copy.svg',
            ],
            'cut' => [
                'href' => 'act=paste',
                'icon' => 'cut.svg',
            ],
            'delete' => [
                'href'       => 'act=delete',
                'icon'       => 'delete.svg',
                'attributes' => 'onclick="if(!confirm(\'' . '%s' . '\'))return false;Backend.getScrollOffset()"',
            ],
            'toggle' => [
                'icon'       => 'visible.svg',
                'attributes' => 'onclick="Backend.getScrollOffset();return AjaxRequest.toggleField(this,true)"',
                'reverse'    => true,
            ],
            'show' => [
                'href' => 'act=show',
                'icon' => 'show.svg',
            ],
        ],
    ],
    'palettes' => [
        '__selector__' => ['entityType'],
        'default' => '
            {title_legend},title,alias,author;
            {entity_legend},entityType;
            {lead_legend},definition,placement,distinction;
            {facts_legend},coreFacts;
            {sources_legend},sources;
            {related_legend},relatedPages;
            {media_legend:hide},addImage;
            {review_legend},lastReviewed;
            {publish_legend},published,start,stop
        ',
    ],
    'subpalettes' => [
        'entityType_other' => 'entityTypeCustom',
        'addImage'         => 'singleSRC,size',
    ],
    'fields' => [
        'id' => [
            'sql' => ['type' => 'integer', 'unsigned' => true, 'autoincrement' => true],
        ],
        'pid' => [
            'foreignKey' => 'tl_grounding_page_archive.title',
            'sql'        => ['type' => 'integer', 'unsigned' => true, 'default' => 0],
            'relation'   => ['type' => 'belongsTo', 'load' => 'lazy'],
        ],
        'tstamp' => [
            'sql' => ['type' => 'integer', 'unsigned' => true, 'default' => 0],
        ],
        'sorting' => [
            'sql' => ['type' => 'integer', 'unsigned' => true, 'default' => 0],
        ],

        'title' => [
            'inputType' => 'text',
            'exclude'   => true,
            'search'    => true,
            'sorting'   => true,
            'eval'      => ['mandatory' => true, 'maxlength' => 255, 'tl_class' => 'w50'],
            'sql'       => ['type' => 'string', 'length' => 255, 'default' => ''],
        ],
        'alias' => [
            'inputType' => 'text',
            'exclude'   => true,
            'search'    => true,
            'eval'      => ['rgxp' => 'alias', 'doNotCopy' => true, 'unique' => true, 'maxlength' => 255, 'tl_class' => 'w50'],
            'save_callback' => [
                ['grounding_page.listener', 'generateAlias'],
            ],
            'sql' => ['type' => 'string', 'length' => 255, 'default' => ''],
        ],
        'author' => [
            'inputType'  => 'select',
            'exclude'    => true,
            'filter'     => true,
            'sorting'    => true,
            'foreignKey' => 'tl_user.name',
            'eval'       => ['doNotCopy' => true, 'mandatory' => true, 'chosen' => true, 'includeBlankOption' => true, 'tl_class' => 'w50'],
            'sql'        => ['type' => 'integer', 'unsigned' => true, 'default' => 0],
            'relation'   => ['type' => 'hasOne', 'load' => 'lazy'],
        ],

        'entityType' => [
            'inputType' => 'select',
            'exclude'   => true,
            'filter'    => true,
            'options'   => ['Organization', 'Product', 'Person', 'Event', 'Place', 'Service', 'SoftwareApplication', 'CreativeWork', 'DefinedTerm', 'other'],
            'reference' => &$GLOBALS['TL_LANG']['tl_grounding_page']['entityTypes'],
            'eval'      => ['mandatory' => true, 'submitOnChange' => true, 'tl_class' => 'w50'],
            'sql'       => ['type' => 'string', 'length' => 64, 'default' => 'Organization'],
        ],
        'entityTypeCustom' => [
            'inputType' => 'text',
            'exclude'   => true,
            'eval'      => ['mandatory' => true, 'maxlength' => 64, 'tl_class' => 'w50'],
            'sql'       => ['type' => 'string', 'length' => 64, 'default' => ''],
        ],

        'definition' => [
            'inputType' => 'textarea',
            'exclude'   => true,
            'search'    => true,
            'eval'      => ['mandatory' => true, 'rows' => 3, 'tl_class' => 'clr'],
            'sql'       => ['type' => 'text', 'notnull' => false],
        ],
        'placement' => [
            'inputType' => 'textarea',
            'exclude'   => true,
            'search'    => true,
            'eval'      => ['rows' => 3, 'tl_class' => 'clr'],
            'sql'       => ['type' => 'text', 'notnull' => false],
        ],
        'distinction' => [
            'inputType' => 'textarea',
            'exclude'   => true,
            'search'    => true,
            'eval'      => ['rows' => 3, 'tl_class' => 'clr'],
            'sql'       => ['type' => 'text', 'notnull' => false],
        ],

        'coreFacts' => [
            'inputType' => 'optionWizard',
            'exclude'   => true,
            'eval'      => ['tl_class' => 'clr'],
            'sql'       => ['type' => 'blob', 'notnull' => false],
        ],

        'sources' => [
            'inputType' => 'optionWizard',
            'exclude'   => true,
            'eval'      => ['tl_class' => 'clr'],
            'sql'       => ['type' => 'blob', 'notnull' => false],
        ],

        'relatedPages' => [
            'inputType'  => 'checkbox',
            'exclude'    => true,
            'foreignKey' => 'tl_grounding_page.title',
            'eval'       => ['multiple' => true, 'tl_class' => 'clr'],
            'sql'        => ['type' => 'blob', 'notnull' => false],
        ],

        'addImage' => [
            'inputType' => 'checkbox',
            'exclude'   => true,
            'eval'      => ['submitOnChange' => true],
            'sql'       => ['type' => 'boolean', 'default' => false],
        ],
        'singleSRC' => [
            'inputType' => 'fileTree',
            'exclude'   => true,
            'eval'      => ['fieldType' => 'radio', 'filesOnly' => true, 'extensions' => 'jpg,jpeg,png,svg,webp', 'tl_class' => 'clr'],
            'sql'       => ['type' => 'binary', 'length' => 16, 'fixed' => true, 'default' => null, 'notnull' => false],
        ],
        'size' => [
            'inputType' => 'imageSize',
            'exclude'   => true,
            'eval'      => ['tl_class' => 'w50'],
            'sql'       => ['type' => 'string', 'length' => 255, 'default' => ''],
        ],

        'lastReviewed' => [
            'inputType' => 'text',
            'exclude'   => true,
            'eval'      => ['rgxp' => 'date', 'datepicker' => true, 'tl_class' => 'w50 wizard'],
            'sql'       => ['type' => 'string', 'length' => 10, 'default' => ''],
        ],

        'published' => [
            'inputType' => 'checkbox',
            'exclude'   => true,
            'filter'    => true,
            'eval'      => ['doNotCopy' => true],
            'sql'       => ['type' => 'boolean', 'default' => false],
        ],
        'start' => [
            'inputType' => 'text',
            'exclude'   => true,
            'eval'      => ['rgxp' => 'datim', 'datepicker' => true, 'tl_class' => 'w50 wizard'],
            'sql'       => ['type' => 'string', 'length' => 10, 'default' => ''],
        ],
        'stop' => [
            'inputType' => 'text',
            'exclude'   => true,
            'eval'      => ['rgxp' => 'datim', 'datepicker' => true, 'tl_class' => 'w50 wizard'],
            'sql'       => ['type' => 'string', 'length' => 10, 'default' => ''],
        ],
    ],
];
