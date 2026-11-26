<?php
// This file is part of Moodle - https://moodle.org/
// Licensed under the GNU GPL v3 or later: https://www.gnu.org/copyleft/gpl.html
/**
 * Video Analiz activity view page.
 * @package mod_videoplayeray
 * @copyright 2017 Adil YILDIZ
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/lib.php');
require_once(__DIR__ . '/locallib.php');
require_once($CFG->libdir . '/completionlib.php');

$id = optional_param('id', 0, PARAM_INT);
$n = optional_param('n', 0, PARAM_INT);
if ($id) {
    $cm = get_coursemodule_from_id('videoplayeray', $id, 0, false, MUST_EXIST);
} else {
    $cm = get_coursemodule_from_instance('videoplayeray', $n, 0, false, MUST_EXIST);
}
$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
$activity = $DB->get_record('videoplayeray', ['id' => $cm->instance], '*', MUST_EXIST);
require_login($course, true, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/videoplayeray:view', $context);

$PAGE->set_url('/mod/videoplayeray/view.php', ['id' => $cm->id]);
$PAGE->set_title(format_string($activity->name));
$PAGE->set_heading(format_string($course->fullname));

$event = \mod_videoplayeray\event\course_module_viewed::create([
    'objectid' => $activity->id,
    'context' => $context,
]);
$event->add_record_snapshot('videoplayeray', $activity);
$event->trigger();

$completion = new completion_info($course);
$completion->set_module_viewed($cm);

$files = get_file_storage()->get_area_files($context->id, 'mod_videoplayeray', 'content', 0,
    'sortorder DESC, id ASC', false);
$file = $files ? reset($files) : false;

// Preserve the legacy olaylar.js question format. Only HTTP(S) URLs are executable.
$overlay = trim((string)$activity->overlayjs);
if ($overlay && preg_match('~^https?://~i', $overlay) && clean_param($overlay, PARAM_URL) === $overlay) {
    $PAGE->requires->js(new moodle_url($overlay));
}

$questions = videoplayeray_get_questions($activity->id);
$legacyquestions = (object)(json_decode((string)($activity->questions ?? '[]'), true) ?: []);
$playerquestions = $questions ?: $legacyquestions;

$PAGE->requires->js_call_amd('mod_videoplayeray/player', 'init', [[
    'cmid' => $cm->id,
    'questions' => $playerquestions,
    'track' => is_enrolled($context, null, '', true) && !isguestuser(),
]]);

echo $OUTPUT->header();

// Moodle 4.0+ renders the activity intro via activityheader; render manually on Moodle 3.9–3.11.
if (!method_exists($PAGE, 'magic_get_activityheader') && !empty($activity->intro)) {
    echo $OUTPUT->box(format_module_intro('videoplayeray', $activity, $cm->id), 'generalbox mod_introbox', 'videoplayerayintro');
}

$actionlinks = [];
if (has_capability('moodle/course:manageactivities', $context)) {
    $actionlinks[] = html_writer::link(new moodle_url('/mod/videoplayeray/questions.php', ['id' => $cm->id]),
        get_string('managequestions', 'videoplayeray'), ['class' => 'btn btn-outline-primary btn-sm']);
}
if (has_capability('mod/videoplayeray:viewreports', $context)) {
    $actionlinks[] = html_writer::link(new moodle_url('/mod/videoplayeray/report.php', ['id' => $cm->id]),
        get_string('report', 'videoplayeray'), ['class' => 'btn btn-outline-secondary btn-sm']);
}
if ($actionlinks) {
    echo html_writer::div(implode(' ', $actionlinks), 'mb-3 videoplayeray-actionbar');
}

if ($file) {
    $url = moodle_url::make_pluginfile_url($context->id, 'mod_videoplayeray', 'content', 0,
        $file->get_filepath(), $file->get_filename());
    $record = $DB->get_record('videoplayeray_izlenme', ['videoid' => $cm->id, 'userid' => $USER->id]);
    $answeredkeys = $record ? array_map('strval', videoplayeray_answered_array($record->answered ?? '[]')) : [];

    echo html_writer::start_div('videoplayeray-workspace');
    echo html_writer::start_div('videoplayeray-stage');
    echo html_writer::start_div('videoplayeray-player', ['id' => 'videoplayeray-player']);
    echo html_writer::tag('video', html_writer::empty_tag('source', ['src' => $url, 'type' => $file->get_mimetype()]),
        ['id' => 'videooynatici', 'controls' => 'controls', 'preload' => 'metadata',
         'playsinline' => 'playsinline', 'aria-label' => format_string($activity->name)]);
    echo html_writer::div('', 'videoplayeray-toast', ['id' => 'videoplayeray-toast', 'role' => 'status', 'hidden' => 'hidden']);
    echo html_writer::div('', 'videoplayeray-question', ['id' => 'sorualani', 'hidden' => 'hidden',
        'role' => 'dialog', 'aria-modal' => 'true', 'aria-label' => get_string('question', 'videoplayeray')]);
    echo html_writer::end_div();
    echo html_writer::end_div();

    echo html_writer::start_tag('aside', ['class' => 'videoplayeray-learning',
        'aria-label' => get_string('learningprogress', 'videoplayeray')]);
    echo html_writer::tag('span', get_string('learningprogress', 'videoplayeray'), ['class' => 'videoplayeray-eyebrow']);
    echo html_writer::tag('strong', '0%', ['id' => 'videoplayeray-percent', 'class' => 'videoplayeray-percent']);
    echo html_writer::tag('progress', '', ['id' => 'videoplayeray-progress', 'max' => 100, 'value' => 0,
        'aria-label' => get_string('progress', 'videoplayeray')]);
    echo html_writer::tag('p', get_string('viewinggoal', 'videoplayeray', (object)[
        'percent' => videoplayeray_threshold(), 'count' => videoplayeray_minimum_views()]));
    echo html_writer::div(html_writer::tag('strong', $record ? (int)$record->izlenmesayisi : 0,
        ['id' => 'videoplayeray-views']) . ' ' . get_string('views', 'videoplayeray'), 'videoplayeray-viewcount');

    if ($questions) {
        $solvedcount = 0;
        foreach ($questions as $q) {
            if (in_array((string)$q['id'], $answeredkeys, true)) {
                $solvedcount++;
            }
        }
        echo html_writer::start_div('videoplayeray-qsummary');
        echo html_writer::tag('div', get_string('questionsprogress', 'videoplayeray') . ': ' .
            html_writer::tag('strong', $solvedcount . ' / ' . count($questions), ['id' => 'videoplayeray-qprogress']),
            ['class' => 'mb-2']);
        echo html_writer::start_tag('ul', ['class' => 'videoplayeray-qchiplist']);
        foreach ($questions as $idx => $q) {
            $cls = in_array((string)$q['id'], $answeredkeys, true) ? 'videoplayeray-qchip is-answered' : 'videoplayeray-qchip';
            echo html_writer::tag('li',
                s(videoplayeray_format_time($q['time'])) . ' · #' . ($idx + 1),
                ['class' => $cls, 'data-qkey' => (string)$q['id'], 'title' => $q['text']]);
        }
        echo html_writer::end_tag('ul');
        echo html_writer::end_div();
    }

    echo html_writer::tag('button', get_string('resume', 'videoplayeray'), ['id' => 'videoplayeray-resume',
        'type' => 'button', 'class' => 'btn btn-primary', 'hidden' => 'hidden']);
    echo html_writer::tag('p', isguestuser() || !is_enrolled($context, null, '', true) ?
        get_string('previewmode', 'videoplayeray') : get_string('autosave', 'videoplayeray'),
        ['id' => 'videoplayeray-saved', 'class' => 'small text-muted mt-3', 'role' => 'status']);
    echo html_writer::end_tag('aside');
    echo html_writer::end_div();
    echo html_writer::div('', 'alert alert-info mt-3', ['id' => 'videoplayeray-status', 'role' => 'status', 'hidden' => 'hidden']);
} else {
    echo $OUTPUT->notification(get_string('filenotfound', 'videoplayeray'), 'warning');
}
echo $OUTPUT->footer();
