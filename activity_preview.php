<?php
require(__DIR__ . '/../../../config.php');
require_login();
require_capability('tool/idnumbergenerator:manage', context_system::instance());
require_sesskey();

$activityoverwrite = optional_param('activityoverwrite', 0, PARAM_BOOL);

$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('admin');
$PAGE->set_url(new moodle_url('/admin/tool/idnumbergenerator/activity_preview.php'));
$PAGE->set_title(get_string('activitypreviewheading', 'tool_idnumbergenerator'));
$PAGE->set_heading(get_string('activitypreviewheading', 'tool_idnumbergenerator'));
$PAGE->navbar->add(get_string('pluginname', 'tool_idnumbergenerator'),
    new moodle_url('/admin/tool/idnumbergenerator/index.php'));
$PAGE->navbar->add(get_string('activitypreviewheading', 'tool_idnumbergenerator'));

$results = \tool_idnumbergenerator\manager::generate_activity_idnumbers($activityoverwrite);

echo $OUTPUT->header();

if (empty($results)) {
    echo $OUTPUT->notification(get_string('noactivities', 'tool_idnumbergenerator'), 'info');
} else {
    echo html_writer::start_tag('form', [
        'method' => 'post',
        'action' => new moodle_url('/admin/tool/idnumbergenerator/activity_apply.php'),
        'id' => 'activity-id-form'
    ]);
    echo html_writer::empty_tag('input', [
        'type' => 'hidden',
        'name' => 'sesskey',
        'value' => sesskey()
    ]);

    // === Table header with "Check All" ===
    $table = new html_table();
    $table->head = [
        html_writer::checkbox('checkall', 1, true, '', ['id' => 'checkall']),
        get_string('course'),
        get_string('activitytype', 'tool_idnumbergenerator'),
        get_string('name'),
        get_string('proposedid', 'tool_idnumbergenerator')
    ];

    foreach ($results as $r) {
        $checkbox = html_writer::checkbox("records[{$r->cmid}][selected]", 1, true, '', [
            'class' => 'record-check'
        ]);

        $hiddenid = html_writer::empty_tag('input', [
            'type' => 'hidden',
            'name' => "records[{$r->cmid}][cmid]",
            'value' => $r->cmid
        ]);
        $hiddennewid = html_writer::empty_tag('input', [
            'type' => 'hidden',
            'name' => "records[{$r->cmid}][proposedidnumber]",
            'value' => $r->proposedidnumber
        ]);

        $table->data[] = [
            $checkbox . $hiddenid . $hiddennewid,
            s($r->coursefullname),
            s($r->modname),
            s($r->activityname),
            s($r->proposedidnumber)
        ];
    }

    echo html_writer::table($table);

    echo html_writer::empty_tag('input', [
        'type' => 'submit',
        'class' => 'btn btn-primary mt-3',
        'value' => get_string('applychanges', 'tool_idnumbergenerator')
    ]);
    echo html_writer::end_tag('form');

    // === JavaScript for check all/none ===
    $js = <<<JS
document.addEventListener('DOMContentLoaded', function() {
    const checkAll = document.getElementById('checkall');
    const boxes = document.querySelectorAll('.record-check');
    if (!checkAll) return;

    checkAll.addEventListener('change', function() {
        boxes.forEach(b => b.checked = checkAll.checked);
    });

    boxes.forEach(b => {
        b.addEventListener('change', function() {
            const allChecked = Array.from(boxes).every(x => x.checked);
            const noneChecked = Array.from(boxes).every(x => !x.checked);
            checkAll.indeterminate = !allChecked && !noneChecked;
            checkAll.checked = allChecked;
        });
    });
});
JS;
    $PAGE->requires->js_init_code($js);
}

echo $OUTPUT->footer();
