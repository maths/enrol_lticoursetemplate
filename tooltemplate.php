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
 * The main entry point for the external system.
 *
 * @package    enrol_lticoursetemplate
 * @copyright  2016 Mark Nelson <markn@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/user/lib.php');
require_once($CFG->dirroot . '/enrol/lticoursetemplate/ims-blti/blti.php');

$toolid = required_param('id', PARAM_INT);

$PAGE->set_context(context_system::instance());
$url = new moodle_url('/enrol/lticoursetemplate/tooltemplate.php');
$PAGE->set_url($url);
$PAGE->set_pagelayout('popup');
$PAGE->set_title(get_string('opentool', 'enrol_lticoursetemplate'));

// Get the tool.
$oldtool = \enrol_lticoursetemplate\helper::get_lti_tool($toolid);

// Create the BLTI request.
$ltirequest = new BLTI($oldtool->secret, false, false);

// Correct launch request.
if ($ltirequest->valid) {

    // Get the new tool.
    $tool = \enrol_lticoursetemplate\helper::get_lti_new_tool(
        $toolid,
        $ltirequest->info['oauth_consumer_key'],
        $ltirequest->info['context_id'],
        $ltirequest->info['context_title'],
        $ltirequest->isInstructor()
    );

    // Log lti request data.
    $event = \enrol_lticoursetemplate\event\lticonnection_launched::create(array(
        'objectid' => 0,
        'context' => context::instance_by_id($tool->contextid),
        'other'    => (array) $ltirequest
    ));

    $event->trigger();

    // Check if the authentication plugin is disabled.
    if (!is_enabled_auth('lti')) {
        print_error('pluginnotenabled', 'auth', '', get_string('pluginname', 'auth_lti'));
        exit();
    }

    // Check if the enrolment plugin is disabled.
    if (!enrol_is_enabled('lticoursetemplate')) {
        print_error('enrolisdisabled', 'enrol_lticoursetemplate');
        exit();
    }

    // Check if the enrolment instance is disabled.
    if ($tool->status != ENROL_INSTANCE_ENABLED) {
        print_error('enrolisdisabled', 'enrol_lticoursetemplate');
        exit();
    }
    //*********** new stuff *****************************************************************
    // Before we do anything check that the context is valid.
    $context = context::instance_by_id($tool->contextid);

    // Set the user data.
    $user = new stdClass();
    $user->username = \enrol_lticoursetemplate\helper::create_username(
        $ltirequest->info['oauth_consumer_key'],
        $ltirequest->info['user_id']
    );

    // Log generated user name.
    $event = \enrol_lticoursetemplate\event\ltiname_created::create(array(
        'objectid' => 0,
        'context' => context::instance_by_id($tool->contextid),
        'other'    => array(
            'username' => $user->username,
            'oauth_consumer_key' => $ltirequest->info['oauth_consumer_key'],
            'user_id' => $ltirequest->info['user_id'],
        )
    ));
    $event->trigger();

    if (!empty($ltirequest->info['lis_person_name_given'])) {
        $user->firstname = $ltirequest->info['lis_person_name_given'];
    } else {
        $user->firstname = $ltirequest->info['user_id'];
    }
    if (!empty($ltirequest->info['lis_person_name_family'])) {
        $user->lastname = $ltirequest->info['lis_person_name_family'];
    } else {
        $user->lastname = $ltirequest->info['context_id'];
    }

    $user->email = \core_user::clean_field($ltirequest->getUserEmail(), 'email');

    // Get the user data from the LTI consumer.
    $user = \enrol_lticoursetemplate\helper::assign_user_tool_data($oldtool, $user);

    // Check if the user exists.
    if (!$dbuser = $DB->get_record('user', array('username' => $user->username, 'deleted' => 0))) {
        // If the email was stripped/not set then fill it with a default one. This
        // stops the user from being redirected to edit their profile page.
        if (empty($user->email)) {
            $user->email = $user->username .  "@example.com";
        }

        $user->auth = 'lti';
        $user->id = user_create_user($user);

        // Get the updated user record.
        $user = $DB->get_record('user', array('id' => $user->id));

        // Log created user.
        $event = \enrol_lticoursetemplate\event\ltiuser_created::create(array(
            'objectid' => 0,
            'context' => context::instance_by_id($tool->contextid),
            'other'    => array(
                'username' => $user->username,
                'email' => $user->email,
            )
        ));
        $event->trigger();
    } else {
        $event = \enrol_lticoursetemplate\event\dbuser_retrieved::create(array(
            'objectid' => 0,
            'context' => context::instance_by_id($tool->contextid),
            'other' => (array) $dbuser
        ));

        $event->trigger();

        if ($dbuser->suspended) {
            // Log suspended users access try.
            $event = \enrol_lticoursetemplate\event\ltiuser_suspended::create(array(
                'objectid' => $dbuser->id,
                'context' => context::instance_by_id($tool->contextid),
                'other'    => (array) $dbuser
            ));
            $event->trigger();

            throw new moodle_exception('useraccountsuspended', 'enrol_lticoursetemplate');
            exit();
        }

        if (\enrol_lticoursetemplate\helper::user_match($user, $dbuser)) {
            $user = $dbuser;

            // Log matched users.
            $event = \enrol_lticoursetemplate\event\ltiuser_matched::create(array(
                'objectid' => 0,
                'context' => context::instance_by_id($tool->contextid),
                'other'    => array(
                    'username' => $user->username,
                    'db_username' => $dbuser->username,
                )
            ));
            $event->trigger();
        } else {
            // If email is empty remove it, so we don't update the user with an empty email.
            if (empty($user->email)) {
                unset($user->email);
            }

            $user->id = $dbuser->id;
            user_update_user($user);

            // Get the updated user record.
            $user = $DB->get_record('user', array('id' => $user->id));
        }
    }

    // Update user image.
    $image = false;
    if (!empty($ltirequest->info['user_image'])) {
        $image = $ltirequest->info['user_image'];
    } else if (!empty($ltirequest->info['custom_user_image'])) {
        $image = $ltirequest->info['custom_user_image'];
    }

    // Check if there is an image to process.
    if ($image) {
        \enrol_lticoursetemplate\helper::update_user_profile_image($user->id, $image);
    }

    // Check if we are an instructor.
    $isinstructor = $ltirequest->isInstructor();

    if ($context->contextlevel == CONTEXT_COURSE) {
        $courseid = $context->instanceid;
        $urltogo = new moodle_url('/course/view.php', array('id' => $courseid));

        // May still be set from previous session, so unset it.
        unset($SESSION->forcepagelayout);
    } else if ($context->contextlevel == CONTEXT_MODULE) {
        $cmid = $context->instanceid;
        $cm = get_coursemodule_from_id(false, $context->instanceid, 0, false, MUST_EXIST);
        $urltogo = new moodle_url('/mod/' . $cm->modname . '/view.php', array('id' => $cm->id));

        // If we are a student in the course module context we do not want to display blocks.
        if (!$isinstructor) {
            // Force the page layout.
            $SESSION->forcepagelayout = 'embedded';
        } else {
            // May still be set from previous session, so unset it.
            unset($SESSION->forcepagelayout);
        }
    } else {
        print_error('invalidcontext');
        exit();
    }

    // Enrol the user in the course with no role.
    $result = \enrol_lticoursetemplate\helper::enrol_user($tool, $user->id);

    // Display an error, if there is one.
    if ($result !== \enrol_lticoursetemplate\helper::ENROLMENT_SUCCESSFUL) {
        print_error($result, 'enrol_lticoursetemplate');
        exit();
    }

    // Give the user the role in the given context.
    $roleid = $isinstructor ? $tool->roleinstructor : $tool->rolelearner;
    role_assign($roleid, $user->id, $tool->contextid);

    // Login user.
    $sourceid = (!empty($ltirequest->info['lis_result_sourcedid'])) ? $ltirequest->info['lis_result_sourcedid'] : '';
    $serviceurl = (!empty($ltirequest->info['lis_outcome_service_url'])) ? $ltirequest->info['lis_outcome_service_url'] : '';

    // Check if we have recorded this user before.
    if ($userlog = $DB->get_record('enrol_ct_users', array('toolid' => $tool->id, 'userid' => $user->id))) {
        if ($userlog->sourceid != $sourceid) {
            $userlog->sourceid = $sourceid;
        }
        if ($userlog->serviceurl != $serviceurl) {
            $userlog->serviceurl = $serviceurl;
        }
        $userlog->lastaccess = time();
        $DB->update_record('enrol_ct_users', $userlog);
    } else {
        // Add the user details so we can use it later when syncing grades and members.
        $userlog = new stdClass();
        $userlog->userid = $user->id;
        $userlog->toolid = $tool->id;
        $userlog->serviceurl = $serviceurl;
        $userlog->sourceid = $sourceid;
        $userlog->consumerkey = $ltirequest->info['oauth_consumer_key'];
        $userlog->consumersecret = $tool->secret;
        $userlog->lastgrade = 0;
        $userlog->lastaccess = time();
        $userlog->timecreated = time();

        if (!empty($ltirequest->info['ext_ims_lis_memberships_url'])) {
            $userlog->membershipsurl = $ltirequest->info['ext_ims_lis_memberships_url'];
        } else {
            $userlog->membershipsurl = '';
        }

        if (!empty($ltirequest->info['ext_ims_lis_memberships_id'])) {
            $userlog->membershipsid = $ltirequest->info['ext_ims_lis_memberships_id'];
        } else {
            $userlog->membershipsid = '';
        }
        $DB->insert_record('enrol_ct_users', $userlog);
    }

    // Finalise the user log in.
    complete_user_login($user);

    if (empty($CFG->allowframembedding)) {
        // Provide an alternative link.
        $stropentool = get_string('opentool', 'enrol_lticoursetemplate');
        echo html_writer::tag('p', get_string('frameembeddingnotenabled', 'enrol_lticoursetemplate'));
        echo html_writer::link($urltogo, $stropentool, array('target' => '_blank'));
    } else {
        // All done, redirect the user to where they want to go.
        redirect($urltogo);
    }
    //***************************************************************************************
    
    
    //*********** original stuff ************************************************************
    // $consumerkey = required_param('oauth_consumer_key', PARAM_TEXT);
    // $ltiversion = optional_param('lti_version', null, PARAM_TEXT);
    // $messagetype = required_param('lti_message_type', PARAM_TEXT);

    // // Only accept launch requests from this endpoint.
    // if ($messagetype != "basic-lti-launch-request") {
    //     print_error('invalidrequest', 'enrol_lticoursetemplate');
    //     exit();
    // }

    // // Initialise tool provider.
    // $toolprovider = new \enrol_lticoursetemplate\tool_provider($toolid);

    // // Special handling for LTIv1 launch requests.
    // if ($ltiversion === \IMSGlobal\LTI\ToolProvider\ToolProvider::LTI_VERSION1) {
    //     $dataconnector = new \enrol_lticoursetemplate\data_connector();
    //     $consumer = new \IMSGlobal\LTI\ToolProvider\ToolConsumer($consumerkey, $dataconnector);
    //     // Check if the consumer has already been registered to the enrol_ct_lti2_consumer table. Register if necessary.
    //     $consumer->ltiVersion = \IMSGlobal\LTI\ToolProvider\ToolProvider::LTI_VERSION1;
    //     // For LTIv1, set the tool secret as the consumer secret.
    //     $consumer->secret = $tool->secret;
    //     $consumer->name = optional_param('tool_consumer_instance_name', '', PARAM_TEXT);
    //     $consumer->consumerName = $consumer->name;
    //     $consumer->consumerGuid = optional_param('tool_consumer_instance_guid', null, PARAM_TEXT);
    //     $consumer->consumerVersion = optional_param('tool_consumer_info_version', null, PARAM_TEXT);
    //     $consumer->enabled = true;
    //     $consumer->protected = true;
    //     $consumer->save();

    //     // Set consumer to tool provider.
    //     $toolprovider->consumer = $consumer;
    //     // Map tool consumer and published tool, if necessary.
    //     $toolprovider->map_tool_to_consumer();
    // }

    // // Handle the request.
    // $toolprovider->handleRequest();

    // echo $OUTPUT->header();
    // echo $OUTPUT->footer();
    //***************************************************************************************
} else {
    echo $ltirequest->message;
}
