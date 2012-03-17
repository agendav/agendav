<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed'); 
/*
 * by Hermann Schwärzler - hermann [dot] schwaerzler [at] chello [dot] at
 * derived from de_DE file (copyright 2011 Andreas Stöckel)
 *
 *  This file is part of AgenDAV.
 *
 *  AgenDAV is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  any later version.
 *
 *  AgenDAV is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with AgenDAV.  If not, see <http://www.gnu.org/licenses/>.
 */

/*
 * German (for Austria) master language file
 */

$labels = array();
$messages = array();

// Labels

$labels['format_date_strftime'] = '%a %e %B %Y'; // Just date with no time, strftime format
// Fullcalendar labels 
// (http://arshaw.com/fullcalendar/docs/utilities/formatDate/)
$labels['format_column_month'] = 'ddd'; 
$labels['format_column_week'] = 'ddd d'; 
$labels['format_column_day'] = 'ddd d MMMM'; 

$labels['format_title_month'] = 'MMMM yyyy';
$labels['format_title_week'] = "MMM d[ yyyy]{ '&#8212;'[ MMM] d yyyy}";
$labels['format_title_day'] = 'dddd, MMM d yyyy';

$labels['username'] = 'Benutzername';
$labels['password'] = 'Passwort';

$labels['january'] = 'Jänner';
$labels['february'] = 'Februar';
$labels['march'] = 'März';
$labels['april'] = 'April';
$labels['may'] = 'Mai';
$labels['june'] = 'Juni';
$labels['july'] = 'Juli';
$labels['august'] = 'August';
$labels['september'] = 'September';
$labels['october'] = 'Oktober';
$labels['november'] = 'November';
$labels['december'] = 'Dezember';

$labels['january_short'] = 'Jan';
$labels['february_short'] = 'Feb';
$labels['march_short'] = 'Mär';
$labels['april_short'] = 'Apr';
$labels['may_short'] = 'Mai';
$labels['june_short'] = 'Jun';
$labels['july_short'] = 'Jul';
$labels['august_short'] = 'Aug';
$labels['september_short'] = 'Sep';
$labels['october_short'] = 'Okt';
$labels['november_short'] = 'Nov';
$labels['december_short'] = 'Dez';

$labels['sunday'] = 'Sonntag';
$labels['monday'] = 'Montag';
$labels['tuesday'] = 'Dienstag';
$labels['wednesday'] = 'Mittwoch';
$labels['thursday'] = 'Donnerstag';
$labels['friday'] = 'Freitag';
$labels['saturday'] = 'Samstag';

$labels['sunday_short'] = 'So';
$labels['monday_short'] = 'Mo';
$labels['tuesday_short'] = 'Di';
$labels['wednesday_short'] = 'Mi';
$labels['thursday_short'] = 'Do';
$labels['friday_short'] = 'Fr';
$labels['saturday_short'] = 'Sa';

$labels['today'] = 'Heute';
$labels['month'] = 'Monat';
$labels['week'] = 'Woche';
$labels['day'] = 'Tag';
$labels['allday'] = 'ganztags';
$labels['choose_date'] = 'Wähle Datum';

$labels['calendar'] = 'Kalender';
$labels['location'] = 'Ort';
$labels['description'] = 'Beschreibung';

$labels['displayname'] = 'Anzeigename';
$labels['internalname'] = 'Interner Name';
$labels['optional'] = '(optional)';
$labels['color'] = 'Farbe';

$labels['summary'] = 'Zusammenfassung';
$labels['startdate'] = 'Startdatum';
$labels['enddate'] = 'Enddatum';
$labels['starttime'] = 'Startzeit';
$labels['endtime'] = 'Endzeit';
$labels['alldayform'] = 'Ganztags';

$labels['repetitionexceptions'] = 'Ausnahmen des sich wiederholenden Termins';

$labels['repeat'] = 'Wiederholen';
$labels['repeatno'] = 'keine Wiederholung';
$labels['repeatdaily'] = 'täglich';
$labels['repeatweekly'] = 'wöchentlich';
$labels['repeatmonthly'] = 'monatlich';
$labels['repeatyearly'] = 'jährlich';

$labels['repeatcount'] = 'Zahl der Wiederholungen';
$labels['repeatuntil'] = 'Bis';

$labels['explntimes'] = '%n-mal';
$labels['expluntil'] = 'bis %d';

$labels['privacy'] = 'Privatsphäre';
$labels['public'] = 'Öffentlich';
$labels['private'] = 'Privat';
$labels['confidential'] = 'Vertraulich';

$labels['transp'] = 'Zeige diese Zeit an als';
$labels['opaque'] = 'Beschäftigt';
$labels['transparent'] = 'Frei';

$labels['generaloptions'] = 'Allgemeine Einstellungen';
$labels['repeatoptions'] = 'Wiederholungen';
$labels['workgroupoptions'] = 'Arbeitsgruppe';
$labels['shareoptions'] = 'Teilen';

$labels['newcalendar'] = 'Neuer Kalender';
$labels['modifycalendar'] = 'Kalender bearbeiten';

