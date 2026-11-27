<?php

declare(strict_types=1);

$admin = new Browser();
$admin->login('gp-admin', env('ACCEPTANCE_PASSWORD'));
$editor = new Browser();
$editor->login('gp-editor', env('ACCEPTANCE_PASSWORD'));
$visitor = new Browser();
$db = database();

$reviews = static fn (int $entry): string => "/contao?do=grounding_page&table=tl_grounding_page&key=reviews&id=$entry";
$count = static fn (int $entry): int => (int) $db->query("SELECT COUNT(*) FROM tl_grounding_page_review WHERE pid=$entry")->fetchColumn();
$complete = [
    'date' => date('Y-m-d'),
    'reviewerType' => 'Person',
    'reviewerName' => 'Erika Muster',
    'checks' => ['accuracy', 'timeliness', 'verifiability', 'identification', 'consistency'],
    'result' => 'approved',
    'note' => '',
];
$document = static fn (Browser $browser, int $entry, array $changes = []) => submit($browser, $reviews($entry), array_replace($complete, $changes), 'tl_grounding_page_review');
$hidden = static fn (Browser $browser, string $url, string $name): string => xpath($browser->get($url)->body)->query("//input[@name=\"$name\"]")->item(0)?->getAttribute('value') ?? '';
$token = static fn (Browser $browser): string => $hidden($browser, '/contao?do=grounding_page&table=tl_grounding_page&id=1', 'REQUEST_TOKEN');
$forge = static fn (Browser $browser, int $entry, array $data) => $browser->post($reviews($entry), $data + ['REQUEST_TOKEN' => $token($browser)]);
$forgedReview = static fn (int $entry): array => $complete + ['FORM_SUBMIT' => 'tl_grounding_page_review', 'fingerprint' => $hidden($admin, $reviews($entry), 'fingerprint')];
$permissions = static function (array $granted) use ($db): void {
    $db->prepare('UPDATE tl_user_group SET groundingPagep=? WHERE id=1')->execute([serialize($granted)]);

    if (hasColumn($db, 'tl_user_group', 'cud')) {
        $db->exec("UPDATE tl_user_group SET cud='a:0:{}' WHERE id=1");
    }
};

$list = $admin->get('/contao?do=grounding_page&table=tl_grounding_page&id=1')->body;
check('review: operation in entry list', null !== linkTo($list, ['key=reviews', 'id=50']));
open($admin, 'review: protocol page', $reviews(50), 'name="reviewerName"');
check('review: nothing documented by opening', 0 === $count(50));

$permissions([]);
$page = open($editor, 'review: editor without permission reads', $reviews(50), 'Pruefling');
check('review: no form without permission', !str_contains($page, 'name="reviewerName"'));
check('review: forged posts carry a token and a fingerprint', '' !== $token($editor) && '' !== $forgedReview(50)['fingerprint']);
$forge($editor, 50, $forgedReview(50));
check('review: forged post without permission stores nothing', 0 === $count(50));

$permissions(['review']);
check('review: foreign archive denied', !$editor->get($reviews(20))->contains('name="reviewerName"'));
$forge($editor, 20, $forgedReview(20));
check('review: forged post for foreign archive stores nothing', 0 === $count(20));

$result = $document($editor, 50, ['checks' => ['accuracy']]);
check('review: approval needs all five checks', 0 === $count(50) && $result->contains('value="Erika Muster"'));
$document($editor, 50, ['date' => date('Y-m-d', strtotime('+1 day'))]);
check('review: no future date', 0 === $count(50));
$result = $document($editor, 50, ['fingerprint' => '1:veraltet']);
check('review: changed content is not saved', 0 === $count(50) && $result->contains('seit dem Öffnen'));

$document($editor, 50);
$row = $db->query('SELECT * FROM tl_grounding_page_review WHERE pid=50')->fetch(PDO::FETCH_ASSOC) ?: [];
check('review: editor with permission documents', 1 === $count(50));
check('review: stores who and what', 'gp-editor' === ($row['userName'] ?? '') && '1.7' === ($row['standardVersion'] ?? '') && 1 === preg_match('~^1:[0-9a-f]{64}$~', $row['fingerprint'] ?? ''));
$document($editor, 50, ['date' => date('Y-m-d', strtotime('-1 day'))]);
check('review: no date before the last review', 1 === $count(50));
$document($editor, 50, ['result' => 'rejected', 'checks' => [], 'note' => '']);
check('review: rejection needs a note', 1 === $count(50));

$id = (int) ($row['id'] ?? 0);
$forge($editor, 50, ['FORM_SUBMIT' => 'tl_grounding_page_review_delete', 'record' => $id]);
check('review: editor cannot delete a record', $id > 0 && false !== $db->query("SELECT id FROM tl_grounding_page_review WHERE id=$id")->fetch());
foreach (["table=tl_grounding_page_review&act=edit&id=$id", "table=tl_grounding_page_review&act=delete&id=$id", 'table=tl_grounding_page_review&id=50'] as $query) {
    $status = $admin->get("/contao?do=grounding_page&$query")->status;
    check("review: no data container access ($query)", 200 !== $status && 1 === $count(50), "HTTP $status");
}

$entries = $admin->get('/contao?do=grounding_page&table=tl_grounding_page&id=1')->body;
$clipboard = operate($admin, $entries, ['act=paste', 'mode=copy', 'id=50']) ?? operate($admin, $entries, ['act=copy', 'id=50']);

if (null !== $clipboard) {
    operate($admin, $clipboard->body, ['act=copy', 'mode=1', 'id=50']);
}

