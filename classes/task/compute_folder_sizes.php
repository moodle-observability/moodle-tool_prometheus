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

namespace tool_prometheus\task;

use tool_prometheus\local\bands;

/**
 * Counts the folders (mod_folder) by size band and download button, for the moodle_folders metrics.
 *
 * "Download folder" makes mod/folder/download_folder.php build the ZIP in PHP, which holds a web server process for
 * minutes with large folders. Read only: one aggregate query; the result is stored in the plugin configuration.
 *
 * @package    tool_prometheus
 * @copyright  2026 Moodle Observability contributors
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class compute_folder_sizes extends \core\task\scheduled_task {
    /** Default band limits, in MiB. */
    public const DEFAULT_BANDS = [25, 50, 100];

    /** Default size above which a folder with the download enabled is reported, in MiB. */
    public const DEFAULT_DOWNLOAD_LIMIT = 50;

    #[\Override]
    public function get_name(): string {
        return get_string('task_compute_folder_sizes', 'tool_prometheus');
    }

    #[\Override]
    public function execute(): void {
        $start = microtime(true);
        $result = self::compute();
        set_config('folders', json_encode($result), 'tool_prometheus');
        set_config('folders_time', time(), 'tool_prometheus');
        set_config('folders_duration', round(microtime(true) - $start, 3), 'tool_prometheus');
        mtrace(sprintf(
            'Folders: %d, largest %s, %d above the download limit with the download enabled.',
            array_sum(array_map('array_sum', $result['bands'])),
            display_size($result['max']),
            $result['largeenabled']
        ));
    }

    /**
     * Folders by size band and download button, the largest folder and the large folders with the download enabled.
     *
     * @return array bands (label => ['0' => count, '1' => count]), max (bytes), largeenabled (count).
     */
    public static function compute(): array {
        global $DB;
        $bands = self::bands();
        $limit = (int) get_config('tool_prometheus', 'folderdownloadlimit');
        $limit = ($limit > 0 ? $limit : self::DEFAULT_DOWNLOAD_LIMIT) * 1048576;

        $result = ['bands' => [], 'max' => 0, 'largeenabled' => 0];
        foreach ($bands->labels() as $label) {
            $result['bands'][$label] = ['0' => 0, '1' => 0];
        }

        $sql = "SELECT cm.id, f.showdownloadfolder AS download, COALESCE(SUM(fl.filesize), 0) AS bytes
                  FROM {course_modules} cm
                  JOIN {modules} m ON m.id = cm.module AND m.name = :modname
                  JOIN {folder} f ON f.id = cm.instance
                  JOIN {context} ctx ON ctx.instanceid = cm.id AND ctx.contextlevel = :contextlevel
             LEFT JOIN {files} fl ON fl.contextid = ctx.id AND fl.component = :component
                                 AND fl.filearea = :filearea AND fl.filename <> :dot
                 WHERE cm.deletioninprogress = 0
              GROUP BY cm.id, f.showdownloadfolder";
        $params = ['modname' => 'folder', 'contextlevel' => CONTEXT_MODULE, 'component' => 'mod_folder',
            'filearea' => 'content', 'dot' => '.'];
        $rs = $DB->get_recordset_sql($sql, $params);
        foreach ($rs as $row) {
            $bytes = (int) $row->bytes;
            $download = empty($row->download) ? '0' : '1';
            $result['bands'][$bands->label($bytes / 1048576)][$download]++;
            $result['max'] = max($result['max'], $bytes);
            if ($download === '1' && $bytes > $limit) {
                $result['largeenabled']++;
            }
        }
        $rs->close();
        return $result;
    }

    /**
     * Size bands, in MiB, from the settings.
     *
     * @return bands
     */
    public static function bands(): bands {
        return bands::from_setting((string) get_config('tool_prometheus', 'folderbands'), self::DEFAULT_BANDS, 'MB');
    }
}
