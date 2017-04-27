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
 * LTI enrolment plugin plugin upgrade code
 *
 * @package enrol_lticoursetemplate
 * @copyright 2017 Arek Juszczyk <arek.juszczyk@ed.ac.uk>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * @param int $oldversion the version we are upgrading from
 * @return bool result
 */
function xmldb_enrol_lticoursetemplate_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager(); // Loads ddl manager and xmldb classes.

    if ($oldversion < 2017042602) {
        // Define table enrol_lti_ct_courses to be created.
        $table = new xmldb_table('enrol_lti_ct_courses');

        // Adding fields to table enrol_lti_ct_courses.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('courseid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('shortname', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table enrol_lti_ct_courses.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('courseid', XMLDB_KEY_FOREIGN, array('courseid'), 'course', array('id'));

        // Adding indexes to table enrol_lti_ct_courses.
        $table->add_index('shortname', XMLDB_INDEX_UNIQUE, array('shortname'));

        // Conditionally launch create table for enrol_lti_ct_courses.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Lticoursetemplate savepoint reached.
        upgrade_plugin_savepoint(true, 2017042602, 'enrol', 'lticoursetemplate');
    }

    return true;
}