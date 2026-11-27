# Contao Grounding-Page Bundle

Contao-Erweiterung, mit der sich Referenzseiten nach dem offenen **[Grounding Page Standard](https://groundingpage.com/spec/)** im Backend pflegen und im Frontend ausgeben lassen – mit JSON-LD, FAQ-Element, Sitemap-Integration und eigenen Backend-Berechtigungen.

> **Status: Alpha.** Datenbankfelder, Templates und PHP-Schnittstellen können sich bis zur Version 1.0 noch ändern. Bitte vor dem Einsatz auf einem Testsystem ausprobieren und vor Updates die Datenbank sichern.

Eine *Grounding Page* ist eine faktenbasierte Referenzseite zu genau einer Entität (Unternehmen, Produkt, Person, Begriff …), die für Menschen lesbar und für KI-Systeme maschinenlesbar aufgebaut ist. Details: [groundingpage.com/spec](https://groundingpage.com/spec/de/).

Der Funktionsumfang orientiert sich an Version 1.6 des Standards. Die Neuerungen aus 1.7 und 2.0 sind noch nicht umgesetzt, der Fahrplan steht in [Issue #5](https://github.com/kikmedia/contao-groundingpage-bundle/issues/5).

## Funktionen

- Archive und Einträge im Backend unter **Inhalte → Grounding-Pages**, mit Versionierung und Veröffentlichungszeitraum
- Frontend-Module **Liste** und **Reader**
- Inhaltselemente je Eintrag für eigenständige Abschnitte
- Inhaltselement **Grounding-Page FAQ** für Frage/Antwort-Paare
- JSON-LD für die Entität und, falls FAQ-Elemente vorhanden sind, zusätzlich als `FAQPage`
- Aufnahme in die `sitemap.xml` der passenden Website-Root
- Berechtigungen je Archiv sowie für Anlegen und Löschen
- Deutsche und englische Sprachdateien für Backend und Frontend

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

Das Paket liegt auf [Packagist](https://packagist.org/packages/kikmedia/contao-groundingpage-bundle). Solange es nur Vorabversionen gibt, muss die Stabilität mit angegeben werden. Im Hauptverzeichnis der Contao-Installation:

```bash
composer require "kikmedia/contao-groundingpage-bundle:^0.1@alpha"
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

Der Reader spiegelt nur sichtbare Inhalte und trennt zwischen der Entität und der Seite, die sie beschreibt.

**Block für die Entität:**

- `@type`, `name`, `description` und `url`
- `additionalProperty` aus den Fakten, sofern Schema.org die Eigenschaft für den Typ vorsieht

`additionalProperty` erhalten die Typen `Accommodation`, `AdministrativeArea`, `CivicStructure`, `IndividualProduct`, `Landform`, `LocalBusiness`, `Place`, `Product`, `ProductGroup`, `ProductModel`, `Residence`, `TouristAttraction` und `Vehicle`, auch wenn sie als eigener Typ eingetragen sind. Bei allen anderen Typen, zum Beispiel `Organization` oder `Person`, stehen die Fakten sichtbar auf der Seite, aber nicht im JSON-LD.

**`WebPage`-Knoten**, den Contao selbst im Schema.org-Graphen anlegt:

- `dateModified`: Datum der letzten Änderung am Eintrag oder an seinen Inhaltselementen
- `citation` aus den Quellen

Enthält der Eintrag FAQ-Elemente, folgt ein weiterer Block vom Typ [`FAQPage`](https://schema.org/FAQPage).

„Zuletzt geprüft am“ erscheint sichtbar auf der Seite, wird aber nicht als `lastReviewed` ausgezeichnet.

## Sprache

Die Zwischenüberschriften und Datumsbeschriftungen im Reader folgen der Sprache der Website-Root. Mitgeliefert werden Deutsch und Englisch. Die Inhalte selbst werden je Sprache in eigenen Einträgen gepflegt, zum Beispiel in einem Archiv je Sprache.

Eigene Texte oder weitere Sprachen lassen sich im Projekt über die Schlüssel `MSC.grounding_page_*` ergänzen.

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

- Mehrsprachige Einträge sind nicht miteinander verknüpft, gemeinsam genutzte Werte gibt es noch nicht.
- Das Löschen oder Ausblenden eines Inhaltselements ändert das Datum „Zuletzt aktualisiert“ nicht. Dazu den Eintrag selbst ändern und speichern.
- Legt ein Benutzer ohne Administratorrechte ein neues Archiv an, muss ihm dieses anschließend in den Berechtigungen freigegeben werden.
- Der Reader setzt weder Seitentitel noch Meta-Beschreibung aus dem Eintrag.
- Antworten im FAQ-Element sind einzeiliger Text ohne Formatierung.
- Sprachverknüpfungen (`hreflang`) werden nicht erzeugt.
- Das Upgrade einer Installation mit Inhalten von Contao 5 auf 6 ist noch nicht getestet. Contao verlangt dafür den Weg über 5.7.

## Umstieg von einer Testversion

Wer das Bundle vor der ersten Alpha aus dem Branch `main` installiert hat:

- Die früheren `.html5`-Templates entfallen. Eigene Anpassungen daran müssen nach Twig übertragen werden.
- Das FAQ-Feld heißt jetzt `groundingPageItems`.
- Einen früher eingetragenen GitHub-Verweis unter `repositories` in der `composer.json` des Projekts entfernen und wie oben von Packagist installieren.
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

Die Unit-Tests decken die Logik ab, die ohne Datenbank auskommt.

### Abnahmetest

Der Abnahmetest installiert eine Contao Managed Edition mit dem Bundle aus dem Arbeitsverzeichnis, legt Testdaten an und prüft Frontend, Sitemap, Backend-Formulare und Berechtigungen über HTTP. Er braucht PHP, Composer, `curl`, die PHP-Erweiterungen `gd`, `intl` und `pdo_mysql` sowie eine **leere** MariaDB- oder MySQL-Datenbank. Die Datenbank wird beschrieben, also nie eine mit echten Inhalten angeben. Port 8000 muss frei sein.

```bash
DATABASE_URL="mysql://root@127.0.0.1:3306/contao_test?serverVersion=10.11.0-MariaDB" tests/acceptance/run.sh "5.7.*"
```

Die CI führt den Test für alle oben als getestet genannten Kombinationen aus.

## Fehler melden

Bitte über die [GitHub-Issues](https://github.com/kikmedia/contao-groundingpage-bundle/issues), mit Contao-Version, PHP-Version und Fehlermeldung. Sicherheitsprobleme bitte nicht öffentlich melden, sondern an <office@kikmedia.de>.

## Lizenz

[LGPL-3.0-or-later](LICENSE.md), Copyright (C) Carolina Koehn, kikmedia.webdevelopment.
