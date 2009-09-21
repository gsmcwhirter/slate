<?php
/**
 * KvScheduler - Miscellaneous Functions
 * @package KvScheduler
 * @subpackage Lib
 * @author Greg McWhirter <gsmcwhirter@gmail.com>
 * @version 1.0
 */

/**
 * Miscellaneous function wrapper
 *
 * @package KvScheduler
 * @subpackage Lib
 */
abstract class MyFunctions extends application_site_class{

  public function is_empty($val)
  {
    return empty($val);
  }

  /**
   * Given two coordinate pairs, determines if they are significantly far apart in (h,v) space
   *
   * @param integer $ah Pair A entry h
   * @param unknown_type $av Pair A entry v
   * @param unknown_type $bh Pair B entry h
   * @param unknown_type $bv Pair B entry v
   * @return boolean
   */
  public static function loczone_far($ah, $av, $bh, $bv){
    $ah = (int)$ah;
    $av = (int)$av;
    $bh = (int)$bh;
    $bv = (int)$bv;
    $factor = ((pow(($ah - $bh), 2)) + (pow(($av - $bv), 2)));
    return ((float)$factor > (float)self::config_vals("travel_tolerance")) ? true : false;
  }

  /**
   * Get Operating hours information  for some dates
   *
   * @param array $dates
   * @return ophdata_struct
   */
  public static function opHoursDataFor(array $dates){
    $tstart = microtime(true);
    $dateconditions = "";

    if(count($dates) == 0){
      throw new Exception("Dates array cannot be empty");
    }

    foreach($dates as $date){
      $dateconditions .= "(startdate <= '".TOOLS::date_to_s($date)."' AND stopdate >= '".TOOLS::date_to_s($date)."' AND FIND_IN_SET('".TOOLS::weekday_transform(TOOLS::wday_for($date))."', ophours.repetition)) OR ";
    }

    $ohours = array();
    $sql = "SELECT *, id as ophour_id FROM ophours WHERE ".substr($dateconditions, 0, -4)." ORDER BY timestamp ASC";
    $q = self::$DB->query($sql);
    while($row = self::$DB->fetch($q)){
      $ohours[] = $row;
    }

    $starts = array();
    $stops = array();

    foreach($dates as $date){
      //$a = TOOLS::array_collect(TOOLS::array_reject($ohours, '$p', '($p->special == "delete" || TOOLS::string_to_date($p->startdate) >= '.$date.' || TOOLS::string_to_date($p->stopdate) <= '.$date.')'), '$p', 'array(TOOLS::string_to_time($p->starttime), TOOLS::string_to_time($p->stoptime))');
       $a = TOOLS::array_collect(TOOLS::array_reject($ohours, '$p', '($p->special == "delete" || TOOLS::string_to_date($p->startdate) > '.$date.' || TOOLS::string_to_date($p->stopdate) < '.$date.')'), '$p', 'array(TOOLS::string_to_time($p->starttime), TOOLS::string_to_time($p->stoptime))');
      if(count($a) > 0){
        usort($a, "sort_by_index_0_asc");
        $starts[$date] = $a[0][0];
        usort($a, "sort_by_index_1_desc");
        $stops[$date] = $a[0][1];
      } else {
        $starts[$date] = 0;
        $stops[$date] = 0;
      }
    }

    $dblocks = array();
    foreach($dates as $date){
      $dblocks[$date] = array($starts[$date], $stops[$date], 0);
      if($starts[$date] > 0 && $stops[$date] > 0){
        /*BIT foreach($positive as $pos){ */
        foreach($ohours as $pos){
          if(!(TOOLS::string_to_date($pos->startdate) <= $date && TOOLS::string_to_date($pos->stopdate) >= $date && in_array(TOOLS::weekday_transform(TOOLS::wday_for($date)), explode(",", $pos->repetition)))){
            continue;
          }

          if(TOOLS::string_to_time($pos->stoptime) > $stops[$date]){$sp = $stops[$date];} else {$sp = TOOLS::string_to_time($pos->stoptime);}
          if(TOOLS::string_to_time($pos->starttime) < $starts[$date]){$st = $starts[$date];} else {$st = TOOLS::string_to_time($pos->starttime);}
          $nblocks = (int)(($sp - $st) / (30*60));
          $offset = (int)(($st - $dblocks[$date][0]) / (30*60));
          if($offset < 0){
            $nblocks += $offset;
            $offset = 0;
          }

          for($i = $offset + 1; $i < $offset + $nblocks + 1; $i++){
            if($pos->special == "delete"){TOOLS::bit_clear($dblocks[$date][2], $i);}
            else{TOOLS::bit_set($dblocks[$date][2], $i);}
          }
        }
      }
    }

    $ret = array();
    foreach($dates as $date){
      $ret[$date] = array("start" => $starts[$date], "stop" => $stops[$date], "intervals" => $dblocks[$date]);
    }

    $d = new ophdata_struct();
    $d->blocks = $ret;

    $tstop = microtime(true);
    kvframework_log::write_log("opHoursDataFor: executed in ".($tstop - $tstart)."s");

    return $d;
  }

