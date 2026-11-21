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
 * Quiz attempts in progress.
 *
 * @package    tool_prometheus
 * @copyright  2026 Moodle Observability contributors
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class quiz implements collector {
    #[\Override]
    public function get_name(): string {
        return 'quiz';
    }

    #[\Override]
    public function collect(): array {
        global $DB;
        if (!\core_component::get_component_directory('mod_quiz')) {
            return [];
        }
        $count = $DB->count_records('quiz_attempts', ['state' => 'inprogress', 'preview' => 0]);
        return [(new metric('moodle_quiz_attempts_in_progress', 'Quiz attempts in progress (previews excluded).'))->set($count)];
    }
}
