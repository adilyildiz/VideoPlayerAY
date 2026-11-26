<?php
// This file is part of Moodle - https://moodle.org/
// Licensed under the GNU GPL v3 or later: https://www.gnu.org/copyleft/gpl.html
/**
 * Video Analiz activity library.
 * @package mod_videoplayeray
 * @copyright 2017 Adil YILDIZ
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

function videoplayeray_supports($feature) {
    switch ($feature) {
        case FEATURE_GROUPS:
        case FEATURE_GROUPINGS:
        case FEATURE_MOD_INTRO:
        case FEATURE_SHOW_DESCRIPTION:
        case FEATURE_GRADE_HAS_GRADE:
        case FEATURE_BACKUP_MOODLE2:
        case FEATURE_COMPLETION_TRACKS_VIEWS:
            return true;
        default:
            if (defined('FEATURE_MOD_PURPOSE') && defined('MOD_PURPOSE_CONTENT') && $feature === FEATURE_MOD_PURPOSE) {
                return MOD_PURPOSE_CONTENT;
            }
            return null;
    }
}

function videoplayeray_add_instance(stdClass $data, $mform = null) {
    global $DB;
    require_once(__DIR__ . '/locallib.php');
    $data->timecreated = time();
    $data->timemodified = time();
    if (isset($data->questiontext)) {
        [$questions, $errors] = videoplayeray_questions_from_form($data);
        if ($errors) {
            throw new moodle_exception('invalidquestion', 'videoplayeray');
        }
        $data->questions = json_encode((object)$questions);
    }
    $data->overlayjs = $data->overlayjs ?? '';
    $data->questions = $data->questions ?? '[]';
    $data->id = $DB->insert_record('videoplayeray', $data);
    $DB->set_field('course_modules', 'instance', $data->id, ['id' => $data->coursemodule]);
    videoplayeray_migrate_legacy_questions($data);
    videoplayeray_set_mainfile($data);
    videoplayeray_grade_item_update($data);
    return $data->id;
}

function videoplayeray_update_instance($data, $mform = null) {
    global $DB;
    require_once(__DIR__ . '/locallib.php');
    $data->id = $data->instance;
    $data->timemodified = time();
    if (isset($data->questiontext)) {
        [$questions, $errors] = videoplayeray_questions_from_form($data);
        if ($errors) {
            throw new moodle_exception('invalidquestion', 'videoplayeray');
        }
        $data->questions = json_encode((object)$questions);
    }
    $DB->update_record('videoplayeray', $data);
    videoplayeray_set_mainfile($data);
    videoplayeray_grade_item_update($data);
    videoplayeray_update_grades($DB->get_record('videoplayeray', ['id' => $data->id], '*', MUST_EXIST));
    return true;
}

function videoplayeray_delete_instance($id) {
    global $DB;
    if (!$activity = $DB->get_record('videoplayeray', ['id' => $id])) {
        return false;
    }
    if ($cm = get_coursemodule_from_instance('videoplayeray', $id)) {
        foreach (['videoplayeray_izlenme', 'videoplayeray_olaylar'] as $table) {
            // The historical videoid stores the course module ID, not the instance ID.
            $DB->delete_records($table, ['videoid' => $cm->id]);
        }
        get_file_storage()->delete_area_files(context_module::instance($cm->id)->id, 'mod_videoplayeray');
    }
    $DB->delete_records('videoplayeray_questions', ['videoplayerayid' => $id]);
    videoplayeray_grade_item_delete($activity);
    $DB->delete_records('videoplayeray', ['id' => $id]);
    return true;
}

function videoplayeray_extend_settings_navigation(settings_navigation $settingsnav, navigation_node $node) {
    $cm = $settingsnav->get_page()->cm;
    if (!$cm) {
        return;
    }
    $context = context_module::instance($cm->id);
    if (has_capability('moodle/course:manageactivities', $context)) {
        $node->add(
            get_string('questions', 'videoplayeray'),
            new moodle_url('/mod/videoplayeray/questions.php', ['id' => $cm->id]),
            navigation_node::TYPE_SETTING,
            null,
            'mod_videoplayeray_questions'
        );
    }
    if (has_capability('mod/videoplayeray:viewreports', $context)) {
        $node->add(
            get_string('report', 'videoplayeray'),
            new moodle_url('/mod/videoplayeray/report.php', ['id' => $cm->id]),
            navigation_node::TYPE_SETTING,
            null,
            'mod_videoplayeray_report'
        );
    }
}

function videoplayeray_reset_course_form_definition(&$mform) {
    $mform->addElement('header', 'videoplayerayheader', get_string('modulenameplural', 'videoplayeray'));
    $mform->addElement('advcheckbox', 'reset_videoplayeray_progress', get_string('resetprogress', 'videoplayeray'));
}

function videoplayeray_reset_course_form_defaults($course) {
    return ['reset_videoplayeray_progress' => 1];
}

function videoplayeray_reset_userdata($data) {
    global $DB;
    $status = [];
    if (empty($data->reset_videoplayeray_progress)) {
        return $status;
    }
    $activities = $DB->get_records('videoplayeray', ['course' => $data->courseid]);
    foreach ($activities as $activity) {
        if ($cm = get_coursemodule_from_instance('videoplayeray', $activity->id, $data->courseid)) {
            $DB->delete_records('videoplayeray_izlenme', ['videoid' => $cm->id]);
            $DB->delete_records('videoplayeray_olaylar', ['videoid' => $cm->id]);
        }
        videoplayeray_grade_item_update($activity, true);
    }
    $status[] = [
        'component' => get_string('modulenameplural', 'videoplayeray'),
        'item' => get_string('resetprogress', 'videoplayeray'),
        'error' => false,
    ];
    return $status;
}

function videoplayeray_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options = []) {
    if ($context->contextlevel != CONTEXT_MODULE || $filearea !== 'content' || !$args) {
        return false;
    }
    require_login($course, true, $cm);
    require_capability('mod/videoplayeray:view', $context);
    if ((int)array_shift($args) !== 0 || !$args) {
        return false;
    }
    $filename = array_pop($args);
    $filepath = '/' . ($args ? implode('/', $args) . '/' : '');
    $file = get_file_storage()->get_file($context->id, 'mod_videoplayeray', 'content', 0, $filepath, $filename);
    if (!$file || $file->is_directory()) {
        return false;
    }
    send_stored_file($file, 0, 0, $forcedownload, $options);
}

function videoplayeray_scale_used($id, $scaleid) {
    global $DB;
    return $scaleid && $DB->record_exists('videoplayeray', ['id' => $id, 'grade' => -$scaleid]);
}

function videoplayeray_scale_used_anywhere($scaleid) {
    global $DB;
    return $scaleid && $DB->record_exists('videoplayeray', ['grade' => -$scaleid]);
}

function videoplayeray_grade_item_update(stdClass $activity, $reset = false) {
    global $CFG;
    require_once($CFG->libdir . '/gradelib.php');
    $item = ['itemname' => $activity->name];
    if ($activity->grade > 0) {
        $item += ['gradetype' => GRADE_TYPE_VALUE, 'grademax' => $activity->grade, 'grademin' => 0];
    } else if ($activity->grade < 0) {
        $item += ['gradetype' => GRADE_TYPE_SCALE, 'scaleid' => -$activity->grade];
    } else {
        $item['gradetype'] = GRADE_TYPE_NONE;
    }
    if ($reset) {
        $item['reset'] = true;
    }
    return grade_update('mod/videoplayeray', $activity->course, 'mod', 'videoplayeray', $activity->id, 0, null, $item);
}

function videoplayeray_grade_item_delete($activity) {
    global $CFG;
    require_once($CFG->libdir . '/gradelib.php');
    return grade_update('mod/videoplayeray', $activity->course, 'mod', 'videoplayeray',
        $activity->id, 0, null, ['deleted' => 1]);
}

function videoplayeray_update_grades(stdClass $activity, $userid = 0) {
    global $CFG, $DB;
    require_once($CFG->libdir . '/gradelib.php');
    require_once(__DIR__ . '/locallib.php');
    videoplayeray_grade_item_update($activity);
    if (!$activity->grade || !$cm = get_coursemodule_from_instance('videoplayeray', $activity->id)) {
        return;
    }
    $conditions = ['videoid' => $cm->id];
    if ($userid) {
        $conditions['userid'] = $userid;
    }
    $maxgrade = $activity->grade;
    if ($maxgrade < 0) {
        $scale = $DB->get_field('scale', 'scale', ['id' => -$maxgrade], MUST_EXIST);
        $maxgrade = count(explode(',', $scale));
    }
    $grades = [];
    foreach ($DB->get_records('videoplayeray_izlenme', $conditions) as $record) {
        $complete = (int)$record->izlenmesayisi >= videoplayeray_minimum_views();
        $grades[$record->userid] = (object)[
            'userid' => $record->userid,
            'rawgrade' => $complete ? $maxgrade : ($activity->grade < 0 ? null : 0),
        ];
    }
    if ($grades) {
        grade_update('mod/videoplayeray', $activity->course, 'mod', 'videoplayeray', $activity->id, 0, $grades);
    }
}
