<?php
require(__DIR__ . '/../../../config.php');
require_login();
require_capability('tool/idnumbergenerator:manage', context_system::instance());

$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('admin');
$PAGE->set_url(new moodle_url('/admin/tool/idnumbergenerator/index.php'));
$PAGE->set_title(get_string('pluginname', 'tool_idnumbergenerator'));
$PAGE->set_heading(get_string('pluginname', 'tool_idnumbergenerator'));
$PAGE->navbar->add(get_string('pluginname', 'tool_idnumbergenerator'));

// ==================================================
// === Form handling must occur BEFORE header() ===
// ==================================================

$userform = new \tool_idnumbergenerator\form\generate_user_form();
$activityform = new \tool_idnumbergenerator\form\generate_activity_form();

// --- Handle user form submission ---
if ($userform->is_cancelled()) {
    redirect(new moodle_url('/admin/index.php'));
} else if ($userdata = $userform->get_data()) {
    $params = [
        'field' => $userdata->field,
        'regex' => urlencode($userdata->regex),
        'useroverwrite' => $userdata->useroverwrite,
        'sesskey' => sesskey(),
    ];
    redirect(new moodle_url('/admin/tool/idnumbergenerator/user_preview.php', $params));
}

// --- Handle activity form submission ---
if ($activityform->is_cancelled()) {
    redirect(new moodle_url('/admin/index.php'));
} else if ($actdata = $activityform->get_data()) {
    $params = [
        'activityoverwrite' => $actdata->activityoverwrite,
        'sesskey' => sesskey(),
    ];
    redirect(new moodle_url('/admin/tool/idnumbergenerator/activity_preview.php', $params));
}

// ==================================================
// === Safe to output after all redirects handled ===
// ==================================================

echo $OUTPUT->header();

// --- User form ---
$userform->display();

echo html_writer::tag('hr', '');

// --- Activity form ---
$activityform->display();

echo $OUTPUT->footer();
