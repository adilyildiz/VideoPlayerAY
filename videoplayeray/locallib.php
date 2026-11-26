<?php
// This file is part of Moodle - https://moodle.org/
// Licensed under the GNU GPL v3 or later: https://www.gnu.org/copyleft/gpl.html
/**
 * Video Analiz activity helpers.
 * @package mod_videoplayeray
 * @copyright 2017 Adil YILDIZ
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

function videoplayeray_set_mainfile($data) {
    if (!isset($data->files)) {
        return;
    }
    $context = context_module::instance($data->coursemodule);
    file_save_draft_area_files($data->files, $context->id, 'mod_videoplayeray', 'content', 0,
        ['subdirs' => false, 'maxfiles' => 1, 'accepted_types' => ['.mp4', '.webm', '.ogv']]);
}

/** PHP 7.2–8.0 compatible array_is_list helper. */
function videoplayeray_is_list(array $data): bool {
    if (function_exists('array_is_list')) {
        return array_is_list($data);
    }
    $i = 0;
    foreach ($data as $key => $_) {
        if ($key !== $i++) {
            return false;
        }
    }
    return true;
}

/** Hide duplicate activity header description/completion on Moodle 4.0+ (no-op on 3.9–3.11). */
function videoplayeray_configure_activity_header(moodle_page $page): void {
    if (method_exists($page, 'magic_get_activityheader')) {
        $page->activityheader->set_attrs(['description' => '', 'hidecompletion' => true]);
    }
}

function videoplayeray_progress_array($json) {
    if (strlen((string)$json) > 400000) {
        return [];
    }
    $data = json_decode((string)$json, true);
    if (!is_array($data) || !videoplayeray_is_list($data) || count($data) > 86400) {
        return [];
    }
    foreach ($data as $value) {
        if (!in_array($value, [0, 1, '0', '1'], true)) {
            return [];
        }
    }
    return array_map('intval', $data);
}

function videoplayeray_progress_percent($json) {
    $data = videoplayeray_progress_array($json);
    return $data ? 100 * array_sum($data) / count($data) : 0;
}

function videoplayeray_answered_array($json): array {
    if (strlen((string)$json) > 100000) {
        return [];
    }
    $data = json_decode((string)$json, true);
    if (!is_array($data) || !videoplayeray_is_list($data) || count($data) > 500) {
        return [];
    }
    $clean = [];
    foreach ($data as $item) {
        if (is_int($item) || (is_string($item) && preg_match('/^(t?\d+)$/', $item))) {
            $clean[] = is_int($item) ? $item : (ctype_digit($item) ? (int)$item : $item);
        }
    }
    return array_values(array_unique($clean, SORT_REGULAR));
}

function videoplayeray_minimum_views() {
    $value = get_config('videoplayeray', 'videoizlenmealtsinir');
    return max(1, $value === false ? 1 : (int)$value);
}

function videoplayeray_threshold() {
    $value = get_config('videoplayeray', 'videoizlenmeoranaltsinir');
    return $value === false ? 95 : max(1, min(100, (int)$value));
}

/** Parse seconds, mm:ss, or hh:mm:ss into an integer timestamp in 0..86399. */
function videoplayeray_parse_time($input): ?int {
    $raw = trim((string)$input);
    if ($raw === '') {
        return null;
    }
    if (ctype_digit($raw)) {
        $seconds = (int)$raw;
        return ($seconds >= 0 && $seconds <= 86399) ? $seconds : null;
    }
    $parts = explode(':', $raw);
    if (count($parts) === 2 && ctype_digit($parts[0]) && ctype_digit($parts[1])) {
        $min = (int)$parts[0];
        $sec = (int)$parts[1];
        if ($sec > 59) {
            return null;
        }
        $total = $min * 60 + $sec;
        return ($total >= 0 && $total <= 86399) ? $total : null;
    }
    if (count($parts) === 3 && ctype_digit($parts[0]) && ctype_digit($parts[1]) && ctype_digit($parts[2])) {
        $hr = (int)$parts[0];
        $min = (int)$parts[1];
        $sec = (int)$parts[2];
        if ($min > 59 || $sec > 59) {
            return null;
        }
        $total = $hr * 3600 + $min * 60 + $sec;
        return ($total >= 0 && $total <= 86399) ? $total : null;
    }
    return null;
}

