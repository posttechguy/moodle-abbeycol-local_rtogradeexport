<?php
/**
 * Export grades to CSV file
 *
 * Administration settings
 *
 * @package    local_rtogradeexport
 * @author     Shane Elliott <shane@pukunui.com>, Pukunui
 * @copyright  2015 onwards, Pukunui
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if (has_capability('local/rtogradeexport:config', context_system::instance())) {

    $settings = new admin_settingpage('local_rtogradeexport_settings',
                                      new lang_string('pluginname', 'local_rtogradeexport'),
                                      'local/rtogradeexport:config');

    $settings->add(new admin_setting_configdirectory(
                'local_rtogradeexport/csvlocation',
                new lang_string('csvlocation', 'local_rtogradeexport'),
                new lang_string('csvlocationdesc', 'local_rtogradeexport'),
                $CFG->dataroot.'/rtogradeexport',
                PARAM_RAW,
                80
                ));

    $settings->add(new admin_setting_configtext(
                'local_rtogradeexport/csvprefix',
                new lang_string('csvprefix', 'local_rtogradeexport'),
                new lang_string('csvprefixdesc', 'local_rtogradeexport'),
                'rtogradeexport_',
                PARAM_RAW,
                80
                ));

    $settings->add(new admin_setting_configcheckbox(
                'local_rtogradeexport/ismanual',
                new lang_string('ismanual', 'local_rtogradeexport'),
                new lang_string('ismanualdesc', 'local_rtogradeexport'),
                'Automatic grade export (not checked)'
                ));

    $ADMIN->add('root', new admin_category('local_rtogradeexport', get_string('pluginname', 'local_rtogradeexport')));

    $ADMIN->add('local_rtogradeexport', new admin_externalpage('manualexport', get_string('manualexport', 'local_rtogradeexport'),
                new moodle_url('/local/rtogradeexport/manual.php'),
                'local/rtogradeexport:config'));

    $ADMIN->add('localplugins', $settings);
}
