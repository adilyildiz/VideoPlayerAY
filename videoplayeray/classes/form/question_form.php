<?php
// This file is part of Moodle - https://moodle.org/
// Licensed under the GNU GPL v3 or later: https://www.gnu.org/copyleft/gpl.html
/**
 * Add/edit timed question form for Video Analiz.
 * @package mod_videoplayeray
 * @copyright 2017 Adil YILDIZ
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_videoplayeray\form;

defined('MOODLE_INTERNAL') || die();

require_once($GLOBALS['CFG']->libdir . '/formslib.php');
require_once($GLOBALS['CFG']->dirroot . '/mod/videoplayeray/locallib.php');

class question_form extends \moodleform {
    public function definition() {
        $mform = $this->_form;
        $cmid = (int)($this->_customdata['cmid'] ?? 0);
        $activityid = (int)($this->_customdata['activityid'] ?? 0);
        $qid = (int)($this->_customdata['qid'] ?? 0);

        $mform->addElement('hidden', 'id', $cmid);
        $mform->setType('id', PARAM_INT);
        $mform->addElement('hidden', 'qid', $qid);
        $mform->setType('qid', PARAM_INT);
        $mform->addElement('hidden', 'activityid', $activityid);
        $mform->setType('activityid', PARAM_INT);

        $mform->addElement('header', 'qheader',
            $qid ? get_string('editquestion', 'videoplayeray') : get_string('addquestion', 'videoplayeray'));

        $mform->addElement('text', 'timetrigger', get_string('questiontime', 'videoplayeray'),
            ['size' => 12, 'placeholder' => '01:30']);
        $mform->setType('timetrigger', PARAM_RAW_TRIMMED);
        $mform->setDefault('timetrigger', '00:00');
        $mform->addRule('timetrigger', null, 'required', null, 'client');
        $mform->addHelpButton('timetrigger', 'questiontime', 'videoplayeray');

        $mform->addElement('select', 'questiontype', get_string('questiontype', 'videoplayeray'), [
            'multichoice' => get_string('qtype_multichoice', 'videoplayeray'),
            'truefalse' => get_string('qtype_truefalse', 'videoplayeray'),
        ]);
        $mform->setDefault('questiontype', 'multichoice');

        $mform->addElement('textarea', 'questiontext', get_string('question', 'videoplayeray'),
            ['rows' => 3, 'cols' => 64]);
        $mform->setType('questiontext', PARAM_TEXT);
        $mform->addRule('questiontext', null, 'required', null, 'client');

        // True/False correct option selector.
        $mform->addElement('select', 'truefalsecorrect', get_string('correctoption', 'videoplayeray'), [
            1 => get_string('optiontrue', 'videoplayeray'),
            0 => get_string('optionfalse', 'videoplayeray'),
        ]);
        $mform->setDefault('truefalsecorrect', 1);
        $mform->hideIf('truefalsecorrect', 'questiontype', 'neq', 'truefalse');

        // Multiple choice options (2 to 6).
        for ($i = 0; $i < 6; $i++) {
            $mform->addElement('text', 'answer' . $i, get_string('answernumber', 'videoplayeray', $i + 1), ['size' => 64]);
            $mform->setType('answer' . $i, PARAM_TEXT);
            $mform->hideIf('answer' . $i, 'questiontype', 'eq', 'truefalse');
        }
        $mform->addElement('select', 'correctanswer', get_string('correctoption', 'videoplayeray'),
            [0 => '1', 1 => '2', 2 => '3', 3 => '4', 4 => '5', 5 => '6']);
        $mform->setDefault('correctanswer', 0);
        $mform->hideIf('correctanswer', 'questiontype', 'eq', 'truefalse');

        $mform->addElement('text', 'rewindtime', get_string('rewindtime', 'videoplayeray'),
            ['size' => 12, 'placeholder' => '00:00']);
        $mform->setType('rewindtime', PARAM_RAW_TRIMMED);
        $mform->setDefault('rewindtime', '00:00');
        $mform->addHelpButton('rewindtime', 'rewindtime', 'videoplayeray');

        $mform->addElement('advcheckbox', 'required', get_string('questionrequired', 'videoplayeray'),
            get_string('questionrequired_desc', 'videoplayeray'));
        $mform->setDefault('required', 1);

        $mform->addElement('advcheckbox', 'shuffleoptions', get_string('shuffleoptions', 'videoplayeray'),
            get_string('shuffleoptions_desc', 'videoplayeray'));
        $mform->setDefault('shuffleoptions', 0);
        $mform->hideIf('shuffleoptions', 'questiontype', 'eq', 'truefalse');

        $mform->addElement('textarea', 'feedbackcorrect', get_string('feedbackcorrect', 'videoplayeray'),
            ['rows' => 2, 'cols' => 64]);
        $mform->setType('feedbackcorrect', PARAM_TEXT);

        $mform->addElement('textarea', 'feedbackwrong', get_string('feedbackwrong', 'videoplayeray'),
            ['rows' => 2, 'cols' => 64]);
        $mform->setType('feedbackwrong', PARAM_TEXT);

        $this->add_action_buttons(true,
            $qid ? get_string('savechanges') : get_string('addquestion', 'videoplayeray'));
    }

    public function validation($data, $files) {
        global $DB;
        $errors = parent::validation($data, $files);
        $activityid = (int)($data['activityid'] ?? 0);
        $qid = (int)($data['qid'] ?? 0);

        $time = videoplayeray_parse_time($data['timetrigger'] ?? '');
        if ($time === null) {
            $errors['timetrigger'] = get_string('invalidquestiontime', 'videoplayeray');
        } else if ($activityid > 0) {
            $duplicate = $DB->get_record_select(
                'videoplayeray_questions',
                'videoplayerayid = :aid AND timetrigger = :t AND id <> :qid',
                ['aid' => $activityid, 't' => $time, 'qid' => $qid]
            );
            if ($duplicate) {
                $errors['timetrigger'] = get_string('duplicatequestiontime', 'videoplayeray');
            }
        }

        if ($qid === 0 && $activityid > 0 && $DB->count_records('videoplayeray_questions', ['videoplayerayid' => $activityid]) >= 100) {
            $errors['questiontext'] = get_string('toomanyquestions', 'videoplayeray');
        }

        $rewindraw = trim((string)($data['rewindtime'] ?? ''));
        $rewind = $rewindraw === '' ? 0 : videoplayeray_parse_time($rewindraw);
        if ($rewind === null || ($time !== null && $rewind > $time)) {
            $errors['rewindtime'] = get_string('invalidrewind', 'videoplayeray');
        }

        if (trim((string)($data['questiontext'] ?? '')) === '') {
            $errors['questiontext'] = get_string('required');
        }

        if (($data['questiontype'] ?? 'multichoice') !== 'truefalse') {
            $nonempty = 0;
            $selected = (int)($data['correctanswer'] ?? 0);
            $correctnonempty = false;
            for ($i = 0; $i < 6; $i++) {
                $val = trim((string)($data['answer' . $i] ?? ''));
                if ($val !== '') {
                    $nonempty++;
                    if ($i === $selected) {
                        $correctnonempty = true;
                    }
                }
            }
            if ($nonempty < 2 || !$correctnonempty) {
                $errors['answer0'] = get_string('invalidquestion', 'videoplayeray');
            }
        }

        return $errors;
    }
}
