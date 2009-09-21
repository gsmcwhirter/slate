<?php
/**
 * KvScheduler - LocTag Model
 * @package KvScheduler
 * @subpackage Lib.Models
 * @author Greg McWhirter <gsmcwhirter@gmail.com>
 * @version 1.0
 */

/**
 * LocTag database model
 *
 * @package KvScheduler
 * @subpackage Lib.Models
 */
abstract class Loctag extends kvframework_base implements iDBWrapper{
  /**
   * Associated database table
   *
   */
  const Table = "loctags";

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

    if(self::remove_tags($ids)){
      return self::$DB->process($q);
    } else {
      return false;
    }
  }

  /**
   * Destroy callback - Make sure every consultant still has a tag of some sort
   *
   * @param mixed $par_ids
   * @return boolean
   */
  protected static function remove_tags($par_ids){
    $cond = (is_array($par_ids)) ? "IN ('".implode("','", $par_ids)."')" : "= '".$par_ids."'";
    #$sql = "SELECT id as tag_id FROM tags WHERE NOT(id ".$cond.") LIMIT 1";
    #$q = self::$DB->query($sql);
    #if(self::$DB->rows($q) == 1){
    #  $alt = self::$DB->fetch($q);
    $sql = "DELETE FROM location_loctags WHERE loctag_id ".$cond;
    if(self::$DB->query($sql)){
      return true;
    } else {
      return false;
    }
    #} else {
    #  return false;
    #}
  }

  /**
   * Generate a descriptive name for a database record
   *
   * @param kvframework_db_object $self
   * @return string
   */
  public static function select_name(kvframework_db_object $self){
    return TOOLS::escape_quotes($self->label);
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
        self::validate_presence_of(array("label","max_concurrent_appts"), $params);
      }
      if(array_key_exists("max_concurrent_appts", $params)){
        self::validate_callback_on(array("max_concurrent_appts"), $params, "is_numeric", " must be an integer.");
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
