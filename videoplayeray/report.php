<?php
// This file is part of Moodle - https://moodle.org/
/**
 * Video Analiz activity report.
 * @package mod_videoplayeray
 * @copyright 2017 Adil YILDIZ
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/locallib.php');

$id = required_param('id', PARAM_INT);
$cm = get_coursemodule_from_id('videoplayeray', $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
require_login($course, true, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/videoplayeray:viewreports', $context);

$allcourses = optional_param('tumdersraporu', false, PARAM_BOOL);
$coursewide = optional_param('dersraporu', false, PARAM_BOOL);
$search = optional_param('search', '', PARAM_TEXT);
$state = optional_param('state', 'all', PARAM_ALPHA);
$download = optional_param('download', false, PARAM_BOOL);
$page = max(0, optional_param('page', 0, PARAM_INT));
if ($allcourses) {
    require_capability('moodle/site:config', context_system::instance());
}

$params = ['id' => $id, 'dersraporu' => (int)$coursewide, 'tumdersraporu' => (int)$allcourses,
    'search' => $search, 'state' => $state];
$PAGE->set_url('/mod/videoplayeray/report.php', $params);
$PAGE->set_title(get_string('report', 'videoplayeray'));
$PAGE->set_heading(format_string($course->fullname));

$courses = [$course->id => $course];
if ($allcourses) {
    $courses = $DB->get_records_sql('SELECT DISTINCT c.* FROM {course} c
        JOIN {videoplayeray} v ON v.course = c.id ORDER BY c.id');
}

$rows = [];
$detailurls = [];
$total = 0;
$finished = 0;
$singlequestionstats = [];

foreach ($courses as $reportcourse) {
    $modinfo = get_fast_modinfo($reportcourse);
    $modules = $allcourses || $coursewide ? ($modinfo->instances['videoplayeray'] ?? []) : [$modinfo->get_cm($cm->id)];
    foreach ($modules as $module) {
        if (!empty($module->deletioninprogress)) {
            continue;
        }
        $modulecontext = context_module::instance($module->id);
        if (!has_capability('mod/videoplayeray:viewreports', $modulecontext)) {
            continue;
        }
        $activity = $DB->get_record('videoplayeray', ['id' => $module->instance], '*', MUST_EXIST);
        $records = $DB->get_records('videoplayeray_izlenme', ['videoid' => $module->id], '',
            'userid, id, izlenmesayisi, izlenme, answered');
        $qstats = videoplayeray_question_stats($module->id, $activity->id);
        if (!$allcourses && !$coursewide) {
            $singlequestionstats = $qstats['questions'];
        }
        $groupid = 0;
        if (groups_get_activity_groupmode($module) == SEPARATEGROUPS &&
                !has_capability('moodle/site:accessallgroups', $modulecontext)) {
            $groupid = groups_get_activity_group($module, true);
            if (!$groupid) {
                continue;
            }
        }
        $users = get_enrolled_users($modulecontext, 'mod/videoplayeray:submit', $groupid,
            'u.id, u.firstname, u.lastname, u.firstnamephonetic, u.lastnamephonetic, u.middlename, u.alternatename',
            'u.lastname, u.firstname');
        foreach ($users as $user) {
            $name = fullname($user);
            if ($search !== '' && core_text::strpos(core_text::strtolower($name), core_text::strtolower($search)) === false) {
                continue;
            }
            $record = $records[$user->id] ?? null;
            $views = $record ? (int)$record->izlenmesayisi : 0;
            $complete = $views >= videoplayeray_minimum_views();
            if (($state === 'complete' && !$complete) || ($state === 'incomplete' && $complete)) {
                continue;
            }
            $percent = $record ? round(videoplayeray_progress_percent($record->izlenme)) : 0;
            $ustat = $qstats['users'][$user->id] ?? null;
            if ($qstats['total'] > 0) {
                $solved = $ustat ? count($ustat['solved']) : 0;
                $qcell = $solved . ' / ' . $qstats['total'];
            } else {
                $qcell = '—';
            }
            $rows[] = [$reportcourse->fullname, $activity->name, $name, $views, $percent . '%', $qcell,
                get_string($complete ? 'complete' : 'incomplete', 'videoplayeray')];
            $detailurls[] = new moodle_url('/mod/videoplayeray/detail.php', ['id' => $module->id, 'userid' => $user->id]);
            $total++;
            $finished += (int)$complete;
        }
    }
}

$head = [
    get_string('course'),
    get_string('modulename', 'videoplayeray'),
    get_string('fullname'),
    get_string('views', 'videoplayeray'),
    get_string('progress', 'videoplayeray'),
    get_string('questionssolved', 'videoplayeray'),
    get_string('status'),
];

if ($download) {
    require_sesskey();
    require_once($CFG->libdir . '/csvlib.class.php');
    $csv = new csv_export_writer();
    $csv->set_filename('videoplayeray-report');
    $csv->add_data($head);
    foreach ($rows as $row) {
        // Prevent spreadsheet formula execution when opening user-entered names.
        $csv->add_data(array_map(static function($value) {
            $value = (string)$value;
            return preg_match('/^[\s]*[=+@-]/u', $value) ? "'" . $value : $value;
        }, $row));
    }
    $csv->download_file();
    exit;
}

videoplayeray_configure_activity_header($PAGE);
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('report', 'videoplayeray'));
echo html_writer::div(get_string('reportintro', 'videoplayeray'), 'text-muted mb-4');

$links = [];
foreach (['report' => [], 'coursereport' => ['dersraporu' => 1]] as $label => $scope) {
    $links[] = html_writer::link(new moodle_url('/mod/videoplayeray/report.php', ['id' => $id] + $scope),
        get_string($label, 'videoplayeray'), ['class' => 'btn btn-outline-secondary']);
}
if (has_capability('moodle/site:config', context_system::instance())) {
    $links[] = html_writer::link(new moodle_url('/mod/videoplayeray/report.php',
        ['id' => $id, 'tumdersraporu' => 1]), get_string('allcoursesreport', 'videoplayeray'),
        ['class' => 'btn btn-outline-secondary']);
}
echo html_writer::div(implode(' ', $links), 'mb-3');

echo html_writer::start_tag('form', ['method' => 'get', 'class' => 'videoplayeray-filters']);
foreach (['id', 'dersraporu', 'tumdersraporu'] as $key) {
    echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => $key, 'value' => $params[$key]]);
}
echo html_writer::tag('label', get_string('searchuser', 'videoplayeray'), ['for' => 'report-search']);
echo html_writer::empty_tag('input', ['id' => 'report-search', 'type' => 'search', 'name' => 'search',
    'value' => $search, 'class' => 'form-control']);
echo html_writer::tag('label', get_string('status'), ['for' => 'report-state']);
echo html_writer::select(['all' => get_string('all'), 'complete' => get_string('complete', 'videoplayeray'),
    'incomplete' => get_string('incomplete', 'videoplayeray')], 'state', $state, false, ['id' => 'report-state']);
echo html_writer::tag('button', get_string('filter'), ['type' => 'submit', 'class' => 'btn btn-primary']);
echo html_writer::end_tag('form');

echo html_writer::div(get_string('reportsummary', 'videoplayeray', (object)['total' => $total, 'complete' => $finished]),
    'videoplayeray-reportsummary');
echo html_writer::link(new moodle_url('/mod/videoplayeray/report.php', $params + ['download' => 1, 'sesskey' => sesskey()]),
    get_string('downloadcsv', 'videoplayeray'), ['class' => 'btn btn-outline-secondary mb-3']);

if ($rows) {
    $table = new html_table();
    $table->head = $head;
    $page = min($page, max(0, (int)ceil(count($rows) / 50) - 1));
    foreach (array_slice($rows, $page * 50, 50, true) as $index => $row) {
        $row = array_map('s', $row);
        $row[2] = html_writer::link($detailurls[$index], $row[2]);
        $table->data[] = $row;
    }
    echo html_writer::div(html_writer::table($table), 'table-responsive');
    echo $OUTPUT->paging_bar(count($rows), $page, 50, new moodle_url('/mod/videoplayeray/report.php', $params));
} else {
    echo $OUTPUT->notification(get_string('noreportrows', 'videoplayeray'), 'info');
}

if ($singlequestionstats) {
    echo $OUTPUT->heading(get_string('questionanalytics', 'videoplayeray'), 3, 'mt-4');
    $qtable = new html_table();
    $qtable->head = [
        get_string('questiontime', 'videoplayeray'),
        get_string('question', 'videoplayeray'),
        get_string('attempts', 'videoplayeray'),
        get_string('correct', 'videoplayeray'),
        get_string('incorrect', 'videoplayeray'),
        get_string('successrate', 'videoplayeray'),
    ];
    foreach ($singlequestionstats as $qs) {
        $rate = $qs['attempts'] > 0 ? round(100 * $qs['correct'] / $qs['attempts']) . '%' : '—';
        $qtable->data[] = [
            s(videoplayeray_format_time($qs['time'])),
            s($qs['text']),
            (int)$qs['attempts'],
            (int)$qs['correct'],
            (int)$qs['wrong'],
            $rate,
        ];
    }
    echo html_writer::div(html_writer::table($qtable), 'table-responsive');
}

echo $OUTPUT->footer();