  /**
   * Get Appointment hours information for some dates and some appointment types
   *
   * @param array $dates
   * @param array $appttypes
   * @return ophdata_struct
   */
  public static function apptHoursDataFor(array $dates, array $appttypes){
    $tstart = microtime(true);
    $dateconditions = "";

    $ophrs_data = self::opHoursDataFor($dates);

    $starts = array();
    $stops = array();
    $dblocks = array();
    $dateconditions = "";

    if(count($dates) == 0){ throw new Exception("Dates cannot be empty");}
    if(count($appttypes) == 0){ throw new Exception("Appointment type IDs cannot be empty");}

    foreach($dates as $date){
      $starts[$date] = $ophrs_data->blocks[$date]["start"];
      $stops[$date] = $ophrs_data->blocks[$date]["stop"];
      $dateconditions .= "(startdate <= '".TOOLS::date_to_s($date)."' AND stopdate >= '".TOOLS::date_to_s($date)."' AND (FIND_IN_SET('".TOOLS::weekday_transform(TOOLS::wday_for($date))."', appthours.repeat) OR htype != 'repeat')) OR ";
    }

    foreach($appttypes as $rc){
      $dblocks[$rc] = array();
      foreach($dates as $date){
        $dblocks[$rc][$date] = array("start" => $starts[$date], "stop" => $stops[$date], "intervals" => 0);
      }
    }

    $hours = array();
    $sql = "SELECT *, id as appthour_id FROM appthours WHERE appttype_id IN ('".implode("','", $appttypes)."') AND (".substr($dateconditions, 0, -4).") ORDER BY timestamp ASC";
    $q = self::$DB->query($sql);
    while($row = self::$DB->fetch($q)){
      $hours[$row->appthour_id] = $row;

      foreach($dates as $date){
        if(!self::datetime_in_hour($date, TOOLS::string_to_time($row->starttime), $row)){continue;}
        $startindex = self::start_index_for(TOOLS::string_to_time($row->starttime), $starts[$date]);

        $span = TOOLS::calcspan((TOOLS::string_to_time($row->starttime) > $starts[$date]) ? TOOLS::string_to_time($row->starttime) : $starts[$date], (TOOLS::string_to_time($row->stoptime) < $stops[$date]) ? TOOLS::string_to_time($row->stoptime) : $stops[$date]);

        for($j = 1; $j < $span + 1; $j++){
          $i = $j + $startindex;
            if($row->htype == "delete" && $i <= 32){TOOLS::bit_clear($dblocks[$row->appttype_id][$date]["intervals"], $i);}
            else{TOOLS::bit_set($dblocks[$row->appttype_id][$date]["intervals"], $i);}
        }
      }
    }

    $d = new aphdata_struct();
    $d->blocks = $dblocks;

    $tstop = microtime(true);
    kvframework_log::write_log("apptHoursDataFor: executed in ".($tstop - $tstart)."s");

    return array("ophours" => $ophrs_data, "aphours" => $d);
  }

