# enrol_lticoursetemplate

The purpose is to remove the need to create new courses by hand when providing access to Moodle courses by LTI.  This plugin provides an LTI service so when a the first authenticated user accesses with a secret a Moodle silently clones a template course automatically giving each teacher a new clean course tied to their consume content system.

Basically, we run through this flowchart.

1. Does the course requested exist?  If not, create a clone of the template course (linked to the course requested by the user).
2. Does the user exist?  If not, create a new use account on Moodle.  This account can only be used via the LTI plugin (no direct login permitted, avoiding the LTI).
3. Is the user enrolled on the requested course (which now both exist)?  If not, enrol the user on the course with the student/teacher permissions which come via LTI.
4. Login the user.
5. Load the course page.

This should all be transparent to the user - they arrive on the course page, with a user account and permissions matching those they had requested via LTI.

![Course menu for the plugin](./docs/images/lti_template_menu.png)

## Basic usage

1. Install as any normal Moodle plugin.
2. Setup the "Tool Provider" in Moodle to expose an LTI service.
3. Anyone consuming the LTI service will arrive with (i) a "user" identity (name, user_id,....),  (ii) a "course" identity (from the consumer LTI), and (iii) a "role" (e.g. student/teacher).  The pluging then basically does the following:
   * Has any authenticated user accessed this course before?  If not, create a blank clone of the template.  (The idea is to have a parallel course in Moodle to a course on the consumer side.)
   * Has this user accessed the service before?  If not, create a user account.
   * Has this user accessed this particular course before?  If not, enrol them on the course with the "role" (student/teacher) specified in the LTI call.
4. At this point we have course, a user, and the user has a role on the course.  Login the user, and silently pass them over to the course page.

From a user's perspective, they immediately see a course page.  They have an account on Moodle (which can only be accessed via LTI, by design they can't later login directly to Moodle), and they have the role on that course as requested by LTI.  (Typically this will correspond to the role in the consumer system, but that depends on what the LTI call asks for.)

When user accounts are created, a random password is assigned which is never shown to the user.  Such users _must_ access this service via the LTI connection.  Users may end up consuming more than one Moodle course if they have access to different courses on the LTI consumer side, which each access this service. (E.g. "Maths 1a" and "Maths 1b" each link the consumer management system to a corresponding course in the Moodle). Only one user account is created (assuming the LTI call provides identical user data to Moodle).  The Moodle site can still support other enrollment methods, and could allow direct login of typical Moodle users in parallel.

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

# LTI Course Template

This is a Moodle plugin that is based on the original Moodle plugin "Publish as LTI tool".
The changes allow automatic course creation (from a template course) through the LTI connection.

This plugin version supports LTI Advantage (LTI 1.3) 

## Install (should be automatic with the install script)
1. copy the litcoursetemplate dir into moodle/enrol/
2. install plugin in Moodle (site administration/notifications)

NOTE: the plugin requires a user ID that is capable to run course backup/restore.
   It is set to the ID of the user that installs the plugin but can be changed in the plugin settings.

# Configure moodle to be a Tool Provider, using Template Courses

Connecting via LTI 1.3 is a multi-step process

## Step 0 - Ensure the plugins are enabled

Go to `Site administration` -> `Plugins` -> `Authentication` -> `Manage authentication` and ensure `LTI` is _Enabled_

Go to `Site administration` -> `Plugins` -> `Enrolments` -> `Manage enrol plugins` and ensure `Publish as LTI Course Template tool` is _Enabled_ (and `Publish as LTI tool` is _Disabled_)

## Step 1 - Register the platform

The Platform, in this instance, refers to a running service, so
- 3 different moodles at the same organisation are 3 different platforms,
- BlackBoard Ultra is 1 platform
- Canvas & Moodle Workplace are currently assumed to appear as different platforms 

Go to `Site administration` -> `Plugins` -> `Enrolments` -> `Tool registration`

![platforms](README_images/platforms.png)
(here - `noteable-beta` has been registered, `delete me` has not. )

