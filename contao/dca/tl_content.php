<?php

declare(strict_types=1);

$GLOBALS['TL_DCA']['tl_content']['palettes']['grounding_page_faq'] = '{type_legend},type,headline;{groundingpagefaq_legend},groundingPageItems;{template_legend:hide},customTpl;{protected_legend:hide},protected;{expert_legend:hide},cssID;{invisible_legend:hide},invisible,start,stop';

$GLOBALS['TL_DCA']['tl_content']['fields']['groundingPageItems'] = [
    'inputType' => 'optionWizard',
    'exclude'   => true,
    'eval'      => ['tl_class' => 'clr'],
    'sql'       => ['type' => 'blob', 'notnull' => false],
];
