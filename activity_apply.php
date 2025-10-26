<?php
require(__DIR__ . '/../../../config.php');
require_login();
require_capability('tool/idnumbergenerator:manage', context_system::instance());
require_sesskey();

$records = $_POST['records'] ?? [];

// Filter: only include checked items that have 'selected' = 1.
$selected = [];
if (!empty($records) && is_array($records)) {
    foreach ($records as $r) {
        if (!empty($r['selected']) && !empty($r['cmid']) && isset($r['proposedidnumber'])) {
            $selected[] = [
                'cmid' => (int)$r['cmid'],
                'proposedidnumber' => clean_param($r['proposedidnumber'], PARAM_NOTAGS)
            ];
        }
    }
}

$count = 0;
if (!empty($selected)) {
    $count = \tool_idnumbergenerator\manager::apply_activity_changes($selected);
}

$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('admin');
$PAGE->set_url(new moodle_url('/admin/tool/idnumbergenerator/activity_apply.php'));
$PAGE->set_title(get_string('activityapplyheading', 'tool_idnumbergenerator'));
$PAGE->set_heading(get_string('activityapplyheading', 'tool_idnumbergenerator'));
$PAGE->navbar->add(get_string('pluginname', 'tool_idnumbergenerator'),
    new moodle_url('/admin/tool/idnumbergenerator/index.php'));
$PAGE->navbar->add(get_string('activityapplyheading', 'tool_idnumbergenerator'));

echo $OUTPUT->header();

if ($count > 0) {
    echo $OUTPUT->notification(get_string('success', 'tool_idnumbergenerator'), 'success');
    echo html_writer::tag('p', get_string('recordsupdated', 'tool_idnumbergenerator', $count));
} else {
    echo $OUTPUT->notification(get_string('nochanges', 'tool_idnumbergenerator'), 'info');
}

echo html_writer::div(
    $OUTPUT->single_button(
        new moodle_url('/admin/tool/idnumbergenerator/index.php'),
        get_string('return', 'tool_idnumbergenerator'),
        'get'
    ),
    'mt-3'
);

echo $OUTPUT->footer();
