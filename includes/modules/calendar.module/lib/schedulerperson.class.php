<?php
/**
 * KvScheduler - Scheduler Person Generator
 * @package KvScheduler
 * @subpackage Modules.Lib
 * @author Greg McWhirter <gsmcwhirter@gmail.com>
 * @version 1.0
 */

/**
 * Scheduler person array generator
 * @package KvScheduler
 * @subpackage Modules.Lib
 */
abstract class SchedulerPerson{

  /**
   * Generate the basic array
   *
   * @param integer $start
   * @param integer $stop
   * @param integer $date
   * @param mixed $ophour_intervals
   * @return array
   */
  public static function newsp($start, $stop, $date, &$ophour_intervals){
    $self = array();
    if($start && $stop && $date && is_int($date) && is_int($start) && is_int($stop) && is_array($ophour_intervals) && count($ophour_intervals) > 0){
      $self[] = new SchedulerBlock(0);
      $duration = ($stop - $start);
      $t = (int)($duration / (30*60));
      for($i = 0; $i < $t; $i++){
        $self[] = new SchedulerBlock();
      }
    } else {
      throw new Exception("We are not operating on this date.");
    }

    return $self;
  }

  /**
   * Load an array with label data
   *
   * @param array $self
   * @param integer $date
   * @param integer $start
   * @return boolean true
   */
  public static function loadlabel(&$self, $date, $start){
    $max_i = count($self);
    for($i = 1; $i < $max_i; $i++){
      $self[$i]->set_status("L");
      $self[$i]->set_content(TOOLS::time_to_s(TOOLS::x_minutes_since((($i-1)*30), $start), true));
      $self[$i]->set_h24(TOOLS::time_to_s(TOOLS::x_minutes_since((($i-1)*30), $start)));
    }

    return true;
  }

  /**
   * Load data into the array
   *
   * @param array $self
   * @param integer $date
   * @param integer $start
   * @param integer $consultant Consultant ID
   * @param ophdata_struct $ophours
   * @param rchdata_struct $rchours
   * @param apdata_struct $appts
   * @return boolean true
   */
  public static function loaddata(&$self, $date, $start, $consultant, ophdata_struct &$ophours, rchdata_struct &$rchours, apdata_struct &$appts){

    $self[0]->set_content($rchours->consultants[$consultant]->realname);

    $d = $rchours->blocks[$consultant][$date];
    $offset_time = ($d[0] >= $ophours->blocks[$date]["start"]) ? 0 : abs($d[0] - $ophours->blocks[$date]["start"]);
    $postset_time = ($d[1] <= $ophours->blocks[$date]["stop"]) ? 0 : abs($d[1] - $ophours->blocks[$date]["stop"]);
    $nat_dur_time = ($d[1] - $d[0]);
    $dur_time = ($nat_dur_time - $offset_time - $postset_time);
    $dur = (int)($dur_time / 1800);
    $offset = (int)($offset_time / 1800);
    $postset = (int)(-1 * ($postset_time / 1800));

    $temp = ($d[0] - $ophours->blocks[$date]["start"]);
    $i_delta = ($temp > 0) ? ($temp / 1800) : 0;

    if(is_array($d) && array_key_exists(0, $d) && $d[0] && $d[2] != 0){
      for($i = 1 + $offset; $i < $dur + 1 + $postset; $i++){
        if(TOOLS::bit_read($d[2], $i)){
          $hour =& $rchours->things[$d[3][$i]];
          $status = "A";
          $span = 1;
          if($hour->htype == "delete"){
             $status = "K";
          } elseif($hour->oncall == "TRUE"){
            $status = "C";
            $t = 0;
            while(true){
              if(array_key_exists($i+$t, $d[3]) && $d[3][$i + $t] == $d[3][$i] && $i + $t <= 32){$t++;}
              else{break;}
            }
            $span = $t;
          }

          if(TOOLS::bit_read($ophours->blocks[$date]["intervals"][2], $i + $i_delta) && MyFunctions::datetime_in_hour($date, TOOLS::string_to_time($hour->starttime), $hour)){
              $self[$i + $i_delta]->set_status($status);
              $self[$i + $i_delta]->set_span($span);
          }
        }
      }
    }

    $d = $appts->blocks[$consultant][$date];
    $offset_time = ($d[0] >= $ophours->blocks[$date]["start"]) ? 0 : abs($d[0] - $ophours->blocks[$date]["start"]);
    $postset_time = ($d[1] <= $ophours->blocks[$date]["stop"]) ? 0 : abs($d[1] - $ophours->blocks[$date]["stop"]);
    $nat_dur_time = ($d[1] - $d[0]);
    $dur_time = ($nat_dur_time - $offset_time - $postset_time);
    $dur = (int)($dur_time / 1800);
    $offset = (int)($offset_time / 1800);
    $postset = (int)(-1 * ($postset_time / 1800));

    $temp = ($d[0] - $ophours->blocks[$date]["start"]);
    $i_delta = ($temp > 0) ? ($temp / 1800) : 0;

    if(is_array($d) && array_key_exists(0, $d) && $d[0] && $d[2] != 0){
      for($i = 1 + $offset; $i < $postset + 1 + $dur; $i++){
        if(TOOLS::bit_read($d[2], $i)){
          $appt = $appts->things[$d[3][$i]];
          $status = "B";
          $type = "a";
          $meta = $appt;
          $override = false;

          if($appt->special2 == "meeting"){
            $override = true;
          }

          if($appt->lockout != "FALSE"){
            $type = "l";
            $status = "O";
          }

          if($appt->special == "repeat_removal"){
            //$status = "A";
	          $status = null;
            $meta = null;
            $type = "h";
          }

          //if((!array_key_exists($i-1, $d[3]) || $d[3][$i-1] != $d[3][$i]) && $status != "A"){
          if((!array_key_exists($i-1, $d[3]) || $d[3][$i-1] != $d[3][$i]) && !is_null($status)){
            $t = 0;
            while(true){
              if(array_key_exists($i+$t, $d[3]) && $d[3][$i+$t] == $d[3][$i] && $i+$t <= 32){$t++;}
              else{break;}
            }
            $span = $t;

          } else{
            //if($status != "A") {
            if(!is_null($status)) {
              $status = "I";
            }

            $span = 1;
          }


          if((TOOLS::bit_read($ophours->blocks[$date]["intervals"][2], $i + $i_delta) || $override) && MyFunctions::datetime_in_appt($date, TOOLS::string_to_time($appt->starttime), $appt)){
            //if(($self[$i + $i_delta]->status == "A" || $self[$i + $i_delta]->status == "C" || $override)){
	          if(!is_null($status)){
              $self[$i + $i_delta]->set_status($status);
              $self[$i + $i_delta]->set_span($span);
              if(!is_null($meta)){
                $self[$i + $i_delta]->set_meta($meta);
              }
            }
          }
        }
      }
   }

   return true;
  }
}
?>
