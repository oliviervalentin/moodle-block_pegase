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
 * Local library for block_pegase.
 *
 * @package     block_pegase
 * @copyright   2026 Olivier VALENTIN
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Get PEGASE periods configured in scolarapps and available from API.
 *
 * @return array List of periods with 'id' (UUID), 'code' and 'libelle'
 */
function block_pegase_get_periods(): array {
    global $DB;

    // Get configured PEGASE scolarapps periods.
    $scolarapps = $DB->get_records_select(
        'enrol_wsscol_scolapps',
        "type = 'pegase' AND status = 1",
        [],
        'id ASC'
    );

    $configuredcodes = [];
    foreach ($scolarapps as $scolarapp) {
        if (!empty($scolarapp->getstudents_periode)) {
            $configuredcodes[] = $scolarapp->getstudents_periode;
        }
    }

    if (empty($configuredcodes)) {
        return [];
    }

    // Get espaces from API and filter on configured codes only.
    try {
        $api     = new \block_pegase\api();
        $espaces = $api->get_espaces(get_config('block_pegase', 'codestructure'));

        $periods = [];
        foreach ($espaces as $espace) {
            if (in_array($espace['code'], $configuredcodes)) {
                $periods[] = [
                    'id'      => $espace['id'],
                    'code'    => $espace['code'],
                    'libelle' => $espace['libelle'],
                ];
            }
        }
        return $periods;
    } catch (\moodle_exception $e) {
        // Fallback : return just codes without UUID if API unavailable.
        $periods = [];
        foreach ($configuredcodes as $code) {
            $periods[] = [
                'id'      => '',
                'code'    => $code,
                'libelle' => $code,
            ];
        }
        return $periods;
    }
}
