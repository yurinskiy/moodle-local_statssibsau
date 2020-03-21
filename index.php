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
 * Main file plugin
 *
 * @package   local_statssibsau
 * @copyright 2020, Yuriy Yurinskiy <yuriyyurinskiy@yandex.ru>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');

$url = new moodle_url('/local/statssibsau/index.php');
$context = context_system::instance();

$PAGE->set_context($context);
$PAGE->set_url($url);
$PAGE->set_title(get_string('pluginname', 'local_statssibsau'));

/** Проверяем авторизован ли пользователь */
require_login();

/** Проверяем права пользователя */
if (!is_siteadmin() && !has_capability('local/statssibsau:view', $context)) {
    header("Location: " . $CFG->wwwroot);
    die();
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('pluginname', 'local_statssibsau'));

echo 'Hi!';

echo $OUTPUT->footer();
?>