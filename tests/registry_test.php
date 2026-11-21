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
use tool_prometheus\hook\collect_metrics;
use tool_prometheus\local\collector;
use tool_prometheus\local\exporter;
use tool_prometheus\local\metric;
use tool_prometheus\local\registry;

/**
 * Tests of the registry and of the collect_metrics hook.
 *
 * @package    tool_prometheus
 * @copyright  2026 Moodle Observability contributors
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversClass(registry::class)]
#[CoversClass(collect_metrics::class)]
final class registry_test extends \advanced_testcase {
    #[\Override]
    public static function setUpBeforeClass(): void {
        parent::setUpBeforeClass();
        require_once(__DIR__ . '/fixtures/hook_callbacks.php');
    }

    /**
     * Metrics indexed by name.
     *
     * @param metric[] $metrics Metrics.
     * @return array<string, metric>
     */
    private function by_name(array $metrics): array {
        $named = [];
        foreach ($metrics as $metric) {
            $named[$metric->name] = $metric;
        }
        return $named;
    }

    /**
     * Value of the sample with the given labels.
     *
     * @param metric $metric Metric.
     * @param array $labels Labels.
     * @return float|int|null
     */
    private function sample(metric $metric, array $labels = []): float|int|null {
        foreach ($metric->get_samples() as $sample) {
            if ($sample['labels'] == array_map('strval', $labels)) {
                return $sample['value'];
            }
        }
        return null;
    }

    /**
     * Uses a hook manager with only the given callbacks.
     *
     * @param array $callbacks Callback definitions (as in db/hooks.php).
     */
    private function use_callbacks(array $callbacks): void {
        $file = make_request_directory() . '/hooks.php';
        file_put_contents($file, '<?php $callbacks = ' . var_export($callbacks, true) . ';');
        \core\di::set(\core\hook\manager::class, \core\hook\manager::phpunit_get_instance(['local_demo' => $file]));
    }

    /**
     * All built-in collectors succeed on a fresh site and the output is valid.
     */
    public function test_builtin_collectors(): void {
        $this->resetAfterTest();
        $this->use_callbacks([]);
        $metrics = $this->by_name((new registry())->collect());

        $success = $metrics['moodle_scrape_collector_success'];
        foreach (['site', 'users', 'sessions', 'tasks', 'quiz', 'cache', 'disk', 'hook'] as $collector) {
            $this->assertSame(1, $this->sample($success, ['collector' => $collector]), $collector);
        }
        $expected = [
            'moodle_info', 'moodle_maintenance_mode', 'moodle_cron_last_start_timestamp_seconds', 'moodle_users_active',
            'moodle_users', 'moodle_sessions_open', 'moodle_adhoc_tasks_queued', 'moodle_adhoc_task_oldest_age_seconds',
            'moodle_quiz_attempts_in_progress', 'moodle_cache_store_ready',
        ];
        foreach ($expected as $name) {
            $this->assertArrayHasKey($name, $metrics);
        }
        // No disk usage until the scheduled task has run.
        $this->assertArrayNotHasKey('moodle_moodledata_bytes', $metrics);
        $this->assertStringEndsWith("# EOF\n", exporter::render(array_values($metrics), true));
    }

    /**
     * A failing collector only loses its own metrics.
     */
    public function test_failing_collector(): void {
        $this->resetAfterTest();
        $this->use_callbacks([]);
        $broken = new class implements collector {
            #[\Override]
            public function get_name(): string {
                return 'broken';
            }

            #[\Override]
            public function collect(): array {
                throw new \moodle_exception('error');
            }
        };
        $metrics = $this->by_name((new registry([$broken, new local\collectors\site()]))->collect());
        $this->assertDebuggingCalled();
        $this->assertSame(0, $this->sample($metrics['moodle_scrape_collector_success'], ['collector' => 'broken']));
        $this->assertSame(1, $this->sample($metrics['moodle_scrape_collector_success'], ['collector' => 'site']));
        $this->assertArrayHasKey('moodle_info', $metrics);
    }

    /**
     * Valid contributions are exported; invalid ones are dropped with debugging().
     */
    public function test_hook_contributions(): void {
        $this->resetAfterTest();
        $this->use_callbacks([
            ['hook' => collect_metrics::class, 'callback' => [fixtures\hook_callbacks::class, 'contribute']],
        ]);
        $metrics = $this->by_name((new registry([]))->collect());

        $this->assertSame(7, $this->sample($metrics['moodle_local_demo_queue'], ['queue' => 'mail']));
        $this->assertSame(42, $this->sample($metrics['moodle_local_demo_sent_total']));
        $this->assertSame(metric::COUNTER, $metrics['moodle_local_demo_sent_total']->type);
        $this->assertArrayHasKey('moodle_local_demo_queue_total', $metrics);
        $this->assertArrayNotHasKey('moodle_other_thing', $metrics);
        $this->assertArrayNotHasKey('moodle_bad_x', $metrics);
        $this->assertSame(1, $this->sample($metrics['moodle_scrape_collector_success'], ['collector' => 'hook']));
        // Three rejected contributions.
        $this->assertCount(3, $this->getDebuggingMessages());
        $this->resetDebugging();
    }

    /**
     * A throwing callback does not break the response; metrics added before the failure are kept.
     */
    public function test_hook_failure(): void {
        $this->resetAfterTest();
        $this->use_callbacks([
            ['hook' => collect_metrics::class, 'callback' => [fixtures\hook_callbacks::class, 'fail']],
        ]);
        $metrics = $this->by_name((new registry([new local\collectors\site()]))->collect());
        $this->assertDebuggingCalled();
        $this->assertSame(0, $this->sample($metrics['moodle_scrape_collector_success'], ['collector' => 'hook']));
        $this->assertArrayHasKey('moodle_local_broken_before', $metrics);
        $this->assertArrayHasKey('moodle_info', $metrics);
    }
}
