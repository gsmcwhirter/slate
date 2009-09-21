<?php
/**
 * KvScheduler - ConsultantAppt Model
 * @package KvScheduler
 * @subpackage Lib.Models
 * @author Greg McWhirter <gsmcwhirter@gmail.com>
 * @version 1.0
 */

/**
 * Consultant Appointment assocation database model
 *
 * @package KvScheduler
 * @subpackage Lib.Models
 */
abstract class Consultantappt extends kvframework_base implements iDBWrapper{
  /**
   * Associated database table
   *
   */
  const Table = "consultantappts";

  /**
   * Create a new database record
   *
   * @param array $params
   * @return mixed
   */
  public static function create(array $params){
    self::do_validations($params);
    if(!self::is_errors()){
      $q = self::$DB->insert_query(array(self::Table));
      $q->fields = $params;
      $id = self::$DB->process($q);
      if($id){
        /* update weekly hour count */
        self::update_weekly_hour_count($params['consultant_id'], $params['appointment_id']);
        return $id;
      } else {
        self::throwError(self::$DB->error("text"));
        return false;
      }
    } else {
      return false;
    }
  }

  /**
   * Update an existing database record
   *
   * @param integer $id
   * @param array $attribs
   * @return boolean
   */
  public static function update_attributes($id, array $attribs){
    self::do_validations($attribs, "update");
    if(!self::is_errors()){
      $q = self::$DB->update_query(array(self::Table));
      $q->fields = $attribs;
      $q->conditions = "rapid = '".$id."'";
      $q->limit = "1";
      if(self::$DB->process($q)){
        return true;
      } else {
        self::throwError(self::$DB->error("text"));
        return false;
      }
    } else {
      return false;
    }
  }

  /**
   * Destroy existing database record(s)
   *
   * @param mixed $ids
   * @return boolean
   */
  public static function destroy($ids){
    $q = self::$DB->delete_query(array(self::Table));
    if(is_array($ids) && count($ids) > 0){
      $q->conditions .= "rapid IN ('".implode("','", $ids)."')";
    } elseif(!is_array($ids)) {
      $q->conditions .= "rapid = '".$ids."'";
    } else {
      return true;
    }

    $aids = array();
    $sql = "SELECT appointment_id, consultant_id FROM consultantappts WHERE ".$q->conditions;
    $q2 = self::$DB->query($sql);
    while($row = self::$DB->fetch($q2)){
      $aids[] = $row;
    }

    $delres = self::$DB->process($q);

    if($delres){
      foreach($aids as $aid){
        self::update_weekly_hour_count($aid->consultant_id, $aid->appointment_id, true);
      }
    }

    return $delres;
  }

  /**
   * Generate a descriptive name for the database record
   *
   * @param kvframework_db_object $self
   * @return string
   */
  public static function select_name(kvframework_db_object $self){
    return TOOLS::escape_quotes((string)$self->consultantappt_id);
  }

  /**
   * Validate data for use in creating or updating a database record
   *
   * @param array $params
   * @param string $type
   */
  protected static function do_validations(array $params, $type = "create"){
    try{
      if($type == "create"){
        self::validate_presence_of(array("consultant_id", "appointment_id"), $params);
      }
      if(array_key_exists("confirmed", $params)){self::validate_inclusion_of("confirmed", $params["confirmed"], array("TRUE","FALSE"));}
      self::validate_resolve();
    } catch(validation_exception $e){
      foreach($e->errors as $err){
        self::throwError($err);
      }
    }
  }

  /**
   * Update the weekly hours count on the creation/modification/destruction of a consultantappointment
   *
   * @param integer $consultant_id
   * @param integer $appt_id
   * @param boolean $force_destroy
   * @return boolean
   */
  protected static function update_weekly_hour_count($consultant_id, $appt_id, $force_destroy = false){
    $sql = "SELECT * FROM appointments WHERE id = '".$appt_id."' LIMIT 1";
    $q = self::$DB->query($sql);
    if(self::$DB->rows($q) == 1){
      $appt = self::$DB->fetch($q);
      if($appt->lockout == "TRUE"){return true;}
      $st = TOOLS::string_to_time($appt->starttime);
      $pt = TOOLS::string_to_time($appt->stoptime);
      $dates = MyFunctions::occurrences_of_appt($appt);
      foreach($dates as $date){
        $wd = TOOLS::x_days_since((-1 * TOOLS::wday_for($date)), $date); //the most recent sunday
        $sql = "SELECT * FROM consultantweeklyhours WHERE consultant_id = '".$consultant_id."' AND week_date = '".TOOLS::date_to_s($wd)."' LIMIT 1";
        $q = self::$DB->query($sql);
        $hours = TOOLS::hours_diff($st, $pt);
        if(self::$DB->rows($q) == 1){
          $sql = "UPDATE consultantweeklyhours SET week_hours = week_hours ".(($appt->special == "repeat_removal" || $force_destroy) ? "-" : "+")." ".$hours." WHERE consultant_id = '".$consultant_id."' AND week_date = '".TOOLS::date_to_s($wd)."' LIMIT 1";
        } else {
          $sql = "INSERT INTO consultantweeklyhours (consultant_id,week_date,week_hours) VALUES ('".$consultant_id."','".TOOLS::date_to_s($wd)."','".$hours."')";
        }
        $q = self::$DB->query($sql);
        if(self::$DB->affected() == 1){
          self::throwMessage("Consultant hours count updated successfully.");
          return true;
        } else {
          self::throwError("Consultant hours count not updated successfully.");
          return false;
        }
      }
    } else {
      self::throwError("Unable to find appointment in the database.");
      return false;
    }
  }
}
?>
