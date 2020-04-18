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

require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/form.php');
require_once(__DIR__ . '/locallib.php');
require_once(__DIR__ . '/render.php');

$categoryid = optional_param('categoryid', 0, PARAM_INT);

$url = new moodle_url('/local/statssibsau/index.php');
$systemcontext = $context = context_system::instance();
if ($categoryid) {
    $category = core_course_category::get($categoryid);
    $context = context_coursecat::instance($category->id);
    $url->param('categoryid', $category->id);
}

$pagetitle = get_string('pluginname', 'local_statssibsau');
$pageheading = format_string($SITE->fullname, true, array('context' => $systemcontext));

$PAGE->set_context($context);
$PAGE->set_url($url);
$PAGE->set_pagelayout('admin');
$PAGE->set_title($pagetitle);
$PAGE->set_heading($pageheading);

$PAGE->requires->jquery();
$PAGE->requires->js(new moodle_url('/local/statssibsau/js/module.js'));

navigation_node::override_active_url($url);

if ($categoryid) {
    $PAGE->navbar->add(get_string('administrationsite'), new moodle_url('/admin/search.php'));
    $PAGE->navbar->add(get_string('reports'), new moodle_url('/admin/category.php', array('category' => 'reports')));
    $PAGE->navbar->add($pagetitle, new moodle_url('/local/statssibsau/index.php'));
    $PAGE->navbar->add($category->name);
}

/** Проверяем авторизован ли пользователь */
require_login();

/** Проверяем права пользователя */
if (!is_siteadmin() && !has_capability('local/statssibsau:view', $context)) {
    header('Location: ' . $CFG->wwwroot);
    die();
}

$mform = new local_statssibsau_form_categories(null, array('categoryid' => $categoryid));
if ($mform->get_data() && property_exists($mform->get_data(), LOCAL_STATSSIBSAU_REDIRECT_TO_REPORT_COURSESIZE)) {
    redirect(new moodle_url('/report/coursesize/index.php', ['category' => $mform->get_data()->categoryid]));
    die();
}

if ($mform->get_data() && property_exists($mform->get_data(), LOCAL_STATSSIBSAU_REDIRECT_TO_REPORT_COURSESIZE_DOWNLOAD)) {
    redirect(new moodle_url('/report/coursesize/index.php', [
            'category' => $mform->get_data()->categoryid,
            'download' => 1
    ]));
    die();
}

echo $OUTPUT->header();
echo $OUTPUT->heading('Статистика по пользователями');

echo local_statssibsau_render_info_sites();

echo $OUTPUT->heading(isset($category) ? 'Статистика по курсам для категории «' . $category->name . '»' : 'Статистика по курсам');

$mform->display();

echo local_statssibsau_render_info_courses($categoryid);

echo $OUTPUT->heading('Экспорт');

$mform = new local_statssibsau_form_teacher_actions(new moodle_url('/local/statssibsau/api/export.php'), array(
        'categoryid' => $categoryid,
        'dbeg' => LOCAL_STATSSIBSAU_DBEG,
        'dend' => LOCAL_STATSSIBSAU_DEND,
));
$mform->display();

echo $OUTPUT->footer();