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
use PHPUnit\Framework\Attributes\DataProvider;
use tool_prometheus\local\auth;

/**
 * Tests of the endpoint authentication.
 *
 * @package    tool_prometheus
 * @copyright  2026 Moodle Observability contributors
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversClass(auth::class)]
final class auth_test extends \advanced_testcase {
    /**
     * Cases of auth::check().
     *
     * @return array
     */
    public static function check_provider(): array {
        return [
            'not configured' => ['', 'abc', '', '10.0.0.1', auth::NOT_CONFIGURED],
            'blank configured' => ['  ', 'abc', '', '10.0.0.1', auth::NOT_CONFIGURED],
            'missing token' => ['secret', null, '', '10.0.0.1', auth::UNAUTHORIZED],
            'empty token' => ['secret', '', '', '10.0.0.1', auth::UNAUTHORIZED],
            'wrong token' => ['secret', 'Secret', '', '10.0.0.1', auth::UNAUTHORIZED],
            'valid token' => ['secret', 'secret', '', '10.0.0.1', auth::OK],
            'configured with spaces' => [" secret\n", 'secret', '', '10.0.0.1', auth::OK],
            'allowed ip' => ['secret', 'secret', "10.0.0.0/8\n192.168.1.5", '10.1.2.3', auth::OK],
            'allowed exact ip' => ['secret', 'secret', "10.0.0.0/8\n192.168.1.5", '192.168.1.5', auth::OK],
            'denied ip' => ['secret', 'secret', '10.0.0.0/8', '172.16.0.1', auth::FORBIDDEN],
            'denied ip before token' => ['secret', 'wrong', '10.0.0.0/8', '172.16.0.1', auth::FORBIDDEN],
            'unknown ip with allow list' => ['secret', 'secret', '10.0.0.0/8', '', auth::FORBIDDEN],
        ];
    }

    /**
     * auth::check() decisions.
     *
     * @param string $configured Configured token.
     * @param string|null $presented Presented token.
     * @param string $allowedips Allow list.
     * @param string $remoteip Client address.
     * @param int $expected Expected status.
     */
    #[DataProvider('check_provider')]
    public function test_check(string $configured, ?string $presented, string $allowedips, string $remoteip, int $expected): void {
        $this->assertSame($expected, auth::check($configured, $presented, $allowedips, $remoteip));
    }

    /**
     * Bearer token extraction; the query string is never used.
     */
    public function test_bearer_token(): void {
        $this->assertSame('abc', auth::bearer_token(['HTTP_AUTHORIZATION' => 'Bearer abc']));
        $this->assertSame('abc', auth::bearer_token(['HTTP_AUTHORIZATION' => '  bearer   abc ']));
        $this->assertSame('abc', auth::bearer_token(['REDIRECT_HTTP_AUTHORIZATION' => 'Bearer abc']));
        $this->assertNull(auth::bearer_token(['HTTP_AUTHORIZATION' => 'Basic YWJj']));
        $this->assertNull(auth::bearer_token(['HTTP_AUTHORIZATION' => 'Bearer']));
        $this->assertNull(auth::bearer_token(['QUERY_STRING' => 'token=abc']));
    }

    /**
     * check_request() reads the plugin configuration (including a forced token).
     */
    public function test_check_request(): void {
        global $CFG;
        $this->resetAfterTest();
        $this->assertSame(auth::NOT_CONFIGURED, auth::check_request(['HTTP_AUTHORIZATION' => 'Bearer abc']));

        set_config('token', 'abc', 'tool_prometheus');
        $this->assertSame(auth::OK, auth::check_request(['HTTP_AUTHORIZATION' => 'Bearer abc']));
        $this->assertSame(auth::UNAUTHORIZED, auth::check_request([]));

        $CFG->forced_plugin_settings['tool_prometheus']['token'] = 'forced';
        $this->assertSame(auth::OK, auth::check_request(['HTTP_AUTHORIZATION' => 'Bearer forced']));
        unset($CFG->forced_plugin_settings['tool_prometheus']);
    }
}
