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
 * Adhoc tasks by class: runs, failures and duration from the task log, and the current queue.
 *
 * The task log metrics have the same names and selection as moodle_ops_exporter (last hour, top 10 classes by summed
 * duration, without "other"). The queue metrics exist only here (top 10 classes by queue size, the rest in "other").
 * Values are kept for one minute in the application cache.
 *
 * @package    tool_prometheus
 * @copyright  2026 Moodle Observability contributors
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class adhoc_classes implements collector {
    /** Most classes reported per metric (bounds the label cardinality). */
    public const TOP = 10;

    /** Default window of the task log metrics, in seconds. */
    public const DEFAULT_WINDOW = 3600;

    /** Label of the classes beyond the top of the queue. */
    public const OTHER = 'other';

    #[\Override]
    public function get_name(): string {
        return 'adhoc_classes';
    }

    #[\Override]
    public function collect(): array {
        $cache = \cache::make('tool_prometheus', 'metrics');
        $data = $cache->get('adhocclasses');
        if ($data === false) {
            $data = ['log' => self::from_log(), 'queue' => self::from_queue()];
            $cache->set('adhocclasses', $data);
        }

        $runs = new metric('moodle_adhoc_class_runs', 'Adhoc task runs in the window, by class (task log).');
        $failures = new metric('moodle_adhoc_class_failures', 'Failed adhoc task runs in the window, by class (task log).');
        $duration = new metric(
            'moodle_adhoc_class_duration_seconds',
            'Duration of the adhoc task runs in the window, by class (stat: sum or max).'
        );
        foreach ($data['log'] as $class => $row) {
            $runs->set($row['runs'], ['classname' => $class]);
            $failures->set($row['failures'], ['classname' => $class]);
            $duration->set($row['sum'], ['classname' => $class, 'stat' => 'sum']);
            $duration->set($row['max'], ['classname' => $class, 'stat' => 'max']);
        }

        $queued = new metric('moodle_adhoc_class_queued', 'Adhoc tasks waiting to run, by class.');
        $oldest = new metric(
            'moodle_adhoc_class_oldest_age_seconds',
            'Age of the oldest adhoc task waiting to run, by class.'
        );
        $now = \core\di::get(\core\clock::class)->time();
        foreach ($data['queue'] as $class => $row) {
            $queued->set($row['queued'], ['classname' => $class]);
            $oldest->set(max(0, $now - $row['oldest']), ['classname' => $class]);
        }
        return [$runs, $failures, $duration, $queued, $oldest];
    }

    /**
     * Runs, failures and duration of the adhoc tasks in the window, for the classes with the longest total duration.
     *
     * @return array Class name => runs, failures, sum, max (seconds).
     */
    public static function from_log(): array {
        global $DB;
        $window = (int) get_config('tool_prometheus', 'adhocclasswindow');
        $window = $window > 0 ? $window : self::DEFAULT_WINDOW;
        $since = \core\di::get(\core\clock::class)->time() - $window;
        $sql = "SELECT classname, COUNT(1) AS runs,
                       SUM(CASE WHEN result <> 0 THEN 1 ELSE 0 END) AS failures,
                       SUM(timeend - timestart) AS total, MAX(timeend - timestart) AS longest
                  FROM {task_log}
                 WHERE type = :type AND timestart >= :since
              GROUP BY classname
              ORDER BY total DESC, classname";
        $rows = $DB->get_records_sql(
            $sql,
            ['type' => \core\task\database_logger::TYPE_ADHOC, 'since' => $since],
            0,
            self::TOP
        );
        $result = [];
        foreach ($rows as $row) {
            $result[self::classname($row->classname)] = [
                'runs' => (int) $row->runs,
                'failures' => (int) $row->failures,
                'sum' => round((float) $row->total, 3),
                'max' => round((float) $row->longest, 3),
            ];
        }
        return $result;
    }

    /**
     * Adhoc tasks waiting to run, for the classes with the longest queue; the others are added up in "other".
     *
     * @return array Class name => queued, oldest (Unix time of the oldest).
     */
    public static function from_queue(): array {
        global $DB;
        $rows = $DB->get_records_sql(
            "SELECT classname, COUNT(1) AS queued, MIN(timecreated) AS oldest
               FROM {task_adhoc}
              WHERE timestarted IS NULL
           GROUP BY classname
           ORDER BY queued DESC, classname"
        );
        $result = [];
        foreach (array_values($rows) as $i => $row) {
            $class = $i < self::TOP ? self::classname($row->classname) : self::OTHER;
            $current = $result[$class] ?? ['queued' => 0, 'oldest' => PHP_INT_MAX];
            $result[$class] = [
                'queued' => $current['queued'] + (int) $row->queued,
                'oldest' => min($current['oldest'], (int) $row->oldest),
            ];
        }
        return $result;
    }

    /**
     * Class name without the leading backslash, as the task log stores it (and as moodle_ops_exporter labels it).
     *
     * @param string $classname Class name.
     * @return string
     */
    public static function classname(string $classname): string {
        return ltrim($classname, '\\');
    }
}
