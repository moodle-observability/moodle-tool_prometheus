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
use tool_prometheus\local\collectors\large_files;

/**
 * Tests of the large files task and collector.
 *
 * @package    tool_prometheus
 * @copyright  2026 Moodle Observability contributors
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversClass(compute_large_files::class)]
#[CoversClass(large_files::class)]
final class compute_large_files_test extends \advanced_testcase {
    /** One MiB. */
    private const MB = 1048576;

    /** @var int Counter of file names. */
    private int $n = 0;

    /**
     * A file record with the given size (no real content is written).
     *
     * @param string $component Component.
     * @param int $size Size, in bytes.
     * @param string $content Content (same content, same contenthash).
     * @return void
     */
    private function file(string $component, int $size, string $content = ''): void {
        global $DB;
        $this->n++;
        $file = get_file_storage()->create_file_from_string(['contextid' => \context_system::instance()->id,
            'component' => $component, 'filearea' => 'content', 'itemid' => 0, 'filepath' => '/',
            'filename' => "f{$this->n}.pdf"], $content !== '' ? $content : "x{$this->n}");
        $DB->set_field('files', 'filesize', $size, ['id' => $file->get_id()]);
    }

    /**
     * Files above the floor are counted by component and band, each content once per component.
     */
    public function test_compute_and_collect(): void {
        $this->resetAfterTest();
        $this->file('mod_resource', 10 * self::MB);
        $this->file('mod_resource', 50 * self::MB);
        $this->file('mod_resource', 60 * self::MB);
        $this->file('mod_resource', 362 * self::MB, 'same');
        $this->file('mod_resource', 362 * self::MB, 'same');
        $this->file('mod_page', 180 * self::MB);
        $this->file('mod_folder', 70 * self::MB, 'shared');
        $this->file('mod_page', 70 * self::MB, 'shared');
        $this->file('mod_assign', 500 * self::MB);

        $this->assertSame([
            'mod_page' => ['50-100MB' => 1, '>100MB' => 1],
            'mod_resource' => ['50-100MB' => 1, '>100MB' => 1],
            'mod_folder' => ['50-100MB' => 1, '>100MB' => 0],
        ], compute_large_files::compute());

        $this->assertSame([], (new large_files())->collect());
        ob_start();
        (new compute_large_files())->execute();
        ob_end_clean();
        $values = [];
        foreach ((new large_files())->collect() as $metric) {
            foreach ($metric->get_samples() as $sample) {
                $values[$metric->name . json_encode($sample['labels'])] = $sample['value'];
            }
        }
        $this->assertSame(1, $values['moodle_files_large{"component":"mod_resource","size_band":">100MB"}']);
        $this->assertSame(0, $values['moodle_files_large{"component":"mod_folder","size_band":">100MB"}']);
        $this->assertCount(6 + 1, $values);
    }

    /**
     * Components and bands come from the settings; invalid values fall back to the defaults.
     */
    public function test_settings(): void {
        $this->resetAfterTest();
        $this->file('mod_assign', 500 * self::MB);
        set_config('largefilecomponents', 'mod_assign, Bad-Name', 'tool_prometheus');
        set_config('largefilebands', '200', 'tool_prometheus');
        $this->assertSame(['mod_assign' => ['>200MB' => 1]], compute_large_files::compute());
        set_config('largefilecomponents', '!!', 'tool_prometheus');
        $this->assertSame(compute_large_files::DEFAULT_COMPONENTS, compute_large_files::components());
    }
}
