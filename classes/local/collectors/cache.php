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
 * Readiness of the cache (MUC) store instances.
 *
 * Moodle keeps cache hit and miss statistics only for the current request (perfdebug), so a hit ratio per
 * store cannot be exported across requests; this collector reports whether each configured store is ready.
 *
 * @package    tool_prometheus
 * @copyright  2026 Moodle Observability contributors
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class cache implements collector {
    #[\Override]
    public function get_name(): string {
        return 'cache';
    }

    #[\Override]
    public function collect(): array {
        $ready = new metric('moodle_cache_store_ready', 'Whether a configured cache store instance is ready (1) or not (0).');
        foreach (\core_cache\administration_helper::get_store_instance_summaries() as $name => $store) {
            $value = (!empty($store['isready']) && !empty($store['requirementsmet'])) ? 1 : 0;
            $ready->set($value, ['store' => (string) $name, 'plugin' => (string) $store['plugin']]);
        }
        return [$ready];
    }
}
