# Grounding-Page Bundle

Contao-Extension für **Contao 5.3+ / 6.0+**, mit der sich Referenzseiten nach dem offenen **[Grounding Page Standard v1.6](https://groundingpage.com/spec/)** ([Technical Implementation Guide](https://groundingpage.com/spec/technical-implementation/)) im Backend pflegen und im Frontend ausgeben lassen – inklusive JSON-LD-Spiegelung, Sitemap-Integration und eigener Backend-Berechtigungsgruppe.

> **Was ist eine Grounding Page?** Eine faktenbasierte, für Menschen gut lesbare *und* für KI-Systeme maschinenlesbar strukturierte Referenzseite zu genau einer Entität (Unternehmen, Produkt, Person, Konzept …). Ziel: weniger Halluzinationen und Entitätsverwechslungen bei KI-gestützter Suche/Retrieval. Details siehe [groundingpage.com/spec](https://groundingpage.com/spec/).

## Struktur

| Ebene                        | Tabelle / Klasse                          |
|-------------------------------|---------------------------------------------|
| Archiv (z. B. „Facts“)         | `tl_grounding_page_archive`                |
| Eintrag = eine Grounding Page  | `tl_grounding_page`                        |
| Models                         | `GroundingPageArchiveModel`, `GroundingPageModel` |
| Frontend-Modul „Liste“         | `grounding_page_list`                       |
| Frontend-Modul „Reader“        | `grounding_page_reader`                     |
| Content-Element „FAQ“          | `grounding_page_faq`                        |
| JSON-LD-Erzeugung              | `Kikmedia\GroundingPageBundle\Util\JsonLdBuilder`  |

Jeder Eintrag besitzt eine eigene `ctable` auf `tl_content`, über die redaktionell beliebige, in sich abgeschlossene H2-Abschnitte (Standard §5, „Self-contained sections“) gepflegt werden – z. B. „Geschichte von X“, „Produkte von X“ – sowie optional das Content-Element **„Grounding-Page FAQ“** für Frage/Antwort-Paare (siehe unten).

## Backend-Felder je Eintrag (Mapping auf den Standard)

| Standard-Baustein (§)                        | Feld(er)                                   |
|------------------------------------------------|-----------------------------------------------|
| H1 mit Entitätsname (§4)                       | `title`                                       |
| Schema.org-Entitätstyp (§9)                    | `entityType` / `entityTypeCustom`             |
| Lead: Definition, Einordnung, Abgrenzung (§4)  | `definition`, `placement`, `distinction`      |
| Strukturierte Fakten (§6)                      | `coreFacts` (Attribut/Wert-Paare, `optionWizard`) |
| Selbstständige H2-Abschnitte (§5)              | Content-Elemente (`ctable` → `tl_content`)    |
| Abgrenzung/Disambiguation (§7)                 | `distinction` (bzw. FAQ/H2 via Content-Elemente) |
| Quellen & Aktualität (§8)                      | `sources`, `lastReviewed`                     |
| JSON-LD-Spiegelung (§9)                        | zur Laufzeit generiert, siehe `JsonLdBuilder` |
| Interne Verlinkung (§11)                       | `relatedPages`, Sitemap-Integration            |

**Wichtig laut Standard:** Das Wort „Grounding Page“ muss **nicht** auf der Seite erscheinen (§2). Der H1 sollte ausschließlich der Entitätsname sein, kein Slogan – die Backend-Hilfetexte weisen im Feld `title` entsprechend darauf hin.

## Installation

```bash
composer require kikmedia/contao-groundingpage-bundle
```

Das Paket ist vom Typ `contao-bundle` und bringt ein `ContaoManager\Plugin` mit, wird also vom Contao Manager automatisch registriert. Ohne Contao Manager manuell in `config/bundles.php` eintragen:

```php
return [
    // ...
    Kikmedia\GroundingPageBundle\GroundingPageBundle::class => ['all' => true],
];
```

Datenbank synchronisieren:

```bash
vendor/bin/contao-console contao:migrate
```

## Verwendung

1. **Backend → Grounding-Pages**: Archiv anlegen (Titel, Weiterleitungsseite für den Reader).
2. Im Archiv Einträge anlegen: Entitätsname, Entitätstyp, Definition/Einordnung/Abgrenzung, strukturierte Fakten, Quellen, ggf. verwandte Einträge, optional Content-Elemente für weitere H2-Abschnitte.
3. Frontend:
   - Modul **„Grounding-Page Liste“** auf einer Übersichtsseite einbinden.
   - Modul **„Grounding-Page Reader“** auf einer Detailseite einbinden (Seite muss `auto_item` unterstützen).
4. Veröffentlichte Einträge erscheinen automatisch in `sitemap.xml` (siehe unten).

## JSON-LD

Der Reader erzeugt automatisch einen `<script type="application/ld+json">`-Block, der **ausschließlich sichtbare Inhalte spiegelt** (Standard §9: keine unsichtbaren Zusatzbehauptungen):

- `@type` aus `entityType`/`entityTypeCustom`
- `name` = `title`, `description` = `definition`, `url` = kanonische Reader-URL
- `dateModified` aus `lastReviewed` (nur wenn gesetzt – wird **nicht** automatisch bei jedem Speichern aktualisiert)
- `additionalProperty` aus den strukturierten Fakten (`coreFacts`)
- `citation` aus den Quellen (`sources`)

Enthält der Eintrag mindestens ein Content-Element **„Grounding-Page FAQ“** mit vollständigen Frage/Antwort-Paaren, erzeugt der Reader zusätzlich einen zweiten `<script type="application/ld+json">`-Block vom Typ [`FAQPage`](https://schema.org/FAQPage) (`mainEntity` = `Question`/`acceptedAnswer`-Paare) – ebenfalls eine reine Spiegelung der sichtbaren Frage/Antwort-Liste, keine zusätzlichen Behauptungen.

## FAQ-Content-Element

Das Content-Element **„Grounding-Page FAQ“** (`grounding_page_faq`, Kategorie „Grounding-Page“) steht wie jedes andere Content-Element innerhalb der `ctable` eines Eintrags zur Verfügung. Es besitzt ein Feld `faqItems` (Frage/Antwort-Paare, `optionWizard`) und rendert diese als `<dl>`-Liste. Mehrere FAQ-Elemente innerhalb desselben Eintrags werden im JSON-LD zu einer gemeinsamen `FAQPage` zusammengeführt.

## Twig-Templates

Zusätzlich zu den bestehenden PHP-Templates (`contao/templates/…/*.html5`) liefert das Bundle Twig-Äquivalente unter `templates/frontend_module/` und `templates/content_element/` (`grounding_page_list.html.twig`, `grounding_page_reader.html.twig`, `grounding_page_faq.html.twig`). Contao 5/6 bevorzugt bei vorhandenem Twig-Pendant automatisch die Twig-Variante; ein Projekt kann per `customTpl` bzw. Theme-Override weiterhin gezielt auf die PHP-Variante zurückgreifen. Beide Varianten sind funktional identisch.

## Eigene Backend-Berechtigungsgruppe

Nicht-Admin-Benutzergruppen (`tl_user_group`) bzw. einzelne Benutzer (`tl_user`) erhalten einen eigenen Bereich **„Grounding-Pages“**:

- **Erlaubte Archive** (`groundingPages`): Checkbox-Auswahl der Archive, die verwaltet werden dürfen.
- **Berechtigungen** (`groundingPagep`): `create` (neue Archive anlegen) und `delete` (Archive/Einträge löschen).

Die Durchsetzung erfolgt in `Kikmedia\GroundingPageBundle\EventListener\DataContainer\GroundingPageListener` (`checkArchivePermission` / `checkPermission`), analog zum bewährten Contao-Muster (z. B. News-/Kalender-Archive): Admins sind immer uneingeschränkt, alle anderen sehen/bearbeiten nur die freigegebenen Archive; die Berechtigungen aus Benutzer **und** allen zugeordneten Gruppen werden vereinigt.

## Sitemap-Integration

`Kikmedia\GroundingPageBundle\EventListener\SitemapListener` hört auf das Contao-Event `contao.sitemap` und fügt jeden veröffentlichten Eintrag mit `addUrlToDefaultUrlSet()` in die generierte `sitemap.xml` ein (URL = Weiterleitungsseite des Archivs + Alias/ID). Der Listener filtert dabei nach Website-Root: ein Eintrag landet nur in der Sitemap des Root, zu dem seine Weiterleitungsseite gehört (`PageModel::rootId` vs. `SitemapEvent::getRootPageId()`) – Multi-Domain-/Multi-Root-Installationen erhalten dadurch pro Root nur die tatsächlich zugehörigen Einträge.

## Contao-5/6-Standardstruktur (validiert)

Gemäß der offiziellen Contao-Dokumentation ([„Creating an Extension“](https://docs.contao.org/5.x/dev/getting-started/extension/)) folgt das Bundle der empfohlenen Struktur:

```
contao-groundingpage-bundle/
├── composer.json
├── config/
│   └── services.yaml
├── contao/
│   ├── dca/
│   ├── languages/{de,en}/
│   └── templates/{frontend_module,content_element}/
├── templates/
│   ├── frontend_module/          (Twig-Äquivalente)
│   └── content_element/          (Twig-Äquivalente)
├── src/
│   ├── ContaoManager/Plugin.php
│   ├── Controller/{FrontendModule,ContentElement}/
│   ├── EventListener/{DataContainer,}/
│   ├── Model/
│   ├── Util/
│   └── GroundingPageBundle.php
└── tests/
    ├── Util/
    └── Controller/ContentElement/
```

**Kein `assets/`-Verzeichnis**: Dieser Ordner wird in einem Contao-Bundle nur für auszuliefernde statische Dateien (JS/CSS/Bilder, per `bin/console assets:install` symlinked) benötigt. Da dieses Bundle bewusst **kein CSS** mitliefert (siehe unten), entfällt er – ein leerer Ordner ohne Inhalt wäre nur Ballast.

**Korrigierter strukturseitiger Bug**: Die Bundle-Klasse `GroundingPageBundle` erweiterte ursprünglich die einfache Symfony-`Bundle`-Klasse. Damit wäre `config/services.yaml` **nie automatisch geladen worden** – Frontend-Module, der Sitemap-Listener und der Berechtigungs-Listener hätten dadurch im Contao Manager gar nicht funktioniert. Die Klasse erweitert jetzt `Symfony\Component\HttpKernel\Bundle\AbstractBundle` und lädt die Service-Konfiguration explizit über `loadExtension()`, wie in der offiziellen Doku beschrieben. Zusätzlich wurden versehentlich beim ersten Scaffolding entstandene leere Fehlverzeichnisse (Shell-Brace-Syntax wurde nicht expandiert) entfernt.



## Kein CSS

Die Templates (PHP unter `contao/templates/…`, Twig unter `templates/…`) enthalten bewusst **kein CSS** – nur semantisches HTML (`<dl>`, `<article>`, Microdata-Attribute). Das entspricht Standard §10 „Design and brand integration“: Die Seite soll sich optisch in das bestehende Theme einfügen, nicht wie ein technischer Fremdkörper wirken. Styling erfolgt über das Kontao-Theme des Projekts (z. B. via `cssClass`-Feld am Archiv).

## Qualitätssicherung

```bash
composer install
composer test        # PHPUnit
composer phpstan      # PHPStan (Level 3)
composer cs-check     # PHP-CS-Fixer, Dry-Run
composer cs-fix       # PHP-CS-Fixer, wendet Korrekturen an
```

Die GitHub-Actions-Pipeline ([.github/workflows/ci.yml](.github/workflows/ci.yml)) führt `composer validate`, PHP-Lint, einen `composer install`-Kompatibilitätstest gegen Contao `5.3.*`/`6.0.*` (jeweils niedrigste/höchste Abhängigkeiten) sowie PHPUnit, PHPStan und PHP-CS-Fixer bei jedem Push/PR aus.

Die Testsuite deckt aktuell die reine Logik ab, die ohne DB/Container testbar ist (`JsonLdBuilder`, `GroundingPageFaqController::extractItems()`, gemockt über `Contao\TestCase\ContaoTestCase`). DCA-Callbacks und die Sitemap-/Berechtigungs-Listener sind wegen ihrer Kopplung an Contao-Statics (`Database`, `PageModel`, Model-Registry) nicht unit-, sondern eher funktional/manuell zu testen.

## Umfang

Enthalten: Backend-Verwaltung, Frontend-Ausgabe, FAQ-Content-Element mit `FAQPage`-JSON-LD, JSON-LD-Spiegelung, Root-gefilterte Sitemap-Integration, Berechtigungsgruppe, DE/EN-Sprachdateien, Alias-Generierung, Versionierung, Twig- und PHP-Templates, PHPUnit/PHPStan/PHP-CS-Fixer inkl. CI.

Bewusst **nicht** enthalten (nächste Ausbaustufen je nach Bedarf):
- Keine Kommentarfunktion (wie gewünscht).
- Mehrsprachigkeit/`hreflang` (Standard §12) – aktuell ein Archiv pro Sprache über separate Seitenbäume abbildbar, aber nicht automatisiert.
- Funktionale/Integrationstests gegen eine echte Contao-Installation (nur Unit-Tests der DB-unabhängigen Logik).

## Veröffentlichung als Contao-Bundle (Packagist)

`composer.json` ist bereits auf Veröffentlichung vorbereitet (Name `kikmedia/contao-groundingpage-bundle`, Typ `contao-bundle`, `contao-manager-plugin`, Lizenz `LGPL-3.0-or-later`, `keywords`, `support`-Links auf das GitHub-Repository). Vor dem Packagist-Submit:

1. Vollständigen LGPL-3.0-Lizenztext in `LICENSE.md` einfügen (Hinweis dort enthalten).
2. Repository taggen (z. B. `v1.0.0`), auf [packagist.org](https://packagist.org) einreichen.
