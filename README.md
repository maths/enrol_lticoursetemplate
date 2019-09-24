# enrol_lticoursetemplate

Provide an LTI service which enables users to clone a course template from a remote connection.  Designed to allow connection of one learning management system to Moodle, automatically giving each teacher the ability to create a new clean course tied to their consumer content system.

### Install as any normal Moodle plugin:

1. Copy/clone into `moodle/enrol/lticoursetemplate`
2. Install plugin in Moodle (site administration/notifications)

NOTE: the plugin requires a user ID that is capable to run course backup/restore.  The user ID is set to the ID of the user that installs the plugin but can be changed in the plugin settings.

### Set up Tool Provider (Moodle):

1. Enable LTI authentication plugin (site admin->plugins->authentication->manage authentications)
2. Enable Publish as LTI Course Template tool enrolment plugin (site admin->plugins->enrolments->manage enrol plugins)
3. Create a template course and hide this course.
4. Set up "Published as LTI Course Template tools" connection (course admin->Published as LTI Course Template tools)

There are a number of options which you should consider.

You can create as many course templates as you like on one Moodle server, and seed these with content.

