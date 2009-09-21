<?php
/**
 * KvScheduler - Appointment checks wrapper
 * @package KvScheduler
 * @subpackage Lib
 * @author Greg McWhirter <gsmcwhirter@gmail.com>
 * @version 1.0
 */

/**
 * Appointment checks wrapper
 *
 * @package KvScheduler
 * @subpackage Lib
 */
abstract class ApptChecks extends kvframework_base{

  /**
   * Checks for repeating appointments
   *
   * @param integer $startdate
   * @param integer $stopdate
   * @param integer $start
   * @param integer $stop
   * @param array $consultants
   * @param string $special2
   * @param array $rep_day
   * @param mixed $rep_week
   * @param string $user
   * @return array
   */
  public static function repeating_checks($startdate, $stopdate, $start, $stop, array $consultants, $special2, array $rep_day, $rep_week, $user){
    $checks_for = array();
    foreach($consultants as $r){
      $checks_for[$r] = array();
      $checks_for[$r]["checks"] = array();
      $checks_for[$r]["reasons"] = array();
    }
    $weeks = ceil(floor(($stopdate - $startdate) / (24*60*60)) / (7 * $rep_week));
    $dint = TOOLS::date_range($startdate, $stopdate);
    $w = TOOLS::wday_for($startdate);
    $wday_reverse = array(
      "M" => (1-$w) % 7,
      "T" => (2-$w) % 7,
      "W" => (3-$w) % 7,
      "H" => (4-$w) % 7,
      "F" => (5-$w) % 7,
      "S" => (6-$w) % 7,
      "N" => (0-$w) % 7
    );

    for($w = 0; $w < $weeks; $w++){
      foreach($rep_day as $d1){
        $d = $wday_reverse[$d1];
        $thisday = TOOLS::x_days_since($d + ($w * 7 * $rep_week),$startdate);
        if(in_array($thisday, $dint)){
          $results = self::day_check($thisday, $consultants, $start, $stop, $special2, $user);
          foreach($consultants as $rc){
            $checks_for[$rc]["checks"][$thisday] = $results[$rc]["check"];
            $checks_for[$rc]["reasons"][$thisday] = $results[$rc]["reason"];
          }
        }
      }
    }

    if($weeks == 0){
      $results = self::day_check($startdate, $consultants, $start, $stop, $special2, $user);
      foreach($consultants as $rc){
        $checks_for[$rc]["checks"][$startdate] = $results[$rc]["check"];
        $checks_for[$rc]["reasons"][$startdate] = $results[$rc]["reason"];
      }
    }

    return $checks_for;
  }

  /**
   * Checks for a single day
   *
   * @param integer $date
   * @param array $consultants
   * @param integer $start
   * @param integer $stop
   * @param string $special2
   * @param string $user
   * @return array
   */
  public static function day_check($date, array $consultants, $start, $stop, $special2, $user){
    $checks_for = array();

    $temp = MyFunctions::consultantHoursDataFor($consultants, array($date));
    $ophrdata =& $temp["ophours"]->blocks[$date];
    $rchd =& $temp["rchours"]->blocks;
    $rchds =& $temp["rchours"];
    $test_thing = new Thing(TOOLS::time_to_s($start), TOOLS::time_to_s($stop));

    //kvframework_log::write_log("OPHRDATA: ".serialize($ophrdata), KVF_LOG_LDEBUG);
    if(!MyFunctions::in_intervals($test_thing, $ophrdata["intervals"])){
      foreach($consultants as $r){
        $checks_for[$r] = array();
        $checks_for[$r]["check"] = false;
        $checks_for[$r]["reason"] = "Appointment is not within operating hours on ". TOOLS::date_to_s($date);
      }
      return $checks_for;
    } else {
      foreach($consultants as $r){
        $checks_for[$r] = array();
        $checks_for[$r]["check"] = true;
        $checks_for[$r]["reason"] = "";
      }
    }

    $sql = "SELECT *, id as consultant_id FROM consultants WHERE id IN (".implode(",", $consultants).")";
    $q = self::$DB->query($sql);
    $rcdata = array();
    while($rca = self::$DB->fetch($q)){
      $rcdata[$rca->id] = $rca;
    }

    foreach($consultants as $rc){
      if(!array_key_exists($rc, $rcdata)){
        $checks_for[$rc]["check"] = false;
        $checks_for[$rc]["reason"] = "Consultant $rc was not found in the system";
        continue;
      } else {
        $checks_for[$rc]["check"] = true;
        $checks_for[$rc]["reason"] = "";
      }

      if(Consultant::hasConsultantHoursOn($rc, $date, $start, $stop, $rchds, true)){
        if(!Lockouts::create($rcdata[$rc]->consultant_id, $start, $stop, $date, $user)){
          $checks_for[$rc]["check"] = false;
          $checks_for[$rc]["reason"] = "Unable to create a lockout for ".$rcdata[$rc]->realname;
        }
      } elseif(Consultant::hasConsultantHoursOn($rc, $date, $start, $stop, $rchds) && $special2 == "meeting"){
        /*BIT $intersections = MyFunctions::overlap_portion(new Thing($start, $stop), TOOLS::array_collect($rchd[$rc][$date], '$d', '(is_array($d)) ? $d[0] : $d')); */
        $intersections = MyFunctions::overlap_portion(new Thing($start, $stop), $rchd[$rc][$date][2]);
        foreach($intersections as $int){
          if(!Lockouts::create($rcdata[$rc]->consultant_id, $int->begin, $int->end, $date, $user)){
            $checks_for[$rc]["check"] = false;
            $checks_for[$rc]["reason"] = "Unable to create a lockout for ".$rcdata[$rc]->realname;
            break;
          }
        }
      } elseif($special2 == "meeting"){

      } else{
        $checks_for[$rc]["check"] = false;
        $checks_for[$rc]["reason"] = "You are trying to schedule a regular appointment outside of regular hours for ".$rcdata[$rc]->realname;
      }
    }

    return $checks_for;
  }

