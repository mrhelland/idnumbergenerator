<?php
defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $ADMIN->add('accounts', new admin_externalpage(
        'tool_idnumbergenerator',
        get_string('pluginname', 'tool_idnumbergenerator'),
        new moodle_url('/admin/tool/idnumbergenerator/index.php'),
        'tool/idnumbergenerator:manage'
    ));
}

