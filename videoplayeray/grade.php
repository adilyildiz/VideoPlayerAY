<?php
// This file is part of Moodle - https://moodle.org/
// Licensed under the GNU GPL v3 or later: https://www.gnu.org/copyleft/gpl.html
/**
 * Redirect the user to the appropriate grading/report or view page.
 * @package mod_videoplayeray
 * @category grade
 * @copyright 2017 Adil YILDIZ
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once(__DIR__ . '/../../config.php');

$id = required_param('id', PARAM_INT);
$userid = optional_param('userid', 0, PARAM_INT);

$cm = get_coursemodule_from_id('videoplayeray', $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
require_login($course, false, $cm);
$context = context_module::instance($cm->id);

if (has_capability('mod/videoplayeray:viewreports', $context)) {
    if ($userid > 0) {
        redirect(new moodle_url('/mod/videoplayeray/detail.php', ['id' => $cm->id, 'userid' => $userid]));
    }
    redirect(new moodle_url('/mod/videoplayeray/report.php', ['id' => $cm->id]));
}

redirect(new moodle_url('/mod/videoplayeray/view.php', ['id' => $cm->id]));
