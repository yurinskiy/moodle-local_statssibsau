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
require_once($CFG->libdir . '/csvlib.class.php');
require_once(__DIR__ . '/../locallib.php');
require_once(__DIR__ . '/../exportlib.php');
require_once(__DIR__ . '/../form.php');

/** Для правильной отдачи файла */
ignore_user_abort(true);

$PAGE->set_context(context_system::instance());

/** Проверяем права пользователя */
if (is_siteadmin() || has_capability('local/statssibsau:view', $context)) {
    $mform = new local_statssibsau_form_teacher_actions();

    if ($data = $mform->get_data()) {
        //Сохраняем файл сессии
        session_write_close();

        $csvexport = new csv_export_writer('commer');
        if (isset(LOCAL_STATSSIBSAU_TYPE_EXPORT[$data->type]) &&
                array_key_exists('text', LOCAL_STATSSIBSAU_TYPE_EXPORT[$data->type])) {
            $csvexport->set_filename(LOCAL_STATSSIBSAU_TYPE_EXPORT[$data->type]['text']);
        }

        switch ($data->type) {
            case 2:
                $data->events = local_statssibsau_get_array_events($data->events);
                $csvexport->add_data(local_statssibsau_export_prepare_header_csv(['ID курса', 'Название курса'], $data->events));
                foreach (local_statssibsau_user_activity(
                        $data->categoryid,
                        LOCAL_STATSSIBSAU_ROLE_TEACHER,
                        null,
                        $data->dbeg,
                        $data->dend,
                        $data->events
                ) as $data) {
                    $csvexport->add_data($data);
                }
                break;
            case 5:
                $data->events = local_statssibsau_get_array_events($data->events);
                $csvexport->add_data(local_statssibsau_export_prepare_header_csv(['ID курса', 'Название курса'], $data->events));
                foreach (local_statssibsau_user_activity(
                        $data->categoryid,
                        LOCAL_STATSSIBSAU_ROLE_STUDENT,
                        null,
                        $data->dbeg,
                        $data->dend,
                        $data->events
                ) as $data) {
                    $csvexport->add_data($data);
                }
                break;
            case 6:
                $csvexport->add_data(['ID курса', 'Краткое название курса', 'Полное название курса', 'Видимость курса',
                        'Видимость категории', 'Категория']);
                foreach (local_statssibsau_list_courses($data->categoryid) as $data) {
                    $csvexport->add_data($data);
                }
                break;
            case 7:
                $data->events = local_statssibsau_get_array_events($data->events);
                $csvexport->add_data(local_statssibsau_export_prepare_header_csv(['ID преподавателя', 'Email', 'ФИО'],
                        $data->events));
                foreach (local_statssibsau_list_users(
                        $data->categoryid,
                        LOCAL_STATSSIBSAU_ROLE_TEACHER,
                        $data->dbeg,
                        $data->dend,
                        $data->events
                ) as $data) {
                    $csvexport->add_data($data);
                }
                break;
            default:
                echo 'В разработке...';
                $csvexport->add_data(['В разработке...']);
                die();
        }

        $csvexport->download_file();
    }
}

header('Location: ' . $CFG->wwwroot);
die();
