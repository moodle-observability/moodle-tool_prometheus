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
use tool_prometheus\local\collectors;
use tool_prometheus\local\metric;

/**
 * Tests of the built-in collectors.
 *
 * @package    tool_prometheus
 * @copyright  2026 Moodle Observability contributors
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversClass(collectors\site::class)]
#[CoversClass(collectors\users::class)]
#[CoversClass(collectors\sessions::class)]
#[CoversClass(collectors\tasks::class)]
#[CoversClass(collectors\quiz::class)]
#[CoversClass(collectors\cache::class)]
#[CoversClass(collectors\disk::class)]
final class collectors_test extends \advanced_testcase {
    /**
     * Samples of the metrics, keyed by name and then by the JSON of the labels.
     *
     * @param metric[] $metrics Metrics.
     * @return array<string, array<string, float|int>>
     */
    private function values(array $metrics): array {
        $values = [];
        foreach ($metrics as $metric) {
            foreach ($metric->get_samples() as $sample) {
                $values[$metric->name][json_encode($sample['labels'])] = $sample['value'];
            }
        }
        return $values;
    }

    /**
     * Active users per window and total users.
     */
    public function test_users(): void {
        global $DB;
        $this->resetAfterTest();
        set_config('activewindows', '3600,300,abc,300,-5', 'tool_prometheus');
        $this->assertSame([300, 3600], collectors\users::windows());

        $now = time();
        $recent = $this->getDataGenerator()->create_user();
        $older = $this->getDataGenerator()->create_user();
        $deleted = $this->getDataGenerator()->create_user();
        $DB->set_field('user', 'lastaccess', $now - 60, ['id' => $recent->id]);
        $DB->set_field('user', 'lastaccess', $now - 1800, ['id' => $older->id]);
        $DB->set_field('user', 'lastaccess', $now - 60, ['id' => $deleted->id]);
        delete_user($deleted);

        $values = $this->values((new collectors\users())->collect());
        $this->assertSame(1, $values['moodle_users_active']['{"window":"300"}']);
        $this->assertSame(2, $values['moodle_users_active']['{"window":"3600"}']);
        // Guest, admin and the two users that are not deleted.
        $this->assertSame(4, $values['moodle_users']['[]']);
    }

    /**
     * Adhoc task queue: queued, due, running, failing and oldest age.
     */
    public function test_tasks(): void {
        global $DB;
        $this->resetAfterTest();
        $DB->delete_records('task_adhoc');
        $now = time();
        $base = ['component' => 'core', 'classname' => '\\core\\task\\refresh_mod_calendar_events_task', 'customdata' => '',
            'blocking' => 0, 'faildelay' => 0];
        $DB->insert_record('task_adhoc', $base + ['nextruntime' => $now - 10, 'timecreated' => $now - 600]);
        $DB->insert_record('task_adhoc', $base + ['nextruntime' => $now + 600, 'timecreated' => $now - 60]);
        $DB->insert_record('task_adhoc', ['faildelay' => 120] + $base + ['nextruntime' => $now + 120, 'timecreated' => $now]);
        $DB->insert_record('task_adhoc', $base + ['nextruntime' => $now - 10, 'timecreated' => $now - 7200,
            'timestarted' => $now - 30, 'hostname' => 'test', 'pid' => 1]);

        $values = $this->values((new collectors\tasks())->collect());
        $this->assertSame(3, $values['moodle_adhoc_tasks_queued']['[]']);
        $this->assertSame(1, $values['moodle_adhoc_tasks_due']['[]']);
        $this->assertSame(1, $values['moodle_adhoc_tasks_running']['[]']);
        $this->assertSame(1, $values['moodle_adhoc_tasks_failing']['[]']);
        $this->assertGreaterThanOrEqual(600, $values['moodle_adhoc_task_oldest_age_seconds']['[]']);
        $this->assertLessThan(700, $values['moodle_adhoc_task_oldest_age_seconds']['[]']);
        $this->assertSame(0, $values['moodle_scheduled_tasks_failing']['[]']);
    }

    /**
     * Quiz attempts in progress, previews excluded.
     */
    public function test_quiz(): void {
        global $DB;
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $quiz = $this->getDataGenerator()->create_module('quiz', ['course' => $course->id]);
        $base = ['quiz' => $quiz->id, 'attempt' => 1, 'uniqueid' => 0, 'layout' => '', 'currentpage' => 0,
            'sumgrades' => null, 'timestart' => time(), 'timefinish' => 0, 'timemodified' => time()];
        foreach ([['inprogress', 0], ['inprogress', 0], ['inprogress', 1], ['finished', 0]] as $i => [$state, $preview]) {
            $user = $this->getDataGenerator()->create_user();
            $DB->insert_record('quiz_attempts', ['userid' => $user->id, 'state' => $state, 'preview' => $preview,
                'uniqueid' => 1000 + $i] + $base);
        }
        $values = $this->values((new collectors\quiz())->collect());
        $this->assertSame(2, $values['moodle_quiz_attempts_in_progress']['[]']);
    }

    /**
     * Sessions are counted through the handler and cached for one minute.
     */
    public function test_sessions(): void {
        global $DB;
        $this->resetAfterTest();
        \cache::make('tool_prometheus', 'metrics')->purge();
        $DB->delete_records('sessions');
        $user = $this->getDataGenerator()->create_user();
        $now = time();
        foreach ([$user->id, 0] as $i => $userid) {
            $DB->insert_record('sessions', ['state' => 0, 'sid' => 'sid' . $i, 'userid' => $userid, 'sessdata' => null,
                'timecreated' => $now, 'timemodified' => $now, 'firstip' => '127.0.0.1', 'lastip' => '127.0.0.1']);
        }
        $values = $this->values((new collectors\sessions())->collect());
        $this->assertSame(2, $values['moodle_sessions_open']['[]']);
        $this->assertSame(1, $values['moodle_sessions_authenticated']['[]']);

        // Cached: a new session is only counted after the cache expires.
        $DB->insert_record('sessions', ['state' => 0, 'sid' => 'sid9', 'userid' => $user->id, 'sessdata' => null,
            'timecreated' => $now, 'timemodified' => $now, 'firstip' => '127.0.0.1', 'lastip' => '127.0.0.1']);
        $values = $this->values((new collectors\sessions())->collect());
        $this->assertSame(2, $values['moodle_sessions_open']['[]']);
    }

    /**
     * Site information, cache stores and disk usage (from the stored scan).
     */
    public function test_site_cache_and_disk(): void {
        global $CFG;
        $this->resetAfterTest();
        $values = $this->values((new collectors\site())->collect());
        $info = array_key_first($values['moodle_info']);
        $this->assertStringContainsString('"version":"' . $CFG->version . '"', $info);
        $this->assertSame(0, $values['moodle_maintenance_mode']['[]']);

        $values = $this->values((new collectors\cache())->collect());
        $this->assertNotEmpty($values['moodle_cache_store_ready']);

        $this->assertSame([], (new collectors\disk())->collect());
        set_config('diskusage', json_encode(['filedir' => 100, '_files' => 5]), 'tool_prometheus');
        set_config('diskusage_time', 1700000000, 'tool_prometheus');
        set_config('diskusage_duration', 0.5, 'tool_prometheus');
        $values = $this->values((new collectors\disk())->collect());
        $this->assertSame(100, $values['moodle_moodledata_bytes']['{"dir":"filedir"}']);
        $this->assertSame(1700000000, $values['moodle_moodledata_scan_timestamp_seconds']['[]']);
        $this->assertSame(0.5, $values['moodle_moodledata_scan_duration_seconds']['[]']);
    }
}
