<?php
namespace tool_idnumbergenerator;

defined('MOODLE_INTERNAL') || die();

class manager {

    public static function generate_user_idnumbers(string $field, string $regex, bool $overwrite): array {
        global $DB;

        // Fetch all users with minimal fields needed.
        $users = $DB->get_records('user', null, '', 'id, username, email, idnumber, firstname, lastname');
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
                $pattern = '/' . $escaped . '/';
            }

            // Try to extract match using regex.
            if (preg_match($pattern, $source, $matches)) {
                $match = $matches[0];
            } else {
                $match = $source; // fallback if regex doesn't match
            }

            // Clean parts for ID-safe format.
            $lastname = self::clean_id_part($u->lastname ?? '');
            $firstname = self::clean_id_part($u->firstname ?? '');
            $matchpart = self::clean_id_part($match ?? '');

            // Build final ID format: lastname_firstname_regexpattern
            $newid = "{$lastname}-{$firstname}__{$matchpart}";

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

        $sql = "SELECT cm.id AS cmid, cm.idnumber, m.name AS modname, 
                       c.id AS courseid, c.fullname AS coursefullname, 
                       cm.instance, cm.id AS activityid, cm.id AS id
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

            // Get the full activity name from its instance table.
            $r->activityname = $DB->get_field($r->modname, 'name', ['id' => $r->instance]);

            // Clean and normalize the activity name for ID-safe format.
            $cleanname = self::clean_id_part($r->activityname ?? '');

            // Build the final ID format:
            // [activitytype]_[courseid]_[activityid]_[cleanedfullactivityname]
            $r->proposedidnumber = "{$cleanname}__{$r->modname}-{$r->courseid}-{$r->activityid}";

            $results[] = $r;
        }

        return $results;
    }

    public static function apply_activity_changes(array $selected): int {
        global $DB;

        $count = 0;
        $transaction = $DB->start_delegated_transaction();

        try {
            foreach ($selected as $r) {
                if (empty($r['cmid']) || empty($r['proposedidnumber'])) {
                    continue;
                }

                $cmid = (int)$r['cmid'];
                $newid = clean_param($r['proposedidnumber'], PARAM_NOTAGS);

                // Update course module.
                $DB->set_field('course_modules', 'idnumber', $newid, ['id' => $cmid]);
                $count++;

                // Update corresponding grade item.
                $sql = "SELECT gi.id
                          FROM {grade_items} gi
                          JOIN {course_modules} cm ON gi.itemmodule = (
                              SELECT name FROM {modules} WHERE id = cm.module
                          )
                         WHERE cm.id = :cmid
                           AND gi.iteminstance = cm.instance";
                $gradeitemid = $DB->get_field_sql($sql, ['cmid' => $cmid]);

                if ($gradeitemid) {
                    $DB->set_field('grade_items', 'idnumber', $newid, ['id' => $gradeitemid]);
                }
            }

            $transaction->allow_commit();

        } catch (\Throwable $e) {
            $transaction->rollback($e);
        }

        return $count;
    }

    /**
     * Cleans a string for safe inclusion in an idnumber.
     * Replaces spaces/punctuation with dashes.
     */
    private static function clean_id_part(string $value): string {
        // Keep case, replace any sequence of non-alphanumeric characters with a dash.
        $clean = preg_replace('/[^A-Za-z0-9]+/u', '-', $value);
        $clean = trim($clean, '-');
        return $clean;
    }

}
