<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * A scheduled task for forum cron.
 *
 * @todo MDL-44734 This job will be split up properly.
 *
 * @package    mod_forum
 * @copyright  2014 Dan Poltawski <dan@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_videoplayeray\task;

class cron_task extends \core\task\scheduled_task {

    /**
     * Get a descriptive name for this task (shown to admins).
     *
     * @return string
     */
    public function get_name() {
        return get_string('taskname', 'videoplayeray');
    }

    /**
     * Run forum cron.
     */
    public function execute() {
        global $CFG;
        require_once($CFG->dirroot . '/mod/videoplayeray/locallib.php');
        require_once($CFG->libdir . '/filelib.php');
        global $DB;
        if (!get_config('videoplayeray', 'smsat') && !get_config('videoplayeray', 'cronsmsbilgiat')) {
            return;
        }
        $url = get_config('videoplayeray', 'apiadres');
        if (!preg_match('~^https://~i', (string)$url)) {
            throw new \moodle_exception('smshttps', 'videoplayeray');
        }
        $recipients = [];
        if (get_config('videoplayeray', 'smsat')) {
            $activities = $DB->get_records('videoplayeray');
            foreach ($activities as $activity) {
                $cm = get_coursemodule_from_instance('videoplayeray', $activity->id);
                if (!$cm || !$cm->visible || $cm->deletioninprogress) { continue; }
                $context = \context_module::instance($cm->id);
                $modinfo = get_fast_modinfo($activity->course);
                $info = $modinfo->get_cm($cm->id);
                if (!$info->visible || !$info->visibleoncoursepage) { continue; }
                $users = get_enrolled_users($context, 'mod/videoplayeray:submit', 0,
                    'u.id, u.firstname, u.lastname, u.phone2', '', 0, 0, true);
                foreach ($users as $user) {
                    $views = $DB->get_field('videoplayeray_izlenme', 'izlenmesayisi',
                        ['videoid' => $cm->id, 'userid' => $user->id]);
                    if ((int)$views < videoplayeray_minimum_views() && trim($user->phone2) !== '') {
                        if (!isset($recipients[$user->id])) {
                            $recipients[$user->id] = ['user' => $user, 'missing' => 0];
                        }
                        $recipients[$user->id]['missing']++;
                    }
                }
            }
        }
        $sent = 0;
        foreach ($recipients as $recipient) {
            $user = $recipient['user'];
            $message = 'Sayın ' . $user->firstname . ' ' . $user->lastname . ', ' .
                $recipient['missing'] . ' video etkinliğinde izlenme hedefiniz henüz tamamlanmadı.';
            $this->send_sms($url, $user->phone2, $message);
            $sent++;
        }
        if (get_config('videoplayeray', 'cronsmsbilgiat')) {
            $phone = get_config('videoplayeray', 'cronsmsbilgialici');
            if ($phone) { $this->send_sms($url, $phone, "Video Analiz: $sent öğrenciye hatırlatma gönderildi."); }
        }
        mtrace("Video Analiz: $sent reminders sent.");
    }

    private function send_sms($url, $phone, $message) {
        $params = [
            'api_id' => get_config('videoplayeray', 'kullanici'),
            'api_key' => get_config('videoplayeray', 'sifre'),
            'sender' => get_config('videoplayeray', 'gonderen'),
            'message_type' => 'turkce', 'message' => $message, 'phones' => [$phone],
        ];
        $curl = new \curl();
        $curl->setHeader('Content-Type: application/json');
        $options = ['CURLOPT_TIMEOUT' => 30, 'CURLOPT_CONNECTTIMEOUT' => 10, 'CURLOPT_FOLLOWLOCATION' => false];
        $type = (int)get_config('videoplayeray', 'apituru');
        if ($type === 2) { throw new \moodle_exception('smssoap', 'videoplayeray'); }
        if ($type === 0) {
            $options['CURLOPT_CUSTOMREQUEST'] = 'GET';
        }
        $curl->post($url, json_encode($params), $options);
        $info = $curl->get_info();
        if ($curl->get_errno() || ($info['http_code'] ?? 0) < 200 || ($info['http_code'] ?? 0) >= 300) {
            throw new \moodle_exception('smsfailed', 'videoplayeray');
        }
    }

}
