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
 * Enrol page by browsing a formation tree.
 *
 * @package     block_pegase
 * @copyright   2026 Olivier VALENTIN
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once($CFG->dirroot . '/blocks/pegase/classes/api.php');
require_once($CFG->dirroot . '/blocks/pegase/locallib.php');

$courseid  = required_param('courseid', PARAM_INT);
$confirmed = optional_param('confirmed', 0, PARAM_INT);
$action    = optional_param('action', '', PARAM_ALPHA);
$code_ec   = optional_param('code_ec', '', PARAM_ALPHANUMEXT);
$periode   = optional_param('periode', 'PERIODE-25-26', PARAM_ALPHANUMEXT);

$course  = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
$context = context_course::instance($courseid);

require_login($course);
require_capability('block/pegase:manage', $context);

$PAGE->set_url('/blocks/pegase/browse.php', ['courseid' => $courseid]);
$PAGE->set_title(get_string('browsetitle', 'block_pegase'));
$PAGE->set_heading($course->fullname);
$PAGE->set_pagelayout('incourse');

// Periods available
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

// Action : confirm and create enrol_wsscol instance
if ($action === 'confirm' && $confirmed && !empty($code_ec) && !empty($periode)) {

    $wsscol_plugin = enrol_get_plugin('wsscol');
    if (!$wsscol_plugin) {
        print_error('Plugin enrol_wsscol not found.');
    }

    $scolarapp_id = get_config('block_pegase', 'wsscol_scolarapp_id');
    if (empty($scolarapp_id)) {
        print_error('PEGASE scolarapp ID not configured in block settings.');
    }

    // Check if already enrolled
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

    // Create enrol_wsscol instance
    $wsscol_plugin->add_instance($course, [
        'name'        => 'PEGASE - ' . $code_ec . ' (' . $periode . ')',
        'customchar1' => $code_ec,
        'customchar2' => $periode,
        'customint2'  => $scolarapp_id,
        'status'      => ENROL_INSTANCE_ENABLED,
    ]);

    redirect(
        new moodle_url('/course/view.php', ['id' => $courseid]),
        get_string('instancecreated', 'block_pegase'),
        null,
        \core\output\notification::NOTIFY_SUCCESS
    );
}

$ajax_url = new moodle_url('/blocks/pegase/ajax.php');
$sesskey  = sesskey();

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('browsetitle', 'block_pegase'));

// Back link
echo html_writer::tag('p',
    html_writer::link(
        new moodle_url('/course/view.php', ['id' => $courseid]),
        '← ' . get_string('backtocourse', 'block_pegase')
    )
);
?>

<div id="pegase-browser">

    <?php /* ================================================================
     STEP 1 : Search formation
    ================================================================ */ ?>
    <div class="card mb-4">
        <div class="card-header fw-bold">
            <?php echo get_string('step1search', 'block_pegase'); ?>
        </div>
        <div class="card-body">
            <div class="mb-3">
                <label for="periode-select" class="form-label fw-bold">
                    <?php echo get_string('selectperiod', 'block_pegase'); ?>
                </label>
                <select id="periode-select" class="form-select w-auto">
                    <option value="">-- Chargement des périodes... --</option>
                </select>
                <div id="periode-loading" class="text-muted small mt-1">
                    <i class="fa fa-spinner fa-spin me-1"></i>Chargement...
                </div>
            </div>
            <div class="input-group w-50">
                <input type="text"
                       id="search-input"
                       class="form-control"
                       placeholder="<?php echo get_string('searchplaceholder', 'block_pegase'); ?>">
                <button id="search-btn" class="btn btn-primary">
                    <?php echo get_string('searchstudents', 'block_pegase'); ?>
                </button>
            </div>
            <div id="search-results" class="mt-3"></div>
        </div>
    </div>

    <?php /* ================================================================
     STEP 2 : Formation tree
    ================================================================ */ ?>
    <div class="card mb-4 d-none" id="tree-card">
        <div class="card-header fw-bold">
            <?php echo get_string('step2tree', 'block_pegase'); ?>
            <span id="tree-formation-name" class="text-muted fw-normal ms-2"></span>
        </div>
        <div class="card-body">
            <div id="formation-tree"></div>
        </div>
    </div>

    <?php /* ================================================================
     STEP 3 : Students list + confirmation
    ================================================================ */ ?>
    <div class="card mb-4 d-none" id="students-card">
        <div class="card-header fw-bold">
            <?php echo get_string('step3students', 'block_pegase'); ?>
            <span id="students-ec-name" class="text-muted fw-normal ms-2"></span>
        </div>
        <div class="card-body">
            <div id="students-list"></div>

            <form method="post"
                  action="<?php echo $PAGE->url; ?>"
                  id="confirm-form"
                  class="d-none mt-3">
                <input type="hidden" name="sesskey"   value="<?php echo $sesskey; ?>">
                <input type="hidden" name="courseid"  value="<?php echo $courseid; ?>">
                <input type="hidden" name="action"    value="confirm">
                <input type="hidden" name="confirmed" value="1">
                <input type="hidden" name="code_ec"   id="confirm-code-ec" value="">
                <input type="hidden" name="periode"   id="confirm-periode"  value="">

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-success">
                        <?php echo get_string('confirmenrol', 'block_pegase'); ?>
                    </button>
                    <a href="<?php echo new moodle_url('/course/view.php', ['id' => $courseid]); ?>"
                       class="btn btn-secondary">
                        <?php echo get_string('cancel', 'block_pegase'); ?>
                    </a>
                </div>
            </form>
        </div>
    </div>

