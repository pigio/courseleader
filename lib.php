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
 * This page lists public api for tool_monitor plugin.
 *
 * @package    tool_monitor
 * @copyright  2014 onwards Ankit Agarwal <ankit.agrr@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;





function local_courseleaderboard_extend_navigation(global_navigation $navigation)
{
    // $main_node = $navigation->add(get_string('pluginname', 'local_courseleaderboard'), new moodle_url('/local/courseleaderboard/courseleaderboard.php'), 0, 'nav_courseleaderboard', 'local_courseleaderboard',  new \pix_icon('i/report',''));
    // $main_node->nodetype = 1;
    // $main_node->collapse = false;
    // $main_node->forceopen = true;
    // $main_node->isexpandable = false;
    // $main_node->showinflatnavigation = true;
    global $PAGE, $COURSE;
    $coursenode = $PAGE->navigation->find($COURSE->id, navigation_node::TYPE_COURSE);
    $thingnode = $coursenode->add(get_string('pluginname', 'local_courseleaderboard'), new moodle_url('/local/courseleaderboard/courseleaderboard.php', array('id'=>$COURSE->id)), 0, 'nav_courseleaderboard', 'local_courseleaderboard',  new \pix_icon('t/award',''));
    $thingnode->make_active();
 
}



?>