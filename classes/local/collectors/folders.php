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
 * Folders (mod_folder) by size band and download button, as computed by the scheduled task (never during a scrape).
 *
 * Names and labels are the same as moodle_ops_exporter, so that the same dashboards and alerts work with both.
 *
 * @package    tool_prometheus
 * @copyright  2026 Moodle Observability contributors
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class folders implements collector {
    #[\Override]
    public function get_name(): string {
        return 'folders';
    }

    #[\Override]
    public function collect(): array {
        $data = json_decode((string) get_config('tool_prometheus', 'folders'), true);
        if (!is_array($data) || !isset($data['bands'])) {
            return [];
        }
        $folders = new metric('moodle_folders', 'Folders (mod_folder) by size band (MiB) and download button.');
        foreach ($data['bands'] as $band => $counts) {
            foreach (['0', '1'] as $download) {
                $folders->set((int) ($counts[$download] ?? 0), ['size_band' => (string) $band, 'download_enabled' => $download]);
            }
        }
        return [
            $folders,
            (new metric('moodle_folder_max_bytes', 'Size of the largest folder, in bytes.'))
                ->set((int) ($data['max'] ?? 0)),
            (new metric(
                'moodle_folders_large_download_enabled',
                'Folders above the download size limit with the "Download folder" button enabled.'
            ))->set((int) ($data['largeenabled'] ?? 0)),
            (new metric('moodle_folders_scan_timestamp_seconds', 'Unix time of the last count of the folders.'))
                ->set((int) get_config('tool_prometheus', 'folders_time')),
        ];
    }
}
