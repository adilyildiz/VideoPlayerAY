<?php
// Isolated QA only: verifies course backup/restore and the legacy upgrade path.
define('CLI_SCRIPT', true);
define('NO_UPGRADE_CHECK', true);
require($argv[1] . '/config.php');
$CFG->debug = DEBUG_DEVELOPER;
$CFG->debugdisplay = true;
require_once($CFG->dirroot . '/backup/util/includes/backup_includes.php');
require_once($CFG->dirroot . '/backup/util/includes/restore_includes.php');
require_once($CFG->dirroot . '/mod/videoplayeray/db/upgrade.php');
require_once($CFG->dirroot . '/mod/videoplayeray/locallib.php');
require_once($CFG->libdir . '/upgradelib.php');
upgrade_finished(); // Recover the disposable test site after an interrupted simulated upgrade.
\core\session\manager::set_user(get_admin());
$fixturepath = dirname(__DIR__) . '/.qa/fixture-' . $CFG->branch . '.json';
$fixture = json_decode(file_get_contents($fixturepath));
$activity = $DB->get_record('videoplayeray', ['id' => $fixture->activity]);
$originalquestions = videoplayeray_get_questions($activity->id);
$originalviews = (int)$DB->get_field('videoplayeray_izlenme', 'izlenmesayisi',
    ['videoid' => $fixture->cmid, 'userid' => $fixture->userid]);
$context = context_module::instance($fixture->cmid);
$backup = new backup_controller(backup::TYPE_1COURSE, $fixture->course, backup::FORMAT_MOODLE,
    backup::INTERACTIVE_NO, backup::MODE_GENERAL, $USER->id);
$backup->get_plan()->get_setting('users')->set_value(true);
$backup->execute_plan();
$results = $backup->get_results();
$backupfile = $results['backup_destination'];
$backup->destroy();
if (!$backupfile) { throw new Exception('No backup archive'); }
$directory = 'videoplayeray-restore-' . uniqid();
$path = $CFG->tempdir . '/backup/' . $directory;
get_file_packer('application/vnd.moodle.backup')->extract_to_pathname($backupfile, $path);
$newcourseid = restore_dbops::create_new_course('Restored Video QA', 'RQA' . time(), 1);
$restore = new restore_controller($directory, $newcourseid, backup::INTERACTIVE_NO,
    backup::MODE_GENERAL, $USER->id, backup::TARGET_NEW_COURSE);
if (!$restore->execute_precheck()) { throw new Exception('Restore precheck failed'); }
$restore->execute_plan();
$restore->destroy();
$restored = $DB->get_record('videoplayeray', ['course' => $newcourseid], '*', MUST_EXIST);
$newcm = get_coursemodule_from_instance('videoplayeray', $restored->id);
$newcontext = context_module::instance($newcm->id);
$restoredquestions = videoplayeray_get_questions($restored->id);
if (count($restoredquestions) !== count($originalquestions) ||
        $restoredquestions[0]['text'] !== $originalquestions[0]['text']) {
    throw new Exception('Normalized question backup/restore mismatch');
}
$files = get_file_storage()->get_area_files($newcontext->id, 'mod_videoplayeray', 'content', 0, '', false);
if (count($files) !== 1) { throw new Exception('Video missing from restore'); }
$record = $DB->get_record('videoplayeray_izlenme', ['videoid' => $newcm->id, 'userid' => $fixture->userid]);
if (!$record || (int)$record->izlenmesayisi !== $originalviews) { throw new Exception('User progress mapping failed'); }
echo "PASS: course backup and restore preserve video, normalized questions, progress and remap IDs\n";

// Recreate the 2023011000 package's schema in this disposable database, then execute the real upgrader.
$dbman = $DB->get_manager();
$qtable = new xmldb_table('videoplayeray_questions');
if ($dbman->table_exists($qtable)) { $dbman->drop_table($qtable); }
$table = new xmldb_table('videoplayeray');
if ($dbman->field_exists($table, new xmldb_field('questions'))) {
    $dbman->drop_field($table, new xmldb_field('questions'));
}
$table = new xmldb_table('videoplayeray_izlenme');
foreach (['answered', 'position'] as $f) {
    if ($dbman->field_exists($table, new xmldb_field($f))) { $dbman->drop_field($table, new xmldb_field($f)); }
}
$table = new xmldb_table('videoplayeray_olaylar');
if ($dbman->field_exists($table, new xmldb_field('questionid'))) {
    $dbman->drop_field($table, new xmldb_field('questionid'));
}
foreach (['videoplayeray_izlenme', 'videoplayeray_olaylar'] as $name) {
    $idx = new xmldb_index('video-user', XMLDB_INDEX_NOTUNIQUE, ['videoid', 'userid']);
    if ($dbman->index_exists(new xmldb_table($name), $idx)) {
        $dbman->drop_index(new xmldb_table($name), $idx);
    }
}
set_config('version', 2023011000, 'mod_videoplayeray');
xmldb_videoplayeray_upgrade(2023011000);
upgrade_finished();
$preserved = $DB->get_record('videoplayeray_izlenme', ['videoid' => $fixture->cmid, 'userid' => $fixture->userid]);
if (!$preserved || (int)$preserved->izlenmesayisi !== $originalviews || (int)$preserved->position !== 0) {
    throw new Exception('Upgrade did not preserve legacy viewing records');
}
// Restore the QA question fixture after this intentionally destructive schema simulation.
$DB->set_field('videoplayeray', 'questions', $activity->questions, ['id' => $activity->id]);
videoplayeray_migrate_legacy_questions($DB->get_record('videoplayeray', ['id' => $activity->id], '*', MUST_EXIST));
$remigrated = videoplayeray_get_questions($activity->id);
$fixture->questionid = $remigrated[0]['id'];
file_put_contents($fixturepath, json_encode($fixture));
echo "PASS: 2023011000 legacy schema upgrades to 2026112600 without losing viewing counts or video files\n";
