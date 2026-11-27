# Contao Grounding-Page Bundle

Contao-Erweiterung, mit der sich Referenzseiten nach dem offenen **[Grounding Page Standard v1.6](https://groundingpage.com/spec/)** im Backend pflegen und im Frontend ausgeben lassen – mit JSON-LD, FAQ-Element, Sitemap-Integration und eigenen Backend-Berechtigungen.

> **Status: Alpha.** Datenbankfelder, Templates und PHP-Schnittstellen können sich bis zur Version 1.0 noch ändern. Bitte vor dem Einsatz auf einem Testsystem ausprobieren und vor Updates die Datenbank sichern.

Eine *Grounding Page* ist eine faktenbasierte Referenzseite zu genau einer Entität (Unternehmen, Produkt, Person, Begriff …), die für Menschen lesbar und für KI-Systeme maschinenlesbar aufgebaut ist. Details: [groundingpage.com/spec](https://groundingpage.com/spec/de/).

## Funktionen

- Archive und Einträge im Backend unter **Inhalte → Grounding-Pages**, mit Versionierung und Veröffentlichungszeitraum
- Frontend-Module **Liste** und **Reader**
- Inhaltselemente je Eintrag für eigenständige Abschnitte
- Inhaltselement **Grounding-Page FAQ** für Frage/Antwort-Paare
- JSON-LD für die Entität und, falls FAQ-Elemente vorhanden sind, zusätzlich als `FAQPage`
- Aufnahme in die `sitemap.xml` der passenden Website-Root
- Berechtigungen je Archiv sowie für Anlegen und Löschen
- Deutsche und englische Backend-Sprachdateien

## Voraussetzungen

| | |
|---|---|
| PHP | 8.3 oder neuer |
| Contao | 5.3 oder neuer, 6.0 |

Getestet sind:

| Contao | PHP 8.3 | PHP 8.4 |
|---|---|---|
| 5.3 | ja | ja |
| 5.7 | ja | ja |
| 6.0 | – (Contao 6 verlangt PHP 8.4) | ja |

## Installation

Solange das Paket nicht auf Packagist liegt, wird das GitHub-Repository im Contao-Projekt eingetragen:

```bash
composer config repositories.groundingpage vcs https://github.com/kikmedia/contao-groundingpage-bundle.git
```

```bash
composer require kikmedia/contao-groundingpage-bundle:^0.1@alpha
```

```bash
vendor/bin/contao-console contao:migrate
```

Der Contao Manager registriert das Bundle automatisch. In einer Installation ohne Contao Manager gehört es in `config/bundles.php`:

```php
Kikmedia\GroundingPageBundle\GroundingPageBundle::class => ['all' => true],
```

## Einrichtung

1. **Zwei Seiten anlegen:** eine Übersichtsseite und eine Detailseite. Für die Detailseite empfiehlt sich die Seiteneinstellung, die einen Parameter in der URL verlangt.
2. **Frontend-Module anlegen** (Themes → Frontend-Module):
   - *Grounding-Page Liste* für die Übersichtsseite
   - *Grounding-Page Reader* für die Detailseite

   Bei beiden Modulen werden die Archive ausgewählt. Der Reader zeigt nur Einträge aus diesen Archiven.
3. **Archiv anlegen** (Inhalte → Grounding-Pages) und als Weiterleitungsseite die Detailseite wählen.
4. **Einträge anlegen** und veröffentlichen.
5. Über **Inhaltselemente** am Eintrag weitere Abschnitte ergänzen, zum Beispiel das FAQ-Element.

## Felder eines Eintrags

| Baustein des Standards | Feld |
|---|---|
| Überschrift mit Entitätsname | Entitätsname |
| Schema.org-Typ | Entitätstyp, optional eigener Typ |
| Definition, Einordnung, Abgrenzung | gleichnamige Felder |
| Strukturierte Fakten | Strukturierte Fakten |
| Quellen und Aktualität | Quellen, Zuletzt geprüft am |
| Verwandte Entitäten | Verwandte Entitäten |
| Eigenständige Abschnitte | Inhaltselemente |

Der Entitätsname sollte nur der Name sein, kein Werbespruch. Der Begriff „Grounding Page“ muss auf der Seite nicht vorkommen.

Fakten, Quellen und FAQ nutzen Contaos Optionen-Assistenten mit den Spalten **Wert** und **Bezeichnung**:

| Verwendung | Wert | Bezeichnung |
|---|---|---|
| Strukturierte Fakten | `2014` | `Gegründet` |
| Quellen | `https://example.com/bericht` | `Jahresbericht` |
| FAQ | Antwort | Frage |

## JSON-LD

Der Reader gibt einen JSON-LD-Block aus, der nur sichtbare Inhalte spiegelt:

- `@type`, `name`, `description` und `url`
- `dateModified` aus „Zuletzt geprüft am“
- `additionalProperty` aus den Fakten
- `citation` aus den Quellen

Enthält der Eintrag FAQ-Elemente, folgt ein zweiter Block vom Typ [`FAQPage`](https://schema.org/FAQPage).

## Berechtigungen

Administratoren dürfen alles. Für andere Benutzer wird in der Benutzergruppe freigegeben:

1. unter den Backend-Modulen **Grounding-Pages**
2. unter **Grounding-Pages** die erlaubten Archive, bei Bedarf „Neue Archive anlegen“ und „Archive/Einträge löschen“
3. die erlaubten Felder der Archive und Einträge sowie die erlaubten Inhaltselemente (ohne freigegebene Felder lässt Contao nichts anlegen)
4. ab Contao 5.7: die erlaubten Operationen (anlegen, bearbeiten, löschen) für Grounding-Page-Archive, -Einträge und Inhaltselemente

## Templates anpassen

Das Bundle verwendet Twig-Templates:

- `frontend_module/grounding_page_list`
- `frontend_module/grounding_page_reader`
- `content_element/grounding_page_faq`

Zum Anpassen eine gleichnamige Datei im Projekt anlegen, zum Beispiel `templates/frontend_module/grounding_page_reader.html.twig`, und vom Original erben:

```twig
{% extends "@Contao/frontend_module/grounding_page_reader.html.twig" %}
```

Das Bundle liefert bewusst kein CSS. Jedes Archiv kann eine CSS-Klasse erhalten, die der Reader am umschließenden `<article>` ausgibt.

## Bekannte Einschränkungen

- Die Zwischenüberschriften im Reader („Kerndaten“, „Zuletzt geprüft“, „Verwandt“, „Quellen“) sind nur auf Deutsch vorhanden.
- Legt ein Benutzer ohne Administratorrechte ein neues Archiv an, muss ihm dieses anschließend in den Berechtigungen freigegeben werden.
- Der Reader setzt weder Seitentitel noch Meta-Beschreibung aus dem Eintrag.
- Antworten im FAQ-Element sind einzeiliger Text ohne Formatierung.
- Sprachverknüpfungen (`hreflang`) werden nicht erzeugt.
- Das Upgrade einer Installation mit Inhalten von Contao 5 auf 6 ist noch nicht getestet. Contao verlangt dafür den Weg über 5.7.

## Umstieg von einer Testversion

Wer das Bundle vor der ersten Alpha aus dem Branch `main` installiert hat:

- Die früheren `.html5`-Templates entfallen. Eigene Anpassungen daran müssen nach Twig übertragen werden.
- Das FAQ-Feld heißt jetzt `groundingPageItems`.
- Nach dem Update `contao:migrate` ausführen.

## Entwicklung

```bash
composer install
```

| Befehl | Zweck |
|---|---|
| `composer test` | PHPUnit |
| `composer phpstan` | statische Analyse |
| `composer cs-check` | Code-Style prüfen |
| `composer cs-fix` | Code-Style korrigieren |

Die Unit-Tests decken die Logik ab, die ohne Datenbank auskommt. Backend, Berechtigungen und Frontend-Ausgabe müssen gegen eine echte Contao-Installation geprüft werden.

## Fehler melden

Bitte über die [GitHub-Issues](https://github.com/kikmedia/contao-groundingpage-bundle/issues), mit Contao-Version, PHP-Version und Fehlermeldung. Sicherheitsprobleme bitte nicht öffentlich melden, sondern an <office@kikmedia.de>.

## Lizenz

[LGPL-3.0-or-later](LICENSE.md), Copyright (C) Carolina Koehn, kikmedia.webdevelopment.
