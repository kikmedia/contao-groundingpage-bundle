# Changelog

Das Format orientiert sich an [Keep a Changelog](https://keepachangelog.com/de/1.0.0/).

## [Unreleased]

### Added
- Root-Page-Filter im `SitemapListener`: Einträge erscheinen nur noch in der Sitemap des Website-Root, zu dem ihre Weiterleitungsseite gehört (`PageModel::rootId` vs. `SitemapEvent::getRootPageIds()`).
- Content-Element „Grounding-Page FAQ“ (`grounding_page_faq`): Frage/Antwort-Paare (`faqItems`, `optionWizard`), gerendert als `<dl>`-Liste.
- `JsonLdBuilder::buildFaqPage()` und Integration im Reader: Enthält ein Eintrag mindestens ein vollständiges FAQ-Element, wird zusätzlich zum Entitäts-JSON-LD ein `https://schema.org/FAQPage`-Block mit `mainEntity` (`Question`/`acceptedAnswer`) ausgegeben.
- Twig-Templates unter `templates/frontend_module/` und `templates/content_element/` als Äquivalent zu den bestehenden PHP-Templates (Liste, Reader, FAQ-Content-Element).
- PHPUnit-Testsuite (`tests/`) für `JsonLdBuilder` und `GroundingPageFaqController::extractItems()`, PHPStan (Level 3, `phpstan.neon.dist`) und PHP-CS-Fixer (`.php-cs-fixer.dist.php`), inkl. `composer test`/`phpstan`/`cs-check`/`cs-fix` Scripts.
- CI-Jobs für PHPUnit, PHPStan und PHP-CS-Fixer in `.github/workflows/ci.yml`.

### Changed
- Paketname von `grounding/page-bundle` auf `kikmedia/contao-groundingpage-bundle` geändert, PHP-Namespace von `Grounding\PageBundle\` auf `Kikmedia\GroundingPageBundle\`, `composer.json`-Metadaten (`homepage`, `support`) auf das tatsächliche GitHub-Repository umgestellt.

### Fixed
- Kritisch: `BE_USER_LOGGED_IN` (in Contao 5 entfernt) durch `System::getContainer()->get('contao.security.token_checker')->hasBackendUser()` ersetzt – der Konstante hätte in Contao 5/6 zu einem Fatal Error geführt.
- Kritisch: `\Database::getInstance()` (globaler Namespace, existiert in Contao 5/6 nicht) durch `Contao\Database::getInstance()` ersetzt.
- Kritisch: `new ContentElement($row)` durch `Contao\Controller::getContentElement($row)` ersetzt – `Contao\ContentElement` ist eine abstrakte Klasse und kann nicht direkt instanziiert werden.
- `SitemapEvent::getRootPageId()` existiert nicht; korrekt ist `SitemapEvent::getRootPageIds(): array` (ein Sitemap-Lauf kann mehrere Root-Seiten abdecken) – der Root-Filter im `SitemapListener` verglich bislang gegen eine nicht existierende Methode.
- Alle drei Fragment-Controller (`GroundingPageListController`, `GroundingPageReaderController`, `GroundingPageFaqController`) typisierten `getResponse()` fälschlich gegen `Contao\Template` statt `Contao\CoreBundle\Twig\FragmentTemplate` – ein LSP-Verstoß, der als Fatal Error beim Klassenladen endete.
- `GroundingPageModel::findMultipleByIds()`/`GroundingPageArchiveModel::findMultipleByIds()` kollidierten inkompatibel mit `Contao\Model::findMultipleByIds()`; ersteres zu `findPublishedMultipleByIds()` umbenannt, letzteres (ungenutzt, redundant zur Core-Implementierung) entfernt.
- `phpstan.neon.dist`: `ignoreErrors`-Muster passten nicht zu den tatsächlich gemeldeten Klassennamen (z. B. `GroundingPageModel`, `ContentModel`, `FragmentTemplate` statt `Contao\Model`); auf Suffix-Matching umgestellt. `phpstan/phpstan` von `^1.11` auf `^2.1` angehoben (volle PHP-8.4-Unterstützung).
- Repository auf die offizielle Contao-5/6-Bundle-Struktur umgestellt: PHP-Klassen liegen jetzt unter `src/…` (PSR-4, `Kikmedia\GroundingPageBundle\`), DI-Konfiguration unter `config/services.yaml`, DCA/Sprachdateien/Templates unter `contao/{dca,languages,templates}`. Vorher lagen alle Dateien flach im Repository-Root.
- Fehlende englische und deutsche Sprachdatei-Einträge für `tl_grounding_page`/`tl_grounding_page_archive` ergänzt (u. a. das `entityTypes`-Referenzarray, auf das die DCA bereits verwies, aber das bislang in keiner Sprachdatei definiert war).
- Kritisch: `PaletteManipulator::applyToPalette('grounding_page_list'/'grounding_page_reader', 'tl_module')` schlug bei jeder Installation mit `PaletteNotFoundException` fehl, da diese Paletten nie existierten – `applyToPalette()` modifiziert nur eine bestehende Palette, erzeugt sie nicht. Beide Paletten werden jetzt zunächst aus `default` kopiert; der Legend-Anker wurde von `config_legend` (existiert in `tl_module`s `default`-Palette nicht) auf `title_legend` korrigiert.

### Fixed
- Kritisch: `GroundingPageBundle` erweiterte die einfache Symfony-`Bundle`-Klasse statt `AbstractBundle`, wodurch `config/services.yaml` nie geladen wurde (Frontend-Module/Listener wären funktionslos geblieben). Jetzt korrekt via `loadExtension()`.
- Versehentlich beim Scaffolding entstandene leere Verzeichnisse mit Brace-Syntax im Namen entfernt.

### Added
- Grundgerüst der Extension: Archiv (`tl_grounding_page_archive`) und Einträge (`tl_grounding_page`).
- Backend-Felder nach dem [Grounding Page Standard v1.6](https://groundingpage.com/spec/): Entitätstyp, Definition/Einordnung/Abgrenzung, strukturierte Fakten, Quellen, verwandte Entitäten, Review-Datum.
- Frontend-Module „Liste“ und „Reader“ (Symfony-Fragments, Contao 5 & 6 kompatibel).
- JSON-LD-Spiegelung im Reader (`Kikmedia\GroundingPageBundle\Util\JsonLdBuilder`).
- Eigene Backend-Berechtigungsgruppe „Grounding-Pages“ (`tl_user_group`/`tl_user`: `groundingPages`, `groundingPagep`).
- Sitemap-Integration über `contao.sitemap` (`SitemapListener`).
- Deutsche und englische Backend-Sprachdateien.
