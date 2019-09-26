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
 * The dbuser_retrieved event class.
 *
 * @property-read array $other {
 *      Extra information about event.
 *
 *      Log all the LTI data coming from the external system
 * }
 *
 * @since     Moodle 2016051900
 * @copyright 2019 Arek Juszczyk
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 **/
class dbuser_retrieved extends \core\event\base
{
    protected function init()
    {
        $this->data['crud'] = 'c'; // c(reate), r(ead), u(pdate), d(elete)
        $this->data['edulevel'] = self::LEVEL_PARTICIPATING;
        $this->data['objecttable'] = 'enrol_lti_ct_tools';
    }
 
    public static function get_name()
    {
        return get_string('dbuser_retrieved', 'enrol_lticoursetemplate');
    }
 
    public function get_description()
    {
        // Print the array
        return print_r($this->other, true);
    }
 
    public function get_url()
    {
        return new \moodle_url('no url', array('parameter' => 'value'));
    }
}
