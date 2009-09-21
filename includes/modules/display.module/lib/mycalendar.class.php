<?php
/**
 * KvScheduler - Consultant Calendar Factory
 * @package KvScheduler
 * @subpackage Modules.Lib
 * @author Greg McWhirter <gsmcwhirter@gmail.com>
 * @version 1.0
 */

/**
 * Generates the consultant main display
 *
 * @package KvScheduler
 * @subpackage Modules.Lib
 */
abstract class MyCalendar extends kvframework_base {

  /**
   * Main factory
   *
   * @param integer $rcid
   * @param integer $date
   * @param integer $numweeks
   * @return array
   */
  public static function newcal($rcid, $date, $numweeks, $admin = false){
    if(is_null($rcid)){
      throw new Exception("No consultant requested.");
    }
    $weekstart = TOOLS::x_days_since(-1 * TOOLS::wday_for($date), $date);
    $weekstop = TOOLS::x_days_since(($numweeks * 7) - 1 , $weekstart);
    $week_data = array();
    $self = array();

    // get consultant record?
    $dtds = TOOLS::date_to_s(TOOLS::date_today());
    $sql = "SELECT *, appointments.id as appointment_id, consultantappts.rapid as consultantappt_id, locations.id as location_id, loczones.id as loczone_id, locations.name as location_name, loczones.name as loczone_name FROM appointments, consultantappts, locations, loczones WHERE consultantappts.consultant_id = '$rcid' AND consultantappts.appointment_id = appointments.id AND locations.id = appointments.location_id AND loczones.id = locations.loczone_id AND appointments.stopdate >= '$dtds' ORDER BY appointments.special = 'repeat_removal' DESC, appointments.startdate, appointments.starttime, appointments.repeat = 'FALSE' DESC";
    $q = self::$DB->query($sql);
    $appointments_all_future = array();
    $tm_ids = array();
    while($row = self::$DB->fetch($q)){
      if(!array_key_exists($row->appointment_id, $appointments_all_future)){$appointments_all_future[$row->appointment_id] = $row;}
      if(!array_key_exists($row->tm_type, $tm_ids) || !is_array($tm_ids[$row->tm_type])){$tm_ids[$row->tm_type] = array();}
      if(!array_key_exists($row->tm_id, $tm_ids[$row->tm_type]) || !is_array($tm_ids[$row->tm_type][$row->tm_id])){$tm_ids[$row->tm_type][$row->tm_id] = array();}
      $tm_ids[$row->tm_type][$row->tm_id][] = $row->appointment_id;
    }

    foreach($tm_ids as $type => $id_array){
      $sql = "SELECT *, '$type' as tm_type, id as tm_id FROM ".strtolower($type)."s WHERE id IN ('".implode("','", array_keys($id_array))."')";
      $q = self::$DB->query($sql);
      while($row = self::$DB->fetch($q)){
        foreach($id_array[$row->tm_id] as $aid){
          $appointments_all_future[$aid]->tm = $row;
        }
      }
    }

    //kvframework_log::write_log("MyCAL_week: ".TOOLS::date_to_s($weekstart)." - ".TOOLS::date_to_s($weekstop), KVF_LOG_LDEBUG);
    $dates1 = TOOLS::date_range($weekstart, $weekstop);
    $dates = array_merge(array(TOOLS::date_today()), $dates1);
    $dates2 = array_unique($dates); //original added all dates hit by any appointment in previous query... why?
    $hd = MyFunctions::appointmentsDataFor(array($rcid), $dates2, false, $admin);
    $rhd =& $hd["rchours"]->blocks[$rcid];
    $ohd =& $hd["ophours"]->blocks;
    $ohdt =& $ohd[TOOLS::date_today()];
    $apd =& $hd["appts"]->blocks[$rcid];

    foreach($dates1 as $day){$week_data[] = $ohd[$day];}

    $week_data[] = $ohdt;
    $week_data = TOOLS::array_reject($week_data, '$i','!$i["start"] || !$i["stop"]');

    if(count($week_data) > 0){
      $temp = TOOLS::array_collect($week_data, '$i', '$i["start"]');
      sort($temp);
      $start = $temp[0];
      $temp = TOOLS::array_collect($week_data, '$i', '$i["stop"]');
      rsort($temp);
      $stop = $temp[0];
    } else {
      $start = $stop = false;
    }

    //print_r($start);
    //print_r($stop);

    if($date && $start && $stop){
      $self["times"] = array();
      $self["today"] = array();
      $self["week"] = array();

      $dur = (int)(($stop - $start) / (30*60));

      //print_r($self);
      //print_r($dur);


      for($i = 0; $i < $dur; $i++){
        $self["times"][] = TOOLS::x_minutes_since($i * 30, $start);
        $self["today"][] = new SchedulerBlock();
      }

      for($i = 0; $i < 7 * $numweeks; $i++){
        $self["week"][$i] = array();
        $self["week"][$i][0] = TOOLS::x_days_since($i, $weekstart);
        $self["week"][$i][1] = array();
        for($j = 0; $j < $dur; $j++){
          $self["week"][$i][1][$j] = new SchedulerBlock();
        }
      }

      //print_r($self);

      $max_i = count($dates);
      for($i = 0; $i < $max_i; $i++){
        /*BIT $ints_today =& $ohd[$dates[$i]]["intervals"]; */

        $d = $rhd[$dates[$i]];
        $offset_time = ($d[0] >= $start) ? 0 : abs($d[0] - $start);
        $postset_time = ($d[1] <= $stop) ? 0 : abs($d[1] - $stop);
        $nat_dur_time = ($d[1] - $d[0]);
        $dur_time = ($nat_dur_time - $offset_time - $postset_time);
        $dur = (int)($dur_time / 1800);
        $offset = (int)($offset_time / 1800);
        $postset = (int)(-1 * ($postset_time / 1800));

        $temp = ($d[0] - $start);
        $j_delta = (($temp > 0) ? ($temp / 1800) : 0) - 1;

        if(is_array($d) && array_key_exists(0, $d) && $d[0] && $d[2] != 0){
          for($j = 1 + $offset; $j < $dur + 1 + $postset; $j++){
            if(TOOLS::bit_read($d[2], $j)){
              $hour = $hd["rchours"]->things[$d[3][$j]];
              //var_dump($hour);
              $status = "A";
              $span = 1;
              if($hour->htype == "delete"){
                 $status = "K";
              } elseif($hour->oncall == "TRUE"){
                $status = "C";
              }

              if(TOOLS::bit_read($ohd[$dates[$i]]["intervals"][2], $j + $j_delta + 1) && MyFunctions::datetime_in_hour($dates[$i], TOOLS::string_to_time($hour->starttime), $hour)){
                if($i == 0){
                  $self["today"][$j + $j_delta]->set_status($status);
                  $self["today"][$j + $j_delta]->set_span($span);
                } else {
                  $self["week"][$i-1][1][$j + $j_delta]->set_status($status);
                  $self["week"][$i-1][1][$j + $j_delta]->set_span($span);
                }
              }
            }
          }
        }

        $d = $apd[$dates[$i]];
        $offset_time = ($d[0] >= $start) ? 0 : abs($d[0] - $start);
        $postset_time = ($d[1] <= $stop) ? 0 : abs($d[1] - $stop);
        $nat_dur_time = ($d[1] - $d[0]);
        $dur_time = ($nat_dur_time - $offset_time - $postset_time);
        $dur = (int)($dur_time / 1800);
        $offset = (int)($offset_time / 1800);
        $postset = (int)(-1 * ($postset_time / 1800));

        //$removals = array();

        $temp = ($d[0] - $start);
        $j_delta = (($temp > 0) ? ($temp / 1800) : 0) - 1;

        if(is_array($d) && array_key_exists(0, $d) && $d[0] && $d[2] != 0){
          for($j = 1 + $offset; $j < $postset + 1 + $dur; $j++){
            if(TOOLS::bit_read($d[2], $j)){
              $appt = $hd["appts"]->things[$d[3][$j]];
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

                //if(!array_key_exists($dates[$i], $removals)){$removals[$dates[$i]] = array();}

                //$removals[$dates[$i]][] = $appt->removal_of;
	              //$removals[] = $appt->removal_of;
              }

              //if((!array_key_exists($j-1, $d[3]) || $d[3][$j-1] != $d[3][$j]) && $status != "A"){
              if((!array_key_exists($j-1, $d[3]) || $d[3][$j-1] != $d[3][$j]) && !is_null($status)){
                $t = 0;
                while(true){
                  if(array_key_exists($j+$t, $d[3]) && $d[3][$j+$t] == $d[3][$j] && $j+$t <= 32){$t++;}
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

              if((TOOLS::bit_read($ohd[$dates[$i]]["intervals"][2], $j + $j_delta + 1) || $override) && MyFunctions::datetime_in_appt($dates[$i], TOOLS::string_to_time($appt->starttime), $appt)){
                if($i == 0){
                  //if(($self["today"][$j + $j_delta]->status == "A" || $self["today"][$j + $j_delta]->status == "C" || $override)){
	                if(!is_null($status)){
                    $self["today"][$j + $j_delta]->set_status($status);
                    $self["today"][$j + $j_delta]->set_span($span);
                    if(!is_null($meta)){
                      $self["today"][$j + $j_delta]->set_meta($meta);
                    }
                  }
                } else {
                  //if(($self["week"][$i-1][1][$j + $j_delta]->status == "A" || $self["week"][$i-1][1][$j + $j_delta]->status == "C" || $override)){
                  if(!is_null($status)){
                    $self["week"][$i-1][1][$j + $j_delta]->set_status($status);
                    $self["week"][$i-1][1][$j + $j_delta]->set_span($span);
                    if(!is_null($meta)){
                      $self["week"][$i-1][1][$j + $j_delta]->set_meta($meta);
                    }
                  }
                }
              }
            }
          }
        }
      }
    } else {
      throw new Exception("We are closed.");
    }

    $temp = TOOLS::array_collect($appointments_all_future, '$appt', 'TOOLS::string_to_date($appt->stopdate)');
    rsort($temp);
    $apptmax = (count($appointments_all_future) > 0) ? $temp[0] : TOOLS::date_today() ;

    list($fah, $fahc) = self::generate_future_appts_hash($appointments_all_future, $apptmax);

    $ucaa = self::generate_unconfirmed_appts_array($appointments_all_future);
    return array($self, $fah, $fahc, $ucaa, array("start" => $weekstart, "stop" => $weekstop));
  }

  /**
   * Generate array of future appointments
   *
   * @param array $appts_all_future
   * @param mixed $apptmax
   * @return array
   */
  protected static function generate_future_appts_hash($appts_all_future, $apptmax){
    $appt_rows = array();
    $appt_count = 0;
    $removed_ids = array();
    $datesr = TOOLS::date_range(TOOLS::date_today(), $apptmax);
    $temp = TOOLS::array_reject($appts_all_future, '$ap', '$ap->confirmed == "FALSE" && $ap->special != "repeat_removal"');
    foreach($temp as $appt){
      $asdate = TOOLS::string_to_date($appt->startdate);
      $tpd = TOOLS::string_to_date($appt->stopdate);
      $drng = TOOLS::date_range($asdate, ($apptmax > $tpd) ? $tpd : $apptmax);

      if($appt->special == "repeat_removal"){
        foreach($drng as $asdate2){
          if(!array_key_exists($asdate2, $removed_ids) || !is_array($removed_ids[$asdate2])){
            $removed_ids[$asdate2] = array();
          }

          if(MyFunctions::datetime_in_appt($asdate2, TOOLS::string_to_time($appt->starttime), $appt)){
            $removed_ids[$asdate2][] = $appt->removal_of;
          }
        }
        continue;
      }

      $actual_dates = array();
      if($appt->repeat == "TRUE"){
        foreach($datesr as $day){
          if(MyFunctions::datetime_in_appt($day, TOOLS::string_to_time($appt->starttime), $appt)){
            $actual_dates[] = $day;
          }
        }
      } else {
        $actual_dates[] = $asdate;
      }

      foreach($actual_dates as $date){
        if(!array_key_exists($date, $appt_rows) || !is_array($appt_rows[$date])){
          $appt_rows[$date] = array();
        }

        if((!array_key_exists($date, $removed_ids) || !is_array($removed_ids[$date]) || !in_array($appt->appointment_id, $removed_ids[$date]))){
          $appt_rows[$date][] = $appt;
        }
      }
    }

    foreach($appt_rows as $appts){
      $appt_count += count($appts);
      usort($appts, array("MyCalendar", "sorter"));
    }

    return array($appt_rows, $appt_count);
  }

  /**
   * Sorts stuff, duh...
   *
   * @param mixed $a
   * @param mixed $b
   * @return integer
   */
  public static function sorter($a, $b){
    $cs = TOOLS::string_to_time($a->starttime);
    $cp = TOOLS::string_to_time($a->stoptime);
    $ds = TOOLS::string_to_time($b->starttime);
    $dp = TOOLS::string_to_time($b->stoptime);
    return(
      ($cs == $ds) ?
        (
          ($cp == $dp) ? 0 :
            (
              ($cp < $dp) ? -1 : 1
            )
        ) :
        (
          ($cs < $ds) ? -1 : 1
        )
      );
  }

  /**
   * Generate an array of unconfirmed appointments
   *
   * @param array $appts_all_future
   * @return array
   */
  protected static function generate_unconfirmed_appts_array($appts_all_future){
    return TOOLS::array_reject($appts_all_future, '$ap', '$ap->confirmed == "TRUE" || $ap->special == "repeat_removal"');
  }
}

?>
