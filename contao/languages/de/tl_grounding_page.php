<?php

declare(strict_types=1);

$GLOBALS['TL_LANG']['tl_grounding_page']['title'] = ['Entitätsname', 'Der Name der Entität – wird als H1 ausgegeben. Bitte nur den Namen, keinen Slogan (z. B. "VeloNova", nicht "Die beste Lösung für X").'];
$GLOBALS['TL_LANG']['tl_grounding_page']['alias'] = ['Alias', 'Hier können Sie einen Alias für den Eintrag festlegen.'];
$GLOBALS['TL_LANG']['tl_grounding_page']['author'] = ['Autor', 'Wählen Sie den Autor bzw. die redaktionell verantwortliche Person aus.'];

$GLOBALS['TL_LANG']['tl_grounding_page']['entityType'] = ['Entitätstyp (Schema.org)', 'Der passende Schema.org-Typ der Entität. Wird 1:1 in das JSON-LD übernommen.'];
$GLOBALS['TL_LANG']['tl_grounding_page']['entityTypeCustom'] = ['Eigener Entitätstyp', 'Nur falls "Sonstiges" gewählt wurde: exakter Schema.org-Typ, z. B. "DefinedTerm" oder "SoftwareApplication".'];
$GLOBALS['TL_LANG']['tl_grounding_page']['entityTypes'] = [
    'Organization'        => 'Organisation',
    'Product'              => 'Produkt',
    'Person'                => 'Person',
    'Event'                 => 'Veranstaltung',
    'Place'                 => 'Ort',
    'Service'               => 'Dienstleistung',
    'SoftwareApplication'   => 'Software',
    'CreativeWork'          => 'Werk / Publikation',
    'DefinedTerm'           => 'Begriff / Konzept',
    'other'                 => 'Sonstiges …',
];

$GLOBALS['TL_LANG']['tl_grounding_page']['definition'] = ['Definition', 'Ein bis zwei Sätze: Was ist die Entität? Keine werblichen Formulierungen, keine Floskeln wie "In der heutigen Zeit …".'];
$GLOBALS['TL_LANG']['tl_grounding_page']['placement'] = ['Einordnung', 'In welchem Segment/Kontext ist die Entität verortet? (optional, aber empfohlen)'];
$GLOBALS['TL_LANG']['tl_grounding_page']['distinction'] = ['Abgrenzung', 'Wovon unterscheidet sich die Entität eindeutig? (optional, reduziert Verwechslungsgefahr)'];

$GLOBALS['TL_LANG']['tl_grounding_page']['coreFacts'] = ['Strukturierte Fakten', 'Attribut/Wert-Paare, die als <dl> und im JSON-LD ausgegeben werden, z. B. "Gegründet" → "2014". Kein Fließtext hier – dafür die Abschnitte weiter unten nutzen.'];
$GLOBALS['TL_LANG']['tl_grounding_page']['sources'] = ['Quellen', 'Titel/URL-Paare als Beleg für die Fakten dieser Seite.'];
$GLOBALS['TL_LANG']['tl_grounding_page']['relatedPages'] = ['Verwandte Entitäten', 'Andere Grounding-Pages, auf die diese Seite verweisen soll (interne Verlinkung).'];

$GLOBALS['TL_LANG']['tl_grounding_page']['addImage'] = ['Bild hinzufügen', 'Optional – der Standard erfordert kein Bild.'];
$GLOBALS['TL_LANG']['tl_grounding_page']['singleSRC'] = ['Bild', 'Wählen Sie eine Datei aus der Dateiübersicht.'];
$GLOBALS['TL_LANG']['tl_grounding_page']['size'] = ['Bildgröße', 'Hier können Sie die Bildgröße festlegen.'];

$GLOBALS['TL_LANG']['tl_grounding_page']['published'] = ['Veröffentlichen', 'Diesen Eintrag veröffentlichen.'];
$GLOBALS['TL_LANG']['tl_grounding_page']['start'] = ['Anzeigen ab', 'Der Eintrag wird erst ab diesem Zeitpunkt angezeigt.'];
$GLOBALS['TL_LANG']['tl_grounding_page']['stop'] = ['Anzeigen bis', 'Der Eintrag wird nur bis zu diesem Zeitpunkt angezeigt.'];

$GLOBALS['TL_LANG']['tl_grounding_page']['title_legend'] = 'Entität';
$GLOBALS['TL_LANG']['tl_grounding_page']['entity_legend'] = 'Entitätstyp';
$GLOBALS['TL_LANG']['tl_grounding_page']['lead_legend'] = 'Lead: Definition, Einordnung, Abgrenzung';
$GLOBALS['TL_LANG']['tl_grounding_page']['facts_legend'] = 'Strukturierte Fakten';
$GLOBALS['TL_LANG']['tl_grounding_page']['sources_legend'] = 'Quellen';
$GLOBALS['TL_LANG']['tl_grounding_page']['related_legend'] = 'Verwandte Entitäten';
$GLOBALS['TL_LANG']['tl_grounding_page']['media_legend'] = 'Medien (optional)';
$GLOBALS['TL_LANG']['tl_grounding_page']['publish_legend'] = 'Veröffentlichung';

