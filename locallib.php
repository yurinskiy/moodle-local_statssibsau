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

define('LOCAL_STATSSIBSAU_ROLE_TEACHER', 3);
define('LOCAL_STATSSIBSAU_ROLE_ASSISTANT', 4);
define('LOCAL_STATSSIBSAU_ROLE_STUDENT', 5);

define('LOCAL_STATSSIBSAU_LOGGIN_ROLE_TEACHER', 1);
define('LOCAL_STATSSIBSAU_LOGGIN_ROLE_ASSISTANT', 2);
define('LOCAL_STATSSIBSAU_LOGGIN_ROLE_STUDENT', 3);

$midnight = new DateTime('midnight', core_date::get_server_timezone_object());

define('LOCAL_STATSSIBSAU_DBEG', $midnight->getTimestamp());
define('LOCAL_STATSSIBSAU_DEND', $midnight->modify('+1day - 1second')->getTimestamp());

/**
 * Возвращает количество успешных попыток авторизации между датами
 *
 * @param int $role
 * @param int $dbeg
 * @param int $dend
 * @return int
 * @throws dml_exception
 */
function local_statssibsau_count_loggedin(int $role, int $dbeg, int $dend) {
    global $DB;
    $sql = 'SELECT count(distinct u.id)
FROM {user} u
         LEFT JOIN {role_assignments} a ON a.userid = u.id
         LEFT JOIN {context} b ON a.contextid = b.id
         LEFT JOIN {course} c ON b.instanceid = c.id
WHERE u.username <> \'guest\' -- отсееваем учетку гостя
  AND u.suspended = 0       -- отсееваем заблокированных пользователей
  AND a.roleid = :role          -- 3 - преподаватель, 4 - ассистент, 5 - студент
  AND b.contextlevel = 50   -- 50 - курсы, 40 - категории
  AND c.id is not null
  AND EXISTS( SELECT 1
           FROM {logstore_standard_log} l
           WHERE l.userid = u.id
           AND l.timecreated BETWEEN :dbeg AND :dend -- ограничение по датам
           AND l.contextlevel = 10   
           AND l.component = \'core\'
           AND l.action = \'loggedin\'
           AND l.target = \'user\' )';
    $params = [
            'role' => $role,
            'dbeg' => $dbeg,
            'dend' => $dend,
    ];

    $result = $DB->count_records_sql($sql, $params);

    if (is_numeric($result)) {
        return $result;
    }

    return 0;
}

/**
 * Возвращает информацию о категории
 *
 * @param int $category
 * @return StdClass
 * @throws dml_exception
 */
function local_statssibsau_count_courses($category = 0) {
    global $DB;

    $result = new StdClass();

    if (0 === (int) $category) {
        $result->visible = $DB->count_records('course', [
                        'visible' => 1
                ]) - 1;

        $result->hidden = $DB->count_records('course', [
                'visible' => 0
        ]);

        $result->teacher = local_statssibsau_count_users_have_role(LOCAL_STATSSIBSAU_ROLE_TEACHER);
        $result->assistant = local_statssibsau_count_users_have_role(LOCAL_STATSSIBSAU_ROLE_ASSISTANT);
        $result->student = local_statssibsau_count_users_have_role(LOCAL_STATSSIBSAU_ROLE_STUDENT);

    } else {
        $result->visible = $DB->count_records('course', [
                'visible' => 1,
                'category' => $category
        ]);

        $result->hidden = $DB->count_records('course', [
                'visible' => 0,
                'category' => $category
        ]);

        $result->teacher = local_statssibsau_users_have_role(LOCAL_STATSSIBSAU_ROLE_TEACHER, $category);
        $result->assistant = local_statssibsau_users_have_role(LOCAL_STATSSIBSAU_ROLE_ASSISTANT, $category);
        $result->student = local_statssibsau_users_have_role(LOCAL_STATSSIBSAU_ROLE_STUDENT, $category);

        $records = $DB->get_records('course_categories', [
                'parent' => $category
        ]);

        while (count($records) > 0) {
            $record = array_shift($records);

            $result->visible += $DB->count_records('course', [
                    'visible' => 1,
                    'category' => $record->id
            ]);

            $result->hidden += $DB->count_records('course', [
                    'visible' => 0,
                    'category' => $record->id
            ]);

            $result->teacher =
                    merge($result->teacher, local_statssibsau_users_have_role(LOCAL_STATSSIBSAU_ROLE_TEACHER, $record->id));

            $result->assistant =
                    merge($result->assistant, local_statssibsau_users_have_role(LOCAL_STATSSIBSAU_ROLE_ASSISTANT, $record->id));

            $result->student =
                    merge($result->student, local_statssibsau_users_have_role(LOCAL_STATSSIBSAU_ROLE_STUDENT, $record->id));

            $records += $DB->get_records('course_categories', [
                    'parent' => $record->id
            ]);
        }

        $result->teacher = count(array_unique($result->teacher));
        $result->assistant = count(array_unique($result->assistant));
        $result->student = count(array_unique($result->student));
    }

    $result->all = $result->visible + $result->hidden;

    return $result;
}

/**
 * Возвращает количество учетных записей
 *
 * @param bool $withSuspended - если FALSE, то считает только незаблокированных пользователей
 * @return int
 * @throws dml_exception
 */
function local_statssibsau_count_users($withSuspended = false) {
    global $DB;
    $sql = 'SELECT COUNT(*) FROM {user} u WHERE u.username <> \'guest\'';
    $params = [];

    if (!$withSuspended) {
        $sql .= ' AND u.suspended=:suspended';

        $params['suspended'] = 0;
    }

    $result = $DB->count_records_sql($sql, $params);

    if (is_numeric($result)) {
        return $result;
    }

    return 0;
}

/**
 * @param int $courserole
 * @return int
 * @throws dml_exception
 */
function local_statssibsau_count_users_have_role($courserole) {
    global $DB;
    $sql = '
SELECT COUNT(DISTINCT u.id) 
  FROM {user} u 
 WHERE u.username <> \'guest\'
   AND u.suspended = :suspended 
   AND EXISTS(
       SELECT 1 userid
         FROM {role_assignments} a
   INNER JOIN {context} b ON a.contextid=b.id
   INNER JOIN {course} c ON b.instanceid=c.id
        WHERE a.userid = u.id 
          AND b.contextlevel = :contextlevel
          AND a.roleid = :courserole
   )';

    $params = [
            'suspended' => 0,
            'contextlevel' => CONTEXT_COURSE,
            'courserole' => $courserole
    ];

    $result = $DB->count_records_sql($sql, $params);

    if (is_numeric($result)) {
        return $result;
    }

    return 0;
}

/**
 * @param int $courserole
 * @param int $categoryid
 * @return array
 * @throws dml_exception
 */
function local_statssibsau_users_information_by_role_category($courserole, $categoryid = 0) {
    global $DB;
    $sql = '
SELECT DISTINCT u.id, u.email, u.firstname, u.lastname 
  FROM {user} u 
 WHERE u.username <> \'guest\'
   AND u.suspended = :suspended 
   AND EXISTS(
       SELECT 1 userid
         FROM {role_assignments} a
   INNER JOIN {context} b ON a.contextid=b.id
   INNER JOIN {course} c ON b.instanceid=c.id AND (c.category = :category OR 0 = :main_category)
        WHERE a.userid = u.id 
          AND b.contextlevel = :contextlevel
          AND a.roleid = :courserole
   )';

    $params = [
            'suspended' => 0,
            'contextlevel' => CONTEXT_COURSE,
            'courserole' => $courserole,
            'category' => $categoryid,
            'main_category' => $categoryid,
    ];

    $result = $DB->get_records_sql($sql, $params);

    if ($categoryid > 0) {
        $categories = $DB->get_records('course_categories', [
                'parent' => $categoryid,
                'visible' => 1,
        ], 'sortorder', 'id');

        foreach ($categories as $category) {
            foreach (local_statssibsau_users_information_by_role_category($courserole, $category->id) as $user) {
                if (!array_key_exists($user->id, $result)) {
                    $result[] = $user;
                }
            }
        }
    }

    return $result;
}

/**
 * @param int $courserole
 * @param $category
 * @return array
 * @throws dml_exception
 */
function local_statssibsau_users_have_role($courserole, $category) {
    global $DB;
    $sql = '
SELECT DISTINCT u.id
  FROM {user} u 
 WHERE u.username <> \'guest\'
   AND u.suspended = :suspended 
   AND EXISTS(
       SELECT 1
         FROM {role_assignments} a
   INNER JOIN {context} b ON a.contextid=b.id
   INNER JOIN {course} c ON b.instanceid=c.id AND c.category = :category
        WHERE a.userid = u.id 
          AND b.contextlevel = :contextlevel
          AND a.roleid = :courserole
   )';

    $params = [
            'suspended' => 0,
            'contextlevel' => CONTEXT_COURSE,
            'courserole' => $courserole,
            'category' => $category
    ];

    $result = $DB->get_records_sql($sql, $params);

    return array_map(static function($a) {
        return $a->id;
    }, $result);
}

/**
 * Export list course
 *
 * @param int $categoryid
 * @param array $categorytree
 * @return array
 * @throws coding_exception
 * @throws dml_exception
 */
function local_statssibsau_list_courses(int $categoryid, array $categorytree = []) {
    global $DB;

    $result = [];

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
    ], 'sortorder', 'id, shortname, fullname, visible');

    foreach ($courses as $course) {
        $fields = [];
        $fields[] = $course->id;
        $fields[] = $course->shortname;
        $fields[] = $course->fullname;
        $fields[] = $course->visible ? 'Курс опубликован' : 'Курс скрыт';
        $fields[] = $category->visible ? 'Категория опубликована' : 'Категория скрыта';

        foreach ($categorytree as $v) {
            $fields[] = $v;
        }

        $result[] = $fields;
    }

    $categories = $DB->get_records('course_categories', [
            'parent' => $categoryid
    ], 'sortorder', 'id');

    foreach ($categories as $category) {
        $result = merge($result, local_statssibsau_list_courses($category->id, $categorytree));
    }

    return $result;
}

