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
 * Counts the forum discussions by number of posts, for the moodle_forum_discussions metrics.
 *
 * Discussions with tens of thousands of posts never open (time outs) and nothing else shows them. Read only: one
 * aggregate query on forum_posts; the result is stored in the plugin configuration.
 *
 * @package    tool_prometheus
 * @copyright  2026 Moodle Observability contributors
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class compute_forum_sizes extends \core\task\scheduled_task {
    /** Default band limits, in posts. */
    public const DEFAULT_BANDS = [1000, 5000, 10000];

    /** Default number of posts above which a discussion is counted as large. */
    public const DEFAULT_LARGE_LIMIT = 10000;

    #[\Override]
    public function get_name(): string {
        return get_string('task_compute_forum_sizes', 'tool_prometheus');
    }

    #[\Override]
    public function execute(): void {
        $start = microtime(true);
        $result = self::compute();
        set_config('forums', json_encode($result), 'tool_prometheus');
        set_config('forums_time', time(), 'tool_prometheus');
        set_config('forums_duration', round(microtime(true) - $start, 3), 'tool_prometheus');
        mtrace(sprintf(
            'Forum discussions: %d, largest with %d posts, %d above the limit.',
            array_sum($result['bands']),
            $result['max'],
            $result['large']
        ));
    }

    /**
     * Discussions by band of posts, the largest discussion and the discussions above the limit.
     *
     * @return array bands (label => count), max (posts), large (count).
     */
    public static function compute(): array {
        global $DB;
        $bands = self::bands();
        $limit = (int) get_config('tool_prometheus', 'forumlargelimit');
        $limit = $limit > 0 ? $limit : self::DEFAULT_LARGE_LIMIT;

        $result = ['bands' => array_fill_keys($bands->labels(), 0), 'max' => 0, 'large' => 0];
        $rs = $DB->get_recordset_sql(
            "SELECT discussion, COUNT(1) AS posts
               FROM {forum_posts}
              WHERE deleted = 0
           GROUP BY discussion"
        );
        foreach ($rs as $row) {
            $posts = (int) $row->posts;
            $result['bands'][$bands->label($posts)]++;
            $result['max'] = max($result['max'], $posts);
            if ($posts > $limit) {
                $result['large']++;
            }
        }
        $rs->close();
        return $result;
    }

    /**
     * Bands of posts, from the settings.
     *
     * @return bands
     */
    public static function bands(): bands {
        return bands::from_setting((string) get_config('tool_prometheus', 'forumbands'), self::DEFAULT_BANDS);
    }
}
