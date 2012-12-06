<?php

    require_once("../../config.php");
    require_once("lib.php");

    $id = required_param('id',PARAM_INT);   // course

    //get the $cm id, and verify that it is correct
    if(!$cm = get_coursemodule_from_id('choice', $id)){
        error("Course module ID was incorrect");      
    }      
    
    //get the module context
    $context = get_context_instance(CONTEXT_MODULE, $cm->id);
    
    
    $PAGE->set_url('/mod/choice/index.php', array('id'=>$id));

    if (!$course = $DB->get_record('course', array('id'=>$id))) {
        print_error('invalidcourseid');
    }

    require_course_login($course);
    $PAGE->set_pagelayout('incourse');

    add_to_log($course->id, "choice", "view all", "index.php?id=$course->id", "");

    $strchoice = get_string("modulename", "choice");
    $strchoices = get_string("modulenameplural", "choice");
    $strsectionname  = get_string('sectionname', 'format_'.$course->format);
    $PAGE->set_title($strchoices);
    $PAGE->set_heading($course->fullname);
    $PAGE->navbar->add($strchoices);
    echo $OUTPUT->header();

    if (! $choices = get_all_instances_in_course("choice", $course)) {
        notice(get_string('thereareno', 'moodle', $strchoices), "../../course/view.php?id=$course->id");
    }

    $usesections = course_format_uses_sections($course->format);
    if ($usesections) {
        $sections = get_all_sections($course->id);
    }

    $sql = "SELECT cha.*
              FROM {choice} ch, {choice_answers} cha
             WHERE cha.choiceid = ch.id AND
                   ch.course = ? AND cha.userid = ?";

    $answers = array () ;
    if (isloggedin() and !isguestuser() and $allanswers = $DB->get_records_sql($sql, array($course->id, $USER->id))) {
        foreach ($allanswers as $aa) {
            $answers[$aa->choiceid] = $aa;
        }
        unset($allanswers);
    }


    $timenow = time();

    $table = new html_table();
    
    //check the permission
    if(has_capability('mod/choice:viewextra', $context)){
        if ($usesections) {
            $table->head  = array ($strsectionname, get_string("question"), get_string("answer"), "Description", "Time Modified", "Time Open", "Time Close", "Show Results?", "Limit Answers?", "Allow Update?", "Show Unanswered?", "Display");
            $table->align = array ("center", "left", "left", "left", "left", "left", "left", "left", "left", "left", "left", "left");
        } else {
            $table->head  = array (get_string("question"), get_string("answer"), "Description", "Time Modified", "Time Open", "Time Close", "Show Results?", "Limit Answers?", "Allow Update?", "Show Unanswered?", "Display");
            $table->align = array ("left", "left", "left","left", "left", "left", "left", "left", "left", "left", "left");
        }
    }else{
        if ($usesections) {
            $table->head  = array ($strsectionname, get_string("question"), get_string("answer"));
            $table->align = array ("center", "left", "left");
        } else {
            $table->head  = array (get_string("question"), get_string("answer"));
            $table->align = array ("left", "left");
        }
    }

    $currentsection = "";

    foreach ($choices as $choice) {
        if (!empty($answers[$choice->id])) {
            $answer = $answers[$choice->id];
        } else {
            $answer = "";
        }
        if (!empty($answer->optionid)) {
            $aa = format_string(choice_get_option_text($choice, $answer->optionid));
            
            //Date response
            $timemodified = userdate($answer->timemodified);    
            
        } else {
            $aa = "";
            $timemodified = "";

        }
        
        //MORE CHOICE OPTIONS
            //Description of choice
            $intro = $choice->intro;

            //Closing and opening date if it is specified
            if($choice->timeopen == 0)
            {
                $timeopen = "";
                $timeclose = "";
            } else {
                $timeopen = userdate($choice->timeopen);
                $timeclose = userdate($choice->timeclose);
            }
            
            if($choice->showresults)
            {
                $showresult = "<input type='checkbox' checked='checked' DISABLED>"; 
            }
            else{
                $showresult = "<input type='checkbox' DISABLED>";
            }
            
            if($choice->limitanswers)
            {
                $limitanswers = "<input type='checkbox' checked='checked' DISABLED>"; 
            }
            else{
                $limitanswers = "<input type='checkbox' DISABLED>";
            }
            
            if($choice->allowupdate)
            {
                $allowupdate = "<input type='checkbox' checked='checked' DISABLED>"; 
            }
            else{
                $allowupdate = "<input type='checkbox' DISABLED>";
            }
            
            if($choice->showunanswered)
            {
                $showunanswered = "<input type='checkbox' checked='checked' DISABLED>"; 
            }
            else{
                $showunanswered = "<input type='checkbox' DISABLED>";
            }
            
            if($choice->display)
            {
                $display = "Vertically"; 
            }
            else{
                $display = "Horizontally";
            }
            
        
        if ($usesections) {
            $printsection = "";
            if ($choice->section !== $currentsection) {
                if ($choice->section) {
                    $printsection = get_section_name($course, $sections[$choice->section]);
                }
                if ($currentsection !== "") {
                    $table->data[] = 'hr';
                }
                $currentsection = $choice->section;
            }
        }

        //Calculate the href
        if (!$choice->visible) {
            //Show dimmed if the mod is hidden
            $tt_href = "<a class=\"dimmed\" href=\"view.php?id=$choice->coursemodule\">".format_string($choice->name,true)."</a>";
        } else {
            //Show normal if the mod is visible
            $tt_href = "<a href=\"view.php?id=$choice->coursemodule\">".format_string($choice->name,true)."</a>";
        }
        
        //check the permission
        if(has_capability('mod/choice:viewextracol', $context)){
            if ($usesections) {
                $table->data[] = array ($printsection, $tt_href, $aa, $intro, $timemodified, $timeopen, $timeclose, $showresult, $limitanswers, $allowupdate, $showunanswered, $display);
            } else {
                $table->data[] = array ($tt_href, $aa, $intro, $timemodified, $timeopen, $timeclose, $showresult, $limitanswers, $allowupdate, $showunanswered, $display);
            }
        }else{
            if ($usesections) {
                $table->data[] = array ($printsection, $tt_href, $aa);
            } else {
                $table->data[] = array ($tt_href, $aa);
            }
        }
    }
    echo "<br />";
    echo html_writer::table($table);

    echo $OUTPUT->footer();


