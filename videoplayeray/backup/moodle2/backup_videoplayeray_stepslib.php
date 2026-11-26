<?php
// This file is part of Moodle - https://moodle.org/
/**
 * Video Analiz activity.
 * @package mod_videoplayeray
 * @copyright 2017 Adil YILDIZ
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

class backup_videoplayeray_activity_structure_step extends backup_activity_structure_step {
    protected function define_structure() {
        $activity = new backup_nested_element('videoplayeray', ['id'],
            ['name', 'intro', 'introformat', 'overlayjs', 'questions', 'grade', 'timecreated', 'timemodified']);
        $questions = new backup_nested_element('questions_list');
        $question = new backup_nested_element('question', ['id'], [
            'timetrigger', 'questiontype', 'questiontext', 'questionformat', 'optionsjson',
            'rewindtime', 'required', 'shuffleoptions', 'feedbackcorrect', 'feedbackwrong',
            'sortorder', 'timecreated', 'timemodified',
        ]);
        $views = new backup_nested_element('views');
        $view = new backup_nested_element('view', ['id'], ['userid', 'izlenmesayisi', 'izlenme', 'position', 'answered']);
        $events = new backup_nested_element('events');
        $event = new backup_nested_element('event', ['id'],
            ['userid', 'questionid', 'kullanici', 'tur', 'soru', 'cevap', 'durum', 'baslama', 'sure', 'resim', 'oturum']);

        $activity->add_child($questions);
        $questions->add_child($question);
        $activity->add_child($views);
        $views->add_child($view);
        $activity->add_child($events);
        $events->add_child($event);

        $activity->set_source_table('videoplayeray', ['id' => backup::VAR_ACTIVITYID]);
        $question->set_source_table('videoplayeray_questions', ['videoplayerayid' => backup::VAR_PARENTID]);
        if ($this->get_setting_value('userinfo')) {
            $view->set_source_table('videoplayeray_izlenme', ['videoid' => backup::VAR_MODID]);
            $event->set_source_table('videoplayeray_olaylar', ['videoid' => backup::VAR_MODID]);
        }
        $view->annotate_ids('user', 'userid');
        $event->annotate_ids('user', 'userid');
        $activity->annotate_files('mod_videoplayeray', 'intro', null);
        $activity->annotate_files('mod_videoplayeray', 'content', null);
        return $this->prepare_activity_structure($activity);
    }
}
