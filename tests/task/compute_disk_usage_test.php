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

/**
 * Tests of the disk usage task.
 *
 * @package    tool_prometheus
 * @copyright  2026 Moodle Observability contributors
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversClass(compute_disk_usage::class)]
final class compute_disk_usage_test extends \advanced_testcase {
    /**
     * scan() sums each top level folder recursively and counts loose files.
     */
    public function test_scan(): void {
        $root = make_request_directory();
        mkdir("{$root}/a/b", 0777, true);
        mkdir("{$root}/empty");
        file_put_contents("{$root}/a/one.txt", str_repeat('x', 10));
        file_put_contents("{$root}/a/b/two.txt", str_repeat('x', 20));
        file_put_contents("{$root}/loose.txt", str_repeat('x', 5));

        $listing = fn(): array => iterator_to_array(new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        ));
        $before = array_keys($listing());

        $this->assertSame(['_files' => 5, 'a' => 30, 'empty' => 0], compute_disk_usage::scan($root));
        // Read only: nothing created or removed.
        $this->assertSame($before, array_keys($listing()));
    }

    /**
     * execute() stores the scan in the plugin configuration.
     */
    public function test_execute(): void {
        $this->resetAfterTest();

        ob_start();
        (new compute_disk_usage())->execute();
        $output = ob_get_clean();

        $usage = json_decode(get_config('tool_prometheus', 'diskusage'), true);
        $this->assertIsArray($usage);
        $this->assertArrayHasKey('_files', $usage);
        $this->assertGreaterThan(time() - 60, (int) get_config('tool_prometheus', 'diskusage_time'));
        $this->assertStringContainsString('moodledata:', $output);
    }
}
