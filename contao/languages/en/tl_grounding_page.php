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

$GLOBALS['TL_LANG']['tl_grounding_page']['lastReviewed'] = ['Last reviewed on', 'Only change this when an actual editorial review has taken place – not automatically on every save. Recommended interval: every 3–6 months.'];

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
$GLOBALS['TL_LANG']['tl_grounding_page']['review_legend'] = 'Recency';
$GLOBALS['TL_LANG']['tl_grounding_page']['publish_legend'] = 'Publication settings';

$GLOBALS['TL_LANG']['tl_grounding_page']['edit'] = ['Edit entry', 'Edit entry ID %s'];
$GLOBALS['TL_LANG']['tl_grounding_page']['editheader'] = ['Edit archive', 'Edit archive settings'];
$GLOBALS['TL_LANG']['tl_grounding_page']['copy'] = ['Duplicate entry', 'Duplicate entry ID %s'];
$GLOBALS['TL_LANG']['tl_grounding_page']['cut'] = ['Move entry', 'Move entry ID %s'];
$GLOBALS['TL_LANG']['tl_grounding_page']['delete'] = ['Delete entry', 'Delete entry ID %s'];
$GLOBALS['TL_LANG']['tl_grounding_page']['toggle'] = ['Toggle publication', 'Toggle publication of entry ID %s'];
$GLOBALS['TL_LANG']['tl_grounding_page']['show'] = ['Entry details', 'Show the details of entry ID %s'];
$GLOBALS['TL_LANG']['tl_grounding_page']['new'] = ['New entry', 'Create a new entry'];
