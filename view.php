<?php  // $Id$

    require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
    require_once(dirname(__FILE__).'/lib.php');

    $id = required_param('id',PARAM_INT);               // Course Module ID
    $view = optional_param('view', 'all', PARAM_ALPHA); // Stamps to display
    $page = optional_param('page', 0, PARAM_INT);       // Page of the batch view

    if (! $cm = get_coursemodule_from_id('stampcoll', $id)) {
        error("Course Module ID was incorrect");
    }

    if (! $course = $DB->get_record("course", array("id" => $cm->course))) {
        error("Course is misconfigured");
    }

    $params = array();
    $params['id'] = $id;
    if ($view) {
        $params['view'] = $view;
    }
    if ($page) {
        $params['page'] = $page;
    }
    $PAGE->set_url('/mod/stampcoll/view.php', $params);

    require_course_login($course, true, $cm);

    if (!$stampcoll = stampcoll_get_stampcoll($cm->instance)) {
        error("Course module is incorrect");
    }

/// Get capabilities
    $context = get_context_instance(CONTEXT_MODULE, $cm->id);
    include(dirname(__FILE__).'/caps.php');

    $PAGE->set_title(format_string($stampcoll->name));
    $PAGE->set_heading(format_string($course->fullname));
    echo $OUTPUT->header();

    /// If it's hidden then don't show anything
    if (empty($cm->visible) && !has_capability('moodle/course:viewhiddenactivities', $context)) {
        notice(get_string("activityiscurrentlyhidden"));
    }

    $strstampcoll = get_string("modulename", "stampcoll");
    $strstampcolls = get_string("modulenameplural", "stampcoll");

    add_to_log($course->id, "stampcoll", "view", "view.php?id=$cm->id", $stampcoll->id, $cm->id);

    if ($cap_viewonlyownstamps && $view == 'all') {
        $view = 'own';
    }

/// Print the tabs
    switch ($view) {
        case 'own': $currenttab = 'viewown'; break;
        default: $currenttab = 'view'; break;
    }
    include(dirname(__FILE__).'/tabs.php');

/// Print activity introduction (description)
    if (in_array($currenttab, array('view', 'viewown')) and (!empty($stampcoll->intro))) {
        echo $OUTPUT->box(format_text($stampcoll->intro), 'generalbox', 'intro');
    }

    if (!$cap_viewsomestamps) {
        notice(get_string('notallowedtoviewstamps', 'stampcoll'), $CFG->wwwroot."/course/view.php?id=$course->id");
    }

    $allstamps = stampcoll_get_stamps($stampcoll->id)
        or $allstamps = array();

    if (empty($allstamps) && !$stampcoll->displayzero) {
        notice(get_string('nostampsincollection', 'stampcoll'), $CFG->wwwroot."/course/view.php?id=$course->id");
    }

