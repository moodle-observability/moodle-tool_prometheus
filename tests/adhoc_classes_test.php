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
use tool_prometheus\local\collectors\adhoc_classes;

/**
 * Tests of the adhoc tasks by class.
 *
 * @package    tool_prometheus
 * @copyright  2026 Moodle Observability contributors
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversClass(adhoc_classes::class)]
final class adhoc_classes_test extends \advanced_testcase {
    /** Frozen time. */
    private const NOW = 1767225600;

    #[\Override]
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        $this->mock_clock_with_frozen(self::NOW);
        global $DB;
        $DB->delete_records('task_log');
        $DB->delete_records('task_adhoc');
    }

    /**
     * Adds a task log row.
     *
     * @param string $classname Class.
     * @param int $start Seconds before now.
     * @param float $duration Seconds.
     * @param int $result 0 success, 1 failure.
     * @param int $type \core\task\database_logger::TYPE_*.
     * @return void
     */
    private function log(
        string $classname,
        int $start,
        float $duration,
        int $result = 0,
        int $type = \core\task\database_logger::TYPE_ADHOC
    ): void {
        global $DB;
        $DB->insert_record('task_log', [
            'type' => $type, 'component' => 'core', 'classname' => $classname, 'userid' => 0,
            'timestart' => self::NOW - $start, 'timeend' => self::NOW - $start + $duration, 'dbreads' => 0,
            'dbwrites' => 0, 'result' => $result, 'output' => '', 'hostname' => 'test', 'pid' => 1,
        ]);
    }

    /**
     * Adds a queued adhoc task.
     *
     * @param string $classname Class, with the leading backslash as the core stores it.
     * @param int $age Seconds since it was created.
     * @return void
     */
    private function queue(string $classname, int $age): void {
        global $DB;
        $DB->insert_record('task_adhoc', [
            'component' => 'core', 'classname' => $classname, 'nextruntime' => self::NOW, 'faildelay' => 0,
            'customdata' => '{}', 'blocking' => 0, 'timecreated' => self::NOW - $age, 'attemptsavailable' => 12,
        ]);
    }

    /**
     * Runs, failures and duration come from adhoc runs in the window, for the top classes by total duration.
     */
    public function test_from_log(): void {
        $this->log('quiz_statistics\task\recalculate', 100, 600);
        $this->log('quiz_statistics\task\recalculate', 200, 1260, 1);
        $this->log('core\task\send_email', 300, 1.5);
        $this->log('core\task\send_email', 7200, 999);
        $this->log('core\task\old_scheduled', 10, 50, 0, \core\task\database_logger::TYPE_SCHEDULED);
        for ($i = 0; $i < 12; $i++) {
            $this->log("local_test\\task\\t{$i}", 60, 2 + $i);
        }

        $log = adhoc_classes::from_log();
        $this->assertCount(adhoc_classes::TOP, $log);
        $this->assertSame(array_key_first($log), 'quiz_statistics\task\recalculate');
        $this->assertSame(
            ['runs' => 2, 'failures' => 1, 'sum' => 1860.0, 'max' => 1260.0],
            $log['quiz_statistics\task\recalculate']
        );
        $this->assertArrayNotHasKey('core\task\old_scheduled', $log);
        // The send_email task (1.5 s in the window) is not among the 10 longest.
        $this->assertArrayNotHasKey('core\task\send_email', $log);

        set_config('adhocclasswindow', 10000, 'tool_prometheus');
        $this->assertSame(1000.5, adhoc_classes::from_log()['core\task\send_email']['sum']);
    }

    /**
     * The queue is reported for the top classes; the rest is added up in "other".
     */
    public function test_from_queue(): void {
        for ($i = 0; $i < 12; $i++) {
            for ($n = 0; $n <= $i; $n++) {
                $this->queue("\\local_test\\task\\t{$i}", 100 * ($n + 1));
            }
        }
        $queue = adhoc_classes::from_queue();
        $this->assertCount(adhoc_classes::TOP + 1, $queue);
        $this->assertSame(['queued' => 12, 'oldest' => self::NOW - 1200], $queue['local_test\task\t11']);
        $this->assertSame(['queued' => 1 + 2, 'oldest' => self::NOW - 200], $queue[adhoc_classes::OTHER]);
    }

    /**
     * The collector exposes the metrics with the classname label (and stat for the duration).
     */
    public function test_collect(): void {
        $this->log('core\task\send_email', 30, 4, 1);
        $this->queue('\core\task\send_email', 90);
        $values = [];
        foreach ((new adhoc_classes())->collect() as $metric) {
            foreach ($metric->get_samples() as $sample) {
                $values[$metric->name . json_encode($sample['labels'])] = $sample['value'];
            }
        }
        $this->assertSame(1, $values['moodle_adhoc_class_runs{"classname":"core\\\\task\\\\send_email"}']);
        $this->assertSame(1, $values['moodle_adhoc_class_failures{"classname":"core\\\\task\\\\send_email"}']);
        $key = 'moodle_adhoc_class_duration_seconds{"classname":"core\\\\task\\\\send_email","stat":"max"}';
        $this->assertSame(4.0, $values[$key]);
        $this->assertSame(1, $values['moodle_adhoc_class_queued{"classname":"core\\\\task\\\\send_email"}']);
        $this->assertSame(90, $values['moodle_adhoc_class_oldest_age_seconds{"classname":"core\\\\task\\\\send_email"}']);
    }
}
