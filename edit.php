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
 * Method 1 : directly search object with its code and enrol students via enrol_wsscol.
 *
 * @package     block_pegase
 * @copyright   2026 Olivier VALENTIN
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once($CFG->dirroot . '/blocks/pegase/classes/api.php');
require_once($CFG->dirroot . '/blocks/pegase/locallib.php');

$courseid = required_param('courseid', PARAM_INT);

$course  = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
$context = context_course::instance($courseid);

require_login($course);
require_capability('block/pegase:manage', $context);

$PAGE->set_url('/blocks/pegase/edit.php', ['courseid' => $courseid]);
$PAGE->set_title(get_string('searchbycode', 'block_pegase'));
$PAGE->set_heading($course->fullname);
$PAGE->set_pagelayout('incourse');

// Periods available.
$periods = block_pegase_get_periods();

if (empty($periods)) {
    echo $OUTPUT->header();
    echo $OUTPUT->notification(
        'Aucune scolarapp PEGASE active configurée. Contactez votre administrateur.',
        \core\output\notification::NOTIFY_WARNING
    );
    echo $OUTPUT->footer();
    exit;
}

// Form data.
$action    = optional_param('action', '', PARAM_ALPHA);
$code_ec   = optional_param('code_ec', '', PARAM_ALPHANUMEXT);
$periode   = optional_param('periode', '', PARAM_ALPHANUMEXT);
$confirmed = optional_param('confirmed', 0, PARAM_INT);
$title     = optional_param('title', '', PARAM_TEXT);

$students        = [];
$object_type     = '';
$object_type_label = '';
$search_done     = false;
$error           = '';

// Action : search students.
if ($action === 'search' && !empty($code_ec) && !empty($periode)) {
    // Use "code etablissement" code defined in settings.
    $codestructure = get_config('block_pegase', 'codestructure');

    // Search for students for this course code through PEGASE API.
    try {
        $api    = new \block_pegase\api();
        $result = $api->get_apprenants($codestructure, $periode, $code_ec);

        $students          = $result['students'];
        $title             = $result['title'];
        $object_type       = $result['object_type'];
        $object_type_label = $result['object_type_label'];
        $search_done       = true;
    } catch (\moodle_exception $e) {
        $error = $e->getMessage();
    }
}

// Action : confirm and create enrol_wsscol instance.
if ($action === 'confirm' && $confirmed && !empty($code_ec) && !empty($periode)) {
    // Check enrol_wsscol is available.
    $wsscol_plugin = enrol_get_plugin('wsscol');
    if (!$wsscol_plugin) {
        throw new \moodle_exception('generalexceptionmessage', 'error', '', 'Plugin enrol_wsscol not found.');
    }

    // Find scolarapp matching selected period dynamically.
    $scolarapp = $DB->get_record_select(
        'enrol_wsscol_scolapps',
        "type = 'pegase' AND getstudents_periode = :periode AND status = 1",
        ['periode' => $periode]
    );

    if (!$scolarapp) {
        throw new \moodle_exception(
            'generalexceptionmessage',
            'error',
            '',
            'Aucune scolarapp PEGASE trouvée pour la période : ' . $periode
        );
    }

    $scolarapp_id = $scolarapp->id;

    // Check if this EC is already enrolled in this course.
    $existing = $DB->get_record('enrol', [
        'enrol'       => 'wsscol',
        'courseid'    => $courseid,
        'customchar1' => $code_ec,
        'customint2'  => $scolarapp_id,
    ]);

    if ($existing) {
        redirect(
            new moodle_url('/course/view.php', ['id' => $courseid]),
            get_string('alreadyenrolled', 'block_pegase'),
            null,
            \core\output\notification::NOTIFY_WARNING
        );
    }

    // Create enrol_wsscol instance.
    $wsscol_plugin->add_instance($course, [
        'customchar1' => $code_ec,
        'customchar2' => $title,
        'customchar3' => $periode,
        'customint2'  => $scolarapp_id,
        'customint3'  => 1,
        'status'      => ENROL_INSTANCE_ENABLED,
    ]);
    redirect(
        new moodle_url('/course/view.php', ['id' => $courseid]),
        get_string('instancecreated', 'block_pegase'),
        null,
        \core\output\notification::NOTIFY_SUCCESS
    );
}

// Display page.
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('searchbycode', 'block_pegase'));

// Back link.
echo html_writer::tag(
    'p',
    html_writer::link(
        new moodle_url('/course/view.php', ['id' => $courseid]),
        '← ' . get_string('backtocourse', 'block_pegase')
    )
);

// Error message.
if (!empty($error)) {
    echo $OUTPUT->notification($error, \core\output\notification::NOTIFY_ERROR);
}

