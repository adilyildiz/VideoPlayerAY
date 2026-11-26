<?php
// This file is part of Moodle - https://moodle.org/
/**
 * Video Analiz activity.
 * @package mod_videoplayeray
 * @copyright 2017 Adil YILDIZ
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

class restore_videoplayeray_activity_structure_step extends restore_activity_structure_step {
    /** @var bool Whether any normalized question rows were restored. */
    private $hasrestoredquestions = false;

    protected function define_structure() {
        $paths = [
            new restore_path_element('videoplayeray', '/activity/videoplayeray'),
            new restore_path_element('videoplayeray_question', '/activity/videoplayeray/questions_list/question'),
        ];
        if ($this->get_setting_value('userinfo')) {
            $paths[] = new restore_path_element('videoplayeray_view', '/activity/videoplayeray/views/view');
            $paths[] = new restore_path_element('videoplayeray_event', '/activity/videoplayeray/events/event');
        }
        return $this->prepare_activity_structure($paths);
    }

    protected function process_videoplayeray($data) {
        global $DB;
        $data = (object)$data;
        $data->course = $this->get_courseid();
        $data->overlayjs = $data->overlayjs ?? '';
        $data->questions = $data->questions ?? '[]';
        $data->timecreated = $this->apply_date_offset($data->timecreated ?? time());
        $data->timemodified = $this->apply_date_offset($data->timemodified ?? time());
        if ($data->grade < 0) {
            $data->grade = -$this->get_mappingid('scale', abs($data->grade));
        }
        $newitemid = $DB->insert_record('videoplayeray', $data);
        $this->apply_activity_instance($newitemid);
    }

    protected function process_videoplayeray_question($data) {
        global $DB;
        $data = (object)$data;
        $oldid = $data->id ?? null;
        unset($data->id);
        $data->videoplayerayid = $this->get_new_parentid('videoplayeray');
        $data->timetrigger = max(0, min(86399, (int)($data->timetrigger ?? 0)));
        $data->questiontype = in_array($data->questiontype ?? 'multichoice', ['multichoice', 'truefalse'], true)
            ? $data->questiontype : 'multichoice';
        $data->questionformat = (int)($data->questionformat ?? FORMAT_PLAIN);
        $data->rewindtime = max(0, min($data->timetrigger, (int)($data->rewindtime ?? 0)));
        $data->required = isset($data->required) ? (int)(bool)$data->required : 1;
        $data->shuffleoptions = !empty($data->shuffleoptions) ? 1 : 0;
        $data->feedbackcorrect = $data->feedbackcorrect ?? '';
        $data->feedbackwrong = $data->feedbackwrong ?? '';
        $data->sortorder = (int)($data->sortorder ?? 0);
        $data->timecreated = $this->apply_date_offset($data->timecreated ?? time());
        $data->timemodified = $this->apply_date_offset($data->timemodified ?? time());
        $newid = $DB->insert_record('videoplayeray_questions', $data);
        if ($oldid) {
            $this->set_mapping('videoplayeray_question', $oldid, $newid);
        }
        $this->hasrestoredquestions = true;
    }

    protected function process_videoplayeray_view($data) {
        $data['position'] = $data['position'] ?? 0;
        if (!empty($data['answered'])) {
            $decoded = json_decode((string)$data['answered'], true);
            if (is_array($decoded)) {
                $remapped = [];
                foreach ($decoded as $qid) {
                    if (is_numeric($qid)) {
                        $mapped = $this->get_mappingid('videoplayeray_question', (int)$qid);
                        $remapped[] = $mapped ? (int)$mapped : (int)$qid;
                    } else {
                        $remapped[] = (string)$qid;
                    }
                }
                $data['answered'] = json_encode(array_values(array_unique($remapped)));
            }
        } else {
            $data['answered'] = '[]';
        }
        $this->restore_userdata('videoplayeray_izlenme', $data);
    }

    protected function process_videoplayeray_event($data) {
        $data['kullanici'] = '{}';
        if (!empty($data['questionid'])) {
            $mapped = $this->get_mappingid('videoplayeray_question', (int)$data['questionid']);
            $data['questionid'] = $mapped ? (int)$mapped : 0;
        } else {
            $data['questionid'] = 0;
        }
        $this->restore_userdata('videoplayeray_olaylar', $data);
    }

    private function restore_userdata($table, $data) {
        global $DB;
        $data = (object)$data;
        unset($data->id);
        $data->userid = $this->get_mappingid('user', $data->userid);
        $data->videoid = $this->task->get_moduleid();
        if ($data->userid) {
            $DB->insert_record($table, $data);
        }
    }

    protected function after_execute() {
        global $DB;
        $this->add_related_files('mod_videoplayeray', 'intro', null);
        $this->add_related_files('mod_videoplayeray', 'content', null);
        // If restoring a legacy backup that only stored JSON in videoplayeray.questions, migrate it now.
        if (!$this->hasrestoredquestions) {
            require_once(__DIR__ . '/../../locallib.php');
            $activityid = $this->task->get_activityid();
            if ($activity = $DB->get_record('videoplayeray', ['id' => $activityid])) {
                videoplayeray_migrate_legacy_questions($activity);
            }
        }
    }
}
