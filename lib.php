<?php
defined('MOODLE_INTERNAL') || die();

function pptgen_supports($feature) {
    switch ($feature) {
        case FEATURE_MOD_INTRO:
            return true;
        case FEATURE_SHOW_DESCRIPTION:
            return true;
        default:
            return null;
    }
}

function pptgen_add_instance($data, $mform = null) {
    global $DB;
    $data->timecreated = time();
    $data->timemodified = time();
    return $DB->insert_record('pptgen', $data);
}

function pptgen_update_instance($data, $mform = null) {
    global $DB;
    $data->timemodified = time();
    $data->id = $data->instance;
    return $DB->update_record('pptgen', $data);
}

function pptgen_delete_instance($id) {
    global $DB;
    if (!$DB->record_exists('pptgen', ['id' => $id])) {
        return false;
    }
    return $DB->delete_records('pptgen', ['id' => $id]);
}
function mod_pptgen_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options = []) {
    if ($context->contextlevel !== CONTEXT_MODULE) {
        return false;
    }

    require_login($course, true, $cm);
    require_capability('mod/pptgen:view', $context);

    if ($filearea !== 'generated') {
        return false;
    }

    $itemid = array_shift($args); // should be 0
    $filename = array_pop($args);

    $filepath = '/';
    if (!empty($args)) {
        $filepath .= implode('/', $args) . '/';
    }

    $fs = get_file_storage();
    $file = $fs->get_file($context->id, 'mod_pptgen', 'generated', $itemid, $filepath, $filename);

    if (!$file || $file->is_directory()) {
        return false;
    }

    // This sends the file securely.
    send_stored_file($file, 0, 0, true, $options);
}
