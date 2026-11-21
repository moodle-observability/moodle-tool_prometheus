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
 * Renders metrics in the Prometheus text format or in OpenMetrics.
 *
 * @package    tool_prometheus
 * @copyright  2026 Moodle Observability contributors
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class exporter {
    /** Content type of the Prometheus text format. */
    public const TEXT_CONTENT_TYPE = 'text/plain; version=0.0.4; charset=utf-8';

    /** Content type of OpenMetrics. */
    public const OPENMETRICS_CONTENT_TYPE = 'application/openmetrics-text; version=1.0.0; charset=utf-8';

    /**
     * Whether the client asked for OpenMetrics (Prometheus does by default).
     *
     * @param string $accept Value of the Accept header.
     * @return bool
     */
    public static function wants_openmetrics(string $accept): bool {
        return str_contains(strtolower($accept), 'application/openmetrics-text');
    }

    /**
     * Content type of the response.
     *
     * @param bool $openmetrics OpenMetrics instead of the text format.
     * @return string
     */
    public static function content_type(bool $openmetrics): string {
        return $openmetrics ? self::OPENMETRICS_CONTENT_TYPE : self::TEXT_CONTENT_TYPE;
    }

    /**
     * Renders the metrics.
     *
     * @param metric[] $metrics Metrics.
     * @param bool $openmetrics OpenMetrics instead of the text format.
     * @return string
     */
    public static function render(array $metrics, bool $openmetrics = false): string {
        $lines = [];
        foreach ($metrics as $metric) {
            // OpenMetrics names the counter family without the _total suffix.
            $family = ($openmetrics && $metric->type === metric::COUNTER)
                ? substr($metric->name, 0, -strlen('_total'))
                : $metric->name;
            $lines[] = '# HELP ' . $family . ' ' . self::escape_help($metric->help);
            $lines[] = '# TYPE ' . $family . ' ' . $metric->type;
            foreach ($metric->get_samples() as $sample) {
                $lines[] = $metric->name . self::labels($sample['labels']) . ' ' . self::value($sample['value']);
            }
        }
        if ($openmetrics) {
            $lines[] = '# EOF';
        }
        return implode("\n", $lines) . "\n";
    }

    /**
     * Label set in braces, or an empty string.
     *
     * @param array $labels Labels.
     * @return string
     */
    private static function labels(array $labels): string {
        if (!$labels) {
            return '';
        }
        $pairs = [];
        foreach ($labels as $name => $value) {
            $pairs[] = $name . '="' . self::escape_label($value) . '"';
        }
        return '{' . implode(',', $pairs) . '}';
    }

    /**
     * Formats a sample value.
     *
     * @param float|int $value Value.
     * @return string
     */
    private static function value(float|int $value): string {
        if (is_int($value)) {
            return (string) $value;
        }
        if (is_nan($value)) {
            return 'NaN';
        }
        if (is_infinite($value)) {
            return $value > 0 ? '+Inf' : '-Inf';
        }
        if (floor($value) == $value && abs($value) < 1e15) {
            return (string) (int) $value;
        }
        return rtrim(rtrim(sprintf('%.6F', $value), '0'), '.');
    }

    /**
     * Escapes a label value (backslash, double quote and line feed).
     *
     * @param string $value Value.
     * @return string
     */
    private static function escape_label(string $value): string {
        return str_replace(['\\', '"', "\n"], ['\\\\', '\\"', '\\n'], $value);
    }

    /**
     * Escapes a help text (backslash and line feed).
     *
     * @param string $help Help text.
     * @return string
     */
    private static function escape_help(string $help): string {
        return str_replace(['\\', "\n"], ['\\\\', '\\n'], $help);
    }
}
