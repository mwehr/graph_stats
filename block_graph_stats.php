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

/*
 * This file is used to make the block in site or course
 *
 * @package    block
 * @subpackage graph_stats
 * @copyright  2011 Éric Bugnet with help of Jean Fruitet, Mario wehr
 * @copyright 2014 Wesley Ellis, Code Improvements.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/*
 * Main block class for graph_stats block
 *
 * @copyright 2011 Éric Bugnet with help of Jean Fruitet
 * @copyright 2014 Wesley Ellis, Code Improvements.
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class block_graph_stats extends block_base {

    /*
    * Standard block API function for initializing block instance
    * @return void
    */
    public function init() {
        $this->title = get_string('blockname', 'block_graph_stats');
    }

    public function has_config() {
        return true;
    }

    public function instance_allow_config() {
        return true;
    }

    public function instance_allow_multiple() {
        return false;
    }

    public function applicable_formats() {
        return array(
            'site' => true,
            'course-view' => true);
    }

    public function get_required_javascript() {
        parent::get_required_javascript();

        $this->page->requires->jquery();
    }

    public function get_content() {
        global $CFG, $COURSE, $USER, $DB;

        if ($this->content !== null) {
            return $this->content;
        }

        /*
         * number of day for the graph
         * @var int
         */
        $daysnb = 30;
        $daysnb = $CFG->daysnb;

        $this->content = new stdClass;
        $this->content->text = '';
        $this->content->footer = '';

        include_once('graph_google.php');
        $this->content->text .= graph_google($COURSE->id, get_string('graphtitle', 'block_graph_stats', $daysnb));

        // Add a link to course report for today.
        if (has_capability('report/log:view', context_course::instance($COURSE->id))) {
            $this->content->text .= html_writer::start_tag('div' , array('class' => 'moredetails'));
            $this->content->text .= html_writer::link(new moodle_url('/blocks/graph_stats/details.php?course_id=' .
                $COURSE->id), get_string('moredetails', 'block_graph_stats') ,
                array('title' => get_string('moredetails', 'block_graph_stats')));
            $this->content->text .= html_writer::end_tag('div');
        }

        $params = array(
            'time' => mktime(0, 0, 0, date("m") , date("d"), date("Y")),
        );
        // Add some details in the footer.
        if ($COURSE->id > 1) {
            if ($CFG->version > 2014051200) { // Moodle 2.7+
                $params['courseid'] = $COURSE->id;
                $params['eventname'] = '\core\event\course_viewed';
                $sql = "SELECT COUNT(DISTINCT(userid)) as countid FROM mdl_logstore_standard_log
                    WHERE timecreated > :time AND eventname = :eventname AND action = 'viewed' AND courseid = :courseid  ";
            } else { // Before Moodle 2.7
                $params['course'] = $COURSE->id;
                $sql = "SELECT COUNT(DISTINCT(userid)) as countid FROM {log}
                    WHERE time > :time AND action = 'view' AND course = :course  ";
            }
            $connections = $DB->get_records_sql($sql , $params);
            $this->content->footer .= get_string('connectedtoday', 'block_graph_stats') . $connections->countid;
        } else {
            if ($CFG->version > 2014051200) { // Moodle 2.7+
                $params['eventname'] = '\core\event\user_loggedin';
                $sql = "SELECT COUNT(userid) as countid FROM mdl_logstore_standard_log WHERE timecreated > :time AND action = 'loggedin' ";
            } else { // Before Moodle 2.7
                $sql = "SELECT COUNT(userid) as countid FROM {log} WHERE time > :time AND action = 'login' ";
            }
            $connections = $DB->get_record_sql($sql, $params);
            $this->content->footer .= get_string('connectedtoday', 'block_graph_stats') . $connections->countid;
            $users = $DB->get_records('user', array('deleted' => 0, 'confirmed' => 1));
            $courses = $DB->get_records('course', array('visible' => 1));
            $this->content->footer .= '<br />'.get_string('membersnb', 'block_graph_stats') . count($users);
            $this->content->footer .= '<br />'.get_string('coursesnb', 'block_graph_stats') . count($courses);
        }
        return $this->content;
    }
}
