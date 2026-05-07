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
 * Block PEGASE english translation file.
 *
 * @package     block_pegase
 * @copyright   2026 Olivier VALENTIN
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

$string['pluginname']           = 'PEGASE enrolments';
$string['pegase:addinstance']   = 'Add PEGASE block';
$string['pegase:myaddinstance'] = 'Add PEGASE block to my dashboard';
$string['pegase:manage']        = 'Manage PEGASE enrolments';

// Settings
$string['authurl']              = 'CAS authentication URL';
$string['authurl_desc']         = 'PEGASE CAS endpoint to obtain a token.';
$string['apiurl']               = 'CHC API base URL';
$string['apiurl_desc']          = 'Base URL of the PEGASE CHC external API.';
$string['odfurl']               = 'ODF API base URL';
$string['odfurl_desc']          = 'Base URL of the PEGASE ODF API for browsing training templates.';
$string['codestructure']        = 'Establishment code';
$string['codestructure_desc']   = 'Establishment code used in API calls (e.g. ETAB00).';
$string['username']             = 'API username';
$string['password']             = 'API password';
$string['wsscol_scolarapp_id']      = 'PEGASE scolarapp ID';
$string['wsscol_scolarapp_id_desc'] = 'ID of the PEGASE scolarapp in enrol_wsscol.';

// Block content
$string['activemethods']        = 'Courses enrolled in this class';
$string['nomethods']            = 'No PEGASE enrolment method found in this course.';
$string['addmethod']            = 'Add a PEGASE course';
$string['deletemethod']         = 'Delete this method';
$string['deleteconfirm']        = 'Are you sure you want to delete this enrolment method?';
$string['students']             = 'student(s)';

// Search
$string['searchbycode']         = 'Search by course code';
$string['browsetree']           = 'Browse training tree';
$string['selectperiod']         = 'Academic year';
$string['codeec']               = 'Course code (EC)';
$string['codeec_help']          = 'Enter the EC code as it appears in PEGASE (e.g. Y4SDU513).';
$string['searchstudents']       = 'Search';
$string['studentsfound']        = 'Students found';
$string['nostudentsfound']      = 'No students found for this course and period.';
$string['notanec']              = 'This code does not correspond to an EC: {$a}. Please enter a course code.';
$string['confirmenrol']         = 'Confirm and enrol students';
$string['instancecreated']      = 'PEGASE enrolment method created successfully.';
$string['cancel']               = 'Cancel';
$string['studentcode']          = 'Student code';
$string['lastname']             = 'Last name';
$string['firstname']            = 'First name';
$string['moodleaccount']        = 'Moodle account';
$string['noaccount']            = 'No account found';
$string['cohortidnumber']       = 'Identifier of the method that will be created';
$string['browsetitle']          = 'Search for a PEGASE training program';
$string['step1search']          = 'Step 1 — Search for a training program';
$string['step2tree']            = 'Step 2 — Browse the training structure';
$string['step3students']        = 'Step 3 — Enrolled students';
$string['searchplaceholder']    = 'Ex: Criminal law, Computer science...';
$string['addcohort']            = 'Add a PEGASE course';
$string['backtocourse']         = 'Back to course';
$string['alreadyenrolled']      = 'This course is already enrolled in this class.';