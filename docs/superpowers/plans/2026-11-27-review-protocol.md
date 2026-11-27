# Review Protocol (0.2.0) Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Menschliche Faktenprüfung je Eintrag dokumentieren, als Prüfhinweis ausgeben und die Konformität mit Grounding Page Standard 1.7 prüfen.

**Architecture:** Eine neue Tabelle `tl_grounding_page_review` hält unveränderliche Prüfdatensätze und wird ausschließlich über eine eigene Backend-Ansicht (`key=reviews`) geschrieben; sie steht nicht in den Modultabellen, Contaos Formulare erreichen sie also nicht. Ob eine Prüfung gilt, ergibt sich bei jedem Aufruf aus dem Vergleich des gespeicherten Fingerabdrucks mit dem aktuellen Inhalt. Fingerabdruck, Prüfregeln und Konformitätsprüfungen sind reine Klassen mit Unit-Tests.

**Tech Stack:** PHP 8.3+, Contao 5.3 bis 6.0, Doctrine DBAL, Twig, PHPUnit 11, PHPStan, PHP-CS-Fixer, Abnahmetest `tests/acceptance/`.

**Spec:** `docs/superpowers/specs/2026-11-27-review-protocol-design.md`

## Global Constraints

- PHP `^8.3`, `contao/core-bundle ^5.3 || ^6.0`; keine neuen Composer-Abhängigkeiten.
- Keine Kommentare im Code (PHP, Twig, YAML, Tests). README, CHANGELOG, LICENSE sind ausgenommen.
- Jeder Backend- und Frontend-Text auf Deutsch und Englisch.
- Nie: Prüfhinweis ohne dokumentierte Freigabe des aktuell sichtbaren Inhalts; Prüfdatum durch Speichern, Update, Migration oder Automatik; altes „Zuletzt geprüft am“ als menschliche Prüfung. Im Zweifel veraltet.
- Prüfhinweis DE „Fakten geprüft von %s · Zuletzt von einem Menschen geprüft: %s“, EN „Facts checked by %s · Last reviewed by a human: %s“.
- Standardversion `1.7`. Konformitätslink `https://groundingpage.com/spec/v1.7/de/` für Sprachen, die mit `de` beginnen, sonst `https://groundingpage.com/spec/v1.7/`.
- Cache-Tag eines Eintrags: `contao.db.tl_grounding_page.<id>`.
- Branch `feat/review-protocol`, lokale Commits je Task. Kein Push, Merge, Tag oder Release ohne ausdrückliche Zustimmung.
- Das Repo ist öffentlich: keine Daten aus Live-Systemen, keine Zugangsdaten.
- Vor jeder Git-Operation (macOS): `find .git -name '._*' -type f -delete` und `find . -name '._*' -type f -not -path './.git/*' -delete`.

**Testbefehle** (`E` = Ordner mit den lokalen Testskripten; Docker muss laufen):

| Kurz | Befehl | Bestanden, wenn |
|---|---|---|
| UNIT | `"$E/a2.sh" unit 40` | `OK` ohne Fehler |
| STATIC | `"$E/a2.sh" static` | keine PHPStan-Fehler, CS-Fixer ohne Diff |
| ACC | `"$E/a2.sh" 'c57-web:5.7.*'` | `exit 0`, `FAIL=0` |
| MATRIX | `"$E/a2.sh" 'c60-web:5.3.*' 'c60-web:5.7.*' 'c60-web:6.0.*' 'c57-web:5.3.*' 'c57-web:5.7.*'` | fünfmal `exit 0`, `FAIL=0` |

Fehlt `a2.sh` (Scratchpad geleert), gelten `composer test`, `composer phpstan`, `composer cs-check` und `tests/acceptance/run.sh` wie im README.

Nach der Arbeit die selbst gestarteten Container `gp-test-p57` und `gp-acc-db` wieder stoppen. Andere Container nicht anfassen.

## Review Focus

1. Eintrag oder Inhaltselement im Backend unverändert speichern: Prüfung bleibt aktuell (Task 5).
2. Seite liegt im HTTP-Cache, danach „nicht freigegeben“: Hinweis verschwindet sofort (Task 5).
3. Dieselben Inhalte in Contao-5- und Contao-6-Speicherform (Entitäten, `[{]`, Spalten in `jsonData`): gleicher Fingerabdruck (Task 1).
4. Binäre Datei-UUIDs in `singleSRC` und `multiSRC`: kein Fehler, anderer Fingerabdruck bei anderer Datei (Task 1).
5. Ein Wert `0` in einem Kernfakt zählt als Inhalt, nicht als leer (Task 1 und 3).

## File Structure

| Datei | Aufgabe |
|---|---|
| `src/Review/Fingerprint.php` | Fingerabdruck aus Eintrag und Elementbaum (rein) |
| `src/Review/EntryContentLoader.php` | Eintrag und Elementbaum aus der Datenbank |
| `src/Review/ReviewStatus.php`, `ReviewState.php`, `PublicReview.php` | Ergebnisobjekte |
| `src/Review/ReviewEvaluator.php` | welcher Zustand gilt (rein) |
| `src/Review/ReviewInput.php` | Formularregeln (rein) |
| `src/Review/ReviewRepository.php` | Tabelle lesen, einfügen, löschen |
| `src/Review/ReviewService.php` | dokumentieren, „unwesentlich“, löschen, Cache leeren |
| `src/Controller/Backend/ReviewController.php` | Ansicht „Prüfungen“ |
| `src/Check/CheckLevel.php`, `CheckResult.php`, `CheckContext.php`, `ConformityChecks.php` | P-Prüfungen (rein) |
| `src/Check/CheckContextFactory.php` | Prüfkontext aus Datenbank und Contao |
| `src/EventListener/DataContainer/ReviewStatusListener.php` | Ergebnisliste und Listenzustand |
| `contao/dca/tl_grounding_page_review.php` | Tabellenschema |
| `contao/languages/{de,en}/tl_grounding_page_review.php` | Texte der Ansicht |
| `contao/templates/backend/grounding_page_reviews.html.twig`, `grounding_page_checks.html.twig` | Backend-Ausgabe |
| `tests/acceptance/review.php` | Abnahme der neuen Funktionen |

