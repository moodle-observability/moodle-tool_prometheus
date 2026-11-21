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
 * Metrics endpoint scraped by Prometheus.
 *
 * Authenticated by the token in the Authorization: Bearer header, never by a Moodle session.
 *
 * @package    tool_prometheus
 * @copyright  2026 Moodle Observability contributors
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('NO_MOODLE_COOKIES', true);
define('NO_DEBUG_DISPLAY', true);

// phpcs:ignore moodle.Files.RequireLogin.Missing -- Authenticated by token (tool_prometheus\local\auth).
require(__DIR__ . '/../../../config.php');

$status = \tool_prometheus\local\auth::check_request($_SERVER);
if ($status !== \tool_prometheus\local\auth::OK) {
    \tool_prometheus\local\auth::send_error($status);
    die();
}

$openmetrics = \tool_prometheus\local\exporter::wants_openmetrics($_SERVER['HTTP_ACCEPT'] ?? '');
$metrics = (new \tool_prometheus\local\registry())->collect();

header('Content-Type: ' . \tool_prometheus\local\exporter::content_type($openmetrics));
header('Cache-Control: no-store');
echo \tool_prometheus\local\exporter::render($metrics, $openmetrics);
