<?php
/**
 * KvScheduler - Ticket Model
 * @package KvScheduler
 * @subpackage Lib.Models
 * @author Greg McWhirter <gsmcwhirter@gmail.com>
 * @version 1.0
 */

/**
 * Ticket database model
 *
 * @package KvScheduler
 * @subpackage Lib.Models
 */
abstract class Ticket extends kvframework_base implements iDBWrapper{
  /**
   * Associated database table
   *
   */
  const Table = "tickets";
  /**
   * Phone validation regular expression
   */
  const phone_regexp = '/^(([0-9]{3}[ \.\-]{1}[0-9]{3}[ \.\-]{1}[0-9]{4})|([0-9]{3}[ \.\-]{1}[0-9]{4})|([0-9]{1}[ \.\-]{1}[0-9]{4}))$/';

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

    if(self::destroy_appointments($ids)){
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
  protected static function destroy_appointments($par_ids){
    $cond = (is_array($par_ids)) ? "IN ('".implode("','", $par_ids)."')" : "= '".$par_ids."'";
    $sql = "SELECT id as appointment_id FROM appointments WHERE tm_id ".$cond." AND tm_type = 'Ticket'";
    $q = self::$DB->query($sql);
    $ids = array();
    while($row = self::$DB->fetch($q)){
      $ids[] = $row->appointment_id;
    }

    return Appointment::destroy($ids);
  }

  /**
   * Generate a descriptive name for a database record
   *
   * @param kvframework_db_object $self
   * @return string
   */
  public static function select_name(kvframework_db_object $self){
    return TOOLS::escape_quotes($self->ticket_id);
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
        self::validate_presence_of(array("person","remedy_ticket","description"), $params);
        if((!array_key_exists("phone", $params) || is_null($params["phone"])) && (!array_key_exists("altphone", $params) || is_null($params["altphone"]))){
          self::validate_presence_of(array("phone"), $params, "or altphone is mandatory");
        }
      }

      if(array_key_exists("remedy_ticket", $params)){
        self::validate_callback_on(array("remedy_ticket"), $params, "callback_remedy_ticket", " has invalid format.");
      }

      if(array_key_exists("phone", $params) && !is_null($params["phone"])){
        self::validate_callback_on(array("phone"), $params, "callback_phone", " has invalid format.");
      }
      if(array_key_exists("altphone", $params) && !is_null($params["altphone"])){
        self::validate_callback_on(array("altphone"), $params, "callback_phone", " has invalid format.");
      }

      if(array_key_exists("description", $params)){
        self::validate_length_of("description", $params["description"], array("min" => 1), "must have non-zero length.");
      }
      if(array_key_exists("person", $params)){
        self::validate_length_of("person", $params["person"], array("min" => 1), "must have non-zero length.");
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