/**
 * Получаем данные по активности пользователей
 *
 * @param int $categoryid
 * @param int $roleid
 * @param int $userid
 * @param $dbeg
 * @param $dend
 * @param array $events
 * @return array
 * @throws dml_exception
 */
function local_statssibsau_user_activity(int $categoryid, int $roleid, $userid, $dbeg, $dend, array $events) {
    global $DB;

    $result = [];

    $courses = $DB->get_records('course', [
            'category' => $categoryid,
            'visible' => 1,
    ], 'sortorder', 'id, fullname');

    foreach ($courses as $course) {
        $fields = [];
        $fields['idcourse'] = $course->id;
        $fields['coursename'] = $course->fullname;

        $sql = '
select count(*) from {logstore_standard_log} l
where l.component = :component
AND l.action = :action
AND l.target = :target
AND l.courseid = :courseid
AND l.timecreated BETWEEN :dbeg AND :dend
and exists(
SELECT 1
     FROM {role_assignments} a
INNER JOIN {context} b ON a.contextid=b.id
INNER JOIN {course} c ON b.instanceid=c.id
    WHERE a.userid = l.userid 
      AND c.id = l.courseid
      AND a.roleid = :roleid)';

        if (null !== $userid) {
            $sql .= ' and l.userid = :userid';
        }


        foreach ($events as $key => $event) {
            $params = [];
            $params['component'] = $event['component'];
            $params['action'] = $event['action'];
            $params['target'] = $event['target'];
            $params['roleid'] = $roleid;
            $params['dbeg'] = $dbeg;
            $params['dend'] = $dend;
            $params['contextlevel'] = CONTEXT_COURSE;
            $params['courseid'] = $course->id;

            if (null !== $userid) {
                $params['userid'] = $userid;
            }

            $fields[$key] = $DB->count_records_sql($sql, $params);
        }

        $result[] = $fields;
    }

    $categories = $DB->get_records('course_categories', [
            'parent' => $categoryid,
            'visible' => 1,
    ], 'sortorder', 'id');

    foreach ($categories as $category) {
        $result = merge($result, local_statssibsau_user_activity($category->id, $roleid, $userid, $dbeg, $dend, $events));
    }

    return $result;
}

