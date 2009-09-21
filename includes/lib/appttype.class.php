<?php
/**
 * KvScheduler - Appointment type model
 * @package KvScheduler
 * @subpackage Lib.Models
 * @author Greg McWhirter <gsmcwhirter@gmail.com>
 * @version 1.0
 */

/**
 * Appointment type model
 * @package KvScheduler
 * @subpackage Lib.Models
 */
abstract class Appttype extends kvframework_base implements iDBWrapper{
  /**
   * Database table for the model
   *
   */
  const Table = "appttypes";

  /**
   * Does nothing
   *
   * @param array $params
   * @return boolean true
   */
  public static function create(array $params){
    return false;
  }

  /**
   * Update a database record
   *
   * @param integer $id
   * @param array $attribs
   * @return boolean
   */
  public static function update_attributes($id, array $attribs){
    if(array_key_exists("max_concurrent_appts", $attribs)){$attribs["max_concurrent_appts"] = (int)$attribs["max_concurrent_appts"];}
    self::do_validations($attribs, "update");
    if(!self::is_errors()){
      $q = self::$DB->update_query(array(self::Table));
      $q->fields = $attribs;
      $q->conditions = "id = '".$id."'";
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
   * Does nothing
   *
   * @param mixed $ids
   * @return boolean false
   */
  public static function destroy($ids){
    return false;
  }

  /**
   * Provide a descriptive name for a database record
   *
   * @param kvframework_db_object $self
   * @return string
   */
  public static function select_name(kvframework_db_object $self){
    return TOOLS::escape_quotes(($self->appttype_name) ? $self->appttype_name : $self->name);
  }

  /**
   * Return an appointment type key for use with config variables
   *
   * @param mixed $appttype
   * @return string
   */
  public static function ats_key($appttype){
    foreach(array("at_generic", "at_comcon","at_wireless","at_other") as $k){
      $val = application_site_class::config_vals($k);
      if($val == $appttype->appttype_id){
        return substr($k, 3);
      }
    }
  }

  /**
   * Validate creation and updates for records
   *
   * @param array $params
   * @param string $type
   */
  protected static function do_validations(array $params, $type = "create"){
    try{
      if($type == "create"){
        self::validate_presence_of(array("name","weekdays_allowed","min_appt_length"), $params);
      }
      if(array_key_exists("name", $params)){
        self::validate_length_of("name", $params["name"], array("min" => 1), "cannot be empty.");
      }
      if(array_key_exists("tm_class", $params)){
        self::validate_inclusion_of("tm_class", $params["tm_class"], array("Ticket","Meeting,Meecket"), "is invalid");
      }
      #if(array_key_exists("max_concurrent_appts", $params)){
      #  self::validate_callback_on(array("max_concurrent_appts"), $params, "is_int", "must be an integer");
      #}
      if(array_key_exists("weekdays_allowed", $params)){
        self::validate_callback_on(array("weekdays_allowed"), $params, "callback_weekdays_allowed", "is not well formatted.");
      }
      if(array_key_exists("min_appt_length", $params)){
        self::validate_callback_on(array("min_appt_length"), $params, "is_int", "must be an integer.");
      }
      self::validate_resolve();
    } catch(validation_exception $e){
      foreach($e->errors as $err){
        self::throwError($err);
      }
    }
  }

}
?>
