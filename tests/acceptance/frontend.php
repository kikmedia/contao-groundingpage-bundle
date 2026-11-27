<?php

declare(strict_types=1);

$visitor = new Browser();

$list = $visitor->get('/fakten');
check('list: 200', 200 === $list->status, "HTTP $list->status");
check('list: shows published entries', $list->contains('VeloNova') && $list->contains('Nachbar'));
check('list: links to reader page', $list->contains('href="/fakt/velonova"'));
check('list: hides entry before start date', !$list->contains('Zukunft'));
check('list: hides entry after stop date', !$list->contains('Abgelaufen'));
check('list: hides unpublished entry', !$list->contains('Entwurf'));
check('list: only selected archives', !$list->contains('Fremdeintrag'));

$reader = $visitor->get('/fakt/velonova');
check('reader: 200', 200 === $reader->status, "HTTP $reader->status");
check('reader: h1 with entity name', 1 === preg_match('~<h1>\s*VeloNova\s*</h1>~', $reader->body));
check('reader: placement and distinction', $reader->contains('Einordnung-Text') && $reader->contains('Abgrenzung-Text'));
check('reader: core fact', 1 === preg_match('~<dt>Sitz</dt>\s*<dd>Hamburg~', $reader->body));
check('reader: old review date is not shown', !$reader->contains('2026-03-05') && !$reader->contains('Zuletzt geprüft'));
check('reader: text content element', $reader->contains('Abschnitt-Inhalt'));
check('reader: FAQ element', $reader->contains('<dt>Was ist das?</dt>'));
check('reader: source link', $reader->contains('href="https://example.com/report"'));
check('reader: related link points to reader page', $reader->contains('href="/fakt/nachbar"'));
check('reader: related hides future and unpublished', !$reader->contains('>Zukunft<') && !$reader->contains('>Entwurf<'));
check('reader: archive css class', 1 === preg_match('~<article[^>]*class="[^"]*marke-archiv~', $reader->body));
check('escaping: no markup from definition', !$reader->contains('<b>fett</b>'));
check('escaping: no markup from core fact', !$reader->contains('<i>Nord</i>'));
check('escaping: no markup from FAQ answer', !$reader->contains('<em>x</em>'));
check('escaping: no double encoded entities', !$reader->contains('&amp;#'));

$nodes = linkedData($reader->body);
$types = array_column($nodes, '@type');
$entity = nodeOfType($nodes, 'Organization');
$webPage = nodeOfType($nodes, 'WebPage');
check('json-ld: entity and FAQPage', in_array('Organization', $types, true) && in_array('FAQPage', $types, true) && !in_array('INVALID', $types, true), implode(',', array_map(json_encode(...), $types)));
check('json-ld: plain text', str_contains($entity['description'] ?? '', '<b>fett</b>'), $entity['description'] ?? '');
check('json-ld: one WebPage node', 1 === count(array_keys($types, 'WebPage', true)), implode(',', array_map(json_encode(...), $types)));
check('json-ld: dateModified on WebPage is the last content change', '2026-04-10' === ($webPage['dateModified'] ?? ''), $webPage['dateModified'] ?? 'missing');
check('json-ld: citation on WebPage', 'https://example.com/report' === ($webPage['citation'][0]['url'] ?? ''));
check('json-ld: no properties on an Organization it does not allow', [] === array_intersect(['additionalProperty', 'citation', 'dateModified'], array_keys($entity)), implode(',', array_keys($entity)));
check('json-ld: no review claim without a documented review', !$reader->contains('lastReviewed') && !$reader->contains('reviewedBy'));
check('reader: visible date of last change', $reader->contains('Zuletzt aktualisiert') && $reader->contains('<time datetime="2026-04-10">'));
check('reader: facts heading names the entity', 1 === preg_match('~<h2>\s*VeloNova: Kerndaten\s*</h2>~', $reader->body));
check('reader: sources heading names the entity', 1 === preg_match('~<h2>\s*Quellen zu VeloNova\s*</h2>~', $reader->body));
$neighbour = $visitor->get('/fakt/nachbar');
check('reader: old review date hidden without core facts', 200 === $neighbour->status && !$neighbour->contains('2026-02-03'));

$english = $visitor->get('/en/fact/veloen');
check('english: 200', 200 === $english->status, "HTTP $english->status");
check('english: facts heading', 1 === preg_match('~<h2>\s*VeloEn: Key facts\s*</h2>~', $english->body));
check('english: sources heading', 1 === preg_match('~<h2>\s*Sources for VeloEn\s*</h2>~', $english->body));
check('english: date labels', !$english->contains('Last reviewed') && $english->contains('Last updated'));
check('english: no German labels', !$english->contains('Kerndaten') && !$english->contains('Quellen') && !$english->contains('Zuletzt'));
$product = nodeOfType(linkedData($english->body), 'Product');
check('json-ld: facts stay on a Product', 'Weight' === ($product['additionalProperty'][0]['name'] ?? ''));

preg_match('~<img[^>]*src="([^"]*logo[^"]*)"~', $reader->body, $image);
$source = $image[1] ?? '';
check('reader: image resized', str_contains($source, 'assets/images/'), $source);

if ('' !== $source) {
    $visitor->get($source);
}

clearstatcache();
check('reader: image reachable', '' !== $source && is_file(env('PROJECT_DIR') . '/public/' . ltrim(rawurldecode($source), '/')), $source);

foreach (['zukunft', 'abgelaufen', 'entwurf', 'gibtsnicht', 'fremdeintrag'] as $alias) {
    $status = $visitor->get("/fakt/$alias")->status;
    check("reader: 404 for $alias", 404 === $status, "HTTP $status");
}

$sitemap = $visitor->get('/sitemap.xml');
check('sitemap: 200', 200 === $sitemap->status, "HTTP $sitemap->status");
check('sitemap: contains published entry', $sitemap->contains('/fakt/velonova'));
check('sitemap: omits future, expired and unpublished', !$sitemap->contains('/fakt/zukunft') && !$sitemap->contains('/fakt/abgelaufen') && !$sitemap->contains('/fakt/entwurf'));
