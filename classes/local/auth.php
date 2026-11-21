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
 * Authentication of the metrics endpoint: bearer token and optional IP allow list.
 *
 * @package    tool_prometheus
 * @copyright  2026 Moodle Observability contributors
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class auth {
    /** Authorized. */
    public const OK = 200;

    /** Missing or invalid token. */
    public const UNAUTHORIZED = 401;

    /** Address not in the allow list. */
    public const FORBIDDEN = 403;

    /** Token not configured. */
    public const NOT_CONFIGURED = 503;

    /**
     * Checks a request against the plugin configuration.
     *
     * @param array $server The $_SERVER array of the request.
     * @return int One of the status constants.
     */
    public static function check_request(array $server): int {
        return self::check(
            (string) get_config('tool_prometheus', 'token'),
            self::bearer_token($server),
            (string) get_config('tool_prometheus', 'allowedips'),
            getremoteaddr('')
        );
    }

    /**
     * Checks a token and an address.
     *
     * @param string $configured Configured token ('' when not configured).
     * @param string|null $presented Token presented by the client.
     * @param string $allowedips Allow list (admin_setting_configiplist format); '' allows any address.
     * @param string $remoteip Client address.
     * @return int One of the status constants.
     */
    public static function check(string $configured, ?string $presented, string $allowedips, string $remoteip): int {
        if (trim($configured) === '') {
            return self::NOT_CONFIGURED;
        }
        if (trim($allowedips) !== '' && ($remoteip === '' || !address_in_subnet($remoteip, self::subnet_list($allowedips)))) {
            return self::FORBIDDEN;
        }
        if ($presented === null || $presented === '' || !hash_equals(trim($configured), $presented)) {
            return self::UNAUTHORIZED;
        }
        return self::OK;
    }

    /**
     * Bearer token of the request, if any.
     *
     * Apache may expose the header under different names; the query string is never accepted.
     *
     * @param array $server The $_SERVER array of the request.
     * @return string|null
     */
    public static function bearer_token(array $server): ?string {
        $header = $server['HTTP_AUTHORIZATION'] ?? $server['REDIRECT_HTTP_AUTHORIZATION'] ?? null;
        if ($header === null && function_exists('getallheaders')) {
            foreach ((array) getallheaders() as $name => $value) {
                if (strtolower((string) $name) === 'authorization') {
                    $header = (string) $value;
                    break;
                }
            }
        }
        if ($header === null || !preg_match('/^\s*Bearer\s+(\S+)\s*$/i', (string) $header, $matches)) {
            return null;
        }
        return $matches[1];
    }

    /**
     * Sends an error response for a status other than OK.
     *
     * @param int $status Status constant.
     * @return void
     */
    public static function send_error(int $status): void {
        $messages = [
            self::UNAUTHORIZED => 'error:unauthorized',
            self::FORBIDDEN => 'error:forbidden',
            self::NOT_CONFIGURED => 'error:notconfigured',
        ];
        http_response_code($status);
        if ($status === self::UNAUTHORIZED) {
            header('WWW-Authenticate: Bearer realm="tool_prometheus"');
        }
        header('Content-Type: text/plain; charset=utf-8');
        header('Cache-Control: no-store');
        echo get_string($messages[$status] ?? 'error:unauthorized', 'tool_prometheus') . "\n";
    }

    /**
     * Converts the allow list (one entry per line) into the comma separated list of address_in_subnet().
     *
     * @param string $allowedips Allow list.
     * @return string
     */
    private static function subnet_list(string $allowedips): string {
        $entries = preg_split('/[\s,]+/', trim($allowedips), -1, PREG_SPLIT_NO_EMPTY);
        return implode(',', $entries);
    }
}
