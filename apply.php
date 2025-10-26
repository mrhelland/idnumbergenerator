<?php
require(__DIR__ . '/../../../config.php');
require_login();
require_sesskey();
require_capability('tool/idnumbergenerator:manage', context_system::instance());

$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('admin');
$PAGE->set_url(new moodle_url('/admin/tool/idnumbergenerator/apply.php'));
$PAGE->set_title(get_string('pluginname', 'tool_idnumbergenerator'));
$PAGE->set_heading(get_string('pluginname', 'tool_idnumbergenerator'));
$PAGE->navbar->add(get_string('pluginname', 'tool_idnumbergenerator'),
    new moodle_url('/admin/tool/idnumbergenerator/index.php'));
$PAGE->navbar->add(get_string('previewheading', 'tool_idnumbergenerator'),
    new moodle_url('/admin/tool/idnumbergenerator/preview.php'));
$PAGE->navbar->add(get_string('applychanges', 'tool_idnumbergenerator'));

// Retrieve submitted records.
$records = $_POST['records'] ?? [];

// Filter: only include checked items that have 'selected' = 1.
$selected = [];
if (!empty($records) && is_array($records)) {
    foreach ($records as $r) {
        if (!empty($r['selected']) && !empty($r['id']) && isset($r['newidnumber'])) {
            $selected[] = [
                'id' => (int)$r['id'],
                'newidnumber' => clean_param($r['newidnumber'], PARAM_NOTAGS)
            ];
        }
    }
}

// Apply the changes.
$count = 0;
$updatedusers = [];

if (!empty($selected)) {
    $count = \tool_idnumbergenerator\manager::apply_changes($selected);

    // Retrieve user info for display.
    $userids = array_column($selected, 'id');
    list($insql, $params) = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED);
    $updatedusers = $DB->get_records_select('user', "id $insql", $params, 'username ASC', 'id, username, email, idnumber');
}

echo $OUTPUT->header();

// === Display outcome ===
if ($count > 0) {
    echo $OUTPUT->notification(get_string('success', 'tool_idnumbergenerator'), 'success');
    echo html_writer::tag('p', get_string('recordsupdated', 'tool_idnumbergenerator', $count));

    // === Display table of updated records ===
    $table = new html_table();
    $table->head = [
        get_string('username'),
        get_string('email'),
        get_string('idnumber', 'tool_idnumbergenerator')
    ];

    foreach ($updatedusers as $u) {
        $table->data[] = [
            s($u->username),
            s($u->email),
            s($u->idnumber)
        ];
    }

    echo html_writer::table($table);

    echo html_writer::tag('p',
        get_string('recordsupdated', 'tool_idnumbergenerator', $count),
        ['class' => 'mt-3 fw-bold']
    );
} else {
    echo $OUTPUT->notification(get_string('nochanges', 'tool_idnumbergenerator'), 'info');
}

// === Return button ===
echo html_writer::div(
    $OUTPUT->single_button(
        new moodle_url('/admin/tool/idnumbergenerator/index.php'),
        get_string('return', 'tool_idnumbergenerator'),
        'get'
    ),
    'mt-3'
);

echo $OUTPUT->footer();
