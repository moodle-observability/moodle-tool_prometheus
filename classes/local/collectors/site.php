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

namespace tool_prometheus\local\collectors;

use tool_prometheus\local\collector;
use tool_prometheus\local\metric;

/**
 * Site information: Moodle version, maintenance mode and last cron start.
 *
 * @package    tool_prometheus
 * @copyright  2026 Moodle Observability contributors
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class site implements collector {
    #[\Override]
    public function get_name(): string {
        return 'site';
    }

    #[\Override]
    public function collect(): array {
        global $CFG;
        $info = (new metric('moodle_info', 'Moodle version information (always 1).'))->set(1, [
            'release' => (string) $CFG->release,
            'version' => (string) $CFG->version,
            'branch' => (string) $CFG->branch,
        ]);
        $maintenance = (new metric('moodle_maintenance_mode', 'Whether the site is in maintenance mode (1) or not (0).'))
            ->set(empty($CFG->maintenance_enabled) ? 0 : 1);
        $cron = (new metric('moodle_cron_last_start_timestamp_seconds', 'Unix time of the last start of the cron (0 if unknown).'))
            ->set((int) get_config('tool_task', 'lastcronstart'));
        return [$info, $maintenance, $cron];
    }
}
