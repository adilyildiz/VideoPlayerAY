<?php
// This file is part of Moodle - https://moodle.org/
// Licensed under the GNU GPL v3 or later: https://www.gnu.org/copyleft/gpl.html
/**
 * Video Analiz event and question answer endpoint.
 * @package mod_videoplayeray
 * @copyright 2017 Adil YILDIZ
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define('AJAX_SCRIPT', true);
require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/locallib.php');

[$cm] = videoplayeray_ajax_context();
$type = required_param('tur', PARAM_ALPHA);
if (!in_array($type, ['sorusoruldu', 'cevapverildi', 'izlemebasladi', 'izlemedevamedildi',
        'izlemebekletildi', 'izlemekaydirildi', 'tamekran', 'seskes', 'izlemebitti'], true)) {
    throw new moodle_exception('invalidrequest', 'error');
}
$questionid = max(0, optional_param('questionid', 0, PARAM_INT));
$durum = optional_param('durum', '', PARAM_TEXT);
$baslama = optional_param('baslama', 0, PARAM_FLOAT);

$record = (object)[
    'videoid' => $cm->id,
    'userid' => $USER->id,
    'questionid' => $questionid,
    // Historical field retained, without copying the full user/session object.
    'kullanici' => '{}',
    'tur' => $type,
    'soru' => optional_param('soru', '', PARAM_TEXT),
    'cevap' => optional_param('cevap', '', PARAM_TEXT),
    'durum' => $durum,
    'baslama' => $baslama,
    'sure' => optional_param('sure', 0, PARAM_FLOAT),
    'resim' => (string)time(),
    'oturum' => optional_param('oturum', '', PARAM_TEXT),
];
$DB->insert_record('videoplayeray_olaylar', $record);

$response = ['ok' => true];
if ($type === 'cevapverildi' && (string)$durum === '1') {
    $key = $questionid > 0 ? $questionid : ('t' . max(0, (int)floor($baslama)));
    $conditions = ['videoid' => $cm->id, 'userid' => $USER->id];
    $viewrec = $DB->get_record('videoplayeray_izlenme', $conditions);
    if (!$viewrec) {
        $viewrec = (object)($conditions + [
            'izlenmesayisi' => 0,
            'position' => 0,
            'izlenme' => '[]',
            'answered' => json_encode([$key]),
        ]);
        $DB->insert_record('videoplayeray_izlenme', $viewrec);
        $response['answered'] = [$key];
    } else {
        $answered = videoplayeray_answered_array($viewrec->answered ?? '[]');
        if (!in_array($key, $answered, true)) {
            $answered[] = $key;
            $viewrec->answered = json_encode($answered);
            $DB->update_record('videoplayeray_izlenme', $viewrec);
        }
        $response['answered'] = $answered;
    }
}
echo json_encode($response);
