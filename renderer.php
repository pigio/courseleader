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
 * YU Kaltura My Media renderer class.
 *
 * @package    local_yumymedia
 * @copyright  (C) 2016-2017 Yamaguchi University <ghcc@mlex.cc.yamaguchi-u.ac.jp>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();


/**
 * Renderer class of local_yumymedia
 * @package local_yumymedia
 * @copyright  (C) 2016-2017 Yamaguchi University <gh-cc@mlex.cc.yamaguchi-u.ac.jp>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_courseleaderboard_renderer extends plugin_renderer_base {


//Definisce il numero di utenti da far vedere nella leaderboard
static $users_to_show = 50;

    /**
     * This function retursn ready status of flavors.
     * @param object $connection - Kaltura connection object.
     * @param string $entryid - id of kaltura media entry.
     * @return bool - If all flavors are ready, return true. Otherwise, return false.
     */
    public function all_courses() {
        
        global $DB;
        $output = '';
        $query = "SELECT id, fullname, shortname from {course}";
        $courselist = $DB->get_records_sql($query);
        foreach ($courselist as $course) {
            $output.= $course->fullname;
        }

        $output .= html_writer::start_tag('div');
        $output .= html_writer::end_tag('div');

        echo $output;
    }

 public function user_enrolled_courses($id) {
        global $USER, $CFG, $DB, $COURSE;
        require_once("{$CFG->dirroot}/course/lib.php");
        require_once("{$CFG->libdir}/completionlib.php");
        require_once("$CFG->dirroot/mod/scorm/locallib.php");
        
        $output = '';
        $course = $COURSE;
        //recupero tutti i miei corsi
       /* $courses = enrol_get_my_courses();
        if (empty($courses)) {
            return array();
        }
*/
        //array dei risultati
        $alluser = array();
        $participants = array();
        $leaderboard  = array(); 
        $report = array();
        $totalsum = 0;
        $data= array();

      //foreach ($courses as $course) {
            //recupero l'elenco degli studenti 
            $context = get_context_instance(CONTEXT_COURSE, $id);
            $students = get_role_users(5 , $context);
            $obj = NULL;
            foreach ($students as $student) {
                $data = array();
                if (count($participants)) {
                    $find = false;

                    foreach ($participants as $part) {
                       if($part['student_id'] == $student->id) {
                         $find = true;
                         break;
                        
                        }
                    }
                    if(!$find) {
                        $data['student_id'] = $student->id;
                        $data['student_username'] = $student->username;
                    }

                } else {

                    $data['student_id'] = $student->id;
                    $data['student_username'] = $student->username;
                }


                
                //controllo se l'utente ha terminato il corso
                $cinfo = new completion_info($COURSE);

                $iscomplete = $cinfo->is_course_complete($student->id);
                $points = 0;
                $time;
                if (!$iscomplete) 
                    { 
                        //$data['leaderboard'] = array();
                        //array_push($data['leaderboard'], array('course_id'=>$course->id, 'course_name' =>$course->shortname, 'points'=>$points));
                        $obj = (object) array('course_id'=>$course->id, 'course_name' =>$course->shortname, 'points'=>$points, 'time'=>$time);
                    }
                else {
                    
                    //recupero le attività
                    $coursemodinfo = new stdClass();
                    $coursemodinfo->modinfo = get_array_of_activities($course->id);

                    ///TODO controllo se ci sono contenuti
                    //var_dump($coursemodinfo);

                    foreach ($coursemodinfo->modinfo as $activity) {
                        $activityItem = new stdClass();
                        $activityItem = $activity;
                        //var_dump($activityItem);
                       
                        if($activityItem->mod == 'scorm') {

                            //MODIFICHE ottengo il tempo
                            $scorm = $DB->get_record('scorm', array('id' => $activityItem->id), '*', MUST_EXIST);
                            if ($scoes = $DB->get_records('scorm_scoes', array('scorm' => $scorm->id), 'sortorder, id')) {
                                foreach ($scoes as $sco) {
                                    if ($trackdata = scorm_get_tracks($sco->id, $student->id)) {
                                        //$time = scorm_format_duration($trackdata->total_time);
                                        $time = $trackdata->total_time;
                                        //var_dump($trackdata->total_time);
                                        //var_dump($time);
                                    }
                                }
                            }

                            //////FINE MODIFICHE

                           
                            //TODO controllo se ottengo un punteggio
                            $grade = scorm_grade_user_attempt($activityItem, $student->id);
                            
                            $points += $grade;

                        }

                    }

                        //$data['leaderboard'] = array('course_id'=>$course->id, 'course_name' =>$course->shortname, 'points'=>$points);
                        $obj = (object) array('course_id'=>$course->id, 'course_name' =>$course->shortname, 'points'=>$points, 'time'=>$time);
                        

                }

                $check = false;
                foreach ($participants as $p) {
                    if($p['student_id'] == $student->id) {
                        $check = true;
                        
                        break;
                    }
                }
                if(!$check) {
                    $data['leaderboard'] = array();
                    array_push($data['leaderboard'], $obj);
                    array_push($participants, $data);

                    /* CORRETTO array_push($data, $obj);
                    array_push($participants, $data);
                    */
                }else
                {   

                   foreach ($participants as $key => $item)
                   {
                        //CORRETTO if($participants[$key]['student_id']==$student->id) {
                        if($participants[$key]['student_id']==$student->id) {

                            $tmp = array();
                            $tmp = $participants[$key]['leaderboard'];
                            array_push($tmp, $obj);
                            $participants[$key]['leaderboard'] =  $tmp;
                            //CORRETTO array_push($participants[$key], $obj);

                        }
                        
                   }

                   
                }
                
                
            }

    
        //}
         

        //var_dump($participants);
        return $participants;

    }

    public function get_total_score($values){
        
        
        $total_score = 0;
       
        foreach ($values['data'] as $value) {
         
           foreach ($value as $data) {
                
                if (is_numeric($data['leaderboard']['points'])) {
               
                    $total_score += $data['leaderboard']['points'];

                }

           }

        }
        //echo $total_score."\n";
        return $total_score;
    }


    public function get_my_enrolled_course_name() {
        global $CFG, $USER;
        require_once("{$CFG->dirroot}/course/lib.php");
        require_once("{$CFG->libdir}/completionlib.php");

         //recupero tutti i miei corsi
        $courses = enrol_get_my_courses();

        if (empty($courses)) {
            return array();
        }
        $titles = array();

        foreach ($courses as $course) {
            /*$cinfo = new completion_info($course);
            $iscomplete = $cinfo->is_course_complete($USER->id);
*/
    //        if ($iscomplete) {
                array_push($titles, $course->shortname); 
   //         }
            
        }

        return $titles;
    }


    public function cmp($key1, $key2){
        return function ($a, $b) use ($key1,  $key2) {
           
            if($a[$key1] == $b[$key1]) {
               
                return ($a[$key2] < $b[$key2]) ? -1 : 1;
               //return 0;
                
            }
            return ($a[$key1] > $b[$key1]) ? -1 : 1;
         };
    }


   public function sort_total_score($data){
        
        usort($data, $this->cmp('total_score', 'total_time'));
        return $data;
    }

    public function my_rank($data) {
        global $USER;

        $key = array_search($USER->id, array_column($data, 'student_id'));

        $output = html_writer::start_tag('h5', array('class'=>'text-center py-3', 'style'=>'color:#fff'));
        $output.=get_string('your_position', 'local_leaderboard', $key+1);
        $output.= html_writer::end_tag('h5');

        return $output;
    }

    public function my_rank_row_data($data) {
        global $USER;

        $key = array_search($USER->id, array_column($data, 'student_id'));

        return $data[$key];
    }


     public function my_leaderboard() {
        global $CFG, $DB, $OUTPUT,  $USER, $COURSE;
        require_once($CFG->libdir.'/filelib.php');
        require_once($CFG->libdir."/badgeslib.php");

        $output = '';
        $output.= html_writer::start_tag('h2', array('class'=>'text-center text-uppercase leaderboard-title pt-3'));
        $output.= $COURSE->shortname." ".get_string('heading_courseleaderboard', 'local_courseleaderboard');
        $output.= html_writer::end_tag('h2');

        //$titles = $this->get_my_enrolled_course_name();

        //24-10
       //24-10$table = new html_table();
        //24-10$table->attributes['class'] = 'table leaderboard-table';
        
        //$arrayhead = array('student', 'total_score');
       // $r = array_merge(array('-',get_string('student', 'local_courseleaderboard')), $titles, array(get_string('total_score_course', 'local_courseleaderboard'))); 

       //24-10$r = array_merge(array('-',get_string('student', 'local_courseleaderboard')), array(get_string('total_score_course', 'local_courseleaderboard'))); 

        //24-10$table->head = $r;
        $results = $this->user_enrolled_courses($COURSE->id);

        $data_leaderboard = array();
       
        foreach ($results as $key => $value) {


           $data = array();
           $data = array();
           $data['rank'] = 1;
           $data['username'] = $results[$key]['student_username'];
           $data['student_id'] = $results[$key]['student_id'];
           $total_score = 0; 
           $total_time = new DateTime('00:00:00.000000');
           //$vbnm = 0;
           foreach ($results[$key]['leaderboard']as $k => $v) {
                    $tmpObj = new stdClass();
                    $tmpObj = $results[$key]['leaderboard'][$k];
                    $total_score += $tmpObj->points;

                    list($hours, $minutes, $seconds, $milliseconds) = sscanf($tmpObj->time, '%d:%d:%d.%d');
                    $interval = new DateInterval(sprintf('PT%dH%dM%dS', $hours, $minutes, $seconds));
                   
                    $total_time->add($interval);
                    
                    $data['games'][] =  $tmpObj->points;
                }
            
            //var_dump($total_time);
             $data['total_score'] = $total_score;
             $data['total_time'] = $total_time;

            $data_leaderboard [] = $data;
        }
       

        $data_leaderboard = $this->sort_total_score($data_leaderboard);
        $data_leaderboard = $this->set_rank_row_data ($data_leaderboard);

         ///***** --24/10 ***/////
        $output.= html_writer::start_div('container');
       

        ///***** --24/10  ***/////

        //ottengo la mia posizione e la stampo
        $output.= $this->my_rank($data_leaderboard);


        //stampo le righe della tabella
         $showing = 0;
         $visible_at_first_time = false;
        foreach ($data_leaderboard as $l) {
               if($showing == local_courseleaderboard_renderer::$users_to_show ) { 
                    /* 24-10 $empty_row = array();
                    $empty_row [] = '...'; //image
                    $empty_row [] = '...'; //username
                    foreach ($l['games'] as $key => $value) {
                         $empty_row[] = "..";
                    }
                    $empty_row [] = '...'; //total score
                    $table->data[] = new html_table_row($empty_row); */

                    //24-10
                    $output.= html_writer::start_div('row leaderboard-row');
                    $output.= html_writer::start_div('col-md-12 rank text-center');
                    $output.= "...";
                    $output.= html_writer::end_div();
                    $output.= html_writer::end_div();
                    break; 
                }
               else { $showing ++;}

            //24-10 $data_table =  array();

            $output.= html_writer::start_div('row leaderboard-row');
            $output.= html_writer::start_div('col-md-1 rank text-right');
            $output.= $l['rank'];
            $output.= html_writer::end_div();
            $output.= html_writer::start_div('col-md-3 first');

            //Ottengo immagine utente
            //24-10 $userpic = $DB->get_record('user', array('id' => $l['student_id']));
            //24-10 $userpicture = $OUTPUT->user_picture($userpic);
            //24-10 $data_table[] = $userpicture;

            //24-10 $data_table[] = $l['username'];

            /*24-10
            foreach ($l['games'] as $key => $value) {
                $data_table[] = $l['games'][$key];
            }
            */
           // $data_table[] = $l['total_score'];


            //24-10 $row = new html_table_row($data_table); 
            

            /* 24-10 if($l['student_id'] == $USER->id) {
                $row->attributes['class'] .= 'my_rank';
                $visible_at_first_time = true;
            }

            $table->data[] = $row;
            */

            //ottengo badge
            if($badges = badges_get_user_badges($l['student_id'])) {
                //var_dump($badges);
                $output .= $this->printBadgeItem($badges);


            } else {
                //Ottengo immagine utente
                $userpic = $DB->get_record('user', array('id' => $l['student_id']));
                $userpicture = $OUTPUT->user_picture($userpic);
                $output.= $userpicture;
            }

            $output.= $l['username'];

            $output.= html_writer::end_div();
            $output.= html_writer::start_div('col-md-6 bar-container');
            $output.= html_writer::start_div("progress");
            $percentage = ($l['total_score']/2000)*100;
            $output.= html_writer::start_div("progress-bar",['role'=>'progressbar', 'style'=>'width:0%;', 'data-value'=>$percentage]);
            //chiusura progress-bar
            $output.= html_writer::end_div();
            //chiusura progress
            $output.= html_writer::end_div();
            //chiusura col
            $output.= html_writer::end_div();

            $output.= html_writer::start_div('col-md-2 last text-center');
            $output.= $l['total_score'];
            $output.= html_writer::end_div();

            //$data_table[] = $l['rank'];
            //$data_table[] = $userpicture;

            //$data_table[] = $l['username'];

            foreach ($l['games'] as $key => $value) {
              //  $data_table[] = $l['games'][$key];
            }
            //$data_table[] = $l['total_score'];


            //$row = new html_table_row($data_table); 
            

            if($l['student_id'] == $USER->id) {
            //    $row->attributes['class'] .= 'my_rank';
                $visible_at_first_time = true;
            }

            //$table->data[] = $row;

            $output.= html_writer::end_div();
        }
        if(!$visible_at_first_time) {
            //24-10 $data_table =  array();
            $my_data =  $this->my_rank_row_data($data_leaderboard);

            //24-10$userpic = $DB->get_record('user', array('id' => $USER->id));
            //24-10$userpicture = $OUTPUT->user_picture($userpic);
            //24-10$data_table[] = $userpicture;

            //24-10$data_table[] = $my_data['username'];
            /*24-10foreach ($my_data['games'] as $key => $value) {
                $data_table[] = $my_data['games'][$key];
            }*/
           // $data_table[] = $my_data['total_score'];

            //24-10 $row = new html_table_row($data_table);
            //24-10 $row->attributes['class'] .= 'my_rank';
            //24-10 $table->data[] = $row;

            $output.= html_writer::start_div('row leaderboard-row');
            $output.= html_writer::start_div('col-md-1 rank text-right');
            $output.= $my_data['rank'];
            $output.= html_writer::end_div();
            $output.= html_writer::start_div('col-md-3 first');

            //ottengo badge
            if($badges = badges_get_user_badges($my_data['student_id'])) {
                //var_dump($badges);
                $output .= $this->printBadgeItem($badges);


            } else {
                //Ottengo immagine utente
                $userpic = $DB->get_record('user', array('id' => $my_data['student_id']));
                $userpicture = $OUTPUT->user_picture($userpic);
                $output.= $userpicture;
            }
            
            $output.= $my_data['username'];

            $output.= html_writer::end_div();
            $output.= html_writer::start_div('col-md-6 bar-container');
            $output.= html_writer::start_div("progress");
            $percentage = ($my_data['total_score']/2000)*100;
            $output.= html_writer::start_div("progress-bar",['role'=>'progressbar', 'style'=>'width:0%;', 'data-value'=>$percentage]);
            //chiusura progress-bar
            $output.= html_writer::end_div();
            //chiusura progress
            $output.= html_writer::end_div();
            //chiusura col
            $output.= html_writer::end_div();

            $output.= html_writer::start_div('col-md-2 last text-center');
            $output.= $my_data['total_score'];
            $output.= html_writer::end_div();
        }

       
            ///***** --24/10 ***/////
            //$output.= html_writer::table($table);
             $output.= html_writer::end_div();

        return $output;
     }
   
   public function printBadgeItem($badges) {
    global $CFG;
    require_once("{$CFG->libdir}/badgeslib.php");

    $count = 0;
    foreach ($badges as $badge) {
        $count++;
        $context = ($badge->type == BADGE_TYPE_SITE) ? context_system::instance() : context_course::instance($badge->courseid);
        $imageurl = moodle_url::make_pluginfile_url($context->id, 'badges', 'badgeimage', $badge->id, '/', 'f1', false);
        
        $image = html_writer::empty_tag('img', array('src' => $imageurl, 'class' => 'userpicture', 'width'=> '35', 'height'=>'35' ,'style'=>'background: rgb(31, 71, 161);' ));
            

        if($count == 1) {
            break;
        }

       
     } 
     return $image;

    }
    

    public function set_rank_row_data(& $data) {
        
        $points = null;
        $rank = 1;

        foreach ($data as $key => $value) {
            $pointsTmp = $data[$key]['total_score'];

            if(!isset($points)) {
                $points = $data[$key]['total_score'];

            }
            if ( $pointsTmp  == $points ) {
                
                 $data[$key]['rank']  = $rank;
                 //continue;
             } 

            if ( $pointsTmp  < $points ) { 
                $rank++;
                $points = $data[$key]['total_score'];
                $data[$key]['rank']  = $rank;

             }
        }
      
        return $data;
    }

}
