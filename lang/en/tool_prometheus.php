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
 * English strings.
 *
 * @package    tool_prometheus
 * @copyright  2026 Moodle Observability contributors
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['activewindows'] = 'Active user windows';
$string['activewindows_desc'] = 'Comma separated time windows, in seconds, for the <code>moodle_users_active</code> gauge (one label per window). Example: 300,3600,86400.';
$string['allowedips'] = 'Allowed IP addresses';
$string['allowedips_desc'] = 'Optional. Only these addresses or ranges (one per line, CIDR or partial addresses) may read the metrics. Empty: any address with a valid token.';
$string['cachedef_metrics'] = 'Values of expensive metrics, kept for one minute';
$string['endpoint'] = 'Metrics endpoint';
$string['endpoint_desc'] = 'Prometheus scrapes <code>{$a}</code> with the header <code>Authorization: Bearer &lt;token&gt;</code>.';
$string['error:forbidden'] = 'Address not allowed.';
$string['error:notconfigured'] = 'The metrics token is not configured.';
$string['error:unauthorized'] = 'Missing or invalid token.';
$string['pluginname'] = 'Prometheus exporter';
$string['privacy:metadata'] = 'The Prometheus exporter only exposes aggregated counts; it does not store personal data.';
$string['task_compute_disk_usage'] = 'Compute moodledata disk usage for the Prometheus exporter';
$string['token'] = 'Token';
$string['token_desc'] = 'Secret sent by Prometheus in the <code>Authorization: Bearer</code> header. Use a long random value. It can also be forced in config.php: <code>$CFG->forced_plugin_settings[\'tool_prometheus\'][\'token\'] = getenv(\'MOODLE_PROMETHEUS_TOKEN\');</code>. Without a token the endpoint answers 503.';
