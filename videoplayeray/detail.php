<?php
// This file is part of Moodle - https://moodle.org/
/**
 * Participant playback and question history.
 * @package mod_videoplayeray
 * @copyright 2017 Adil YILDIZ
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/locallib.php');

$id = required_param('id', PARAM_INT);
$userid = required_param('userid', PARAM_INT);
$page = max(0, optional_param('page', 0, PARAM_INT));
$cm = get_coursemodule_from_id('videoplayeray', $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
require_login($course, true, $cm);
$context = context_module::instance($id);
require_capability('mod/videoplayeray:viewreports', $context);

if (groups_get_activity_groupmode($cm) == SEPARATEGROUPS &&
        !has_capability('moodle/site:accessallgroups', $context)) {
    $mine = groups_get_all_groups($course->id, $USER->id, $cm->groupingid);
    $theirs = groups_get_all_groups($course->id, $userid, $cm->groupingid);
    if (!array_intersect_key($mine, $theirs)) {
        throw new moodle_exception('nopermissions', 'error', '', 'view group reports');
    }
}

$user = $DB->get_record('user', ['id' => $userid, 'deleted' => 0], '*', MUST_EXIST);
$PAGE->set_url('/mod/videoplayeray/detail.php', ['id' => $id, 'userid' => $userid]);
$PAGE->set_title(get_string('participantdetail', 'videoplayeray'));
$PAGE->set_heading(format_string($course->fullname));
videoplayeray_configure_activity_header($PAGE);

echo $OUTPUT->header();
echo html_writer::link(new moodle_url('/mod/videoplayeray/report.php', ['id' => $id]),
    get_string('report', 'videoplayeray'), ['class' => 'btn btn-outline-secondary mb-3']);
echo $OUTPUT->heading(s(fullname($user)));

$record = $DB->get_record('videoplayeray_izlenme', ['videoid' => $id, 'userid' => $userid]);
$qstats = videoplayeray_question_stats($id, $cm->instance);
$ustat = $qstats['users'][$userid] ?? null;
$qsummary = $qstats['total'] > 0
    ? (' · ' . get_string('questionssolved', 'videoplayeray') . ': ' . ($ustat ? count($ustat['solved']) : 0) . ' / ' . $qstats['total'])
    : '';

echo html_writer::div(
    get_string('views', 'videoplayeray') . ': ' . ($record ? (int)$record->izlenmesayisi : 0) .
    ' · ' . get_string('progress', 'videoplayeray') . ': ' .
    ($record ? round(videoplayeray_progress_percent($record->izlenme)) : 0) . '%' . $qsummary,
    'videoplayeray-reportsummary'
);

$conditions = ['videoid' => $id, 'userid' => $userid];
$count = $DB->count_records('videoplayeray_olaylar', $conditions);
$page = min($page, max(0, (int)ceil($count / 50) - 1));
$events = $DB->get_records('videoplayeray_olaylar', $conditions, 'id DESC', '*', $page * 50, 50);

if ($events) {
    $table = new html_table();
    $table->head = [
        get_string('time'),
        get_string('event', 'videoplayeray'),
        get_string('question', 'videoplayeray'),
        get_string('answer', 'videoplayeray'),
        get_string('result', 'videoplayeray'),
    ];
    foreach ($events as $event) {
        $time = ctype_digit((string)$event->resim) ? userdate((int)$event->resim) : '—';
        $result = $event->tur === 'cevapverildi' ? get_string(
            $event->durum == 1 ? 'correct' : 'incorrect', 'videoplayeray') : '—';
        $qtext = (string)($event->soru ?? '');
        if ($qtext === '' && !empty($event->questionid) && isset($qstats['questions'][(int)$event->questionid])) {
            $qtext = $qstats['questions'][(int)$event->questionid]['text'];
        }
        $table->data[] = [
            s($time),
            s(get_string('event_' . $event->tur, 'videoplayeray')),
            s($qtext),
            s($event->cevap ?? ''),
            s($result),
        ];
    }
    echo html_writer::div(html_writer::table($table), 'table-responsive');
    echo $OUTPUT->paging_bar($count, $page, 50, $PAGE->url);
} else {
    echo $OUTPUT->notification(get_string('noevents', 'videoplayeray'), 'info');
}
echo $OUTPUT->footer();
