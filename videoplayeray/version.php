<?php
// This file is part of Moodle - https://moodle.org/
// Licensed under the GNU GPL v3 or later: https://www.gnu.org/copyleft/gpl.html
/**
 * Video Analiz activity.
 * @package mod_videoplayeray
 * @copyright 2017 Adil YILDIZ
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();
$plugin->component = 'mod_videoplayeray';
$plugin->version = 2026112600;
$plugin->release = '2.1.0';
$plugin->requires = 2020061500; // Moodle 3.9 LTS.
$plugin->supported = [309, 503];
$plugin->maturity = MATURITY_BETA; // Pending the documented live Moodle acceptance matrix.
