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
 * A metric family (one name, one type) with its samples.
 *
 * @package    tool_prometheus
 * @copyright  2026 Moodle Observability contributors
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class metric {
    /** Gauge type. */
    public const GAUGE = 'gauge';

    /** Counter type (the name must end in _total). */
    public const COUNTER = 'counter';

    /** Valid metric name (Prometheus data model, without colons). */
    public const NAME_PATTERN = '/^[a-zA-Z_][a-zA-Z0-9_]*$/';

    /** Valid label name; names starting with "__" are reserved. */
    public const LABEL_PATTERN = '/^[a-zA-Z_][a-zA-Z0-9_]*$/';

    /** Maximum number of samples (label combinations) of one metric. */
    public const MAX_SAMPLES = 100;

    /** @var string Metric name. */
    public readonly string $name;

    /** @var string Help text. */
    public readonly string $help;

    /** @var string Type (metric::GAUGE or metric::COUNTER). */
    public readonly string $type;

    /** @var array<string, array{labels: array<string, string>, value: float|int}> Samples keyed by their labels. */
    private array $samples = [];

    /**
     * Constructor.
     *
     * @param string $name Metric name.
     * @param string $help Help text.
     * @param string $type metric::GAUGE or metric::COUNTER.
     * @throws \coding_exception If the name or the type is invalid.
     */
    public function __construct(string $name, string $help, string $type = self::GAUGE) {
        $this->name = $name;
        $this->help = $help;
        $this->type = $type;
        if (!preg_match(self::NAME_PATTERN, $name)) {
            throw new \coding_exception("Invalid metric name: {$name}");
        }
        if (!in_array($type, [self::GAUGE, self::COUNTER], true)) {
            throw new \coding_exception("Invalid metric type: {$type}");
        }
        if ($type === self::COUNTER && !str_ends_with($name, '_total')) {
            throw new \coding_exception("Counter names must end in _total: {$name}");
        }
    }

    /**
     * Sets the value of one sample.
     *
     * @param float|int $value Value.
     * @param array $labels Label names and values.
     * @return self
     * @throws \coding_exception If a label is invalid or the metric has too many samples.
     */
    public function set(float|int $value, array $labels = []): self {
        $clean = [];
        foreach ($labels as $label => $labelvalue) {
            $label = (string) $label;
            if (!preg_match(self::LABEL_PATTERN, $label) || str_starts_with($label, '__')) {
                throw new \coding_exception("Invalid label name in {$this->name}: {$label}");
            }
            $clean[$label] = (string) $labelvalue;
        }
        ksort($clean);
        $key = json_encode($clean);
        if (!isset($this->samples[$key]) && count($this->samples) >= self::MAX_SAMPLES) {
            throw new \coding_exception("Too many samples in {$this->name} (maximum " . self::MAX_SAMPLES . ')');
        }
        $this->samples[$key] = ['labels' => $clean, 'value' => $value];
        return $this;
    }

    /**
     * Samples of this metric.
     *
     * @return array<int, array{labels: array<string, string>, value: float|int}>
     */
    public function get_samples(): array {
        return array_values($this->samples);
    }
}
