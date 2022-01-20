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
defined('MOODLE_INTERNAL') || die();

$services = array (
    'scormreport_heatmap_interactive_elements' => array (
        'functions' => array('scormreport_heatmap_fetchmap'),
        'requirecapability' => '',
        'restricteduseres' => 0,
        'enabled' => 1,
        'downloadfiles' => 0,
        'uploadfiles' => 0
    )
);
$functions = array (
    'scormreport_heatmap_fetchmap' => array (
        'classname' => 'scormreport_heatmap_external',
        'methodname' => 'fetchmap',
        'classpath' => 'mod/scorm/report/heatmap/externallib.php',
        'description' => 'Fetch a heatmap with specific detail level',
        'type' => 'read',
        'ajax' => 'true',
        'capabilities' => ''
    ),
);
