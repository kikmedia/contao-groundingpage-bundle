<?php

declare(strict_types=1);

function open(Browser $browser, string $label, string|null $url, string $expected): string
{
    if (null === $url) {
        check($label, false, 'link not found');

        return '';
    }

    $page = $browser->get($url);
    check($label, 200 === $page->status && $page->contains($expected), "HTTP $page->status, expected '$expected'");

    return $page->body;
}

$admin = new Browser();
$visitor = new Browser();

$home = $admin->login('gp-admin', env('ACCEPTANCE_PASSWORD'));
check('admin: login', 200 === $home->status && $home->contains('do=grounding_page'), "HTTP $home->status");

$entryListUrl = '/contao?do=grounding_page&table=tl_grounding_page&id=1';

$archives = open($admin, 'admin: archive list', '/contao?do=grounding_page', 'Facts');
open($admin, 'admin: archive form', linkTo($archives, ['do=grounding_page', 'act=edit', 'id=1']), 'name="jumpTo"');
$entries = open($admin, 'admin: entry list', linkTo($archives, ['do=grounding_page', 'table=tl_grounding_page', 'id=1']), 'VeloNova');
check('admin: entry list shows entity type', 1 === preg_match('~VeloNova.{0,200}Organi[sz]ation~s', $entries));
$entryUrl = linkTo($entries, ['do=grounding_page', 'table=tl_grounding_page', 'act=edit', 'id=1']);
open($admin, 'admin: entry form', $entryUrl, 'name="definition"');
$elements = open($admin, 'admin: content element list', linkTo($entries, ['do=grounding_page', 'table=tl_content', 'id=1']), 'Was ist das?');
$faqUrl = linkTo($elements, ['do=grounding_page', 'act=edit', 'id=11']);
open($admin, 'admin: FAQ element form', $faqUrl, 'groundingPageItems');
open($admin, 'admin: module list', '/contao?do=themes&table=tl_module&id=1', 'grounding_page_list');
$groups = open($admin, 'admin: user group list', '/contao?do=group', 'Redaktion');
open($admin, 'admin: user group form', linkTo($groups, ['do=group', 'act=edit', 'id=1']), 'groundingPagep');

$saved = submit($admin, $entryUrl, ['title' => 'VeloNova Neu', 'alias' => '']);
check('admin: save entry and generate alias', 200 === $saved->status && $saved->contains('value="velonova-neu"'), "HTTP $saved->status");
$reader = $visitor->get('/fakt/velonova-neu');
check('admin: saved entry keeps facts, sources and related', $reader->contains('<dt>Sitz</dt>') && $reader->contains('example.com/report') && $reader->contains('>Nachbar<'));
check('admin: saved entry keeps review date', $reader->contains('<time datetime="2026-03-05">'));

$saved = submit($admin, $faqUrl, ['groundingPageItems' => [['value' => 'Neue Antwort & mehr', 'label' => 'Neue Frage?']]]);
$reader = $visitor->get('/fakt/velonova-neu');
check('admin: save FAQ element', 200 === $saved->status && $reader->contains('Neue Frage?') && $reader->contains('Neue Antwort &'), "HTTP $saved->status");

$saved = submit($admin, '/contao?do=themes&table=tl_module&act=edit&id=1', []);
check('admin: save list module', 200 === $saved->status && $saved->contains('grounding_page_archives'), "HTTP $saved->status");
$saved = submit($admin, '/contao?do=grounding_page&act=edit&id=1', ['title' => 'Facts neu']);
check('admin: save archive', 200 === $saved->status && $saved->contains('Facts neu'), "HTTP $saved->status");

$entries = $admin->get($entryListUrl)->body;
check('admin: can create archive', canCreate($admin->get('/contao?do=grounding_page')->body));
check('admin: can create entry', canCreate($entries));
check('admin: can delete entry', null !== linkTo($entries, ['act=delete', 'id=2']));

$toggle = linkTo($entries, ['act=toggle', 'field=published', 'id=2']);
check('admin: publish toggle', null !== $toggle);

if (null !== $toggle) {
    $admin->get($toggle);
    check('admin: toggle unpublishes entry', !$visitor->get('/fakten')->contains('Nachbar'));
}

$entries = $admin->get($entryListUrl)->body;
$clipboard = operate($admin, $entries, ['act=paste', 'mode=copy', 'id=1']) ?? operate($admin, $entries, ['act=copy', 'id=1']);
check('admin: copy operation', null !== $clipboard);

if (null !== $clipboard) {
    operate($admin, $clipboard->body, ['act=copy', 'mode=1', 'id=1']);
    $copied = $admin->get($entryListUrl)->body;
    check('admin: copy creates a second entry', substr_count($copied, 'VeloNova Neu') > substr_count($entries, 'VeloNova Neu'));
    check('admin: copy is not published', 1 === substr_count($visitor->get('/fakten')->body, '>VeloNova Neu<'));
    $reviewOfCopy = database()->query("SELECT lastReviewed FROM tl_grounding_page WHERE title LIKE 'VeloNova Neu%' ORDER BY id DESC LIMIT 1")->fetchColumn();
    check('admin: copy carries no review date', '' === $reviewOfCopy, var_export($reviewOfCopy, true));
}
