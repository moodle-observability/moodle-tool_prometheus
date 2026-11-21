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

namespace tool_prometheus;

use PHPUnit\Framework\Attributes\CoversClass;
use tool_prometheus\local\exporter;
use tool_prometheus\local\metric;

/**
 * Tests of the metric value object and the text/OpenMetrics exporter.
 *
 * @package    tool_prometheus
 * @copyright  2026 Moodle Observability contributors
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversClass(exporter::class)]
#[CoversClass(metric::class)]
final class exporter_test extends \advanced_testcase {
    /**
     * Text format: HELP, TYPE, labels sorted and escaped, integer and float values.
     */
    public function test_render_text(): void {
        $gauge = (new metric('moodle_test_gauge', "Help with \\ and\nline"))
            ->set(3, ['b' => 'x"y', 'a' => "1\n2"])
            ->set(0.25);
        $counter = (new metric('moodle_test_events_total', 'Events.', metric::COUNTER))->set(5.0);

        $output = exporter::render([$gauge, $counter]);

        $this->assertSame(
            "# HELP moodle_test_gauge Help with \\\\ and\\nline\n" .
            "# TYPE moodle_test_gauge gauge\n" .
            "moodle_test_gauge{a=\"1\\n2\",b=\"x\\\"y\"} 3\n" .
            "moodle_test_gauge 0.25\n" .
            "# HELP moodle_test_events_total Events.\n" .
            "# TYPE moodle_test_events_total counter\n" .
            "moodle_test_events_total 5\n",
            $output
        );
    }

    /**
     * OpenMetrics: counter family without _total and the # EOF terminator.
     */
    public function test_render_openmetrics(): void {
        $counter = (new metric('moodle_test_events_total', 'Events.', metric::COUNTER))->set(2);
        $output = exporter::render([$counter], true);
        $this->assertStringContainsString("# TYPE moodle_test_events counter\n", $output);
        $this->assertStringContainsString("moodle_test_events_total 2\n", $output);
        $this->assertStringEndsWith("# EOF\n", $output);
    }

    /**
     * Special float values.
     */
    public function test_special_values(): void {
        $metric = (new metric('moodle_test_special', 'Special.'))
            ->set(NAN, ['v' => 'nan'])
            ->set(INF, ['v' => 'inf'])
            ->set(-INF, ['v' => 'neginf']);
        $output = exporter::render([$metric]);
        $this->assertStringContainsString('moodle_test_special{v="nan"} NaN', $output);
        $this->assertStringContainsString('moodle_test_special{v="inf"} +Inf', $output);
        $this->assertStringContainsString('moodle_test_special{v="neginf"} -Inf', $output);
    }

    /**
     * Content negotiation.
     */
    public function test_wants_openmetrics(): void {
        $this->assertTrue(exporter::wants_openmetrics('application/openmetrics-text;version=1.0.0,text/plain;q=0.5'));
        $this->assertFalse(exporter::wants_openmetrics('text/plain'));
        $this->assertFalse(exporter::wants_openmetrics(''));
        $this->assertStringStartsWith('application/openmetrics-text', exporter::content_type(true));
        $this->assertStringStartsWith('text/plain; version=0.0.4', exporter::content_type(false));
    }

    /**
     * Invalid names, types, labels and too many samples are refused.
     */
    public function test_metric_validation(): void {
        $invalid = [
            fn() => new metric('1bad', 'x'),
            fn() => new metric('moodle_x', 'x', 'histogram'),
            fn() => new metric('moodle_x', 'x', metric::COUNTER),
            fn() => (new metric('moodle_x', 'x'))->set(1, ['bad-label' => 'v']),
            fn() => (new metric('moodle_x', 'x'))->set(1, ['__reserved' => 'v']),
        ];
        foreach ($invalid as $i => $call) {
            try {
                $call();
                $this->fail("Case {$i} should throw");
            } catch (\coding_exception $e) {
                $this->assertInstanceOf(\coding_exception::class, $e);
            }
        }

        $metric = new metric('moodle_x', 'x');
        for ($i = 0; $i < metric::MAX_SAMPLES; $i++) {
            $metric->set($i, ['n' => $i]);
        }
        // Updating an existing sample is still allowed.
        $metric->set(1, ['n' => 0]);
        $this->expectException(\coding_exception::class);
        $metric->set(1, ['n' => 'one too many']);
    }
}