</div>

<script>
const AJAX_URL = '<?php echo $ajax_url; ?>';
const SESSKEY  = '<?php echo $sesskey; ?>';

// =========================================================================
// UTILITY
// =========================================================================

function showSpinner(container) {
    document.getElementById(container).innerHTML =
        '<div class="d-flex align-items-center gap-2 text-muted">' +
        '<div class="spinner-border spinner-border-sm"></div>' +
        '<span>Chargement...</span></div>';
}

function showError(container, message) {
    document.getElementById(container).innerHTML =
        '<div class="alert alert-danger">' + message + '</div>';
}

async function ajaxCall(action, params) {
    const body = new URLSearchParams({ action, sesskey: SESSKEY, ...params });
    const response = await fetch(AJAX_URL, { method: 'POST', body });
    const data = await response.json();
    if (!data.success) throw new Error(data.error || 'Unknown error');
    return data.data;
}

function escapeHtml(str) {
    if (!str) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}

// =========================================================================
// LOAD PERIODS ON PAGE LOAD
// =========================================================================

async function loadPeriods() {
    try {
        const periods = await ajaxCall('espaces', {});
        const select  = document.getElementById('periode-select');
        const loading = document.getElementById('periode-loading');

        select.innerHTML = '';
        periods.forEach(period => {
            const option = document.createElement('option');
            option.value           = period.id;    // UUID for ODF search
            option.dataset.code    = period.code;  // PERIODE-25-26 for CHC API
            option.textContent     = period.libelle;
            select.appendChild(option);
        });

        if (loading) loading.style.display = 'none';

    } catch (e) {
        console.error('Failed to load periods:', e);
        document.getElementById('periode-select').innerHTML =
            '<option value="">Erreur de chargement</option>';
    }
}

// Load periods when page is ready
loadPeriods();

// =========================================================================
// STEP 1 : SEARCH FORMATIONS — now uses espace UUID
// =========================================================================

document.getElementById('search-btn').addEventListener('click', async () => {
    const keyword    = document.getElementById('search-input').value.trim();
    const select     = document.getElementById('periode-select');
    const espace_id  = select.value;  // UUID

    if (!keyword || !espace_id) return;

    showSpinner('search-results');
    document.getElementById('tree-card').classList.add('d-none');
    document.getElementById('students-card').classList.add('d-none');

    try {
        const result = await ajaxCall('search', { keyword, espace_id });
        renderSearchResults(result);
    } catch (e) {
        showError('search-results', e.message);
    }
});

document.getElementById('search-input').addEventListener('keypress', (e) => {
    if (e.key === 'Enter') document.getElementById('search-btn').click();
});

function renderSearchResults(result) {
    const container = document.getElementById('search-results');

    if (!result.items || result.items.length === 0) {
        container.innerHTML = '<div class="alert alert-warning">Aucune formation trouvée.</div>';
        return;
    }

    let html = '<p class="text-muted">' + result.items.length + ' formation(s) trouvée(s)';
    if (result.total_elements > result.items.length) {
        html += ' sur ' + result.total_elements
              + ' — affinez votre recherche pour voir plus de résultats.';
    }
    html += '</p><div class="list-group">';

    result.items.forEach(item => {
        html += '<button type="button" '
              + 'class="list-group-item list-group-item-action" '
              + 'data-id="' + escapeHtml(item.id) + '" '
              + 'data-label="' + escapeHtml(item.libelleLong || item.libelle) + '" '
              + 'onclick="loadTree(this)">'
              + '<strong>' + escapeHtml(item.code) + '</strong> — '
              + escapeHtml(item.libelleLong || item.libelle)
              + '</button>';
    });

    html += '</div>';
    container.innerHTML = html;
}

// =========================================================================
// STEP 2 : LOAD FORMATION TREE
// =========================================================================

async function loadTree(btn) {
    const formation_id = btn.dataset.id;
    const label        = btn.dataset.label;

    document.querySelectorAll('#search-results .list-group-item').forEach(el => {
        el.classList.remove('active');
    });
    btn.classList.add('active');

    document.getElementById('tree-card').classList.remove('d-none');
    document.getElementById('tree-formation-name').textContent = label;
    document.getElementById('students-card').classList.add('d-none');
    showSpinner('formation-tree');

    try {
        const tree = await ajaxCall('tree', { formation_id });
        renderTree(tree);
    } catch (e) {
        showError('formation-tree', e.message);
    }
}

function renderTree(node) {
    window.nodeCounter = 0;
    document.getElementById('formation-tree').innerHTML = buildTreeHtml(node, 0);
}

