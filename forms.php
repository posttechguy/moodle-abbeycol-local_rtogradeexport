<?php
/**
 * Manual Export for Wentworth Institute of Higher Education project
 *
 * Form definitions
 *
 * @package    local_rtogradeexport
 * @author     Bevan Holman <bevan@pukunui.com>, Pukunui
 * @copyright  2015 onwards, Pukunui
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir.'/formslib.php');

class local_rtogradeexport_manual_export_form extends moodleform {

    /**
     * Define the form
     */
    public function definition() {
        global $DB;

        $mform =& $this->_form;
        $id = $this->_customdata;

        $strrequired = get_string('required');

        $sql = "
            SELECT id, concat(fullname, ' - ', shortname) as name from {course} ORDER BY fullname
        ";

        $coursenames = array("selectacourse" => get_string('selectacourse', 'local_rtogradeexport'), "0" => "All");

        if ($courses = $DB->get_records_sql_menu($sql, null)) {
            foreach ($courses as $id => $name) {
                $coursenames["$id"] = $name;
            }
        }
        $mform->addElement('select', 'course', get_string('coursetype', 'local_rtogradeexport'),
            $coursenames);
        $mform->addRule('course', get_string('errorcourse', 'local_rtogradeexport'), 'required', null, 'client', true, true);


        $sql = "
            SELECT name from {groups} GROUP BY name
        ";

        $groupnames = array("selectagroup" => get_string('selectagroup', 'local_rtogradeexport'), "All" => "All");

        if ($groups = $DB->get_fieldset_sql($sql, null)) {

            foreach ($groups as $key => $value) {
                $groupnames["$value"] = $value;
            }
        }
        $mform->addElement('select', 'group', get_string('grouptype', 'local_rtogradeexport'), $groupnames);
        $mform->addRule('group', get_string('errorgroup', 'local_rtogradeexport'), 'required', null, 'client', true, true);

        $strsubmit = get_string('exportnow', 'local_rtogradeexport');
        $this->add_action_buttons(true, $strsubmit);
    }

    /**
     * Validate the form submission
     *
     * @param array $data  submitted form data
     * @param array $files submitted form files
     * @return array
     */
    public function validation($data, $files) {
        global $DB;

        $error = array();

        if ($data['course'] == 'selectacourse') {
            $error['course'] = get_string('errorcourse', 'local_rtogradeexport');
        }
        if ($data['group'] == 'selectagroup') {
            $error['group'] = get_string('errorgroup', 'local_rtogradeexport');
        }

        return (count($error) == 0) ? true : $error;
    }
}