$GLOBALS['TL_LANG']['tl_grounding_page']['edit'] = ['Eintrag bearbeiten', 'Eintrag ID %s bearbeiten'];
$GLOBALS['TL_LANG']['tl_grounding_page']['copy'] = ['Eintrag duplizieren', 'Eintrag ID %s duplizieren'];
$GLOBALS['TL_LANG']['tl_grounding_page']['cut'] = ['Eintrag verschieben', 'Eintrag ID %s verschieben'];
$GLOBALS['TL_LANG']['tl_grounding_page']['delete'] = ['Eintrag löschen', 'Eintrag ID %s löschen'];
$GLOBALS['TL_LANG']['tl_grounding_page']['toggle'] = ['Veröffentlichung umschalten', 'Veröffentlichung des Eintrags ID %s umschalten'];
$GLOBALS['TL_LANG']['tl_grounding_page']['show'] = ['Eintragsdetails', 'Details des Eintrags ID %s anzeigen'];
$GLOBALS['TL_LANG']['tl_grounding_page']['new'] = ['Neuer Eintrag', 'Einen neuen Eintrag erstellen'];
$GLOBALS['TL_LANG']['tl_grounding_page']['children'] = ['Inhaltselemente', 'Die Inhaltselemente des Eintrags ID %s bearbeiten'];
$GLOBALS['TL_LANG']['tl_grounding_page']['reviews'] = ['Prüfungen', 'Prüfungen des Eintrags ID %s'];

$GLOBALS['TL_LANG']['tl_grounding_page']['claimConformity'] = ['Konformität mit Version 1.7 angeben', 'Zeigt unter dem Prüfhinweis „Grounding Page Standard 1.7“, solange die Prüfung aktuell ist und alle Pflichtprüfungen bestehen.'];
$GLOBALS['TL_LANG']['tl_grounding_page']['check_legend'] = 'Prüfung nach Standard 1.7';
$GLOBALS['TL_LANG']['tl_grounding_page']['checkHeading'] = 'Prüfergebnisse (Stand: zuletzt gespeichert)';
$GLOBALS['TL_LANG']['tl_grounding_page']['reviewStates'] = [
    'current' => 'Prüfung aktuell',
    'stale' => 'Prüfung veraltet',
    'rejected' => 'Prüfung nicht freigegeben',
    'missing' => 'Prüfung fehlt',
];
$GLOBALS['TL_LANG']['tl_grounding_page']['checkStates'] = [
    'passed' => 'erfüllt',
    'open' => 'offen',
    'hint' => 'Hinweis',
    'info' => 'Information',
];
$GLOBALS['TL_LANG']['tl_grounding_page']['checks'] = [
    'p2' => 'P-2: Die Definition ist ausgefüllt.',
    'p3' => 'P-3: Jede H2-Überschrift nennt den Entitätsnamen.',
    'p4' => 'P-4: Mindestens ein Kernfakt hat Bezeichnung und Wert.',
    'p5' => 'P-5: Ein spezifischer Entitätstyp ist gewählt (weder Thing noch WebPage).',
    'p7' => 'P-7: Eintrag und Weiterleitungsseite sind öffentlich erreichbar und indexierbar.',
    'p8' => 'P-8: Eine Faktenprüfung ist freigegeben und aktuell.',
    'p6_alt' => 'P-6: Dem Bild fehlt in der Sprache der Weiterleitungsseite der Alt-Text.',
    'p11' => 'P-11: Die Abgrenzung fehlt.',
    'p12' => 'P-12: Es fehlt eine Quelle oder eine Quelle hat keine http(s)-Adresse.',
    'p15' => 'P-15: Das Archiv hat keine Weiterleitungsseite oder ist in keinem Listen-Modul gewählt.',
    'p16' => 'P-16: Die letzte Faktenprüfung ist älter als 6 Monate.',
    'p16_late' => 'P-16: Die letzte Faktenprüfung ist älter als 12 Monate und sollte dringend wiederholt werden.',
    'p6' => 'P-6: Die strukturierten Daten stammen aus den sichtbaren Feldern (bei unveränderten Templates).',
    'p9' => 'P-9: Prüfhinweis, reviewedBy und lastReviewed stammen aus derselben dokumentierten Prüfung (bei unveränderten Templates).',
    'p10' => 'P-10: Die Konformitätsangabe nennt Version 1.7 und erscheint nur bei bestandenen Pflichtprüfungen (bei unveränderten Templates).',
    'legacy_date' => 'Altes Prüfdatum %s: ohne Protokoll, wird nicht angezeigt.',
];
