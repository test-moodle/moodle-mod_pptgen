<?php
defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {
    // Gemini API Key setting
    $settings->add(new admin_setting_configtext(
        'local_pptgen/geminiapikey',
        get_string('geminiapikey', 'mod_pptgen'),
        get_string('geminiapikey_desc', 'mod_pptgen'),
        '',
        PARAM_TEXT
    ));
}
