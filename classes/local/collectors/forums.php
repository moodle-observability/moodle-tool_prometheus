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
 * Forum discussions by number of posts, as computed by the scheduled task (never during a scrape).
 *
 * Names and labels are the same as moodle_ops_exporter, so that the same dashboards and alerts work with both.
 *
 * @package    tool_prometheus
 * @copyright  2026 Moodle Observability contributors
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class forums implements collector {
    #[\Override]
    public function get_name(): string {
        return 'forums';
    }

    #[\Override]
    public function collect(): array {
        $data = json_decode((string) get_config('tool_prometheus', 'forums'), true);
        if (!is_array($data) || !isset($data['bands'])) {
            return [];
        }
        $discussions = new metric('moodle_forum_discussions', 'Forum discussions by band of number of posts.');
        foreach ($data['bands'] as $band => $count) {
            $discussions->set((int) $count, ['posts_band' => (string) $band]);
        }
        return [
            $discussions,
            (new metric('moodle_forum_discussion_max_posts', 'Posts of the largest forum discussion.'))
                ->set((int) ($data['max'] ?? 0)),
            (new metric('moodle_forum_discussions_large', 'Forum discussions above the large discussion limit.'))
                ->set((int) ($data['large'] ?? 0)),
            (new metric('moodle_forum_discussions_scan_timestamp_seconds', 'Unix time of the last count of the discussions.'))
                ->set((int) get_config('tool_prometheus', 'forums_time')),
        ];
    }
}
