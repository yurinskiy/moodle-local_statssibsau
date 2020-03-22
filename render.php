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
 * This file contains render code.
 *
 * @package   local_statssibsau
 * @copyright 2020, Yuriy Yurinskiy <yuriyyurinskiy@yandex.ru>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

/**
 * Формирует таблицу для вывода информации
 *
 * @param $id
 * @param $data
 * @return string
 * @throws coding_exception
 */
function local_statssibsau_render_table($id, $data) {
    $table = new html_table();
    $table->head = [
            'Характеристика',
            'Значение'
    ];
    $table->align = [
            'left',
            'center',
    ];
    $table->size = [
            '80%',
            '20%',
    ];
    $table->id = $id;
    $table->attributes['class'] = 'admintable generaltable';
    $table->data = $data;

    return html_writer::table($table);
}

/**
 * Генерирует таблицу информации о категории
 *
 * @param int $category
 * @return string
 * @throws coding_exception
 * @throws dml_exception
 */
function local_statssibsau_render_info_courses($category = 0) {
    global $OUTPUT;

    $coursescount = local_statssibsau_count_courses($category);

    $data[] = [
            'Курсы',
            $coursescount->all
    ];
    $data[] = [
            'Опубликованные курсы',
            $coursescount->visible
    ];
    $data[] = [
            'Скрытые курсы',
            $coursescount->hidden
    ];

    $data[] = [
            'Преподаватели',
            $coursescount->teacher ?? 0
    ];

    $data[] = [
            'Ассистенты',
            $coursescount->assistant ?? 0
    ];
    $data[] = [
            'Студенты',
            $coursescount->student ?? 0
    ];

    return local_statssibsau_render_table('courses', $data);
}

/**
 * Генерирует таблицу информации о сайте
 *
 * @return string
 * @throws coding_exception
 * @throws dml_exception
 */
function local_statssibsau_render_info_sites() {
    global $OUTPUT, $SESSION;

    $data[] = [
            'Пользователи',
            local_statssibsau_count_users(true)
    ];

    $data[] = [
            'Незаблокированные пользователи',
            local_statssibsau_count_users(false)
    ];

    $data[] = [
            'Преподаватели',
            local_statssibsau_count_users_have_role(LOCAL_STATSSIBSAU_ROLE_TEACHER)
    ];

    $data[] = [
            'Ассистенты',
            local_statssibsau_count_users_have_role(LOCAL_STATSSIBSAU_ROLE_ASSISTANT)
    ];
    $data[] = [
            'Студенты',
            local_statssibsau_count_users_have_role(LOCAL_STATSSIBSAU_ROLE_STUDENT)
    ];
    $title = $SESSION->loggin_teacher ?? 'Нажмите, чтобы посчитать';
    $data[] = [
            'Количество преподавателей за сегодня',
            sprintf('<button class="ajaxLoadData" data-url="%s">%s</button>',
                    new moodle_url('/local/statssibsau/api/index.php', array('type' => LOCAL_STATSSIBSAU_LOGGIN_ROLE_TEACHER)),
                    $title),
    ];
    $title = $SESSION->loggin_assistant ?? 'Нажмите, чтобы посчитать';
    $data[] = [
            'Количество ассистентов за сегодня',
            sprintf('<button class="ajaxLoadData" data-url="%s">%s</button>',
                    new moodle_url('/local/statssibsau/api/index.php', array('type' => LOCAL_STATSSIBSAU_LOGGIN_ROLE_ASSISTANT)),
                    $title),
    ];
    $title = $SESSION->loggin_student ?? 'Нажмите, чтобы посчитать';
    $data[] = [
            'Количество студентов за сегодня',
            sprintf('<button class="ajaxLoadData" data-url="%s">%s</button>',
                    new moodle_url('/local/statssibsau/api/index.php', array('type' => LOCAL_STATSSIBSAU_LOGGIN_ROLE_STUDENT)),
                    $title),
    ];

    return local_statssibsau_render_table('users', $data);
}