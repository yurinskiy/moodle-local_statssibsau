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

define('LOCAL_STATSSIBSAU_TYPE_EXPORT',array(
        1 => 'Ранжированная активность преподавателей',
        2 => 'Вся активность преподавателей',
        3 => 'Вся активность преподавателей курсов',
        4 => 'Ранжированная активность студентов',
        5 => 'Вся активность студентов',
        6 => 'Список курсов',
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
 * @param int $categoryid
 * @param array $categorytree
 * @return string
 * @throws coding_exception
 * @throws dml_exception
 */
function local_statssibsau_export_list_courses(int $categoryid, array $categorytree = []) {
    global $DB;

    $result = '';

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
    ], 'visible', 'id, fullname, visible');

    foreach ($courses as $course) {
        $temp = [];
        $temp[] = $course->id;
        $temp[] = $course->fullname;
        $temp[] = $course->visible ? 'Курс опубликован' : 'Курс скрыт';
        $temp[] = $category->visible ? 'Категория опубликована' : 'Категория скрыта';

        foreach ($categorytree as $v) {
            $temp[] = $v;
        }

        $result .= implode(',', $temp) . PHP_EOL;
    }

    $categories = $DB->get_records('course_categories', [
            'parent' => $categoryid
    ], 'visible', 'id');

    foreach ($categories as $category) {
        $result .= local_statssibsau_export_list_courses($category->id, $categorytree);
    }

    return $result;
}