// SEARCH FORM.

?>
<form method="post" action="<?php echo $PAGE->url; ?>">
    <input type="hidden" name="sesskey"  value="<?php echo sesskey(); ?>">
    <input type="hidden" name="courseid" value="<?php echo $courseid; ?>">
    <input type="hidden" name="action"   value="search">
    <input type="hidden" name="title" value="<?php echo s($title); ?>">

    <div class="card mb-4">
        <div class="card-body">

            <div class="mb-3">
                <label for="periode" class="form-label fw-bold">
                    <?php echo get_string('selectperiod', 'block_pegase'); ?>
                </label>
                <select name="periode" id="periode" class="form-select w-auto">
                    <?php foreach ($periods as $period) : ?>
                        <option value="<?php echo $period['code']; ?>"
                            <?php echo ($periode === $period['code']) ? 'selected' : ''; ?>>
                            <?php echo $period['libelle']; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="mb-3">
                <label for="code_ec" class="form-label fw-bold">
                    <?php echo get_string('codeec', 'block_pegase'); ?>
                </label>
                <input type="text"
                       name="code_ec"
                       id="code_ec"
                       class="form-control w-auto"
                       value="<?php echo s($code_ec); ?>"
                       placeholder="ex: Y4SDU513"
                       required>
                <div class="form-text">
                    <?php echo get_string('codeec_help', 'block_pegase'); ?>
                </div>
            </div>

            <button type="submit" class="btn btn-primary">
                <?php echo get_string('searchstudents', 'block_pegase'); ?>
            </button>

        </div>
    </div>

</form>

<?php

// SEARCH RESULTS.

if ($search_done) {
    // Display EC title and type.
    if (!empty($title)) {
        echo html_writer::tag(
            'h3',
            s($code_ec) . ' — ' . s($title),
            ['class' => 'mt-4']
        );
    }

    // TO DO - check objects that can be enrolled.
    // if (!empty($object_type) && $object_type !== 'EC') {
    //     echo $OUTPUT->notification(
    //         get_string('notanec', 'block_pegase', s($object_type_label)),
    //         \core\output\notification::NOTIFY_WARNING
    //     );
    // } else {

        echo html_writer::tag(
            'p',
            get_string('studentsfound', 'block_pegase') . ' : ' . count($students),
            ['class' => 'text-muted']
        );

        if (empty($students)) {
            echo $OUTPUT->notification(
                get_string('nostudentsfound', 'block_pegase'),
                \core\output\notification::NOTIFY_WARNING
            );
        } else {

            // Students table.
            $table = new html_table();
            $table->head = [
                get_string('studentcode', 'block_pegase'),
                get_string('lastname', 'block_pegase'),
                get_string('firstname', 'block_pegase'),
                get_string('moodleaccount', 'block_pegase'),
            ];
            $table->attributes['class'] = 'table table-striped table-hover';

            foreach ($students as $student) {
                $code = $student['codeApprenant'];

                $moodleuser = $DB->get_record(
                    'user',
                    ['idnumber' => $code],
                    'id, firstname, lastname'
                );

                $account_cell = $moodleuser
                    ? html_writer::tag('span', 'Compte Moodle OK', ['class' => 'text-success'])
                    : html_writer::tag('span', get_string('noaccount', 'block_pegase'), ['class' => 'text-warning']);

                $table->data[] = [
                    s($code),
                    s($student['nomFamille']),
                    s($student['prenom']),
                    $account_cell,
                ];
            }

            echo html_writer::table($table);

            // Confirmation form.
            ?>
            <form method="post" action="<?php echo $PAGE->url; ?>">
                <input type="hidden" name="sesskey"   value="<?php echo sesskey(); ?>">
                <input type="hidden" name="courseid"  value="<?php echo $courseid; ?>">
                <input type="hidden" name="action"    value="confirm">
                <input type="hidden" name="confirmed" value="1">
                <input type="hidden" name="code_ec"   value="<?php echo s($code_ec); ?>">
                <input type="hidden" name="periode"   value="<?php echo s($periode); ?>">
                <input type="hidden" name="title"     value="<?php echo s($title); ?>">

                <div class="d-flex gap-2 mt-3">
                    <button type="submit" class="btn btn-success">
                        <?php echo get_string('confirmenrol', 'block_pegase'); ?>
                    </button>
                    <a href="<?php echo new moodle_url('/course/view.php', ['id' => $courseid]); ?>"
                       class="btn btn-secondary">
                        <?php echo get_string('cancel', 'block_pegase'); ?>
                    </a>
                </div>
            </form>
            <?php
        }
   // }
}

echo $OUTPUT->footer();