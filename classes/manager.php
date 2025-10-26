<?php
namespace tool_idnumbergenerator;

defined('MOODLE_INTERNAL') || die();

class manager {

    public static function generate_user_idnumbers(string $field, string $regex, bool $overwrite): array {
        global $DB;

        $users = $DB->get_records('user', null, '', 'id, username, email, idnumber');
        $results = [];

        foreach ($users as $u) {
            if (!$overwrite && !empty($u->idnumber)) {
                continue;
            }
            $source = $u->$field ?? '';
            

            // Ensure regex has valid delimiters.
            $pattern = trim($regex);

            // Auto-wrap if delimiters missing.
            if (@preg_match($pattern, null) === false) {
                $escaped = str_replace('/', '\/', $pattern);
                $pattern = '/'.$escaped.'/';
            }

            if (preg_match($pattern, $source, $matches)) {
                $newid = $matches[0];
            } else {
                $newid = '';
            }

            if ($newid === null || $newid === '') {
                continue;
            }

            $results[] = (object)[
                'id' => $u->id,
                'username' => $u->username,
                'email' => $u->email,
                'newidnumber' => $newid,
            ];
        }
        return $results;
    }

    public static function apply_user_changes(array $data): int {
        global $DB;
        $transaction = $DB->start_delegated_transaction();

        $count = 0;
        foreach ($data as $record) {
            $DB->set_field('user', 'idnumber', $record['newidnumber'], ['id' => $record['id']]);
            $count++;
        }

        $transaction->allow_commit();
        return $count;
    }

    public static function generate_activity_idnumbers(bool $overwrite = false): array {
        global $DB;

        $sql = "SELECT cm.id AS cmid, cm.idnumber, m.name AS modname, c.id AS courseid, c.fullname AS coursefullname, cm.instance, cm.id AS activityid, cm.id AS id
                FROM {course_modules} cm
                JOIN {modules} m ON m.id = cm.module
                JOIN {course} c ON c.id = cm.course
                WHERE cm.deletioninprogress = 0";
        $records = $DB->get_records_sql($sql);

        $results = [];
        foreach ($records as $r) {
            if (!$overwrite && !empty($r->idnumber)) {
                continue; // Skip existing IDs
            }
            $r->activityname = $DB->get_field($r->modname, 'name', ['id' => $r->instance]);
            $r->proposedidnumber = "{$r->modname}_{$r->courseid}_{$r->activityid}";
            $results[] = $r;
        }

        return $results;
    }

    public static function apply_activity_changes(array $selected): int {
        global $DB;

        $count = 0;
        foreach ($selected as $r) {
            $DB->set_field('course_modules', 'idnumber', $r['proposedidnumber'], ['id' => $r['cmid']]);
            $count++;
        }
        return $count;
    }


}
