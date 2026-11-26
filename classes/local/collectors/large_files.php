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
 * Large files by component and size band, as computed by the scheduled task (never during a scrape).
 *
 * @package    tool_prometheus
 * @copyright  2026 Moodle Observability contributors
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class large_files implements collector {
    #[\Override]
    public function get_name(): string {
        return 'large_files';
    }

    #[\Override]
    public function collect(): array {
        $data = json_decode((string) get_config('tool_prometheus', 'largefiles'), true);
        if (!is_array($data)) {
            return [];
        }
        $files = new metric('moodle_files_large', 'Large files (distinct contents) by component and size band (MiB).');
        foreach ($data as $component => $bands) {
            foreach ((array) $bands as $band => $count) {
                $files->set((int) $count, ['component' => (string) $component, 'size_band' => (string) $band]);
            }
        }
        return [
            $files,
            (new metric('moodle_files_large_scan_timestamp_seconds', 'Unix time of the last count of the large files.'))
                ->set((int) get_config('tool_prometheus', 'largefiles_time')),
        ];
    }
}