  /**
   * Gets operating hour data and then consultant hour data for some consultants and some dates
   *
   * @see MyFunctions::opHoursDataFor()
   * @param array $consultant_ids
   * @param array $dates
   * @param boolean $repeat_only
   * @return array
   */
  public static function consultantHoursDataFor(array $consultant_ids, array $dates, $repeat_only = false){

    $tstart = microtime(true);
    $consultants = array();
    $sql = "SELECT *, consultants.id as consultant_id, tags.id as tag_id FROM consultants, tags WHERE consultants.id IN ('".implode("','", $consultant_ids)."') AND tags.id = consultants.tag_id ORDER BY consultants.staff='true' ASC, ".CONFIG::SQL_REALNAME_ORDER_CLAUSE;
    $q = self::$DB->query($sql);
    while($row = self::$DB->fetch($q)){
      $consultants[$row->consultant_id] = $row;
    }
    $tstop = microtime(true);
    kvframework_log::write_log("consultantHoursDataFor: consultants executed in ".($tstop - $tstart)."s");

    $d = new chdata_struct();

    $ophrs_data = self::opHoursDataFor($dates);

    $tstart = microtime(true);
    $starts = array();
    $stops = array();
    $dblocks = array();
    $dateconditions = "";

    if(count($dates) == 0){ throw new Exception("Dates cannot be empty");}
    if(count($consultant_ids) == 0){ throw new Exception("Consultant IDs cannot be empty");}

    foreach($dates as $date){
      $starts[$date] = $ophrs_data->blocks[$date]["start"];
      $stops[$date] = $ophrs_data->blocks[$date]["stop"];
      $dateconditions .= "(startdate <= '".TOOLS::date_to_s($date)."' AND stopdate >= '".TOOLS::date_to_s($date)."' AND (FIND_IN_SET('".TOOLS::weekday_transform(TOOLS::wday_for($date))."', consultanthours.repeat) OR htype != 'repeat')) OR ";
    }

    foreach($consultant_ids as $rc){
      $dblocks[$rc] = array();
      foreach($dates as $date){
        /*BIT $dblocks[$rc][$date] = TOOLS::prep_blocks($starts[$date], $stops[$date]);*/
        $dblocks[$rc][$date] = array(0 => $starts[$date], 1 => $stops[$date], 2 => 0, 3 => array());
      }
    }

    $hours = array();
    $sql = "SELECT *, id as consultanthour_id FROM consultanthours WHERE consultant_id IN ('".implode("','", $consultant_ids)."') AND (".substr($dateconditions, 0, -4).") AND htype2 != 'request'".(($repeat_only) ? "AND (htype = 'repeat' OR (htype = 'delete' AND stopdate != startdate))" : "")."ORDER BY timestamp ASC";
    $q = self::$DB->query($sql);
    while($row = self::$DB->fetch($q)){
      $hours[$row->consultanthour_id] = $row;
      //print "--date--<br />";
      //print_r($row);
      //print "<br />";
      foreach($dates as $date){
        //print $date."<br />";
        if(!self::datetime_in_hour($date, TOOLS::string_to_time($row->starttime), $row)){
          //print "!dtih<br />";
          continue;
        }
        $startindex = self::start_index_for(TOOLS::string_to_time($row->starttime), $starts[$date]);

        $span = TOOLS::calcspan((TOOLS::string_to_time($row->starttime) > $starts[$date]) ? TOOLS::string_to_time($row->starttime) : $starts[$date], (TOOLS::string_to_time($row->stoptime) < $stops[$date]) ? TOOLS::string_to_time($row->stoptime) : $stops[$date]);

        for($j = 1; $j < $span + 1; $j++){
          $i = $j + $startindex;
          if($row->htype == "delete" && $i <= 32){
            //print "del<br />";
            TOOLS::bit_clear($dblocks[$row->consultant_id][$date][2], $i);
          } else{
            //print "add<br />";
            TOOLS::bit_set($dblocks[$row->consultant_id][$date][2], $i);
          }
          $dblocks[$row->consultant_id][$date][3][$i] = $row->consultanthour_id;
        }
      }
    }

    $d->blocks = $dblocks;
    $d->things = $hours;
    $d->consultants = $consultants;

    $tstop = microtime(true);
    kvframework_log::write_log("consultantHoursDataFor: executed in ".($tstop - $tstart)."s");

    return array("ophours" => $ophrs_data, "rchours" => $d);

  }

