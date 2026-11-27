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
 * Counts the large files of some components (pages, resources, folders) by size band, for moodle_files_large.
 *
 * Very large files (PDFs of hundreds of MB) hurt downloads and backups. Only files above the first band limit are
 * read, and each content (contenthash) is counted once per component, so copies of a file are not counted twice.
 *
 * @package    tool_prometheus
 * @copyright  2026 Moodle Observability contributors
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class compute_large_files extends \core\task\scheduled_task {
    /** Default band limits, in MiB; the first one is the floor (smaller files are not counted). */
    public const DEFAULT_BANDS = [50, 100];

    /** Default components. */
    public const DEFAULT_COMPONENTS = ['mod_page', 'mod_resource', 'mod_folder'];

    /** Most components accepted (bounds the label cardinality). */
    public const MAX_COMPONENTS = 20;

    #[\Override]
    public function get_name(): string {
        return get_string('task_compute_large_files', 'tool_prometheus');
    }

    #[\Override]
    public function execute(): void {
        $start = microtime(true);
        $result = self::compute();
        set_config('largefiles', json_encode($result), 'tool_prometheus');
        set_config('largefiles_time', time(), 'tool_prometheus');
        set_config('largefiles_duration', round(microtime(true) - $start, 3), 'tool_prometheus');
        $total = 0;
        foreach ($result as $bandcounts) {
            $total += array_sum($bandcounts);
        }
        mtrace("Large files: {$total}.");
    }

    /**
     * Large files by component and size band.
     *
     * @return array Component => [band label => count]; bands below the floor are left out.
     */
    public static function compute(): array {
        global $DB;
        $bands = self::bands();
        $labels = array_slice($bands->labels(), 1);
        $components = self::components();
        $floor = $bands->limits()[0] * 1048576;

        $result = [];
        foreach ($components as $component) {
            $result[$component] = array_fill_keys($labels, 0);
        }
        [$insql, $params] = $DB->get_in_or_equal($components, SQL_PARAMS_NAMED);
        $params += ['floor' => $floor, 'dot' => '.'];
        $rs = $DB->get_recordset_sql(
            "SELECT component, contenthash, MAX(filesize) AS bytes
               FROM {files}
              WHERE filesize > :floor AND filename <> :dot AND component {$insql}
           GROUP BY component, contenthash",
            $params
        );
        foreach ($rs as $row) {
            $result[$row->component][$bands->label((int) $row->bytes / 1048576)]++;
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
        return bands::from_setting((string) get_config('tool_prometheus', 'largefilebands'), self::DEFAULT_BANDS, 'MB');
    }

    /**
     * Components whose files are counted, from the settings.
     *
     * @return string[]
     */
    public static function components(): array {
        $setting = (string) get_config('tool_prometheus', 'largefilecomponents');
        $components = array_filter(array_map('trim', explode(',', $setting)), fn(string $c): bool =>
            (bool) preg_match('/^[a-z][a-z0-9_]*$/', $c));
        $components = array_slice(array_values(array_unique($components)), 0, self::MAX_COMPONENTS);
        return $components ?: self::DEFAULT_COMPONENTS;
    }
}
