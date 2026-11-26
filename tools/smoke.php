<?php
// Run only against the isolated disposable Moodle QA installation.
define('CLI_SCRIPT', true);
$moodleroot = $argv[1] ?? '';
require($moodleroot . '/config.php');
$CFG->debug = DEBUG_DEVELOPER;
$CFG->debugdisplay = true;
require_once($CFG->dirroot . '/course/lib.php');
require_once($CFG->dirroot . '/course/modlib.php');
require_once($CFG->dirroot . '/user/lib.php');
require_once($CFG->dirroot . '/mod/videoplayeray/lib.php');
require_once($CFG->dirroot . '/mod/videoplayeray/locallib.php');
require_once($CFG->dirroot . '/mod/videoplayeray/mod_form.php');
require_once($CFG->libdir . '/xmldb/xmldb_file.php');
require_once($CFG->libdir . '/adminlib.php');
admin_apply_default_settings(null, true);
\core\session\manager::set_user(get_admin());
$checks = 0;
function check($condition, $message) {
    global $checks;
    if (!$condition) { throw new Exception($message); }
    $checks++;
    echo "PASS: $message\n";
}
check(videoplayeray_progress_percent(null) === 0, 'missing progress is safe');
check(videoplayeray_progress_percent('invalid') === 0, 'invalid JSON is safe');
check(videoplayeray_progress_percent('[1,0,1,0]') == 50, 'progress percentage');
check(videoplayeray_progress_percent('[1,1,1,1]') == 100, 'full progress');
check(videoplayeray_is_list([1, 2, 3]) === true && videoplayeray_is_list(['a' => 1]) === false, 'array list polyfill');
check(videoplayeray_progress_array('[1,{"bad":1}]') === [], 'reject nested progress');
check(videoplayeray_progress_array('{"3":1}') === [], 'reject sparse progress');
check(count(videoplayeray_progress_array(json_encode(array_fill(0, 7200, 1)))) === 7200, 'two hour video JSON');
check(videoplayeray_parse_time('01:30') === 90, 'parse mm:ss timestamp');
check(videoplayeray_parse_time('01:02:05') === 3725, 'parse hh:mm:ss timestamp');
check(videoplayeray_parse_time('00:75') === null, 'reject invalid seconds in mm:ss');
check(videoplayeray_format_time(90) === '01:30', 'format mm:ss timestamp');
$formdata = (object)['questiontext' => ['What is 2 + 2?'], 'questiontime' => [5], 'rewindtime' => [0],
    'answer0' => ['3'], 'answer1' => ['4'], 'correctanswer' => [1]];
[$questions, $errors] = videoplayeray_questions_from_form($formdata);
check(!$errors && $questions[5][1][1][1] === true, 'declarative questions and correct option');
$formdata->rewindtime = [10];
[, $errors] = videoplayeray_questions_from_form($formdata);
check(isset($errors['rewindtime[0]']), 'reject invalid rewind');
$schema = new xmldb_file($CFG->dirroot . '/mod/videoplayeray/db/install.xml');
check($schema->loadXMLStructure(), 'Moodle XMLDB schema parses');
$suffix = time() . random_int(100, 999);
$course = create_course((object)['fullname' => 'Video QA ' . $suffix, 'shortname' => 'VQA' . $suffix,
    'category' => 1, 'enablecompletion' => 1, 'newsitems' => 0]);
$user = (object)['username' => 'videoqa' . $suffix, 'password' => 'VideoPlayerQA_Only_2026',
    'confirmed' => 1, 'mnethostid' => $CFG->mnet_localhost_id,
    'firstname' => 'Video', 'lastname' => 'QA', 'email' => 'videoqa' . $suffix . '@example.invalid'];
$user->id = class_exists('core\\user') && method_exists('core\\user', 'create_user') ?
    \core\user::create_user($user) : user_create_user($user);
$enrol = enrol_get_plugin('manual');
$enrol->add_instance($course, ['status' => ENROL_INSTANCE_ENABLED]);
$instances = enrol_get_instances($course->id, true);
foreach ($instances as $instance) {
    if ($instance->enrol === 'manual') { $enrol->enrol_user($instance, $user->id, 5); }
}
$module = (object)['modulename' => 'videoplayeray', 'course' => $course->id, 'section' => 0,
    'module' => $DB->get_field('modules', 'id', ['name' => 'videoplayeray']),
    'visible' => 1, 'name' => 'A safe video <title>', 'intro' => 'QA introduction', 'introformat' => FORMAT_HTML,
    'grade' => 100, 'overlayjs' => '', 'questions' => json_encode((object)$questions),
    'completion' => COMPLETION_TRACKING_AUTOMATIC, 'completionview' => 1];