  /**
   * Test whether the max number of concurrent appointments has been exceeded
   *
   * @param integer $loc
   * @param integer $date
   * @param integer $stime
   * @param integer $ptime
   * @return integer
   */
  public static function max_concurrent_appts($loc, $date, $stime, $ptime){
    $ctdappts = array();
    $ltags = array();
    $sql = "SELECT DISTINCT loctag_id FROM location_loctags WHERE location_id = '".$loc."'";
    $q = self::$DB->query($sql);
    while($ltag = self::$DB->fetch($q))
    {
    	$ltags[] = $ltag->loctag_id;
    }

    $incl = "'".implode("','",$ltags)."'";

    $sql = "SELECT appointments.*, appointments.id as appointment_id, loctags.id as loctag_id, loctags.*, consultants.id as consultant_id FROM appointments, locations, location_loctags, loctags, consultantappts, consultants WHERE location_loctags.loctag_id IN (".$incl.") AND locations.id = location_loctags.location_id AND loctags.id = location_loctags.loctag_id AND appointments.location_id = locations.id AND consultantappts.appointment_id = appointments.id AND consultants.id = consultantappts.consultant_id AND appointments.startdate <= '".TOOLS::date_to_s($date)."' AND appointments.stopdate >= '".TOOLS::date_to_s($date)."' AND appointments.starttime <= '".TOOLS::time_to_s($ptime)."' AND appointments.stoptime > '".TOOLS::time_to_s($stime)."'";
    $q = self::$DB->query($sql);
    $counts = array(); /* Will be indexed by loctag, giving a count for concurrency broken into hours */
    #foreach(TOOLS::every_30m_between($stime, $ptime) as $h){
    #  $counts[$h] = 0;
    #}

    while($ap = self::$DB->fetch($q))
    {
      if(!array_key_exists($ap->loctag_id, $counts))
      {
        $counts[$ap->loctag_id] = array("loctag" => array($ap->label, $ap->max_concurrent_appts, $ap->loctag_id), "counts" => array());
        foreach(TOOLS::every_30m_between($stime, $ptime) as $h)
        {
          $counts[$ap->loctag_id]["counts"][$h] = 0;
        }
      }
      if(!in_array($ap->appointment_id, $ctdappts))
      {
        foreach(TOOLS::every_30m_between($stime, $ptime) as $t)
        {
          if(MyFunctions::datetime_in_appt($date, $t, $ap))
          {
            $counts[$ap->loctag_id]["counts"][$t]++;
          }
        }
        $ctdappts[] = $ap->appointment_id;
      }
    }

    foreach($counts as $c)
    {
      rsort($c["counts"]);
    }

    return $counts;
    #sort($counts);
    #return array_pop($counts);
  }

}
?>