/** Format integer seconds into mm:ss or hh:mm:ss. */
function videoplayeray_format_time(int $seconds): string {
    $seconds = max(0, $seconds);
    $hours = intdiv($seconds, 3600);
    $minutes = intdiv($seconds % 3600, 60);
    $secs = $seconds % 60;
    if ($hours > 0) {
        return sprintf('%02d:%02d:%02d', $hours, $minutes, $secs);
    }
    return sprintf('%02d:%02d', $minutes, $secs);
}

/** Require an authenticated, enrolled participant and a CSRF protected POST. */
function videoplayeray_ajax_context($parameter = 'videoid') {
    global $DB;
    $id = required_param($parameter, PARAM_INT);
    $cm = get_coursemodule_from_id('videoplayeray', $id, 0, false, MUST_EXIST);
    $course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
    require_login($course, false, $cm);
    $context = context_module::instance($cm->id);
    require_capability('mod/videoplayeray:view', $context);
    if (!is_enrolled($context, null, '', true)) {
        throw new moodle_exception('notenrolled', 'videoplayeray');
    }
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
        throw new moodle_exception('invalidrequest', 'error');
    }
    require_sesskey();
    header('Content-Type: application/json; charset=utf-8');
    return [$cm, $context];
}

/** Migrate legacy JSON questions in videoplayeray.questions into videoplayeray_questions (idempotent). */
function videoplayeray_migrate_legacy_questions(stdClass $activity): int {
    global $DB;
    if ($DB->record_exists('videoplayeray_questions', ['videoplayerayid' => $activity->id])) {
        return 0;
    }
    $decoded = json_decode((string)($activity->questions ?? ''), true);
    if (!is_array($decoded) || empty($decoded)) {
        return 0;
    }
    $now = time();
    $sort = 0;
    $inserted = 0;
    foreach ($decoded as $sec => $item) {
        if (!is_numeric($sec) || !is_array($item) || count($item) < 2 || !is_array($item[1])) {
            continue;
        }
        $options = [];
        foreach ($item[1] as $opt) {
            if (is_array($opt) && isset($opt[0]) && trim((string)$opt[0]) !== '') {
                $options[] = ['text' => trim((string)$opt[0]), 'correct' => !empty($opt[1])];
            }
        }
        if (count($options) < 2) {
            continue;
        }
        $time = max(0, min(86399, (int)$sec));
        $rewind = max(0, min($time, (int)($item[2] ?? 0)));
        $DB->insert_record('videoplayeray_questions', (object)[
            'videoplayerayid' => $activity->id,
            'timetrigger' => $time,
            'questiontype' => 'multichoice',
            'questiontext' => trim((string)$item[0]),
            'questionformat' => FORMAT_PLAIN,
            'optionsjson' => json_encode($options, JSON_UNESCAPED_UNICODE),
            'rewindtime' => $rewind,
            'required' => 1,
            'shuffleoptions' => 0,
            'feedbackcorrect' => '',
            'feedbackwrong' => '',
            'sortorder' => $sort++,
            'timecreated' => $now,
            'timemodified' => $now,
        ]);
        $inserted++;
    }
    return $inserted;
}

/** Keep legacy videoplayeray.questions JSON column synchronized with videoplayeray_questions. */
function videoplayeray_sync_questions_json(int $activityid): void {
    global $DB;
    $rows = $DB->get_records('videoplayeray_questions', ['videoplayerayid' => $activityid], 'timetrigger ASC, sortorder ASC, id ASC');
    $legacy = [];
    foreach ($rows as $row) {
        $options = json_decode($row->optionsjson, true);
        if (!is_array($options)) {
            continue;
        }
        $pairs = [];
        foreach ($options as $opt) {
            $pairs[] = [(string)($opt['text'] ?? ''), !empty($opt['correct'])];
        }
        $legacy[(int)$row->timetrigger] = [(string)$row->questiontext, $pairs, (int)$row->rewindtime];
    }
    ksort($legacy, SORT_NUMERIC);
    $DB->set_field('videoplayeray', 'questions', json_encode((object)$legacy, JSON_UNESCAPED_UNICODE), ['id' => $activityid]);
}