- If the platform is already registered skip to [step 3 - register the customers deployment](#step-3-register-the-customers-deployment)
- If the platform has not been registered: Click on `Register a platform`, give it a sensible name, and you'll be shown the _Registration_ details


### If the platform has _NOT_ been registered

**Send the Registration details to the customer**

- Send the customer the Registration details:
    - **Registration URL** eg https://foo.edu/moodle/enrol/lticoursetemplate/register.php?token=abc123def
    - **Tool URL** eg https://foo.edu/moodle/enrol/lticoursetemplate/launch.php
    - **Initiate login URL** eg https://foo.edu/moodle/enrol/lticoursetemplate/login.php?id=abc123def
    - **JWKS URL** eg https://foo.edu/moodle/enrol/lticoursetemplate/jwks.php
    - **Deep linking URL** eg https://foo.edu/moodle/enrol/lticoursetemplate/launch_deeplink.php

You may also want to send them a URL to the `icon` for your service.

## Step 2 - Wait for customer to configure their VLE & send you _Configuration_ details

- If they are BlackBoard Ultra, they will only send back a `Deployment ID`
- If they use the `Registration URL`, this will configure their "Platform" _and_ auto-configure the appropriate details back to StackEd. (This may be a "Moodle-specific" thing)
- If they configure manually, they will send _back_ details akin to:
    - `Platform ID`: https://example.com/ultra
    - `Client ID`: 123456AbCd789X
    - `Deployment ID`: 6
    - `Public keyset URL`: https://example.com/ultra/mod/lti/certs.php
    - `Access token URL`: https://example.com/ultra/mod/lti/token.php
    - `Authentication request URL`: https://example.com/ultra/mod/lti/auth.php

Note than the URLs are pointing to their platform.

### Configure the Platform Registration

This assumes you've been sent details akin to:
- `Platform ID`: https://example.com/ultra
- `Client ID`: 123456AbCd789X
- `Deployment ID`: 6
- `Public keyset URL`: https://example.com/ultra/mod/lti/certs.php
- `Access token URL`: https://example.com/ultra/mod/lti/token.php
- `Authentication request URL`: https://example.com/ultra/mod/lti/auth.php

and that `Site administration` -> `Plugins` -> `Enrolments` -> `Tool registration` does _not_ show `Details`
- Click on the `pen-on-paper` icon (![pen-on-paper](README_images/pen-on-paper.png))
    - Click on `Edit platform details`
    - Fill in the details given
    - Click on `save changes`
    - Continue with Step 3

## Step 3 - Register the Customer's deployment

Go to `Site administration` -> `Plugins` -> `Enrolments` -> `Tool registration` and find the appropriate registered platform for the customer

There are two options at this point:

![platforms_with_deployments](README_images/platforms_with_deployments.png)
1. The platform may already have some deployments (eg `noteable-beta` above)
2. The platform has no current deployments (eg `delete me` above)

### Platform already has deployments

Click on the number in the **Deployments** column, and check if the `Deployment ID` from the customer is listed.

- If it is, job done, go to Step 4
- If not, you need to add it - use the `Add a deployment` button
    - Give it a sensible name
    - enter the `Deployment ID` from the customer
    - Click on `Save changes`

### Platform has no deployments

Click on the `hierarchy` icon (![hierarchy](README_images/hierarchy.png))

- use the `Add a deployment` button
    - Give it a sensible name
    - enter the `Deployment ID` from the customer
    - Click on `Save changes`

## Step 4 - Creating a template course for the customer

The plugin works by using a base course, and then, for each LTI connection from the VLE, creating a _new course_ using the _base course_ as a template

This new course will take the name of the course from the LTI connection.

### Create the base course

Go to `Site administration` -> `Courses` -> `Manages courses and categories`

- We suggest creating a new `category` using the name of the customer.... unless you are creating a new course for an existing customer
- Create a new `course`
  - The `full name` should be indicative of the customer
  - The `short name` should be short, use only the characters `a-zA-Z0-9-`
  - The `category` should be appropriate for the customer
  - Set `visibility` to `hide` 
  - Remove the tick enabling `end date`
  - Click on `Save and return`
- Click on the `cog` icon for the template course
  - Under the `More` tab, select `Publish as LTI Course Template tools`
  - Ensure your using the `LTI Advantage` tab
  - Click `Add` to create a new connection
    - Set the name to something sensible
    - change **Teacher first launch provisioning mode** to be `New accounts only (automatic)` [this stops remote `Admin`s connecting to your `Admin` account!]
    - Under `User default values` (some fields are hidden under `Show more...`) you may want to set `City/town` and `Country`, and maybe set `Institution` to the customer's name
    - Click `Add method`
  - **Note the `Custom properties` entry** (`id=abc-123-etc`)
  
#### You may wish to pre-fill the template with course-work:

You can _restore_ a moodle backup course (.mbz) file to the course, which will then appear is all subsequent courses using this template.

`Site administration` -> `Courses` -> `Restore course`
    Standard Moodle backup process, and it can be put into any category (miscellaneous is fine).  

### Send the customer the required Custom Parameters

The LTI Course Template Tool requires 2 custom parameters from the customer's connection: an `id` to identify the course to use as the template; and `platform` to seed the short-code for the created course

- `id` is the value from the `Custom properties` above
- `platform` should be a _short_ code for the customer - it is used to seed the short code for courses created from the template

The customer needs to add these to their Tool Configuration.
 
## Step 5 - Test!

Get the customer to test the connection works

---

# **Notes on manually registered moodle fields**

**Tool Settings**
- **Tool URL** == _Tool URL_
- **Plublic key type** == `Keyset URL`
- **Public keyset** == _JWKS URL_
- **Initiate login** == _Initialte login URL_
- **Redirection URI(s)** == _Tool URL_ & _Deep linking URL_
- **Custom parameters** == clear, for now
- **Default launch container** == `New window`
- (`Supports Deep Linking (Content-Item Message)` checked)
- **Content Selection URL** == _Deep linking URL_

**Privacy**
- **Share launcher's name with tool** == `Always`
- **Share launcher's email with tool** == `Always`
  
---

### LTI 1 version setup

Set up Tool Consumer (general, in Learn):
1. Add external tool to a course
2. Insert launch URL and secret from the Tool Provider
3. Type a unique string as Consumer key.  Use any string that will be the same for every LTI connections between the tool consumer and provider. THIS MUST BE UNIQUE FOR YOUR TOOL CONSUMER WITHIN TOOL PROVIDER! And must not be changed after the link has been used.

NOTE: if “Allow frame embedding” HTTP Security option is not enabled in Moodle then the users will see an additional page with a link to redirect them to the Moodle course.

DONE

**Set up Blackboard Learn as Tool Consumer:**

I. Method 1 - Register New Tool

   This method requires the TC administrator to set up Consumer Key and Secret for every tool set up in the TC.

1. Register new Basic LTI tool (System Admin->Basic LTI tools(Tools and Utilities) )
2. Registration Settings
   1. Provide name
   2. Set Launch URL from Tool Provider
   3. Set Consumer key - IMPORTANT, MUST BE SET and should not be changed after the link has been used
   4. Set Shared secret form Tool Provider
   5. Set Outcomes - required by the tool
   6. Tick box next to “Create a grade column in advance of first use?”
   7. Tic box next to: Outcomes (LTI 1.1) [DISABLED], Tool Consumer Profile [DISABLED], Tool Settings [DISABLED]
   8. Leave anything else unchanged and click Submit
3. Data Settings
   
   **CONTEXT DATA**
   1. Tick Context ID box
   2. Select “Database key” as Value to use for context ID
   3. Tick SourcedId box
   4. Tick Context Title box
   
   **PERSONAL DATA**

   5. User Id - Required by tool
   6. Value to use for user ID - Database key
   7. SourcedId tick box
   8. User name - Required by tool
   9. Email - Required by tool
   10. User Avatar - optional
   11. User Roles - tick box
   12. Leave anything else unchanged and click Submit
   13. CONNECT TO TOOL OPTIONS - set up to whatever preferred
   
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