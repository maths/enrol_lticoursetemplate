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
 * The EVENTNAME event.
 *
 * @package    enrol_lticoursetemplate
 * @copyright  2014 YOUR NAME
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace enrol_lticoursetemplate\event;

defined('MOODLE_INTERNAL') || die();
/**
 * The ltiuser_created event class.
 *
 * @property-read array $other {
 *      Extra information about event.
 *
 *      Log the name created for the user
 * }
 *
 * @since     Moodle 2016051900
 * @copyright 2019 Arek Juszczyk
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 **/
class ltiuser_created extends \core\event\base {

    const DEBUGFLAG = 0;

    protected function init() {
        $this->data['crud'] = 'c'; // Read these codes as c(reate), r(ead), u(pdate), d(elete).
        $this->data['edulevel'] = self::LEVEL_PARTICIPATING;
        $this->data['objecttable'] = 'enrol_lti_ct_tools';
    }

    public static function get_name() {
        return get_string('ltiuser_created', 'enrol_lticoursetemplate');
    }

    public function get_description() {
        if (self::DEBUGFLAG) {
            // @codingStandardsIgnoreStart
            return print_r($this->other, true);
            // @codingStandardsIgnoreEnd
        } else {
            return "A new LTI user has been created: username: '{$this->other['username']}', email: '{$this->other['email']}'";
        }
    }

    public function get_url() {
        return new \moodle_url('no url', array('parameter' => 'value'));
    }
}