/** Fetch structured questions for player and management views. */
function videoplayeray_get_questions(int $activityid): array {
    global $DB;
    $rows = $DB->get_records('videoplayeray_questions', ['videoplayerayid' => $activityid], 'timetrigger ASC, sortorder ASC, id ASC');
    $result = [];
    foreach ($rows as $row) {
        $options = json_decode($row->optionsjson, true);
        if (!is_array($options) || count($options) < 2) {
            continue;
        }
        $cleanopts = [];
        foreach ($options as $opt) {
            if (isset($opt['text']) && trim((string)$opt['text']) !== '') {
                $cleanopts[] = ['text' => (string)$opt['text'], 'correct' => !empty($opt['correct'])];
            }
        }
        if (count($cleanopts) < 2) {
            continue;
        }
        $result[] = [
            'id' => (int)$row->id,
            'time' => (int)$row->timetrigger,
            'type' => (string)$row->questiontype,
            'text' => (string)$row->questiontext,
            'options' => $cleanopts,
            'rewind' => (int)$row->rewindtime,
            'required' => (bool)$row->required,
            'shuffle' => (bool)$row->shuffleoptions,
            'feedbackCorrect' => (string)($row->feedbackcorrect ?? ''),
            'feedbackWrong' => (string)($row->feedbackwrong ?? ''),
        ];
    }
    return $result;
}

/** Save (insert or update) a question from the question_form data. */
function videoplayeray_save_question(int $activityid, stdClass $data, int $questionid = 0): int {
    global $DB;
    $time = videoplayeray_parse_time($data->timetrigger ?? 0);
    $rewind = videoplayeray_parse_time($data->rewindtime ?? 0);
    $type = ($data->questiontype ?? 'multichoice') === 'truefalse' ? 'truefalse' : 'multichoice';
    $options = [];
    if ($type === 'truefalse') {
        $iscorrecttrue = ((int)($data->truefalsecorrect ?? 1)) === 1;
        $options[] = ['text' => get_string('optiontrue', 'videoplayeray'), 'correct' => $iscorrecttrue];
        $options[] = ['text' => get_string('optionfalse', 'videoplayeray'), 'correct' => !$iscorrecttrue];
    } else {
        $selected = (int)($data->correctanswer ?? 0);
        for ($i = 0; $i < 6; $i++) {
            $text = trim((string)($data->{'answer' . $i} ?? ''));
            if ($text !== '') {
                $options[] = ['text' => $text, 'correct' => ($selected === $i)];
            }
        }
    }
    $now = time();
    $record = (object)[
        'videoplayerayid' => $activityid,
        'timetrigger' => $time ?? 0,
        'questiontype' => $type,
        'questiontext' => trim((string)($data->questiontext ?? '')),
        'questionformat' => FORMAT_PLAIN,
        'optionsjson' => json_encode($options, JSON_UNESCAPED_UNICODE),
        'rewindtime' => $rewind ?? 0,
        'required' => !empty($data->required) ? 1 : 0,
        'shuffleoptions' => ($type === 'multichoice' && !empty($data->shuffleoptions)) ? 1 : 0,
        'feedbackcorrect' => trim((string)($data->feedbackcorrect ?? '')),
        'feedbackwrong' => trim((string)($data->feedbackwrong ?? '')),
        'timemodified' => $now,
    ];
    if ($questionid > 0) {
        $existing = $DB->get_record('videoplayeray_questions', ['id' => $questionid, 'videoplayerayid' => $activityid], '*', MUST_EXIST);
        $record->id = $existing->id;
        $record->sortorder = $existing->sortorder;
        $record->timecreated = $existing->timecreated;
        $DB->update_record('videoplayeray_questions', $record);
        $id = (int)$existing->id;
    } else {
        $record->sortorder = (int)$DB->count_records('videoplayeray_questions', ['videoplayerayid' => $activityid]);
        $record->timecreated = $now;
        $id = (int)$DB->insert_record('videoplayeray_questions', $record);
    }
    videoplayeray_sync_questions_json($activityid);
    return $id;
}

/** Delete a question belonging to an activity. */
function videoplayeray_delete_question(int $activityid, int $questionid): bool {
    global $DB;
    $deleted = $DB->delete_records('videoplayeray_questions', ['id' => $questionid, 'videoplayerayid' => $activityid]);
    videoplayeray_sync_questions_json($activityid);
    return $deleted;
}

