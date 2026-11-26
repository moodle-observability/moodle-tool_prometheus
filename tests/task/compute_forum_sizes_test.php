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

namespace tool_prometheus\task;

use PHPUnit\Framework\Attributes\CoversClass;
use tool_prometheus\local\collectors\forums;

/**
 * Tests of the forum discussion sizes task and collector.
 *
 * @package    tool_prometheus
 * @copyright  2026 Moodle Observability contributors
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversClass(compute_forum_sizes::class)]
#[CoversClass(forums::class)]
final class compute_forum_sizes_test extends \advanced_testcase {
    /**
     * A discussion with the given number of posts (the first post included).
     *
     * @param \stdClass $forum Forum.
     * @param int $posts Posts.
     * @return \stdClass Discussion.
     */
    private function discussion(\stdClass $forum, int $posts): \stdClass {
        global $USER;
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_forum');
        $discussion = $generator->create_discussion(['course' => $forum->course, 'forum' => $forum->id,
            'userid' => $USER->id]);
        for ($i = 1; $i < $posts; $i++) {
            $generator->create_post(['discussion' => $discussion->id, 'userid' => $USER->id,
                'parent' => $discussion->firstpost]);
        }
        return $discussion;
    }

    /**
     * Discussions are counted by band of posts (deleted posts excluded); large ones are reported.
     */
    public function test_compute_and_collect(): void {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();
        set_config('forumbands', '2,5', 'tool_prometheus');
        set_config('forumlargelimit', 4, 'tool_prometheus');
        $course = $this->getDataGenerator()->create_course();
        $forum = $this->getDataGenerator()->create_module('forum', ['course' => $course->id]);
        $this->discussion($forum, 1);
        $this->discussion($forum, 2);
        $this->discussion($forum, 3);
        $this->discussion($forum, 5);
        $big = $this->discussion($forum, 7);
        // Two deleted posts: the discussion counts 5.
        $ids = array_slice($DB->get_fieldset_select('forum_posts', 'id', 'discussion = ? AND parent > 0', [$big->id]), 0, 2);
        [$insql, $params] = $DB->get_in_or_equal($ids);
        $DB->set_field_select('forum_posts', 'deleted', 1, "id {$insql}", $params);

        $result = compute_forum_sizes::compute();
        $this->assertSame(['0-2' => 2, '2-5' => 3, '>5' => 0], $result['bands']);
        $this->assertSame(5, $result['max']);
        $this->assertSame(2, $result['large']);

        $this->assertSame([], (new forums())->collect());
        ob_start();
        (new compute_forum_sizes())->execute();
        ob_end_clean();
        $values = [];
        foreach ((new forums())->collect() as $metric) {
            foreach ($metric->get_samples() as $sample) {
                $values[$metric->name . json_encode($sample['labels'])] = $sample['value'];
            }
        }
        $this->assertSame(3, $values['moodle_forum_discussions{"posts_band":"2-5"}']);
        $this->assertSame(0, $values['moodle_forum_discussions{"posts_band":">5"}']);
        $this->assertSame(5, $values['moodle_forum_discussion_max_posts[]']);
        $this->assertSame(2, $values['moodle_forum_discussions_large[]']);
        $this->assertGreaterThan(0, $values['moodle_forum_discussions_scan_timestamp_seconds[]']);
    }

    /**
     * Default bands are the same as moodle_ops_exporter.
     */
    public function test_default_bands(): void {
        $this->resetAfterTest();
        $this->assertSame(['0-1000', '1000-5000', '5000-10000', '>10000'], compute_forum_sizes::bands()->labels());
    }
}
