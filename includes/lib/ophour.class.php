<?php
/**
 * KvScheduler - Operating hour Model
 * @package KvScheduler
 * @subpackage Lib.Models
 * @author Greg McWhirter <gsmcwhirter@gmail.com>
 * @version 1.0
 */

/**
 * Operating Hour database model
 *
 * @package KvScheduler
 * @subpackage Lib.Models
 */
abstract class Ophour extends kvframework_base implements iDBWrapper{
  /**
   * Associated database table
   *
   */
  const Table = "ophours";

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
      $params["starttime"] = TOOLS::time_to_s($params["starttime"]);
      $params["stoptime"] = TOOLS::time_to_s($params["stoptime"]);
      $params["startdate"] = TOOLS::date_to_s($params["startdate"]);
      $params["stopdate"] = TOOLS::date_to_s($params["stopdate"]);
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
      if(array_key_exists("starttime", $attribs)){$attribs["starttime"] = TOOLS::time_to_s($attribs["starttime"]);}
      if(array_key_exists("stoptime", $attribs)){$attribs["stoptime"] = TOOLS::time_to_s($attribs["stoptime"]);}
      if(array_key_exists("startdate", $attribs)){$attribs["startdate"] = TOOLS::date_to_s($attribs["startdate"]);}
      if(array_key_exists("stopdate", $attribs)){$attribs["stopdate"] = TOOLS::date_to_s($attribs["stopdate"]);}
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

    return self::$DB->process($q);
  }

  /**
   * Generate a descriptive name for a database record
   *
   * @param kvframework_db_object $self
   * @return string
   */
  public static function select_name(kvframework_db_object $self){
    return TOOLS::escape_quotes($self->ophour_id);
  }

  /**
   * Validate data used to create and update database records
   *
   * @param array $params
   * @param string $type
   */
  protected static function do_validations(array $params, $type = "create"){
    try{
      if($type == "create"){
        self::validate_presence_of(array("starttime","stoptime","startdate","stopdate","timestamp","repetition","special"), $params);
      }
      if(array_key_exists("starttime", $params) || array_key_exists("stoptime", $params)){
        self::validate_callback_on(array("starttime","stoptime"), $params, "is_int", "has invalid format");
        self::validate_callback_on(array("stoptime"), $params, "callback_datetime_check", "must be after starttime", array($params["starttime"], "strict"));
      }
      if(array_key_exists("startdate", $params) || array_key_exists("stopdate", $params)){
        self::validate_callback_on(array("startdate","stopdate"), $params, "is_int", "has invalid format");
        self::validate_callback_on(array("stopdate"), $params, "callback_datetime_check", "must be after startdate", array($params["startdate"]));
      }
      if(array_key_exists("timestamp", $params)){
        self::validate_callback_on(array("timestamp"), $params, "is_int", "has invalid format");
        self::validate_callback_on(array("timestamp"), $params, "callback_valid_id", "must be greater than 0.");
      }
      if(array_key_exists("special", $params)){
        self::validate_inclusion_of("special",$params["special"],array("regular","delete"));
      }
      if(array_key_exists("repetition", $params)){
        self::validate_callback_on(array("repetition"), $params, "callback_weekdays_allowed", "is not well formatted.");
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