$module = add_moduleinfo($module, $course);
$cm = get_coursemodule_from_id('videoplayeray', $module->coursemodule, 0, false, MUST_EXIST);
$context = context_module::instance($cm->id);
$activity = $DB->get_record('videoplayeray', ['id' => $cm->instance], '*', MUST_EXIST);
$dbquestions = videoplayeray_get_questions($activity->id);
check(count($dbquestions) === 1 && $dbquestions[0]['time'] === 5, 'activity migrates questions into videoplayeray_questions');
$qid2 = videoplayeray_save_question($activity->id, (object)[
    'timetrigger' => '00:12',
    'questiontype' => 'truefalse',
    'questiontext' => 'Sky is blue?',
    'truefalsecorrect' => 1,
    'rewindtime' => '00:04',
    'required' => 0,
    'feedbackcorrect' => 'Nice!',
    'feedbackwrong' => 'Look again.',
]);
check($qid2 > 0 && count(videoplayeray_get_questions($activity->id)) === 2, 'save true/false question');
check(count(videoplayeray_missing_required_questions($activity->id, [])) === 1, 'only required questions block completion');
check(count(videoplayeray_missing_required_questions($activity->id, [$dbquestions[0]['id']])) === 0, 'answered required question clears gate');
videoplayeray_delete_question($activity->id, $qid2);
check(count(videoplayeray_get_questions($activity->id)) === 1, 'delete question updates table and JSON');
check(is_enrolled($context, $user->id, '', true), 'enrolment detected');
$filerecord = (object)['contextid' => $context->id,
    'component' => 'mod_videoplayeray', 'filearea' => 'content', 'itemid' => 0,
    'filepath' => '/', 'filename' => 'qa-video.mp4', 'mimetype' => 'video/mp4'];
$video = $CFG->dirroot . '/lib/editor/tiny/tests/behat/fixtures/moodle-logo.mp4';
$file = is_file($video) ? get_file_storage()->create_file_from_pathname($filerecord, $video) :
    get_file_storage()->create_file_from_string($filerecord, 'QA video fixture');
check($file->get_filesize() > 0, 'video file area stores media');
$record = (object)['videoid' => $cm->id, 'userid' => $user->id, 'izlenmesayisi' => 1,
    'izlenme' => '[1,0,1,0]', 'position' => 2, 'answered' => '[]'];
$DB->insert_record('videoplayeray_izlenme', $record);
set_config('videoizlenmealtsinir', 1, 'videoplayeray');
videoplayeray_update_grades($activity, $user->id);
$grade = $DB->get_record_sql('SELECT gg.* FROM {grade_grades} gg JOIN {grade_items} gi ON gi.id = gg.itemid
    WHERE gi.itemmodule = :module AND gi.iteminstance = :instance AND gg.userid = :userid',
    ['module' => 'videoplayeray', 'instance' => $activity->id, 'userid' => $user->id]);
check($grade && (float)$grade->rawgrade === 100.0, 'completed viewing updates gradebook');
$provider = '\\mod_videoplayeray\\privacy\\provider';
$contexts = $provider::get_contexts_for_userid($user->id);
check(in_array($context->id, $contexts->get_contextids()), 'privacy context discovery');
$metadata = $provider::get_metadata(new \core_privacy\local\metadata\collection('mod_videoplayeray'));
check(count($metadata->get_collection()) === 3, 'privacy database and SMS metadata');
// Instantiate both mod_form and question_form.
$PAGE->set_course($course);
$PAGE->set_context($context);
$current = (object)(array)$activity;
$current->instance = $activity->id;
$current->coursemodule = $cm->id;
$form = new mod_videoplayeray_mod_form($current, $cm->section, $cm, $course);
ob_start(); $form->display(); $html = ob_get_clean();
check(str_contains($html, 'questions.php'), 'mod_form links to dedicated question manager');
$qform = new \mod_videoplayeray\form\question_form(null, ['cmid' => $cm->id, 'activityid' => $activity->id, 'qid' => 0]);
ob_start(); $qform->display(); $qhtml = ob_get_clean();
check(str_contains($qhtml, 'timetrigger') && str_contains($qhtml, 'questiontype'), 'question_form renders');
$qerrors = $qform->validation([
    'activityid' => $activity->id, 'qid' => 0, 'timetrigger' => '00:05',
    'questiontype' => 'multichoice', 'questiontext' => 'Dup', 'answer0' => 'A', 'answer1' => 'B',
    'correctanswer' => 0, 'rewindtime' => '00:00',
], []);
check(isset($qerrors['timetrigger']), 'question_form rejects duplicate timestamp');
$info = ['course' => $course->id, 'cmid' => $cm->id, 'activity' => $activity->id,
    'questionid' => $dbquestions[0]['id'], 'userid' => $user->id, 'username' => $user->username];
file_put_contents(dirname(__DIR__) . '/.qa/fixture-' . $CFG->branch . '.json', json_encode($info));
echo "OK: $checks integration checks; fixture " . json_encode($info) . "\n";
