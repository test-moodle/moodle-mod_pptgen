<?php
namespace mod_pptgen\form;

defined('MOODLE_INTERNAL') || die();

require_once($GLOBALS['CFG']->libdir . '/formslib.php');

class prompt_form extends \moodleform {

    public function definition() {
        $mform = $this->_form;

        // Prompt input
        $mform->addElement('textarea', 'prompt', 'Prompt', [
            'rows' => 6,
            'cols' => 80
        ]);
        $mform->setType('prompt', PARAM_TEXT);
        $mform->addRule('prompt', null, 'required', null, 'client');

        // Slide count
        $mform->addElement('text', 'slides', 'Number of slides');
        $mform->setType('slides', PARAM_INT);
        $mform->setDefault('slides', 3);

        // Submit
        $this->add_action_buttons(false, 'Generate PPT');
    }
}
