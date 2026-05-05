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
 * Block PEGASE version file.
 *
 * @package     block_pegase
 * @copyright   2026 Olivier VALENTIN
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');

$instanceid = required_param('instanceid', PARAM_INT);
$courseid   = required_param('courseid', PARAM_INT);

require_sesskey();

$course  = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
$context = context_course::instance($courseid);

require_login($course);
require_capability('block/pegase:manage', $context);

// Verify instance belongs to this course and is wsscol type
$instance = $DB->get_record('enrol', [
    'id'       => $instanceid,
    'courseid' => $courseid,
    'enrol'    => 'wsscol',
], '*', MUST_EXIST);

// Delete the enrol instance
$plugin = enrol_get_plugin('wsscol');
$plugin->delete_instance($instance);

redirect(
    new moodle_url('/course/view.php', ['id' => $courseid]),
    get_string('instancedeleted', 'block_pegase'),
    null,
    \core\output\notification::NOTIFY_SUCCESS
);