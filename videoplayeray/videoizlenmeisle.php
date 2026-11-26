<?php
// This file is part of Moodle - https://moodle.org/
// Licensed under the GNU GPL v3 or later: https://www.gnu.org/copyleft/gpl.html
/**
 * Video Analiz activity.
 * @package mod_videoplayeray
 * @copyright 2017 Adil YILDIZ
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define('AJAX_SCRIPT', true);
require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/locallib.php');

[$cm] = videoplayeray_ajax_context();
// JSON avoids PHP max_input_vars silently truncating long videos.
$json = required_param('progress', PARAM_RAW);
$data = videoplayeray_progress_array($json);
if (!$data) {
    throw new moodle_exception('invalidprogress', 'videoplayeray');
}
$conditions = ['videoid' => $cm->id, 'userid' => $USER->id];
$record = $DB->get_record('videoplayeray_izlenme', $conditions);
if (!$record) {
    $record = (object)($conditions + ['izlenmesayisi' => 0]);
}
$record->izlenme = json_encode($data);
$record->position = max(0, min(86400, (int)optional_param('position', 0, PARAM_FLOAT)));
if (empty($record->id)) {
    $DB->insert_record('videoplayeray_izlenme', $record);
} else {
    $DB->update_record('videoplayeray_izlenme', $record);
}
echo json_encode(['ok' => true]);
