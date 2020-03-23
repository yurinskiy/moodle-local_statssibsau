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
        1 => 'Ранжированная активность преподавателей по курсам (не реализовано)',
        2 => 'Вся активность преподавателей по курсам (в разработке)',
        3 => 'Вся активность преподавателей курсов (не реализовано)',
        4 => 'Ранжированная активность студентов по курсам (не реализовано)',
        5 => 'Вся активность студентов по курсам (в разработке)',
        6 => 'Список курсов',
));

define('LOCAL_STATSSIBSAU_COURSE_VIEWED', array(
        'text' => 'Просмотр курса',
        'component' => 'core',
        'action' => 'viewed',
        'target' => 'course',
));

define('LOCAL_STATSSIBSAU_MESSAGE_VIEWED', array(
        'text' => 'Прочтение сообщения',
        'component' => 'core',
        'action' => 'viewed',
        'target' => 'message',
));

define('LOCAL_STATSSIBSAU_MESSAGE_SENT', array(
        'text' => 'Отправка сообщения',
        'component' => 'core',
        'action' => 'sent',
        'target' => 'message',
));

define('LOCAL_STATSSIBSAU_MOD_FORUM_VIEWED', array(
        'text' => 'Просмотр элемента курса "Форум"',
        'component' => 'mod_forum',
        'action' => 'viewed',
        'target' => 'course_module',
));

define('LOCAL_STATSSIBSAU_MOD_FORUM_DISCUSSION_VIEWED', array(
        'text' => 'Просмотр обсуждения в элементе курса "Форум"',
        'component' => 'mod_forum',
        'action' => 'viewed',
        'target' => 'discussion',
));

define('LOCAL_STATSSIBSAU_MOD_CHAT_VIEWED', array(
        'text' => 'Просмотр элемента курса "Чат"',
        'component' => 'mod_chat',
        'action' => 'viewed',
        'target' => 'course_module',
));

define('LOCAL_STATSSIBSAU_MOD_CHAT_SENT', array(
        'text' => 'Отправка сообщения в элементе курса "Чат"',
        'component' => 'mod_chat',
        'action' => 'sent',
        'target' => 'message',
));

define('LOCAL_STATSSIBSAU_MOD_QUIZ_VIEWED', array(
        'text' => 'Просмотр результатов теста',
        'component' => 'mod_quiz',
        'action' => 'viewed',
        'target' => 'report',
));

define('LOCAL_STATSSIBSAU_MOD_ASSIGN_GRADED', array(
        'text' => 'Оценивание задания',
        'component' => 'mod_assign',
        'action' => 'graded',
        'target' => 'submission',
));

define('LOCAL_STATSSIBSAU_GRADED_VIEWED', array(
        'text' => 'Просмотр журнала',
        'component' => 'gradereport_grader',
        'action' => 'viewed',
        'target' => 'grade_report',
));

/**
 * Response file
 *
 * @param $filepath
 * @param $filename
 */
function local_statssibsau_file_csv_export($filepath, $filename) {
    // заставляем браузер показать окно сохранения файла
    header('Content-Description: File Transfer');
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename=' . $filename);
    header('Content-Transfer-Encoding: binary');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Content-Length: ' . filesize($filepath));

    $handle = fopen($filepath, 'rb');

    if ($handle) {
        // сбрасываем буфер вывода PHP, чтобы избежать переполнения памяти выделенной под скрипт
        // если этого не сделать файл будет читаться в память полностью!
        if (ob_get_level()) {
            ob_end_clean();
        }

        // читаем файл и отправляем его пользователю
        readfile($filepath);

        fclose($handle);
    } else {
        exit(1);
    }

    register_shutdown_function('local_statssibsau_delete_temp_file', $filepath);

    exit;
}

/**
 * Delete file if exists
 *
 * @param $filepath
 */
function local_statssibsau_delete_temp_file($filepath) {
    if (file_exists($filepath)) {
        unlink($filepath);
    }
}

/**
 * Export list course
 *
 * @param $handle - Указатель на файл должен быть корректным и указывать на файл, успешно открытый функциями fopen()
 * @param int $categoryid
 * @param array $categorytree
 * @throws coding_exception
 * @throws dml_exception
 */
function local_statssibsau_export_list_courses($handle, int $categoryid, array $categorytree = []) {
    global $DB;

    if (0 === $categoryid) {
        $category = new stdClass();
        $category->name = get_string('top');
        $category->visible = 1;
    } else {
        $category = $DB->get_record('course_categories', [
                'id' => $categoryid
        ], 'name, visible');
    }

    $categorytree[] = $category->name;

    $courses = $DB->get_records('course', [
            'category' => $categoryid
    ], 'sortorder', 'id, fullname, visible');

    foreach ($courses as $course) {
        $fields = [];
        $fields[] = $course->id;
        $fields[] = $course->fullname;
        $fields[] = $course->visible ? 'Курс опубликован' : 'Курс скрыт';
        $fields[] = $category->visible ? 'Категория опубликована' : 'Категория скрыта';

        foreach ($categorytree as $v) {
            $fields[] = $v;
        }

        fputcsv($handle, $fields);
    }

    $categories = $DB->get_records('course_categories', [
            'parent' => $categoryid
    ], 'sortorder', 'id');

    foreach ($categories as $category) {
        local_statssibsau_export_list_courses($handle, $category->id, $categorytree);
    }
}

function local_statssibsau_export_student_activity($handle, int $categoryid, int $roleid, $dbeg, $dend, array $typelogs) {
    global $DB;

    $courses = $DB->get_records('course', [
            'category' => $categoryid,
            'visible' => 1,
    ], 'sortorder', 'id, fullname');

    foreach ($courses as $course) {
        $fields = [];
        $fields[] = $course->id;
        $fields[] = $course->fullname;

        $sql = '
select count(*) from {logstore_standard_log} l
where l.component = :component
AND l.action = :action
AND l.target = :target
AND l.courseid = :courseid
AND l.timecreated BETWEEN :dbeg AND :dend
and l.contextlevel = :contextlevel
and exists(select 1 from {role_assignments} a
join {user} u on u.id = a.userid and u.username <> \'guest\'
where a.roleid = :roleid and a.userid = l.userid and a.contextid = l.contextid)';

        foreach ($typelogs as $typelog) {
            $params = [];
            $params['component'] = $typelog['component'];
            $params['action'] = $typelog['action'];
            $params['target'] = $typelog['target'];
            $params['roleid'] = $roleid;
            $params['dbeg'] = $dbeg;
            $params['dend'] = $dend;
            $params['contextlevel'] = CONTEXT_COURSE;
            $params['courseid'] = $course->id;

            $fields[] = $DB->count_records_sql($sql, $params);
        }

        fputcsv($handle, $fields);
    }

    $categories = $DB->get_records('course_categories', [
            'parent' => $categoryid,
            'visible' => 1,
    ], 'sortorder', 'id');

    foreach ($categories as $category) {
        local_statssibsau_export_student_activity($handle, $category->id, $roleid, $dbeg, $dend, $typelogs);
    }
}

function local_statssibsau_export_prepare_header_csv(array $header, array $events) {
    foreach ($events as $event) {
        $header[] = $event['text'];
    }

    return $header;
}