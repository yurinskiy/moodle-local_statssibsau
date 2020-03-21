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
 * Exports plugin
 *
 * @package   local_statssibsau
 * @copyright 2020, Yuriy Yurinskiy <yuriyyurinskiy@yandex.ru>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../../config.php');
require_once('../locallib.php');
require_once('../form.php');

$PAGE->set_context(context_system::instance());

/** Проверяем права пользователя */
if (is_siteadmin() || has_capability('local/statssibsau:view', $context)) {
    $mform = new local_statssibsau_form_teacher_actions();

    if ($data = $mform->get_data()) {
        switch ($data->type) {
            case 1:
                break;
            case 2:
                break;
            case 3:
                break;
            case 4:
                break;
            case 5:
                break;
            case 6:
                break;
            default:
        }
        echo '<pre>' . $data->dbeg . '</pre>';
        echo '<pre>' . $data->dend . '</pre>';
        echo '<pre>' . $data->type . '</pre>';
        echo 'В разработке';
        die();
    }
}

header('Location: ' . $CFG->wwwroot);
die();