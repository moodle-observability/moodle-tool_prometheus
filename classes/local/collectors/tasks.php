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
 * Adhoc task queue and failing scheduled tasks.
 *
 * @package    tool_prometheus
 * @copyright  2026 Moodle Observability contributors
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class tasks implements collector {
    #[\Override]
    public function get_name(): string {
        return 'tasks';
    }

    #[\Override]
    public function collect(): array {
        global $DB;
        $now = time();

        $queued = $DB->count_records_select('task_adhoc', 'timestarted IS NULL');
        $due = $DB->count_records_select('task_adhoc', 'timestarted IS NULL AND nextruntime <= :now', ['now' => $now]);
        $running = $DB->count_records_select('task_adhoc', 'timestarted IS NOT NULL');
        $failing = $DB->count_records_select('task_adhoc', 'faildelay > 0');
        $oldest = $DB->get_field_sql('SELECT MIN(timecreated) FROM {task_adhoc} WHERE timestarted IS NULL');
        $scheduledfailing = $DB->count_records_select('task_scheduled', 'faildelay > 0 AND disabled = 0');

        return [
            (new metric('moodle_adhoc_tasks_queued', 'Adhoc tasks waiting to run (not started).'))->set($queued),
            (new metric('moodle_adhoc_tasks_due', 'Adhoc tasks waiting to run whose next run time has passed.'))->set($due),
            (new metric('moodle_adhoc_tasks_running', 'Adhoc tasks marked as running.'))->set($running),
            (new metric('moodle_adhoc_tasks_failing', 'Adhoc tasks with a fail delay (failed, waiting for a retry).'))
                ->set($failing),
            (new metric('moodle_adhoc_task_oldest_age_seconds', 'Age of the oldest adhoc task waiting to run (0 if none).'))
                ->set($oldest ? max(0, $now - (int) $oldest) : 0),
            (new metric('moodle_scheduled_tasks_failing', 'Enabled scheduled tasks with a fail delay.'))->set($scheduledfailing),
        ];
    }
}
