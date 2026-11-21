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

namespace tool_prometheus\fixtures;

use tool_prometheus\hook\collect_metrics;

/**
 * Callbacks of the collect_metrics hook used by the tests.
 *
 * @package    tool_prometheus
 * @copyright  2026 Moodle Observability contributors
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class hook_callbacks {
    /**
     * Valid and invalid contributions.
     *
     * @param collect_metrics $hook Hook.
     */
    public static function contribute(collect_metrics $hook): void {
        $hook->add_gauge('local_demo', 'moodle_local_demo_queue', 'Demo queue size.', 7, ['queue' => 'mail']);
        $hook->add_counter('local_demo', 'moodle_local_demo_sent_total', 'Demo messages sent.', 42);
        // Rejected: wrong prefix, invalid component, type change.
        $hook->add_gauge('local_demo', 'moodle_other_thing', 'Wrong prefix.', 1);
        $hook->add_gauge('Bad Component', 'moodle_bad_x', 'Invalid component.', 1);
        $hook->add_counter('local_demo', 'moodle_local_demo_queue_total', 'ok counter', 1);
        $hook->add_gauge('local_demo', 'moodle_local_demo_sent_total', 'Type change.', 1);
    }

    /**
     * A callback that throws after contributing one metric.
     *
     * @param collect_metrics $hook Hook.
     */
    public static function fail(collect_metrics $hook): void {
        $hook->add_gauge('local_broken', 'moodle_local_broken_before', 'Added before failing.', 1);
        throw new \moodle_exception('error');
    }
}
