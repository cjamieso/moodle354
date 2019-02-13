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

namespace block_nurs_navigation\output;

defined('MOODLE_INTERNAL') || die;
require_once($CFG->dirroot . '/course/lib.php');

use renderable;
use templatable;
use renderer_base;
use stdClass;

/**
 * Holds the data needed to draw the activity table on the screen.
 *
 * @package    block_nurs_navigation
 * @copyright  2018 Craig Jamieson
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class activity_table_renderable implements renderable, templatable {

    /** @var int the ID of the course. */
    public $courseid;
    /** @var object the full course object. */
    public $course;
    /** @var string the type of activity to render. (quiz|assign|quest) */
    public $type;

    /**
     * Constructor.
     *
     * @param  int    $courseid  the ID of the course to use
     * @param  array  $type      the type of activity
     */
    public function __construct($courseid, $type) {

        $this->courseid = $courseid;
        $this->course = get_course($this->courseid);
        $this->type = $type;
    }

    /**
     * Export data for mustache template rendering.
     *
     * @param  object  $output  the output renderer
     * @return object  the data needed to render the template
     */
    public function export_for_template(renderer_base $output) {

        $data = new \stdClass();
        $data->mods = $this->format_results($this->generate_list());
        return $data;
    }

    /**
     * Generate a list of mods (by cmid) to use for the list.
     *
     * @return array   a list of cmids
     */
    protected function generate_list() {
        global $DB;

        $cmids = [];
        if ($this->type == 'quiz' || $this->type == 'assign') {
            $mods = get_all_instances_in_course($this->type, $this->course, null, true);
            foreach ($mods as $mod) {
                $field = $this->type . $mod->coursemodule;
                $activity = new \block_nurs_navigation\activity($this->courseid, $field);
                if ($activity->get_type() == $this->type) {
                    $cmids[] = $mod->coursemodule;
                }
            }
        }
        $params = array($this->course->id, $this->type);
        $query = "SELECT * FROM {nurs_navigation_activities} WHERE courseid = ? AND type = ?";
        $records = $DB->get_records_sql($query, $params);
        foreach ($records as $record) {
            $activity = new \block_nurs_navigation\activity($this->course->id, $record->activity);
            // If type matches moodle type, then we've already counted this.
            if (($this->type == 'quiz' && $activity->get_moodle_type() == 'quiz') || ($this->type == 'assign' &&
                $activity->get_moodle_type() == 'assign')) {
                continue;
            }
            $cmids[] = preg_replace("/[^0-9]/", '', $record->activity);
        }
        return $cmids;
    }

    /**
     * Format the information about the mod by retrieving the appropriate information.
     *
     * @param  array  $cmids  the cmIDs to include in the table
     * @return array  the final list of mods to display
     */
    protected function format_results($cmids) {

        $results = [];
        $modinfo = get_fast_modinfo($this->course);
        $sectionheaders = array();
        get_section_titles($this->courseid, $sectionheaders);
        foreach ($modinfo->sections as $number => $mods) {
            foreach ($mods as $mod) {
                if (array_search($mod, $cmids) !== false) {
                    $section = ($number > 0) ? $sectionheaders[$number] : get_string('general');
                    // Skip if flagged as hidden on course page.
                    if (!$modinfo->cms[$mod]->visible) {
                        continue;
                    }
                    $temp = array('section' => $section, 'visible' => $modinfo->cms[$mod]->uservisible, 'cmid' => $mod);
                    if ($modinfo->cms[$mod]->modname == 'quiz') {
                        $quizinfo = $this->get_quiz_info($modinfo->cms[$mod]->instance);
                    } else if ($modinfo->cms[$mod]->modname == 'assign') {
                        $quizinfo = $this->get_assign_info($modinfo->cms[$mod]->instance);
                    } else {
                        throw new \Exception(get_string('modnotfound', 'block_nurs_navigation'));
                        // Throw exception.
                    }
                    $results[] = array_merge($temp, $quizinfo);
                }
            }
        }
        return $results;
    }

    /**
     * Gets the quiz information.
     *
     * @param  int  $id  the quiz ID
     * @return array  quiz information (name + time closing)
     */
    protected function get_quiz_info($id) {
        global $DB;

        $record = $DB->get_record('quiz', array('id' => $id));
        $close = ($record->timeclose == 0) ? get_string('noclose', 'quiz') : userdate($record->timeclose);
        return array('name' => $record->name, 'close' => $close);
    }

    /**
     * Gets the assignment information.
     *
     * @param  int  $id  the assignment ID
     * @return array  the assignment information (name + time closing)
     */
    protected function get_assign_info($id) {
        global $DB;

        $record = $DB->get_record('assign', array('id' => $id));
        $close = ($record->duedate == 0) ? get_string('noclose', 'assign') : userdate($record->duedate);
        return array('name' => $record->name, 'close' => $close);
    }

}
