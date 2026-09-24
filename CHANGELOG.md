# Changelog

Das Format orientiert sich an [Keep a Changelog](https://keepachangelog.com/de/1.0.0/).

## [Unreleased]

### Added
- Root-Page-Filter im `SitemapListener`: Einträge erscheinen nur noch in der Sitemap des Website-Root, zu dem ihre Weiterleitungsseite gehört (`PageModel::rootId` vs. `SitemapEvent::getRootPageId()`).
- Content-Element „Grounding-Page FAQ“ (`grounding_page_faq`): Frage/Antwort-Paare (`faqItems`, `optionWizard`), gerendert als `<dl>`-Liste.
- `JsonLdBuilder::buildFaqPage()` und Integration im Reader: Enthält ein Eintrag mindestens ein vollständiges FAQ-Element, wird zusätzlich zum Entitäts-JSON-LD ein `https://schema.org/FAQPage`-Block mit `mainEntity` (`Question`/`acceptedAnswer`) ausgegeben.
- Twig-Templates unter `templates/frontend_module/` und `templates/content_element/` als Äquivalent zu den bestehenden PHP-Templates (Liste, Reader, FAQ-Content-Element).
- PHPUnit-Testsuite (`tests/`) für `JsonLdBuilder` und `GroundingPageFaqController::extractItems()`, PHPStan (Level 3, `phpstan.neon.dist`) und PHP-CS-Fixer (`.php-cs-fixer.dist.php`), inkl. `composer test`/`phpstan`/`cs-check`/`cs-fix` Scripts.
- CI-Jobs für PHPUnit, PHPStan und PHP-CS-Fixer in `.github/workflows/ci.yml`.

### Changed
- Paketname von `grounding/page-bundle` auf `kikmedia/contao-groundingpage-bundle` geändert, PHP-Namespace von `Grounding\PageBundle\` auf `Kikmedia\GroundingPageBundle\`, `composer.json`-Metadaten (`homepage`, `support`) auf das tatsächliche GitHub-Repository umgestellt.
- Repository auf die offizielle Contao-5/6-Bundle-Struktur umgestellt: PHP-Klassen liegen jetzt unter `src/…` (PSR-4, `Kikmedia\GroundingPageBundle\`), DI-Konfiguration unter `config/services.yaml`, DCA/Sprachdateien/Templates unter `contao/{dca,languages,templates}`. Vorher lagen alle Dateien flach im Repository-Root.
- Fehlende englische und deutsche Sprachdatei-Einträge für `tl_grounding_page`/`tl_grounding_page_archive` ergänzt (u. a. das `entityTypes`-Referenzarray, auf das die DCA bereits verwies, aber das bislang in keiner Sprachdatei definiert war).

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
