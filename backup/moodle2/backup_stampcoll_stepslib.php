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
 * @package moodlecore
 * @subpackage backup-moodle2
 * @copyright 2010 onwards Eloy Lafuente (stronk7) {@link http://stronk7.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Define all the backup steps that will be used by the backup_stampcoll_activity_task
 */

/**
 * Define the complete stampcoll structure for backup, with file and id annotations
 */
class backup_stampcoll_activity_structure_step extends backup_activity_structure_step {

    protected function define_structure() {

        // To know if we are including userinfo
        $userinfo = $this->get_setting_value('userinfo');

        // Define each element separated
        $stampcoll = new backup_nested_element('stampcoll', array('id'), array(
            'name', 'intro', 'introformat', 'image',
            'timemodified', 'displayzero', 'anonymous'));

        $stamps = new backup_nested_element('stamps');

        $stamp = new backup_nested_element('stamp', array('id'), array(
            'userid', 'giver', 'text', 'timemodified'));

        // Build the tree
        $stampcoll->add_child($stamps);
        $stamps->add_child($stamp);

        // Define sources
        $stampcoll->set_source_table('stampcoll', array('id' => backup::VAR_ACTIVITYID));

        // All the rest of elements only happen if we are including user info
        if ($userinfo) {
            $stamp->set_source_table('stampcoll_stamps', array('stampcollid' => '../../id'));
        }

        // Define id annotations
        $stamp->annotate_ids('user', 'userid');
        $stamp->annotate_ids('user', 'giver');

        // Define file annotations
        $stampcoll->annotate_files('mod_stampcoll', 'intro', null); // This file area hasn't itemid

        // Return the root element (stampcoll), wrapped into standard activity structure
        return $this->prepare_activity_structure($stampcoll);
    }
}
