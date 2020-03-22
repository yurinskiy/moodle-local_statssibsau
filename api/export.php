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

require_once(__DIR__ . '/../../../config.php');
require_once(__DIR__ . '/../exportlib.php');
require_once(__DIR__ . '/../form.php');

/** Для правильной отдачи файла */
ignore_user_abort(true);

$PAGE->set_context(context_system::instance());

/** Проверяем права пользователя */
if (is_siteadmin() || has_capability('local/statssibsau:view', $context)) {
    $mform = new local_statssibsau_form_teacher_actions();

    if ($data = $mform->get_data()) {
        switch ($data->type) {
            case 6:
                $temp_filepath = tempnam(sys_get_temp_dir(), 'exp');
                // флаг LOCK_EX для предотвращения записи данного файла кем-нибудь другим в данное время
                file_put_contents($temp_filepath, 'ID курса, Название курса, Видимость курса, Видимость категории, Категория', LOCK_EX);
                file_put_contents($temp_filepath, local_statssibsau_export_list_courses($data->categoryid), LOCK_EX);
                local_statssibsau_file_csv_export($temp_filepath, LOCAL_STATSSIBSAU_TYPE_EXPORT[$data->type] . '.csv');
                break;
            default:
                echo 'В разработке...';
                die();
        }
    }
}

header('Location: ' . $CFG->wwwroot);
die();