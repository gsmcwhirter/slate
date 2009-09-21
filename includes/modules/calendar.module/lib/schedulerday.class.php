<?php
/**
 * KvScheduler - Scheduler Day Generator
 * @package KvScheduler
 * @subpackage Modules.Lib
 * @author Greg McWhirter <gsmcwhirter@gmail.com>
 * @version 1.0
 */

/**
 * Scheduler day array generator
 *
 * @package KvScheduler
 * @subpackage Modules.Lib
 */
abstract class SchedulerDay extends kvframework_base{

  /**
   * Generate a SchedulerDay array
   *
   * @param integer $date
   * @return array
   */
  public static function newsd($date){
    $consultants = array();
    $sql = "SELECT consultants.id as consultant_id FROM consultants WHERE status = 'active' ORDER BY consultants.staff='true' ASC, ".CONFIG::SQL_REALNAME_ORDER_CLAUSE;
    $q = self::$DB->query($sql);
    $i = 0;
    while($row = self::$DB->fetch($q)){
      $consultants[$i] = $row->consultant_id;
      $i++;
    }

    $tstart = microtime(true);
    $data = MyFunctions::appointmentsDataFor($consultants, array($date));
    $day_ophours =& $data["ophours"]->blocks[$date];
    $start = $day_ophours["start"];
    $stop = $day_ophours["stop"];
    $oh_intervals =& $day_ophours["intervals"];
    $tstop = microtime(true);
    kvframework_log::write_log("*****SD ADF Get: ".($tstop - $tstart)."s");

    $self = array();
    $self[] = SchedulerPerson::newsp($start, $stop, $date, $oh_intervals);
    $max_rc = count($consultants);
    for($i = 0; $i < $max_rc; $i++){
      $self[] = SchedulerPerson::newsp($start, $stop, $date, $oh_intervals);
    }

    $tstart = microtime(true);
    SchedulerPerson::loadlabel($self[0], $date, $start);
    $max_rc = count($consultants);
    for($i = 0; $i < $max_rc; $i++){
      SchedulerPerson::loaddata($self[$i+1], $date, $start, $consultants[$i], $data["ophours"], $data["rchours"], $data["appts"]);
    }
    $tstop = microtime(true);
    kvframework_log::write_log("*****SD Data Set: ".($tstop - $tstart)."s");

    return array($self, $consultants, $data["rchours"]->consultants);
  }

}
?>