function local_statssibsau_user_activity_global(int $roleid, $userid, $dbeg, $dend, array $events) {
    global $DB;

    $result = [];

    $sql = '
select count(*) from {logstore_standard_log} l
where l.component = :component
AND l.action = :action
AND l.target = :target
AND l.timecreated BETWEEN :dbeg AND :dend
and exists(
SELECT 1
     FROM {role_assignments} a
INNER JOIN {context} b ON a.contextid=b.id
    WHERE a.userid = l.userid 
      AND a.roleid = :roleid)';

    if (null !== $userid) {
        $sql .= ' and l.userid = :userid';
    }

    foreach ($events as $key => $event) {
        $params = [];
        $params['roleid'] = $roleid;
        $params['dbeg'] = $dbeg;
        $params['dend'] = $dend;
        $params['component'] = $event['component'];
        $params['action'] = $event['action'];
        $params['target'] = $event['target'];

        if (null !== $userid) {
            $params['userid'] = $userid;
        }

        $result[$key] = $DB->count_records_sql($sql, $params);
    }

    return $result;
}

function local_statssibsau_list_users($categoryid, $roleid, $dbeg, $dend, array $events) {
    global $DB;

    $result = [];

    if (0 === (int) $categoryid) {
        $users = local_statssibsau_users_information_by_role_category($roleid);

        foreach ($users as $user) {
            // Если пользователей не новый, то пропускаем мы его уже посчитали
            if (array_key_exists($user->id, $result)) {
                continue;
            }

            $temp = [];
            $temp['id'] = $user->id;
            $temp['email'] = $user->email;
            $temp['fio'] = trim($user->firstname . ' ' . $user->lastname);

            foreach (local_statssibsau_user_activity_global($roleid, $user->id, $dbeg, $dend, $events) as $key => $cnt) {
                $temp[$key] = $cnt;
            }

            $result[$user->id] = $temp;
        }

    } else {
        $users = local_statssibsau_users_information_by_role_category($roleid, $categoryid);

        foreach ($users as $user) {
            // Если пользователей не новый, то пропускаем мы его уже посчитали
            if (array_key_exists($user->id, $result)) {
                continue;
            }

            $temp = [];
            $temp['id'] = $user->id;
            $temp['email'] = $user->email;
            $temp['fio'] = trim($user->firstname . ' ' . $user->lastname);

            foreach (local_statssibsau_user_activity($categoryid, $roleid, $user->id, $dbeg, $dend, $events) as $key => $course) {
                foreach ($course as $k => $cnt) {
                    if (!is_numeric($k)) {
                        continue;
                    }
                    // Складываем разрозненную статистику по курсам
                    if (array_key_exists($k, $temp)) {
                        $temp[$k] += $cnt;
                    } else {
                        $temp[$k] = $cnt;
                    }
                }
            }

            $result[$user->id] = $temp;
        }

        $categories = $DB->get_records('course_categories', [
                'parent' => $categoryid
        ]);
        foreach ($categories as $category) {
            $result = addNewUsers($result, local_statssibsau_list_users($category->id, $roleid, $dbeg, $dend, $events));
        }
    }

    return $result;
}