/** Return IDs of required questions not yet answered correctly in the current viewing cycle. */
function videoplayeray_missing_required_questions(int $activityid, array $answered): array {
    $questions = videoplayeray_get_questions($activityid);
    $answeredmap = [];
    foreach ($answered as $key) {
        $answeredmap[(string)$key] = true;
    }
    $missing = [];
    foreach ($questions as $q) {
        if ($q['required'] && empty($answeredmap[(string)$q['id']])) {
            $missing[] = $q['id'];
        }
    }
    return $missing;
}

/** Compute per-user and per-question answer statistics for reports. */
function videoplayeray_question_stats(int $cmid, int $activityid): array {
    global $DB;
    $questions = videoplayeray_get_questions($activityid);
    $totalquestions = count($questions);
    $byquestion = [];
    foreach ($questions as $q) {
        $byquestion[$q['id']] = [
            'id' => $q['id'],
            'time' => $q['time'],
            'text' => $q['text'],
            'required' => $q['required'],
            'attempts' => 0,
            'correct' => 0,
            'wrong' => 0,
        ];
    }
    $byuser = [];
    $events = $DB->get_records('videoplayeray_olaylar', ['videoid' => $cmid, 'tur' => 'cevapverildi'],
        'id ASC', 'id, userid, questionid, soru, durum');
    foreach ($events as $event) {
        $uid = (int)$event->userid;
        $qid = (int)($event->questionid ?? 0);
        $iscorrect = ((string)$event->durum === '1');
        if (!isset($byuser[$uid])) {
            $byuser[$uid] = ['attempts' => 0, 'correct' => 0, 'wrong' => 0, 'solved' => [], 'total' => $totalquestions];
        }
        $byuser[$uid]['attempts']++;
        if ($iscorrect) {
            $byuser[$uid]['correct']++;
            $key = $qid > 0 ? (string)$qid : trim((string)$event->soru);
            if ($key !== '') {
                $byuser[$uid]['solved'][$key] = true;
            }
        } else {
            $byuser[$uid]['wrong']++;
        }
        if ($qid > 0 && isset($byquestion[$qid])) {
            $byquestion[$qid]['attempts']++;
            if ($iscorrect) {
                $byquestion[$qid]['correct']++;
            } else {
                $byquestion[$qid]['wrong']++;
            }
        }
    }
    return ['users' => $byuser, 'questions' => $byquestion, 'total' => $totalquestions];
}

/** Build the historical timestamp -> [question, answers, rewind] structure without executable code. */
function videoplayeray_questions_from_form($data) {
    $questions = [];
    $errors = [];
    $texts = $data->questiontext ?? [];
    if (count($texts) > 100) {
        return [[], ['questiontext[0]' => get_string('toomanyquestions', 'videoplayeray')]];
    }
    foreach ($texts as $index => $text) {
        if (!empty($data->deletequestion[$index])) {
            continue;
        }
        $text = trim($text);
        $answers = [];
        $selected = (int)($data->correctanswer[$index] ?? 0);
        $hascontent = $text !== '';
        for ($i = 0; $i < 4; $i++) {
            $answer = trim($data->{'answer' . $i}[$index] ?? '');
            $hascontent = $hascontent || $answer !== '';
            if ($answer !== '') {
                $answers[] = [$answer, $selected === $i];
            }
        }
        if (!$hascontent) {
            continue;
        }
        $time = videoplayeray_parse_time($data->questiontime[$index] ?? 0);
        $rewind = videoplayeray_parse_time($data->rewindtime[$index] ?? 0);
        if ($time === null || isset($questions[$time])) {
            $errors['questiontime[' . $index . ']'] = get_string('invalidquestiontime', 'videoplayeray');
        }
        if ($rewind === null || ($time !== null && $rewind > $time)) {
            $errors['rewindtime[' . $index . ']'] = get_string('invalidrewind', 'videoplayeray');
        }
        if ($text === '' || count($answers) < 2 || !in_array(true, array_column($answers, 1), true)) {
            $errors['questiontext[' . $index . ']'] = get_string('invalidquestion', 'videoplayeray');
        }
        if ($time !== null) {
            $questions[$time] = [$text, $answers, $rewind ?? 0];
        }
    }
    ksort($questions, SORT_NUMERIC);
    return [$questions, $errors];
}
