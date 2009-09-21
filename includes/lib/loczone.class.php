<?php
/**
 * KvScheduler - Location Zone Model
 * @package KvScheduler
 * @subpackage Lib.Models
 * @author Greg McWhirter <gsmcwhirter@gmail.com>
 * @version 1.0
 */

/**
 * Location Zone database model
 *
 * @package KvScheduler
 * @subpackage Lib.Models
 */
abstract class Loczone extends kvframework_base implements iDBWrapper{
  /**
   * Associated database table
   *
   */
  const Table = "loczones";

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
   * Update an existing database table
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

    return self::$DB->process($q);
    /*if(self::destroy_locations($ids)){
      return self::$DB->process($q);
    } else {
      return false;
    }*/
  }

  /**
   * Destroy callback - destroys associated locations
   *
   * @param mixed $par_ids
   * @return boolean
   */
  protected static function destroy_locations($par_ids){
    $cond = (is_array($par_ids)) ? "IN ('".implode("','", $par_ids)."')" : "= '".$par_ids."'";
    $sql = "SELECT id as location_id FROM locations WHERE loczone_id ".$cond;
    $q = self::$DB->query($sql);
    $ids = array();
    while($row = self::$DB->fetch($q)){
      $ids[] = $row->location_id;
    }

    return Location::destroy($ids);
  }

  /**
   * Generate a descriptive name for a database record
   *
   * @param kvframework_db_object $self
   * @return string
   */
  public static function select_name(kvframework_db_object $self){
    return TOOLS::escape_quotes($self->name);
  }

  /**
   * Validate input for creating or updating an database record
   *
   * @param array $params
   * @param string $type
   */
  protected static function do_validations(array $params, $type = "create"){
    try{
      if($type == "create"){
        self::validate_presence_of(array("name","potentialh","potentialv"), $params);
      }
      if(array_key_exists("potentialh", $params)){
        self::validate_callback_on(array("potentialh"), $params, "callback_valid_potential", " is not a valid potential.");
      }
      if(array_key_exists("potentialv", $params)){
        self::validate_callback_on(array("potentialv"), $params, "callback_valid_potential", " is not a valid potential.");
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
