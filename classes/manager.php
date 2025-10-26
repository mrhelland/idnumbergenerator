<?php
namespace tool_idnumbergenerator;

defined('MOODLE_INTERNAL') || die();

class manager {

    public static function generate_idnumbers(string $field, string $regex, bool $overwrite): array {
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

    public static function apply_changes(array $data): int {
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
}
