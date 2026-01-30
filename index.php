<?php
require_once('../../config.php');
require_once($CFG->dirroot.'/mod/pptgen/lib.php');

$id = required_param('id', PARAM_INT); // Course Module ID

$course = $DB->get_record('course', array('id' => $id), '*', MUST_EXIST);

require_course_login($course);

$PAGE->set_url('/mod/pptgen/index.php', array('id' => $id));
$PAGE->set_title(format_string($course->fullname));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context(context_course::instance($id));

echo $OUTPUT->header();

echo $OUTPUT->heading(get_string('modulenameplural', 'mod_pptgen'));

// Get all instances of this module in the course
$pptgens = get_all_instances_in_course('pptgen', $course);

if (empty($pptgens)) {
    notice(get_string('noinstances', 'mod_pptgen'), new moodle_url('/course/view.php', array('id' => $course->id)));
}

// Print a table of instances
$table = new html_table();
$table->head = array(get_string('name'));

foreach ($pptgens as $pptgen) {
    $url = new moodle_url('/mod/pptgen/view.php', array('id' => $pptgen->coursemodule));
    $table->data[] = array(html_writer::link($url, format_string($pptgen->name)));
}

echo html_writer::table($table);

echo $OUTPUT->footer();
