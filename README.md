# lticoursetemplate
## LTI Course Template

This is a Moodle plugin that is based on the original Moodle plugin "Publish as LTI tool".
The changes allow automatic course creation (template course) through the LTI connection.

This plugin version supports LTI Advantage (LTI 1.3) 

** Install (should be automatic with the install script)
1. copy the litcoursetemplate dir into moodle/enrol/
2. install plugin in Moodle (site administration/notifications)
3. NOTE: the plugin requires a user ID that is capable to run course backup/restore.
   It is set to the ID of the user that installs the plugin but can be changed in the plugin settings.

** Set up Tool Provider (Moodle):
1. Enable LTI authentication plugin (site admin->plugins->authentication->manage authentications)
2. Enable Publish as LTI Course Template tool enrolment plugin (site admin->plugins->enrolments->manage enrol plugins)
2.1 enrol_lticoursetemplate | city = Edinburgh
    enrol_lticoursetemplate | country = UK
3. Create a template course
3.1 Restore the template moodle backup course (.mbz) stored in this git repro templates/template.mbz.
    Dashboard -> Site administration -> Courses -> Restore course
    Standard Moodle backup process, and it can be put into any category (miscellaneous is fine).
3.2 Hide this course.
    Dashboard -> Site administration -> Courses -> Manage courses and categories -> Miscellaneous
4. Set up “Published as LTI Course Template tools” connection (course admin->Published as LTI Course Template tools)
   "User synchronisation" = "no".


###LTI 1 version setup

Set up Tool Consumer (general, in Learn):
1. Add external tool to a course
2. Insert launch URL and secret from the Tool Provider
3. Type a unique string as Consumer key.  Use any string that will be the same for every LTI connections between the tool consumer and provider. THIS MUST BE UNIQUE FOR YOUR TOOL CONSUMER WITHIN TOOL PROVIDER! And must not be changed after the link has been used.

NOTE: if “Allow frame embedding” HTTP Security option is not enabled in Moodle then the users will see an additional page with a link to redirect them to the Moodle course.

DONE

Set up Blackboard Learn as Tool Consumer:

I. Method 1 - Register New Tool

   This method requires the TC administrator to set up Consumer Key and Secret for every tool set up in the TC.

1. Register new Basic LTI tool (System Admin->Basic LTI tools(Tools and Utilities) )
2. Registration Settings
   2.a. Provide name
   2.b. Set Launch URL from Tool Provider
   2.c. Set Consumer key - IMPORTANT, MUST BE SET and should not be changed after the link has been used
   2.d. Set Shared secret form Tool Provider
   2.e. Set Outcomes - required by the tool
   2.f. Tick box next to “Create a grade column in advance of first use?”
   2.g. Tic box next to: Outcomes (LTI 1.1) [DISABLED], Tool Consumer Profile [DISABLED], Tool Settings [DISABLED]
   2.h. Leave anything else unchanged and click Submit
3. Data Settings
   CONTEXT DATA
   3.a. Tick Context ID box
   3.b. Select “Database key” as Value to use for context ID
   3.c. Tick SourcedId box
   3.d. Tick Context Title box
   PERSONAL DATA
   3.d. User Id - Required by tool
   3.e. Value to use for user ID - Database key
   3.f. SourcedId tick box
   3.g. User name - Required by tool
   3.h. Email - Required by tool
   3.i. User Avatar - optional
   3.j. User Roles - tick box
   3.k. Leave anything else unchanged and click Submit
   3.l. CONNECT TO TOOL OPTIONS - set up to whatever preferred
4. Add Basic LTI Tool “by Name” to a course

DONE

II. Method 2 - Register New Domain

   This method allows to set up the Moodle instance as a domain and define the Consumer key and Secret only once.

1. Registration Settings
   1.a. Domain name - your Moodle domain (i.e.: moodle.ed.ac.uk)

   The rest of the settings is the same as in the Method 1.

2. Add Basic LTI Tool to a course
   2.a. Select “By URL”
   2.b. Provide name
   2.c. Provide Launch URL form TP

DONE