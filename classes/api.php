<?php
// This file is part of Moodle - http://moodle.org/
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
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * API class for Gateway integration and AI functionality.
 *
 * @package    local_trustgrade
 * @copyright  2025 CentricApp LTD <support@centricapp.co.il>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_trustgrade;

defined('MOODLE_INTERNAL') || die();

/**
 * API class with Gateway-only integration
 */
class api {

    /**
     * Send instructions to AI for analysis via Gateway
     *
     * @param string $instructions The assignment instructions (plain text)
     * @param array $files Array of attachment objects: [
     *   ['filename' => string, 'mimetype' => string, 'size' => int, 'content' => string]
     * ]
     * @return array Response from Gateway or error
     */
    public static function check_instructions($instructions, array $files = []) {
        try {
            $gateway = new gateway_client();
            $result = $gateway->checkInstructions($instructions, $files);

            if ($result['success']) {
                return [
                    'success' => true,
                    'recommendation' => json_encode($result['data']['recommendation']) ?? json_encode($result['data']['content'])
                ];
            } else {
                return ['error' => $result['error']];
            }

        } catch (\Exception $e) {
            return ['error' => 'Gateway error: ' . $e->getMessage()];
        }
    }
}