  /**
   * Gets appointment data, consultanthour data, and operating hour data for some consultants on some dates
   *
   * @see MyFunctions::opHoursDataFor()
   * @see MyFunctions::consultantHoursDataFor()
   * @param array $consultant_ids
   * @param array $dates
   * @param string $lockout_user
   * @param boolean $nonconfirmed
   * @return array
   */
  public static function appointmentsDataFor(array $consultant_ids, array $dates, $lockout_user = false, $nonconfirmed = true){

    $d = new apdata_struct();

    $dat = self::consultantHoursDataFor($consultant_ids, $dates);

    $tstart = microtime(true);
    $ophrs_data =& $dat["ophours"];
    $rchrs_data =& $dat["rchours"];

    $starts = array();
    $stops = array();
    $dblocks = array();
    $appts_removed = array();
    $dateconditions = "";

    if(count($dates) == 0){ throw new Exception("Dates cannot be empty");}
    if(count($consultant_ids) == 0){ throw new Exception("Consultant IDs cannot be empty");}

    foreach($dates as $date){
      $starts[$date] = $ophrs_data->blocks[$date]["start"];
      $stops[$date] = $ophrs_data->blocks[$date]["stop"];
      $dateconditions .= "(appointments.startdate <= '".TOOLS::date_to_s($date)."' AND appointments.stopdate >= '".TOOLS::date_to_s($date)."' AND (FIND_IN_SET('".TOOLS::weekday_transform(TOOLS::wday_for($date))."', appointments.repetition_day) OR appointments.repeat = 'FALSE')) OR ";
    }

    foreach($consultant_ids as $rc){
      $dblocks[$rc] = array();
      $appts_removed[$rc] = array();
      foreach($dates as $date){
        $appts_removed[$rc][$date] = array();
        /*BIT $dblocks[$rc][$date] = TOOLS::prep_blocks($starts[$date], $stops[$date]);*/
        $dblocks[$rc][$date] = array(0 => $starts[$date], 1 => $stops[$date], 2 => 0, 3 => array());
      }
    }

    $appts = array();
    //$appts =& $d->things;
    $tms = array();
    $sql  = "SELECT appointments.*, consultantappts.*, consultantappts.consultant_id, locations.name as location_name, loczones.potentialh, loczones.potentialv, loczones.id as loczone_id, loczones.name as loczone_name FROM appointments, consultantappts, locations, loczones WHERE appointments.lockout = 'FALSE' AND consultantappts.appointment_id = appointments.id AND locations.id = appointments.location_id AND loczones.id = locations.loczone_id AND consultantappts.consultant_id IN (".implode(",",$consultant_ids).") ".(($nonconfirmed) ? "" : "AND (consultantappts.confirmed = 'TRUE' OR appointments.special = 'repeat_removal')")." AND (".substr($dateconditions, 0, -4).") ".(($lockout_user) ? "AND appointments.lockout_user != '".$lockout_user."'" : "")." ORDER BY appointments.special = 'repeat_removal' DESC";
    $sql2 = "SELECT appointments.*, consultantappts.*, consultantappts.consultant_id FROM appointments, consultantappts WHERE appointments.lockout = 'TRUE' AND consultantappts.appointment_id = appointments.id AND consultantappts.consultant_id IN (".implode(",",$consultant_ids).") ".(($nonconfirmed) ? "" : "AND consultantappts.confirmed = 'TRUE'")." AND (".substr($dateconditions, 0, -4).") ".(($lockout_user) ? "AND appointments.lockout_user != '".$lockout_user."'" : "")." ORDER BY appointments.special = 'repeat_removal' DESC";
    $q = self::$DB->query($sql);
    $q2 = self::$DB->query($sql2);

    $temp = array();
    while($row = self::$DB->fetch($q)){
      $temp[] = $row;
    }
    while($row = self::$DB->fetch($q2)){
      $temp[] = $row;
    }

    foreach($temp as $row){
      if(!array_key_exists($row->appointment_id, $appts) || is_null($appts[$row->appointment_id])){$appts[$row->appointment_id] = $row;}
      if($row->lockout == 'FALSE' && (!array_key_exists($row->tm_type, $tms) || !is_array($tms[$row->tm_type]))){$tms[$row->tm_type] = array();}
      if($row->lockout == 'FALSE'){$tms[$row->tm_type][$row->appointment_id] = $row->tm_id;}
      if(!is_array($appts[$row->appointment_id]->consultantappts)){
        $appts[$row->appointment_id]->consultantappts = array();
      }
      $appts[$row->appointment_id]->consultantappts[$row->consultant_id] = array("confirmed" => $row->confirmed, "version" => $row->version);
      if(!is_array($appts[$row->appointment_id]->consultants)){
        $appts[$row->appointment_id]->consultants = array();
      }
      $appts[$row->appointment_id]->consultants[$row->consultant_id] = true;

      foreach($dates as $date){
        $rc =& $row->consultant_id;
        $startindex = self::start_index_for(TOOLS::string_to_time($row->starttime), $starts[$date]);
        if(!self::datetime_in_appt($date, TOOLS::string_to_time($row->starttime), $row)){continue;}
        $span = TOOLS::calcspan((TOOLS::string_to_time($row->starttime) > $starts[$date]) ? TOOLS::string_to_time($row->starttime) : $starts[$date], (TOOLS::string_to_time($row->stoptime) < $stops[$date]) ? TOOLS::string_to_time($row->stoptime) : $stops[$date]);
        for($j = 1; $j < $span + 1; $j++){
          $i = $j + $startindex;
          if($row->special == "repeat_removal"){
            $appts_removed[$rc][$date][] = $row->removal_of;
          } else {
            if(!array_key_exists($rc, $appts_removed) || !is_array($appts_removed[$rc]) || !array_key_exists($date, $appts_removed[$rc]) || !is_array($appts_removed[$rc][$date]) || !in_array($row->appointment_id, $appts_removed[$rc][$date])){
              TOOLS::bit_set($dblocks[$rc][$date][2], $i);
              $dblocks[$rc][$date][3][$i] = $row->appointment_id;
            }
          }
        }
      }
    }

    foreach($tms as $type => $type_ids){
      $from = strtolower($type);
      $temp = array();
      switch($from){
        case "ticket":
          $sql = "SELECT id as tm_id, person, remedy_ticket FROM ".$from."s WHERE id IN (".implode(",", $type_ids).")";
          break;
        case "meeting":
        case "meecket":
          $sql = "SELECT id as tm_id, subject FROM ".$from."s WHERE id IN (".implode(",", $type_ids).")";
          break;
      }

      $q = self::$DB->query($sql);
      while($row = self::$DB->fetch($q)){
        $temp[$row->tm_id] = $row;
      }

      foreach($type_ids as $apid => $tmid){
        $appts[$apid]->tm = $temp[$tmid];
      }
    }

    $d->blocks = $dblocks;
    $d->things = $appts;

    $tstop = microtime(true);
    kvframework_log::write_log("appointmentsDataFor: executed in ".($tstop - $tstart)."s");

    return array("ophours" => $ophrs_data, "rchours" => $rchrs_data, "appts" => $d);
  }

