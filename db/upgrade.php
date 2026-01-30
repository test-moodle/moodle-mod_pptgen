<?php
defined('MOODLE_INTERNAL') || die();

function xmldb_pptgen_upgrade($oldversion) {
    global $DB;
    $dbman = $DB->get_manager();

    // Add upgrade steps here if needed in the future.

    return true;
}
