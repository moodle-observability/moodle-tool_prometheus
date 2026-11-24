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

namespace tool_prometheus\local;

/**
 * Persistent counters ({tool_prometheus_counter}), incremented by event observers and read by the collectors.
 *
 * Values survive cache purges; Prometheus handles resets anyway. Two requests incrementing the same counter at the
 * same instant may lose one increment: acceptable for monitoring, and it avoids raw SQL.
 *
 * @package    tool_prometheus
 * @copyright  2026 Moodle Observability contributors
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class counter {
    /**
     * Adds to a counter, creating it when missing.
     *
     * @param string $name Metric name (ending in _total).
     * @param array $labels Labels.
     * @param int $amount Amount.
     * @return void
     */
    public static function increment(string $name, array $labels, int $amount = 1): void {
        global $DB;
        ksort($labels);
        $key = json_encode(array_map('strval', $labels));
        $now = \core\di::get(\core\clock::class)->time();
        $record = $DB->get_record('tool_prometheus_counter', ['name' => $name, 'labels' => $key]);
        if ($record) {
            $DB->update_record('tool_prometheus_counter', (object) ['id' => $record->id,
                'value' => (int) $record->value + $amount, 'timemodified' => $now]);
            return;
        }
        try {
            $DB->insert_record('tool_prometheus_counter', (object) ['name' => $name, 'labels' => $key,
                'value' => $amount, 'timemodified' => $now]);
        } catch (\dml_write_exception $e) {
            // Created at the same time by another request: add to it.
            self::increment($name, $labels, $amount);
        }
    }

    /**
     * Values of a counter, by labels.
     *
     * @param string $name Metric name.
     * @return array List of [labels, value].
     */
    public static function values(string $name): array {
        global $DB;
        $values = [];
        foreach ($DB->get_records('tool_prometheus_counter', ['name' => $name], 'labels') as $record) {
            $values[] = [json_decode($record->labels, true) ?: [], (int) $record->value];
        }
        return $values;
    }
}
