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
 * Block PEGASE isntance file.
 *
 * @package     block_pegase
 * @copyright   2026 Olivier VALENTIN
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot . '/blocks/pegase/locallib.php');

class block_pegase extends block_base {

    public function init(): void {
        $this->title = get_string('pluginname', 'block_pegase');
    }

    /**
     * Allow global configuration via settings page
     */
    public function has_config(): bool {
        return true;
    }

    /**
     * Only show in courses
     */
    public function applicable_formats(): array {
        return [
            'course-view' => true,
            'site'        => false,
            'my'          => false,
        ];
    }

    /**
     * Allow multiple instances in same course
     */
    public function instance_allow_multiple(): bool {
        return false;
    }

    /**
     * Block content
     */
    public function get_content(): stdClass {
        global $DB, $OUTPUT, $COURSE;

        if ($this->content !== null) {
            return $this->content;
        }

        $this->content = new stdClass();
        $this->content->text   = '';
        $this->content->footer = '';

        $context = context_course::instance($COURSE->id);

        // Check capability
        if (!has_capability('block/pegase:manage', $context)) {
            return $this->content;
        }

        // Check enrol_wsscol is installed
        if (!enrol_is_enabled('wsscol')) {
            $this->content->text = $OUTPUT->notification(
                'Le plugin enrol_wsscol est requis mais non activé.',
                \core\output\notification::NOTIFY_ERROR
            );
            return $this->content;
        }

        // Get all wsscol instances linked to PEGASE scolarapp in this course
        $sql = "SELECT e.*, ewa.name as scolarapp_name
                FROM {enrol} e
                JOIN {enrol_wsscol_scolapps} ewa ON e.customint2 = ewa.id
                WHERE e.enrol = 'wsscol'
                AND e.courseid = :courseid
                AND ewa.type = 'pegase'";

        $instances = $DB->get_records_sql($sql, ['courseid' => $COURSE->id]);

        $html = '<div class="block-pegase">';

        // List active methods
        $html .= '<h6>' . get_string('activemethods', 'block_pegase') . '</h6>';

        if (empty($instances)) {
            $html .= '<p class="text-muted small">' 
                   . get_string('nomethods', 'block_pegase') 
                   . '</p>';
        } else {
            $html .= '<ul class="list-unstyled">';
            foreach ($instances as $instance) {
                // Count enrolled students
                $count = $DB->count_records('user_enrolments', ['enrolid' => $instance->id]);

                // Delete URL
                $delete_url = new moodle_url('/blocks/pegase/delete.php', [
                    'instanceid' => $instance->id,
                    'courseid'   => $COURSE->id,
                    'sesskey'    => sesskey(),
                ]);

                $html .= '<li class="mb-2 p-2 border rounded">';
                $html .= '<div class="d-flex justify-content-between align-items-start">';
                $html .= '<div>';
                $html .= '<strong>' . s($instance->customchar1) . '</strong>'; // code EC
                $html .= ' — ' . s($instance->customchar2); // titre du cours
                $html .= '<br><small class="text-muted">' . s($instance->customchar3) . '</small>'; // période
                $html .= '<br><small>' . $count . ' ' . get_string('students', 'block_pegase') . '</small>';
                $html .= '</div>';
                $html .= '<a href="' . $delete_url . '" '
                    . 'onclick="return confirm(\'' . get_string('deleteconfirm', 'block_pegase') . '\')" '
                    . 'class="btn btn-sm btn-outline-danger" '
                    . 'title="' . get_string('deletemethod', 'block_pegase') . '">'
                    . '<i class="fa fa-trash"></i>'
                    . '</a>';
                $html .= '</div>';
                $html .= '</li>';
            }
            $html .= '</ul>';
        }

        // Add buttons
        $browse_url = new moodle_url('/blocks/pegase/browse.php', ['courseid' => $COURSE->id]);
        $edit_url   = new moodle_url('/blocks/pegase/edit.php',   ['courseid' => $COURSE->id]);

        $html .= '<div class="d-grid gap-2 mt-2">';
        $html .= '<a href="' . $browse_url . '" class="btn btn-primary btn-sm">'
               . '<i class="fa fa-sitemap"></i> ' . get_string('browsetree', 'block_pegase')
               . '</a>';
        $html .= '<a href="' . $edit_url . '" class="btn btn-outline-primary btn-sm">'
               . '<i class="fa fa-pencil"></i> ' . get_string('searchbycode', 'block_pegase')
               . '</a>';
        $html .= '</div>';

        $html .= '</div>';

        $this->content->text = $html;
        return $this->content;
    }
}