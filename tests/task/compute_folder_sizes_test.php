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
use tool_prometheus\local\bands;
use tool_prometheus\local\collectors\folders;

/**
 * Tests of the folder sizes task, of the bands and of the folders collector.
 *
 * @package    tool_prometheus
 * @copyright  2026 Moodle Observability contributors
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversClass(compute_folder_sizes::class)]
#[CoversClass(bands::class)]
#[CoversClass(folders::class)]
final class compute_folder_sizes_test extends \advanced_testcase {
    /** One MiB. */
    private const MB = 1048576;

    /**
     * A folder whose files add up to the given size (file records are resized, no real content is written).
     *
     * @param \stdClass $course Course.
     * @param int[] $sizes File sizes, in bytes.
     * @param bool $download Whether the "Download folder" button is enabled.
     * @return \stdClass Folder module.
     */
    private function folder(\stdClass $course, array $sizes, bool $download): \stdClass {
        global $DB;
        $folder = $this->getDataGenerator()->create_module('folder', ['course' => $course->id,
            'showdownloadfolder' => $download ? 1 : 0]);
        $context = \context_module::instance($folder->cmid);
        $fs = get_file_storage();
        foreach ($sizes as $i => $size) {
            $file = $fs->create_file_from_string(['contextid' => $context->id, 'component' => 'mod_folder',
                'filearea' => 'content', 'itemid' => 0, 'filepath' => '/', 'filename' => "f{$i}.bin"], 'x');
            $DB->set_field('files', 'filesize', $size, ['id' => $file->get_id()]);
        }
        return $folder;
    }

    /**
     * Labels come from the limits; a value equal to a limit belongs to the band of that limit.
     */
    public function test_bands(): void {
        $bands = new bands([100, 25, 50, 25], 'MB');
        $this->assertSame(['0-25MB', '25-50MB', '50-100MB', '>100MB'], $bands->labels());
        $this->assertSame('0-25MB', $bands->label(0));
        $this->assertSame('0-25MB', $bands->label(25));
        $this->assertSame('25-50MB', $bands->label(25.0001));
        $this->assertSame('>100MB', $bands->label(2883));
        $this->assertSame(
            ['0-1000', '1000-5000', '5000-10000', '>10000'],
            (new bands([1000, 5000, 10000]))->labels()
        );
        $this->assertSame([25, 50, 100], bands::from_setting('x,-1', [25, 50, 100])->limits());
        $this->assertSame([10], bands::from_setting(' 10 ', [25])->limits());
    }

    /**
     * Folders are counted by band and button; the largest folder and the large ones with the button are reported.
     */
    public function test_compute(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $this->folder($course, [], true);
        $this->folder($course, [25 * self::MB], true);
        $this->folder($course, [20 * self::MB, 20 * self::MB], true);
        $this->folder($course, [60 * self::MB], false);
        $this->folder($course, [80 * self::MB], true);
        $this->folder($course, [300 * self::MB], false);
        $deleted = $this->folder($course, [900 * self::MB], true);
        // Being deleted (recycle bin or asynchronous deletion): not counted.
        $GLOBALS['DB']->set_field('course_modules', 'deletioninprogress', 1, ['id' => $deleted->cmid]);

        $result = compute_folder_sizes::compute();
        $this->assertSame([
            '0-25MB' => ['0' => 0, '1' => 2],
            '25-50MB' => ['0' => 0, '1' => 1],
            '50-100MB' => ['0' => 1, '1' => 1],
            '>100MB' => ['0' => 1, '1' => 0],
        ], $result['bands']);
        $this->assertSame(300 * self::MB, $result['max']);
        $this->assertSame(1, $result['largeenabled']);

        set_config('folderdownloadlimit', 30, 'tool_prometheus');
        $this->assertSame(2, compute_folder_sizes::compute()['largeenabled']);
        set_config('folderbands', '50', 'tool_prometheus');
        $this->assertSame(['0-50MB', '>50MB'], array_keys(compute_folder_sizes::compute()['bands']));
    }

    /**
     * The task stores the result; the collector exposes it (nothing before the first run).
     */
    public function test_task_and_collector(): void {
        $this->resetAfterTest();
        $this->assertSame([], (new folders())->collect());
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $this->folder($course, [60 * self::MB], true);

        ob_start();
        (new compute_folder_sizes())->execute();
        ob_end_clean();

        $values = [];
        foreach ((new folders())->collect() as $metric) {
            foreach ($metric->get_samples() as $sample) {
                $values[$metric->name . json_encode($sample['labels'])] = $sample['value'];
            }
        }
        $this->assertSame(1, $values['moodle_folders{"download_enabled":"1","size_band":"50-100MB"}']);
        $this->assertSame(0, $values['moodle_folders{"download_enabled":"0","size_band":"0-25MB"}']);
        $this->assertCount(8 + 3, $values);
        $this->assertSame(60 * self::MB, $values['moodle_folder_max_bytes[]']);
        $this->assertSame(1, $values['moodle_folders_large_download_enabled[]']);
        $this->assertGreaterThan(0, $values['moodle_folders_scan_timestamp_seconds[]']);
    }
}
