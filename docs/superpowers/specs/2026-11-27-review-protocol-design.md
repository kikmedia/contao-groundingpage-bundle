# Prüfprotokoll nach Grounding Page Standard 1.7 (Version 0.2.0)

Stand: 2026-11-27. Status: freigegeben am 2026-11-27, einschließlich der Vorschläge.

Grundlagen: [Issue #5](https://github.com/kikmedia/contao-groundingpage-bundle/issues/5), [Standard 1.7](https://groundingpage.com/spec/v1.7/de/), Abschnitt 7 und die Anforderungen P-1 bis P-16 aus [Standard 2.0](https://groundingpage.com/spec/de/).

Mit **(Vorschlag)** markierte Punkte sind noch nicht ausdrücklich entschieden.

## Absicht

Eine mit dem Bundle gepflegte Seite soll Standard 1.7 erfüllen können. Dafür dokumentiert ein Mensch die Faktenprüfung, die Seite zeigt Prüfer und Datum, und das Backend zeigt, was zur Konformität noch fehlt.

## Ziele

- Prüfprotokoll je Eintrag mit vollständiger Historie
- Sichtbarer Prüfhinweis, identisch mit `reviewedBy` und `lastReviewed` am `WebPage`-Knoten
- Prüfhinweis verschwindet, sobald sich der geprüfte Inhalt ändert
- Eigenes Recht „Prüfung dokumentieren“
- Prüfergebnisse im Backend und eine optionale Konformitätsangabe

## Nicht Teil dieser Version

Standard 2.0 (Reference Integrity, siebter Prüfpunkt), gemeinsame Werte über Sprachen, Human-Verified-Badge, Veröffentlichung nur des geprüften Stands, automatische Faktenprüfung, Seitentitel und Meta-Beschreibung aus dem Eintrag, Erinnerungen per E-Mail.

## Darf nie passieren

- Ein Prüfhinweis ohne dokumentierte Freigabe des aktuell sichtbaren Inhalts.
- Ein Prüfdatum, das durch Speichern, Update, Migration oder Automatik entsteht.
- Ein altes „Zuletzt geprüft am“, das als menschliche Prüfung erscheint.

Im Zweifel gilt eine Prüfung als veraltet.

## Rahmen

- Contao 5.3 bis 6.0, PHP ab 8.3
- Keine Kommentare im Code
- Backend und Frontend auf Deutsch und Englisch
- Neues Verhalten wird im Abnahmetest gegen echte Contao-Installationen geprüft
- Brüche gegenüber alpha.2 sind zulässig, stehen aber im CHANGELOG

## Betrachtete Wege

| Weg | Dafür | Dagegen |
|---|---|---|
| 1. Prüffelder direkt am Eintrag | wenig Aufwand | keine Historie, Wiederherstellen einer Version holt alte Prüfdaten zurück |
| **2. Eigenes Prüfprotokoll (gewählt)** | vollständige Historie, sauber über Rechte geschützt | neue Tabelle; ungeprüfte Änderungen können online gehen, dann ohne Prüfhinweis |
| 3. Öffentlich nur der geprüfte Stand | erzwingt „erst prüfen, dann veröffentlichen“ | Inhaltselemente müssten eingefroren werden, sehr aufwendig, für Redakteure ungewohnt |

Entschieden wurden Weg 2 und ein eigenes Recht zum Dokumentieren.

## Rechte

- Neuer Wert **„Prüfung dokumentieren“** in den Grounding-Page-Berechtigungen von Benutzergruppe und Benutzer. Administratoren dürfen immer.
- Das Recht gilt nur für Archive, auf die der Benutzer Zugriff hat.
- Wer Zugriff auf ein Archiv hat, darf das Protokoll lesen.
- Dokumentieren und „unwesentlich“ bestätigen darf nur, wer das Recht hat.
- **(Vorschlag)** Gespeicherte Prüfungen kann niemand bearbeiten oder kopieren, auch nicht in der Mehrfachbearbeitung. Löschen dürfen nur Administratoren.
- **(Vorschlag)** Das neue Recht genügt allein. Zusätzliche Feld- oder Operationsfreigaben für das Protokoll sind nicht nötig, soweit Contao das zulässt. Andernfalls beschreibt das README die nötigen Freigaben.

## Prüfung dokumentieren

Am Eintrag gibt es die Aktion **„Prüfungen“**. Sie zeigt alle Prüfungen mit Datum, öffentlichem Prüfer, dokumentierendem Benutzer, Ergebnis und Zustand.

| Feld | Regel |
|---|---|
| Datum | Vorgabe heute, nicht in der Zukunft, nicht vor der letzten Prüfung dieses Eintrags **(Vorschlag)** |
| Prüfer, Art | Person oder Organisation, Pflicht |
| Prüfer, Name | öffentlich, Pflicht |
| Dokumentiert von | angemeldeter Benutzer mit ID und Name, automatisch, nie öffentlich |
| Prüfpunkte | fünf Häkchen: faktische Richtigkeit; Aktualität inkl. Stand-Daten; Nachprüfbarkeit und Quellenqualität; eindeutige Entität und Abgrenzung; Übereinstimmung mit strukturierten Daten, Seitentitel, Meta-Beschreibung und Alt-Texten |
| Ergebnis | „freigegeben“ (sechster Prüfpunkt, die redaktionelle Freigabe) oder „nicht freigegeben“ |
| Notiz | intern; Pflicht bei „nicht freigegeben“ **(Vorschlag)** |

- „Freigegeben“ ist nur mit allen fünf Häkchen möglich.
- Gespeichert werden außerdem die Standardversion `1.7` und der Fingerabdruck des geprüften Inhalts.
- Hat sich der Inhalt zwischen Öffnen und Speichern des Formulars geändert, wird nicht gespeichert, sondern darauf hingewiesen.
- **(Vorschlag)** Prüfer wird aus der letzten Prüfung des Eintrags vorbelegt, sonst aus neuen Archivfeldern „Standard-Prüfer“ (Art, Name), sonst leer.
- Abgebrochene, nie gespeicherte Datensätze zählen nirgends.
- Eine Prüfung ist auch für unveröffentlichte Einträge möglich.
- Beim Kopieren eines Eintrags wird das Protokoll nicht mitkopiert. Beim Löschen wird es mitgelöscht.

## Fingerabdruck und veraltete Prüfungen

Maßgeblich ist immer die zuletzt gespeicherte Prüfung. Stimmt ihr Fingerabdruck nicht mit dem aktuellen Inhalt überein, ist sie veraltet. Wird eine Änderung zurückgenommen, gilt die Prüfung wieder.

**Enthalten:**

- Entitätsname, Typ, eigener Typ
- Definition, Einordnung, Abgrenzung
- Kernfakten (einschließlich Reihenfolge), Quellen
- verwandte Einträge (ohne Reihenfolge)
- Bild: Schalter und Datei
- alle nicht versteckten Inhaltselemente einschließlich verschachtelter, mit ihren inhaltlichen Feldern, auch wenn sie zeitgesteuert gerade nicht erscheinen

**Nicht enthalten:** Alias, Autor, Veröffentlichung, Bildgröße sowie technische und Layout-Felder der Inhaltselemente (zum Beispiel ID, Sortierung, Zeitstempel, CSS-Klasse, eigenes Template, Zugriffsschutz).

- Es werden alle übrigen Spalten der Inhaltselemente berücksichtigt, damit auch Felder fremder Erweiterungen zählen.
- Werte werden vereinheitlicht: leere Werte entfallen, HTML-Entitäten werden dekodiert, serialisierte Listen entpackt. So macht ein Wechsel von Contao 5 auf 6 allein keine Prüfung ungültig.
- Der Fingerabdruck trägt eine Verfahrensversion.
- Ergänzt ein Update eine Spalte mit nicht leerem Vorgabewert, werden Prüfungen veraltet. Das ist die sichere Richtung.

**Nicht erkannt** (kommt ins README): Änderungen an Dateimetadaten wie Alt-Texten, an eingebundenen Modulen oder referenzierten Elementen, an Titeln verwandter Einträge und an Templates.

## Unwesentliche Änderungen

Ist die letzte Prüfung freigegeben oder bereits als unwesentlich fortgeschrieben, aber veraltet, bietet das Protokoll **„Änderung als unwesentlich bestätigen“**.

- Es entsteht ein eigener Datensatz mit neuem Fingerabdruck, Benutzer, Zeitpunkt und Verweis auf die freigegebene Prüfung.
- **(Vorschlag)** Eine Notiz, was geändert wurde, ist Pflicht.
- Öffentlich bleiben Prüfer und Datum der ursprünglichen Prüfung.
- Das Formular nennt, was nach dem Standard unwesentlich ist: Tippfehler, Layout, Umformulierungen ohne Faktenänderung, Versionshinweis.

## Frontend

Ein Prüfhinweis erscheint nur, wenn die letzte Prüfung freigegeben (oder als unwesentlich fortgeschrieben) und nicht veraltet ist. Dann gilt:

- Am Ende des Readers, vor dem JSON-LD:
  - DE: „Fakten geprüft von %s · Zuletzt von einem Menschen geprüft: %s“
  - EN: „Facts checked by %s · Last reviewed by a human: %s“
- Am `WebPage`-Knoten von Contao: `lastReviewed` (Datum) und `reviewedBy` (`Person` oder `Organization` mit `name`)

Sonst erscheint weder Hinweis noch Platzhalter noch Auszeichnung. „Zuletzt aktualisiert“ bleibt unverändert.

## Konformitätsangabe

- Neues Häkchen am Eintrag: „Konformität mit Version 1.7 angeben“, Vorgabe aus, wird nicht mitkopiert.
- Sie erscheint nur, wenn das Häkchen gesetzt ist, ein Prüfhinweis erscheint und alle Pflichtprüfungen bestehen.
- **(Vorschlag)** Text „Grounding Page Standard 1.7“ unter dem Prüfhinweis, verlinkt auf `https://groundingpage.com/spec/v1.7/de/`, in anderen Sprachen auf `https://groundingpage.com/spec/v1.7/`.

## Prüfungen im Backend

Oben im Eintragsformular steht eine Ergebnisliste zum gespeicherten Stand. Die Eintragsliste zeigt je Eintrag „Prüfung aktuell“, „veraltet“, „nicht freigegeben“ oder „fehlt“. Dieselbe Logik entscheidet im Frontend über die Konformitätsangabe.

**Pflicht** (verhindert die Konformitätsangabe):

| Bezug | Bestanden, wenn |
|---|---|
| P-2 | Definition ausgefüllt |
| P-3 | jede H2 in Inhaltselementen (Überschrift oder `<h2>` im Text) den Entitätsnamen enthält, ohne Beachtung der Groß- und Kleinschreibung |
| P-4 | mindestens ein Kernfakt mit Bezeichnung und Wert vorhanden ist |
| P-5 | der Typ gesetzt und weder `Thing` noch `WebPage` ist |
| P-7 | Eintrag veröffentlicht und im Zeitraum; Weiterleitungsseite vorhanden, veröffentlicht, ohne Zugriffsschutz und nicht auf `noindex` |
| P-8 | die letzte Prüfung freigegeben und aktuell ist |

P-6, P-9 und P-10 erfüllt das Bundle durch seinen Aufbau, solange Templates nicht verändert werden. Sie werden als Information angezeigt. P-1 folgt mit 0.3.0.

**Hinweis** (warnt nur):

| Bezug | Warnung, wenn |
|---|---|
| P-6 | ein Bild eingebunden ist, dem in der Sprache der Weiterleitungsseite der Alt-Text fehlt |
| P-11 | die Abgrenzung fehlt |
| P-12 | keine Quelle vorhanden ist oder eine Quelle keine `http`- oder `https`-Adresse hat |
| P-15 | das Archiv keine Weiterleitungsseite hat oder in keinem Listen-Modul gewählt ist |
| P-16 | das Datum der freigegebenen Prüfung (nicht einer Bestätigung „unwesentlich“) älter als 6 Monate ist; deutlicher ab 12 Monaten |

**(Vorschlag)** Abweichend von Issue #5 werden Seitentitel und Meta-Beschreibung nicht automatisch geprüft, weil das Bundle sie noch nicht aus dem Eintrag setzt. Sie gehören zum fünften Prüfpunkt. Die 12-Monats-Grenze ist nur ein Hinweis, weil es das Badge in dieser Version nicht gibt.

## Bestehende Installationen

- `contao:migrate` legt die neue Tabelle und die neuen Felder an.
- Das bisherige Feld „Zuletzt geprüft am“ bleibt in der Datenbank, verschwindet aber aus Formular und Frontend. Die Ergebnisliste nennt ein vorhandenes altes Datum als „ohne Protokoll, wird nicht angezeigt“.
- Die Template-Variablen `lastReviewed` und `lastReviewedIso` entfallen. Eigene Reader-Templates müssen angepasst werden.
- README und CHANGELOG beschreiben das. `docs/` kommt nicht in das Composer-Paket.

## Abnahme

Automatisiert, auf allen unterstützten Kombinationen:

1. Ohne Recht lässt sich keine Prüfung anlegen, auch nicht über die Adresse; mit Recht schon, nur in erlaubten Archiven.
2. Nach einer Freigabe stehen Hinweis, `reviewedBy` und `lastReviewed` mit gleichen Werten auf der Seite.
3. Geänderter Fakt, geändertes, gelöschtes und verstecktes Inhaltselement entfernen den Hinweis; Zurücknehmen stellt ihn her.
4. „Unwesentlich“ stellt den Hinweis mit altem Datum und Prüfer wieder her.
5. „Nicht freigegeben“ entfernt den Hinweis.
6. Gespeicherte Prüfungen sind nicht bearbeitbar; Kopien eines Eintrags haben kein Protokoll.
7. Die Konformitätsangabe erscheint nur mit Häkchen, aktueller Prüfung und bestandenen Pflichtprüfungen.
8. Ein altes Prüfdatum erscheint nicht.

Fingerabdruck und Prüfungen haben zusätzlich Unit-Tests.

## Dem Umsetzenden überlassen

Klassen- und Feldnamen, genaue Liste der ausgeschlossenen Layout-Felder nach dem beschriebenen Grundsatz, technische Umsetzung der Backend-Ansichten, Reihenfolge der Arbeitspakete.
