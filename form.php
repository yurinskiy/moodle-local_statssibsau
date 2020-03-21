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

/**
 * This class is form course categories
 *
 * @copyright 2019, YuriyYurinskiy <yuriyyurinskiy@yandex.ru>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_statssibsau_form_categories extends moodleform
{
    /**
     * @see lib/moodleform#definition()
     */
    public function definition()
    {
        $mform = $this->_form;

        $options = array();
        $options[0] = get_string('top');
        $options = array_merge($options, core_course_category::make_categories_list('moodle/category:manage'));

        $mform->addElement('select', 'categoryid', get_string('categories'), $options);
        $mform->setDefault('categoryid', $this->_customdata['categoryid']);

        $this->add_action_buttons(false, 'Отфильтровать');
    }
}