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

namespace block_nurs_navigation;

defined('MOODLE_INTERNAL') || die();
require_once($CFG->libdir.'/formslib.php');
require_once($CFG->dirroot.'/blocks/nurs_navigation/lib.php');

/**
 * edit_activities_form class
 *
 * This class creates form which allows users to categorize quizzes and assignments in the
 * course so that they can be aggregated and displayed to users.
 *
 * @package    block_nurs_navigation
 * @copyright  2018 Craig Jamieson
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class edit_activities_form extends \moodleform {

    /**
     * This method constructs the form and stores the course ID for later use.
     *
     * @param int $courseid The ID of the course to create the form for.
     *
     */
    public function __construct($courseid) {

        $this->courseid = $courseid;
        parent::__construct();
    }

    /**
     * Form definition: the form contains two sections: quizzes and assignments
     * The user may indicate what the classificiation of each quiz/assignment is from
     * a list of 4 choices: {quiz|assignment|quest|none}
     */
    public function definition() {
        global $DB;

        $mform = &$this->_form;
        $course = $DB->get_record('course', array('id' => $this->courseid));

        $this->add_assessments('quiz', get_string('setquizzes', BNN_LANG_TABLE), get_all_instances_in_course('quiz', $course));
        $this->add_assessments('assign', get_string('setassignments', BNN_LANG_TABLE), get_all_instances_in_course('assign',
            $course));

        // Hidden elements (courseid + blockid: needed for posting).
        $mform->addElement('hidden', 'courseid');
        $mform->setType('courseid', PARAM_INT);
        $mform->addElement('hidden', 'blockid');
        $mform->setType('blockid', PARAM_INT);

        $this->add_action_buttons();
    }

    /**
     * Add a list of assessments (quizzes or assignments) to the form.
     *
     * @param  string  $headerid     the ID for the form header
     * @param  string  $headertitle  the title for the section
     * @param  object  $mods         the list of assessments to add
     */
    private function add_assessments($headerid, $headertitle, $mods) {

        $mform = &$this->_form;
        $options = array('quiz' => get_string('modulename', 'mod_quiz'), 'assign' => get_string('modulename', 'mod_assign'),
            'quest' => get_string('quest', BNN_LANG_TABLE), 'none' => get_string('none'));
        $mform->addElement('header', $headerid, $headertitle);
        foreach ($mods as $mod) {
            $field = $headerid . $mod->coursemodule;
            $select = $mform->addElement('select', $field, $mod->name, $options);
            $activity = new activity($this->courseid, $field);
            $select->setSelected($activity->get_type());
        }
    }

    /**
     * Process a submitted form.  Go through each quiz and assignment and update
     * its associated type field that the user has indicated.
     *
     * @param  object  $form  the submitted data
     */
    public function process_form($form) {
        global $DB;

        $course = $DB->get_record('course', array('id' => $this->courseid));
        $quizzes = get_all_instances_in_course("quiz", $course);
        foreach ($quizzes as $quiz) {
            $field = 'quiz' . $quiz->coursemodule;
            $activity = new activity($this->courseid, $field);
            $activity->update_type($form->$field);
        }

        $assignments = get_all_instances_in_course("assign", $course);
        foreach ($assignments as $assignment) {
            $field = 'assign' . $assignment->coursemodule;
            $activity = new activity($this->courseid, $field);
            $activity->update_type($form->$field);
        }
    }
}
