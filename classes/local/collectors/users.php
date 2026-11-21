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
 * Active users per time window.
 *
 * @package    tool_prometheus
 * @copyright  2026 Moodle Observability contributors
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class users implements collector {
    #[\Override]
    public function get_name(): string {
        return 'users';
    }

    /**
     * Configured windows, in seconds (positive, unique, sorted, at most ten).
     *
     * @return int[]
     */
    public static function windows(): array {
        $config = (string) get_config('tool_prometheus', 'activewindows');
        $values = array_map('intval', explode(',', $config !== '' ? $config : '300,3600,86400'));
        $windows = array_values(array_unique(array_filter($values, fn(int $window): bool => $window > 0)));
        sort($windows);
        return array_slice($windows, 0, 10);
    }

    #[\Override]
    public function collect(): array {
        global $DB;
        $now = time();
        $active = new metric('moodle_users_active', 'Users whose last access is within the window (window label in seconds).');
        foreach (self::windows() as $window) {
            $count = $DB->count_records_select('user', 'deleted = 0 AND lastaccess >= :since', ['since' => $now - $window]);
            $active->set($count, ['window' => $window]);
        }
        $total = (new metric('moodle_users', 'Users that are not deleted (suspended included).'))
            ->set($DB->count_records('user', ['deleted' => 0]));
        return [$active, $total];
    }
}
