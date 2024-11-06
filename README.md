# enrol_lticoursetemplate

The purpose is to remove the need to create new courses by hand when providing access to Moodle courses by LTI.  This plugin provides an LTI service which enables users to automatically and silently clone a course template from a remote LTI connection.  It is designed to allow connection of one learning management system to Moodle, automatically giving each teacher the ability to create a new clean course tied to their consumer content system.    

The basic useage is as follows.

1. Install as any normal Moodle plugin.
2. Setup the "Tool Provider" in moodle to expose an LTI service.
3. Anyone consuming the LTI service will arrive with (i) a "user" identity (name, user_id,....),  (ii) a "course" identity (from the consumer LTI), and (iii) a "role" (e.g. student/teacher).  The pluging then basically does the following:
   * Has any authenticated user accessed this course before?  If not, create a blank clone of the template.  (The idea is to have a parallel course in Moodle to a course on the consumer side.)
   * Has this user accessed the service before?  If not, create a user account.  
   * Has this user accesses this particular course before?  If not, enrole them on the course with the "role" (student/teacher) specified in the LTI call.
4. At this point we have course, a user, and the user has a role on the course.  Login the user, and silently pass them over to the course page.

From a user's perspective, they now see a course.  They have an account on Moodle (which can only be accessed via LTI, by design they can't later login directly to Moodle), and they have the role on that course as requested by LTI.  (Typically this will corrrespond to the role in the consumer system, but that depends on what the LTI call asks for.)

When user accounts are created. a random password is assigned which is never shown to the user.  Such users _must_ access this service via LTI.  Users may end up consuming more than one moodle course if they have access to different courses on the LTI consumer side, which each access this service. (E.g. "Maths 1a" and "Maths 1b" each link the consumer management system to a corresponding course in the Moodle). Only one user account is created (assuming the LTI call provides identical user data to Moodle).  The Moodle site can still support other enrolement methods, and could allow direct login of typical Moodle users in parallel.

Typically the "cloned" course will be empty or minimal.  It _could_ be a fully populated content course, such as an online book.  In that situation, Moodle might be configured to restrict the teachers' ability to edit the content.  It is possible to expose more than one template on a single Moodle instance, via different LTI calls.

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