---

### Task 1: Fingerabdruck

**Files:**
- Create: `src/Review/Fingerprint.php`, `src/Review/EntryContentLoader.php`
- Test: `tests/Review/FingerprintTest.php`

**Interfaces:**
- Produces: `Fingerprint::of(array $entry, array $elements): string` (statisch, Format `1:<sha256 hex>`). `$entry` ist eine Zeile aus `tl_grounding_page`; `$elements` eine Liste von `tl_content`-Zeilen, jede optional mit `children` (gleiche Struktur).
- Produces: `EntryContentLoader::__construct(Doctrine\DBAL\Connection $connection)`, `load(int $entryId): array` → `['entry' => array, 'elements' => array]`; Elemente nach `sorting, id`, verschachtelte über `ptable = 'tl_content'`, auch versteckte (das Filtern macht `Fingerprint`). Unbekannte ID wirft `\InvalidArgumentException`.

- [ ] **Step 1: Branch anlegen und Entwurf samt Plan committen**

```bash
git switch -c feat/review-protocol
```

```bash
git add docs && git commit -m "Add the design and plan for the review protocol"
```

- [ ] **Step 2: Tests schreiben**

```php
<?php

declare(strict_types=1);

namespace Kikmedia\GroundingPageBundle\Tests\Review;

use Kikmedia\GroundingPageBundle\Review\Fingerprint;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class FingerprintTest extends TestCase
{
    public function testHasAVersionedFormat(): void
    {
        self::assertMatchesRegularExpression('~^1:[0-9a-f]{64}$~', Fingerprint::of($this->entry(), []));
    }

    #[DataProvider('reviewedEntryFields')]
    public function testChangesWithReviewedEntryFields(string $field, mixed $value): void
    {
        self::assertNotSame(Fingerprint::of($this->entry(), []), Fingerprint::of($this->entry([$field => $value]), []));
    }

    public static function reviewedEntryFields(): iterable
    {
        yield ['title', 'Anders'];
        yield ['entityType', 'Product'];
        yield ['entityTypeCustom', 'Brand'];
        yield ['definition', 'Neu.'];
        yield ['placement', 'Neu.'];
        yield ['distinction', 'Neu.'];
        yield ['coreFacts', serialize([['value' => '2015', 'label' => 'Gegründet']])];
        yield ['sources', serialize([['value' => 'https://example.com/neu', 'label' => 'Bericht']])];
        yield ['relatedPages', serialize(['2', '4'])];
        yield ['addImage', '1'];
        yield ['singleSRC', str_repeat("\x01\xff", 8)];
    }

    public function testIgnoresPublicationAndTechnicalEntryFields(): void
    {
        $changed = $this->entry(['id' => 99, 'alias' => 'x', 'author' => 7, 'published' => 0, 'start' => '1', 'stop' => '2', 'tstamp' => 5, 'sorting' => 9, 'size' => serialize(['1', '', '']), 'claimConformity' => '1', 'lastReviewed' => '1']);

        self::assertSame(Fingerprint::of($this->entry(), []), Fingerprint::of($changed, []));
    }

    public function testRelatedEntriesCountRegardlessOfOrder(): void
    {
        self::assertSame(
            Fingerprint::of($this->entry(['relatedPages' => serialize(['2', '3'])]), []),
            Fingerprint::of($this->entry(['relatedPages' => serialize(['3', '2'])]), [])
        );
    }

    public function testFactOrderCounts(): void
    {
        $facts = [['value' => 'a', 'label' => 'A'], ['value' => 'b', 'label' => 'B']];

        self::assertNotSame(
            Fingerprint::of($this->entry(['coreFacts' => serialize($facts)]), []),
            Fingerprint::of($this->entry(['coreFacts' => serialize(array_reverse($facts))]), [])
        );
    }

    public function testZeroAsAFactValueIsContent(): void
    {
        self::assertNotSame(
            Fingerprint::of($this->entry(['coreFacts' => serialize([['value' => '', 'label' => 'Mängel']])]), []),
            Fingerprint::of($this->entry(['coreFacts' => serialize([['value' => '0', 'label' => 'Mängel']])]), [])
        );
    }

    public function testEmptyValuesAndEmptyRowsDoNotCount(): void
    {
        $empty = $this->entry(['placement' => null, 'coreFacts' => null]);
        $blank = $this->entry(['placement' => '', 'coreFacts' => serialize([['value' => '', 'label' => '']])]);

        self::assertSame(Fingerprint::of($empty, []), Fingerprint::of($blank, []));
    }

    public function testContaoFiveAndSixStorageGiveTheSameResult(): void
    {
        $five = Fingerprint::of(
            $this->entry(['title' => 'M&#38;M', 'definition' => '&#60;b&#62; &#123;&#123;x&#125;&#125;']),
            [$this->element(['text' => '<p>A &#40;B&#41;</p>', 'playerSize' => serialize(['1', '2']), 'newColumn' => ''])]
        );
        $six = Fingerprint::of(
            $this->entry(['title' => 'M&M', 'definition' => '<b> [{]x[}]']),
            [$this->element(['text' => '<p>A (B)</p>', 'jsonData' => json_encode(['playerSize' => serialize(['1', '2'])]), 'otherColumn' => 0])]
        );

        self::assertSame($five, $six);
    }

    public function testValuesMovedIntoJsonDataStillCount(): void
    {
        self::assertSame(
            Fingerprint::of($this->entry(), [$this->element(['playerCaption' => 'Film'])]),
            Fingerprint::of($this->entry(), [$this->element(['jsonData' => '{"playerCaption":"Film"}'])])
        );
    }

    public function testBinaryFileReferencesInElements(): void
    {
        $one = $this->element(['multiSRC' => serialize([str_repeat("\x00\xfe", 8)])]);
        $two = $this->element(['multiSRC' => serialize([str_repeat("\x00\xfd", 8)])]);

        self::assertNotSame(Fingerprint::of($this->entry(), [$one]), Fingerprint::of($this->entry(), [$two]));
    }

    public function testElementContentNestedContentAndOrderCount(): void
    {
        $a = $this->element(['id' => 1, 'text' => 'a']);
        $b = $this->element(['id' => 2, 'text' => 'b']);
        $base = Fingerprint::of($this->entry(), [$a, $b]);

        self::assertNotSame($base, Fingerprint::of($this->entry(), [$b, $a]));
        self::assertNotSame($base, Fingerprint::of($this->entry(), [$a]));
        self::assertNotSame($base, Fingerprint::of($this->entry(), [$a, $this->element(['text' => 'c'])]));
        self::assertNotSame($base, Fingerprint::of($this->entry(), [$a, $b + ['children' => [$this->element(['text' => 'innen'])]]]));
        self::assertNotSame($base, Fingerprint::of($this->entry(), [$a, $this->element(['text' => 'b', 'foreignExtensionField' => 'x'])]));
    }

    public function testHiddenElementsAndTheirChildrenDoNotCount(): void
    {
        $hidden = $this->element(['invisible' => '1', 'children' => [$this->element(['text' => 'innen'])]]);

        self::assertSame(Fingerprint::of($this->entry(), []), Fingerprint::of($this->entry(), [$hidden]));
    }

    public function testTechnicalAndLayoutElementFieldsDoNotCount(): void
    {
        $changed = $this->element(['id' => 8, 'pid' => 9, 'sorting' => 7, 'tstamp' => 6, 'start' => '1', 'stop' => '2', 'cssID' => serialize(['a', 'b']), 'customTpl' => 'x', 'protected' => '1', 'groups' => serialize(['1']), 'size' => serialize(['1', '', '']), 'floating' => 'left', 'fullsize' => '1']);

        self::assertSame(Fingerprint::of($this->entry(), [$this->element()]), Fingerprint::of($this->entry(), [$changed]));
    }

    private function entry(array $overrides = []): array
    {
        return $overrides + [
            'title' => 'VeloNova',
            'entityType' => 'Organization',
            'entityTypeCustom' => '',
            'definition' => 'Ein Hersteller.',
            'placement' => '',
            'distinction' => '',
            'coreFacts' => serialize([['value' => '2014', 'label' => 'Gegründet']]),
            'sources' => serialize([['value' => 'https://example.com', 'label' => 'Bericht']]),
            'relatedPages' => serialize(['2', '3']),
            'addImage' => '',
            'singleSRC' => null,
        ];
    }

    private function element(array $overrides = []): array
    {
        return $overrides + ['id' => 1, 'type' => 'text', 'invisible' => '', 'headline' => serialize(['unit' => 'h2', 'value' => '']), 'text' => '<p>Text</p>'];
    }
}
```

