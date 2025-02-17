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
 * LTI enrolment plugin main library file.
 *
 * @package enrol_lticoursetemplate
 * @copyright 2016 Mark Nelson <markn@moodle.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use enrol_lticoursetemplate\data_connector;
use enrol_lticoursetemplate\local\ltiadvantage\repository\resource_link_repository;
use IMSGlobal\LTI\ToolProvider\ToolConsumer;

defined('MOODLE_INTERNAL') || die();

/**
 * LTI enrolment plugin class.
 *
 * @package enrol_lticoursetemplate
 * @copyright 2016 Mark Nelson <markn@moodle.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class enrol_lticoursetemplate_plugin extends enrol_plugin {

    /**
     * Return true if we can add a new instance to this course.
     *
     * @param int $courseid
     * @return boolean
     */
    public function can_add_instance($courseid) {
        $context = context_course::instance($courseid, MUST_EXIST);
        return has_capability('moodle/course:enrolconfig', $context) && has_capability('enrol/lticoursetemplate:config', $context);
    }

    /**
     * Is it possible to delete enrol instance via standard UI?
     *
     * @param object $instance
     * @return bool
     */
    public function can_delete_instance($instance) {
        $context = context_course::instance($instance->courseid);
        return has_capability('enrol/lticoursetemplate:config', $context);
    }

    /**
     * Is it possible to hide/show enrol instance via standard UI?
     *
     * @param stdClass $instance
     * @return bool
     */
    public function can_hide_show_instance($instance) {
        $context = context_course::instance($instance->courseid);
        return has_capability('enrol/lticoursetemplate:config', $context);
    }

    /**
     * Returns true if it's possible to unenrol users.
     *
     * @param stdClass $instance course enrol instance
     * @return bool
     */
    public function allow_unenrol(stdClass $instance) {
        return true;
    }

    /**
     * We are a good plugin and don't invent our own UI/validation code path.
     *
     * @return boolean
     */
    public function use_standard_editing_ui() {
        return true;
    }

    /**
     * Add new instance of enrol plugin.
     *
     * @param object $course
     * @param array $fields instance fields
     * @return int id of new instance, null if can not be created
     */
    public function add_instance($course, array $fields = null) {
        global $DB;

        $instanceid = parent::add_instance($course, $fields);

        // Add additional data to our table.
        $data = new stdClass();
        $data->enrolid = $instanceid;
        $data->timecreated = time();
        $data->timemodified = $data->timecreated;
        foreach ($fields as $field => $value) {
            $data->$field = $value;
        }

        // LTI Advantage: make a unique identifier for the published resource.
        if (empty($data->ltiversion) || $data->ltiversion == 'LTI-1p3') {
            $data->uuid = \core\uuid::generate();
        }

        $DB->insert_record('enrol_ct_tools', $data);

        return $instanceid;
    }

    /**
     * Update instance of enrol plugin.
     *
     * @param stdClass $instance
     * @param stdClass $data modified instance fields
     * @return boolean
     */
    public function update_instance($instance, $data) {
        global $DB;

        parent::update_instance($instance, $data);

        // Remove the fields we don't want to override.
        unset($data->id);
        unset($data->timecreated);
        unset($data->timemodified);

        // Convert to an array we can loop over.
        $fields = (array) $data;

        // Update the data in our table.
        $tool = new stdClass();
        $tool->id = $data->toolid;
        $tool->timemodified = time();
        foreach ($fields as $field => $value) {
            $tool->$field = $value;
        }

        // LTI Advantage: make a unique identifier for the published resource.
        if ($tool->ltiversion == 'LTI-1p3' && empty($tool->uuid)) {
            $tool->uuid = \core\uuid::generate();
        }

        return $DB->update_record('enrol_ct_tools', $tool);
    }

    /**
     * Delete plugin specific information.
     *
     * @param stdClass $instance
     * @return void
     */
    public function delete_instance($instance) {
        global $DB;

        // Get the tool associated with this instance.
        $tool = $DB->get_record('enrol_ct_tools', array('enrolid' => $instance->id), 'id', MUST_EXIST);

        // LTI Advantage: delete any resource_link and user_resource_link mappings.
        $resourcelinkrepo = new resource_link_repository();
        $resourcelinkrepo->delete_by_resource($tool->id);

        // Delete any users associated with this tool.
        $DB->delete_records('enrol_ct_users', array('toolid' => $tool->id));

        // Get tool and consumer mappings.
        $rsmapping = $DB->get_recordset('enrol_ct_tool_cons_map', array('toolid' => $tool->id));

        // Delete consumers that are linked to this tool and their related data.
        $dataconnector = new data_connector();
        foreach ($rsmapping as $mapping) {
            $consumer = new ToolConsumer(null, $dataconnector);
            $consumer->setRecordId($mapping->consumerid);
            $dataconnector->deleteToolConsumer($consumer);
        }
        $rsmapping->close();

        // Delete mapping records.
        $DB->delete_records('enrol_ct_tool_cons_map', array('toolid' => $tool->id));

        // Delete the lti tool record.
        $DB->delete_records('enrol_ct_tools', array('id' => $tool->id));

        // Delete lti course record
        $DB->delete_records('enrol_ct_courses', array('courseid' => $instance->courseid));

        // Time for the parent to do it's thang, yeow.
        parent::delete_instance($instance);
    }

    /**
     * Handles un-enrolling a user.
     *
     * @param stdClass $instance
     * @param int $userid
     * @return void
     */
    public function unenrol_user(stdClass $instance, $userid) {
        global $DB;

        // Get the tool associated with this instance. Note - it may not exist if we have deleted
        // the tool. This is fine because we have already cleaned the 'enrol_ct_users' table.
        if ($tool = $DB->get_record('enrol_ct_tools', array('enrolid' => $instance->id), 'id')) {
            // Need to remove the user from the users table.
            $DB->delete_records('enrol_ct_users', array('userid' => $userid, 'toolid' => $tool->id));
        }

        parent::unenrol_user($instance, $userid);
    }

    /**
     * Add elements to the edit instance form.
     *
     * @param stdClass $instance
     * @param MoodleQuickForm $mform
     * @param context $context
     * @return bool
     */
    public function edit_instance_form($instance, MoodleQuickForm $mform, $context) {
        global $DB;

        $versionoptions = [
            'LTI-1p3' => get_string('lti13', 'enrol_lticoursetemplate'),
            'LTI-1p0/LTI-2p0' => get_string('ltilegacy', 'enrol_lticoursetemplate')
        ];
        $mform->addElement('select', 'ltiversion', get_string('ltiversion', 'enrol_lticoursetemplate'), $versionoptions);
        $mform->addHelpButton('ltiversion', 'ltiversion', 'enrol_lticoursetemplate');
        $legacy = optional_param('legacy', 0, PARAM_INT);
        if (empty($instance->id)) {
            $mform->setDefault('ltiversion', $legacy ? 'LTI-1p0/LTI-2p0' : 'LTI-1p3');
        }

        $nameattribs = array('size' => '20', 'maxlength' => '255');
        $mform->addElement('text', 'name', get_string('custominstancename', 'enrol'), $nameattribs);
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'server');

        $tools = array();
        $tools[$context->id] = get_string('course');
        $modinfo = get_fast_modinfo($instance->courseid);
        $mods = $modinfo->get_cms();
        foreach ($mods as $mod) {
            $tools[$mod->context->id] = format_string($mod->name);
        }

        $mform->addElement('select', 'contextid', get_string('tooltobeprovided', 'enrol_lticoursetemplate'), $tools);
        $mform->setDefault('contextid', $context->id);

        $mform->addElement('duration', 'enrolperiod', get_string('enrolperiod', 'enrol_lticoursetemplate'),
            array('optional' => true, 'defaultunit' => DAYSECS));
        $mform->setDefault('enrolperiod', 0);
        $mform->addHelpButton('enrolperiod', 'enrolperiod', 'enrol_lticoursetemplate');

        $mform->addElement('date_time_selector', 'enrolstartdate', get_string('enrolstartdate', 'enrol_lticoursetemplate'),
            array('optional' => true));
        $mform->setDefault('enrolstartdate', 0);
        $mform->addHelpButton('enrolstartdate', 'enrolstartdate', 'enrol_lticoursetemplate');

        $mform->addElement('date_time_selector', 'enrolenddate', get_string('enrolenddate', 'enrol_lticoursetemplate'),
            array('optional' => true));
        $mform->setDefault('enrolenddate', 0);
        $mform->addHelpButton('enrolenddate', 'enrolenddate', 'enrol_lticoursetemplate');

        $mform->addElement('text', 'maxenrolled', get_string('maxenrolled', 'enrol_lticoursetemplate'));
        $mform->setDefault('maxenrolled', 0);
        $mform->addHelpButton('maxenrolled', 'maxenrolled', 'enrol_lticoursetemplate');
        $mform->setType('maxenrolled', PARAM_INT);

        $assignableroles = get_assignable_roles($context);

        $mform->addElement('select', 'roleinstructor', get_string('roleinstructor', 'enrol_lticoursetemplate'), $assignableroles);
        $mform->setDefault('roleinstructor', '3');
        $mform->addHelpButton('roleinstructor', 'roleinstructor', 'enrol_lticoursetemplate');

        $mform->addElement('select', 'rolelearner', get_string('rolelearner', 'enrol_lticoursetemplate'), $assignableroles);
        $mform->setDefault('rolelearner', '5');
        $mform->addHelpButton('rolelearner', 'rolelearner', 'enrol_lticoursetemplate');

        if (!$legacy) {
            global $CFG;
            require_once($CFG->dirroot . '/auth/lti/auth.php');
            $authmodes = [
                auth_plugin_lti::PROVISIONING_MODE_AUTO_ONLY => get_string('provisioningmodeauto', 'auth_lti'),
                auth_plugin_lti::PROVISIONING_MODE_PROMPT_NEW_EXISTING => get_string('provisioningmodenewexisting', 'auth_lti'),
                auth_plugin_lti::PROVISIONING_MODE_PROMPT_EXISTING_ONLY => get_string('provisioningmodeexistingonly', 'auth_lti')
            ];
            $mform->addElement('select', 'provisioningmodeinstructor', get_string('provisioningmodeteacherlaunch', 'enrol_lticoursetemplate'),
                $authmodes);
            $mform->addHelpButton('provisioningmodeinstructor', 'provisioningmode', 'enrol_lticoursetemplate');
            $mform->setDefault('provisioningmodeinstructor', auth_plugin_lti::PROVISIONING_MODE_PROMPT_NEW_EXISTING);

            $mform->addElement('select', 'provisioningmodelearner', get_string('provisioningmodestudentlaunch', 'enrol_lticoursetemplate'),
                $authmodes);
            $mform->addHelpButton('provisioningmodelearner', 'provisioningmode', 'enrol_lticoursetemplate');
            $mform->setDefault('provisioningmodelearner', auth_plugin_lti::PROVISIONING_MODE_AUTO_ONLY);
        }

        $mform->addElement('header', 'remotesystem', get_string('remotesystem', 'enrol_lticoursetemplate'));

        $mform->addElement('text', 'secret', get_string('secret', 'enrol_lticoursetemplate'), 'maxlength="64" size="25"');
        $mform->setType('secret', PARAM_ALPHANUM);
        $mform->setDefault('secret', random_string(32));
        $mform->addHelpButton('secret', 'secret', 'enrol_lticoursetemplate');
        $mform->hideIf('secret', 'ltiversion', 'eq', 'LTI-1p3');

        $mform->addElement('selectyesno', 'gradesync', get_string('gradesync', 'enrol_lticoursetemplate'));
        $mform->setDefault('gradesync', 1);
        $mform->addHelpButton('gradesync', 'gradesync', 'enrol_lticoursetemplate');

        $mform->addElement('selectyesno', 'gradesynccompletion', get_string('requirecompletion', 'enrol_lticoursetemplate'));
        $mform->setDefault('gradesynccompletion', 0);
        $mform->disabledIf('gradesynccompletion', 'gradesync', 0);

        $mform->addElement('selectyesno', 'membersync', get_string('membersync', 'enrol_lticoursetemplate'));
        $mform->setDefault('membersync', 1);
        $mform->addHelpButton('membersync', 'membersync', 'enrol_lticoursetemplate');

        $options = array();
        $options[\enrol_lticoursetemplate\helper::MEMBER_SYNC_ENROL_AND_UNENROL] = get_string('membersyncmodeenrolandunenrol', 'enrol_lticoursetemplate');
        $options[\enrol_lticoursetemplate\helper::MEMBER_SYNC_ENROL_NEW] = get_string('membersyncmodeenrolnew', 'enrol_lticoursetemplate');
        $options[\enrol_lticoursetemplate\helper::MEMBER_SYNC_UNENROL_MISSING] = get_string('membersyncmodeunenrolmissing', 'enrol_lticoursetemplate');
        $mform->addElement('select', 'membersyncmode', get_string('membersyncmode', 'enrol_lticoursetemplate'), $options);
        $mform->setDefault('membersyncmode', \enrol_lticoursetemplate\helper::MEMBER_SYNC_ENROL_AND_UNENROL);
        $mform->addHelpButton('membersyncmode', 'membersyncmode', 'enrol_lticoursetemplate');
        $mform->disabledIf('membersyncmode', 'membersync', 0);

        $mform->addElement('header', 'defaultheader', get_string('userdefaultvalues', 'enrol_lticoursetemplate'));

        $emaildisplay = get_config('enrol_lticoursetemplate', 'emaildisplay');
        $choices = array(
            0 => get_string('emaildisplayno'),
            1 => get_string('emaildisplayyes'),
            2 => get_string('emaildisplaycourse')
        );
        $mform->addElement('select', 'maildisplay', get_string('emaildisplay'), $choices);
        $mform->setDefault('maildisplay', $emaildisplay);
        $mform->addHelpButton('maildisplay', 'emaildisplay');

        $city = get_config('enrol_lticoursetemplate', 'city');
        $mform->addElement('text', 'city', get_string('city'), 'maxlength="100" size="25"');
        $mform->setType('city', PARAM_TEXT);
        $mform->setDefault('city', $city);

        $country = get_config('enrol_lticoursetemplate', 'country');
        $countries = array('' => get_string('selectacountry') . '...') + get_string_manager()->get_list_of_countries();
        $mform->addElement('select', 'country', get_string('selectacountry'), $countries);
        $mform->setDefault('country', $country);
        $mform->setAdvanced('country');

        $timezone = get_config('enrol_lticoursetemplate', 'timezone');
        $choices = core_date::get_list_of_timezones(null, true);
        $mform->addElement('select', 'timezone', get_string('timezone'), $choices);
        $mform->setDefault('timezone', $timezone);
        $mform->setAdvanced('timezone');

        $lang = get_config('enrol_lticoursetemplate', 'lang');
        $mform->addElement('select', 'lang', get_string('preferredlanguage'), get_string_manager()->get_list_of_translations());
        $mform->setDefault('lang', $lang);
        $mform->setAdvanced('lang');

        $institution = get_config('enrol_lticoursetemplate', 'institution');
        $mform->addElement('text', 'institution', get_string('institution'), 'maxlength="40" size="25"');
        $mform->setType('institution', core_user::get_property_type('institution'));
        $mform->setDefault('institution', $institution);
        $mform->setAdvanced('institution');

        // Check if we are editing an instance.
        if (!empty($instance->id)) {
            // Get the details from the enrol_ct_tools table.
            $ltitool = $DB->get_record('enrol_ct_tools', array('enrolid' => $instance->id), '*', MUST_EXIST);

            $mform->addElement('hidden', 'toolid');
            $mform->setType('toolid', PARAM_INT);
            $mform->setConstant('toolid', $ltitool->id);

            $mform->addElement('hidden', 'uuid');
            $mform->setType('uuid', PARAM_ALPHANUMEXT);
            $mform->setConstant('uuid', $ltitool->uuid);

            $mform->setDefaults((array) $ltitool);
        }
    }

    /**
     * Perform custom validation of the data used to edit the instance.
     *
     * @param array $data array of ("fieldname"=>value) of submitted data
     * @param array $files array of uploaded files "element_name"=>tmp_file_path
     * @param object $instance The instance loaded from the DB
     * @param context $context The context of the instance we are editing
     * @return array of "element_name"=>"error_description" if there are errors,
     *         or an empty array if everything is OK.
     * @return void
     */
    public function edit_instance_validation($data, $files, $instance, $context) {
        global $COURSE, $DB;

        $errors = array();

        // Secret must be set.
        if (empty($data['secret'])) {
            $errors['secret'] = get_string('required');
        }

        if (!empty($data['enrolenddate']) && $data['enrolenddate'] < $data['enrolstartdate']) {
            $errors['enrolenddate'] = get_string('enrolenddateerror', 'enrol_lticoursetemplate');
        }

        if (!empty($data['requirecompletion'])) {
            $completion = new completion_info($COURSE);
            $moodlecontext = $DB->get_record('context', array('id' => $data['contextid']));
            if ($moodlecontext->contextlevel == CONTEXT_MODULE) {
                $cm = get_coursemodule_from_id(false, $moodlecontext->instanceid, 0, false, MUST_EXIST);
            } else {
                $cm = null;
            }

            if (!$completion->is_enabled($cm)) {
                $errors['requirecompletion'] = get_string('errorcompletionenabled', 'enrol_lticoursetemplate');
            }
        }

        return $errors;
    }

    /**
     * Restore instance and map settings.
     *
     * @param restore_enrolments_structure_step $step
     * @param stdClass $data
     * @param stdClass $course
     * @param int $oldid
     */
    public function restore_instance(restore_enrolments_structure_step $step, stdClass $data, $course, $oldid) {
        // We want to call the parent because we do not want to add an enrol_ct_tools row
        // as that is done as part of the restore process.
        $instanceid = parent::add_instance($course, (array)$data);
        $step->set_mapping('enrol', $oldid, $instanceid);
    }

    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     * @since Moodle 2.3
     */
    public static function duplicate_course_parameters() {
        return new external_function_parameters(
            array(
                'courseid' => new external_value(PARAM_INT, 'course to duplicate id'),
                'fullname' => new external_value(PARAM_TEXT, 'duplicated course full name'),
                'shortname' => new external_value(PARAM_TEXT, 'duplicated course short name'),
                'categoryid' => new external_value(PARAM_INT, 'duplicated course category parent'),
                'visible' => new external_value(PARAM_INT, 'duplicated course visible, default to yes', VALUE_DEFAULT, 1),
                'options' => new external_multiple_structure(
                    new external_single_structure(
                        array(
                            'name' => new external_value(PARAM_ALPHAEXT, 'The backup option name:
                            "activities" (int) Include course activites (default to 1 that is equal to yes),
                            "blocks" (int) Include course blocks (default to 1 that is equal to yes),
                            "filters" (int) Include course filters (default to 1 that is equal to yes),
                            "users" (int) Include users (default to 0 that is equal to no),
                            "role_assignments" (int) Include role assignments (default to 0 that is equal to no),
                            "comments" (int) Include user comments (default to 0 that is equal to no),
                            "userscompletion" (int) Include user course completion information (default to 0 that is equal to no),
                            "logs" (int) Include course logs (default to 0 that is equal to no),
                            "grade_histories" (int) Include histories (default to 0 that is equal to no)'
                            ),
                            'value' => new external_value(PARAM_RAW, 'the value for the option 1 (yes) or 0 (no)'
                            )
                        )
                    ), VALUE_DEFAULT, array()
                ),
            )
        );
    }

    /**
     * Duplicate a course
     *
     * @param int $courseid
     * @param string $fullname Duplicated course fullname
     * @param string $shortname Duplicated course shortname
     * @param int $categoryid Duplicated course parent category id
     * @param int $visible Duplicated course availability
     * @param array $options List of backup options
     * @return array New course info
     * @since Moodle 2.3
     */
    public static function duplicate_course($courseid, $fullname, $shortname, $categoryid, $visible = 1, $options = array()) {
        global $CFG, $USER, $DB;
        require_once($CFG->dirroot . '/backup/util/includes/backup_includes.php');
        require_once($CFG->dirroot . '/backup/util/includes/restore_includes.php');

        // Parameter validation.
        $params = array(
                      'courseid' => $courseid,
                      'fullname' => $fullname,
                      'shortname' => $shortname,
                      'categoryid' => $categoryid,
                      'visible' => $visible,
                      'options' => $options
                );

        // Context validation.

        if (! ($course = $DB->get_record('course', array('id' => $params['courseid'])))) {
            throw new moodle_exception('invalidcourseid', 'error');
        }

        // Category where duplicated course is going to be created.
        $categorycontext = context_coursecat::instance($params['categoryid']);

        // Course to be duplicated.
        $coursecontext = context_course::instance($course->id);

        $backupdefaults = array(
            'activities' => 1,
            'blocks' => 1,
            'filters' => 1,
            'users' => 0,
            'enrolments' => backup::ENROL_ALWAYS,
            'role_assignments' => 0,
            'comments' => 0,
            'userscompletion' => 0,
            'logs' => 0,
            'grade_histories' => 0
        );

        $backupsettings = array();
        // Check for backup and restore options.
        if (!empty($params['options'])) {
            foreach ($params['options'] as $option) {

                // Strict check for a correct value (allways 1 or 0, true or false).
                $value = clean_param($option['value'], PARAM_INT);

                if ($value !== 0 and $value !== 1) {
                    throw new moodle_exception('invalidextparam', 'webservice', '', $option['name']);
                }

                if (!isset($backupdefaults[$option['name']])) {
                    throw new moodle_exception('invalidextparam', 'webservice', '', $option['name']);
                }

                $backupsettings[$option['name']] = $value;
            }
        }

        // Check if the shortname is used.
        if ($foundcourses = $DB->get_records('course', array('shortname' => $shortname))) {
            foreach ($foundcourses as $foundcourse) {
                $foundcoursenames[] = $foundcourse->fullname;
            }

            $foundcoursenamestring = implode(',', $foundcoursenames);
            throw new moodle_exception('shortnametaken', '', '', $foundcoursenamestring);
        }

        // Backup the course.
        $bc = new backup_controller(backup::TYPE_1COURSE, $course->id, backup::FORMAT_MOODLE,
        backup::INTERACTIVE_NO, backup::MODE_SAMESITE, get_config('enrol_lticoursetemplate', 'manager'));

        foreach ($backupsettings as $name => $value) {
            if ($setting = $bc->get_plan()->get_setting($name)) {
                $bc->get_plan()->get_setting($name)->set_value($value);
            }
        }

        $backupid       = $bc->get_backupid();
        $backupbasepath = $bc->get_plan()->get_basepath();

        $bc->execute_plan();
        $results = $bc->get_results();
        $file = $results['backup_destination'];

        $bc->destroy();

        // Restore the backup immediately.

        // Check if we need to unzip the file because the backup temp dir does not contains backup files.
        if (!file_exists($backupbasepath . "/moodle_backup.xml")) {
            $file->extract_to_pathname(get_file_packer('application/vnd.moodle.backup'), $backupbasepath);
        }

        // Create new course.
        $newcourseid = restore_dbops::create_new_course($params['fullname'], $params['shortname'], $params['categoryid']);

        $rc = new restore_controller($backupid, $newcourseid,
                backup::INTERACTIVE_NO, backup::MODE_SAMESITE, get_config('enrol_lticoursetemplate', 'manager'),
                backup::TARGET_NEW_COURSE);

        foreach ($backupsettings as $name => $value) {
            $setting = $rc->get_plan()->get_setting($name);
            if ($setting->get_status() == backup_setting::NOT_LOCKED) {
                $setting->set_value($value);
            }
        }

        if (!$rc->execute_precheck()) {
            $precheckresults = $rc->get_precheck_results();
            if (is_array($precheckresults) && !empty($precheckresults['errors'])) {
                if (empty($CFG->keeptempdirectoriesonbackup)) {
                    fulldelete($backupbasepath);
                }

                $errorinfo = '';

                foreach ($precheckresults['errors'] as $error) {
                    $errorinfo .= $error;
                }

                if (array_key_exists('warnings', $precheckresults)) {
                    foreach ($precheckresults['warnings'] as $warning) {
                        $errorinfo .= $warning;
                    }
                }

                throw new moodle_exception('backupprecheckerrors', 'webservice', '', $errorinfo);
            }
        }

        $rc->execute_plan();
        $rc->destroy();

        $course = $DB->get_record('course', array('id' => $newcourseid), '*', MUST_EXIST);
        $course->fullname = $params['fullname'];
        $course->shortname = $params['shortname'];
        $course->visible = $params['visible'];

        // Set shortname and fullname back.
        $DB->update_record('course', $course);

        if (empty($CFG->keeptempdirectoriesonbackup)) {
            fulldelete($backupbasepath);
        }

        // Delete the course backup file created by this WebService. Originally located in the course backups area.
        $file->delete();

        return array('id' => $course->id, 'shortname' => $course->shortname);
    }

    /**
     * Returns description of method result value
     *
     * @return external_description
     * @since Moodle 2.3
     */
    public static function duplicate_course_returns() {
        return new external_single_structure(
            array(
                'id'       => new external_value(PARAM_INT, 'course id'),
                'shortname' => new external_value(PARAM_TEXT, 'short name'),
            )
        );
    }
}

