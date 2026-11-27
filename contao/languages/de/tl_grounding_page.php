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

$GLOBALS['TL_LANG']['tl_grounding_page']['lastReviewed'] = ['Zuletzt geprüft am', 'Nur ändern, wenn tatsächlich eine redaktionelle Prüfung stattgefunden hat – nicht automatisch bei jedem Speichern. Empfehlung: alle 3–6 Monate.'];

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
$GLOBALS['TL_LANG']['tl_grounding_page']['review_legend'] = 'Aktualität';
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