- [ ] **Step 3: UNIT ausführen** – erwartet: Fehler „Class "…\Review\Fingerprint" not found“.

- [ ] **Step 4: `Fingerprint` implementieren**

Konstanten `ENTRY_FIELDS` = `title, entityType, entityTypeCustom, definition, placement, distinction, coreFacts, sources, relatedPages, addImage, singleSRC` (Positivliste) und `IGNORED_ELEMENT_FIELDS` = `id, pid, ptable, sorting, tstamp, invisible, start, stop, protected, groups, guests, cssID, space, customTpl, size, floating, fullsize, imagemargin, perRow, perPage, galleryTpl, playerSize, playerAspect, playerColor, sliderDelay, sliderSpeed, sliderStartSlide, sliderContinuous, com_order, com_perPage, com_moderate, com_bbcode, com_requireLogin, com_disableCaptcha, com_template, children`.

Regeln in dieser Reihenfolge:

1. Elementzeilen mit `invisible` ungleich leer/`0` entfallen samt Kindern.
2. Ist `jsonData` ein JSON-Objekt, wandern dessen Schlüssel in die Zeile; `jsonData` selbst entfällt. Danach die ignorierten Spalten entfernen.
3. Wert vereinheitlichen, rekursiv: Zeichenkette der Form `^a:\d+:\{` wird mit `unserialize(..., ['allowed_classes' => false])` entpackt; kein gültiges UTF-8 wird zu `hex:` + `bin2hex`; sonst `[{]`→`{{`, `[}]`→`}}`, `[&]`→`&`, `[lt]`→`<`, `[gt]`→`>`, `[nbsp]`→`&nbsp;`, `[-]`→`&shy;`, dann `html_entity_decode` (`ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5`) wiederholen, bis sich nichts mehr ändert. Zahlen und Booleans werden Zeichenketten.
4. In Arrays entfallen `null`, `''` und leere Arrays; Listen werden neu nummeriert, sonst `ksort`.
5. Auf oberster Ebene einer Zeile entfallen zusätzlich `'0'`.
6. `relatedPages` wird sortiert.
7. `sha256` über `json_encode` (`JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE`) von Eintrag und Elementbaum, Präfix `1:`.

- [ ] **Step 5: `EntryContentLoader` implementieren** (Signatur oben; wird in Task 4 und 5 durch den Abnahmetest geprüft).

- [ ] **Step 6: UNIT und STATIC ausführen** – beide bestanden.

- [ ] **Step 7: Commit** `Add the content fingerprint for reviews`

---

### Task 2: Prüfregeln

**Files:**
- Create: `src/Review/ReviewStatus.php`, `PublicReview.php`, `ReviewState.php`, `ReviewEvaluator.php`, `ReviewInput.php`
- Test: `tests/Review/ReviewEvaluatorTest.php`, `tests/Review/ReviewInputTest.php`

**Interfaces:**
- Produces:
  - `enum ReviewStatus: string { Current = 'current'; Stale = 'stale'; Rejected = 'rejected'; Missing = 'missing'; }`
  - `final readonly class PublicReview(string $date, string $reviewerType, string $reviewerName)`; `$date` ist `Y-m-d`.
  - `final readonly class ReviewState(ReviewStatus $status, ?PublicReview $review = null, int $approvedId = 0)` mit `minorAllowed(): bool` (Status `Stale` und `review` gesetzt) und `visibleReview(): ?PublicReview` (nur bei `Current`).
  - `ReviewEvaluator::evaluate(array $records, string $fingerprint): ReviewState` (statisch). Ein Datensatz ist ein Array mit `id, kind ('review'|'minor'), reference, reviewDate, reviewerType, reviewerName, approved, fingerprint`.
  - `final readonly class ReviewInput(string $date, string $reviewerType, string $reviewerName, array $checks, ?bool $approved, string $note)`, `const CHECKS = ['accuracy', 'timeliness', 'verifiability', 'identification', 'consistency']`, `const REVIEWER_TYPES = ['Person', 'Organization']`, `errors(string $today, ?string $lastDate): array` (Liste von Fehlerschlüsseln).

