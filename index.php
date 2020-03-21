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
require_once('./form.php');
require_once('./locallib.php');
require_once('./render.php');

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

echo $OUTPUT->header();
echo $OUTPUT->heading('Статистика по пользователями');

echo local_statssibsau_render_info_sites();

echo $OUTPUT->heading(isset($category) ? 'Статистика по курсам для категории «' . $category->name .'»' : 'Статистика по курсам');

$mform = new local_statssibsau_form_categories(null, array('categoryid' => $categoryid));
$mform->display();

echo local_statssibsau_render_info_courses($categoryid);

?>

    <script>
        function getLoggedinNow(context) {
            $.ajax({
                type: 'POST',
                url: '<?php echo new moodle_url('/local/statssibsau/api/index.php'); ?>?type=' + context.data('type'),
                beforeSend: function() {
                    context.html('Загрузка...');
                },
                success: function(data){
                    context.html(data.count);
                    context.attr('disabled', true);
                },
                error: function () {
                    context.html('Что-то пошло не так...')
                },
            });
        }
    </script>

<?php

echo $OUTPUT->footer();