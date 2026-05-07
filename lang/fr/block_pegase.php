<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Block PEGASE french translation file.
 *
 * @package     block_pegase
 * @copyright   2026 Olivier VALENTIN
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

$string['pegase:addinstance']   = 'Ajouter le bloc PEGASE';
$string['pegase:manage']        = 'Gérer les inscriptions PEGASE';
$string['pegase:myaddinstance'] = 'Ajouter le bloc PEGASE à mon tableau de bord';
$string['pluginname']           = 'Inscriptions PEGASE';

// Settings.
$string['authurl']              = 'URL d\'authentification CAS';
$string['authurl_desc']         = 'Endpoint CAS de PEGASE pour obtenir un token.';
$string['apiurl']               = 'URL de base de l\'API CHC';
$string['apiurl_desc']          = 'URL de base de l\'API externe PEGASE CHC.';
$string['odfurl']               = 'URL de base de l\'API ODF';
$string['odfurl_desc']          = 'URL de base de l\'API PEGASE pour la navigation dans les maquettes.';
$string['codestructure']        = 'Code établissement';
$string['codestructure_desc']   = 'Code établissement utilisé dans les appels API (ex: ETAB00).';
$string['username']             = 'Identifiant API';
$string['password']             = 'Mot de passe API';
$string['wsscol_scolarapp_id']      = 'ID de la scolarapp PEGASE';
$string['wsscol_scolarapp_id_desc'] = 'ID de la scolarapp PEGASE dans enrol_wsscol.';

// Block content.
$string['activemethods']        = 'Matières inscrites dans ce cours';
$string['nomethods']            = 'Aucune matière PEGASE inscrite dans ce cours.';
$string['addmethod']            = 'Ajouter une matière PEGASE';
$string['deletemethod']         = 'Supprimer cette méthode';
$string['deleteconfirm']        = 'Êtes-vous sûr de vouloir supprimer cette méthode d\'inscription ?';
$string['students']             = 'étudiant(s)';

// Search.
$string['searchbycode']         = 'Rechercher par code matière';
$string['browsetree']           = 'Parcourir l\'arborescence';
$string['selectperiod']         = 'Année universitaire';
$string['codeec']               = 'Code matière (EC)';
$string['codeec_help']          = 'Saisissez le code EC tel qu\'il apparaît dans PEGASE (ex: Y4SDU513).';
$string['searchstudents']       = 'Rechercher';
$string['studentsfound']        = 'Étudiants trouvés';
$string['nostudentsfound']      = 'Aucun étudiant trouvé pour ce cours et cette période.';
$string['notanec']              = 'Ce code ne correspond pas à un EC : {$a}. Veuillez saisir un code de matière.';
$string['confirmenrol']         = 'Confirmer et inscrire les étudiants';
$string['instancecreated']      = 'Méthode d\'inscription PEGASE créée avec succès.';
$string['cancel']               = 'Annuler';
$string['studentcode']          = 'Code étudiant';
$string['lastname']             = 'Nom';
$string['firstname']            = 'Prénom';
$string['moodleaccount']        = 'Compte Moodle';
$string['noaccount']            = 'Aucun compte trouvé';
$string['cohortidnumber']       = 'Identifiant de la méthode qui sera créée';
$string['browsetitle']          = 'Rechercher une formation PEGASE';
$string['step1search']          = 'Étape 1 — Rechercher une formation';
$string['step2tree']            = 'Étape 2 — Parcourir la maquette';
$string['step3students']        = 'Étape 3 — Étudiants inscrits';
$string['searchplaceholder']    = 'Ex: Droit pénal, Informatique...';
$string['addcohort']            = 'Ajouter une matière PEGASE';
$string['backtocourse']         = 'Retour au cours';
$string['alreadyenrolled']      = 'Cette matière est déjà inscrite dans ce cours.';
