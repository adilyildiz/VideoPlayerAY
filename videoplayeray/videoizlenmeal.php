<?php
// This file is part of Moodle - https://moodle.org/
// Licensed under the GNU GPL v3 or later: https://www.gnu.org/copyleft/gpl.html
/**
 * Video Analiz progress loader endpoint.
 * @package mod_videoplayeray
 * @copyright 2017 Adil YILDIZ
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define('AJAX_SCRIPT', true);
require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/locallib.php');

[$cm] = videoplayeray_ajax_context();
$record = $DB->get_record('videoplayeray_izlenme', ['videoid' => $cm->id, 'userid' => $USER->id]);
$data = $record ? videoplayeray_progress_array($record->izlenme) : [];
$answered = $record ? videoplayeray_answered_array($record->answered ?? '[]') : [];
echo json_encode([
    'durum' => $data ? 1 : 0,
    'veri' => json_encode($data),
    'position' => $record ? (int)$record->position : 0,
    'answered' => $answered,
]);
