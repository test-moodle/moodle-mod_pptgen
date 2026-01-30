<?php
defined('MOODLE_INTERNAL') || die();

function mod_pptgen_pluginfile(
    $course,
    $cm,
    $context,
    $filearea,
    $args,
    $forcedownload,
    array $options = []
) {
    if ($context->contextlevel !== CONTEXT_MODULE) {
        return false;
    }

    require_login($course, true, $cm);
    require_capability('mod/pptgen:view', $context);

    if ($filearea !== 'generated') {
        return false;
    }

    $itemid = 0;
    $filename = array_pop($args);
    $filepath = '/';

    $fs = get_file_storage();
    $file = $fs->get_file(
        $context->id,
        'mod_pptgen',
        'generated',
        $itemid,
        $filepath,
        $filename
    );

    if (!$file) {
        return false;
    }

    send_stored_file($file, 0, 0, true);
}
