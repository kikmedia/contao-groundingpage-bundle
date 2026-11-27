<?php

declare(strict_types=1);

$GLOBALS['TL_LANG']['tl_grounding_page']['title'] = ['Entity name', 'The name of the entity – rendered as H1. Name only, no slogan (e.g. "VeloNova", not "The best solution for X").'];
$GLOBALS['TL_LANG']['tl_grounding_page']['alias'] = ['Alias', 'Here you can set an alias for the entry.'];
$GLOBALS['TL_LANG']['tl_grounding_page']['author'] = ['Author', 'Select the author / editorially responsible person.'];

$GLOBALS['TL_LANG']['tl_grounding_page']['entityType'] = ['Entity type (Schema.org)', 'The matching Schema.org type of the entity. Mirrored 1:1 into the JSON-LD.'];
$GLOBALS['TL_LANG']['tl_grounding_page']['entityTypeCustom'] = ['Custom entity type', 'Only if "Other" was selected: the exact Schema.org type, e.g. "DefinedTerm" or "SoftwareApplication".'];
$GLOBALS['TL_LANG']['tl_grounding_page']['entityTypes'] = [
    'Organization'        => 'Organization',
    'Product'              => 'Product',
    'Person'                => 'Person',
    'Event'                 => 'Event',
    'Place'                 => 'Place',
    'Service'               => 'Service',
    'SoftwareApplication'   => 'Software',
    'CreativeWork'          => 'Creative work / publication',
    'DefinedTerm'           => 'Concept / term',
    'other'                 => 'Other …',
];

$GLOBALS['TL_LANG']['tl_grounding_page']['definition'] = ['Definition', 'One or two sentences: what is the entity? No marketing language, no phrases like "In today\'s world …".'];
$GLOBALS['TL_LANG']['tl_grounding_page']['placement'] = ['Placement', 'Which segment/context is the entity positioned in? (optional but recommended)'];
$GLOBALS['TL_LANG']['tl_grounding_page']['distinction'] = ['Distinction', 'What is the entity clearly distinguished from? (optional, reduces confusion risk)'];

$GLOBALS['TL_LANG']['tl_grounding_page']['coreFacts'] = ['Structured facts', 'Attribute/value pairs, rendered as a <dl> and in the JSON-LD, e.g. "Founded" → "2014". No prose here – use the sections below for that.'];
$GLOBALS['TL_LANG']['tl_grounding_page']['sources'] = ['Sources', 'Title/URL pairs backing up the facts on this page.'];
$GLOBALS['TL_LANG']['tl_grounding_page']['relatedPages'] = ['Related entities', 'Other Grounding Pages this page should link to (internal linking).'];

$GLOBALS['TL_LANG']['tl_grounding_page']['addImage'] = ['Add image', 'Optional – the standard does not require an image.'];
$GLOBALS['TL_LANG']['tl_grounding_page']['singleSRC'] = ['Image', 'Select a file from the file tree.'];
$GLOBALS['TL_LANG']['tl_grounding_page']['size'] = ['Image size', 'Here you can set the image size.'];

$GLOBALS['TL_LANG']['tl_grounding_page']['published'] = ['Publish', 'Publish this entry.'];
$GLOBALS['TL_LANG']['tl_grounding_page']['start'] = ['Display from', 'The entry will only be shown from this date on.'];
$GLOBALS['TL_LANG']['tl_grounding_page']['stop'] = ['Display until', 'The entry will only be shown until this date.'];

$GLOBALS['TL_LANG']['tl_grounding_page']['title_legend'] = 'Entity';
$GLOBALS['TL_LANG']['tl_grounding_page']['entity_legend'] = 'Entity type';
$GLOBALS['TL_LANG']['tl_grounding_page']['lead_legend'] = 'Lead: definition, placement, distinction';
$GLOBALS['TL_LANG']['tl_grounding_page']['facts_legend'] = 'Structured facts';
$GLOBALS['TL_LANG']['tl_grounding_page']['sources_legend'] = 'Sources';
$GLOBALS['TL_LANG']['tl_grounding_page']['related_legend'] = 'Related entities';
$GLOBALS['TL_LANG']['tl_grounding_page']['media_legend'] = 'Media (optional)';
$GLOBALS['TL_LANG']['tl_grounding_page']['publish_legend'] = 'Publication settings';