  /**
   * Determines whether a date and time are affected by an hour record
   *
   * @param integer $date
   * @param integer $time
   * @param mixed $hour
   * @return boolean
   */
  public static function datetime_in_hour($date, $time, &$hour){
    if(
        $time >= TOOLS::string_to_time($hour->starttime) &&
        $time < TOOLS::string_to_time($hour->stoptime) &&
        (
          (
            $hour->htype == "once" ||
              //(
              //  $hour->htype == "delete" &&
              //  !preg_match("#,#",$hour->repeat)
              //)
	          false
          ) &&
          TOOLS::string_to_date($hour->startdate) == $date
        )
    ){
        //print "1<br />";
      return true;
    }
    elseif(
          $time >= TOOLS::string_to_time($hour->starttime) &&
          $time < TOOLS::string_to_time($hour->stoptime) &&
          (
            $date >= TOOLS::string_to_date($hour->startdate) &&
            $date <= TOOLS::string_to_date($hour->stopdate) &&
            (
              $hour->htype == "repeat" ||
              $hour->htype == "delete"
						) &&
            in_array(TOOLS::weekday_transform(TOOLS::wday_for($date)), explode(",",$hour->repeat))
          )
        )
    {
        //print "2<br />";
      return true;
    } else {
      return false;
    }
  }

