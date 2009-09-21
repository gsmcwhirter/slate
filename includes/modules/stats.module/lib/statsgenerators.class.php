<?php
/**
 * KvScheduler - Stats Generators
 * @package KvScheduler
 * @subpackage Modules.Lib
 * @author Greg McWhirter <gsmcwhirter@gmail.com>
 * @version 1.0
 */

/**
 * Generates stat reports
 *
 * @package KvScheduler
 * @subpackage Modules.Lib
 */
abstract class StatsGenerators extends kvframework_base {

  /**
   * Generate the statistics for a percentages report
   * @param int $startdate Timestamp of the starting date
   * @param int $stopdate Timestamp of the stopping date
   * @return array Report data
   */
  public static function percents($startdate, $stopdate){
    $over_total = 0;
    $totals = array();

    $appttypes = array();
    $sql = "SELECT *, id as appttype_id FROM appttypes";
    $q = self::$DB->query($sql);
    while($row = self::$DB->fetch($q)){
      if(!array_key_exists($row->appttype_id, $totals)){$totals[$row->appttype_id] = 0;}
      if(!array_key_exists($row->appttype_id, $appttypes)){$appttypes[$row->appttype_id] = $row;}
    }

    $report = array();
    $dateconditions = "appointments.startdate <= '".TOOLS::date_to_s($stopdate)."' AND appointments.stopdate >= '".TOOLS::date_to_s($startdate)."'";
    $sql = "SELECT consultantappts.*, appttypes.id as appttype_id, appointments.*, appointments.id as appointment_id FROM appointments, consultantappts, locations, appttypes WHERE appttypes.id = locations.appttype_id AND locations.id = appointments.location_id AND consultantappts.appointment_id = appointments.id AND $dateconditions";
    $q = self::$DB->query($sql);
    while($row = self::$DB->fetch($q)){
      if($row->repeat == "TRUE"){
        $asd = TOOLS::string_to_date($row->startdate);
        $apd = TOOLS::string_to_date($row->stopdate);
        $ast = TOOLS::string_to_time($row->starttime);
        $apt = TOOLS::string_to_time($row->stoptime);
        $d2c = TOOLS::date_range(($startdate > $asd) ? $startdate : $asd, ($stopdate < $apd) ? $stopdate : $apd);
        foreach($d2c as $d){
          if(MyFunctions::datetime_in_appt($d, $ast, $row)){
            $totals[$row->appttype_id] += 1;
            $over_total += 1;
          }
        }
      } else {
        $totals[$row->appttype_id] += 1;
        $over_total += 1;
      }
    }


    foreach($appttypes as $at){
      if($over_total == 0){
        $report[Appttype::select_name($at)] = "N/A";
      } else {
        $report[Appttype::select_name($at)] = round(100 * $totals[$at->appttype_id] / $over_total, 1);
      }
    }


    return array("output" => $report, "Total" => $over_total);
  }

