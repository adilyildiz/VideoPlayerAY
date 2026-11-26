<?php
// This file is part of Moodle - https://moodle.org/
/**
 * Video Analiz activity.
 * @package mod_videoplayeray
 * @copyright 2017 Adil YILDIZ
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

function xmldb_videoplayeray_upgrade($oldversion) {
    global $DB;
    $dbman = $DB->get_manager();

    if ($oldversion < 2026112500) {
        foreach (['videoplayeray_izlenme', 'videoplayeray_olaylar'] as $name) {
            $table = new xmldb_table($name);
            $index = new xmldb_index('video-user', XMLDB_INDEX_NOTUNIQUE, ['videoid', 'userid']);
            if (!$dbman->index_exists($table, $index)) {
                $dbman->add_index($table, $index);
            }
        }
        $table = new xmldb_table('videoplayeray');
        $field = new xmldb_field('questions', XMLDB_TYPE_TEXT, null, null, null, null, null);
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        $table = new xmldb_table('videoplayeray_izlenme');
        $field = new xmldb_field('position', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        upgrade_mod_savepoint(true, 2026112500, 'videoplayeray');
    }

    if ($oldversion < 2026112600) {
        $table = new xmldb_table('videoplayeray_questions');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('videoplayerayid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timetrigger', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('questiontype', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, 'multichoice');
        $table->add_field('questiontext', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);
        $table->add_field('questionformat', XMLDB_TYPE_INTEGER, '4', null, XMLDB_NOTNULL, null, '1');
        $table->add_field('optionsjson', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);
        $table->add_field('rewindtime', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('required', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '1');
        $table->add_field('shuffleoptions', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('feedbackcorrect', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('feedbackwrong', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('sortorder', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('videoplayerayid', XMLDB_KEY_FOREIGN, ['videoplayerayid'], 'videoplayeray', ['id']);
        $table->add_index('video-time', XMLDB_INDEX_NOTUNIQUE, ['videoplayerayid', 'timetrigger']);
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        $table = new xmldb_table('videoplayeray_izlenme');
        $field = new xmldb_field('answered', XMLDB_TYPE_TEXT, null, null, null, null, null, 'position');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $table = new xmldb_table('videoplayeray_olaylar');
        $field = new xmldb_field('questionid', XMLDB_TYPE_INTEGER, '10', null, null, null, '0', 'userid');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Migrate any existing JSON questions from videoplayeray.questions into videoplayeray_questions.
        $activities = $DB->get_records('videoplayeray', null, '', 'id, questions');
        $now = time();
        foreach ($activities as $activity) {
            if ($DB->record_exists('videoplayeray_questions', ['videoplayerayid' => $activity->id])) {
                continue;
            }
            $decoded = json_decode((string)$activity->questions, true);
            if (!is_array($decoded) || empty($decoded)) {
                continue;
            }
            $sort = 0;
            foreach ($decoded as $sec => $item) {
                if (!is_numeric($sec) || !is_array($item) || count($item) < 2 || !is_array($item[1])) {
                    continue;
                }
                $options = [];
                foreach ($item[1] as $opt) {
                    if (is_array($opt) && isset($opt[0]) && trim((string)$opt[0]) !== '') {
                        $options[] = ['text' => trim((string)$opt[0]), 'correct' => !empty($opt[1])];
                    }
                }
                if (count($options) < 2) {
                    continue;
                }
                $time = max(0, min(86399, (int)$sec));
                $rewind = max(0, min($time, (int)($item[2] ?? 0)));
                $DB->insert_record('videoplayeray_questions', (object)[
                    'videoplayerayid' => $activity->id,
                    'timetrigger' => $time,
                    'questiontype' => 'multichoice',
                    'questiontext' => trim((string)$item[0]),
                    'questionformat' => FORMAT_PLAIN,
                    'optionsjson' => json_encode($options, JSON_UNESCAPED_UNICODE),
                    'rewindtime' => $rewind,
                    'required' => 1,
                    'shuffleoptions' => 0,
                    'feedbackcorrect' => '',
                    'feedbackwrong' => '',
                    'sortorder' => $sort++,
                    'timecreated' => $now,
                    'timemodified' => $now,
                ]);
            }
        }

        upgrade_mod_savepoint(true, 2026112600, 'videoplayeray');
    }

    return true;
}