  /**
   * Determines whether a date and time are affected by an appointment record
   *
   * @param integer $date
   * @param integer $time
   * @param mixed $appt
   * @return boolean
   */
  public static function datetime_in_appt($date, $time, &$appt){
    if($time >= TOOLS::string_to_time($appt->starttime) && $time < TOOLS::string_to_time($appt->stoptime) && (($date == TOOLS::string_to_date($appt->startdate) && $appt->repeat == "FALSE") || ($date >= TOOLS::string_to_date($appt->startdate) && $date <= TOOLS::string_to_date($appt->stopdate) && $appt->repeat == "TRUE" && TOOLS::on_multiple_weeks($date, TOOLS::string_to_date($appt->startdate), $appt->repetition_week) && in_array(TOOLS::weekday_transform(TOOLS::wday_for($date)), explode(",", $appt->repetition_day))))){
      /*
       * (
       * 	$time >= TOOLS::string_to_time($appt->starttime)
       * 	&&
       * 	$time < TOOLS::string_to_time($appt->stoptime)
       * 	&&
       * 	(
       * 		(
       * 			$date == TOOLS::string_to_date($appt->startdate)
       * 			&&
       * 			$appt->repeat == "FALSE"
       * 		)
       * 		||
       * 		(
       * 			$date >= TOOLS::string_to_date($appt->startdate)
       * 			&&
       * 			$date <= TOOLS::string_to_date($appt->stopdate)
       * 			&&
       * 			$appt->repeat == "TRUE"
       * 			&&
       * 			TOOLS::on_multiple_weeks($date, TOOLS::string_to_date($appt->startdate), $appt->repetition_week)
       * 			&&
       * 			in_array(TOOLS::weekday_transform(TOOLS::wday_for($date)), explode(",", $appt->repetition_day))
       * 		)
       * 	)
       * )
       */
      return true;
    } else {
      return false;
    }
  }

  /**
   * Determines whether or not a record in within some intervals
   *
   * @param mixed $thing
   * @param array $blocks
   * @param boolean $fully
   * @return boolean
   */
  public static function in_intervals($thing, $blocks, $fully = false){
    if(!$blocks[0]){return false;}

    $nblocks = (int)((TOOLS::string_to_time($thing->stoptime) - TOOLS::string_to_time($thing->starttime)) / (30*60));
    $offset = (int)((TOOLS::string_to_time($thing->starttime) - $blocks[0]) / (30*60));
    if($offset < 0){
      $nblocks += $offset;
      $offset = 0;
    }

    for($i = $offset + 1; $i < $offset + 1 + $nblocks; $i++){
      if(TOOLS::bit_read($blocks[2], $i) && !$fully){return true;}
      elseif($fully && !TOOLS::bit_read($blocks[2], $i)){return false;}
    }
    return ($fully) ? true : false;
  }

