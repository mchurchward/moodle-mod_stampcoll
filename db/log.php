<?php

/**
 * Definition of log events
 *
 * @package    mod
 * @subpackage stampcoll
 * @copyright  2011 David Mudrack
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$logs = array(
    array('module'=>'stampcoll', 'action'=>'view', 'mtable'=>'stampcoll', 'field'=>'name'),
    array('module'=>'stampcoll', 'action'=>'update', 'mtable'=>'stampcoll', 'field'=>'name'),
    array('module'=>'stampcoll', 'action'=>'add', 'mtable'=>'stampcoll', 'field'=>'name'),
    array('module'=>'stampcoll', 'action'=>'update stamp', 'mtable'=>'user', 'field'=>'concat(firstname, \' \', lastname)'),
    array('module'=>'stampcoll', 'action'=>'delete stamp', 'mtable'=>'user', 'field'=>'concat(firstname, \' \', lastname)'),
    );