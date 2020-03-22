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
 *
 *
 * @package   local_statssibsau
 * @copyright 2020, Yuriy Yurinskiy <yuriyyurinskiy@yandex.ru>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../../config.php');
require_once(__DIR__ . '/../locallib.php');

header('Content-Type: application/json');

$type = required_param('type', PARAM_INT);
$dbeg = optional_param('dbeg', LOCAL_STATSSIBSAU_DBEG, PARAM_INT);
$dend = optional_param('dend', LOCAL_STATSSIBSAU_DEND, PARAM_INT);

/** Проверяем права пользователя */
if (!is_siteadmin() && !has_capability('local/statssibsau:view', $context)) {
    $error = [
            'error' => 'yes',
            'code_error' => '403',
            'message' => '403 Forbidden',
    ];
    echo json_encode($error);
    die();
}

switch ($type) {
    case LOCAL_STATSSIBSAU_LOGGIN_ROLE_TEACHER:
        $result = [
                'count' => local_statssibsau_count_loggedin(LOCAL_STATSSIBSAU_ROLE_TEACHER, $dbeg, $dend),
        ];
        $SESSION->loggin_teacher = $result['count'];
        echo json_encode($result);
        break;
    case LOCAL_STATSSIBSAU_LOGGIN_ROLE_ASSISTANT:
        $result = [
                'count' => local_statssibsau_count_loggedin(LOCAL_STATSSIBSAU_ROLE_ASSISTANT, $dbeg, $dend),
        ];
        $SESSION->loggin_assistant = $result['count'];
        echo json_encode($result);
        break;
    case LOCAL_STATSSIBSAU_LOGGIN_ROLE_STUDENT:
        $result = [
                'count' => local_statssibsau_count_loggedin(LOCAL_STATSSIBSAU_ROLE_STUDENT, $dbeg, $dend),
        ];
        $SESSION->loggin_student = $result['count'];
        echo json_encode($result);
        break;
    default:
        $error = [
                'error' => 'yes',
                'code_error' => '404',
                'message' => 'This type not found',
        ];
        echo json_encode($error);
}
die();