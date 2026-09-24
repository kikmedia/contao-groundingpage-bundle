# Changelog

Das Format orientiert sich an [Keep a Changelog](https://keepachangelog.com/de/1.0.0/).

## [Unreleased]

### Changed
- Repository auf die offizielle Contao-5/6-Bundle-Struktur umgestellt: PHP-Klassen liegen jetzt unter `src/…` (PSR-4, `Grounding\PageBundle\`), DI-Konfiguration unter `config/services.yaml`, DCA/Sprachdateien/Templates unter `contao/{dca,languages,templates}`. Vorher lagen alle Dateien flach im Repository-Root.
- Fehlende englische und deutsche Sprachdatei-Einträge für `tl_grounding_page`/`tl_grounding_page_archive` ergänzt (u. a. das `entityTypes`-Referenzarray, auf das die DCA bereits verwies, aber das bislang in keiner Sprachdatei definiert war).

### Fixed
- Kritisch: `GroundingPageBundle` erweiterte die einfache Symfony-`Bundle`-Klasse statt `AbstractBundle`, wodurch `config/services.yaml` nie geladen wurde (Frontend-Module/Listener wären funktionslos geblieben). Jetzt korrekt via `loadExtension()`.
- Versehentlich beim Scaffolding entstandene leere Verzeichnisse mit Brace-Syntax im Namen entfernt.

### Added
- Grundgerüst der Extension: Archiv (`tl_grounding_page_archive`) und Einträge (`tl_grounding_page`).
- Backend-Felder nach dem [Grounding Page Standard v1.6](https://groundingpage.com/spec/): Entitätstyp, Definition/Einordnung/Abgrenzung, strukturierte Fakten, Quellen, verwandte Entitäten, Review-Datum.
- Frontend-Module „Liste“ und „Reader“ (Symfony-Fragments, Contao 5 & 6 kompatibel).
- JSON-LD-Spiegelung im Reader (`Grounding\PageBundle\Util\JsonLdBuilder`).
- Eigene Backend-Berechtigungsgruppe „Grounding-Pages“ (`tl_user_group`/`tl_user`: `groundingPages`, `groundingPagep`).
- Sitemap-Integration über `contao.sitemap` (`SitemapListener`).
- Deutsche und englische Backend-Sprachdateien.
