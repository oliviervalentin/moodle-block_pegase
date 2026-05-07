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
 * AJAX code for browsing formation page.
 *
 * @package     block_pegase
 * @copyright   2026 Olivier VALENTIN
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('AJAX_SCRIPT', true);
require_once('../../config.php');
require_once($CFG->dirroot . '/blocks/pegase/classes/api.php');

require_login();
require_sesskey();

$action         = required_param('action', PARAM_ALPHA);
$codestructure  = get_config('block_pegase', 'codestructure');

header('Content-Type: application/json');

try {
    $api = new \block_pegase\api();

    switch ($action) {

        // Search formations by keyword.
        case 'search':
            $keyword    = required_param('keyword', PARAM_TEXT);
            $espace_id  = optional_param('espace_id', '', PARAM_TEXT);
            $result     = $api->search_formations($codestructure, $keyword, $espace_id);
            echo json_encode(['success' => true, 'data' => $result]);
            break;

        // Get formation tree by ID.
        case 'tree':
            $formation_id = required_param('formation_id', PARAM_TEXT);
            $result       = $api->get_formation_tree($codestructure, $formation_id);
            echo json_encode(['success' => true, 'data' => $result]);
            break;

        // Get students for a code.
        case 'students':
            $code_ec      = required_param('code_ec', PARAM_ALPHANUMEXT);
            $code_periode = required_param('code_periode', PARAM_ALPHANUMEXT);
            $result       = $api->get_apprenants($codestructure, $code_periode, $code_ec);

            // Retrieve user info from Moodle account.
            global $DB;
            foreach ($result['students'] as &$student) {
                $moodleuser = $DB->get_record(
                    'user',
                    ['idnumber' => $student['codeApprenant']],
                    'id, firstname, lastname'
                );
                $student['moodle_account'] = $moodleuser ? true : false;
                $student['moodle_name']    = $moodleuser ? fullname($moodleuser) : null;
            }
            echo json_encode(['success' => true, 'data' => $result]);
            break;

        // Get periods list.
        case 'espaces':
            $result = $api->get_espaces($codestructure);
            echo json_encode(['success' => true, 'data' => $result]);
            break;

        default:
            throw new \moodle_exception('invalidaction', 'block_pegase');
    }

} catch (\moodle_exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
} catch (\Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}