<?php

declare(strict_types=1);

$editor = new Browser();
$db = database();

$home = $editor->login('gp-editor', env('ACCEPTANCE_PASSWORD'));
check('editor: login', 200 === $home->status && $home->contains('do=grounding_page'), "HTTP $home->status");

$archives = open($editor, 'editor: archive list', '/contao?do=grounding_page', 'Facts');
check('editor: foreign archive not listed', !str_contains($archives, 'Geheimarchiv'));
check('editor: no new archive without permission', !canCreate($archives));

$entries = open($editor, 'editor: own entry list', '/contao?do=grounding_page&table=tl_grounding_page&id=1', 'VeloNova');
check('editor: no delete operation without permission', null === linkTo($entries, ['act=delete', 'id=2']));
open($editor, 'editor: own entry form', '/contao?do=grounding_page&table=tl_grounding_page&act=edit&id=1', 'name="definition"');
open($editor, 'editor: own content elements', '/contao?do=grounding_page&table=tl_content&id=1', 'Abschnitt-Inhalt');

foreach ([
    'foreign content elements' => ['table=tl_content&id=20', 'Geheimtext'],
    'foreign content element form' => ['table=tl_content&act=edit&id=30', 'Geheimtext'],
    'foreign entry list' => ['table=tl_grounding_page&id=2', 'Geheimsache'],
    'foreign entry form' => ['table=tl_grounding_page&act=edit&id=20', 'Geheimsache'],
    'foreign archive form' => ['act=edit&id=2', 'Geheimarchiv'],
] as $label => [$query, $secret]) {
    check("editor: $label denied", !$editor->get("/contao?do=grounding_page&$query")->contains($secret));
}

$db->prepare('UPDATE tl_user_group SET groundingPagep=?, alexf=? WHERE id=1')->execute([
    serialize(['create', 'delete']),
    serialize(['tl_grounding_page::title', 'tl_grounding_page::definition', 'tl_grounding_page_archive::title', 'tl_grounding_page_archive::jumpTo']),
]);

if (hasColumn($db, 'tl_user_group', 'cud')) {
    $operations = [];

    foreach (['tl_grounding_page_archive', 'tl_grounding_page', 'tl_content'] as $table) {
        array_push($operations, "$table::create", "$table::update", "$table::delete");
    }

    $db->prepare('UPDATE tl_user_group SET cud=? WHERE id=1')->execute([serialize($operations)]);
}

$entries = open($editor, 'editor with permission: entry list', '/contao?do=grounding_page&table=tl_grounding_page&id=1', 'Nachbar');
check('editor with permission: delete operation', null !== linkTo($entries, ['act=delete', 'id=2']));
$archives = open($editor, 'editor with permission: archive list', '/contao?do=grounding_page', 'Facts');
check('editor with permission: new archive', canCreate($archives));
