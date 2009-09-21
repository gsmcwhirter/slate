<?php
/**
 * KvScheduler - Metaloc Model
 * @package KvScheduler
 * @subpackage Lib.Models
 * @author Greg McWhirter <gsmcwhirter@gmail.com>
 * @version 1.0
 */

/**
 * Metaloc database model
 *
 * @package KvScheduler
 * @subpackage Lib.Models
 */
abstract class Metaloc extends kvframework_base implements iDBWrapper{
  /**
   * Associated database table
   *
   */
  const Table = "metalocs";

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

    if(self::remove_loc_refs($ids)){
      return self::$DB->process($q);
    } else {
      return false;
    }
  }

  /**
   * Destroy callback -  removes location associations
   *
   * @param mixed $par_ids
   * @return boolean
   */
  protected static function remove_loc_refs($par_ids){
    $cond = (is_array($par_ids)) ? "IN ('".implode("','", $par_ids)."')" : "= '".$par_ids."'";
    $sql = "DELETE FROM metaloc_locations WHERE metaloc_id ".$cond;
    return self::$DB->query($sql);
  }

  /**
   * Generates a descriptive name for the database record
   *
   * @param kvframework_db_object $self
   * @return string
   */
  public static function select_name(kvframework_db_object $self){
    return TOOLS::escape_quotes(($self->metaloc_name) ? $self->metaloc_name : $self->name);
  }

  /**
   * Validates data for creation and updating of records
   *
   * @param array $params
   * @param string $type
   */
  protected static function do_validations(array $params, $type = "create"){
    try{
      if($type == "create"){
        self::validate_presence_of(array("name","universal"), $params);
      }
      if(array_key_exists("universal", $params)){
        self::validate_inclusion_of("universal", $params["universal"], array("TRUE","FALSE"));
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
