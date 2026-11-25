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
 * Admin settings.
 *
 * @package    tool_prometheus
 * @copyright  2026 Moodle Observability contributors
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $settings = new admin_settingpage('tool_prometheus', get_string('pluginname', 'tool_prometheus'));
    $ADMIN->add('tools', $settings);

    if ($ADMIN->fulltree) {
        $url = new moodle_url('/admin/tool/prometheus/metrics.php');
        $settings->add(new admin_setting_heading(
            'tool_prometheus/endpoint',
            get_string('endpoint', 'tool_prometheus'),
            get_string('endpoint_desc', 'tool_prometheus', $url->out(false))
        ));
        $settings->add(new admin_setting_configpasswordunmask(
            'tool_prometheus/token',
            get_string('token', 'tool_prometheus'),
            get_string('token_desc', 'tool_prometheus'),
            ''
        ));
        $settings->add(new admin_setting_configiplist(
            'tool_prometheus/allowedips',
            get_string('allowedips', 'tool_prometheus'),
            get_string('allowedips_desc', 'tool_prometheus'),
            ''
        ));
        $settings->add(new admin_setting_configtext(
            'tool_prometheus/activewindows',
            get_string('activewindows', 'tool_prometheus'),
            get_string('activewindows_desc', 'tool_prometheus'),
            '300,3600,86400',
            PARAM_SEQUENCE
        ));
        $settings->add(new admin_setting_configtext(
            'tool_prometheus/adhocclasswindow',
            get_string('adhocclasswindow', 'tool_prometheus'),
            get_string('adhocclasswindow_desc', 'tool_prometheus'),
            '3600',
            PARAM_INT
        ));
        $settings->add(new admin_setting_configtext(
            'tool_prometheus/folderbands',
            get_string('folderbands', 'tool_prometheus'),
            get_string('folderbands_desc', 'tool_prometheus'),
            '25,50,100',
            PARAM_SEQUENCE
        ));
        $settings->add(new admin_setting_configtext(
            'tool_prometheus/folderdownloadlimit',
            get_string('folderdownloadlimit', 'tool_prometheus'),
            get_string('folderdownloadlimit_desc', 'tool_prometheus'),
            '50',
            PARAM_INT
        ));
    }
}
