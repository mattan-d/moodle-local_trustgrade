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
 * Form for generating questions from uploaded files (question bank page).
 *
 * @package    local_trustgrade
 * @copyright  2025 CentricApp LTD <support@centricapp.co.il>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_trustgrade\form;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

/**
 * Form to upload files and set options for AI question generation.
 */
class generate_from_files_form extends \moodleform {

    /**
     * Form definition.
     */
    protected function definition() {
        $mform = $this->_form;
        $cmid = isset($this->_customdata['cmid']) ? (int) $this->_customdata['cmid'] : 0;

        $mform->addElement('hidden', 'cmid', $cmid);
        $mform->setType('cmid', PARAM_INT);

        $mform->addElement('header', 'generate_from_files_header', get_string('generate_from_files', 'local_trustgrade'));

        $fileoptions = [
            'accepted_types' => '*',
            'maxbytes' => 0,
            'maxfiles' => 20,
            'subdirs' => 0,
        ];
        $mform->addElement('filemanager', 'generatefiles', get_string('upload_files_for_questions', 'local_trustgrade'), null, $fileoptions);
        $mform->addHelpButton('generatefiles', 'upload_files_for_questions', 'local_trustgrade');

        $countoptions = [];
        for ($i = 1; $i <= 50; $i++) {
            $countoptions[$i] = $i;
        }
        $mform->addElement('select', 'questioncount', get_string('number_of_questions', 'local_trustgrade'), $countoptions);
        $mform->setDefault('questioncount', 5);

        $mform->addElement('textarea', 'instructions', get_string('optional_instructions', 'local_trustgrade'), ['rows' => 3, 'cols' => 60]);
        $mform->setType('instructions', PARAM_TEXT);
        $mform->addHelpButton('instructions', 'optional_instructions', 'local_trustgrade');

        $this->add_action_buttons(false, get_string('generate_questions_from_files', 'local_trustgrade'));
    }

    /**
     * Validation: at least one file or non-empty instructions.
     *
     * @param array $data form data
     * @param array $files uploaded files
     * @return array errors
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);
        $draftitemid = isset($data['generatefiles']) ? (int) $data['generatefiles'] : 0;
        $instructions = isset($data['instructions']) ? trim($data['instructions']) : '';

        if ($draftitemid > 0) {
            $fs = get_file_storage();
            $userctx = \context_user::instance($GLOBALS['USER']->id);
            $areafiles = $fs->get_area_files($userctx->id, 'user', 'draft', $draftitemid, 'sortorder, id', false);
            if (!empty($areafiles)) {
                return $errors;
            }
        }

        if ($instructions !== '') {
            return $errors;
        }

        $errors['generatefiles'] = get_string('no_instructions_or_files', 'local_trustgrade');
        return $errors;
    }
}