function buildTreeHtml(node, depth) {
    if (!node) return '';

    const type    = node.type || '';
    const code    = node.code || '';
    const label   = node.libelle || '';
    const enfants = node.enfants || [];

    // All nodes are clickable to load students
    const clickable = '<button type="button" class="btn btn-sm btn-outline-success ms-1" '
                    + 'onclick="loadStudents(\'' + escapeHtml(code) + '\', \''
                    + escapeHtml(label) + '\')" '
                    + 'title="Voir les étudiants"><i class="fa fa-users me-1"></i> '
                    + '<strong>' + escapeHtml(code) + '</strong> — '
                    + escapeHtml(label)
                    + '</button>';

    // Leaf node — just the clickable button
    if (enfants.length === 0) {
        return '<div class="py-1" style="padding-left:' + (depth * 20) + 'px">'
             + getTypeBadge(type) + clickable
             + '</div>';
    }

    // Node with children — collapsible + clickable
    const node_id = 'node-' + (++window.nodeCounter);

    let html = '<div class="py-1" style="padding-left:' + (depth * 20) + 'px">';

    // Toggle button to expand/collapse
    html += '<button class="btn btn-sm btn-link text-start p-0 text-decoration-none me-1" '
          + 'type="button" onclick="toggleNode(\'' + node_id + '\', this)">'
          + '<i class="fa fa-chevron-right me-1"></i> ' + getTypeBadge(type)
          + '</button>';

    // Clickable button for this node
    html += clickable;

    // Children container
    html += '<div id="' + node_id + '" style="display:none">';
    enfants.forEach(enfant => {
        const child = enfant.objetMaquette || enfant;
        html += buildTreeHtml(child, depth + 1);
    });
    html += '</div></div>';

    return html;
}

function getTypeBadge(type) {
    const badges = {
        'SEMESTRE': '<span class="badge bg-primary me-1">SEM</span>',
        'UE':       '<span class="badge bg-info text-dark me-1">UE</span>',
        'EC':       '<span class="badge bg-success me-1">EC</span>',
    };
    return badges[type] || '<span class="badge bg-secondary me-1">' + (type || '?') + '</span>';
}

function toggleNode(nodeId, btn) {
    const el   = document.getElementById(nodeId);
    const icon = btn.querySelector('i');
    if (!el || !icon) return;

    const isHidden = el.style.display === 'none';
    el.style.display = isHidden ? 'block' : 'none';
    icon.className = isHidden
        ? 'fa fa-chevron-down me-1'
        : 'fa fa-chevron-right me-1';
}

// =========================================================================
// STEP 3 : LOAD STUDENTS
// =========================================================================

async function loadStudents(code_ec, label) {
    const select      = document.getElementById('periode-select');
    const selected    = select.options[select.selectedIndex];
    const code_periode = selected ? selected.dataset.code : ''; // PERIODE-25-26

    document.getElementById('students-card').classList.remove('d-none');
    document.getElementById('students-ec-name').textContent = code_ec + ' — ' + label;
    document.getElementById('confirm-form').classList.add('d-none');
    showSpinner('students-list');
    document.getElementById('students-card').scrollIntoView({ behavior: 'smooth' });

    try {
        const result = await ajaxCall('students', { code_ec, code_periode });
        renderStudents(result, code_ec, periode);
    } catch (e) {
        showError('students-list', e.message);
    }
}

function renderStudents(result, code_ec, periode) {
    const students  = result.students || [];
    const container = document.getElementById('students-list');

    if (students.length === 0) {
        container.innerHTML = '<div class="alert alert-warning">Aucun étudiant trouvé.</div>';
        return;
    }

    let matched = 0;
    let html = '<p class="text-muted">' + students.length + ' étudiant(s) trouvé(s)</p>';
    html += '<table class="table table-striped table-hover table-sm">';
    html += '<thead><tr>'
          + '<th>Code</th><th>Nom</th><th>Prénom</th><th>Compte Moodle</th>'
          + '</tr></thead><tbody>';

    students.forEach(s => {
        const account = s.moodle_account
            ? '<span class="text-success"><i class="fa fa-check-circle text-success"></i> ' + escapeHtml(s.moodle_name) + '</span>'
            : '<span class="text-warning"><i class="fa fa-exclamation-triangle text-warning"></i> Aucun compte</span>';
        if (s.moodle_account) matched++;
        html += '<tr>'
              + '<td>' + escapeHtml(s.codeApprenant) + '</td>'
              + '<td>' + escapeHtml(s.nomFamille) + '</td>'
              + '<td>' + escapeHtml(s.prenom) + '</td>'
              + '<td>' + account + '</td>'
              + '</tr>';
    });

    html += '</tbody></table>';
    html += '<p class="text-muted">'
          + matched + ' compte(s) Moodle trouvé(s) sur '
          + students.length + ' étudiant(s).</p>';

    container.innerHTML = html;

    // Show confirmation form
    document.getElementById('confirm-code-ec').value = code_ec;
    document.getElementById('confirm-periode').value = code_periode; // PERIODE-25-26
    document.getElementById('confirm-form').classList.remove('d-none');
}
</script>

<?php echo $OUTPUT->footer(); ?>