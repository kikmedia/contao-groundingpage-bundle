<?php

declare(strict_types=1);

require __DIR__ . '/lib.php';
require env('PROJECT_DIR') . '/vendor/autoload.php';

$project = env('PROJECT_DIR');
$db = database();
$contao6 = class_exists(Contao\CoreBundle\Migration\Version600\OutputEncodingMigration::class);
$now = time();

$put = static function (string $table, array $row) use ($db): void {
    $columns = implode(',', array_map(static fn (string $column): string => "`$column`", array_keys($row)));
    $marks = implode(',', array_fill(0, count($row), '?'));
    $db->prepare("REPLACE INTO `$table` ($columns) VALUES ($marks)")->execute(array_values($row));
};

$stored = static fn (string $value): string => $contao6
    ? $value
    : str_replace(['<', '>', '"', '(', ')', '='], ['&#60;', '&#62;', '&#34;', '&#40;', '&#41;', '&#61;'], $value);

foreach (['tl_theme', 'tl_layout', 'tl_page', 'tl_article', 'tl_module', 'tl_content', 'tl_files', 'tl_user_group', 'tl_grounding_page_archive', 'tl_grounding_page', 'tl_grounding_page_review'] as $table) {
    $db->exec("DELETE FROM `$table`");
}

$author = (int) $db->query("SELECT id FROM tl_user WHERE username='gp-admin'")->fetchColumn();

$put('tl_user_group', [
    'id' => 1,
    'tstamp' => $now,
    'name' => 'Redaktion',
    'modules' => serialize(['grounding_page']),
    'groundingPages' => serialize(['1']),
    'alexf' => serialize(['tl_grounding_page::title', 'tl_grounding_page::definition']),
    'elements' => serialize(['text', 'grounding_page_faq']),
]);

if (hasColumn($db, 'tl_user_group', 'cud')) {
    $db->prepare('UPDATE tl_user_group SET cud=? WHERE id=1')->execute([serialize(['tl_grounding_page::update', 'tl_grounding_page::create', 'tl_content::update', 'tl_content::create'])]);
}

$db->prepare("UPDATE tl_user SET `groups`=?, inherit='group' WHERE username='gp-editor'")->execute([serialize(['1'])]);

$put('tl_theme', ['id' => 1, 'tstamp' => $now, 'name' => 'Test']);
$put('tl_layout', [
    'id' => 1,
    'pid' => 1,
    'tstamp' => $now,
    'name' => 'Test',
    'modules' => serialize([['mod' => '0', 'col' => 'main', 'enable' => '1']]),
] + ($contao6 ? ['type' => 'modern', 'template' => 'page/layout'] : ['rows' => '1rw', 'cols' => '1cl', 'template' => 'fe_page']));

$page = ['tstamp' => $now, 'published' => 1];
$put('tl_page', $page + ['id' => 1, 'pid' => 0, 'sorting' => 128, 'title' => 'Root', 'alias' => 'root', 'type' => 'root', 'language' => 'de', 'fallback' => 1, 'includeLayout' => 1, 'layout' => 1, 'useSSL' => 0, 'chmod' => '']);
$put('tl_page', $page + ['id' => 2, 'pid' => 1, 'sorting' => 128, 'title' => 'Fakten', 'alias' => 'fakten', 'type' => 'regular']);
$put('tl_page', $page + ['id' => 3, 'pid' => 1, 'sorting' => 256, 'title' => 'Fakt', 'alias' => 'fakt', 'type' => 'regular', 'requireItem' => 1]);

foreach ([2 => 1, 3 => 2] as $pageId => $moduleId) {
    $put('tl_article', ['id' => $pageId, 'pid' => $pageId, 'sorting' => 128, 'tstamp' => $now, 'title' => "Artikel $pageId", 'alias' => "artikel-$pageId", 'author' => $author, 'inColumn' => 'main', 'published' => 1]);
    $put('tl_content', ['id' => $moduleId, 'pid' => $pageId, 'ptable' => 'tl_article', 'sorting' => 128, 'tstamp' => $now, 'type' => 'module', 'module' => $moduleId]);
}

foreach ([1 => 'grounding_page_list', 2 => 'grounding_page_reader'] as $id => $type) {
    $put('tl_module', ['id' => $id, 'pid' => 1, 'tstamp' => $now, 'name' => $type, 'type' => $type, 'grounding_page_archives' => serialize(['1'])]);
}

$put('tl_grounding_page_archive', ['id' => 1, 'tstamp' => $now, 'title' => 'Facts', 'jumpTo' => 3, 'cssClass' => 'marke-archiv']);
$put('tl_grounding_page_archive', ['id' => 2, 'tstamp' => $now, 'title' => 'Geheimarchiv', 'jumpTo' => 0]);

$entry = static function (int $id, string $title, array $fields = []) use ($put, $now, $author): void {
    $put('tl_grounding_page', $fields + [
        'id' => $id,
        'pid' => 1,
        'sorting' => $id * 128,
        'tstamp' => $now,
        'author' => $author,
        'entityType' => 'Organization',
        'title' => $title,
        'alias' => strtolower($title),
        'definition' => "ist der Eintrag $title.",
        'published' => 1,
    ]);
};

