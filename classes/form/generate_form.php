<?php
namespace tool_idnumbergenerator\form;

defined('MOODLE_INTERNAL') || die();
require_once($CFG->libdir . '/formslib.php');

class generate_form extends \moodleform {
    public function definition() {
        $mform = $this->_form;

        $mform->addElement('header', 'hdr', get_string('heading', 'tool_idnumbergenerator'));

        $mform->addElement('select', 'field', get_string('selectfield', 'tool_idnumbergenerator'), [
            'username' => get_string('username'),
            'email' => get_string('email'),
        ]);
        $mform->setDefault('field', 'email');

        $mform->addElement('text', 'regex', get_string('regexquery', 'tool_idnumbergenerator'));
        $mform->setType('regex', PARAM_RAW_TRIMMED);
        $mform->setDefault('regex', '^[^@]*');
        $mform->addRule('regex', null, 'required', null, 'client');

        $mform->addElement('advcheckbox', 'overwrite', get_string('overwrite', 'tool_idnumbergenerator'));
        $mform->setDefault('overwrite', 0);

        $this->add_action_buttons(false, get_string('generate', 'tool_idnumbergenerator'));
    }

    /**
     * Form data validation.
     *
     * @param array $data The form data.
     * @param array $files Uploaded files (unused).
     * @return array of validation errors.
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        // Validate regex pattern.
        $pattern = trim($data['regex'] ?? '');
        if ($pattern === '') {
            $errors['regex'] = get_string('required');
        } else if (@preg_match($pattern, '') === false) {
            // Try auto-wrap and recheck with slashes.
            $testpattern = '/' . str_replace('/', '\/', $pattern) . '/';
            if (@preg_match($testpattern, '') === false) {
                $errors['regex'] = get_string('invalidregex', 'tool_idnumbergenerator');
            }
        }

        return $errors;
    }
}
