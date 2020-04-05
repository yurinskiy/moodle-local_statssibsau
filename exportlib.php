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
 * Library plugin
 *
 * @package   local_statssibsau
 * @copyright 2020, Yuriy Yurinskiy <yuriyyurinskiy@yandex.ru>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

define('LOCAL_STATSSIBSAU_TYPE_EXPORT', array(
        1 => array(
                'text' => 'Ранжированная активность преподавателей по курсам (не реализовано)',
                'disabled' => true,
        ),
        2 => array(
                'text' => 'Вся активность преподавателей по курсам',
                'disabled' => false,
        ),
        3 => array(
                'text' => 'Вся активность преподавателей курсов (не реализовано)',
                'disabled' => true,
        ),
        4 => array(
                'text' => 'Ранжированная активность студентов по курсам (не реализовано)',
                'disabled' => true,
        ),
        5 => array(
                'text' => 'Вся активность студентов по курсам',
                'disabled' => false,
        ),
        6 => array(
                'text' => 'Список курсов',
                'disabled' => false,
        ),
        7 => array(
                'text' => 'Вся активность по преподавателям',
                'disabled' => false,
        )
));

define('LOCAL_STATSSIBSAU_EVENTS', array(
        1 => array(
                'text' => 'Просмотр курса',
                'component' => 'core',
                'action' => 'viewed',
                'target' => 'course',
        ),
        2 => array(
                'text' => 'Прочтение сообщения',
                'component' => 'core',
                'action' => 'viewed',
                'target' => 'message',
        ),
        3 => array(
                'text' => 'Отправка сообщения',
                'component' => 'core',
                'action' => 'sent',
                'target' => 'message',
        ),
        4 => array(
                'text' => 'Просмотр элемента курса "Форум"',
                'component' => 'mod_forum',
                'action' => 'viewed',
                'target' => 'course_module',
        ),
        5 => array(
                'text' => 'Просмотр обсуждения в элементе курса "Форум"',
                'component' => 'mod_forum',
                'action' => 'viewed',
                'target' => 'discussion',
        ),
        6 => array(
                'text' => 'Просмотр элемента курса "Чат"',
                'component' => 'mod_chat',
                'action' => 'viewed',
                'target' => 'course_module',
        ),
        7 => array(
                'text' => 'Отправка сообщения в элементе курса "Чат"',
                'component' => 'mod_chat',
                'action' => 'sent',
                'target' => 'message',
        ),
        8 => array(
                'text' => 'Просмотр результатов теста',
                'component' => 'mod_quiz',
                'action' => 'viewed',
                'target' => 'report',
        ),
        9 => array(
                'text' => 'Оценивание задания',
                'component' => 'mod_assign',
                'action' => 'graded',
                'target' => 'submission',
        ),
        10 => array(
                'text' => 'Просмотр журнала',
                'component' => 'gradereport_grader',
                'action' => 'viewed',
                'target' => 'grade_report',
        )
));

function local_statssibsau_export_prepare_header_csv(array $header, array $events) {
    foreach ($events as $event) {
        $header[] = $event['text'];
    }

    return $header;
}

function local_statssibsau_get_array_events(array $events) {
    $_events = [];
    foreach (LOCAL_STATSSIBSAU_EVENTS as $k => $v) {
        if (in_array($k, $events, false)) {
            $_events[$k] = $v;
        }
    }
    return $_events;
}