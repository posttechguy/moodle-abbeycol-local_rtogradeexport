<?php
/**
 * Export grades to CSV file
 *
 * Class definition for scheduled task execution
 *
 * @package    local_rtogradeexport
 * @author     Shane Elliott <shane@pukunui.com>, Pukunui
 * @copyright  2015 onwards, Pukunui
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_rtogradeexport\task;

require_once($CFG->dirroot.'/local/rtogradeexport/locallib.php');

/**
 * Extend core scheduled task class
 */
class exporttocsv extends \core\task\scheduled_task {

    /**
     * Return name of the task
     *
     * @return string
     */
    public function get_name() {
        return get_string('pluginname', 'local_rtogradeexport');
    }

    /**
     * Perform the task
     */
    public function execute() {
        local_rtogradeexport_write_csv_to_file('auto');
    }
}
