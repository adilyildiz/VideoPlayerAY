<?php
// This file is part of Moodle - https://moodle.org/
// Licensed under the GNU GPL v3 or later: https://www.gnu.org/copyleft/gpl.html
/**
 * Dedicated timed question manager for Video Analiz.
 * @package mod_videoplayeray
 * @copyright 2017 Adil YILDIZ
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/locallib.php');

$id = required_param('id', PARAM_INT);
$qid = optional_param('qid', 0, PARAM_INT);
$action = optional_param('action', '', PARAM_ALPHA);

$cm = get_coursemodule_from_id('videoplayeray', $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
$activity = $DB->get_record('videoplayeray', ['id' => $cm->instance], '*', MUST_EXIST);

require_login($course, true, $cm);
$context = context_module::instance($cm->id);
require_capability('moodle/course:manageactivities', $context);

$baseurl = new moodle_url('/mod/videoplayeray/questions.php', ['id' => $cm->id]);

if ($action === 'delete' && $qid > 0) {
    require_sesskey();
    videoplayeray_delete_question($activity->id, $qid);
    redirect($baseurl, get_string('questiondeleted', 'videoplayeray'), null, \core\output\notification::NOTIFY_SUCCESS);
}

$editing = null;
if ($qid > 0) {
    $editing = $DB->get_record('videoplayeray_questions', ['id' => $qid, 'videoplayerayid' => $activity->id], '*', MUST_EXIST);
}

$form = new \mod_videoplayeray\form\question_form($baseurl, [
    'cmid' => $cm->id,
    'activityid' => $activity->id,
    'qid' => $editing ? (int)$editing->id : 0,
]);

if ($form->is_cancelled()) {
    redirect($baseurl);
}
if ($data = $form->get_data()) {
    videoplayeray_save_question($activity->id, $data, (int)($data->qid ?? 0));
    $msg = !empty($data->qid) ? get_string('questionupdated', 'videoplayeray') : get_string('questionadded', 'videoplayeray');
    redirect($baseurl, $msg, null, \core\output\notification::NOTIFY_SUCCESS);
}

if ($editing) {
    $defaults = [
        'id' => $cm->id,
        'qid' => (int)$editing->id,
        'activityid' => $activity->id,
        'timetrigger' => videoplayeray_format_time((int)$editing->timetrigger),
        'questiontype' => $editing->questiontype,
        'questiontext' => $editing->questiontext,
        'rewindtime' => videoplayeray_format_time((int)$editing->rewindtime),
        'required' => (int)$editing->required,
        'shuffleoptions' => (int)$editing->shuffleoptions,
        'feedbackcorrect' => (string)($editing->feedbackcorrect ?? ''),
        'feedbackwrong' => (string)($editing->feedbackwrong ?? ''),
    ];
    $opts = json_decode($editing->optionsjson, true) ?: [];
    if ($editing->questiontype === 'truefalse') {
        $defaults['truefalsecorrect'] = !empty($opts[0]['correct']) ? 1 : 0;
    } else {
        foreach ($opts as $idx => $opt) {
            if ($idx < 6) {
                $defaults['answer' . $idx] = (string)($opt['text'] ?? '');
                if (!empty($opt['correct'])) {
                    $defaults['correctanswer'] = $idx;
                }
            }
        }
    }
    $form->set_data($defaults);
}

$PAGE->set_url($baseurl);
$PAGE->set_title(get_string('managequestions', 'videoplayeray') . ': ' . format_string($activity->name));
$PAGE->set_heading(format_string($course->fullname));
videoplayeray_configure_activity_header($PAGE);

$questions = videoplayeray_get_questions($activity->id);
$files = get_file_storage()->get_area_files($context->id, 'mod_videoplayeray', 'content', 0,
    'sortorder DESC, id ASC', false);
$file = $files ? reset($files) : false;

$PAGE->requires->js_call_amd('mod_videoplayeray/questions', 'init', [[
    'questions' => array_map(static function($q) {
        return ['id' => $q['id'], 'time' => $q['time'], 'text' => $q['text']];
    }, $questions),
]]);

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('managequestions', 'videoplayeray'));
echo html_writer::div(get_string('managequestionsintro', 'videoplayeray'), 'text-muted mb-3');

if ($file) {
    $videourl = moodle_url::make_pluginfile_url($context->id, 'mod_videoplayeray', 'content', 0,
        $file->get_filepath(), $file->get_filename());
    echo html_writer::start_div('videoplayeray-qpreview mb-4');
    echo html_writer::tag('video',
        html_writer::empty_tag('source', ['src' => $videourl, 'type' => $file->get_mimetype()]),
        ['id' => 'videoplayeray-preview', 'controls' => 'controls', 'preload' => 'metadata', 'playsinline' => 'playsinline']);
    echo html_writer::start_div('videoplayeray-qtoolbar');
    echo html_writer::tag('span', get_string('currenttime', 'videoplayeray') . ': ' .
        html_writer::tag('strong', '00:00', ['id' => 'videoplayeray-curtime']) .
        ' / ' . html_writer::tag('span', '--:--', ['id' => 'videoplayeray-duration']),
        ['class' => 'videoplayeray-timebadge']);
    echo html_writer::tag('button', get_string('usecurrenttime', 'videoplayeray'), [
        'type' => 'button', 'id' => 'videoplayeray-picktime', 'class' => 'btn btn-primary btn-sm',
    ]);
    echo html_writer::tag('button', get_string('userewindtime', 'videoplayeray'), [
        'type' => 'button', 'id' => 'videoplayeray-pickrewind', 'class' => 'btn btn-outline-secondary btn-sm',
    ]);
    echo html_writer::end_div();
    echo html_writer::div('', 'videoplayeray-timeline', ['id' => 'videoplayeray-timeline', 'hidden' => 'hidden']);
    echo html_writer::end_div();
} else {
    echo $OUTPUT->notification(get_string('filenotfound', 'videoplayeray'), 'warning');
}

echo $OUTPUT->heading(get_string('questionlist', 'videoplayeray') . ' (' . count($questions) . ')', 3, 'mt-3');
if ($questions) {
    $table = new html_table();
    $table->attributes['class'] = 'generaltable videoplayeray-qtable';
    $table->head = [
        get_string('questiontime', 'videoplayeray'),
        get_string('questiontype', 'videoplayeray'),
        get_string('question', 'videoplayeray'),
        get_string('rewindtime', 'videoplayeray'),
        get_string('questionrequired', 'videoplayeray'),
        get_string('actions'),
    ];
    foreach ($questions as $q) {
        $timelabel = videoplayeray_format_time($q['time']);
        $timecell = html_writer::tag('button', s($timelabel), [
            'type' => 'button',
            'class' => 'btn btn-link p-0 videoplayeray-seek',
            'data-time' => $q['time'],
        ]) . html_writer::tag('span', ' ' . get_string('beyondduration', 'videoplayeray'), [
            'class' => 'badge badge-warning videoplayeray-overflow-warn',
            'data-time' => $q['time'],
            'hidden' => 'hidden',
        ]);
        $editurl = new moodle_url('/mod/videoplayeray/questions.php', ['id' => $cm->id, 'qid' => $q['id']]);
        $deleteurl = new moodle_url('/mod/videoplayeray/questions.php', [
            'id' => $cm->id, 'qid' => $q['id'], 'action' => 'delete', 'sesskey' => sesskey(),
        ]);
        $actions = html_writer::div(
            html_writer::link($editurl, get_string('edit'), ['class' => 'btn btn-sm btn-outline-primary']) .
            html_writer::link($deleteurl, get_string('delete'), [
                'class' => 'btn btn-sm btn-outline-danger',
                'onclick' => 'return confirm(' . json_encode(get_string('confirmdeletequestion', 'videoplayeray')) . ');',
            ]),
            'videoplayeray-table-actions'
        );
        $table->data[] = [
            $timecell,
            s(get_string('qtype_' . $q['type'], 'videoplayeray')),
            s($q['text']) . html_writer::tag('div',
                get_string('optionscount', 'videoplayeray', count($q['options'])),
                ['class' => 'small text-muted']),
            s(videoplayeray_format_time($q['rewind'])),
            s(get_string($q['required'] ? 'yes' : 'no')),
            $actions,
        ];
    }
    echo html_writer::div(html_writer::table($table), 'table-responsive mb-4');
} else {
    echo $OUTPUT->notification(get_string('noquestions', 'videoplayeray'), 'info');
}

echo html_writer::start_div('videoplayeray-qform-wrap', ['id' => 'videoplayeray-qform']);
$form->display();
echo html_writer::end_div();

echo $OUTPUT->footer();
