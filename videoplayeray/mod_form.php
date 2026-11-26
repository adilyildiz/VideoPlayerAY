<?php
// This file is part of Moodle - https://moodle.org/
/**
 * Video Analiz activity settings form.
 * @package mod_videoplayeray
 * @copyright 2017 Adil YILDIZ
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot . '/course/moodleform_mod.php');
require_once(__DIR__ . '/locallib.php');

class mod_videoplayeray_mod_form extends moodleform_mod {
    public function definition() {
        global $DB;
        $mform = $this->_form;

        $mform->addElement('header', 'general', get_string('general', 'form'));
        $mform->addElement('text', 'name', get_string('videoplayerayname', 'videoplayeray'), ['size' => 64]);
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');
        $this->standard_intro_elements();
        $mform->addElement('filemanager', 'files', get_string('resourcecontent', 'videoplayeray'), null,
            ['accepted_types' => ['.mp4', '.webm', '.ogv'], 'maxfiles' => 1, 'subdirs' => false]);

        $mform->addElement('header', 'questionheader', get_string('questions', 'videoplayeray'));
        if (!empty($this->current->coursemodule) && !empty($this->current->instance)) {
            $count = $DB->count_records('videoplayeray_questions', ['videoplayerayid' => $this->current->instance]);
            $qurl = new moodle_url('/mod/videoplayeray/questions.php', ['id' => $this->current->coursemodule]);
            $link = html_writer::link($qurl, get_string('managequestions', 'videoplayeray'),
                ['class' => 'btn btn-secondary btn-sm']);
            $mform->addElement('static', 'questionmanagerlink', get_string('questions', 'videoplayeray'),
                get_string('questionscountinfo', 'videoplayeray', $count) . ' ' . $link);
        } else {
            $mform->addElement('static', 'questionhelp', '', get_string('questionssavefirst', 'videoplayeray'));
        }

        $mform->addElement('header', 'legacyheader', get_string('legacyquestions', 'videoplayeray'));
        $mform->setExpanded('legacyheader', false);
        $mform->addElement('text', 'overlayjs', get_string('videoplayerayoverlayjs', 'videoplayeray'), ['size' => 80]);
        // Validate before cleaning: never silently convert javascript: to an HTTP URL.
        $mform->setType('overlayjs', PARAM_RAW_TRIMMED);
        $mform->addHelpButton('overlayjs', 'videoplayerayoverlayjs', 'videoplayeray');

        $this->standard_grading_coursemodule_elements();
        $this->standard_coursemodule_elements();
        $this->add_action_buttons();
    }

    public function data_preprocessing(&$defaults) {
        $draftid = file_get_submitted_draft_itemid('files');
        file_prepare_draft_area($draftid, $this->context->id, 'mod_videoplayeray', 'content', 0, ['subdirs' => false]);
        $defaults['files'] = $draftid;
    }

    public function validation($data, $files) {
        global $USER;
        $errors = parent::validation($data, $files);
        $overlay = trim($data['overlayjs'] ?? '');
        if ($overlay && (!preg_match('~^https?://~i', $overlay) || strlen($overlay) > 255 ||
                clean_param($overlay, PARAM_URL) !== $overlay)) {
            $errors['overlayjs'] = get_string('invalidoverlay', 'videoplayeray');
        }
        if ($overlay && $overlay !== ($this->current->overlayjs ?? '') &&
                !has_capability('moodle/site:trustcontent', $this->context)) {
            $errors['overlayjs'] = get_string('overlaypermission', 'videoplayeray');
        }
        $draftcontext = context_user::instance($USER->id);
        if (!get_file_storage()->get_area_files($draftcontext->id, 'user', 'draft', $data['files'], '', false)) {
            $errors['files'] = get_string('required');
        }
        return $errors;
    }
}
