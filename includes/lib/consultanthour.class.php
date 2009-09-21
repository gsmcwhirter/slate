<?php
/**
 * KvScheduler - ConsultantHour Model
 * @package KvScheduler
 * @subpackage Lib.Models
 * @author Greg McWhirter <gsmcwhirter@gmail.com>
 * @version 1.0
 */

/**
 * Consultant Hour database model
 *
 * @package KvScheduler
 * @subpackage Lib.Models
 */
abstract class Consultanthour extends kvframework_base implements iDBWrapper{
  /**
   * Associated database table
   *
   */
  const Table = "consultanthours";

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
      kvframework_log::write_log("Consultanthour_Create: ".serialize($q), KVF_LOG_LDEBUG);
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
    return TOOLS::escape_quotes($self->consultanthour_id);
  }

  /**
   * Validate data to be used for creating or updating a database record
   *
   * @param array $params
   * @param string $type
   */
  protected static function do_validations(array $params, $type = "create"){
    try{
      if($type == "create"){
        self::validate_presence_of(array("starttime","stoptime","startdate","stopdate","consultant_id","timestamp","htype","htype2","repeat","oncall"), $params);
      }
      if(array_key_exists("starttime", $params) || array_key_exists("stoptime", $params)){
        self::validate_callback_on(array("starttime","stoptime"), $params, "is_int", "has invalid format");
        self::validate_callback_on(array("stoptime"), $params, "callback_datetime_check", "must be after starttime", array($params["starttime"]));
      }
      if(array_key_exists("startdate", $params) || array_key_exists("stopdate", $params)){

        self::validate_callback_on(array("startdate","stopdate"), $params, "is_int", "has invalid format");
        self::validate_callback_on(array("stopdate"), $params, "callback_datetime_check", "must be after startdate", array($params["startdate"]));
      }
      if(array_key_exists("timestamp", $params)){
        self::validate_callback_on(array("timestamp"), $params, "is_int", "has invalid format");
        self::validate_callback_on(array("timestamp"), $params, "callback_valid_id", "must be greater than 0.");
      }
      if(array_key_exists("consultant_id", $params)){
        self::validate_callback_on(array("timestamp"), $params, "is_int", "has invalid format");
        self::validate_exists_in_db("consultant_id", array("id" => $params["consultant_id"]), "consultants","does not reflect an actual consultant.");
      }
      if(array_key_exists("htype", $params)){
        self::validate_inclusion_of("htype",$params["htype"],array("repeat","delete","once"));
      }
      if(array_key_exists("htype2", $params)){
        self::validate_inclusion_of("htype2",$params["htype2"],array("regular","request"));
      }
      if(array_key_exists("oncall", $params)){
        self::validate_inclusion_of("oncall",$params["oncall"],array("TRUE","FALSE"));
      }
      if(array_key_exists("repeat", $params)){
        self::validate_callback_on(array("repeat"), $params, "callback_weekdays_allowed", "is not well formatted.");
      }
      self::validate_resolve();
    } catch(validation_exception $e){
      foreach($e->errors as $err){
        self::throwError($err);
      }
      kvframework_log::write_log("Validation_errors: ".serialize(self::$MESSAGES), KVF_LOG_LDEBUG);
    }
  }

}
?>
