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
 * Block PEGASE settings file.
 *
 * @package     block_pegase
 * @copyright   2026 Olivier VALENTIN
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $settings->add(new admin_setting_configtext(
        'block_pegase/authurl',
        get_string('authurl', 'block_pegase'),
        get_string('authurl_desc', 'block_pegase'),
        'https://authn-app.test-umlp.pc-scol.fr/cas/v1/tickets'
    ));

    $settings->add(new admin_setting_configtext(
        'block_pegase/apiurl',
        get_string('apiurl', 'block_pegase'),
        get_string('apiurl_desc', 'block_pegase'),
        'https://chc.test-umlp.pc-scol.fr/api/ext/chc/v1'
    ));

    $settings->add(new admin_setting_configtext(
        'block_pegase/odfurl',
        get_string('odfurl', 'block_pegase'),
        get_string('odfurl_desc', 'block_pegase'),
        'https://odf.test-umlp.pc-scol.fr/api/odf/ext/v1'
    ));

    $settings->add(new admin_setting_configtext(
        'block_pegase/codestructure',
        get_string('codestructure', 'block_pegase'),
        get_string('codestructure_desc', 'block_pegase'),
        'ETAB00'
    ));

    $settings->add(new admin_setting_configtext(
        'block_pegase/username',
        get_string('username', 'block_pegase'),
        '',
        ''
    ));

    $settings->add(new admin_setting_configpasswordunmask(
        'block_pegase/password',
        get_string('password', 'block_pegase'),
        '',
        ''
    ));
}
