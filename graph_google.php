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
 * This file is used to setting the block allover the site
 *
 * @package    block
 * @subpackage graph_stats
 * @copyright  2011 Éric Bugnet with help of Jean Fruitet, Mario wehr
 * @copyright  2014 Wesley Ellis, Code Improvements.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

function graph_google($courseid, $title) {
    global $CFG, $DB;

    // Number of day for the graph.
    $daysnb = $CFG->daysnb;

    // Define type.
    if ($CFG->style == 'area') {
        $type1 = 'area';
        $type2 = 'area';
    } else {
        $type1 = 'bars';
        $type2 = 'line';
    }

    $days = array();
    $day = array();
    $logs = array();
    $logsmulti = array();

    // Let's get the datas.
    $a = 0;
    if ($courseid > 1) {
        for ($i = $daysnb; $i > -1; $i--) { // Days count.
            $params = array(
                'time1' => mktime(0, 0, 0, date("m") , date("d") - $i, date("Y")),
                'time2' => mktime(0, 0, 0, date("m") , date("d") - ($i - 1), date("Y")),
                'courseid' => $courseid);
            if ($CFG->version > 2014051200) { // Moodle 2.7+
                $params['eventname'] = '\core\event\course_viewed';
                $sql = "SELECT COUNT(DISTINCT(userid)) as countid FROM mdl_logstore_standard_log
                  WHERE timecreated > :time1 AND timecreated < :time2 AND action = 'viewed' AND eventname = :eventname AND courseid = :courseid";
            } else { // Before Moodle 2.7
                $sql = "SELECT COUNT(DISTINCT(userid)) as countid FROM {log}
                    WHERE time > :time1 AND time < :time2 AND action = 'view' AND course = :courseid";
            }
            $countgraphmulti = $DB->get_record_sql($sql, $params);
            $days[$a] = '';
                    $logsmulti[$a] = $countgraphmulti->countid;
                    $day[$a] = substr(userdate(mktime(0, 0, 0, date("m") , date("d") - $i, date("Y"))), 0, -7);
                    $a = $a + 1;
        }
    } else {
        for ($i = $daysnb; $i > -1; $i--) { // Days count.
            $params = array(
                'time1' => mktime(0, 0, 0, date("m") , date("d") - $i, date("Y")),
                'time2' => mktime(0, 0, 0, date("m") , date("d") - ($i - 1), date("Y")),
                'courseid' => $courseid);
            if ($CFG->version > 2014051200) { // Moodle 2.7+
                $params['eventname'] = '\core\event\user_loggedin';
                $sql = "SELECT COUNT(DISTINCT(userid)) as countid FROM mdl_logstore_standard_log
                    WHERE timecreated > :time1 AND timecreated < :time2 AND eventname = :eventname AND action = 'loggedin' ";
            } else { // Before Moodle 2.7
                $sql = "SELECT COUNT(DISTINCT(userid)) as countid FROM {log}
                    WHERE time > :time1 AND time < :time2 AND action = 'login'";
            }
            $countgraph = $DB->get_record_sql($sql, $params);
            $days[$a] = '';
            $logs[$a] = $countgraph->countid;
            if ($CFG->multi == 1) {
                // no need to fill params a second time
                /*$params = array(
                    'time1' => mktime(0, 0, 0, date("m") , date("d") - $i, date("Y")),
                    'time2' => mktime(0, 0, 0, date("m") , date("d") - ($i - 1), date("Y")),
                    'courseid' => $courseid );*/
                if ($CFG->version > 2014051200) { // Moodle 2.7+
                    $sql = "SELECT COUNT(userid) as countid FROM mdl_logstore_standard_log
                        WHERE timecreated > :time1 AND timecreated < :time2 AND eventname = :eventname AND action = 'loggedin' ";
                } else { // Before Moodle 2.7
                    $sql = "SELECT COUNT(userid) as countid FROM {log}
                        WHERE time > :time1 AND time < :time2 AND action = 'login' ";
                }
                $countgraphmulti = $DB->get_record_sql($sql, $params);
                $logsmulti[$a] = $countgraphmulti->countid;
            }
            $day[$a] = substr(userdate(mktime(0, 0, 0, date("m") , date("d") - $i, date("Y"))), 0, -7);
            $a = $a + 1;
        }
    }

    $graph = '
        <script type="text/javascript" src="https://www.google.com/jsapi"></script>
        <script type="text/javascript">
        google.load("visualization", "1", {packages:["corechart"]});
        google.setOnLoadCallback(drawChart);
        function drawChart() {
        var data = new google.visualization.DataTable();
        data.addColumn("string", "Day");
        data.addColumn("number", "' . get_string('visitors', 'block_graph_stats') . '"); ';
    if ($courseid <= 1) {
        $graph .= 'data.addColumn("number", "'. get_string('uniquevisitors', 'block_graph_stats') . '");';
    }

        $graph .= '
        data.addRows([ ';
            $a = 0;
    for ($i = $daysnb; $i > -1; $i--) {
        if ($courseid > 1) {
            $graph .= '["' . $day[$a] . '",' . $logsmulti[$a] . '],';
        } else {
            $graph .= '["' . $day[$a] . '",' . $logsmulti[$a] . ',' . $logs[$a] . '],';
        }
        $a++;
    }
    $graph .= '    ]);
        var options = {
        legend: {position: "none"},
        backgroundColor: {
                           fill: "'. $CFG->outer_background . '",
                           stroke: "'. $CFG->inner_border . '",
                           strokeWidth: "'. $CFG->border_width . '"
                         },
        hAxis: {
                textPosition: "none",
               },
        vAxis: {
                gridlines: {
                            color: "'. $CFG->axis_colour . '"
                         }
                },
        series: {0:{color: "'. $CFG->color1 .'", type: "'.$type1.'"},1:{color: "'. $CFG->color2 .'", type: "'.$type2.'"}}
        };

        var chart = new google.visualization.AreaChart(document.getElementById("chart_div"));
        chart.draw(data, options);
        }

        $(document).ready(function () {
        $(window).resize(function(){
            drawChart();
                });
            });
        </script>
        <div id="chart_div" style="width:100%; height:' . $CFG->graphheight .'"></div>';

    return $graph;
}