/// Re-sort all stamps into "by-user-array"
    $userstamps = array();
    foreach ($allstamps as $s) {
        if (($s->userid == $USER->id) && (!$cap_viewownstamps)) {
            continue;
        }
        if (($s->userid != $USER->id) && (!$cap_viewotherstamps)) {
            continue;
        }
        $userstamps[$s->userid][] = $s;
    }
    unset($allstamps);
    unset($s);

    if (($cap_viewonlyownstamps) || (($cap_viewsomestamps) && ($view == 'own')))  {
        /// Display a page with own stamps only
        if (isset($userstamps[$USER->id])) {
            $mystamps = $userstamps[$USER->id];
        } else {
            $mystamps = array();
        }
        unset($userstamps);
        $stampimages = '';
        foreach ($mystamps as $s) {
            $stampimages .= stampcoll_stamp($s, $stampcoll->image);
        }
        unset($s);

        echo $OUTPUT->box_start();
        echo $OUTPUT->heading(get_string('numberofyourstamps', 'stampcoll', count($mystamps)));
        echo '<div class="stamppictures">'.$stampimages.'</div>';
        echo $OUTPUT->box_end();

    } elseif ($cap_viewotherstamps) {
        /// Display a table of users and their stamps
        groups_print_activity_menu($cm, $CFG->wwwroot.'/mod/stampcol/view.php?page='.$page.'&amp;id='.$cm->id);
        $currentgroup = groups_get_activity_group($cm);
        $users = stampcoll_get_users_can_collect($cm, $context, $currentgroup);
        if (!$users) {
            echo $OUTPUT->heading(get_string("nousersyet"));
        }

        /// First we check to see if the form has just been submitted
        /// to request user_preference updates
        if (isset($_POST['updatepref'])){
            $perpage = optional_param('perpage', STAMPCOLL_USERS_PER_PAGE, PARAM_INT);
            $perpage = ($perpage <= 0) ? STAMPCOLL_USERS_PER_PAGE : $perpage ;
            set_user_preference('stampcoll_perpage', $perpage);
        }

        /// Next we get perpage param from database
        $perpage    = get_user_preferences('stampcoll_perpage', STAMPCOLL_USERS_PER_PAGE);

        $tablecolumns = array('picture', 'fullname', 'count', 'stamps');
        $tableheaders = array('', get_string('fullname'), get_string('numberofstamps', 'stampcoll'), '');

        require_once($CFG->libdir.'/tablelib.php');

        $table = new flexible_table('mod-stampcoll-stamps');

        $table->define_columns($tablecolumns);
        $table->define_headers($tableheaders);
        $table->define_baseurl($CFG->wwwroot.'/mod/stampcoll/view.php?id='.$cm->id.'&amp;currentgroup='.$currentgroup);

        $table->sortable(true);
        $table->collapsible(false);
        $table->initialbars(true);

        $table->column_class('picture', 'picture');
        $table->column_class('fullname', 'fullname');
        $table->column_class('count', 'count');
        $table->column_class('stamps', 'stamps');
        $table->column_style('stamps', 'width', '50%');

        $table->set_attribute('cellspacing', '0');
        $table->set_attribute('id', 'stamps');
        $table->set_attribute('class', 'stamps');
        $table->set_attribute('width', '90%');
        $table->set_attribute('align', 'center');

        $table->setup();

        if (empty($users)) {
            echo $OUTPUT->heading(get_string('nousers','stampcoll'));
            return true;
        }

    /// Construct the SQL

        if ($where = $table->get_sql_where()) {
            $where .= ' AND ';
        }

        if ($sort = $table->get_sql_sort()) {
            $sort = ' ORDER BY '.$sort;
        }

        $select = 'SELECT u.id, u.firstname, u.lastname, u.picture, COUNT(s.id) AS count ';
        $params['stampcollid'] = $stampcoll->id;
        list($uids, $params) = $DB->get_in_or_equal(array_keys($users));
        $sql = "FROM {user} AS u ".
               "LEFT JOIN {stampcoll_stamps} s ON u.id = s.userid AND s.stampcollid = :stampcollid ".
               "WHERE $where u.id $uids ".
               "GROUP BY u.id, u.firstname, u.lastname, u.picture ";

        if (!$stampcoll->displayzero) {
            $sql .= 'HAVING COUNT(s.id) > 0 ';
        }

        // First query with not limits to get the number of returned rows
        $ausers = $DB->get_records_sql($select.$sql.$sort, $params);
        $table->pagesize($perpage, count($ausers));
        // Second query with pagination limits
        $ausers = $DB->get_records_sql($select.$sql.$sort, $params, $table->get_page_start(), $table->get_page_size());
        foreach ($ausers as $auser) {
            $picture = $OUTPUT->user_picture($auser->id, $course->id, $auser->picture, false, true);
            $fullname = fullname($auser);
            $count = $auser->count;
            $stamps = '';
            if (isset($userstamps[$auser->id])) {
                foreach ($userstamps[$auser->id] as $s) {
                    $stamps .= stampcoll_stamp($s, $stampcoll->image);
                }
                unset($s);
            }
            $row = array($picture, $fullname, $count, $stamps);
            $table->add_data($row);
        }
        $table->print_html();  /// Print the whole table

        /// Mini form for setting user preference
        echo '<br />';
        echo '<form name="options" action="view.php?id='.$cm->id.'" method="post">';
        echo '<input type="hidden" id="updatepref" name="updatepref" value="1" />';
        echo '<table id="optiontable" align="center">';
        echo '<tr align="right"><td>';
        echo '<label for="perpage">'.get_string('studentsperpage','stampcoll').'</label>';
        echo ':</td>';
        echo '<td align="left">';
        echo '<input type="text" id="perpage" name="perpage" size="1" value="'.$perpage.'" />';
        helpbutton('pagesize', get_string('studentsperpage','stampcoll'), 'stampcoll');
        echo '</td></tr>';
        echo '<tr>';
        echo '<td colspan="2" align="right">';
        echo '<input type="submit" value="'.get_string('savepreferences').'" />';
        echo '</td></tr></table>';
        echo '</form>';
        ///End of mini form
    }

    echo $OUTPUT->footer($course);
