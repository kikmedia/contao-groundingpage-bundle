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
| JSON-LD-Erzeugung              | `Grounding\PageBundle\Util\JsonLdBuilder`  |

Es gibt **kein Content-Element** – ausschließlich die beiden Frontend-Module Liste und Reader, wie gewünscht. Jeder Eintrag besitzt weiterhin eine eigene `ctable` auf `tl_content`, über die redaktionell beliebige, in sich abgeschlossene H2-Abschnitte (Standard §5, „Self-contained sections“) gepflegt werden – z. B. „Geschichte von X“, „Produkte von X“.

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
composer require grounding/page-bundle
```

Das Paket ist vom Typ `contao-bundle` und bringt ein `ContaoManager\Plugin` mit, wird also vom Contao Manager automatisch registriert. Ohne Contao Manager manuell in `config/bundles.php` eintragen:

```php
return [
    // ...
    Grounding\PageBundle\GroundingPageBundle::class => ['all' => true],
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

## Eigene Backend-Berechtigungsgruppe

Nicht-Admin-Benutzergruppen (`tl_user_group`) bzw. einzelne Benutzer (`tl_user`) erhalten einen eigenen Bereich **„Grounding-Pages“**:

- **Erlaubte Archive** (`groundingPages`): Checkbox-Auswahl der Archive, die verwaltet werden dürfen.
- **Berechtigungen** (`groundingPagep`): `create` (neue Archive anlegen) und `delete` (Archive/Einträge löschen).

Die Durchsetzung erfolgt in `Grounding\PageBundle\EventListener\DataContainer\GroundingPageListener` (`checkArchivePermission` / `checkPermission`), analog zum bewährten Contao-Muster (z. B. News-/Kalender-Archive): Admins sind immer uneingeschränkt, alle anderen sehen/bearbeiten nur die freigegebenen Archive; die Berechtigungen aus Benutzer **und** allen zugeordneten Gruppen werden vereinigt.

## Sitemap-Integration

`Grounding\PageBundle\EventListener\SitemapListener` hört auf das Contao-Event `contao.sitemap` und fügt jeden veröffentlichten Eintrag mit `addUrlToDefaultUrlSet()` in die generierte `sitemap.xml` ein (URL = Weiterleitungsseite des Archivs + Alias/ID).

**Vereinfachung:** Es wird aktuell nicht nach Website-Root/Domain gefiltert – bei einer Single-Root-Installation ist das korrekt, bei mehreren Root-Seiten/Domains sollte vor dem Produktiveinsatz eine Root-Zuordnung ergänzt werden (Kommentar dazu direkt im Listener).

## Contao-5/6-Standardstruktur (validiert)

Gemäß der offiziellen Contao-Dokumentation ([„Creating an Extension“](https://docs.contao.org/5.x/dev/getting-started/extension/)) folgt das Bundle der empfohlenen Struktur:

```
grounding-page-bundle/
├── composer.json
├── config/
│   └── services.yaml
├── contao/
│   ├── dca/
│   ├── languages/{de,en}/
│   └── templates/frontend_module/
└── src/
    ├── ContaoManager/Plugin.php
    ├── Controller/FrontendModule/
    ├── EventListener/{DataContainer,}/
    ├── Model/
    ├── Util/
    └── GroundingPageBundle.php
```

**Kein `assets/`-Verzeichnis**: Dieser Ordner wird in einem Contao-Bundle nur für auszuliefernde statische Dateien (JS/CSS/Bilder, per `bin/console assets:install` symlinked) benötigt. Da dieses Bundle bewusst **kein CSS** mitliefert (siehe unten), entfällt er – ein leerer Ordner ohne Inhalt wäre nur Ballast.

**Korrigierter strukturseitiger Bug**: Die Bundle-Klasse `GroundingPageBundle` erweiterte ursprünglich die einfache Symfony-`Bundle`-Klasse. Damit wäre `config/services.yaml` **nie automatisch geladen worden** – Frontend-Module, der Sitemap-Listener und der Berechtigungs-Listener hätten dadurch im Contao Manager gar nicht funktioniert. Die Klasse erweitert jetzt `Symfony\Component\HttpKernel\Bundle\AbstractBundle` und lädt die Service-Konfiguration explizit über `loadExtension()`, wie in der offiziellen Doku beschrieben. Zusätzlich wurden versehentlich beim ersten Scaffolding entstandene leere Fehlverzeichnisse (Shell-Brace-Syntax wurde nicht expandiert) entfernt.



## Kein CSS

Die Templates (`contao/templates/frontend_module/mod_grounding_page_*.html5`) enthalten bewusst **kein CSS** – nur semantisches HTML (`<dl>`, `<article>`, Microdata-Attribute). Das entspricht Standard §10 „Design and brand integration“: Die Seite soll sich optisch in das bestehende Theme einfügen, nicht wie ein technischer Fremdkörper wirken. Styling erfolgt über das Kontao-Theme des Projekts (z. B. via `cssClass`-Feld am Archiv).

## Umfang / bewusst nicht enthalten

Enthalten: Backend-Verwaltung, Frontend-Ausgabe, JSON-LD, Sitemap, Berechtigungsgruppe, DE/EN-Sprachdateien, Alias-Generierung, Versionierung.

Bewusst **nicht** enthalten (nächste Ausbaustufen je nach Bedarf):
- Keine Kommentarfunktion (wie gewünscht).
- Kein Content-Element (wie gewünscht) – nur Frontend-Module.
- FAQ-Abschnitt wird aktuell über normale Content-Elemente abgebildet, nicht als eigenes `FAQPage`-JSON-LD (könnte ergänzt werden, sobald FAQ-Content-Elemente strukturell erkennbar sind).
- Mehrsprachigkeit/`hreflang` (Standard §12) – aktuell ein Archiv pro Sprache über separate Seitenbäume abbildbar, aber nicht automatisiert.
- Root-Page-Filterung im Sitemap-Listener bei Multi-Domain-Setups (siehe oben).
- Unit-Tests, GitHub-Actions-CI, PHPStan-Konfiguration.

## Veröffentlichung als Contao-Bundle (Packagist)

`composer.json` ist bereits auf Veröffentlichung vorbereitet (Typ `contao-bundle`, `contao-manager-plugin`, Lizenz `LGPL-3.0-or-later`, `keywords`, `support`-Links). Vor dem `composer publish` bzw. Packagist-Submit:

1. Platzhalter in `composer.json` (`authors`, `homepage`, `support.issues/source`) durch echte Angaben ersetzen.
2. Vollständigen LGPL-3.0-Lizenztext in `LICENSE.md` einfügen (Hinweis dort enthalten).
3. Ein öffentliches Git-Repository anlegen, taggen (z. B. `v1.0.0`), auf [packagist.org](https://packagist.org) einreichen.
4. Optional: GitHub-Actions-Workflow für PHPStan/PHP-CS-Fixer/PHPUnit ergänzen, bevor breiter Rollout erfolgt.

Sag mir, welchen der offenen Punkte du als Nächstes brauchst (z. B. FAQPage-JSON-LD, Mehrsprachigkeit, Root-Filterung, CI-Setup), dann bauen wir gezielt weiter aus.
