<?php
require_once('../../config.php');
require_once(__DIR__ . '/ppt_generator.php');

$id = required_param('id', PARAM_INT);

$cm = get_coursemodule_from_id('pptgen', $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);

require_login($course, true, $cm);

$context = context_module::instance($cm->id);
require_capability('mod/pptgen:view', $context);

$PAGE->set_url('/mod/pptgen/view.php', ['id' => $id]);
$PAGE->set_title('Prompt to PPT');
$PAGE->set_heading('Prompt to PPT');

echo $OUTPUT->header();

$downloadurl = null;

if (optional_param('submit', false, PARAM_BOOL)) {
    require_sesskey();

    $prompt = required_param('prompt', PARAM_RAW_TRIMMED);
    $slides = required_param('slides', PARAM_INT);

    // Basic guardrails.
    if ($slides < 1) {
        $slides = 1;
    }
    if ($slides > 20) {
        $slides = 20;
    }

    try {
        // 1) Generate PPTX as a temp file path (your existing function).
        $pptpath = pptgen_generate_ppt($prompt, $slides, $context);


        if (empty($pptpath) || !file_exists($pptpath)) {
            throw new moodle_exception('Generated PPT file was not created on disk.');
        }

        // 2) Store into Moodle File API so pluginfile.php can serve it.
        $fs = get_file_storage();

        $filearea = 'generated';
        $itemid   = 0;
        $filepath = '/';

        // Make filename unique per user + time.
        $filename = 'pptgen_' . $cm->id . '_' . $USER->id . '_' . time() . '.pptx';

        // OPTIONAL: delete previous generated files for this user (keeps storage clean)
        // If you want to keep history, comment this block.
        $existingfiles = $fs->get_area_files($context->id, 'mod_pptgen', $filearea, $itemid, 'timemodified DESC', false);
        foreach ($existingfiles as $f) {
            // Delete only user's previous generated files by prefix check (safe simple cleanup)
            if (str_starts_with($f->get_filename(), 'pptgen_' . $cm->id . '_' . $USER->id . '_')) {
                $f->delete();
            }
        }

        $filerecord = [
            'contextid' => $context->id,
            'component' => 'mod_pptgen',
            'filearea'  => $filearea,
            'itemid'    => $itemid,
            'filepath'  => $filepath,
            'filename'  => $filename,
        ];

        // Save file contents into Moodle file pool.
        $storedfile = $fs->create_file_from_pathname($filerecord, $pptpath);

        // 3) Build correct pluginfile URL.
        $downloadurl = moodle_url::make_pluginfile_url(
            $context->id,
            'mod_pptgen',
            $filearea,
            $itemid,
            $filepath,
            $storedfile->get_filename(),
            true
        );

        echo $OUTPUT->notification('PPT generated successfully!', 'notifysuccess');

        echo html_writer::link(
            $downloadurl,
            'Download PPT',
            ['class' => 'btn btn-success', 'style' => 'margin: 10px 0; display:inline-block;']
        );

    } catch (Exception $e) {
        echo $OUTPUT->notification('Exception: ' . $e->getMessage(), 'notifyproblem');
    }
}

// FORM
echo html_writer::start_tag('form', [
    'method' => 'post',
    'action' => (new moodle_url('/mod/pptgen/view.php', ['id' => $id]))->out(false),
]);

echo html_writer::empty_tag('input', [
    'type'  => 'hidden',
    'name'  => 'sesskey',
    'value' => sesskey(),
]);

echo html_writer::tag('label', 'Prompt');
echo html_writer::tag('textarea', '', [
    'name' => 'prompt',
    'required' => true,
    'style' => 'width:100%;height:120px;',
]);

echo html_writer::empty_tag('br');

echo html_writer::tag('label', 'Number of slides');
echo html_writer::empty_tag('input', [
    'type' => 'number',
    'name' => 'slides',
    'value' => 3,
    'min' => 1,
    'max' => 20,
]);

echo html_writer::empty_tag('br');
echo html_writer::empty_tag('br');

echo html_writer::empty_tag('input', [
    'type' => 'submit',
    'name' => 'submit',
    'value' => 'Generate PPT',
    'class' => 'btn btn-primary'
]);

echo html_writer::end_tag('form');

echo $OUTPUT->footer();