- [ ] **Step 1: Tests schreiben**

`ReviewEvaluatorTest`, Hilfsmethode `record(int $id, array $overrides = [])` mit `kind=review, reference=0, reviewDate=2026-11-01, reviewerType=Person, reviewerName=Erika Muster, approved=1, fingerprint=1:a`:

| Test | Datensätze, aktueller Fingerabdruck | Erwartet |
|---|---|---|
| `testWithoutRecordsTheReviewIsMissing` | `[]`, `1:a` | `Missing`, `review` null |
| `testMatchingApprovalIsCurrent` | `[record(1)]`, `1:a` | `Current`, Datum `2026-11-01`, Name `Erika Muster`, `approvedId` 1 |
| `testChangedContentIsStale` | `[record(1)]`, `1:b` | `Stale`, `minorAllowed()` true, `visibleReview()` null |
| `testRevertedContentIsCurrentAgain` | wie oben, wieder `1:a` | `Current` |
| `testTheLatestRecordDecides` | `[record(2, approved=0), record(1)]`, `1:a` | `Rejected`, `minorAllowed()` false, `review` null |
| `testOrderOfTheListDoesNotMatter` | `[record(1), record(2, approved=0)]` | `Rejected` |
| `testMinorConfirmationKeepsOriginalReviewerAndDate` | `[record(1), record(2, kind=minor, reference=1, reviewDate='', reviewerName='', fingerprint=1:b)]`, `1:b` | `Current`, Datum `2026-11-01`, Name `Erika Muster`, `approvedId` 1 |
| `testMinorConfirmationBecomesStaleOnTheNextChange` | wie oben, `1:c` | `Stale`, `minorAllowed()` true |
| `testOnlyTheLatestFingerprintCounts` | wie oben, `1:a` | `Stale` |
| `testMinorWithoutItsApprovalIsStale` | nur der Minor-Datensatz, `1:b` | `Stale`, `review` null, `minorAllowed()` false |
| `testMinorPointingToARejectionIsStale` | `[record(1, approved=0), minor(reference=1, fp 1:b)]`, `1:b` | `Stale`, `review` null |

`ReviewInputTest`, gültige Basis: `date=2026-11-20, reviewerType=Person, reviewerName=Erika Muster, checks=CHECKS, approved=true, note=''`, Aufruf `errors('2026-11-27', '2026-11-01')`:

| Abweichung | Erwartete Fehler |
|---|---|
| keine | `[]` |
| keine, `lastDate` null bzw. `2026-11-20` | `[]` |
| `date=''` oder `2026-02-30` oder `20.11.2026` | `['date_invalid']` |
| `date=2026-11-28` | `['date_future']` |
| `date=2026-10-31` | `['date_before_last']` |
| `reviewerType=Thing` | `['reviewer_type']` |
| `reviewerName='  '` oder 256 Zeichen | `['reviewer_name']` |
| Name mit `<`, `>`, `{{`, `}}`, `&amp;` oder `&#38;` | `['reviewer_name_chars']` |
| Name `Müller & Söhne "O'Neil"` | `[]` |
| `approved=null` | `['result']` |
| nur vier `checks`, auch mit Fremdwert aufgefüllt | `['checks_incomplete']` |
| `approved=false, checks=[], note='Zahl falsch'` | `[]` |
| `approved=false, note=' '` | `['note_required']` |

- [ ] **Step 2: UNIT** – erwartet: Klassen fehlen.
- [ ] **Step 3: Klassen implementieren.** „Zuletzt gespeichert“ ist die höchste `id`.
- [ ] **Step 4: UNIT und STATIC** – bestanden.
- [ ] **Step 5: Commit** `Add the rules for review records`

---

### Task 3: Konformitätsprüfungen

**Files:**
- Create: `src/Check/CheckLevel.php`, `CheckResult.php`, `CheckContext.php`, `ConformityChecks.php`
- Test: `tests/Check/ConformityChecksTest.php`

**Interfaces:**
- Consumes: `ReviewState`, `ReviewStatus`, `PublicReview` (Task 2).
- Produces:
  - `enum CheckLevel: string { Required = 'required'; Hint = 'hint'; Info = 'info'; }`
  - `final readonly class CheckResult(string $requirement, CheckLevel $level, bool $passed, string $message, array $parameters = [])`
  - `final readonly class CheckContext(array $entry, array $elements, ReviewState $review, ?array $page, bool $imageAltMissing, bool $archiveListed, int $now)`; `$page` ist `['published' => bool, 'protected' => bool, 'noindex' => bool, 'language' => string]` oder null.
  - `ConformityChecks::run(CheckContext $context): array` (Liste `CheckResult`), `requiredPassed(array $results): bool`, `statementUrl(string $language): string` (alle statisch).

Ergebnisse (je Zeile genau ein Eintrag, `message` in Klammern):

