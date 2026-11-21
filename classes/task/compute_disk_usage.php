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

/**
 * Computes the size of the top level folders of moodledata for the moodle_moodledata_bytes metric.
 *
 * Read only: it walks the folders (without following links) and stores the result in the plugin configuration.
 *
 * @package    tool_prometheus
 * @copyright  2026 Moodle Observability contributors
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class compute_disk_usage extends \core\task\scheduled_task {
    /** Maximum number of folders reported (bounds the label cardinality). */
    public const MAX_DIRS = 50;

    /** Label of the files directly inside moodledata. */
    public const ROOT_FILES = '_files';

    #[\Override]
    public function get_name(): string {
        return get_string('task_compute_disk_usage', 'tool_prometheus');
    }

    #[\Override]
    public function execute(): void {
        global $CFG;
        $start = microtime(true);
        $usage = self::scan($CFG->dataroot);
        set_config('diskusage', json_encode($usage), 'tool_prometheus');
        set_config('diskusage_time', time(), 'tool_prometheus');
        set_config('diskusage_duration', round(microtime(true) - $start, 3), 'tool_prometheus');
        mtrace('moodledata: ' . display_size(array_sum($usage)) . ' in ' . count($usage) . ' entries');
    }

    /**
     * Size of each top level folder of a directory (the largest MAX_DIRS), plus its loose files.
     *
     * @param string $root Directory.
     * @return array<string, int> Bytes by folder name.
     */
    public static function scan(string $root): array {
        $usage = [];
        $files = 0;
        foreach (new \DirectoryIterator($root) as $entry) {
            if ($entry->isDot() || $entry->isLink()) {
                continue;
            }
            if ($entry->isDir()) {
                $usage[$entry->getFilename()] = self::size($entry->getPathname());
            } else if ($entry->isFile()) {
                $files += $entry->getSize();
            }
        }
        arsort($usage);
        $usage = array_slice($usage, 0, self::MAX_DIRS, true);
        $usage[self::ROOT_FILES] = $files;
        ksort($usage);
        return $usage;
    }

    /**
     * Total size of the files in a folder, recursively, without following links.
     *
     * @param string $dir Folder.
     * @return int Bytes.
     */
    public static function size(string $dir): int {
        $total = 0;
        try {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::LEAVES_ONLY,
                \RecursiveIteratorIterator::CATCH_GET_CHILD
            );
            foreach ($iterator as $file) {
                if (!$file->isLink() && $file->isFile()) {
                    $total += $file->getSize();
                }
            }
        } catch (\UnexpectedValueException $e) {
            // Folder removed or not readable while scanning: report what was counted.
            debugging('tool_prometheus: could not scan ' . $dir . ': ' . $e->getMessage(), DEBUG_DEVELOPER);
        }
        return $total;
    }
}
