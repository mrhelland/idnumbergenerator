<?php
namespace tool_idnumbergenerator\form;

defined('MOODLE_INTERNAL') || die();
require_once($CFG->libdir . '/formslib.php');

class generate_activity_form extends \moodleform {
    public function definition() {
        $mform = $this->_form;

        $mform->addElement('header', 'hdr', get_string('activitysection', 'tool_idnumbergenerator'));
        $mform->addElement('static', 'activitypatterninfo', '', get_string('activitydesc', 'tool_idnumbergenerator'));

        $mform->addElement('advcheckbox', 'activityoverwrite', get_string('overwrite', 'tool_idnumbergenerator'));
        $mform->setDefault('activityoverwrite', 0);

        $this->add_action_buttons(false, get_string('generateactivityids', 'tool_idnumbergenerator'));
    }
}