| Bezug | Stufe | bestanden, wenn |
|---|---|---|
| P-2 (`p2`) | Required | Definition nach Trimmen nicht leer |
| P-3 (`p3`) | Required | jede H2 sichtbarer Elemente, auch verschachtelter, enthält den Entitätsnamen; ohne H2 bestanden. H2 = `headline` mit `unit` `h2` und nicht leerem `value`, sowie `<h2>` in `text`, `html`, `unfilteredHtml`. Beide Seiten ohne Tags, Entitäten dekodiert, Leerraum inkl. geschütztem zusammengefasst, `mb_stripos` |
| P-4 (`p4`) | Required | ein Kernfakt mit nicht leerer Bezeichnung und Wert ungleich `''` |
| P-5 (`p5`) | Required | wirksamer Typ (bei `other` der eigene) nicht leer und ohne Beachtung der Schreibung weder `Thing` noch `WebPage` |
| P-7 (`p7`) | Required | `published`, `start`/`stop` passen zu `now`, Seite vorhanden, veröffentlicht, nicht geschützt, nicht `noindex` |
| P-8 (`p8`) | Required | Status `Current` |
| P-6 (`p6_alt`) | Hint | `imageAltMissing` false |
| P-11 (`p11`) | Hint | Abgrenzung nicht leer |
| P-12 (`p12`) | Hint | mindestens eine nicht leere Quellenzeile und jede hat einen Wert, der mit `http://` oder `https://` beginnt |
| P-15 (`p15`) | Hint | Seite vorhanden und `archiveListed` |
| P-16 (`p16` bzw. `p16_late`) | Hint | kein freigegebenes Datum oder höchstens 6 Monate alt; `p16_late` ab älter als 12 Monate |
| P-6, P-9, P-10 (`p6`, `p9`, `p10`) | Info | immer |
| – (`legacy_date`) | Info | nur vorhanden, wenn `lastReviewed` gefüllt ist; Parameter: Datum `Y-m-d` über `ReviewDate::iso` |

`statementUrl`: beginnt die Sprache mit `de` → `https://groundingpage.com/spec/v1.7/de/`, sonst `https://groundingpage.com/spec/v1.7/`.

- [ ] **Step 1: Tests schreiben.** Hilfsmethode `context(array $overrides = [])` mit einem Kontext, der alles besteht: Titel `VeloNova`, Definition gefüllt, ein Fakt, eine `https`-Quelle, Abgrenzung, `published=1`, ein Element mit H2 `Über VeloNova`, Status `Current` mit Datum `2026-11-01`, Seite veröffentlicht und indexierbar, Sprache `de`, `now = strtotime('2026-11-27 12:00')`.

```php
public function testACompletePagePasses(): void
{
    $results = ConformityChecks::run($this->context());

    self::assertTrue(ConformityChecks::requiredPassed($results));
    self::assertSame([], array_values(array_filter($results, static fn (CheckResult $r): bool => !$r->passed)));
}
```

Je ein Datenprovider-Fall, der genau den genannten Bezug scheitern lässt (`requiredPassed` false bei Pflicht, true bei Hinweis): Definition `'  '`; H2 `Fragen` im `headline`; `<h2 class="x">Preise</h2>` in `text`; solche H2 in einem verschachtelten Element; Fakten leer; Fakt ohne Bezeichnung; Typ `Thing`; Typ `webpage` als eigener; `other` ohne eigenen Typ; `published=0`; `start` morgen; `stop` gestern; Seite null; Seite unveröffentlicht; geschützt; `noindex`; Status `Stale`, `Rejected`, `Missing`; `imageAltMissing`; Abgrenzung leer; keine Quelle; Quelle `www.example.com`; Quelle mit Bezeichnung ohne Wert; `archiveListed` false; Datum `2026-05-01` (`p16`); Datum `2025-11-01` (`p16_late`).

Zusätzlich bestanden: H2 `ÜBER VELONOVA`; H2 in einem versteckten Element ohne Namen; `<h3>Preise</h3>`; Titel `M&#38;M` mit H2 `Über M&M`; Fakt mit Wert `0`; Datum `2026-05-27`. `legacy_date` fehlt ohne `lastReviewed` und trägt bei `lastReviewed = strtotime('2026-03-05')` den Parameter `2026-03-05`. `statementUrl('de-CH')` und `statementUrl('fr')` liefern die genannten Adressen.

- [ ] **Step 2: UNIT** – erwartet: Klassen fehlen.
- [ ] **Step 3: Klassen implementieren.**
- [ ] **Step 4: UNIT und STATIC** – bestanden.
- [ ] **Step 5: Commit** `Add the conformity checks for Standard 1.7`

---

### Task 4: Prüfprotokoll im Backend

**Files:**
- Create: `src/Review/ReviewRepository.php`, `src/Review/ReviewService.php`, `src/Controller/Backend/ReviewController.php`, `contao/dca/tl_grounding_page_review.php`, `contao/languages/de/tl_grounding_page_review.php`, `contao/languages/en/tl_grounding_page_review.php`, `contao/templates/backend/grounding_page_reviews.html.twig`, `tests/acceptance/review.php`
- Modify: `contao/config/config.php`, `contao/dca/tl_grounding_page.php`, `contao/dca/tl_grounding_page_archive.php`, `contao/dca/tl_user_group.php`, `contao/dca/tl_user.php`, `contao/languages/{de,en}/{default,tl_grounding_page,tl_grounding_page_archive}.php`, `src/Security/GroundingPagePermissions.php`, `tests/acceptance/{check,lib,seed}.php`

**Interfaces:**
- Consumes: Task 1 und 2.
- Produces:
  - `GroundingPagePermissions::REVIEW = 'contao_user.groundingPagep.review'`
  - `ReviewRepository::findByEntry(int $entryId): array` (neueste zuerst), `insert(array $record): int`, `delete(int $id, int $entryId): void`
  - `ReviewService::state(int $entryId): ReviewState`, `fingerprint(int $entryId): string`, `records(int $entryId): array`, `document(int $entryId, ReviewInput $input, string $expectedFingerprint, int $userId, string $userName): array`, `confirmMinor(int $entryId, string $note, string $expectedFingerprint, int $userId, string $userName): array`, `delete(int $entryId, int $recordId): void`. `document` und `confirmMinor` liefern Fehlerschlüssel, `[]` heißt gespeichert; zusätzliche Schlüssel `content_changed` und `minor_not_allowed`, für „unwesentlich“ außerdem `note_required`.
  - `ReviewController::run(): Symfony\Component\HttpFoundation\Response|string`

**Festlegungen:**

