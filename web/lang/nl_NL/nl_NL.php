<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed'); 
/*
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

$labels = array();
$messages = array();

// Labels
$labels['username'] = 'Gebruikersnaam';
$labels['password'] = 'Wachtwoord';

$labels['january'] = 'januari';
$labels['february'] = 'februari';
$labels['march'] = 'maart';
$labels['april'] = 'april';
$labels['may'] = 'mei';
$labels['june'] = 'juni';
$labels['july'] = 'juli';
$labels['august'] = 'augustus';
$labels['september'] = 'september';
$labels['october'] = 'october';
$labels['november'] = 'november';
$labels['december'] = 'december';

$labels['january_short'] = 'jan';
$labels['february_short'] = 'feb';
$labels['march_short'] = 'mrt';
$labels['april_short'] = 'apr';
$labels['may_short'] = 'mei';
$labels['june_short'] = 'jun';
$labels['july_short'] = 'jul';
$labels['august_short'] = 'aug';
$labels['september_short'] = 'sep';
$labels['october_short'] = 'oct';
$labels['november_short'] = 'nov';
$labels['december_short'] = 'dec';

$labels['sunday'] = 'zondag';
$labels['monday'] = 'maandag';
$labels['tuesday'] = 'dinsdag';
$labels['wednesday'] = 'woensdag';
$labels['thursday'] = 'donderdag';
$labels['friday'] = 'vrijdag';
$labels['saturday'] = 'zaterdag';

$labels['sunday_short'] = 'zo';
$labels['monday_short'] = 'ma';
$labels['tuesday_short'] = 'di';
$labels['wednesday_short'] = 'wo';
$labels['thursday_short'] = 'do';
$labels['friday_short'] = 'vr';
$labels['saturday_short'] = 'za';

$labels['today'] = 'Vandaag';
$labels['tomorrow'] = 'Morgen';
$labels['month'] = 'maand';
$labels['week'] = 'week';
$labels['day'] = 'dag';
$labels['tableview'] = 'agenda';
$labels['allday'] = 'Hele dag';
$labels['choose_date'] = 'Kies datum';

$labels['thisweek'] = 'Deze week';
$labels['nextweek'] = 'Volgende week';
$labels['thismonth'] = 'Deze maand';
$labels['nextmonth'] = 'Volgende maand';
$labels['future'] = 'Toekomstige gebeurtenissen';

$labels['calendar'] = 'Agenda';
$labels['location'] = 'Locatie';
$labels['description'] = 'Beschrijving';

$labels['displayname'] = 'Agendanaam';
$labels['internalname'] = 'Interne naam';
$labels['optional'] = '(optioneel)';
$labels['color'] = 'Kleur';

$labels['summary'] = 'Titel';
$labels['startdate'] = 'Begindatum';
$labels['enddate'] = 'Einddatum';
$labels['starttime'] = 'Begintijd';
$labels['endtime'] = 'Eindtijd';
$labels['alldayform'] = 'Hele dag';

$labels['repetitionexceptions'] = 'Uitzonderingen op herhalende gebeurtenissen';

$labels['repeat'] = 'Herhalen';
$labels['repeatno'] = 'Geen herhalingen';
$labels['repeatdaily'] = 'Dagelijks';
$labels['repeatweekly'] = 'Wekelijks';
$labels['repeatmonthly'] = 'Maandelijks';
$labels['repeatyearly'] = 'Jaarlijks';

$labels['repeatcount'] = 'Aantal';
$labels['repeatuntil'] = 'Tot';

$labels['explntimes'] = '%n keer';
$labels['expluntil'] = 'tot %d';

$labels['privacy'] = 'Privacy';
$labels['public'] = 'Openbaar';
$labels['private'] = 'Prive';
$labels['confidential'] = 'Vertrouwelijk';

$labels['transp'] = 'Toon tijd als';
$labels['opaque'] = 'Bezet';
$labels['transparent'] = 'Beschikbaar';

$labels['generaloptions'] = 'Algemene opties';
$labels['repeatoptions'] = 'Herhalen';
$labels['workgroupoptions'] = 'Privacy';
$labels['shareoptions'] = 'Delen';

$labels['newcalendar'] = 'Nieuwe agenda';
$labels['modifycalendar'] = 'Agenda aanpassen';

$labels['createevent'] = 'Nieuwe gebeurtenis';
$labels['editevent'] = 'Gebeurtenis aanpassen';
$labels['deleteevent'] = 'Verwijder gebeurtenis';
$labels['deletecalendar'] = 'Verwijder agenda';
$labels['calendars'] = 'Agenda\'s';
$labels['shared_calendars'] = 'Gedeelde agenda\'s';
$labels['refresh'] = 'Verversen';
$labels['delete'] = 'Verwijderen';
$labels['add'] = 'Toevoegen';
$labels['close'] = 'Sluiten';
$labels['save'] = 'Opslaan';
$labels['create'] = 'Aanmaken';
$labels['login'] = 'Inloggen';
$labels['logout'] = 'Uitloggen';
$labels['modify'] = 'Aanpassen';
$labels['cancel'] = 'Annuleren';
$labels['next'] = 'volgende';
$labels['previous'] = 'vorige';
$labels['yes'] = 'Ja';

$labels['untitled'] = 'Naamloos';

$labels['sharewith'] = 'Delen met';
$labels['publicurl'] = 'URL voor agenda applicaties';

$labels['access'] = 'Toegang';
$labels['readonly'] = 'Alleen lezen';
$labels['readandwrite'] = 'Lezen en schrijven';

$labels['pastevents'] = 'Gebeurtenissen in het verleden';

$labels['preferences'] = 'Voorkeuren';
$labels['return'] = 'Terug';

$labels['hidelist'] = 'Verberg in de lijst';
$labels['defaultcalendar'] = 'Standaard agenda';

$labels['toggleallcalendars'] = 'Toon/verberg alles';

// Messages
$messages['error_auth'] = 'Ongeldige gebruikersnaam of wachtwoord';
$messages['error_invaliddate'] = 'Ongeldige datum voor veld %s';
$messages['error_invalidtime'] = 'Ongeldige tijd voor veld %s';
$messages['error_denied'] = 'Server weigerde uw verzoek (geen rechten)';
$messages['error_notimplemented'] = '%feature: nog niet geimplementeerd';
$messages['error_startgreaterend'] = 'Einddatum moet groter of gelijk zijn aan begindatum';
$messages['error_bogusrepeatrule'] = 'Fout, controleer de parameters voor de herhaling';
$messages['error_internalgen'] = 'Fout bij het aanmeken van de interne agenda';
$messages['error_internalcalnameinuse'] = 'Interne agendanaam is al in gebruik';

$messages['info_confirmcaldelete'] = 'Weet u zeker dat u de volgende agenda wilt verwijderen?';
$messages['info_confirmeventdelete'] = 'Weet u zeker dat u de volgende gebeurtenis wilt verwijderen?';
$messages['info_permanentremoval'] = 'Uw informatie wordt definitief verwijderd';
$messages['info_repetitivedeleteall'] = 'Alle herhalingen van deze gebeurtenis worden verwijderd';
$messages['info_sharedby'] = 'U heeft toegang tot deze agenda omdat gebruiker %user deze met u heeft gedeeld';
$messages['info_shareexplanation'] = 'U kunt deze agenda met andere gebruikers delen en laten aanpassen.
Geef de gebruikersnamen in, gescheiden door komma\'s of spaties';
$messages['info_notshared'] = 'Deze agenda wordt niet gedeeld';
$messages['error_sessexpired'] = 'Uw sessie is verlopen';
$messages['error_loginagain'] = 'Log a.u.b. opnieuw in';

$messages['error_modfailed'] = 'Bijwerken mislukt';
$messages['error_loadevents'] = 'Er is een fout opgetreden bij het laden van agenda %cal';
$messages['error_sessrefresh'] = 'Er is een fout opgetreden bij het vernieuwen van uw sessie';
$messages['error_internal'] = 'Interne fout';
$messages['error_genform'] = 'Fout bij het genereren van de pagina';
$messages['error_invalidinput'] = 'Ongeldige waarde';
$messages['error_caldelete'] = 'Fout bij het verwijderen van de agenda';

$messages['overlay_synchronizing'] = 'Gebeurtenissen synchroniseren...';
$messages['overlay_loading_dialog'] = 'Dialoog laden...';
$messages['overlay_sending_form'] = 'Pagina versturen...';
$messages['overlay_loading_calendar_list'] = 'Agendalijst laden...';
$messages['error_loading_dialog'] = 'Fout bij het laden van de dialoog';

$messages['error_oops'] = 'Oeps. Onverwachte fout';
$messages['error_interfacefailure'] = 'Interface fout';
$messages['error_current_event_not_loaded'] = 'Huidige gebeurtenis is niet beschikbaar';

$messages['error_event_not_deleted'] = 'Fout bij het verwijderen van de gebeurtenis';
$messages['error_loading_calendar_list'] = 'Fout bij het lezen van de agendalijst';
$messages['notice_no_calendars'] = 'Geen agenda\'s beschikbaar';
$messages['info_repetition_human'] = 'Deze gebeurtenis herhaalt zich %explanation';
$messages['info_repetition_unparseable'] = 'Deze gebeurtenis heeft herhalende regels die dit programma niet kan interpreteren. Ruwe definitie:';
$messages['error_calendarnotfound'] = 'Ongeldige agenda %calendar';
$messages['error_eventnotfound'] = 'Gebeurtenis niet gevonden';
$messages['error_eventchanged'] = 'Gebeurtenis is gewijzigd terwijl u het aan het bewerken was. Verversen alstublieft.';
$messages['error_unknownhttpcode'] = 'Onbekende fout, HTTP code=%res';
$messages['error_internalcalnamemissing'] = 'Interne agendanaam ontbreekt';
$messages['error_calname_missing'] = 'Agendanaam ontbreekt';
$messages['error_calcolor_missing'] = 'Kleur moet worden opgegeven';
$messages['error_mkcalendar'] = 'Server weigerde de agenda aan te maken. Controleer de parameters.';
$messages['error_shareunknownusers'] = 'Sommige door u opgegeven gebruikers komen niet voor.';

$messages['help_defaultcalendar'] = 'Nieuwe gebeurtenissen worden standaard in deze agenda geplaatst. Geef hier je meest gebruikte agenda aan.';

$messages['info_prefssaved'] = 'Voorkeuren opgeslagen';
