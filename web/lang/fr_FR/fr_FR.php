<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed'); 
/*
 * Copyright 2011 Jorge López Pérez <jorge@adobo.org>
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
 * French language file
 * 2011-12-05 Created by Guillaume BF
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

$labels['username'] = 'Nom d\'utilisateur';
$labels['password'] = 'Mot de passe';

$labels['january'] = 'Janvier';
$labels['february'] = 'Février';
$labels['march'] = 'Mars';
$labels['april'] = 'Avril';
$labels['may'] = 'Mai';
$labels['june'] = 'Juin';
$labels['july'] = 'Juillet';
$labels['august'] = 'Août';
$labels['september'] = 'Septembre';
$labels['october'] = 'Octobre';
$labels['november'] = 'Novembre';
$labels['december'] = 'Décembre';

$labels['january_short'] = 'Jan';
$labels['february_short'] = 'Fév';
$labels['march_short'] = 'Mar';
$labels['april_short'] = 'Avr';
$labels['may_short'] = 'Mai';
$labels['june_short'] = 'Juin';
$labels['july_short'] = 'Juil';
$labels['august_short'] = 'Août';
$labels['september_short'] = 'Sep';
$labels['october_short'] = 'Oct';
$labels['november_short'] = 'Nov';
$labels['december_short'] = 'Déc';

$labels['sunday'] = 'Dimanche';
$labels['monday'] = 'Lundi';
$labels['tuesday'] = 'Mardi';
$labels['wednesday'] = 'Mercredi';
$labels['thursday'] = 'Jeudi';
$labels['friday'] = 'Vendredi';
$labels['saturday'] = 'Samedi';

$labels['sunday_short'] = 'Dim';
$labels['monday_short'] = 'Lun';
$labels['tuesday_short'] = 'Mar';
$labels['wednesday_short'] = 'Mer';
$labels['thursday_short'] = 'Jeu';
$labels['friday_short'] = 'Ven';
$labels['saturday_short'] = 'Sam';

$labels['today'] = 'Aujourd\'hui';
$labels['month'] = 'mois';
$labels['week'] = 'semaine';
$labels['day'] = 'jour';
$labels['allday'] = 'tous les jours';
$labels['choose_date'] = 'Choisissez une date';

$labels['calendar'] = 'Agenda';
$labels['location'] = 'Lieu';
$labels['description'] = 'Description';

$labels['displayname'] = 'Nom affiché';
$labels['internalname'] = 'Nom interne';
$labels['optional'] = '(optionnel)';
$labels['color'] = 'Couleur';

$labels['summary'] = 'Résumé';
$labels['startdate'] = 'Date de début';
$labels['enddate'] = 'Date de fin';
$labels['starttime'] = 'Heure de début';
$labels['endtime'] = 'Heure de Fin';
$labels['alldayform'] = 'Toute la journée';

$labels['repetitionexceptions'] = 'Exceptions aux événements récurrents';

$labels['repeat'] = 'Répétition';
$labels['repeatno'] = 'Pas de répétition';
$labels['repeatdaily'] = 'Quotidienne';
$labels['repeatweekly'] = 'Hebdomadaire';
$labels['repeatmonthly'] = 'Mensuelle';
$labels['repeatyearly'] = 'Annuelle';

$labels['repeatcount'] = 'Nombre de répétitions';
$labels['repeatuntil'] = 'Jusqu\'à';

$labels['explntimes'] = '%n fois';
$labels['expluntil'] = 'jusqu\'à %d';

$labels['privacy'] = 'Confidentialité';
$labels['public'] = 'Public';
$labels['private'] = 'Privé';
$labels['confidential'] = 'Confidentiel';

$labels['transp'] = 'Afficher cette période comme';
$labels['opaque'] = 'Occupé';
$labels['transparent'] = 'Libre';

$labels['generaloptions'] = 'Options générales';
$labels['repeatoptions'] = 'Répétitions';
$labels['workgroupoptions'] = 'Groupe de travail';
$labels['shareoptions'] = 'Partage';

$labels['newcalendar'] = 'Nouvel agenda';
$labels['modifycalendar'] = 'Modifier l\'agenda';

$labels['createevent'] = 'Créer un événement';
$labels['editevent'] = 'Éditer un événement';
$labels['deleteevent'] = 'Supprimer l\'événement';
$labels['deletecalendar'] = 'Supprimer l\'agenda';
$labels['calendars'] = 'Agendas';
$labels['refresh'] = 'Rafraîchir';
$labels['delete'] = 'Supprimer';
$labels['close'] = 'Fermer';
$labels['save'] = 'Enregistrer';
$labels['create'] = 'Créer';
$labels['login'] = 'Se connecter';
$labels['modify'] = 'Modifier';
$labels['cancel'] = 'Annuler';
$labels['next'] = 'suivant';
$labels['previous'] = 'précédent';
$labels['yes'] = 'Oui';

$labels['untitled'] = 'Sans titre';

$labels['sharewith'] = 'Partager avec';
$labels['publicurl'] = 'URL de l\'agenda'; 

// Messages
$messages['error_auth'] = 'Nom d\'utilisateur ou mot de passe invalide';
$messages['error_invaliddate'] = 'Date invalide pour le champ %s';
$messages['error_invalidtime'] = 'Heure invalide pour le champ %s';
$messages['error_denied'] = 'Le serveur a refusé votre requête (permission refusé)';
$messages['error_notimplemented'] = '%feature: n\'est pas encore implémenté(e)';
$messages['error_startgreaterend'] = 'La date de fin doit être supérieure ou égale à la date de début';
$messages['error_bogusrepeatrule'] = 'Erreur, vérifiez vos paramètres de récurrence';
$messages['error_internalgen'] = 'Erreur lors de la création du agenda interne';
$messages['error_internalcalnameinuse'] = 'Le nom interne du agenda est déjà utilisé';

$messages['info_confirmcaldelete'] = 'Êtes-vous sûr(e) de vouloir supprimer l\'agenda suivant?';
$messages['info_confirmeventdelete'] = 'Êtes-vous sûr(e) de vouloir supprimer l\'événement suivant?';
$messages['info_permanentremoval'] = 'Votre information sera définitivement perdue';
$messages['info_repetitivedeleteall'] = 'Toutes les instances de cet événement récurrent vont être supprimées';
$messages['info_sharedby'] = 'Vous avez accès à cet agenda car %user l\'a partagé avec vous';
$messages['info_shareexplanation'] = 'Vous pouvez partager cet agenda avec
d\'autres utilisateurs et les laisser le modifier. Mettez leurs noms d\'utilisateur ci-dessous, séparés
par des virgules ou des espaces';
$messages['error_sessexpired'] = 'Votre session a expiré';
$messages['error_loginagain'] = 'Veuillez vous reconnecter, s\'il vous plaît';

$messages['error_modfailed'] = 'La modification a échoué';
$messages['error_loadevents'] = 'Erreur lors du chargement des événement de l\'agenda %cal';
$messages['error_sessrefresh'] = 'Erreur lors du rafraîchissement de votre session';
$messages['error_internal'] = 'Erreur interne';
$messages['error_genform'] = 'Erreur lors de la génération du formulaire';
$messages['error_invalidinput'] = 'Valeur invalide';
$messages['error_caldelete'] = 'Erreur lors de l\'effacement de l\'agenda';

$messages['overlay_synchronizing'] = 'Synchronisation des événements...';
$messages['overlay_loading_dialog'] = 'Chargement de la boîte de dialogue...';
$messages['overlay_sending_form'] = 'Envoi du formulaire...';
$messages['overlay_loading_calendar_list'] = 'Chargement de la liste des agendas...';
$messages['error_loading_dialog'] = 'Erreur lors du chargement de la boîte de dialogue...';

$messages['error_oops'] = 'Oups. Erreur inattendue';
$messages['error_interfacefailure'] = 'Erreur d\'interface';
$messages['error_current_event_not_loaded'] = 'L\'événement courant est indisponible';

$messages['error_event_not_deleted'] = 'Erreur lors de l\'effacement de l\'événement';
$messages['error_loading_calendar_list'] = 'Erreur lors de lecture de la liste des agendas';
$messages['notice_no_calendars'] = 'Aucun agenda disponible';
$messages['info_repetition_human'] = 'Cet événement répète %explanation';
$messages['info_repetition_unparseable'] = 'Les règles de récurrence de cet événement ne peuvent pas être comprises par ce programme. Définition brute:';
$messages['error_calendarnotfound'] = 'Agenda invalide %calendar';
$messages['error_eventnotfound'] = 'Élément non trouvé';
$messages['error_eventchanged'] = 'L\'élément a été modifié pendant que vous l\'éditiez. Veuillez rafraîchir s\'il vous plaît.';
$messages['error_unknownhttpcode'] = 'Erreur indéterminée, code HTTP=%res';
$messages['error_internalcalnamemissing'] = 'Nom de l\'agenda interne manquant';
$messages['error_calname_missing'] = 'Nom de l\agenda manquant';
$messages['error_calcolor_missing'] = 'La couleur doit être précisée';
$messages['error_mkcalendar'] = 'Le serveur refuse de créer l\'agenda. Veuillez contrôler vos paramètres de création s\'il vous plaît';
$messages['error_shareunknownusers'] = 'Certains des utilisateurs que vous avez spécifiés n\'existe pas';