$entry(1, 'VeloNova', [
    'definition' => $stored('ist ein Hersteller <b>fett</b> von Raedern.'),
    'placement' => 'Einordnung-Text',
    'distinction' => 'Abgrenzung-Text',
    'coreFacts' => serialize([['value' => $stored('Hamburg <i>Nord</i>'), 'label' => 'Sitz']]),
    'sources' => serialize([['value' => 'https://example.com/report', 'label' => 'Jahresbericht']]),
    'relatedPages' => serialize(['2', '3', '5']),
    'lastReviewed' => (string) mktime(0, 0, 0, 3, 5, 2026),
    'tstamp' => mktime(12, 0, 0, 4, 1, 2026),
]);
$entry(2, 'Nachbar', ['lastReviewed' => (string) mktime(0, 0, 0, 2, 3, 2026)]);
$entry(3, 'Zukunft', ['start' => (string) ($now + 86400)]);
$entry(4, 'Abgelaufen', ['stop' => (string) ($now - 86400)]);
$entry(5, 'Entwurf', ['published' => 0]);
$entry(20, 'Geheimsache', ['pid' => 2, 'published' => 0]);
$entry(21, 'Fremdeintrag', ['pid' => 2]);

$element = static fn (int $id, int $entryId, string $type, array $fields) => $put('tl_content', $fields + ['id' => $id, 'pid' => $entryId, 'ptable' => 'tl_grounding_page', 'sorting' => $id * 128, 'tstamp' => $now, 'type' => $type]);

$element(10, 1, 'text', ['text' => '<p>Abschnitt-Inhalt</p>', 'tstamp' => mktime(12, 0, 0, 4, 10, 2026)]);
$element(11, 1, 'grounding_page_faq', [
    'tstamp' => mktime(12, 0, 0, 4, 2, 2026),
    'headline' => serialize(['unit' => 'h2', 'value' => 'Fragen']),
    'groundingPageItems' => serialize([['value' => $stored('Antwort </script><em>x</em>'), 'label' => 'Was ist das?']]),
]);
$element(30, 20, 'text', ['text' => '<p>Geheimtext</p>']);

$entry(50, 'Pruefling', [
    'definition' => 'ist ein Testobjekt.',
    'distinction' => 'Nicht zu verwechseln.',
    'coreFacts' => serialize([['value' => '2020', 'label' => 'Gegründet']]),
    'sources' => serialize([['value' => 'https://example.com/pruefling', 'label' => 'Quelle']]),
]);
$element(50, 50, 'text', ['text' => '<h2>Über Pruefling</h2><p>Inhalt</p>']);
$element(51, 50, 'element_group', []);
$element(52, 51, 'text', ['ptable' => 'tl_content', 'text' => '<p>Verschachtelt</p>']);

$put('tl_page', $page + ['id' => 10, 'pid' => 0, 'sorting' => 256, 'title' => 'English', 'alias' => 'english', 'type' => 'root', 'language' => 'en', 'fallback' => 0, 'urlPrefix' => 'en', 'includeLayout' => 1, 'layout' => 1, 'useSSL' => 0, 'chmod' => '']);
$put('tl_page', $page + ['id' => 12, 'pid' => 10, 'sorting' => 128, 'title' => 'Fact', 'alias' => 'fact', 'type' => 'regular', 'requireItem' => 1, 'includeCache' => 1, 'cache' => 3600]);
$put('tl_article', ['id' => 12, 'pid' => 12, 'sorting' => 128, 'tstamp' => $now, 'title' => 'Article', 'alias' => 'article-12', 'author' => $author, 'inColumn' => 'main', 'published' => 1]);
$put('tl_content', ['id' => 12, 'pid' => 12, 'ptable' => 'tl_article', 'sorting' => 128, 'tstamp' => $now, 'type' => 'module', 'module' => 3]);
$put('tl_module', ['id' => 3, 'pid' => 1, 'tstamp' => $now, 'name' => 'reader en', 'type' => 'grounding_page_reader', 'grounding_page_archives' => serialize(['3'])]);
$put('tl_grounding_page_archive', ['id' => 3, 'tstamp' => $now, 'title' => 'English', 'jumpTo' => 12]);
$entry(40, 'VeloEn', [
    'pid' => 3,
    'entityType' => 'Product',
    'coreFacts' => serialize([['value' => '12 kg', 'label' => 'Weight']]),
    'sources' => serialize([['value' => 'https://example.com/sheet', 'label' => 'Data sheet']]),
    'lastReviewed' => (string) mktime(0, 0, 0, 3, 7, 2026),
]);

if (!is_dir("$project/files/gp")) {
    mkdir("$project/files/gp", 0o777, true);
}

touch("$project/files/gp/.public");
imagepng(imagecreatetruecolor(400, 200), "$project/files/gp/logo.png");

$uuid = static function (): string {
    $bytes = random_bytes(16);
    $bytes[6] = chr((ord($bytes[6]) & 0x0F) | 0x10);
    $bytes[8] = chr((ord($bytes[8]) & 0x3F) | 0x80);

    return $bytes;
};
$folder = $uuid();
$file = $uuid();
$put('tl_files', ['id' => 1, 'uuid' => $folder, 'tstamp' => $now, 'type' => 'folder', 'path' => 'files/gp', 'name' => 'gp']);
$put('tl_files', ['id' => 2, 'pid' => $folder, 'uuid' => $file, 'tstamp' => $now, 'type' => 'file', 'path' => 'files/gp/logo.png', 'extension' => 'png', 'name' => 'logo.png', 'hash' => md5_file("$project/files/gp/logo.png")]);
$db->prepare('UPDATE tl_grounding_page SET addImage=1, singleSRC=?, size=? WHERE id=1')->execute([$file, serialize(['100', '', 'proportional'])]);
