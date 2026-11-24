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
 * Value bands with labels generated from their limits, as moodle_ops_exporter does, so that both label the same way.
 *
 * A band is closed on the right: a value equal to a limit belongs to the band of that limit. With limits 25, 50 and
 * 100 and the suffix "MB", the labels are "0-25MB", "25-50MB", "50-100MB" and ">100MB".
 *
 * @package    tool_prometheus
 * @copyright  2026 Moodle Observability contributors
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class bands {
    /** Most limits accepted (bounds the label cardinality). */
    public const MAX_LIMITS = 10;

    /** @var int[] Limits, ascending. */
    private array $limits;

    /** @var string Suffix of the labels. */
    private string $suffix;

    /**
     * Constructor.
     *
     * @param int[] $limits Limits (positive; duplicates and order do not matter).
     * @param string $suffix Suffix of the labels (for example "MB").
     */
    public function __construct(array $limits, string $suffix = '') {
        $limits = array_values(array_unique(array_filter(array_map('intval', $limits), fn(int $v): bool => $v > 0)));
        sort($limits);
        $this->limits = array_slice($limits, 0, self::MAX_LIMITS);
        $this->suffix = $suffix;
    }

    /**
     * Bands from a comma separated setting, or from the defaults when it has no valid limit.
     *
     * @param string $setting Setting value.
     * @param int[] $defaults Default limits.
     * @param string $suffix Suffix of the labels.
     * @return self
     */
    public static function from_setting(string $setting, array $defaults, string $suffix = ''): self {
        $bands = new self(explode(',', $setting), $suffix);
        return $bands->limits ? $bands : new self($defaults, $suffix);
    }

    /**
     * Labels of every band, in order.
     *
     * @return string[]
     */
    public function labels(): array {
        $labels = [];
        $previous = 0;
        foreach ($this->limits as $limit) {
            $labels[] = $previous . '-' . $limit . $this->suffix;
            $previous = $limit;
        }
        $labels[] = '>' . $previous . $this->suffix;
        return $labels;
    }

    /**
     * Label of the band of a value, in the unit of the limits.
     *
     * @param float $value Value.
     * @return string
     */
    public function label(float $value): string {
        $labels = $this->labels();
        foreach ($this->limits as $i => $limit) {
            if ($value <= $limit) {
                return $labels[$i];
            }
        }
        return end($labels);
    }

    /**
     * Limits, ascending.
     *
     * @return int[]
     */
    public function limits(): array {
        return $this->limits;
    }
}
