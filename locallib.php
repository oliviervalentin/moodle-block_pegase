<?php
// locallib.php
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
 * Local library for block_pegase.
 *
 * @package     block_pegase
 * @copyright   2026 Olivier VALENTIN
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Returns available periods from active PEGASE scolarapps in enrol_wsscol.
 *
 * @return array ['PERIODE-25-26' => 'PEGASE 2025-2026 (PERIODE-25-26)', ...]
 */
function block_pegase_get_periods(): array {
    global $DB;

    $periods    = [];
    $scolarapps = $DB->get_records_select(
        'enrol_wsscol_scolapps',
        "type = 'pegase' AND status = 1",
        [], 'id ASC'
    );

    foreach ($scolarapps as $scolarapp) {
        if (!empty($scolarapp->getstudents_periode)) {
            $periods[$scolarapp->getstudents_periode] = $scolarapp->name
                . ' (' . $scolarapp->id . ')';
        }
    }

    return $periods;
}