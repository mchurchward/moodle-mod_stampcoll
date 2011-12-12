<?php  //$Id$

// This file keeps track of upgrades to
// the stampcoll module
//
// Sometimes, changes between versions involve
// alterations to database structures and other
// major things that may break installations.
//
// The upgrade function in this file will attempt
// to perform all the necessary actions to upgrade
// your older installtion to the current version.
//
// If there's something it cannot do itself, it
// will tell you what you need to do.
//
// The commands in here will all be database-neutral,
// using the functions defined in lib/ddllib.php

function xmldb_stampcoll_upgrade($oldversion=0) {

    global $CFG, $THEME, $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2008021900) {

    /// CONTRIB-288 Drop field "publish" from the table "stampcoll" and controll the access by capabilities
        if ($collections = $DB->get_records('stampcoll', array('publish' => '0'))) {
            // collections with publish set to STAMPCOLL_PUBLISH_NONE - prevent displaying from legacy:students
            foreach ($collections as $collection) {
                if ($cm = get_coursemodule_from_instance('stampcoll', $collection->id)) {
                    $context = get_context_instance(CONTEXT_MODULE, $cm->id);
                    // find all roles with legacy:student
                    if ($studentroles = get_roles_with_capability('moodle/legacy:student', CAP_ALLOW)) {
                        foreach ($studentroles as $studentrole) {
                            // prevent students from viewing own stamps
                            assign_capability('mod/stampcoll:viewownstamps', CAP_PREVENT, $studentrole->id, $context->id);
                        }
                    }
                }
            }
        }
        if ($collections = $DB->get_records('stampcoll', array('publish' => '2'))) {
            // collections with publish set to STAMPCOLL_PUBLISH_ALL - allow legacy:students to view others' stamps
            foreach ($collections as $collection) {
                if ($cm = get_coursemodule_from_instance('stampcoll', $collection->id)) {
                    $context = get_context_instance(CONTEXT_MODULE, $cm->id);
                    // find all roles with legacy:student
                    if ($studentroles = get_roles_with_capability('moodle/legacy:student', CAP_ALLOW)) {
                        foreach ($studentroles as $studentrole) {
                            // allow students to view others' stamps
                            assign_capability('mod/stampcoll:viewotherstamps', CAP_ALLOW, $studentrole->id, $context->id);
                        }
                    }
                }
            }
        }
        $table = new xmldb_table('stampcoll');
        $field = new xmldb_field('publish');
        $dbman->drop_field($table, $field);

    /// CONTRIB-289 Drop field "teachercancollect" in the table "mdl_stampcoll"
        if ($collections = $DB->get_records('stampcoll', array('teachercancollect' => '1'))) {
            // collections which allow teachers to collect stamps
            foreach ($collections as $collection) {
                if ($cm = get_coursemodule_from_instance('stampcoll', $collection->id)) {
                    $context = get_context_instance(CONTEXT_MODULE, $cm->id);
                    // find all roles with legacy:teacher and legacy:editingteacher
                    // and allow them to collect stamps
                    if ($teacherroles = get_roles_with_capability('moodle/legacy:teacher', CAP_ALLOW)) {
                        foreach ($teacherroles as $teacherrole) {
                            assign_capability('mod/stampcoll:collectstamps', CAP_ALLOW, $teacherrole->id, $context->id);
                        }
                    }
                    if ($teacherroles = get_roles_with_capability('moodle/legacy:editingteacher', CAP_ALLOW)) {
                        foreach ($teacherroles as $teacherrole) {
                            assign_capability('mod/stampcoll:collectstamps', CAP_ALLOW, $teacherrole->id, $context->id);
                        }
                    }
                }
            }
        }
        $table = new xmldb_table('stampcoll');
        $field = new xmldb_field('teachercancollect');
        $dbman->drop_field($table, $field);

        upgrade_mod_savepoint(true, 2008021900, 'stampcoll');
    }


    if ($oldversion < 2008022002) {

    /// Define field anonymous to be added to stampcoll
        $table = new xmldb_table('stampcoll');
        $field = new xmldb_field('anonymous');
        $field->set_attributes(XMLDB_TYPE_INTEGER, '1', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0', 'displayzero');
        $dbman->add_field($table, $field);

    /// Rename field comment on table stampcoll_stamps to text
        $table = new xmldb_table('stampcoll_stamps');
        $field = new xmldb_field('comment');
        $field->set_attributes(XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null, 'userid');
        $dbman->rename_field($table, $field, 'text');

    /// Define field giver to be added to stampcoll_stamps
        $table = new xmldb_table('stampcoll_stamps');
        $field = new xmldb_field('giver');
        $field->set_attributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0', 'userid');
        $dbman->add_field($table, $field);

    /// Define key mdl_stampcoll_id_idx (unique) to be dropped form stampcoll
        $table = new xmldb_table('stampcoll');
        $key = new xmldb_key('mdl_stampcoll_id_idx');
        $key->set_attributes(XMLDB_KEY_UNIQUE, array('id'));
        $dbman->drop_key($table, $key);

    /// Define index mdl_stampcoll_course_idx (not unique) to be dropped form stampcoll
        $table = new xmldb_table('stampcoll');
        $index = new xmldb_index('mdl_stampcoll_course_idx');
        $index->set_attributes(XMLDB_INDEX_NOTUNIQUE, array('course'));
        $dbman->drop_index($table, $index);

    /// Define index course (not unique) to be added to stampcoll
        $table = new xmldb_table('stampcoll');
        $index = new xmldb_index('course');
        $index->set_attributes(XMLDB_INDEX_NOTUNIQUE, array('course'));
        $dbman->add_index($table, $index);

    /// Define index mdl_stampcoll_stamps_userid_idx (not unique) to be dropped form stampcoll_stamps
        $table = new xmldb_table('stampcoll_stamps');
        $index = new xmldb_index('mdl_stampcoll_stamps_userid_idx');
        $index->set_attributes(XMLDB_INDEX_NOTUNIQUE, array('userid'));
        $dbman->drop_index($table, $index);

    /// Define index mdl_stampcoll_stamps_stampcollid_idx (not unique) to be dropped form stampcoll_stamps
        $table = new xmldb_table('stampcoll_stamps');
        $index = new xmldb_index('mdl_stampcoll_stamps_stampcollid_idx');
        $index->set_attributes(XMLDB_INDEX_NOTUNIQUE, array('stampcollid'));
        $dbman->drop_index($table, $index);

    /// Define index userid (not unique) to be added to stampcoll_stamps
        $table = new xmldb_table('stampcoll_stamps');
        $index = new xmldb_index('userid');
        $index->set_attributes(XMLDB_INDEX_NOTUNIQUE, array('userid'));

    /// Launch add index userid
        $dbman->add_index($table, $index);

    /// Define index giver (not unique) to be added to stampcoll_stamps
        $table = new xmldb_table('stampcoll_stamps');
        $index = new xmldb_index('giver');
        $index->set_attributes(XMLDB_INDEX_NOTUNIQUE, array('giver'));

    /// Launch add index giver
        $dbman->add_index($table, $index);

    /// Define key mdl_stampcoll_stamps_id_idx (unique) to be dropped form stampcoll_stamps
        $table = new xmldb_table('stampcoll_stamps');
        $key = new xmldb_key('mdl_stampcoll_stamps_id_idx');
        $key->set_attributes(XMLDB_KEY_UNIQUE, array('id'));

    /// Launch drop key mdl_stampcoll_stamps_id_idx
        $dbman->drop_key($table, $key);

    /// Define key stampcollid (foreign) to be added to stampcoll_stamps
        $table = new xmldb_table('stampcoll_stamps');
        $key = new xmldb_key('stampcollid');
        $key->set_attributes(XMLDB_KEY_FOREIGN, array('stampcollid'), 'stampcoll', array('id'));

    /// Launch add key stampcollid
        $dbman->add_key($table, $key);

        upgrade_mod_savepoint(true, 2008022002, 'stampcoll');
    }

    if ($oldversion < 2010080300) {

    /// Rename field text on table stampcoll to intro
        $table = new xmldb_table('stampcoll');
        $field = new xmldb_field('text', XMLDB_TYPE_TEXT, 'small', null, XMLDB_NOTNULL, null, null, 'name');

    /// Launch rename field description
        $dbman->rename_field($table, $field, 'intro');

    /// Define field introformat to be added to data
        $field = new xmldb_field('format', XMLDB_TYPE_INTEGER, '4', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0', 'intro');

    /// Launch rename field introformat
        $dbman->rename_field($table, $field, 'introformat');

        // conditionally migrate to html format in intro
        if ($CFG->texteditors !== 'textarea') {
            $rs = $DB->get_recordset('stampcoll', array('introformat'=>FORMAT_MOODLE), '', 'id,intro,introformat');
            foreach ($rs as $d) {
                $d->intro       = text_to_html($d->intro, false, false, true);
                $d->introformat = FORMAT_HTML;
                $DB->update_record('stampcoll', $d);
                upgrade_set_timeout();
            }
            $rs->close();
        }

    /// stampcoll savepoint reached
        upgrade_mod_savepoint(true, 2010080300, 'stampcoll');
    }

    return true;
}