$labels['createevent'] = 'Termin anlegen';
$labels['editevent'] = 'Termin bearbeiten';
$labels['deleteevent'] = 'Termin löschen';
$labels['deletecalendar'] = 'Kalender löschen';
$labels['calendars'] = 'Kalender';
$labels['refresh'] = 'Aktualisieren';
$labels['delete'] = 'Löschen';
$labels['close'] = 'Schließen';
$labels['save'] = 'Speichern';
$labels['create'] = 'Anlegen';
$labels['login'] = 'Anmelden';
$labels['modify'] = 'Ändern';
$labels['cancel'] = 'Abbrechen';
$labels['next'] = 'Nächstes';
$labels['previous'] = 'Vorheriges';
$labels['yes'] = 'Ja';

$labels['untitled'] = 'Unbenannt';

$labels['sharewith'] = 'Teilen mit';
$labels['publicurl'] = 'URL für Terminprogramme';

// Messages
$messages['error_auth'] = 'Ungültiger Benutzername oder Passwort';
$messages['error_invaliddate'] = 'Ungültiges Datum in Feld %s';
$messages['error_invalidtime'] = 'Ungültige Zeit in Feld %s';
$messages['error_denied'] = 'Der Server hat ihre Anforderung abgelehnt (permission forbidden)';
$messages['error_notimplemented'] = '%feature: Noch nicht implementiert';
$messages['error_startgreaterend'] = 'Das Enddatum muss nach oder auf dem Startdatum liegen';
$messages['error_bogusrepeatrule'] = 'Fehler, überprüfen Sie die Wiederholungs-Einstellungen';
$messages['error_internalgen'] = 'Interner Fehler beim Anlegen des Kalenders';
$messages['error_internalcalnameinuse'] = 'Interner Kalendername wird bereits genutzt';

$messages['info_confirmcaldelete'] = 'Sind Sie sich sicher, dass Sie den folgenden Kalender löschen möchten?';
$messages['info_confirmeventdelete'] = 'Sind Sie sich sicher, dass Sie den folgenden Termin löschen möchten?';
$messages['info_permanentremoval'] = 'Ihre Informationen werden unwiederrufbar gelöscht';
$messages['info_repetitivedeleteall'] = 'Alle Wiederholungsinstanzen des Termins werden gelöscht';
$messages['info_sharedby'] = 'Sie haben Zugriff auf diesen Kalender, da %user ihn mit Ihnen teilt';
$messages['info_shareexplanation'] = 'Sie können diesen Kalender mit anderen
Nutzern teilen und bearbeiten lassen. Geben Sie unten die Benutzernamen ein, abgetrennt
durch Kommas oder Leerzeichen';
$messages['error_sessexpired'] = 'Ihre Sitzung ist abgelaufen';
$messages['error_loginagain'] = 'Bitte melden Sie sich erneut an';

$messages['error_modfailed'] = 'Änderungen Fehlgeschlagen';
$messages['error_loadevents'] = 'Fehler beim Laden der Termine vom Kalender %cal';
$messages['error_sessrefresh'] = 'Fehler beim Aktualisieren Ihrer Sitzung';
$messages['error_internal'] = 'Interner Fehler';
$messages['error_genform'] = 'Fehler beim Anlegen des Dialogs';
$messages['error_invalidinput'] = 'Ungültiger Wert';
$messages['error_caldelete'] = 'Fehler beim Löschen des Kalenders';

$messages['overlay_synchronizing'] = 'Synchronisiere Termine...';
$messages['overlay_loading_dialog'] = 'Lade Dialogfenster...';
$messages['overlay_sending_form'] = 'Sende Formulardaten...';
$messages['overlay_loading_calendar_list'] = 'Lade Kalenderliste...';
$messages['error_loading_dialog'] = 'Fehler beim Laden des Dialogfensters';

$messages['error_oops'] = 'Ups. Unerwarteter Fehler';
$messages['error_interfacefailure'] = 'Schnittstellenfehler';
$messages['error_current_event_not_loaded'] = 'Der aktuelle Termin ist nicht verfügbar';

$messages['error_event_not_deleted'] = 'Fehler beim Löschen des Termins';
$messages['error_loading_calendar_list'] = 'Fehler beim Laden der Kalenderliste';
$messages['notice_no_calendars'] = 'Keine Kalender verfügbar';
$messages['info_repetition_human'] = 'Dieser Termin wiederholt sich %explanation';
$messages['info_repetition_unparseable'] = 'Dieser Termin besitzt Wiederholungsregeln, die dieses Programm nicht versteht. Rohfassung:';
$messages['error_calendarnotfound'] = 'Ungültiger Kalender %calendar';
$messages['error_eventnotfound'] = 'Element nicht gefunden';
$messages['error_eventchanged'] = 'Das Element wurde geändert, während Sie es bearbeitet haben. Bitte aktualisieren Sie die Ansicht.';
$messages['error_unknownhttpcode'] = 'Unbekannter Fehler, HTTP code=%res';
$messages['error_internalcalnamemissing'] = 'Leerer interner Kalendername';
$messages['error_calname_missing'] = 'Leerer Kalendername';
$messages['error_calcolor_missing'] = 'Eine Farbe muss angegeben werden.';
$messages['error_mkcalendar'] = 'Der Server widerstrebt sich den Kalender anzulegen. Bitte überprüfen Sie Ihre Eingaben.';
$messages['error_shareunknownusers'] = 'Einige der Benutzer, die Sie angegeben haben existieren nicht.';
