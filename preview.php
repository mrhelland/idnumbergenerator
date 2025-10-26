<?php
require(__DIR__ . '/../../../config.php');
require_login();
require_sesskey();
require_capability('tool/idnumbergenerator:manage', context_system::instance());

$field = required_param('field', PARAM_ALPHANUMEXT);
$regex = urldecode(required_param('regex', PARAM_RAW));
$overwrite = optional_param('overwrite', 0, PARAM_BOOL);

$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('admin');
$PAGE->set_url(new moodle_url('/admin/tool/idnumbergenerator/preview.php'));
$PAGE->set_title(get_string('previewheading', 'tool_idnumbergenerator'));
$PAGE->set_heading(get_string('previewheading', 'tool_idnumbergenerator'));
$PAGE->navbar->add(get_string('pluginname', 'tool_idnumbergenerator'),
    new moodle_url('/admin/tool/idnumbergenerator/index.php'));
$PAGE->navbar->add(get_string('previewheading', 'tool_idnumbergenerator'));

$results = \tool_idnumbergenerator\manager::generate_idnumbers($field, $regex, $overwrite);

// Handle possible duplicates.
$duplicates = [];
foreach ($results as $r) {
    $duplicates[$r->newidnumber] = ($duplicates[$r->newidnumber] ?? 0) + 1;
}

echo $OUTPUT->header();

if (!$results) {
    echo $OUTPUT->notification(get_string('nochanges', 'tool_idnumbergenerator'), 'info');
} else {
    echo html_writer::start_tag('form', [
        'method' => 'post',
        'action' => new moodle_url('/admin/tool/idnumbergenerator/apply.php'),
        'id' => 'idnumber-form'
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
        get_string('username'),
        get_string('email'),
        get_string('idnumber')
    ];

    // === Table rows ===
    foreach ($results as $r) {
        $duplicate = ($duplicates[$r->newidnumber] > 1);
        $style = $duplicate ? 'background:#ffe5e5;' : '';

        $checkbox = html_writer::checkbox("records[{$r->id}][selected]", 1, true, '', [
            'class' => 'record-check'
        ]);

        $hiddenid = html_writer::empty_tag('input', [
            'type' => 'hidden',
            'name' => "records[{$r->id}][id]",
            'value' => $r->id
        ]);
        $hiddennewid = html_writer::empty_tag('input', [
            'type' => 'hidden',
            'name' => "records[{$r->id}][newidnumber]",
            'value' => $r->newidnumber
        ]);

        $table->data[] = [
            $checkbox . $hiddenid . $hiddennewid,
            s($r->username),
            s($r->email),
            html_writer::span(s($r->newidnumber), '', ['style' => $style])
        ];
    }

    echo html_writer::table($table);

    if (max($duplicates) > 1) {
        echo $OUTPUT->notification(get_string('duplicatewarning', 'tool_idnumbergenerator'), 'warning');
    }

    echo html_writer::tag('div',
        html_writer::empty_tag('input', [
            'type' => 'submit',
            'class' => 'btn btn-primary mt-3',
            'value' => get_string('applychanges', 'tool_idnumbergenerator')
        ]),
        ['class' => 'mt-2']
    );

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
