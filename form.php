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
 * This file contains the code for the plugin integration.
 *
 * @package   local_statssibsau
 * @copyright 2020, Yuriy Yurinskiy <yuriyyurinskiy@yandex.ru>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir . '/formslib.php');
require_once (__DIR__ . '/exportlib.php');

define('LOCAL_STATSSIBSAU_REDIRECT_TO_REPORT_COURSESIZE', 'coursesizebutton');

/**
 * This class is form course categories
 *
 * @copyright 2019, YuriyYurinskiy <yuriyyurinskiy@yandex.ru>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_statssibsau_form_categories extends moodleform {
    /**
     * @see lib/moodleform#definition()
     */
    public function definition() {
        $mform = $this->_form;

        $options = array();
        $options[0] = get_string('top');
        $options += core_course_category::make_categories_list('moodle/category:manage');

        $mform->addElement('select', 'categoryid', get_string('categories'), $options);
        $mform->setDefault('categoryid', $this->_customdata['categoryid']);


        $buttonarray=array();
        $buttonarray[] = &$mform->createElement('submit', 'submitbutton', 'Отфильтровать');
        $buttonarray[] = &$mform->createElement('submit', LOCAL_STATSSIBSAU_REDIRECT_TO_REPORT_COURSESIZE, 'Показать размер курсов');
        $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);
        $mform->closeHeaderBefore('buttonar');
    }
}

/**
 * This class is form course categories
 *
 * @copyright 2019, YuriyYurinskiy <yuriyyurinskiy@yandex.ru>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_statssibsau_form_teacher_actions extends moodleform {
    /**
     * @see lib/moodleform#definition()
     */
    public function definition() {
        $mform = $this->_form;

        $options = LOCAL_STATSSIBSAU_TYPE_EXPORT;

        $select = (new HTML_QuickForm())->createElement('select', 'type', 'Тип выгрузки');
        foreach ($options as $k => $v) {
            if (in_array($k, [1, 3, 4], true)) {
                $select->addOption($v, $k, array('disabled' => 'disabled'));
            } else {
                $select->addOption($v, $k);
            }
        }
        $mform->addElement($select);

        $mform->addElement('text', 'custom_courses', 'Идентификаторы курсов через запятую', array('size' => '20'));
        $mform->setType('custom_courses', PARAM_TEXT);

        $options = array();
        $options[0] = get_string('top');
        $options += core_course_category::make_categories_list('moodle/category:manage');

        $mform->addElement('select', 'categoryid', get_string('categories'), $options);
        $mform->setDefault('categoryid', $this->_customdata['categoryid']);

        $mform->addElement('date_time_selector', 'dbeg', get_string('from'));
        $mform->setDefault('dbeg', $this->_customdata['dbeg']);
        $mform->addElement('date_time_selector', 'dend', get_string('to'));
        $mform->setDefault('dend', $this->_customdata['dend']);

        $mform->addRule('type', 'Выберите тип выгрузки', 'required');

        $mform->disabledIf('custom_courses', 'type', 'neq', 3);
        $mform->disabledIf('categoryid', 'type', 'eq', 3);
        $mform->disabledIf('dbeg', 'type', 'eq', 6);
        $mform->disabledIf('dend', 'type', 'eq', 6);

        $this->add_action_buttons(false, 'Выгрузить');
    }
}