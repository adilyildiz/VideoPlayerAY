<?php
// This file is part of Moodle - https://moodle.org/
/**
 * Personal data discovery, export and deletion.
 * @package mod_videoplayeray
 * @copyright 2017 Adil YILDIZ
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_videoplayeray\privacy;

defined('MOODLE_INTERNAL') || die();

use core_privacy\local\metadata\collection;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\userlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\writer;

class provider implements \core_privacy\local\metadata\provider,
        \core_privacy\local\request\plugin\provider,
        \core_privacy\local\request\core_userlist_provider {
    public static function get_metadata(collection $collection): collection {
        $collection->add_database_table('videoplayeray_izlenme', [
            'userid' => 'privacy:metadata:userid', 'videoid' => 'privacy:metadata:videoid',
            'izlenme' => 'privacy:metadata:izlenme', 'izlenmesayisi' => 'privacy:metadata:izlenmesayisi',
            'position' => 'privacy:metadata:position', 'answered' => 'privacy:metadata:answered',
        ], 'privacy:metadata:views');
        $fields = ['userid' => 'privacy:metadata:userid', 'videoid' => 'privacy:metadata:videoid'];
        foreach (['questionid', 'kullanici', 'tur', 'soru', 'cevap', 'durum', 'baslama', 'sure', 'resim', 'oturum'] as $field) {
            $fields[$field] = 'privacy:metadata:eventdata';
        }
        $collection->add_database_table('videoplayeray_olaylar', $fields, 'privacy:metadata:events');
        $collection->add_external_location_link('sms', [
            'phone' => 'privacy:metadata:phone', 'message' => 'privacy:metadata:message',
        ], 'privacy:metadata:sms');
        return $collection;
    }

    public static function get_contexts_for_userid(int $userid): contextlist {
        $contexts = new contextlist();
        foreach (['videoplayeray_izlenme', 'videoplayeray_olaylar'] as $table) {
            $contexts->add_from_sql("SELECT ctx.id FROM {context} ctx
                JOIN {course_modules} cm ON cm.id = ctx.instanceid AND ctx.contextlevel = :level
                JOIN {modules} m ON m.id = cm.module AND m.name = :module
                JOIN {{$table}} data ON data.videoid = cm.id WHERE data.userid = :userid",
                ['level' => CONTEXT_MODULE, 'module' => 'videoplayeray', 'userid' => $userid]);
        }
        return $contexts;
    }

    public static function get_users_in_context(userlist $userlist) {
        $context = $userlist->get_context();
        if (!self::valid_context($context)) { return; }
        foreach (['videoplayeray_izlenme', 'videoplayeray_olaylar'] as $table) {
            $userlist->add_from_sql('userid', "SELECT userid FROM {{$table}} WHERE videoid = :cmid",
                ['cmid' => $context->instanceid]);
        }
    }

    private static function valid_context($context): bool {
        global $DB;
        return $context->contextlevel == CONTEXT_MODULE && $DB->record_exists_sql(
            'SELECT cm.id FROM {course_modules} cm JOIN {modules} m ON m.id = cm.module
             WHERE cm.id = :cmid AND m.name = :name', ['cmid' => $context->instanceid, 'name' => 'videoplayeray']);
    }

    public static function export_user_data(approved_contextlist $contextlist) {
        global $DB;
        foreach ($contextlist->get_contexts() as $context) {
            if (!self::valid_context($context)) { continue; }
            foreach (['videoplayeray_izlenme', 'videoplayeray_olaylar'] as $table) {
                $data = $DB->get_records($table, ['videoid' => $context->instanceid,
                    'userid' => $contextlist->get_user()->id]);
                writer::with_context($context)->export_data([$table], (object)['records' => array_values($data)]);
            }
        }
    }

    public static function delete_data_for_all_users_in_context(\context $context) {
        global $DB;
        if (!self::valid_context($context)) { return; }
        foreach (['videoplayeray_izlenme', 'videoplayeray_olaylar'] as $table) {
            $DB->delete_records($table, ['videoid' => $context->instanceid]);
        }
    }

    public static function delete_data_for_user(approved_contextlist $contextlist) {
        global $DB;
        foreach ($contextlist->get_contexts() as $context) {
            if (!self::valid_context($context)) { continue; }
            foreach (['videoplayeray_izlenme', 'videoplayeray_olaylar'] as $table) {
                $DB->delete_records($table, ['videoid' => $context->instanceid,
                    'userid' => $contextlist->get_user()->id]);
            }
        }
    }

    public static function delete_data_for_users(approved_userlist $userlist) {
        global $DB;
        $context = $userlist->get_context();
        if (!self::valid_context($context) || !$userlist->get_userids()) { return; }
        [$sql, $params] = $DB->get_in_or_equal($userlist->get_userids(), SQL_PARAMS_NAMED);
        $params['cmid'] = $context->instanceid;
        foreach (['videoplayeray_izlenme', 'videoplayeray_olaylar'] as $table) {
            $DB->delete_records_select($table, "videoid = :cmid AND userid $sql", $params);
        }
    }
}
