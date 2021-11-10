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
 * Core Report class of heatmap reporting plugin
 *
 * @package    scormreport_heatmap
 * @copyright  2021 Robin Tschudi
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace scormreport_heatmap;

defined('MOODLE_INTERNAL') || die();

use context_module;
use core\chart_series;


/**
 * Main class to control the heatmap reporting
 *
 * @package    scormreport_heatmap
 * @copyright  2021 Robin Tschudi
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class report extends \mod_scorm\report {

    const PLUGINNAME = 'scormreport_heatmap';

    public static function parse_percent_truefalse ($data) {
        return ((int) $data['result'] == 'correct') * 100;
    }

    // Since scorm doesn't tell us all the options we'll have to rely on the students answers.
    public static function parse_percent_choice($data) {
        $response = explode(',', $data['student_response']);
        $options = array();
        foreach ($data['correct_responses'] as $correctresponsestring) {
            $correctresponses = explode(',', $correctresponsestring);
            $totalno = count($correctresponses);
            $correctno = 0;
            foreach ($response as $studentresponse) {
                if (!in_array($studentresponse, $correctresponses)) {
                    $totalno++;
                } else {
                    $correctno++;
                }
            }
            array_push($options, ($correctno * 100 / $totalno));
        }
        return max($options);
    }

    public function parse_data_to_percent($data) {
        $parseddata = array();
        foreach ($data as $uid => $attempt) {
            $parseddata[$uid] = array();
            foreach ($attempt as $key => $questiondata) {
                $type = str_replace('-', '', $questiondata['type']);
                $callbackname = 'parse_percent_' . $type;
                if (method_exists($this, $callbackname)) {
                    $parsed = self::$callbackname($questiondata);
                    $parseddata[$uid][$key] = $parsed;
                }
            }
        }
        return $parseddata;
    }

    public function get_data($scormid) {
        global $DB;
        $attemptbased = false;
        $rawdata = $DB->get_records('scorm_scoes_track', array('scormid' => $scormid), "", 'id, userid, attempt, element, value');
        $refineddata = array();
        foreach ($rawdata as $key => $value) {
            if ($attemptbased) {
                $attemptid = $value->userid . "-" . $value->attempt;
            } else {
                $attemptid = $value->userid;
            }
            if (!array_key_exists($attemptid, $refineddata)) {
                $refineddata[$attemptid] = array();
            }
            $scormvalue = $value->value;
            $element = $value->element;
            $match = array();
            if (preg_match('/cmi\.interactions_([0-9]*)\.(.*)/', $element, $match)) {
                $questionno = $match[1];
                $newkey = $match[2];
                if (!array_key_exists($questionno, $refineddata[$attemptid]) or !is_array($refineddata[$attemptid][$questionno])) {
                    $refineddata[$attemptid][$questionno] = array();
                }
                $responses = array();
                if (preg_match('/correct_responses_[0-9]*\.pattern/', $newkey, $responses)) {
                    if (!array_key_exists('correct_responses', $refineddata[$attemptid][$questionno])) {
                        $refineddata[$attemptid][$questionno]['correct_responses'] = array();
                    }
                    array_push($refineddata[$attemptid][$questionno]['correct_responses'], $scormvalue);
                } else {
                    $refineddata[$attemptid][$questionno][$newkey] = $scormvalue;
                }
            }
        }
        return $refineddata;
    }

    public function get_average($data) {
        $count = count($data);
        if ($count == 0) {
            return array (0);
        } else {
            $rowcount = count($data[array_keys($data)[0]]);
        }
        $sums = array();
        for ($i = 0; $i < $rowcount; $i++) {
            $sums[] = 0;
        }
        foreach ($data as $datapoint) {
            for ($i = 0; $i < $rowcount; $i++) {
                $sums[$i] = $datapoint[$i] + $sums[$i];
            }
        }
        return array_map(function($x) use ($count) {
            return $x / $count;
        }, $sums);
    }

    public function get_categories ($data, $numofcategories = 10) {
        $barnumber = count($data[array_keys($data)[0]]);
        $divider = 100 / ($numofcategories - 1);
        $catarray = array();
        for ($i = 0; $i < $numofcategories; $i++) {
            $catarray[] = array();
            for ($j = 0; $j < $barnumber; $j++) {
                $catarray[$i][$j] = 0;

            }
        }
        foreach ($data as $attempt) {
            for ($j = 0; $j < $barnumber; $j++) {
                $section = $attempt[$j] / $divider;
                $catarray[$section][$j]++;
            }
        }
        return array_map(function ($cat) use ($data) {
            return array_map(function ($val) use ($data) {
                return $val * 100 / count($data);
            }, $cat);
        }, $catarray);
    }

    private function dehex ($num) {
        return str_pad((dechex(max(0, min(256, $num)))), 2, '0', STR_PAD_LEFT);
    }

    public function get_colors($data) {
        return array_map(function($catarray) {
            return array_map(function($percent) {
                $blue = 128 + (242 - 128) * (100 - $percent) / 100;
                $redgreen = 229 * (100 - $percent) / 100;
                return '#' . $this->dehex($redgreen) . $this->dehex($redgreen) . $this->dehex($blue);
            }, $catarray);
        }, $data);
    }

    /**
     * Displays the full report.
     *
     * @param \stdClass $scorm full SCORM object
     * @param \stdClass $cm - full course_module object
     * @param \stdClass $course - full course object
     * @param string $download - type of download being requested
     * @return void
     */
    public function display($scorm, $cm, $course, $download) {
        global $DB, $OUTPUT, $PAGE;
        $rawdata = $this->get_data($scorm->id);
        $attemptsize = count($rawdata);
        $parsedata = $this->parse_data_to_percent($rawdata);
        $categorybararray = $this->get_categories($parsedata);
        $colorarray = $this->get_colors($categorybararray);
        $averagearray = $this->get_average($parsedata);
        $numberarray = array();
        for ($i = 0; $i < count($averagearray); $i++) {
            array_push($numberarray, $i);
        }

        $chart = new \core\chart_bar(); // Create a bar chart instance.
        $series1 = new \core\chart_series(get_string('average', self::PLUGINNAME), $averagearray);
        $series1->set_type(\core\chart_series::TYPE_LINE);
        $chart->add_series($series1);
        $barseries = array();
        for ($i = 0; $i < 10; $i++) {
            $barseries[] = new \core\chart_series('', [10, 10, 10]);
            $barseries[$i]->set_color($colorarray[$i]);
            $chart->add_series($barseries[$i]);
        }
        $chart->set_stacked(true);
        $chart->set_labels(array_map(function ($i) {
            return get_string('question', self::PLUGINNAME) . " " . ($i + 1);
        }, $numberarray));
        $chart->get_yaxis(0, true)->set_max(100);
        $chart->get_yaxis(0, true)->set_stepsize(10);
        echo $OUTPUT->render($chart);
        $contextmodule = context_module::instance($cm->id);

    }
}
