# Contao Grounding-Page Bundle

Contao-Erweiterung, mit der sich Referenzseiten nach dem offenen **[Grounding Page Standard](https://groundingpage.com/spec/)** im Backend pflegen und im Frontend ausgeben lassen – mit JSON-LD, FAQ-Element, Sitemap-Integration und eigenen Backend-Berechtigungen.

> **Status: Alpha.** Datenbankfelder, Templates und PHP-Schnittstellen können sich bis zur Version 1.0 noch ändern. Bitte vor dem Einsatz auf einem Testsystem ausprobieren und vor Updates die Datenbank sichern.

Eine *Grounding Page* ist eine faktenbasierte Referenzseite zu genau einer Entität (Unternehmen, Produkt, Person, Begriff …), die für Menschen lesbar und für KI-Systeme maschinenlesbar aufgebaut ist. Details: [groundingpage.com/spec](https://groundingpage.com/spec/de/).

Der Funktionsumfang orientiert sich an [Version 1.7](https://groundingpage.com/spec/v1.7/de/) des Standards, einschließlich der dokumentierten menschlichen Faktenprüfung. Die Neuerungen aus 2.0 sind noch nicht umgesetzt, der Fahrplan steht in [Issue #5](https://github.com/kikmedia/contao-groundingpage-bundle/issues/5).

## Funktionen

- Archive und Einträge im Backend unter **Inhalte → Grounding-Pages**, mit Versionierung und Veröffentlichungszeitraum
- Frontend-Module **Liste** und **Reader**
- Inhaltselemente je Eintrag für eigenständige Abschnitte
- Inhaltselement **Grounding-Page FAQ** für Frage/Antwort-Paare
- JSON-LD für die Entität und, falls FAQ-Elemente vorhanden sind, zusätzlich als `FAQPage`
- Aufnahme in die `sitemap.xml` der passenden Website-Root
- Prüfprotokoll je Eintrag mit sichtbarem Prüfhinweis, `reviewedBy` und `lastReviewed`
- Prüfergebnisse im Backend und optionale Konformitätsangabe zu Version 1.7
- Berechtigungen je Archiv sowie für Anlegen, Löschen und das Dokumentieren von Prüfungen
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
| Quellen | Quellen |
| Aktualität und Faktenprüfung | Aktion **Prüfungen** am Eintrag |
| Verwandte Entitäten | Verwandte Entitäten |
| Eigenständige Abschnitte | Inhaltselemente |

Der Entitätsname sollte nur der Name sein, kein Werbespruch. Der Begriff „Grounding Page“ muss auf der Seite nicht vorkommen.

Fakten, Quellen und FAQ nutzen Contaos Optionen-Assistenten mit den Spalten **Wert** und **Bezeichnung**:

| Verwendung | Wert | Bezeichnung |
|---|---|---|
| Strukturierte Fakten | `2014` | `Gegründet` |
| Quellen | `https://example.com/bericht` | `Jahresbericht` |
| FAQ | Antwort | Frage |

## Faktenprüfung

Standard 1.7 verlangt, dass ein Mensch die Fakten vor der Veröffentlichung und nach jeder wesentlichen Änderung prüft. Das Bundle dokumentiert diese Prüfung. Es prüft die Fakten nicht selbst.

### Ablauf

1. Eintrag und Inhaltselemente fertigstellen.
2. In der Eintragsliste die Aktion **Prüfungen** wählen.
3. Datum, Prüfer (Person oder Organisation, mit Name) und Ergebnis angeben. Für „freigegeben“ müssen alle fünf Prüfpunkte bestätigt sein: faktische Richtigkeit, Aktualität inklusive Stand-Daten, Nachprüfbarkeit und Quellenqualität, eindeutige Entität und Abgrenzung sowie Übereinstimmung mit strukturierten Daten, Seitentitel, Meta-Beschreibung und Alt-Texten.
4. Speichern. Bei „nicht freigegeben“ ist eine interne Notiz Pflicht.

Prüfer und Datum erscheinen öffentlich. Der Backend-Benutzer, der die Prüfung dokumentiert hat, und die Notiz bleiben intern. Wer eine Prüfung dokumentiert, die eine andere Person durchgeführt hat, trägt deren Namen als Prüfer ein.

Je Archiv lässt sich ein Standard-Prüfer eintragen, der die erste Prüfung eines Eintrags vorbelegt.

Gespeicherte Prüfungen lassen sich nicht bearbeiten oder kopieren. Löschen dürfen nur Administratoren. Danach gilt wieder die vorherige Prüfung. Beim Kopieren eines Eintrags wird das Protokoll nicht mitkopiert, beim Löschen wird es mitgelöscht.

### Prüfhinweis

Solange die zuletzt gespeicherte Prüfung freigegeben ist und der Inhalt seither unverändert blieb, zeigt der Reader:

> Fakten geprüft von Erika Muster · Zuletzt von einem Menschen geprüft: 2026-11-27

Dieselben Werte stehen als `reviewedBy` und `lastReviewed` am `WebPage`-Knoten. Sonst erscheint weder Hinweis noch Auszeichnung.

Eine Prüfung gilt als veraltet, sobald sich Entitätsname, Typ, Definition, Einordnung, Abgrenzung, Kernfakten, Quellen, verwandte Einträge, das Bild oder ein nicht verstecktes Inhaltselement ändern. Alias, Autor, Veröffentlichung, Bildgröße und Layout-Einstellungen zählen nicht. Wird eine Änderung zurückgenommen, gilt die Prüfung wieder.

War die Änderung unwesentlich (Tippfehler, Layout, Umformulierung ohne Faktenänderung, Versionshinweis), lässt sie sich im Protokoll mit einer Notiz **als unwesentlich bestätigen**. Prüfer und Datum der ursprünglichen Prüfung bleiben öffentlich.

Zeigt die Frontend-Vorschau auch versteckte Elemente, bleibt der Prüfhinweis dort aus, sobald der Eintrag solche Elemente enthält.

**Nicht erkannt** werden Änderungen an Dateimetadaten wie Alt-Texten, an eingebundenen Modulen oder referenzierten Elementen, an Titeln verwandter Einträge und an Templates. In diesen Fällen muss selbst neu geprüft werden.

Bringt ein Update von Contao, diesem Bundle oder einer anderen Erweiterung neue Spalten mit Vorgabewert für Inhaltselemente, werden Prüfungen veraltet. Nach Updates lohnt ein Blick in die Eintragsliste.

### Prüfergebnisse und Konformitätsangabe

Die Eintragsliste zeigt je Eintrag den Prüfzustand, das Eintragsformular die Ergebnisse zum gespeicherten Stand:

| Bezug | Pflicht |
|---|---|
| P-2 | Definition ausgefüllt |
| P-3 | jede H2-Überschrift in den Inhaltselementen nennt den Entitätsnamen |
| P-4 | mindestens ein Kernfakt mit Bezeichnung und Wert |
| P-5 | spezifischer Typ, weder `Thing` noch `WebPage` |
| P-7 | Eintrag veröffentlicht, Weiterleitungsseite veröffentlicht, ohne Zugriffsschutz und nicht auf `noindex` |
| P-8 | Prüfung freigegeben und aktuell |

Hinweise, die nichts verhindern: fehlender Alt-Text (P-6), fehlende Abgrenzung (P-11), fehlende oder nicht per `http(s)` erreichbare Quellen (P-12), Archiv ohne Weiterleitungsseite oder in keinem Listen-Modul (P-15), Prüfung älter als 6 oder 12 Monate (P-16).

Mit dem Häkchen **Konformität mit Version 1.7 angeben** zeigt der Reader unter dem Prüfhinweis „Grounding Page Standard 1.7“ mit Link zum Standard, solange die Prüfung aktuell ist und alle Pflichtprüfungen bestehen. Die Prüfungen decken ab, was sich maschinell feststellen lässt. Ob eine Seite den Standard erfüllt, verantwortet der Betreiber.

## JSON-LD

Der Reader spiegelt nur sichtbare Inhalte und trennt zwischen der Entität und der Seite, die sie beschreibt.

**Block für die Entität:**

- `@type`, `name`, `description` und `url`
- `additionalProperty` aus den Fakten, sofern Schema.org die Eigenschaft für den Typ vorsieht

`additionalProperty` erhalten die Typen `Accommodation`, `AdministrativeArea`, `CivicStructure`, `IndividualProduct`, `Landform`, `LocalBusiness`, `Place`, `Product`, `ProductGroup`, `ProductModel`, `Residence`, `TouristAttraction` und `Vehicle`, auch wenn sie als eigener Typ eingetragen sind. Bei allen anderen Typen, zum Beispiel `Organization` oder `Person`, stehen die Fakten sichtbar auf der Seite, aber nicht im JSON-LD.

**`WebPage`-Knoten**, den Contao selbst im Schema.org-Graphen anlegt:

- `dateModified`: Datum der letzten Änderung am Eintrag oder an seinen Inhaltselementen
- `citation` aus den Quellen
- `lastReviewed` und `reviewedBy`, nur bei aktueller Prüfung

Enthält der Eintrag FAQ-Elemente, folgt ein weiterer Block vom Typ [`FAQPage`](https://schema.org/FAQPage).

## Sprache

Die Zwischenüberschriften und Datumsbeschriftungen im Reader folgen der Sprache der Website-Root. Mitgeliefert werden Deutsch und Englisch. Die Inhalte selbst werden je Sprache in eigenen Einträgen gepflegt, zum Beispiel in einem Archiv je Sprache.

Eigene Texte oder weitere Sprachen lassen sich im Projekt über die Schlüssel `MSC.grounding_page_*` ergänzen.

## Berechtigungen

Administratoren dürfen alles. Für andere Benutzer wird in der Benutzergruppe freigegeben:

1. unter den Backend-Modulen **Grounding-Pages**
2. unter **Grounding-Pages** die erlaubten Archive, bei Bedarf „Neue Archive anlegen“, „Archive/Einträge löschen“ und „Prüfung dokumentieren“
3. die erlaubten Felder der Archive und Einträge sowie die erlaubten Inhaltselemente (ohne freigegebene Felder lässt Contao nichts anlegen)
4. ab Contao 5.7: die erlaubten Operationen (anlegen, bearbeiten, löschen) für Grounding-Page-Archive, -Einträge und Inhaltselemente

Das Protokoll lesen darf, wer Zugriff auf das Archiv hat. Zum Dokumentieren genügt zusätzlich „Prüfung dokumentieren“, die Punkte 3 und 4 sind dafür nicht nötig.

## Templates anpassen

Das Bundle verwendet Twig-Templates:

- `frontend_module/grounding_page_list`
- `frontend_module/grounding_page_reader`
- `content_element/grounding_page_faq`

Zum Anpassen eine gleichnamige Datei im Projekt anlegen, zum Beispiel `templates/frontend_module/grounding_page_reader.html.twig`, und vom Original erben:

```twig
{% extends "@Contao/frontend_module/grounding_page_reader.html.twig" %}
```

Der Reader übergibt den Prüfhinweis als `review` (`reviewer`, `date`, `dateIso`, sonst `null`) und den Link der Konformitätsangabe als `conformityUrl`.

Das Bundle liefert bewusst kein CSS. Jedes Archiv kann eine CSS-Klasse erhalten, die der Reader am umschließenden `<article>` ausgibt.

## Bekannte Einschränkungen

- Mehrsprachige Einträge sind nicht miteinander verknüpft, gemeinsam genutzte Werte gibt es noch nicht.
- Das Löschen oder Ausblenden eines Inhaltselements ändert das Datum „Zuletzt aktualisiert“ nicht. Dazu den Eintrag selbst ändern und speichern.
- Legt ein Benutzer ohne Administratorrechte ein neues Archiv an, muss ihm dieses anschließend in den Berechtigungen freigegeben werden.
- Der Reader setzt weder Seitentitel noch Meta-Beschreibung aus dem Eintrag.
- Antworten im FAQ-Element sind einzeiliger Text ohne Formatierung.
- Sprachverknüpfungen (`hreflang`) werden nicht erzeugt.
- Das Upgrade einer Installation mit Inhalten von Contao 5 auf 6 ist noch nicht getestet. Contao verlangt dafür den Weg über 5.7.

## Umstieg von 0.1.0

- Nach dem Update `contao:migrate` ausführen.
- Das Feld „Zuletzt geprüft am“ entfällt in Formular und Frontend. Die Daten bleiben in der Datenbank, zählen aber nicht als Prüfung. Jeder Eintrag zeigt erst nach einer dokumentierten Prüfung wieder einen Prüfhinweis.
- Eigene Reader-Templates anpassen: `lastReviewed` und `lastReviewedIso` gibt es nicht mehr.
- Eigene Rechtegruppen um „Prüfung dokumentieren“ ergänzen. Administratoren brauchen nichts zu ändern.

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