- Spalten: `id`, `pid` (index), `tstamp`, `kind` (string 8, `review`), `reference` (uint 0), `reviewDate` (string 10), `reviewerType` (string 16), `reviewerName` (string 255), `approved` (boolean false), `checks` (string 128, kommagetrennt), `note` (text null), `userId` (uint 0), `userName` (string 255), `standardVersion` (string 8), `fingerprint` (string 80).
- DCA: `dataContainer` `DC_Table`, `ptable` `tl_grounding_page`, `closed`, `notEditable`, `notDeletable`, `notCopyable`, `notSortable`, `doNotCopyRecords` je `true`; Felder nur mit `sql`.
- `tl_grounding_page` `ctable`: `['tl_content', 'tl_grounding_page_review']` in dieser Reihenfolge. Die Tabelle kommt nicht in `BE_MOD…['tables']`.
- `config.php`: `$GLOBALS['BE_MOD']['content']['grounding_page']['reviews'] = [ReviewController::class, 'run'];`; die Klasse trägt `#[Autoconfigure(public: true)]`.
- Operation `'reviews' => ['href' => 'key=reviews', 'icon' => 'ok.svg']` nach `children`.
- Rechteoption `review`: „Prüfung dokumentieren“ / „Document reviews“; Feldbeschreibung `groundingPagep` entsprechend ergänzen.
- Archivfelder `reviewerType` (select `Person`, `Organization`, `includeBlankOption`) und `reviewerName` (text, 255), beide `exclude`, Palette `{review_legend},reviewerType,reviewerName`, Label „Standard-Prüfer: Art / Name“ / „Default reviewer: type / name“.
- Zugriff: Eintrag aus `id` der Anfrage; fehlt er oder `isGranted(EDIT_ARCHIVE, pid)` ist false → `Contao\CoreBundle\Exception\AccessDeniedException`. Dokumentieren und „unwesentlich“ verlangen `REVIEW`, Löschen einen Administrator; jeweils beim POST geprüft, nicht nur in der Ausgabe. Der Benutzer (ID, Name) kommt aus der Sitzung, nie aus dem Formular.
- Eingaben über das Symfony-Request-Objekt, getrimmt, unverändert gespeichert. `userName` ist der Name des Benutzers.
- Formulare: `method="post"`, `data-turbo="false"`, versteckt `REQUEST_TOKEN` (`ContaoCsrfTokenManager::getDefaultTokenValue()`), `FORM_SUBMIT`, `fingerprint`. `FORM_SUBMIT`: `tl_grounding_page_review` (Felder `date` als `type="date"`, `reviewerType`, `reviewerName`, `checks[]`, `result` = `approved`|`rejected`, `note`), `tl_grounding_page_review_minor` (`note`), `tl_grounding_page_review_delete` (`record`). `result` ist nicht vorausgewählt, kein Häkchen vorab gesetzt.
- Vorbelegung: Datum heute; Prüfer aus dem neuesten Datensatz mit `kind=review`, sonst Archivfelder, sonst leer. `lastDate` für `ReviewInput::errors` ist `reviewDate` dieses Datensatzes.
- Nach Erfolg `Message::addConfirmation` und Weiterleitung auf dieselbe Adresse; bei Fehlern Ansicht mit Meldungen und erhaltenen Eingaben.
- Ein „unwesentlich“-Datensatz hat `kind` `minor`, `reference` = `approvedId`, `approved` false, `reviewDate`, `reviewerType`, `reviewerName` und `checks` leer; `tstamp`, Benutzer, `standardVersion`, `fingerprint` und Notiz wie bei jeder Prüfung.
- Nach jedem Schreiben `fos_http_cache.cache_manager` (optional injiziert) → `invalidateTags(['contao.db.tl_grounding_page.<id>'])`.
- Tabelle der Ansicht: Datum, Prüfer, dokumentiert von, Ergebnis, Zustand, Notiz; „unwesentlich“-Zeilen zeigen Datum und Prüfer der referenzierten Prüfung; nur die neueste Zeile hat den Zustand `aktuell`/`veraltet`/`nicht freigegeben`, ältere `abgelöst`.
- Texte: Operation „Prüfungen“ / „Reviews“ (`tl_grounding_page.reviews`, zweiter Wert „Prüfungen des Eintrags ID %s“ / „Reviews of entry ID %s“); fünf Prüfpunkte „Faktische Richtigkeit“, „Aktualität inkl. Stand-Daten“, „Nachprüfbarkeit und Quellenqualität“, „Eindeutige Entität und Abgrenzung“, „Übereinstimmung mit strukturierten Daten, Seitentitel, Meta-Beschreibung und Alt-Texten“; Ergebnis „freigegeben“ / „nicht freigegeben“; Knopf „Änderung als unwesentlich bestätigen“ mit Hinweis „Nach dem Standard unwesentlich sind Tippfehler, Layout, Umformulierungen ohne Faktenänderung und der Versionshinweis.“; `content_changed` „Der Inhalt wurde seit dem Öffnen des Formulars geändert. Bitte erneut prüfen.“ Englisch sinngleich, je Fehlerschlüssel ein Satz.

- [ ] **Step 1: Abnahmetest schreiben**

`lib.php`: `submit(Browser $browser, string|null $url, array $changes, string $form = '')` – mit `$form` wird das Formular mit genau diesem `FORM_SUBMIT`-Wert gewählt.

`seed.php`: `tl_grounding_page_review` in die Löschliste; Eintrag 50 `Pruefling` (Archiv 1, `definition` „ist ein Testobjekt.“, `distinction` „Nicht zu verwechseln.“, Fakt `Gegründet` → `2020`, Quelle `https://example.com/pruefling`, `published` 1); Elemente 50 (`text`, `<h2>Über Pruefling</h2><p>Inhalt</p>`), 51 (`element_group` am Eintrag), 52 (`text`, `pid` 51, `ptable` `tl_content`, `<p>Verschachtelt</p>`); Seite 12 zusätzlich `includeCache` 1, `cache` 3600.

`check.php`: `require __DIR__ . '/review.php';` nach `editor.php`.

`review.php`:

```php
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
check('review: editor cannot delete a record', 1 === $count(50));
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
```