$copy = (int) $db->query("SELECT id FROM tl_grounding_page WHERE title LIKE 'Pruefling%' AND id<>50")->fetchColumn();
check('review: copy of an entry has no protocol', $copy > 0 && 0 === $count($copy));

$document($admin, $copy, ['reviewerName' => 'Nur Kopie']);
$copyRecord = (int) $db->query("SELECT id FROM tl_grounding_page_review WHERE pid=$copy")->fetchColumn();
submit($admin, $reviews($copy), ['record' => $copyRecord], 'tl_grounding_page_review_delete');
check('review: admin deletes a record', $copyRecord > 0 && 0 === $count($copy));
$document($admin, $copy);
operate($admin, $admin->get('/contao?do=grounding_page&table=tl_grounding_page&id=1')->body, ['act=delete', "id=$copy"]);
check('review: protocol is deleted with its entry', 0 === $count($copy) && false === $db->query("SELECT id FROM tl_grounding_page WHERE id=$copy")->fetch());

$notice = 'Fakten geprüft von Erika Muster · Zuletzt von einem Menschen geprüft: ' . date('Y-m-d');
$shown = static fn (): bool => $visitor->get('/fakt/pruefling')->contains($notice);
$sql = static fn (string $query) => $db->exec($query);
$entryForm = '/contao?do=grounding_page&table=tl_grounding_page&act=edit&id=50';

$reader = $visitor->get('/fakt/pruefling');
$webPage = nodeOfType(linkedData($reader->body), 'WebPage');
check('notice: shown after approval', $reader->contains($notice));
check('notice: same values on the WebPage node', date('Y-m-d') === ($webPage['lastReviewed'] ?? '') && 'Person' === ($webPage['reviewedBy']['@type'] ?? '') && 'Erika Muster' === ($webPage['reviewedBy']['name'] ?? ''), json_encode($webPage));
check('notice: documenting user is not public', !$reader->contains('gp-editor'));
check('notice: nested content is part of the page', $reader->contains('Verschachtelt'));

$column = static fn (string $query): string => (string) $db->query($query)->fetchColumn();

submit($admin, $entryForm, ['stop' => '2099-01-01 00:00']);
check('notice: stays after saving the entry unchanged', '' !== $column('SELECT stop FROM tl_grounding_page WHERE id=50') && $shown());
submit($admin, '/contao?do=grounding_page&table=tl_content&act=edit&id=50', ['cssID' => ['', 'probe']]);
check('notice: stays after saving an element unchanged', str_contains($column('SELECT cssID FROM tl_content WHERE id=50'), 'probe') && $shown());
submit($admin, $entryForm, []);
check('notice: stays after saving the entry a second time', $shown());

foreach ([
    'changed element' => ["UPDATE tl_content SET text=CONCAT(text, '<p>Neu</p>') WHERE id=50", "UPDATE tl_content SET text=REPLACE(text, '<p>Neu</p>', '') WHERE id=50"],
    'changed nested element' => ["UPDATE tl_content SET text=CONCAT(text, '<p>Neu</p>') WHERE id=52", "UPDATE tl_content SET text=REPLACE(text, '<p>Neu</p>', '') WHERE id=52"],
    'hidden element' => ['UPDATE tl_content SET invisible=1 WHERE id=52', 'UPDATE tl_content SET invisible=0 WHERE id=52'],
    'deleted element' => ['UPDATE tl_content SET pid=999999 WHERE id=52', 'UPDATE tl_content SET pid=51 WHERE id=52'],
] as $label => [$change, $revert]) {
    $sql($change);
    $reader = $visitor->get('/fakt/pruefling');
    check("notice: gone after $label", !$reader->contains('Fakten geprüft') && !$reader->contains('lastReviewed') && !$reader->contains('reviewedBy'));
    $sql($revert);
    check("notice: back after reverting $label", $shown());
}

$saved = $db->query('SELECT coreFacts FROM tl_grounding_page WHERE id=50')->fetchColumn();
submit($admin, $entryForm, ['coreFacts' => [['value' => '2021', 'label' => 'Gegründet']]]);
check('notice: gone after a changed fact', str_contains($column('SELECT coreFacts FROM tl_grounding_page WHERE id=50'), '2021') && !$shown());

$records = $count(50);
submit($editor, $reviews(50), ['note' => ''], 'tl_grounding_page_review_minor');
check('minor: note required', $records === $count(50) && !$shown());
submit($editor, $reviews(50), ['note' => 'Tippfehler'], 'tl_grounding_page_review_minor');
check('minor: restores the notice with original date and reviewer', $records + 1 === $count(50) && $shown());

$document($editor, 50, ['result' => 'rejected', 'checks' => [], 'note' => 'Zahl falsch']);
check('rejected: removes the notice', !$shown());
check('rejected: note is not public', !$visitor->get('/fakt/pruefling')->contains('Zahl falsch'));
submit($editor, $reviews(50), ['note' => 'x'], 'tl_grounding_page_review_minor');
check('rejected: no minor confirmation', !$shown());

$english = static fn (): Response => $visitor->get('/en/fact/veloen');
check('cache: no notice before a review', !$english()->contains('Facts checked by'));
$document($admin, 40, ['reviewerName' => 'Cache Probe']);
check('cache: approval is visible at once', $english()->contains('Facts checked by Cache Probe · Last reviewed by a human: ' . date('Y-m-d')));
$sql("UPDATE tl_grounding_page_review SET reviewerName='Direkt geändert' WHERE pid=40");
check('cache: page is served from the cache', $english()->contains('Cache Probe'));
$document($admin, 40, ['result' => 'rejected', 'checks' => [], 'note' => 'nein']);
check('cache: rejection removes the notice at once', !$english()->contains('Facts checked by'));
