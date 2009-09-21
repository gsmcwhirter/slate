<?php
/**
 * KvScheduler - Location Model
 * @package KvScheduler
 * @subpackage Lib.Models
 * @author Greg McWhirter <gsmcwhirter@gmail.com>
 * @version 1.0
 */

/**
 * Model for a Location record
 *
 * @package KvScheduler
 * @subpackage Lib.Models
 */
abstract class Location extends kvframework_base implements iDBWrapper{
  /**
   * Associated database table
   *
   */
  const Table = "locations";

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
   * Destroy existing database record(s)
   *
   * @param mixed $ids
   * @return boolean
   */
  public static function destroy($ids){
    $q = self::$DB->delete_query(array(self::Table));
    if(is_array($ids) && count($ids) > 0){
      $q->conditions .= "id IN ('".implode("','", $ids)."')";
    } elseif(!is_array($ids)) {
      $q->conditions .= "id = '".$ids."'";
    } else {
      return true;
    }

    if(/*self::destroy_appointments($ids) &&*/ self::destroy_metaloc_locations($ids)){
      return self::$DB->process($q);
    } else {
      return false;
    }
  }

  /**
   * Destroy callback - destroy associated appointments
   *
   * @param mixed $par_ids
   * @return boolean
   */
  /*protected static function destroy_appointments($par_ids){
    $cond = (is_array($par_ids)) ? "IN ('".implode("','", $par_ids)."')" : "= '".$par_ids."'";
    $sql = "SELECT id as appointment_id FROM appointments WHERE location_id ".$cond;
    $q = self::$DB->query($sql);
    $ids = array();
    while($row = self::$DB->fetch($q)){
      $ids[] = $row->appointment_id;
    }

    return Appointment::destroy($ids);
  }*/

  /**
   * Destroy callback - destroy metaloc associations
   *
   * @param mixed $par_ids
   * @return boolean
   */
  protected static function destroy_metaloc_locations($par_ids){
    $cond = (is_array($par_ids)) ? "IN ('".implode("','", $par_ids)."')" : "= '".$par_ids."'";
    $sql = "DELETE FROM metaloc_locations WHERE location_id ".$cond;
    return self::$DB->query($sql);
  }

  /**
   * Generate a descriptive name for a database record
   *
   * @param kvframework_db_object $self
   * @return string
   */
  public static function select_name(kvframework_db_object $self){
    return TOOLS::escape_quotes($self->location_name." (".$self->loczone_name."[".$self->appttype_id."])");
  }

  /**
   * Validate data for creating / updating a database record
   *
   * @param array $params
   * @param string $type
   */
  protected static function do_validations(array $params, $type = "create"){
    try{
      if($type == "create"){
        self::validate_presence_of(array("name","appttype_id","loczone_id","restrict_gender"), $params);
      }
      if(array_key_exists("appttype_id", $params)){
        self::validate_callback_on(array("appttype_id"), $params, "callback_valid_id", " is not a positive integer.");
      }
      if(array_key_exists("location_id", $params)){
        self::validate_callback_on(array("location_id"), $params, "callback_valid_id", " is not a positive integer.");
      }
      if(array_key_exists("restrict_gender", $params)){
        self::validate_inclusion_of("restrict_gender", $params["restrict_gender"], array("M","F","FALSE"), "is not valid.");
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
