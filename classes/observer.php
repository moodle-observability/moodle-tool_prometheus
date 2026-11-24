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

namespace tool_prometheus;

use tool_prometheus\local\counter;
use tool_prometheus\task\compute_folder_sizes;

/**
 * Event observers.
 *
 * @package    tool_prometheus
 * @copyright  2026 Moodle Observability contributors
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class observer {
    /** Counter of folder downloads. */
    public const FOLDER_DOWNLOADS = 'moodle_folder_downloads_total';

    /**
     * Counts a folder download (ZIP of every file) by size band and by the state of the "Download folder" button.
     *
     * download_enabled="0" means the ZIP was requested through the URL of a folder whose button is hidden. Never
     * breaks the download: failures are reported with debugging().
     *
     * @param \mod_folder\event\all_files_downloaded $event Event.
     * @return void
     */
    public static function folder_downloaded(\mod_folder\event\all_files_downloaded $event): void {
        global $DB;
        try {
            $bytes = (int) $DB->get_field_sql(
                "SELECT COALESCE(SUM(filesize), 0)
                   FROM {files}
                  WHERE contextid = :contextid AND component = :component AND filearea = :filearea
                        AND filename <> :dot",
                ['contextid' => $event->contextid, 'component' => 'mod_folder', 'filearea' => 'content', 'dot' => '.']
            );
            $download = (int) $DB->get_field('folder', 'showdownloadfolder', ['id' => $event->objectid]);
            counter::increment(self::FOLDER_DOWNLOADS, [
                'size_band' => compute_folder_sizes::bands()->label($bytes / 1048576),
                'download_enabled' => $download ? '1' : '0',
            ]);
        } catch (\Throwable $e) {
            debugging('tool_prometheus: could not count a folder download: ' . $e->getMessage(), DEBUG_DEVELOPER);
        }
    }
}
