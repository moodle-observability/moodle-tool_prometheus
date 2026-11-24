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

use tool_prometheus\hook\collect_metrics;

/**
 * Runs the collectors and the collect_metrics hook, isolating failures.
 *
 * @package    tool_prometheus
 * @copyright  2026 Moodle Observability contributors
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class registry {
    /** @var collector[]|null Collectors; null uses the built-in ones. */
    private ?array $collectors;

    /**
     * Constructor.
     *
     * @param collector[]|null $collectors Collectors; null uses the built-in ones.
     */
    public function __construct(?array $collectors = null) {
        $this->collectors = $collectors;
    }

    /**
     * Built-in collectors.
     *
     * @return collector[]
     */
    public static function builtin(): array {
        return [
            new collectors\site(),
            new collectors\users(),
            new collectors\sessions(),
            new collectors\tasks(),
            new collectors\quiz(),
            new collectors\cache(),
            new collectors\disk(),
            new collectors\folders(),
        ];
    }

    /**
     * Collects every metric. A failing collector or hook callback only loses its own metrics.
     *
     * @return metric[]
     */
    public function collect(): array {
        $metrics = [];
        $success = new metric('moodle_scrape_collector_success', 'Whether a collector succeeded (1) or failed (0).');
        $duration = new metric('moodle_scrape_collector_duration_seconds', 'Time spent by a collector, in seconds.');

        foreach ($this->collectors ?? self::builtin() as $collector) {
            $start = microtime(true);
            $ok = 1;
            try {
                foreach ($collector->collect() as $metric) {
                    $metrics[$metric->name] = $metric;
                }
            } catch (\Throwable $e) {
                $ok = 0;
                debugging('tool_prometheus: collector ' . $collector->get_name() . ' failed: ' . $e->getMessage(), DEBUG_DEVELOPER);
            }
            $success->set($ok, ['collector' => $collector->get_name()]);
            $duration->set(round(microtime(true) - $start, 6), ['collector' => $collector->get_name()]);
        }

        $start = microtime(true);
        $ok = 1;
        $hook = new collect_metrics();
        try {
            \core\di::get(\core\hook\manager::class)->dispatch($hook);
        } catch (\Throwable $e) {
            $ok = 0;
            debugging('tool_prometheus: a collect_metrics callback failed: ' . $e->getMessage(), DEBUG_DEVELOPER);
        }
        foreach ($hook->get_errors() as $error) {
            debugging('tool_prometheus: metric rejected: ' . $error, DEBUG_DEVELOPER);
        }
        foreach ($hook->get_metrics() as $metric) {
            if (isset($metrics[$metric->name])) {
                debugging('tool_prometheus: metric rejected: ' . $metric->name . ' is a built-in metric', DEBUG_DEVELOPER);
                continue;
            }
            $metrics[$metric->name] = $metric;
        }
        $success->set($ok, ['collector' => 'hook']);
        $duration->set(round(microtime(true) - $start, 6), ['collector' => 'hook']);

        $metrics[$success->name] = $success;
        $metrics[$duration->name] = $duration;
        return array_values($metrics);
    }
}
