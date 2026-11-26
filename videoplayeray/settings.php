<?php
// This file is part of Moodle - https://moodle.org/
// Licensed under the GNU GPL v3 or later: https://www.gnu.org/copyleft/gpl.html
/**
 * Video Analiz module admin settings and defaults.
 * @package mod_videoplayeray
 * @copyright 2017 Adil YILDIZ
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

if ($ADMIN->fulltree) {
    $apitipleri = ['HTTP GET', 'HTTP POST', 'SOAP'];
    $settings->add(new admin_setting_configtext('videoplayeray/apiadres',
        get_string('setting_apiadres', 'videoplayeray'), '', '', PARAM_TEXT));
    $settings->add(new admin_setting_configtext('videoplayeray/kullanici',
        get_string('setting_kullanici', 'videoplayeray'), '', '', PARAM_TEXT));
    $settings->add(new admin_setting_configpasswordunmask('videoplayeray/sifre',
        get_string('setting_sifre', 'videoplayeray'), '', ''));
    $settings->add(new admin_setting_configtext('videoplayeray/gonderen',
        get_string('setting_gonderen', 'videoplayeray'), '', '', PARAM_TEXT));
    $settings->add(new admin_setting_configselect('videoplayeray/apituru',
        get_string('setting_apituru', 'videoplayeray'), '', 1, $apitipleri));
    $settings->add(new admin_setting_configcheckbox('videoplayeray/smsat',
        get_string('setting_smsat', 'videoplayeray'), '', 0));
    $settings->add(new admin_setting_configtext('videoplayeray/videoizlenmealtsinir',
        get_string('setting_minviews', 'videoplayeray'), '', 1, PARAM_INT));
    $settings->add(new admin_setting_configtext('videoplayeray/videoizlenmeoranaltsinir',
        get_string('setting_threshold', 'videoplayeray'), '', 95, PARAM_INT));
    $settings->add(new admin_setting_configcheckbox('videoplayeray/cronsmsbilgiat',
        get_string('setting_adminsms', 'videoplayeray'), '', 0));
    $settings->add(new admin_setting_configtext('videoplayeray/cronsmsbilgialici',
        get_string('setting_adminphone', 'videoplayeray'), '', '', PARAM_TEXT));
}
