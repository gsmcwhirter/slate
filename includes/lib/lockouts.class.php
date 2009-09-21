<?php
/**
 * KvScheduler - Location Model
 * @package KvScheduler
 * @subpackage Lib
 * @author Greg McWhirter <gsmcwhirter@gmail.com>
 * @version 1.0
 */

/**
 * Lockout control functions
 *
 * @package KvScheduler
 * @subpackage Lib
 */
abstract class Lockouts extends kvframework_base{

  /**
   * Destroy lockouts made by a user
   *
   * @param string $user
   * @return boolean
   */
  public static function destroy($user){
    $q = self::$DB->delete_query(array("appointments"));
    $q->conditions = "lockout = 'TRUE' AND lockout_user = '".$user."'";
    return self::$DB->process($q);
  }

  /**
   * Create a lockout
   *
   * @param integer $consultant_id
   * @param integer $start
   * @param integer $stop
   * @param integer $date
   * @param string $user
   * @param mixed $ignore_appt_id
   * @return boolean
   */
  public static function create($consultant_id, $start, $stop, $date, $user, $ignore_appt_id = null){
    if(self::exist($consultant_id, $start, $stop, $date, $user)){
      return true;
    } else {
      $apd = MyFunctions::appointmentsDataFor(array($consultant_id), array($date));

      if(Consultant::isFreeOn($consultant_id, $date, $start, $stop, $apd["rchours"], $apd["appts"], $user, $ignore_appt_id)){
        $id = Appointment::create(array("starttime" => $start, "stoptime" => $stop, "startdate" => $date, "stopdate" => $date, "lockout" => "TRUE", "lockout_user" => $user, "timestamp" => time()));
        if($id){
          $id2 = Consultantappt::create(array("consultant_id" => $consultant_id, "appointment_id" => $id));
          if($id2){
            return true;
          } else{
            self::throwError(self::$DB->error("text"));
            return false;
          }
        } else {
          self::throwError(self::$DB->error("text"));
          return false;
        }
      } else {
        return false;
      }
    }
  }

  /**
   * Check if a lockout exists
   *
   * @param integer $consultant_id
   * @param integer $start
   * @param integer $stop
   * @param integer $date
   * @param string $user
   * @return boolean
   */
  public static function exist($consultant_id, $start, $stop, $date, $user){
    $sql = "SELECT * FROM appointments, consultantappts WHERE consultantappts.appointment_id = appointments.id AND consultantappts.consultant_id = '".$consultant_id."' AND appointments.starttime <= '".TOOLS::time_to_s($start)."' AND appointments.stoptime >= '".TOOLS::time_to_s($stop)."' AND appointments.startdate = '".TOOLS::date_to_s($date)."' AND appointments.stopdate = '".TOOLS::date_to_s($date)."' AND appointments.lockout = 'TRUE' AND appointments.lockout_user = '".$user."' LIMIT 1";
    $q = self::$DB->query($sql);
    if(self::$DB->rows($q) == 1){
      return true;
    } else {
      return false;
    }
  }

}
?>