/**
 * Display the LTI link in the course administration menu.
 *
 * @param settings_navigation $navigation The settings navigation object
 * @param stdClass $course The course
 * @param stdclass $context Course context
 */
function enrol_lticoursetemplate_extend_navigation_course($navigation, $course, $context) {
    // Check that the LTI plugin is enabled.
    if (enrol_is_enabled('lticoursetemplate')) {
        // Check that they can add an instance.
        $ltiplugin = enrol_get_plugin('lticoursetemplate');
        if ($ltiplugin->can_add_instance($course->id)) {
            $url = new moodle_url('/enrol/lticoursetemplate/index.php', ['courseid' => $course->id]);
            $settingsnode = navigation_node::create(get_string('sharedexternaltools', 'enrol_lticoursetemplate'), $url,
                navigation_node::TYPE_SETTING, null, 'publishedcttools', new pix_icon('i/settings', ''));
            $navigation->add_node($settingsnode);
        }
    }
}

/**
 * Get icon mapping for font-awesome.
 */
function enrol_llticoursetemplate_get_fontawesome_icon_map() {
    return [
        'enrol_lticoursetemplate:managedeployments' => 'fa-sitemap',
        'enrol_lticoursetemplate:platformdetails' => 'fa-pencil-square-o'
    ];
}

/**
 * Hook called before we delete a course.
 *
 * @param \stdClass $course The course record.
 */
function enrol_lticoursetemplate_pre_course_delete($course) {
    global $DB;
    // It is possible that the course deletion which triggered this hook
    // was from an in progress course restore.
    if (isset($course->deletesource) && $course->deletesource == 'restore') {
        return;
    }
    // Delete course from the plugin table.
    $DB->delete_records('enrol_ct_courses', array('courseid' => $course->id));
}