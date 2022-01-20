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

require_once(__DIR__ . '/../../config.php');
$pluginname = 'report_scorm';
$scormid = required_param('scormid', PARAM_INT);
global $DB;
$scormrecord = $DB->get_record('scorm', array('id' => htmlspecialchars($scormid)));
if (!$scormrecord) {
    print_error ('invalid_scormid');
}
$cm = get_coursemodule_from_instance("scorm", $scormid, $scormrecord->course, true);
if (!$cm) {
    print_error ('coursemodule_not_found');
}

require_login($scormrecord->course, true, $cm);

global $PAGE;
$PAGE->set_context(context_module::instance($cm->id));
$PAGE->set_url('/report/scorm/report.php', array('scormid' => $scormid));
$PAGE->set_title($scormrecord->name . get_string('report', $pluginname));
$PAGE->set_heading(get_string('report_for', $pluginname));
$PAGE->set_pagelayout('report');
$PAGE->set_cm($cm);
global $OUTPUT;
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('report_for', $pluginname) . $scormrecord->name);

echo $OUTPUT->footer();