$GLOBALS['TL_LANG']['tl_grounding_page']['edit'] = ['Edit entry', 'Edit entry ID %s'];
$GLOBALS['TL_LANG']['tl_grounding_page']['copy'] = ['Duplicate entry', 'Duplicate entry ID %s'];
$GLOBALS['TL_LANG']['tl_grounding_page']['cut'] = ['Move entry', 'Move entry ID %s'];
$GLOBALS['TL_LANG']['tl_grounding_page']['delete'] = ['Delete entry', 'Delete entry ID %s'];
$GLOBALS['TL_LANG']['tl_grounding_page']['toggle'] = ['Toggle publication', 'Toggle publication of entry ID %s'];
$GLOBALS['TL_LANG']['tl_grounding_page']['show'] = ['Entry details', 'Show the details of entry ID %s'];
$GLOBALS['TL_LANG']['tl_grounding_page']['new'] = ['New entry', 'Create a new entry'];
$GLOBALS['TL_LANG']['tl_grounding_page']['children'] = ['Content elements', 'Edit the content elements of entry ID %s'];
$GLOBALS['TL_LANG']['tl_grounding_page']['reviews'] = ['Reviews', 'Reviews of entry ID %s'];

$GLOBALS['TL_LANG']['tl_grounding_page']['claimConformity'] = ['State conformity with version 1.7', 'Shows “Grounding Page Standard 1.7” below the review notice while the review is current and all required checks pass.'];
$GLOBALS['TL_LANG']['tl_grounding_page']['check_legend'] = 'Review following standard 1.7';
$GLOBALS['TL_LANG']['tl_grounding_page']['checkHeading'] = 'Check results (as last saved)';
$GLOBALS['TL_LANG']['tl_grounding_page']['reviewStates'] = [
    'current' => 'Review current',
    'stale' => 'Review outdated',
    'rejected' => 'Review not approved',
    'missing' => 'Review missing',
];
$GLOBALS['TL_LANG']['tl_grounding_page']['checkStates'] = [
    'passed' => 'met',
    'open' => 'open',
    'hint' => 'Hint',
    'info' => 'Information',
];
$GLOBALS['TL_LANG']['tl_grounding_page']['checks'] = [
    'p2' => 'P-2: The definition is filled in.',
    'p3' => 'P-3: Every H2 heading names the entity.',
    'p4' => 'P-4: At least one core fact has a label and a value.',
    'p5' => 'P-5: A specific entity type is selected (neither Thing nor WebPage).',
    'p7' => 'P-7: Entry and redirect page are publicly reachable and indexable.',
    'p8' => 'P-8: A fact review is approved and current.',
    'p6_alt' => 'P-6: The image has no alt text in the language of the redirect page.',
    'p11' => 'P-11: The distinction is missing.',
    'p12' => 'P-12: A source is missing or a source has no http(s) address.',
    'p15' => 'P-15: The archive has no redirect page or is not selected in any list module.',
    'p16' => 'P-16: The last fact review is older than 6 months.',
    'p16_late' => 'P-16: The last fact review is older than 12 months and should be repeated urgently.',
    'p6' => 'P-6: The structured data comes from the visible fields (with unchanged templates).',
    'p9' => 'P-9: Review notice, reviewedBy and lastReviewed come from the same documented review (with unchanged templates).',
    'p10' => 'P-10: The conformity statement names version 1.7 and appears only when the required checks pass (with unchanged templates).',
    'legacy_date' => 'Old review date %s: no protocol, not shown.',
];
