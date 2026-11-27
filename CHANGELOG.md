# Changelog

Das Format orientiert sich an [Keep a Changelog](https://keepachangelog.com/de/1.1.0/), die Versionsnummern folgen [Semantic Versioning](https://semver.org/lang/de/).

## [0.1.0-alpha.1] – 2026-11-27

Erste Alpha-Version.

### Enthalten

- Archive und Einträge nach dem Grounding Page Standard v1.6
- Frontend-Module „Liste“ und „Reader“, Inhaltselement „Grounding-Page FAQ“
- JSON-LD für die Entität und als `FAQPage`
- Sitemap-Integration je Website-Root
- Berechtigungen je Archiv sowie für Anlegen und Löschen
- Unterstützung für Contao 5.3 bis 6.0 ab PHP 8.3

### Geändert gegenüber den Testständen

- Contao 6.0 wird unterstützt.
- Mindestversion ist PHP 8.3 statt 8.4.
- Twig-Templates unter `contao/templates/` ersetzen die `.html5`-Templates.
- Die Berechtigungen werden über Security-Voter geprüft.
- Das JSON-LD maskiert spitze Klammern, sodass Inhalte den `<script>`-Block nicht schließen können.
- Das FAQ-Feld heißt `groundingPageItems` statt `faqItems`.

### Behoben

- Sicherheit: Eingeschränkte Benutzer konnten Inhaltselemente aus nicht freigegebenen Archiven öffnen.
- „Anzeigen ab“ und „Anzeigen bis“ blieben in Liste, Reader und Sitemap ohne Wirkung.
- Der Reader zeigte auch Einträge aus nicht ausgewählten Archiven.
- Links zu verwandten Einträgen führten nicht zur Reader-Seite.
- Inhaltselemente eines Eintrags waren im Backend nicht erreichbar.
- Der Schalter zum Veröffentlichen fehlte.
- Unter Contao 5 standen im JSON-LD HTML-Entitäten statt Klartext.
- Das Bild wurde ohne die eingestellte Bildgröße ausgegeben.
- Die CSS-Klasse des Archivs wurde nicht ausgegeben.
- „Zuletzt geprüft“ erschien als Zahl, und `dateModified` fehlte im JSON-LD.
