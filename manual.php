<?php
/**
 * Manual export grades to CSV file
 *
 * Main landing page. Nothing to do here so simply redirect to front page.
 *
 * @package    local_rtogradeexport
 * @author     Bevan Holman <Bevan@pukunui.com>, Pukunui
 * @copyright  2015 onwards, Pukunui
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require('../../config.php');
require_once('./locallib.php');
require_once('./forms.php');
require_once($CFG->dirroot.'/lib/adminlib.php');

admin_externalpage_setup('manualexport');

$returnurl = '/local/rtogradeexport/manual.php';
$submitbutton = optional_param('submitbutton', '', PARAM_RAW);

$systemcontext = context_system::instance();
require_capability('local/rtogradeexport:config', $systemcontext);
$title = get_string('manualexportheader', 'local_rtogradeexport');
$PAGE->set_url($returnurl);
$PAGE->set_context($systemcontext);
$PAGE->set_title($title);
$PAGE->set_pagelayout('standard');
$PAGE->set_heading($title);

$config = get_config('local_rtogradeexport');

if ($config->ismanual == 0) {
    redirect(new moodle_url("/admin/settings.php?section=local_rtogradeexport_settings"),
        get_string('isnotmanual', 'local_rtogradeexport'));
    exit;
}

$form = new local_rtogradeexport_manual_export_form();

if ($form->is_cancelled()) { // Form cancelled?
    redirect(new moodle_url($returnurl));
    exit;
}

if (empty($submitbutton)) {
    echo $OUTPUT->header();
    $form->display();
    echo $OUTPUT->footer();
} else {
    if ($data = $form->get_data()) {

        $config = get_config('local_rtogradeexport');
        if ($config->ismanual) {
            local_rtogradeexport_write_csv_to_file('manual', $data);
            redirect(new moodle_url($returnurl), get_string('exportsuccess', 'local_rtogradeexport'));
        }
    } else {
        redirect(new moodle_url($returnurl), get_string('exporterror', 'local_rtogradeexport'));
    }
}
exit;





