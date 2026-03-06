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
 * Form for editing assignment instructions (intro + activity) on the question bank page.
 *
 * @package    local_trustgrade
 * @copyright  2025 CentricApp LTD <support@centricapp.co.il>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_trustgrade\form;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

/**
 * Form to edit assignment description (intro) and activity instructions.
 */
class edit_instructions_form extends \moodleform {

    /**
     * Form definition.
     */
    protected function definition() {
        $mform = $this->_form;
        $cmid = isset($this->_customdata['cmid']) ? (int) $this->_customdata['cmid'] : 0;
        $context = isset($this->_customdata['context']) ? $this->_customdata['context'] : null;
        if (!$context) {
            $context = \context_system::instance();
        }

        $mform->addElement('hidden', 'cmid', $cmid);
        $mform->setType('cmid', PARAM_INT);

        $editoropts = [
            'maxfiles' => EDITOR_UNLIMITED_FILES,
            'noclean' => true,
            'context' => $context,
            'subdirs' => true,
        ];

        $mform->addElement('editor', 'introeditor', get_string('assignment_intro_preview', 'local_trustgrade'), ['rows' => 10], $editoropts);
        $mform->setType('introeditor', PARAM_RAW);

        $mform->addElement('editor', 'activityeditor', get_string('activityeditor', 'assign'), ['rows' => 10], $editoropts);
        $mform->setType('activityeditor', PARAM_RAW);

        $this->add_action_buttons(true, get_string('save_instructions', 'local_trustgrade'));
    }
}
