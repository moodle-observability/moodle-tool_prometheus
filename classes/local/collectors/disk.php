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
 * Disk usage of moodledata, as computed by the scheduled task (never during a scrape).
 *
 * @package    tool_prometheus
 * @copyright  2026 Moodle Observability contributors
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class disk implements collector {
    #[\Override]
    public function get_name(): string {
        return 'disk';
    }

    #[\Override]
    public function collect(): array {
        $usage = json_decode((string) get_config('tool_prometheus', 'diskusage'), true);
        if (!is_array($usage)) {
            return [];
        }
        $bytes = new metric('moodle_moodledata_bytes', 'Size of the top level folders of moodledata, in bytes.');
        foreach ($usage as $dir => $size) {
            $bytes->set((int) $size, ['dir' => (string) $dir]);
        }
        return [
            $bytes,
            (new metric('moodle_moodledata_scan_timestamp_seconds', 'Unix time of the last moodledata scan.'))
                ->set((int) get_config('tool_prometheus', 'diskusage_time')),
            (new metric('moodle_moodledata_scan_duration_seconds', 'Duration of the last moodledata scan, in seconds.'))
                ->set((float) get_config('tool_prometheus', 'diskusage_duration')),
        ];
    }
}
