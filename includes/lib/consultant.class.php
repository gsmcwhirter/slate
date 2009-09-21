<?php
/**
 * KvScheduler - Consultant Model
 * @package KvScheduler
 * @subpackage Lib.Models
 * @author Greg McWhirter <gsmcwhirter@gmail.com>
 * @version 1.0
 */

/**
 * Consultant database model
 *
 * @package KvScheduler
 * @subpackage Lib.Models
 */
abstract class Consultant extends kvframework_base implements iDBWrapper{
  /**
   * Associated database table
   *
   */
  const Table = "consultants";

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
    /*
    $q = self::$DB->delete_query(array(self::Table));
		*/
    $q = self::$DB->update_query(array(self::Table));
    $q->fields = array("status" => "deleted");
    if(is_array($ids) && count($ids) > 0){
      $q->conditions .= "id IN ('".implode("','", $ids)."')";
    } elseif(!is_array($ids)) {
      $q->conditions .= "id = '".$ids."'";
    } else {
      return true;
    }

    //if(self::destroy_consultantappts($ids) && self::destroy_consultanthours($ids)){
      return self::$DB->process($q);
    //} else {
    //  return false;
    //}
  }

  /**
   * Destroy callback - destroy associated consultantappt records
   *
   * @param mixed $par_ids
   * @return boolean
   */
  protected static function destroy_consultantappts($par_ids){
    //$cond = (is_array($par_ids)) ? "IN ('".implode("','", $par_ids)."')" : "= '".$par_ids."'";
    //$sql = "DELETE FROM consultantappts WHERE consultant_id ".$cond;
    //return self::$DB->query($sql);
	  return true;
  }

  /**
   * Destroy callback - destroy associated consultanthour records
   *
   * @param mixed $par_ids
   * @return boolean
   */
  protected static function destroy_consultanthours($par_ids){
    /*$cond = (is_array($par_ids)) ? "IN ('".implode("','", $par_ids)."')" : "= '".$par_ids."'";
    $sql = "SELECT id as consultanthour_id FROM consultanthours WHERE consultant_id ".$cond;
    $q = self::$DB->query($sql);
    $ids = array();
    while($row = self::$DB->fetch($q)){
      $ids[] = $row->consultanthour_id;
    }

    return Consultanthour::destroy($ids);*/
    return true;
  }

  /**
   * Generate a descriptive name for the database record
   *
   * @param kvframework_db_object $self
   * @return string
   */
  public static function select_name(kvframework_db_object $self){
    return TOOLS::escape_quotes($self->realname." (".$self->username.")");
  }

  /**
   * Validate data used to create or update a database record
   *
   * @param array $params
   * @param string $type
   * @param mixed $id
   */
  protected static function do_validations(array $params, $type = "create", $id = null){
    try{
      if($type == "create"){
        self::validate_presence_of(array("realname","username","password","gender","staff","tag_id"), $params);
      }
      if(array_key_exists("username", $params)){
        self::validate_db_uniqueness_of("username",$params["username"],self::Table,"username",null,$id,"id");
      }
      if(array_key_exists("gender", $params)){
        self::validate_inclusion_of("gender", $params["gender"], array("M","F"), "is not valid.");
      }
      if(array_key_exists("staff", $params)){
        self::validate_inclusion_of("staff", $params["staff"], array("TRUE","FALSE"), "is not valid.");
      }
      if(array_key_exists("tag_id", $params)){
        //self::validate_callback_neg_on(array("tag_id"), $params, "empty", "cannot be empty or 0.");
	self::validate_callback_neg_on(array("tag_id"), $params, array("MyFunctions","is_empty"), "cannot be empty or 0.");
      }
      if(array_key_exists("pref_send_text", $params) && $params["pref_send_text"] == "yes"){
        self::validate_presence_of(array("pref_text_address"), $params);
        self::validate_length_of("pref_text_address", $params["pref_text_address"], array("min" => 1));
      }
      self::validate_resolve();
    } catch(validation_exception $e){
      foreach($e->errors as $err){
        self::throwError($err);
      }
    }
  }

  /**
   * Determines whether or not a consultant is free on a date and time period
   *
   * @param integer $consultant_id
   * @param integer $date
   * @param integer $start
   * @param integer $stop
   * @param chdata_struct $rch_data
   * @param apdata_struct $ap_data
   * @param string $lockout_user
   * @param mixed $ignore_appt_id
   * @return boolean
   */
  public static function isFreeOn($consultant_id, $date, $start, $stop, chdata_struct &$rch_data, apdata_struct &$ap_data, $lockout_user = false, $ignore_appt_id = null){
    $nblocks = (int)(($stop - $start) / 1800);
    $offset = (int)(($start - $rch_data->blocks[$consultant_id][$date][0]) / 1800);

    if($offset < 0){
      $nblocks += $offset;
      $offset = 0;
    }

    for($i = 1 + $offset; $i < 1 + $offset + $nblocks; $i++){
      if(!TOOLS::bit_read($rch_data->blocks[$consultant_id][$date][2], $i) || (TOOLS::bit_read($ap_data->blocks[$consultant_id][$date][2], $i) && $ap_data->things[$ap_data->blocks[$consultant_id][$date][3][$i]]->appointment_id != $ignore_appt_id && ($ap_data->things[$ap_data->blocks[$consultant_id][$date][3][$i]]->lockout == 'FALSE' || ($ap_data->things[$ap_data->blocks[$consultant_id][$date][3][$i]]->lockout == 'TRUE'
          && $ap_data->things[$ap_data->blocks[$consultant_id][$date][3][$i]]->lockout_user != $lockout_user)))){
        return false;
      }
    }

    return true;
  }

  /**
   * Determines whether or not a consultant has consultanthours on a date and time period
   *
   * @param integer $consultant_id
   * @param integer $date
   * @param integer $start
   * @param integer $stop
   * @param chdata_struct $rch_data
   * @param boolean $fully
   * @return boolean
   */
  public static function hasConsultantHoursOn($consultant_id, $date, $start, $stop, chdata_struct &$rch_data, $fully = false){
    $nblocks = (int)(($stop - $start) / 1800);
    $offset = (int)(($start - $rch_data->blocks[$consultant_id][$date][0]) / 1800);

    if($offset < 0){
      $nblocks += $offset;
      $offset = 0;
    }

    for($i = 1 + $offset; $i < 1 + $offset + $nblocks; $i++){
      if(TOOLS::bit_read($rch_data->blocks[$consultant_id][$date][2], $i) && !$fully){
        return true;
      } elseif($fully && !TOOLS::bit_read($rch_data->blocks[$consultant_id][$date][2], $i)){
        return false;
      }
    }

    return $fully;
  }

  /**
   * Get an array of appointments in anterior temporal proximity
   *
   * @param integer $consultant_id
   * @param integer $date
   * @param integer $time
   * @param apdata_struct $ap_data
   * @param mixed $reschedule
   * @return array
   */
  public static function previousAppointments($consultant_id, $date, $time, apdata_struct &$ap_data, $reschedule = false){
    $b = application_site_class::config_vals("loc_time_buff");
    $buffer = (int)((float)$b * 2.0);
    $start = $ap_data->blocks[$consultant_id][$date][0];
    $check_index = (int)(($time - $start) / 1800);
    $ret = array();

    for($i = 1; $i < $buffer + 1; $i++){
      if(($check_index - $i) >= 1 && ($check_index - $i) <= 32 && TOOLS::bit_read($ap_data->blocks[$consultant_id][$date][2], $check_index - $i) && $ap_data->things[$ap_data->blocks[$consultant_id][$date][3][$check_index - $i]]->lockout != "TRUE" && $ap_data->things[$ap_data->blocks[$consultant_id][$date][3][$check_index - $i]]->appointment_id != $reschedule){
        $ret[$ap_data->blocks[$consultant_id][$date][3][$check_index - $i]] = $ap_data->things[$ap_data->blocks[$consultant_id][$date][3][$check_index - $i]];
        break;
      }
    }

    return $ret;
  }

  /**
   * Get an array of appointments in posterior temporal proximity
   *
   * @param integer $consultant_id
   * @param integer $date
   * @param integer $time
   * @param apdata_struct $ap_data
   * @param mixed $reschedule
   * @return array
   */
  public static function followingAppointments($consultant_id, $date, $time, apdata_struct &$ap_data, $reschedule = false){
    $b = application_site_class::config_vals("loc_time_buff");
    $buffer = (int)((float)$b * 2.0);
    $start = $ap_data->blocks[$consultant_id][$date][0];
    $check_index = (int)(($time - $start) / 1800);
    $ret = array();

    for($i = 1; $i < $buffer + 1; $i++){
      if(($check_index + $i) >= 1 && ($check_index + $i) <= 32 && TOOLS::bit_read($ap_data->blocks[$consultant_id][$date][2], $check_index + $i) && $ap_data->things[$ap_data->blocks[$consultant_id][$date][3][$check_index + $i]]->lockout != "TRUE" && $ap_data->things[$ap_data->blocks[$consultant_id][$date][3][$check_index + $i]]->appointment_id != $reschedule){
        $ret[$ap_data->blocks[$consultant_id][$date][3][$check_index + $i]] = $ap_data->things[$ap_data->blocks[$consultant_id][$date][3][$check_index + $i]];
        break;
      }
    }

    return $ret;
  }

  /**
   * Get an array of appointment type appointment permissions for a consultant
   *
   * @param mixed $self
   * @return array
   */
  public static function ats_allowed($self){
    $t = new ApHash($self->appt_perms);
    return $t->hash();
  }

  /**
   * Check to see if a certain consultant is associated with a certain appointment
   *
   * @param integer $consultant_id
   * @param integer $appt_id
   * @return mixed
   */
  public static function check_appt($consultant_id, $appt_id){
    if(is_int($consultant_id) && is_int($appt_id)){
      $ret = null;
      $sql = "SELECT *, appointments.id as appointment_id, consultants.id as consultant_id, locations.id as location_id, locations.name as location_name, consultantappts.rapid as consultantappt_id, loczones.id as loczone_id, loczones.name as loczone_name FROM appointments, consultantappts, consultants, locations, loczones WHERE appointments.id = '".$appt_id."' AND consultantappts.consultant_id = consultants.id AND consultantappts.appointment_id = appointments.id AND locations.id = appointments.location_id AND loczones.id = locations.loczone_id";
      $q = self::$DB->query($sql);
      if(self::$DB->rows($q) >= 1){
        while($row = self::$DB->fetch($q)){
          if(is_null($ret)){$ret = $row;}
          if(!is_array($ret->consultants)){$ret->consultants = array();}
          $ret->consultants[$row->consultant_id] = $row;
        }

        $sql2 = "SELECT *, id as tm_id FROM ".strtolower($ret->tm_type)."s WHERE id = '".$ret->tm_id."' LIMIT 1";
        $q2 = self::$DB->query($sql2);
        $ret->tm = self::$DB->fetch($q2);

        if(in_array($consultant_id, array_keys($ret->consultants))){ return $ret; }
        else{ return false; }
      } else {
        return false;
      }
    } else {
      return false;
    }
  }

}
?>
