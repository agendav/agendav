<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed'); 
/*
 * Copyright 2011 Lorenzo Novaro novalore [at] 19 [dot] coop
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
 * Italian master language file
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

$labels['username'] = 'Nome Utente';
$labels['password'] = 'Password';
$labels['months_long'] = array('Gennaio', 'Febbraio', 'Marzo', 'Aprile', 'Maggio', 'Giugno', 'Luglio', 'Agosto', 'Settembre', 'Ottobre', 'Novembre', 'Dicembre');
$labels['months_short'] = array('Gen', 'Feb', 'Mar', 'Apr', 'Mag', 'Giu', 'Lug', 'Ago', 'Set', 'Ott', 'Nov', 'Dic');
$labels['daynames_long'] = array('Domenica', 'Lunedì', 'Martedì', 'Mercoledì', 'Giovedì', 'Venerdì', 'Sabato');
$labels['daynames_short'] = array('Dom', 'Lun', 'Mar', 'Mer', 'Gio', 'Ven', 'Sab');

$labels['today'] = 'Oggi';
$labels['month'] = 'Mese';
$labels['week'] = 'Settimana';
$labels['day'] = 'Giorno';
$labels['allday'] = 'Tutto il giorno';
$labels['choose_date'] = 'Scegli la data';

$labels['calendar'] = 'Calendario';
$labels['location'] = 'Luogo';
$labels['description'] = 'Descrizione';

$labels['displayname'] = 'Nome del calendario';
$labels['internalname'] = 'Nome interno';
$labels['optional'] = '(opzionale)';
$labels['color'] = 'Colore';

$labels['summary'] = 'Titolo';
$labels['startdate'] = 'Data inizio';
$labels['enddate'] = 'Data fine';
$labels['starttime'] = 'Dalle ore';
$labels['endtime'] = 'Alle ore';
$labels['alldayform'] = 'Tutto il giorno';

$labels['repetitionexceptions'] = 'Eccezioni per gli eventi ricorrenti';

$labels['repeat'] = 'Ripeti';
$labels['repeatno'] = 'Senza ripetizioni';
$labels['repeatdaily'] = 'Quotidianamente';
$labels['repeatweekly'] = 'Settimanalmente';
$labels['repeatmonthly'] = 'Mensilmente';
$labels['repeatyearly'] = 'Annualmente';

$labels['repeatcount'] = 'Numero di volte';
$labels['repeatuntil'] = 'Fino a';

$labels['explntimes'] = '%n volte';
$labels['expluntil'] = 'fino a %d';

$labels['privacy'] = 'Privacy';
$labels['public'] = 'Pubblico';
$labels['private'] = 'Privato';
$labels['confidential'] = 'Riservato';

$labels['transp'] = 'Mostra come';
$labels['opaque'] = 'Occupato';
$labels['transparent'] = 'Libero';

$labels['generaloptions'] = 'Opzioni generali';
$labels['repeatoptions'] = 'Ripetizione';
$labels['workgroupoptions'] = 'Gruppo di lavoro';
$labels['shareoptions'] = 'Condivisione';

$labels['newcalendar'] = 'Nuovo calendario';
$labels['modifycalendar'] = 'Modifica calendario';

$labels['createevent'] = 'Crea evento';
$labels['editevent'] = 'Modifica evento';
$labels['deleteevent'] = 'Cancella evento';
$labels['deletecalendar'] = 'Cancella calendario';
$labels['calendars'] = 'Calendari';
$labels['refresh'] = 'Aggiorna';
$labels['delete'] = 'Cancella';
$labels['close'] = 'Chiudi';
$labels['save'] = 'Salva';
$labels['create'] = 'Crea';
$labels['login'] = 'Login';
$labels['modify'] = 'Modifica';
$labels['cancel'] = 'Annulla';
$labels['next'] = 'successivo';
$labels['previous'] = 'precedente';
$labels['yes'] = 'Sì';

$labels['untitled'] = 'Senza titolo';

$labels['sharewith'] = 'Condividi con';
$labels['publicurl'] = 'URL per applicazioni compatibili';

// Messages
$messages['error_auth'] = 'Nome utente o password non validi';
$messages['error_invaliddate'] = 'Data non valida nel campo %s';
$messages['error_invalidtime'] = 'Orario non valido nel campo %s';
$messages['error_denied'] = 'Il server ha rifiutato la richiesta (autorizzazione negata)';
$messages['error_notimplemented'] = '%feature: funzione ancora non disponibile';
$messages['error_startgreaterend'] = 'La data di conclusione deve essere succcessiva o uguale alla data di inizio';
$messages['error_bogusrepeatrule'] = 'Errore, controlla i parametri di ricorrenza';
$messages['error_internalgen'] = 'Errore nella generazione del calendario';
$messages['error_internalcalnameinuse'] = 'Nome del calendario già in uso';

$messages['info_confirmcaldelete'] = 'Vuoi davvero cancellare questo calendario?';
$messages['info_confirmeventdelete'] = 'Vuoi davvero cancellare questo evento?';
$messages['info_permanentremoval'] = 'I dati verranno cancellati in modo definitivo';
$messages['info_repetitivedeleteall'] = 'Tutte le ripetizioni di questo evento verranno cancellate';
$messages['info_sharedby'] = 'Puoi accedere a questo calendario perché %user lo ha condiviso con te';
$messages['info_shareexplanation'] = 'Puoi condividere questo calendario con altri utenti e permettere loro di modificarlo. Indicane i nomi qui sotto separati da virgole o spazi';
$messages['error_sessexpired'] = 'La sessione è scaduta';
$messages['error_loginagain'] = 'Esegui nuovamente la login';

$messages['error_modfailed'] = 'Modifica fallita';
$messages['error_loadevents'] = 'Caricando il calendario %cal si è verificato un errore';
$messages['error_sessrefresh'] = 'Aggiornando la sessione si è verificato un errore';
$messages['error_internal'] = 'Errore interno';
$messages['error_genform'] = 'Errore nella generazione del modulo';
$messages['error_invalidinput'] = 'Valore non valido';
$messages['error_caldelete'] = 'Errore nella cancellazione del calendario';

$messages['overlay_synchronizing'] = 'Sincronizzo gli eventi...';
$messages['overlay_loading_dialog'] = 'Carico...';
$messages['overlay_sending_form'] = 'Invio il modulo...';
$messages['overlay_loading_calendar_list'] = 'Carico la lista dei calendari...';
$messages['error_loading_dialog'] = 'Errore nel caricamento';

$messages['error_oops'] = 'Oops. Errore inatteso';
$messages['error_interfacefailure'] = "Errore dell'interfaccia";
$messages['error_current_event_not_loaded'] = "L'evento corrente non è disponibile";

$messages['error_event_not_deleted'] = "Errore nella cancellazione dell'evento";
$messages['error_loading_calendar_list'] = 'Errore nella lettura della lista dei calendari';
$messages['notice_no_calendars'] = 'Non ci sono calendari disponibili';
$messages['info_repetition_human'] = 'Questo evento si ripete %explanation';
$messages['info_repetition_unparseable'] = 'Ci sono delle regole di ricorrenza associate a questo evento che qesto programma non è ancora in grado di interpretare. Raw definition:';
$messages['error_calendarnotfound'] = 'Calendario %calendar non valido';
$messages['error_eventnotfound'] = 'Elemento non trovato';
$messages['error_eventchanged'] = "L'Elemento è stato modificato da qualcun altro. Ricarica, per favore.";
$messages['error_unknownhttpcode'] = 'Errore sconosciuto, HTTP code=%res';
$messages['error_internalcalnamemissing'] = 'Nome interno per il calendario vuoto';
$messages['error_calname_missing'] = 'Nome del calendario vuoto';
$messages['error_calcolor_missing'] = 'Bisogna inserire un colore';
$messages['error_mkcalendar'] = 'Il server si è rifiutato di creare il calendario. Controllare i parametri di creazione.';
$messages['error_shareunknownusers'] = 'Alcuni degli utenti specificati non esistono';
