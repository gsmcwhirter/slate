<?php
/**
 * KvScheduler - Supervisor Model
 * @package KvScheduler
 * @subpackage Lib.Models
 * @author Greg McWhirter <gsmcwhirter@gmail.com>
 * @version 1.0
 */

/**
 * Supervisor database model
 *
 * @package KvScheduler
 * @subpackage Lib.Models
 */
abstract class Supervisor extends kvframework_base implements iDBWrapper{
  /**
   * Associated database table
   *
   */
  const Table = "supervisors";

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
    self::do_validations($attribs, "update", $id);
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
  }

  /**
   * Generate a descriptive name for a database record
   *
   * @param kvframework_db_object $self
   * @return string
   */
  public static function select_name(kvframework_db_object $self){
    return TOOLS::escape_quotes($self->realname." (".$self->username.")");
  }

  /**
   * Validate data for use in creating or updating a database record
   *
   * @param array $params
   * @param string $type
   * @param mixed $id
   */
  protected static function do_validations(array $params, $type = "create", $id = null){
    try{
      if($type == "create"){
        self::validate_presence_of(array("realname","username","password"), $params);
      }
      if(array_key_exists("username", $params)){
        self::validate_db_uniqueness_of("username",$params["username"],self::Table,"username",null,$id,"id");
      }
      if(array_key_exists("acdaccount", $params)){
        self::validate_inclusion_of("acdaccount", $params["acdaccount"], array("yes","no"), "is not valid.");
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
