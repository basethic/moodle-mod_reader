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
 * Filter attempts for reports on a Reader activity
 *
 * @package   mod-reader
 * @copyright 2013 Gordon Bateson <gordon.bateson@gmail.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

// get parent class
require_once($CFG->dirroot.'/user/filters/select.php');

/**
 * reader_report_filter_group
 *
 * @copyright 2013 Gordon Bateson
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since     Moodle 2.0
 */
class reader_report_filter_group extends user_filter_select {
    /**
     * Constructor
     * @param string $name the name of the filter instance
     * @param boolean $advanced advanced form element flag
     * @param mixed $default option
     */
    function __construct($filtername, $advanced, $default=null) {
        global $reader;

        $label = '';
        $options = array();

        $strgroup = get_string('group', 'group');
        $strgrouping = get_string('grouping', 'group');

        if ($groupings = groups_get_all_groupings($reader->course->id)) {
            $label = $strgrouping;
            $has_groupings = true;
        } else {
            $has_groupings = false;
            $groupings = array();
        }

        if ($groups = groups_get_all_groups($reader->course->id)) {
            if ($label) {
                $label .= ' / ';
            }
            $label .= $strgroup;
            $has_groups = true;
        } else {
            $has_groups = false;
            $groups = array();
        }

        foreach ($groupings as $gid => $grouping) {
            if ($has_groups) {
                $prefix = $strgrouping.': ';
            } else {
                $prefix = '';
            }
            if ($members = groups_get_grouping_members($gid)) {
                $options["grouping$gid"] = $prefix.format_string($grouping->name).' ('.count($members).')';
            }
        }

        foreach ($groups as $gid => $group) {
            if ($members = groups_get_members($gid)) {
                if ($has_groupings) {
                    $prefix = $strgroup.': ';
                } else {
                    $prefix = '';
                }
                $options["group$gid"] = $prefix.format_string($group->name).' ('.count($members).')';
            }
        }

        parent::user_filter_select($filtername, $label, $advanced, '', $options, $default);
    }

    /**
     * setupForm
     *
     * @param xxx $mform (passed by reference)
     */
    function setupForm(&$mform)  {
        // only setup the select element if it has any options
        if (count($this->_options)) {
            parent::setupForm($mform);
        }
    }

    /**
     * get_sql_filter
     *
     * @param xxx $data
     * @return xxx
     */
    function get_sql_filter($data)  {
        global $DB, $reader;

        $filter = '';
        $params = array();

        if (($value = $data['value']) && ($operator = $data['operator'])) {

            $userids = array();
            if (substr($value, 0, 5)=='group') {
                if (substr($value, 5, 3)=='ing') {
                    $gids = groups_get_all_groupings($reader->course->id);
                    $gid = intval(substr($value, 8));
                    if ($gids && array_key_exists($gid, $gids) && ($members = groups_get_grouping_members($gid))) {
                        $userids = array_keys($members);
                    }
                } else {
                    $gids = groups_get_all_groups($reader->course->id);
                    $gid = intval(substr($value, 5));
                    if ($gids && array_key_exists($gid, $gids) && ($members = groups_get_members($gid))) {
                        $userids = array_keys($members);
                    }
                }
            }

            if (count($userids)) {
                switch($operator) {
                    case 1: // is equal to
                        list($filter, $params) = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED, '', true);
                        break;
                    case 2: // isn't equal to
                        list($filter, $params) = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED, '', false);
                        break;
                }
                if ($filter) {
                    $filter = 'id '.$filter;
                }
            }
        }

        // no userids found
        return array($filter, $params);
    }
}

