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

require_once('../../../config.php');
require_once($CFG->dirroot.'/mod/scorm/locallib.php');

$cmid = optional_param('id', '', PARAM_INT);  // Course Module ID, or
$a = optional_param('a', '', PARAM_INT);  // scorm ID.
$scoid = required_param('scoid', PARAM_INT);  // Sco ID.
$attempt = required_param('attempt', PARAM_INT);  // Attempt number.
$function  = required_param('function', PARAM_RAW);  // Function to call.
$request = optional_param('request', '', PARAM_RAW);  // Scorm ID.

if (!empty($cmid)) {
    list($context, $course, $cm) = $PAGE->login_to_cm('scorm', $cmid, null, PAGELOGIN_NO_AUTOLOGIN);
} else if (!empty($a)) {
    list($context, $course, $cm) = $PAGE->login_to_activity('scorm', $a, null, PAGELOGIN_NO_AUTOLOGIN);
} else {
    print_error('missingparameter');
}

$PAGE->set_url('/mod/scorm/datamodels/sequencinghandler.php',
    array('scoid'=>$scoid, 'attempt'=>$attempt, 'id'=>$cm->id, 'function' => $function, 'request' => $request));

if (!empty($scoid) && !empty($function)) {
    require_once($CFG->dirroot.'/mod/scorm/datamodels/scorm_13lib.php');

    if (has_capability('mod/scorm:savetrack', $context)) {
        $result = null;
        switch ($function) {
            case 'scorm_seq_flow' :
                if ($request == 'forward' || $request == 'backward') {
                    $seq = scorm_seq_navigation ($scoid, $USER->id, $request.'_', $attempt);
                    $sco = scorm_get_sco($scoid);
                    $seq = scorm_seq_flow($sco, $request, $seq, true, $USER->id);
                    if (!empty($seq->nextactivity)) {
                        scorm_seq_end_attempt($sco, $USER->id, $seq);
                    }
                }
                echo json_encode($seq);
                break;
        }
    }
}
