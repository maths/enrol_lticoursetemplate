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
 * LTI enrolment plugin version information
 *
 * @package enrol_lticoursetemplate
 * @copyright 2016 Mark Nelson <markn@moodle.com> 2017 Arek Juszczyk <arek.juszczyk@ed.ac.uk>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['allowframeembedding'] = 'Note: It is recommended that the site administration setting \'Allow frame embedding\' is enabled, so that tools are displayed within a frame rather than in a new window.';
$string['authltimustbeenabled'] = 'Note: This plugin requires the LTI authentication plugin to be enabled too.';
$string['enrolenddate'] = 'End date';
$string['enrolenddate_help'] = 'If enabled, users can access until this date only.';
$string['enrolenddateerror'] = 'Enrolment end date cannot be earlier than start date';
$string['enrolisdisabled'] = 'The \'Publish as LTI Course Template tool\' plugin is disabled.';
$string['enrolperiod'] = 'Enrolment duration';
$string['enrolperiod_help'] = 'Length of time that the enrolment is valid, starting with the moment the user enrols themselves from the remote system. If disabled, the enrolment duration will be unlimited.';
$string['enrolmentfinished'] = 'Enrolment finished.';
$string['enrolmentnotstarted'] = 'Enrolment has not started.';
$string['enrolstartdate'] = 'Start date';
$string['enrolstartdate_help'] = 'If enabled, users can access from this date onward only.';
$string['frameembeddingnotenabled'] = 'To access the tool, please follow the link below.';
$string['gradesync'] = 'Grade synchronisation';
$string['gradesync_help'] = 'Whether grades from the tool are sent to the remote system (LTI consumer).';
$string['maxenrolled'] = 'Maximum enrolled users';
$string['maxenrolled_help'] = 'The maximum number of remote users who can access the tool. If set to zero, the number of enrolled users is unlimited.';
$string['maxenrolledreached'] = 'The maximum number of remote users allowed to access the tool has been reached.';
$string['membersync'] = 'User synchronisation';
$string['membersync_help'] = 'Whether a scheduled task synchronises enrolled users in the remote system with enrolments in this course, creating an account for each remote user as necessary, and enrolling or unenrolling them as required.

If set to no, at the moment when a remote user accesses the tool, an account will be created for them and they will be automatically enrolled.';
$string['membersyncmode'] = 'User synchronisation mode';
$string['membersyncmode_help'] = 'Whether remote users should be enrolled and/or unenrolled from this course.';
$string['membersyncmodeenrolandunenrol'] = 'Enrol new and unenrol missing users';
$string['membersyncmodeenrolnew'] = 'Enrol new users';
$string['membersyncmodeunenrolmissing'] = 'Unenrol missing users';
$string['notoolsprovided'] = 'No tools provided';
$string['lticoursetemplate:config'] = 'Configure \'Publish as LTI Course Template tool\' instances';
$string['lticoursetemplate:unenrol'] = 'Unenrol users from the course';
$string['opentool'] = 'Open tool';
$string['pluginname'] = 'Publish as LTI Course Template tool';
$string['pluginname_desc'] = 'The \'Publish as LTI Course Template tool\' plugin, together with the LTI authentication plugin, allows remote users to access selected courses and activities. In other words, Moodle functions as an LTI tool provider. It is a copy of the original \'Publish as LTI tool\' plugin but duplicates a template course when run from the tool consumer for the first time.';
$string['remotesystem'] = 'Remote system';
$string['requirecompletion'] = 'Require course or activity completion prior to grade synchronisation';
$string['roleinstructor'] = 'Role for teacher';
$string['roleinstructor_help'] = 'The role assigned in the tool to the remote teacher.';
$string['rolelearner'] = 'Role for student';
$string['rolelearner_help'] = 'The role assigned in the tool to the remote student.';
$string['secret'] = 'Secret';
$string['secret_help'] = 'A string of characters which is shared with the remote system (LTI consumer) to provide access to the tool.';
$string['sharedexternaltools'] = 'Published as LTI Course Template tools';
$string['tasksyncgrades'] = 'Publish as LTI Course Template tool grade sync';
$string['tasksyncmembers'] = 'Publish as LTI Course Template tool users sync';
$string['toolsprovided'] = 'Published tools';
$string['tooltobeprovided'] = 'Tool to be published';
$string['userdefaultvalues'] = 'User default values';
$string['invalidconsumerkey'] = 'Consumer key has not been set';
$string['invalidshortname'] = 'Course shortname has not been set';
$string['manager'] = 'User ID';
$string['manager_help'] = 'Set user id that will run course backup/restore. By default it is your user id value but you can create a new user with required capabilities and set its id here.';
$string['admindefaultvalues'] = 'Manager default values';
$string['onlyonetoolallowed'] = 'Only one tool can be creatad for a course';
$string['useraccountsuspended'] = 'Your account has been suspended';