- [ ] **Step 2: ACC** – erwartet: `FAIL review: operation in entry list` und folgende (Seed bricht vorher ab, solange die Tabelle fehlt; das zählt ebenfalls als erwarteter Fehlschlag).
- [ ] **Step 3: DCA, Rechte, Archivfelder, Sprachdateien anlegen** wie festgelegt.
- [ ] **Step 4: `ReviewRepository`, `ReviewService`, `ReviewController`, Template implementieren.**
- [ ] **Step 5: ACC, UNIT, STATIC** – bestanden.
- [ ] **Step 6: Commit** `Add the review protocol to the back end`

---

### Task 5: Prüfhinweis im Frontend

**Files:**
- Modify: `src/Controller/FrontendModule/GroundingPageReaderController.php`, `src/Util/JsonLdBuilder.php`, `contao/templates/frontend_module/grounding_page_reader.html.twig`, `contao/dca/tl_grounding_page.php`, `contao/languages/{de,en}/{default,tl_grounding_page}.php`, `tests/Util/JsonLdBuilderTest.php`, `tests/acceptance/{frontend,backend,review}.php`

**Interfaces:**
- Consumes: `ReviewService::state()`, `ReviewState::visibleReview()`.
- Produces: `JsonLdBuilder::reviewProperties(PublicReview $review): array`; Template-Variable `review` = `null` oder `['reviewer' => string, 'date' => string, 'dateIso' => string]`.

**Festlegungen:** `ReviewService` per Konstruktor in den Reader. `tagResponse('contao.db.tl_grounding_page.' . $entry->id)`. `lastReviewed`/`lastReviewedIso` entfallen aus Controller und Template; die Liste darunter zeigt nur noch „Zuletzt aktualisiert“. Der Hinweis steht als `<p>` nach den Quellen und vor dem JSON-LD, Datum im Format `Config::get('dateFormat')`, Schlüssel `MSC.grounding_page_review_notice`. `MSC.grounding_page_reviewed` entfällt. Das DCA-Feld `lastReviewed` behält nur `eval.doNotCopy` und `sql` (kein `inputType`, nicht in der Palette, keine Beschriftung).

- [ ] **Step 1: Unit-Test ergänzen**

```php
public function testDescribesADocumentedReview(): void
{
    self::assertSame(
        ['lastReviewed' => '2026-11-20', 'reviewedBy' => ['@type' => 'Organization', 'name' => 'Müller & Söhne']],
        JsonLdBuilder::reviewProperties(new PublicReview('2026-11-20', 'Organization', 'Müller & Söhne'))
    );
}
```

- [ ] **Step 2: Abnahmetest anpassen und erweitern**

`frontend.php`: `reader: review date as time element` → `reader: old review date is not shown` (weder `2026-03-05` noch „Zuletzt geprüft“); `english: date labels` → „Last updated“ vorhanden, „Last reviewed“ nicht; `reader: review date without core facts` → `/fakt/nachbar` ohne `2026-02-03`. `backend.php`: die Prüfung `admin: saved entry keeps review date` durch `admin: entry form has no old review field` (`!str_contains(Formular, 'name="lastReviewed"')`) ersetzen.

Ans Ende von `review.php`:

```php
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

submit($admin, $entryForm, []);
check('notice: stays after saving the entry unchanged', $shown());
submit($admin, '/contao?do=grounding_page&table=tl_content&act=edit&id=50', []);
check('notice: stays after saving an element unchanged', $shown());

foreach ([
    'changed element' => ["UPDATE tl_content SET text=CONCAT(text, '<p>Neu</p>') WHERE id=50", "UPDATE tl_content SET text=REPLACE(text, '<p>Neu</p>', '') WHERE id=50"],
    'changed nested element' => ["UPDATE tl_content SET text=CONCAT(text, '<p>Neu</p>') WHERE id=52", "UPDATE tl_content SET text=REPLACE(text, '<p>Neu</p>', '') WHERE id=52"],
    'hidden element' => ['UPDATE tl_content SET invisible=1 WHERE id=52', "UPDATE tl_content SET invisible=0 WHERE id=52"],
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
check('notice: gone after a changed fact', !$shown());

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
```

- [ ] **Step 3: UNIT und ACC** – erwartet: `reviewProperties` fehlt; im Abnahmetest scheitern die `notice:`-Prüfungen.
- [ ] **Step 4: JSON-LD, Controller, Template, Sprachen, DCA implementieren.**
- [ ] **Step 5: UNIT, ACC, STATIC** – bestanden. Scheitert nur `cache: page is served from the cache`, zuerst klären, ob der Testserver den HTTP-Cache nutzt (Ursache vor Änderung); die Prüfung nicht ersatzlos streichen.
- [ ] **Step 6: Commit** `Show the documented review on the page`

---

### Task 6: Prüfergebnisse und Konformitätsangabe

**Files:**
- Create: `src/Check/CheckContextFactory.php`, `src/EventListener/DataContainer/ReviewStatusListener.php`, `contao/templates/backend/grounding_page_checks.html.twig`
- Modify: `contao/dca/tl_grounding_page.php`, `src/Controller/FrontendModule/GroundingPageReaderController.php`, `contao/templates/frontend_module/grounding_page_reader.html.twig`, `contao/languages/{de,en}/tl_grounding_page.php`, `tests/acceptance/review.php`

**Interfaces:**
- Consumes: `ConformityChecks`, `CheckContext` (Task 3), `ReviewService`, `EntryContentLoader`.
- Produces: `CheckContextFactory::create(int $entryId): CheckContext`; Template-Variable `conformityUrl` = `?string`.

**Festlegungen:**