  /**
   * Generates the overlapping intervals of a thing and some other intervals
   *
   * @param mixed $thing
   * @param array $blocks
   * @return unknown
   */
  public static function overlap_portion($thing, $blocks){
    if(!$blocks[0] || !$blocks[1]){return array();}

    $nblocks = (int)((TOOLS::string_to_time($thing->stoptime) - TOOLS::string_to_time($thing->starttime)) / (30*60));
    $offset = (int)((TOOLS::string_to_time($thing->starttime) - $blocks[0]) / (30*60));
    if($offset < 0){
      $nblocks += $offset;
      $offset = 0;
    }

    $open = false;
    $ints = array();
    $k = 0;
    for($i = $offset + 1; $i < $offset + 1 + $nblocks; $i++){
      if(TOOLS::bit_read($blocks[2], $i)){
        if(!$open){
          $ints[$k] = array($i);
          $open = true;
          if(!TOOLS::bit_read($blocks[2], $i+1)){$ints[$k][1] = $i; $k++;}
        }
        else{
          if(TOOLS::bit_read($blocks[2], $i+1)){continue;}
          else{$ints[$k][1] = $i; $k++;}
        }
      }
    }

    $to_return = array();

    foreach($ints as $int){
      $to_return[] = new Thing(TOOLS::x_minutes_since((($int[0] - 1) * 30), $blocks[0]), TOOLS::x_minutes_since((($int[1] - 1) * 30), $blocks[0]));
    }

    return $to_return;
  }


  /**
   * Determine which index of an array to start at given a start time of an event and the start time of the block
   *
   * @param integer $hour_st
   * @param integer $block0_time
   * @return integer
   */
  public static function start_index_for($hour_st, $block0_time){
    $diff = $hour_st - $block0_time;
    return(($diff > 0) ? (int)floor($diff / (30*60)) : 0);
  }

  /**
   * Generate an array of weekly appointment hours for some consultants on some week
   *
   * @param array $consultant_ids
   * @param integer $week_of_date
   * @return array
   */
  public static function week_appt_counts(array $consultant_ids, $week_of_date){
    $res = array();
    $wd = TOOLS::x_days_since((-1 * TOOLS::wday_for($week_of_date)), $week_of_date); //the most recent sunday

    $sql = "SELECT * FROM consultantweeklyhours WHERE week_date = '".TOOLS::date_to_s($wd)."' AND consultant_id IN ('".implode("','", $consultant_ids)."')";
    $q = self::$DB->query($sql);
    while($row = self::$DB->fetch($q)){
      $res[$row->consultant_id] = $row->week_hours;
    }

    return $res;
  }

  /**
   * Find the dates of an appointment's occurrences
   *
   * @param mixed $appt
   * @return array
   */
  public static function occurrences_of_appt($appt){
    $ret = array();

    $start = TOOLS::string_to_date($appt->startdate);

    if($appt->repeat == "TRUE"){
      $stop = TOOLS::string_to_date($appt->stopdate);
      $mw = (int)$appt->repetition_week;
      $md = explode(",", $appt->repetition_day);

      $loop_max = (int)(($stop - $start) / (24*60*60));
      $wdays = TOOLS::wday_for($start);

      for($i = 0; $i < $loop_max + 1; $i++){
        if(!in_array(TOOLS::weekday_transform($wdays + $i), $md)){continue;}
        $nd = TOOLS::x_days_since($i, $start);
        if(!TOOLS::on_multiple_weeks($start, $nd, $mw)){continue;}
        $ret[] = $nd;
      }
    } else {
      $ret[] = $start;
    }

    return $ret;
  }

}
?>
