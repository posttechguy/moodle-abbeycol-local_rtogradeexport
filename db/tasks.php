<?php
/**
 * Export grades to CSV file
 *
 * Scheduled task definition
 *
 * @package    local_rtogradeexport
 * @author     Shane Elliott <shane@pukunui.com>, Pukunui
 * @copyright  2015 onwards, Pukunui
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$tasks = array(
    array(
        'classname' => 'local_rtogradeexport\task\exporttocsv',
        'blocking'  => 0,
        'minute'    => '5',
        'hour'      => '0',
        'day'       => '*',
        'dayofweek' => '*',
        'month'     => '*'
    )
);