- Factory: Seite = `tl_grounding_page_archive.jumpTo` → `PageModel::findById()` + `loadDetails()`; `published` berücksichtigt `start`/`stop`; `noindex` = `robots` beginnt mit `noindex`. `imageAltMissing` nur bei `addImage` und `singleSRC` und vorhandener Seite: `FilesModel::findByUuid()->getMetadata(<Sprache mit _>, <erste zwei Buchstaben>)?->getAlt()` leer oder Datei fehlt. `archiveListed` = ein `tl_module` vom Typ `grounding_page_list` führt das Archiv in `grounding_page_archives`.
- Neues Feld `claimConformity`: checkbox, `exclude`, `eval.doNotCopy`, boolean false; „Konformität mit Version 1.7 angeben“ / „State conformity with version 1.7“.
- Neues Feld `groundingPageChecks` ohne `sql`, `exclude => false`, `eval.doNotShow`; Ausgabe per `#[AsCallback(table: 'tl_grounding_page', target: 'fields.groundingPageChecks.input_field')]`. Erste Palettengruppe: `{check_legend},groundingPageChecks,claimConformity;`; `review_legend` entfällt.
- Ergebnisliste: Pflicht immer mit „erfüllt“/„offen“, Hinweise nur wenn nicht bestanden, Infos als Information; Überschrift nennt „Stand: zuletzt gespeichert“. Je `message`-Schlüssel ein Satz unter `$GLOBALS['TL_LANG']['tl_grounding_page']['checks']` in beiden Sprachen, beginnend mit dem Bezug (`P-3: Jede H2-Überschrift nennt den Entitätsnamen.`); `legacy_date`: „Altes Prüfdatum %s: ohne Protokoll, wird nicht angezeigt.“
- Listenzustand per `#[AsCallback(table: 'tl_grounding_page', target: 'list.label.label')]`: `$label . ' <span class="label-info">…</span>'` mit „Prüfung aktuell“, „Prüfung veraltet“, „Prüfung nicht freigegeben“, „Prüfung fehlt“.
- Reader: nur wenn `claimConformity` und `visibleReview()` gesetzt, Kontext bauen; bei `requiredPassed` `conformityUrl = statementUrl($request->getLocale())`. Ausgabe direkt nach dem Hinweis: `<p><a href="{{ conformityUrl }}">Grounding Page Standard 1.7</a></p>`.

- [ ] **Step 1: Abnahmetest ans Ende von `review.php`**

```php
$de = 'href="https://groundingpage.com/spec/v1.7/de/"';
$claimed = static fn (): bool => $visitor->get('/fakt/pruefling')->contains($de);
$state = static fn (string $label) => check("list: shows $label", $admin->get('/contao?do=grounding_page&table=tl_grounding_page&id=1')->contains($label));

$state('Prüfung nicht freigegeben');
$db->prepare('UPDATE tl_grounding_page SET coreFacts=? WHERE id=50')->execute([$saved]);
$document($editor, 50);
$state('Prüfung aktuell');
$state('Prüfung fehlt');

check('conformity: not without the checkbox', $shown() && !$claimed());
submit($admin, $entryForm, ['claimConformity' => '1']);
check('conformity: shown with checkbox, review and passing checks', $shown() && $claimed() && $visitor->get('/fakt/pruefling')->contains('Grounding Page Standard 1.7'));

$sql("UPDATE tl_content SET text='<h2>Preise</h2>' WHERE id=50");
check('conformity: not on an outdated review', !$claimed());
$state('Prüfung veraltet');
$document($editor, 50);
check('conformity: not while a required check fails', $shown() && !$claimed());
$form = open($admin, 'checks: entry form', $entryForm, 'P-3');
check('checks: failing requirement is marked open', 1 === preg_match('~offen.{0,200}P-3|P-3.{0,200}offen~s', strip_tags($form)));
check('checks: old review date is named', str_contains($admin->get('/contao?do=grounding_page&table=tl_grounding_page&act=edit&id=1')->body, 'ohne Protokoll'));

$sql("UPDATE tl_content SET text='<h2>Über Pruefling</h2><p>Inhalt</p>' WHERE id=50");
$document($editor, 50);
check('conformity: back once everything passes', $claimed());

$db->exec('UPDATE tl_grounding_page SET claimConformity=1 WHERE id=40');
$document($admin, 40);
check('conformity: English link', $visitor->get('/en/fact/veloen')->contains('href="https://groundingpage.com/spec/v1.7/"'));
```

- [ ] **Step 2: ACC** – erwartet: `list:`, `conformity:` und `checks:` scheitern.
- [ ] **Step 3: Factory, Listener, Template, DCA, Sprachen, Reader implementieren.**
- [ ] **Step 4: ACC, UNIT, STATIC** – bestanden.
- [ ] **Step 5: Commit** `Show check results and the conformity statement`

---

### Task 7: Dokumentation und Abschluss

**Files:**
- Modify: `README.md`, `CHANGELOG.md`, `.gitattributes`, `src/Util/ReviewDate.php`, `tests/Util/ReviewDateTest.php`

- [ ] **Step 1: README** – Funktionsumfang (1.7 umgesetzt, 2.0 offen, Issue #5); Zeile „Quellen und Aktualität“ auf Prüfprotokoll; neuer Abschnitt „Faktenprüfung“ (Ablauf, Rechte, Hinweis, „unwesentlich“, Konformitätsangabe, Pflichtprüfungen und Hinweise); „Nicht erkannt“: Dateimetadaten wie Alt-Texte, eingebundene Module und referenzierte Elemente, Titel verwandter Einträge, Templates; Löschen eines Datensatzes durch Administratoren macht den vorherigen wieder maßgeblich; Bundle- oder Contao-Updates mit neuen Spalten mit Vorgabewert machen Prüfungen veraltet; Berechtigungsschritt „Prüfung dokumentieren“.
- [ ] **Step 2: CHANGELOG** – Abschnitt `## [0.2.0] – unveröffentlicht` mit „Hinzugefügt“ und „Geändert“, darunter ausdrücklich: „Zuletzt geprüft am“ verschwindet aus Formular und Frontend (Daten bleiben gespeichert), Template-Variablen `lastReviewed`/`lastReviewedIso` entfallen, `contao:migrate` erforderlich.
- [ ] **Step 3:** `.gitattributes` um `/docs export-ignore` ergänzen; die Kommentare in `ReviewDate.php` und `ReviewDateTest.php` entfernen.
- [ ] **Step 4: Prüfen** – `git archive HEAD | tar -t | grep -c '^docs/'` nach dem Commit ergibt `0`; `grep -rnE '^\s*(//|/\*|\*)' src contao tests` ohne Treffer; MATRIX, UNIT, STATIC bestanden.
- [ ] **Step 5: Commit** `Document the review protocol`
- [ ] **Step 6:** Container stoppen, Ergebnis berichten. Push, Pull Request, Merge und Release erst nach Zustimmung.