  /**
   * Generates the list report for consultants
   *
   * @param array $consultant_ids
   * @param array $appttype_ids
   * @param integer $startdate
   * @param integer $stopdate
   * @return array
   */
  public static function consultants_list(array $consultant_ids, array $appttype_ids, $startdate, $stopdate){

    $consultants = array();
    $sql = "SELECT *, id as consultant_id FROM consultants WHERE id IN ('".implode("','", $consultant_ids)."') ORDER BY SUBSTRING(realname FROM (INSTR(realname, ' ')+1)) ASC";
    $q = self::$DB->query($sql);
    while($row = self::$DB->fetch($q)){
      if(!array_key_exists($row->consultant_id, $consultants)){$consultants[$row->consultant_id] = $row;}
    }

    $appttypes = array();
    $sql = "SELECT *, id as appttype_id FROM appttypes WHERE id IN ('".implode("','", $appttype_ids)."')";
    $q = self::$DB->query($sql);
    while($row = self::$DB->fetch($q)){
      if(!array_key_exists($row->appttype_id, $appttypes)){$appttypes[$row->appttype_id] = $row;}
    }

    $report = array();
    $dateconditions = "appointments.startdate <= '".TOOLS::date_to_s($stopdate)."' AND appointments.stopdate >= '".TOOLS::date_to_s($startdate)."'";

    $sql = "SELECT *, appointments.id as appointment_id, consultantappts.rapid as consultantappt_id, locations.id as location_id, loczones.id as loczone_id, locations.name as location_name, loczones.name as loczone_name, consultants.id as consultant_id, appttypes.id as appttype_id, appttypes.name as appttype_name FROM appointments, consultantappts, locations, loczones, consultants, appttypes WHERE appttypes.id IN ('".implode("','", $appttype_ids)."') AND locations.appttype_id = appttypes.id AND consultantappts.consultant_id = consultants.id AND consultantappts.appointment_id = appointments.id AND locations.id = appointments.location_id AND loczones.id = locations.loczone_id AND $dateconditions AND appointments.lockout != 'TRUE' ORDER BY appointments.special = 'repeat_removal' DESC, appointments.startdate, appointments.repeat = 'FALSE' DESC";
    $q = self::$DB->query($sql);
    $appointments_all_future = array();
    $tm_ids = array();
    while($row = self::$DB->fetch($q)){
      if(!array_key_exists($row->appointment_id, $appointments_all_future)){$appointments_all_future[$row->appointment_id] = $row;}
      if(!is_array($appointments_all_future[$row->appointment_id]->consultants)){$appointments_all_future[$row->appointment_id]->consultants = array();}
      $appointments_all_future[$row->appointment_id]->consultants[$row->consultant_id] = $row;
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


    foreach($consultants as $rc){
      $report[Consultant::select_name($rc)] = array();
      foreach($appttypes as $at){
        $report[Consultant::select_name($rc)][Appttype::select_name($at)] = array();
      }
    }

    $rc_removed_dates = array();

    //get removal dates for appointments
    foreach($appointments_all_future as $appt){
      $asdate = TOOLS::string_to_date($appt->startdate);
      $apdate = TOOLS::string_to_date($appt->stopdate);
      $astime = TOOLS::string_to_time($appt->starttime);
      $dates = TOOLS::date_range(($startdate < $asdate) ? $asdate : $startdate, ($stopdate > $apdate) ? $apdate : $stopdate);

      if($appt->special == "repeat_removal"){
        foreach($appt->consultants as $consultant){
					if(!array_key_exists($consultant->consultant_id, $rc_removed_dates) || !is_array($rc_removed_dates[$consultant->consultant_id])){
	        	$rc_removed_dates[$consultant->consultant_id] = array();
	      	}
          if(!array_key_exists($appt->removal_of, $rc_removed_dates[$consultant->consultant_id]) || !is_array($rc_removed_dates[$consultant->consultant_id][$appt->removal_of])){
            $rc_removed_dates[$consultant->consultant_id][$appt->removal_of] = array();
          }
        }

        if($appt->repeat == "TRUE"){
          foreach($dates as $date){
            if(MyFunctions::datetime_in_appt($date, $astime, $appt)){
              foreach($appt->consultants as $consultant){
                $rc_removed_dates[$consultant->consultant_id][$appt->removal_of][] = TOOLS::date_to_s($date);
              }
            }
          }
        } else {
          foreach($appt->consultants as $consultant){
            $rc_removed_dates[$consultant->consultant_id][$appt->removal_of][] = TOOLS::date_to_s($asdate);
          }
        }
      } else {
        // results were sorted so all repeat_removals were first, so we can do stuff here and not in another loop
        foreach($appt->consultants as $consultant){
          $rc_removed_dates[$consultant->consultant_id][$appt->appointment_id] = (array_key_exists($consultant->consultant_id, $rc_removed_dates) && array_key_exists($appt->appointment_id, $rc_removed_dates[$consultant->consultant_id])) ? array_unique($rc_removed_dates[$consultant->consultant_id][$appt->appointment_id]) : array();
					$report[Consultant::select_name($consultant)][Appttype::select_name($appt)][TOOLS::string_to_datetime($appt->startdate." ".$appt->starttime).$appt->appointment_id] = array("appt" => $appt, "minus" => $rc_removed_dates[$consultant->consultant_id][$appt->appointment_id]);
        }
      }

    }


    return array("output" => $report);
  }

  /**
   * Generates the count report for consultants
   *
   * @param array $consultant_ids
   * @param array $appttype_ids
   * @param integer $startdate
   * @param integer $stopdate
   * @return array
   */
  public static function consultants_count(array $consultant_ids, array $appttype_ids, $startdate, $stopdate){
    $over_totals = array();
    $totals = array();

    $consultants = array();
    $sql = "SELECT *, id as consultant_id FROM consultants WHERE id IN ('".implode("','", $consultant_ids)."') ORDER BY SUBSTRING(realname FROM (INSTR(realname, ' ')+1)) ASC";
    $q = self::$DB->query($sql);
    while($row = self::$DB->fetch($q)){
      if(!array_key_exists($row->consultant_id, $totals)){$totals[$row->consultant_id] = array();}
      if(!array_key_exists($row->consultant_id, $over_totals)){$over_totals[$row->consultant_id] = 0;}
      if(!array_key_exists($row->consultant_id, $consultants)){$consultants[$row->consultant_id] = $row;}
    }

    $appttypes = array();
    $sql = "SELECT *, id as appttype_id FROM appttypes WHERE id IN ('".implode("','", $appttype_ids)."')";
    $q = self::$DB->query($sql);
    while($row = self::$DB->fetch($q)){
      if(!array_key_exists($row->appttype_id, $appttypes)){$appttypes[$row->appttype_id] = $row;}
      foreach($consultants as $rc){
        if(!array_key_exists($row->appttype_id, $totals[$rc->consultant_id])){
          $totals[$rc->consultant_id][$row->appttype_id] = 0;
        }
      }
    }

    $report = array();
    $dateconditions = "appointments.startdate <= '".TOOLS::date_to_s($stopdate)."' AND appointments.stopdate >= '".TOOLS::date_to_s($startdate)."'";
    $sql = "SELECT consultantappts.*, appttypes.id as appttype_id, appointments.*, appointments.id as appointment_id FROM appointments, consultantappts, locations, appttypes WHERE consultantappts.consultant_id IN ('".implode("','",$consultant_ids)."') AND appttypes.id IN ('".implode("','", $appttype_ids)."') AND appttypes.id = locations.appttype_id AND locations.id = appointments.location_id AND consultantappts.appointment_id = appointments.id AND $dateconditions AND appointments.lockout != 'TRUE' AND appointments.special != 'repeat_removal'";
    $q = self::$DB->query($sql);
    while($row = self::$DB->fetch($q)){
      if($row->repeat == "TRUE"){
        $asd = TOOLS::string_to_date($row->startdate);
        $apd = TOOLS::string_to_date($row->stopdate);
        $ast = TOOLS::string_to_time($row->starttime);
        $apt = TOOLS::string_to_time($row->stoptime);
        $d2c = TOOLS::date_range(($startdate > $asd) ? $startdate : $asd, ($stopdate < $apd) ? $stopdate : $apd);
        foreach($d2c as $d){
          if(MyFunctions::datetime_in_appt($d, $ast, $row)){
            $totals[$row->consultant_id][$row->appttype_id] += 1;
            $over_totals[$row->consultant_id] += 1;
          }
        }
      } else {
        $totals[$row->consultant_id][$row->appttype_id] += 1;
        $over_totals[$row->consultant_id] += 1;
      }
    }


    foreach($consultants as $rc){
      foreach($appttypes as $at){
        $report[Consultant::select_name($rc)][Appttype::select_name($at)] = $totals[$rc->consultant_id][$at->appttype_id];
      }
      $report[Consultant::select_name($rc)]["Total"] = $over_totals[$rc->consultant_id];
    }

    kvframework_log::write_log("Report-Consultant_count output: ".serialize($report), KVF_LOG_LDEBUG);
    return array("output" => $report);
  }

  /**
   * Generates the list report for appointment types
   *
   * @param array $appttype_ids
   * @param integer $startdate
   * @param integer $stopdate
   * @return array
   */
  public static function appointments_list(array $appttype_ids, $startdate, $stopdate){

    $appttypes = array();
    $sql = "SELECT *, id as appttype_id FROM appttypes WHERE id IN ('".implode("','", $appttype_ids)."')";
    $q = self::$DB->query($sql);
    while($row = self::$DB->fetch($q)){
      if(!array_key_exists($row->appttype_id, $appttypes)){$appttypes[$row->appttype_id] = $row;}
    }

    $report = array();
    $dateconditions = "appointments.startdate <= '".TOOLS::date_to_s($stopdate)."' AND appointments.stopdate >= '".TOOLS::date_to_s($startdate)."'";

    $sql = "SELECT *, appointments.id as appointment_id, consultantappts.rapid as consultantappt_id, locations.id as location_id, loczones.id as loczone_id, locations.name as location_name, loczones.name as loczone_name, consultants.id as consultant_id, appttypes.id as appttype_id, appttypes.name as appttype_name FROM appointments, consultantappts, locations, loczones, consultants, appttypes WHERE appttypes.id IN ('".implode("','", $appttype_ids)."') AND locations.appttype_id = appttypes.id AND consultantappts.consultant_id = consultants.id AND consultantappts.appointment_id = appointments.id AND locations.id = appointments.location_id AND loczones.id = locations.loczone_id AND $dateconditions AND appointments.lockout != 'TRUE' ORDER BY appointments.special = 'repeat_removal' DESC, appointments.startdate, appointments.repeat = 'FALSE' DESC";
    $q = self::$DB->query($sql);
    $appointments_all_future = array();
    $tm_ids = array();
    while($row = self::$DB->fetch($q)){
      if(!array_key_exists($row->appointment_id, $appointments_all_future)){$appointments_all_future[$row->appointment_id] = $row;}
      if(!is_array($appointments_all_future[$row->appointment_id]->consultants)){$appointments_all_future[$row->appointment_id]->consultants = array();}
      $appointments_all_future[$row->appointment_id]->consultants[$row->consultant_id] = $row;
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


    foreach($appttypes as $at){
      $report[Appttype::select_name($at)] = array();
    }

    $removed_dates = array();

    //get removal dates for appointments
    foreach($appointments_all_future as $appt){
      $asdate = TOOLS::string_to_date($appt->startdate);
      $apdate = TOOLS::string_to_date($appt->stopdate);
      $astime = TOOLS::string_to_time($appt->starttime);
      $dates = TOOLS::date_range(($startdate < $asdate) ? $asdate : $startdate, ($stopdate > $apdate) ? $apdate : $stopdate);

      if($appt->special == "repeat_removal"){
        if(!array_key_exists($appt->removal_of, $removed_dates) || !is_array($removed_dates[$appt->removal_of])){
          $removed_dates[$appt->removal_of] = array();
        }

        if($appt->repeat == "TRUE"){
          foreach($dates as $date){
            if(MyFunctions::datetime_in_appt($date, $astime, $appt)){
              $removed_dates[$appt->removal_of][] = TOOLS::date_to_s($date);
            }
          }
        } else {
          $removed_dates[$appt->removal_of][] = TOOLS::date_to_s($asdate);
        }
      } else {
        // results were sorted so all repeat_removals were first, so we can do stuff here and not in another loop
        $removed_dates[$appt->appointment_id] = (array_key_exists($appt->appointment_id, $removed_dates)) ? array_unique($removed_dates[$appt->appointment_id]) : array();
        $report[Appttype::select_name($appt)][TOOLS::string_to_datetime($appt->startdate." ".$appt->starttime).$appt->appointment_id] = array("appt" => $appt, "minus" => $removed_dates[$appt->appointment_id]);
      }

    }


    return array("output" => $report);
  }

  /**
   * Generates the count report for appointment types
   *
   * @param array $appttype_ids
   * @param integer $startdate
   * @param integer $stopdate
   * @return array
   */
  public static function appointments_count(array $appttype_ids, $startdate, $stopdate){
    $over_total = 0;
    $totals = array();

    $appttypes = array();
    $sql = "SELECT *, id as appttype_id FROM appttypes WHERE id IN ('".implode("','", $appttype_ids)."')";
    $q = self::$DB->query($sql);
    while($row = self::$DB->fetch($q)){
      if(!array_key_exists($row->appttype_id, $totals)){$totals[$row->appttype_id] = 0;}
      if(!array_key_exists($row->appttype_id, $appttypes)){$appttypes[$row->appttype_id] = $row;}
    }

    $report = array();
    $dateconditions = "appointments.startdate <= '".TOOLS::date_to_s($stopdate)."' AND appointments.stopdate >= '".TOOLS::date_to_s($startdate)."'";
    $sql = "SELECT consultantappts.*, appttypes.id as appttype_id, appointments.*, appointments.id as appointment_id FROM appointments, consultantappts, locations, appttypes WHERE appttypes.id IN ('".implode("','", $appttype_ids)."') AND appttypes.id = locations.appttype_id AND locations.id = appointments.location_id AND consultantappts.appointment_id = appointments.id AND $dateconditions AND appointments.lockout != 'TRUE' AND appointments.special != 'repeat_removal'";
    $q = self::$DB->query($sql);
    while($row = self::$DB->fetch($q)){
      if($row->repeat == "TRUE"){
        $asd = TOOLS::string_to_date($row->startdate);
        $apd = TOOLS::string_to_date($row->stopdate);
        $ast = TOOLS::string_to_time($row->starttime);
        $apt = TOOLS::string_to_time($row->stoptime);
        $d2c = TOOLS::date_range(($startdate > $asd) ? $startdate : $asd, ($stopdate < $apd) ? $stopdate : $apd);
        foreach($d2c as $d){
          if(MyFunctions::datetime_in_appt($d, $ast, $row)){
            $totals[$row->appttype_id] += 1;
            $over_total += 1;
          }
        }
      } else {
        $totals[$row->appttype_id] += 1;
        $over_total += 1;
      }
    }


    foreach($appttypes as $at){
      $report[Appttype::select_name($at)] = $totals[$at->appttype_id];
    }


    return array("output" => $report, "Total" => $over_total);
  }

  /**
   * Generates the list report for Meta-Locations
   *
   * @param array $metaloc_ids
   * @param array $appttype_ids
   * @param integer $startdate
   * @param integer $stopdate
   * @return array
   */
  public static function metalocs_list(array $metaloc_ids, array $appttype_ids, $startdate, $stopdate){

    $metalocs = array();
    $sql = "SELECT *, id as metaloc_id FROM metalocs WHERE id IN ('".implode("','", $metaloc_ids)."') ORDER BY name";
    $q = self::$DB->query($sql);
    while($row = self::$DB->fetch($q)){
      if(!array_key_exists($row->metaloc_id, $metalocs)){$metalocs[$row->metaloc_id] = $row;}
    }

    $appttypes = array();
    $sql = "SELECT *, id as appttype_id FROM appttypes WHERE id IN ('".implode("','", $appttype_ids)."')";
    $q = self::$DB->query($sql);
    while($row = self::$DB->fetch($q)){
      if(!array_key_exists($row->appttype_id, $appttypes)){$appttypes[$row->appttype_id] = $row;}
    }

    $report = array();
    $dateconditions = "appointments.startdate <= '".TOOLS::date_to_s($stopdate)."' AND appointments.stopdate >= '".TOOLS::date_to_s($startdate)."'";

    $sql = "SELECT *, appointments.id as appointment_id, consultantappts.rapid as consultantappt_id, locations.id as location_id, loczones.id as loczone_id, locations.name as location_name, loczones.name as loczone_name, consultants.id as consultant_id, appttypes.id as appttype_id, appttypes.name as appttype_name, metalocs.id as metaloc_id, metalocs.name as metaloc_name FROM appointments, consultantappts, locations, loczones, consultants, appttypes, metalocs, metaloc_locations WHERE metalocs.id IN ('".implode("','", $metaloc_ids)."') AND  appttypes.id IN ('".implode("','", $appttype_ids)."') AND metaloc_locations.metaloc_id = metalocs.id AND locations.id = metaloc_locations.location_id AND locations.appttype_id = appttypes.id AND consultantappts.consultant_id = consultants.id AND consultantappts.appointment_id = appointments.id AND locations.id = appointments.location_id AND loczones.id = locations.loczone_id AND $dateconditions AND appointments.lockout != 'TRUE' ORDER BY appointments.special = 'repeat_removal' DESC, appointments.startdate, appointments.repeat = 'FALSE' DESC";
    $q = self::$DB->query($sql);
    $appointments_all_future = array();
    $tm_ids = array();
    while($row = self::$DB->fetch($q)){
      if(!array_key_exists($row->appointment_id, $appointments_all_future)){$appointments_all_future[$row->appointment_id] = $row;}
      if(!is_array($appointments_all_future[$row->appointment_id]->consultants)){$appointments_all_future[$row->appointment_id]->consultants = array();}
      $appointments_all_future[$row->appointment_id]->consultants[$row->consultant_id] = $row;
      if(!is_array($appointments_all_future[$row->appointment_id]->metalocs)){$appointments_all_future[$row->appointment_id]->metalocs = array();}
      $appointments_all_future[$row->appointment_id]->metalocs[$row->metaloc_id] = $row;
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


    foreach($metalocs as $ml){
      $report[Metaloc::select_name($ml)] = array();
      foreach($appttypes as $at){
        $report[Metaloc::select_name($ml)][Appttype::select_name($at)] = array();
      }
    }

    $removed_dates = array();

    //get removal dates for appointments
    foreach($appointments_all_future as $appt){
      $asdate = TOOLS::string_to_date($appt->startdate);
      $apdate = TOOLS::string_to_date($appt->stopdate);
      $astime = TOOLS::string_to_time($appt->starttime);
      $dates = TOOLS::date_range(($startdate < $asdate) ? $asdate : $startdate, ($stopdate > $apdate) ? $apdate : $stopdate);

      if($appt->special == "repeat_removal"){
        if(!array_key_exists($appt->removal_of, $removed_dates) || !is_array($removed_dates[$appt->removal_of])){
          $removed_dates[$appt->removal_of] = array();
        }

        if($appt->repeat == "TRUE"){
          foreach($dates as $date){
            if(MyFunctions::datetime_in_appt($date, $astime, $appt)){
              $removed_dates[$appt->removal_of][] = TOOLS::date_to_s($date);
            }
          }
        } else {
          $removed_dates[$appt->removal_of][] = TOOLS::date_to_s($asdate);
        }
      } else {
        // results were sorted so all repeat_removals were first, so we can do stuff here and not in another loop
        $removed_dates[$appt->appointment_id] = (array_key_exists($appt->appointment_id, $removed_dates)) ? array_unique($removed_dates[$appt->appointment_id]) : array();
        foreach($appt->metalocs as $ml){
          $report[Metaloc::select_name($ml)][Appttype::select_name($appt)][TOOLS::string_to_datetime($appt->startdate." ".$appt->starttime).$appt->appointment_id] = array("appt" => $appt, "minus" => $removed_dates[$appt->appointment_id]);
        }
      }

    }


    return array("output" => $report);
  }

  /**
   * Generates the count report for Meta-locations
   *
   * @param array $metaloc_ids
   * @param array $appttype_ids
   * @param integer $startdate
   * @param integer $stopdate
   * @return array
   */
  public static function metalocs_count(array $metaloc_ids, array $appttype_ids, $startdate, $stopdate){
    $over_totals = array();
    $totals = array();

    $metalocs = array();
    $sql = "SELECT *, id as metaloc_id FROM metalocs WHERE id IN ('".implode("','", $metaloc_ids)."') ORDER BY name";
    $q = self::$DB->query($sql);
    while($row = self::$DB->fetch($q)){
      if(!array_key_exists($row->metaloc_id, $totals)){$totals[$row->metaloc_id] = array();}
      if(!array_key_exists($row->metaloc_id, $over_totals)){$over_totals[$row->metaloc_id] = 0;}
      if(!array_key_exists($row->metaloc_id, $metalocs)){$metalocs[$row->metaloc_id] = $row;}
    }

    $appttypes = array();
    $sql = "SELECT *, id as appttype_id FROM appttypes WHERE id IN ('".implode("','", $appttype_ids)."')";
    $q = self::$DB->query($sql);
    while($row = self::$DB->fetch($q)){
      if(!array_key_exists($row->appttype_id, $appttypes)){$appttypes[$row->appttype_id] = $row;}
      foreach($metalocs as $rc){
        if(!array_key_exists($row->appttype_id, $totals[$rc->metaloc_id])){
          $totals[$rc->metaloc_id][$row->appttype_id] = 0;
        }
      }
    }

    $report = array();
    $dateconditions = "appointments.startdate <= '".TOOLS::date_to_s($stopdate)."' AND appointments.stopdate >= '".TOOLS::date_to_s($startdate)."'";
    $sql = "SELECT appttypes.id as appttype_id, appointments.*, appointments.id as appointment_id, metalocs.id as metaloc_id FROM appointments, locations, appttypes, metalocs, metaloc_locations WHERE metalocs.id IN ('".implode("','", $metaloc_ids)."') AND metaloc_locations.metaloc_id = metalocs.id AND locations.id = metaloc_locations.location_id AND appttypes.id IN ('".implode("','", $appttype_ids)."') AND appttypes.id = locations.appttype_id AND locations.id = appointments.location_id AND $dateconditions AND appointments.lockout != 'TRUE' AND appointments.special != 'repeat_removal'";
    $q = self::$DB->query($sql);
    while($row = self::$DB->fetch($q)){
      if($row->repeat == "TRUE"){
        $asd = TOOLS::string_to_date($row->startdate);
        $apd = TOOLS::string_to_date($row->stopdate);
        $ast = TOOLS::string_to_time($row->starttime);
        $apt = TOOLS::string_to_time($row->stoptime);
        $d2c = TOOLS::date_range(($startdate > $asd) ? $startdate : $asd, ($stopdate < $apd) ? $stopdate : $apd);
        foreach($d2c as $d){
          if(MyFunctions::datetime_in_appt($d, $ast, $row)){
            $totals[$row->metaloc_id][$row->appttype_id] += 1;
            $over_totals[$row->metaloc_id] += 1;
          }
        }
      } else {
        $totals[$row->metaloc_id][$row->appttype_id] += 1;
        $over_totals[$row->metaloc_id] += 1;
      }
    }


    foreach($metalocs as $rc){
      foreach($appttypes as $at){
        $report[Metaloc::select_name($rc)][Appttype::select_name($at)] = $totals[$rc->metaloc_id][$at->appttype_id];
      }
      $report[Metaloc::select_name($rc)]["Total"] = $over_totals[$rc->metaloc_id];
    }

    kvframework_log::write_log("Report-Metaloc_count output: ".serialize($report), KVF_LOG_LDEBUG);
    return array("output" => $report);
  }

  /**
   * Generates the list report for Remedy tickets
   *
   * @param array $ticket_ids
   * @return array
   */
  public static function tickets_list(array $ticket_ids){

    $report = array();

    $sql = "SELECT *, appointments.id as appointment_id, consultantappts.rapid as consultantappt_id, locations.id as location_id, loczones.id as loczone_id, locations.name as location_name, loczones.name as loczone_name, consultants.id as consultant_id, tickets.id as tm_id FROM appointments, consultantappts, locations, loczones, consultants, tickets WHERE tickets.remedy_ticket IN ('".implode("','", $ticket_ids)."') AND appointments.tm_id = tickets.id AND appointments.tm_type = 'Ticket' AND consultantappts.consultant_id = consultants.id AND consultantappts.appointment_id = appointments.id AND locations.id = appointments.location_id AND loczones.id = locations.loczone_id AND appointments.lockout != 'TRUE' ORDER BY appointments.special = 'repeat_removal' DESC, appointments.startdate, appointments.repeat = 'FALSE' DESC";
    $q = self::$DB->query($sql);
    $appointments_all_future = array();
    while($row = self::$DB->fetch($q)){
      if(!array_key_exists($row->appointment_id, $appointments_all_future)){$appointments_all_future[$row->appointment_id] = $row;}
      if(!is_array($appointments_all_future[$row->appointment_id]->consultants)){$appointments_all_future[$row->appointment_id]->consultants = array();}
      $appointments_all_future[$row->appointment_id]->consultants[$row->consultant_id] = $row;
      $appointments_all_future[$row->appointment_id]->tm = $row;
    }


    foreach($ticket_ids as $tid){
      $report[$tid] = array();
    }

    $removed_dates = array();

    //get removal dates for appointments
    foreach($appointments_all_future as $appt){
      $asdate = TOOLS::string_to_date($appt->startdate);
      $apdate = TOOLS::string_to_date($appt->stopdate);
      $astime = TOOLS::string_to_time($appt->starttime);
      $dates = TOOLS::date_range($asdate, $apdate);

      if($appt->special == "repeat_removal"){
        if(!array_key_exists($appt->removal_of, $removed_dates) || !is_array($removed_dates[$appt->removal_of])){
          $removed_dates[$appt->removal_of] = array();
        }

        if($appt->repeat == "TRUE"){
          foreach($dates as $date){
            if(MyFunctions::datetime_in_appt($date, $astime, $appt)){
              $removed_dates[$appt->removal_of][] = TOOLS::date_to_s($date);
            }
          }
        } else {
          $removed_dates[$appt->removal_of][] = TOOLS::date_to_s($asdate);
        }
      } else {
        // results were sorted so all repeat_removals were first, so we can do stuff here and not in another loop
        $removed_dates[$appt->appointment_id] = (array_key_exists($appt->appointment_id, $removed_dates)) ? array_unique($removed_dates[$appt->appointment_id]) : array();
        $report[$appt->remedy_ticket][TOOLS::string_to_datetime($appt->startdate." ".$appt->starttime).$appt->appointment_id] = array("appt" => $appt, "minus" => $removed_dates[$appt->appointment_id]);
      }

    }


    return array("output" => $report);
  }

  /**
   * Generates the count report for users
   *
   * @param array $usernames
   * @param integer $startdate
   * @param integer $stopdate
   * @return array
   */
  public static function users_count(array $usernames, $startdate, $stopdate){
    $totals = array();

    foreach($usernames as $un){
      if(!array_key_exists($un, $totals)){$totals[$un] = 0;}
    }

    $dateconditions = "appointments.startdate <= '".TOOLS::date_to_s($stopdate)."' AND appointments.stopdate >= '".TOOLS::date_to_s($startdate)."'";
    $sql = "SELECT consultantappts.*, appointments.*, appointments.id as appointment_id FROM appointments, consultantappts, locations WHERE appointments.appointment_user IN ('".implode("','", $usernames)."') AND locations.id = appointments.location_id AND consultantappts.appointment_id = appointments.id AND $dateconditions AND appointments.lockout != 'TRUE' AND appointments.special != 'repeat_removal'";
    $q = self::$DB->query($sql);
    while($row = self::$DB->fetch($q)){
      if($row->repeat == "TRUE"){
        $asd = TOOLS::string_to_date($row->startdate);
        $apd = TOOLS::string_to_date($row->stopdate);
        $ast = TOOLS::string_to_time($row->starttime);
        $apt = TOOLS::string_to_time($row->stoptime);
        $d2c = TOOLS::date_range(($startdate > $asd) ? $startdate : $asd, ($stopdate < $apd) ? $stopdate : $apd);
        foreach($d2c as $d){
          if(MyFunctions::datetime_in_appt($d, $ast, $row)){
            $totals[$row->appointment_user] += 1;
          }
        }
      } else {
        $totals[$row->appointment_user] += 1;
      }
    }

    return array("output" => $totals);
  }

  /**
   * Generates the list report for users
   *
   * @param array $usernames
   * @param integer $startdate
   * @param integer $stopdate
   * @return array
   */
  public static function users_list(array $usernames, $startdate, $stopdate){

    $report = array();
    $dateconditions = "appointments.startdate <= '".TOOLS::date_to_s($stopdate)."' AND appointments.stopdate >= '".TOOLS::date_to_s($startdate)."'";

    $sql = "SELECT *, appointments.id as appointment_id, consultantappts.rapid as consultantappt_id, locations.id as location_id, loczones.id as loczone_id, locations.name as location_name, loczones.name as loczone_name, consultants.id as consultant_id FROM appointments, consultantappts, locations, loczones, consultants WHERE appointments.appointment_user IN ('".implode("','", $usernames)."') AND consultantappts.consultant_id = consultants.id AND consultantappts.appointment_id = appointments.id AND locations.id = appointments.location_id AND loczones.id = locations.loczone_id AND $dateconditions AND appointments.lockout != 'TRUE' ORDER BY appointments.special = 'repeat_removal' DESC, appointments.startdate, appointments.repeat = 'FALSE' DESC";
    $q = self::$DB->query($sql);
    $appointments_all_future = array();
    $tm_ids = array();
    while($row = self::$DB->fetch($q)){
      if(!array_key_exists($row->appointment_id, $appointments_all_future)){$appointments_all_future[$row->appointment_id] = $row;}
      if(!is_array($appointments_all_future[$row->appointment_id]->consultants)){$appointments_all_future[$row->appointment_id]->consultants = array();}
      $appointments_all_future[$row->appointment_id]->consultants[$row->consultant_id] = $row;
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


    foreach($usernames as $tid){
      $report[$tid] = array();
    }

    $removed_dates = array();

    //get removal dates for appointments
    foreach($appointments_all_future as $appt){
      $asdate = TOOLS::string_to_date($appt->startdate);
      $apdate = TOOLS::string_to_date($appt->stopdate);
      $astime = TOOLS::string_to_time($appt->starttime);
      $dates = TOOLS::date_range(($startdate < $asdate) ? $asdate : $startdate, ($stopdate > $apdate) ? $apdate : $stopdate);

      if($appt->special == "repeat_removal"){
        if(!array_key_exists($appt->removal_of, $removed_dates) || !is_array($removed_dates[$appt->removal_of])){
          $removed_dates[$appt->removal_of] = array();
        }

        if($appt->repeat == "TRUE"){
          foreach($dates as $date){
            if(MyFunctions::datetime_in_appt($date, $astime, $appt)){
              $removed_dates[$appt->removal_of][] = TOOLS::date_to_s($date);
            }
          }
        } else {
          $removed_dates[$appt->removal_of][] = TOOLS::date_to_s($asdate);
        }
      } else {
        // results were sorted so all repeat_removals were first, so we can do stuff here and not in another loop
        $removed_dates[$appt->appointment_id] = (array_key_exists($appt->appointment_id, $removed_dates)) ? array_unique($removed_dates[$appt->appointment_id]) : array();
        $report[$appt->appointment_user][TOOLS::string_to_datetime($appt->startdate." ".$appt->starttime).$appt->appointment_id] = array("appt" => $appt, "minus" => $removed_dates[$appt->appointment_id]);
      }

    }


    return array("output" => $report);
  }

}

?>