function local_statssibsau_list_empty_courses($categoryid, $categorytree = []) {
    global $DB;

    $result = [];

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
    ], 'sortorder', 'id, shortname, fullname, visible');

    foreach ($courses as $course) {
        $count = $DB->count_records('course_modules', [
                'course' => $course->id,
        ]);

        if ($count > 0) {
            continue;
        }

        $fields = [];
        $fields[] = $course->id;
        $fields[] = $course->shortname;
        $fields[] = $course->fullname;
        $fields[] = $course->visible ? 'Курс опубликован' : 'Курс скрыт';
        $fields[] = $category->visible ? 'Категория опубликована' : 'Категория скрыта';

        foreach ($categorytree as $v) {
            $fields[] = $v;
        }

        $result[] = $fields;
    }

    $categories = $DB->get_records('course_categories', [
            'parent' => $categoryid
    ], 'sortorder', 'id');

    foreach ($categories as $category) {
        $result = merge($result, local_statssibsau_list_empty_courses($category->id, $categorytree));
    }

    return $result;
}


function local_statssibsau_list_empty_courses_short($categoryid, $categorytree = []) {
    global $DB;

    $result = [];

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

    $count = $DB->count_records_sql('select count(c.id) from {course} c where c.category = :category and not exists(select 1 from {course_modules} m where m.course = c.id)', [
            'category' => $categoryid
    ]);

    $fields = [];

    $fields[] = $count;

    foreach ($categorytree as $v) {
        $fields[] = $v;
    }

    $result[] = $fields;


    $categories = $DB->get_records('course_categories', [
            'parent' => $categoryid
    ], 'sortorder', 'id');

    foreach ($categories as $category) {
        $result = merge($result, local_statssibsau_list_empty_courses_short($category->id, $categorytree));
    }

    return $result;
}

/**
 * Объединяет массивы
 *
 * @param $a
 * @param $b
 * @return array
 */
function merge($a, $b) {
    foreach ($b as $key => $arr) {
        $a[] = $arr;
        unset($b[$key]);
    }
    return $a;
}

/**
 * Объединяет массивы пользователей
 *
 * @param $a
 * @param $b
 * @return array
 */
function addNewUsers($a, $b) {
    foreach ($b as $key => $data) {
        if (!array_key_exists($key, $a)) {
            $a[$key] = $data;
        }

        unset($b[$key]);
    }

    return $a;
}

/*function ddd(...$arg) {
    echo '<pre>';
    foreach ($arg as $key => $item) {
        echo print_r($key, true), PHP_EOL;
        echo print_r($item, true);
    }
    echo '</pre>';
}*/