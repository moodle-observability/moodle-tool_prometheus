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

namespace tool_prometheus\hook;

use tool_prometheus\local\metric;

/**
 * Lets other plugins contribute metrics to the /metrics endpoint.
 *
 * Rules for callbacks: names start with "moodle_<component>_", at most metric::MAX_SAMPLES label combinations per
 * metric, no personal data in labels, no side effects and no expensive work (use a cache or a scheduled task).
 * Invalid metrics are dropped and reported with debugging(); they never break the response.
 *
 * @package    tool_prometheus
 * @copyright  2026 Moodle Observability contributors
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\core\attribute\label('Allows plugins to contribute metrics to the Prometheus /metrics endpoint.')]
#[\core\attribute\tags('metrics')]
final class collect_metrics {
    /** @var metric[] Contributed metrics keyed by name. */
    private array $metrics = [];

    /** @var string[] Rejected contributions. */
    private array $errors = [];

    /**
     * Adds a gauge sample.
     *
     * @param string $component Frankenstyle name of the contributing plugin.
     * @param string $name Metric name, starting with "moodle_<component>_".
     * @param string $help Help text.
     * @param float|int $value Value.
     * @param array $labels Labels.
     * @return void
     */
    public function add_gauge(string $component, string $name, string $help, float|int $value, array $labels = []): void {
        $this->add($component, $name, $help, metric::GAUGE, $value, $labels);
    }

    /**
     * Adds a counter sample (the name must end in "_total").
     *
     * @param string $component Frankenstyle name of the contributing plugin.
     * @param string $name Metric name, starting with "moodle_<component>_" and ending in "_total".
     * @param string $help Help text.
     * @param float|int $value Value.
     * @param array $labels Labels.
     * @return void
     */
    public function add_counter(string $component, string $name, string $help, float|int $value, array $labels = []): void {
        $this->add($component, $name, $help, metric::COUNTER, $value, $labels);
    }

    /**
     * Contributed metrics.
     *
     * @return metric[]
     */
    public function get_metrics(): array {
        return array_values($this->metrics);
    }

    /**
     * Messages of the rejected contributions.
     *
     * @return string[]
     */
    public function get_errors(): array {
        return $this->errors;
    }

    /**
     * Validates and stores one sample.
     *
     * @param string $component Component.
     * @param string $name Metric name.
     * @param string $help Help text.
     * @param string $type Metric type.
     * @param float|int $value Value.
     * @param array $labels Labels.
     * @return void
     */
    private function add(string $component, string $name, string $help, string $type, float|int $value, array $labels): void {
        try {
            $clean = clean_param($component, PARAM_COMPONENT);
            if ($clean === '' || $clean !== $component || !str_contains($component, '_')) {
                throw new \coding_exception("Invalid component: {$component}");
            }
            if (!str_starts_with($name, "moodle_{$component}_")) {
                throw new \coding_exception("Metric {$name} must start with moodle_{$component}_");
            }
            if (isset($this->metrics[$name]) && $this->metrics[$name]->type !== $type) {
                throw new \coding_exception("Metric {$name} was already added with another type");
            }
            $metric = $this->metrics[$name] ?? new metric($name, $help, $type);
            $metric->set($value, $labels);
            $this->metrics[$name] = $metric;
        } catch (\coding_exception $e) {
            $this->errors[] = $e->getMessage();
        }
    }
}
