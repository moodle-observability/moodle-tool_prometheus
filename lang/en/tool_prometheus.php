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
$string['adhocclasswindow'] = 'Adhoc task log window';
$string['adhocclasswindow_desc'] = 'Seconds of the task log read by <code>moodle_adhoc_class_runs</code>, <code>moodle_adhoc_class_failures</code> and <code>moodle_adhoc_class_duration_seconds</code> (the 10 classes with the longest total duration).';
$string['allowedips'] = 'Allowed IP addresses';
$string['allowedips_desc'] = 'Optional. Only these addresses or ranges (one per line, CIDR or partial addresses) may read the metrics. Empty: any address with a valid token.';
$string['cachedef_metrics'] = 'Values of expensive metrics, kept for one minute';
$string['endpoint'] = 'Metrics endpoint';
$string['endpoint_desc'] = 'Prometheus scrapes <code>{$a}</code> with the header <code>Authorization: Bearer &lt;token&gt;</code>.';
$string['error:forbidden'] = 'Address not allowed.';
$string['error:notconfigured'] = 'The metrics token is not configured.';
$string['error:unauthorized'] = 'Missing or invalid token.';
$string['folderbands'] = 'Folder size bands';
$string['folderbands_desc'] = 'Comma separated limits, in MiB, of the size bands of the <code>moodle_folders</code> metric. A folder equal to a limit belongs to the band of that limit; with 25,50,100 the bands are 0-25MB, 25-50MB, 50-100MB and >100MB.';
$string['folderdownloadlimit'] = 'Folder download limit';
$string['folderdownloadlimit_desc'] = 'Size, in MiB, above which a folder with the "Download folder" button enabled is counted in <code>moodle_folders_large_download_enabled</code>: the ZIP of such a folder is built in PHP and holds a web server process for a long time.';
$string['forumbands'] = 'Forum discussion bands';
$string['forumbands_desc'] = 'Comma separated limits, in posts, of the bands of <code>moodle_forum_discussions</code>. A discussion equal to a limit belongs to the band of that limit; with 1000,5000,10000 the bands are 0-1000, 1000-5000, 5000-10000 and >10000.';
$string['forumlargelimit'] = 'Large discussion limit';
$string['forumlargelimit_desc'] = 'Number of posts above which a forum discussion is counted in <code>moodle_forum_discussions_large</code>: such discussions take too long to open.';
$string['pluginname'] = 'Prometheus exporter';
$string['privacy:metadata'] = 'The Prometheus exporter only exposes aggregated counts; it does not store personal data.';
$string['task_compute_disk_usage'] = 'Compute moodledata disk usage for the Prometheus exporter';
$string['task_compute_folder_sizes'] = 'Count the folders by size for the Prometheus exporter';
$string['task_compute_forum_sizes'] = 'Count the forum discussions by posts for the Prometheus exporter';
$string['token'] = 'Token';
$string['token_desc'] = 'Secret sent by Prometheus in the <code>Authorization: Bearer</code> header. Use a long random value. It can also be forced in config.php: <code>$CFG->forced_plugin_settings[\'tool_prometheus\'][\'token\'] = getenv(\'MOODLE_PROMETHEUS_TOKEN\');</code>. Without a token the endpoint answers 503.';
