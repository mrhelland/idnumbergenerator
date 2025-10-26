<?php
require(__DIR__ . '/../../../config.php');
require_login();
require_capability('tool/idnumbergenerator:manage', context_system::instance());

$PAGE->set_pagelayout('admin');
$PAGE->set_url(new moodle_url('/admin/tool/idnumbergenerator/index.php'));
$PAGE->set_context(context_system::instance());
$PAGE->set_title(get_string('pluginname', 'tool_idnumbergenerator'));
$PAGE->set_heading(get_string('heading', 'tool_idnumbergenerator'));
$PAGE->navbar->add(get_string('pluginname', 'tool_idnumbergenerator'),
    new moodle_url('/admin/tool/idnumbergenerator/index.php'));


$form = new \tool_idnumbergenerator\form\generate_form();

if ($form->is_cancelled()) {
    redirect(new moodle_url('/admin/index.php'));
} else if ($data = $form->get_data()) {
    $params = [
        'field' => $data->field,
        'regex' => urlencode($data->regex),
        'overwrite' => $data->overwrite,
        'sesskey' => sesskey(),
    ];
    redirect(new moodle_url('/admin/tool/idnumbergenerator/preview.php', $params));
}

echo $OUTPUT->header();
$form->display();
echo $OUTPUT->footer();
