<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * General plugin functions.
 *
 * @package    enrol_lticoursetemplate
 * @copyright  2016 Mark Nelson <markn@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use enrol_lticoursetemplate\local\ltiadvantage\admin\admin_setting_registeredplatforms;

defined('MOODLE_INTERNAL') || die;
// The 'Publish as LTI Course Template tool' node is a category.
$ADMIN->add('enrolments', new admin_category('enrollticoursetemplatefolder', new lang_string('pluginname', 'enrol_lticoursetemplate'),
    $this->is_enabled() === false));

$settings = new admin_settingpage($section, "User default values", 'moodle/site:config', $this->is_enabled() === false);
// Add all the user default values settings to the first page.
if ($ADMIN->fulltree) {
    $settings->add(new admin_setting_heading('enrol_lticoursetemplate_settings', '', get_string('pluginname_desc', 'enrol_lticoursetemplate')));

    if (!is_enabled_auth('lti')) {
        $notify = new \core\output\notification(get_string('authltimustbeenabled', 'enrol_lticoursetemplate'),
            \core\output\notification::NOTIFY_WARNING);
        $settings->add(new admin_setting_heading('enrol_lticoursetemplate_enable_auth_lti', '', $OUTPUT->render($notify)));
    }

    if (empty($CFG->allowframembedding)) {
        $notify = new \core\output\notification(get_string('allowframeembedding', 'enrol_lticoursetemplate'),
            \core\output\notification::NOTIFY_WARNING);
        $settings->add(new admin_setting_heading('enrol_lticoursetemplate_enable_embedding', '', $OUTPUT->render($notify)));
    }

    $settings->add(new admin_setting_heading('enrol_lticoursetemplate_user_default_values',
        get_string('userdefaultvalues', 'enrol_lticoursetemplate'), ''));

    $choices = array(0 => get_string('emaildisplayno'),
                     1 => get_string('emaildisplayyes'),
                     2 => get_string('emaildisplaycourse'));
    $maildisplay = isset($CFG->defaultpreference_maildisplay) ? $CFG->defaultpreference_maildisplay : 2;
    $settings->add(new admin_setting_configselect('enrol_lticoursetemplate/emaildisplay', get_string('emaildisplay'),
        get_string('emaildisplay_help'), $maildisplay, $choices));

    $city = '';
    if (!empty($CFG->defaultcity)) {
        $city = $CFG->defaultcity;
    }
    $settings->add(new admin_setting_configtext('enrol_lticoursetemplate/city', get_string('city'), '', $city));

    $country = '';
    if (!empty($CFG->country)) {
        $country = $CFG->country;
    }
    $countries = array('' => get_string('selectacountry') . '...') + get_string_manager()->get_list_of_countries();
    $settings->add(new admin_setting_configselect('enrol_lticoursetemplate/country', get_string('selectacountry'), '', $country,
        $countries));

    $settings->add(new admin_setting_configselect('enrol_lticoursetemplate/timezone', get_string('timezone'), '', 99,
        core_date::get_list_of_timezones(null, true)));

    $settings->add(new admin_setting_configselect('enrol_lticoursetemplate/lang', get_string('preferredlanguage'), '', $CFG->lang,
        get_string_manager()->get_list_of_translations()));

    $settings->add(new admin_setting_configtext('enrol_lticoursetemplate/institution', get_string('institution'), '', ''));

    $settings->add(new admin_setting_heading('enrol_lticoursetemplate_admin_default_values',
        get_string('admindefaultvalues', 'enrol_lticoursetemplate'), ''));

    $manager = $USER->id;
    if (!empty($CFG->manager)) {
        $manager = $CFG->manager;
    }
    $settings->add(new admin_setting_configtext('enrol_lticoursetemplate/manager', get_string('manager',
            'enrol_lticoursetemplate'), get_string('manager_help', 'enrol_lticoursetemplate'), $manager, PARAM_INT));
}
$ADMIN->add('enrollticoursetemplatefolder', $settings);

// Now, create a tool registrations settings page.
$settings = new admin_settingpage('enrolsettingsltict_registrations', "Tool registration", 'moodle/site:config',
    $this->is_enabled() === false);

$settings->add(new admin_setting_heading('enrol_ct_tool_registrations_heading',
    get_string('registeredplatforms', 'enrol_lticoursetemplate'), ''));
$settings->add(new admin_setting_registeredplatforms());

$ADMIN->add('enrollticoursetemplatefolder', $settings);

// This adds a settings page to the 'publish as LTI Course Template tool' folder, hidden.
// On this page, we'll  override the active node to force a match on enrolsettingsltict_registrations settings page.
$ADMIN->add('enrollticoursetemplatefolder', new admin_externalpage('enrolsettingsltict_registrations_edit',
    get_string('registerplatformadd', 'enrol_lticoursetemplate'), "$CFG->wwwroot/$CFG->admin/enrol/lticoursetemplate/register_platform.php",
    'moodle/site:config', true));

// And deployments add/edit.
$ADMIN->add('enrollticoursetemplatefolder', new admin_externalpage('enrolsettingsltict_deployment_manage',
    get_string('deployments', 'enrol_lticoursetemplate'), "$CFG->wwwroot/$CFG->admin/enrol/lticoursetemplate/manage_deployment.php",
    'moodle/site:config', true));

// Tell core we're finished.
$settings = null;
