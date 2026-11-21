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
 * Open sessions, counted through the configured session handler (database, Redis, memcached...).
 *
 * The counts are kept for one minute in the application cache: listing sessions can be expensive.
 *
 * @package    tool_prometheus
 * @copyright  2026 Moodle Observability contributors
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class sessions implements collector {
    #[\Override]
    public function get_name(): string {
        return 'sessions';
    }

    #[\Override]
    public function collect(): array {
        $cache = \cache::make('tool_prometheus', 'metrics');
        $counts = $cache->get('sessions');
        if (!is_array($counts)) {
            $counts = $this->count();
            $cache->set('sessions', $counts);
        }
        return [
            (new metric('moodle_sessions_open', 'Open sessions in the session handler.'))->set($counts['total']),
            (new metric('moodle_sessions_authenticated', 'Open sessions of logged in users (guests excluded).'))
                ->set($counts['authenticated']),
        ];
    }

    /**
     * Counts the sessions of the configured handler.
     *
     * @return array{total: int, authenticated: int}
     */
    private function count(): array {
        global $CFG;
        $class = \core\session\manager::get_handler_class();
        $handler = new $class();
        $handler->init();
        $guest = (int) ($CFG->siteguest ?? 0);
        $total = 0;
        $authenticated = 0;
        foreach ($handler->get_all_sessions() as $session) {
            $total++;
            $userid = (int) ($session->userid ?? 0);
            if ($userid > 0 && $userid !== $guest) {
                $authenticated++;
            }
        }
        return ['total' => $total, 'authenticated' => $authenticated];
    }
}
