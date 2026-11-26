<?php
// This file is part of Moodle - https://moodle.org/
// Licensed under the GNU GPL v3 or later: https://www.gnu.org/copyleft/gpl.html
/**
 * Video Analiz completion endpoint.
 * @package mod_videoplayeray
 * @copyright 2017 Adil YILDIZ
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define('AJAX_SCRIPT', true);
require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/locallib.php');
require_once(__DIR__ . '/lib.php');

[$cm] = videoplayeray_ajax_context('id');
$record = $DB->get_record('videoplayeray_izlenme', ['videoid' => $cm->id, 'userid' => $USER->id]);
$percent = $record ? videoplayeray_progress_percent($record->izlenme) : 0;
$views = $record ? (int)$record->izlenmesayisi : 0;
$answered = $record ? videoplayeray_answered_array($record->answered ?? '[]') : [];
$missing = videoplayeray_missing_required_questions($cm->instance, $answered);

if ($record && $percent >= videoplayeray_threshold() && empty($missing)) {
    $record->izlenmesayisi = ++$views;
    $record->izlenme = '[]';
    $record->position = 0;
    $record->answered = '[]';
    $DB->update_record('videoplayeray_izlenme', $record);
    $activity = $DB->get_record('videoplayeray', ['id' => $cm->instance], '*', MUST_EXIST);
    videoplayeray_update_grades($activity, $USER->id);
    echo json_encode(['tam_izlenme', round($percent), $views]);
} else if ($record && $percent >= videoplayeray_threshold() && !empty($missing)) {
    echo json_encode(['eksik_soru', round($percent), $views, count($missing)]);
} else {
    echo json_encode(['eksik_izlenme', round($percent), $views]);
}
