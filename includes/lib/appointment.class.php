<?php
/**
 * KvScheduler - Appointment model
 * @package KvScheduler
 * @subpackage Lib.Models
 * @author Greg McWhirter <gsmcwhirter@gmail.com>
 * @version 1.0
 */

/**
 * Appointment model
 *
 * @package KvScheduler
 * @subpackage Lib.Models
 */
abstract class Appointment extends kvframework_base implements iDBWrapper{
  /**
   * Database table maintained by this model
   *
   */
  const Table = "appointments";
  /**
   * Static indicator of whether or not the calls for the request are from a cronjob
   *
   * @var boolean
   */
  protected static $CRONJOB = false;

  /**
   * Creation of a new appointment
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
   * Update a current appointment record
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
   * Delete existing database record(s)
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

    if(self::destroy_consultantappts($ids) && self::destroy_repeat_removals($ids)){
      return self::$DB->process($q);
    } else {
      return false;
    }
  }

  /**
   * Destruction callback - remove associated consultant appts
   *
   * @param mixed $par_ids
   * @return boolean
   */
  protected static function destroy_consultantappts($par_ids){
    if(count($par_ids) == 0 || empty($par_ids)){return true;}
    $cond = (is_array($par_ids)) ? "IN ('".implode("','", $par_ids)."')" : "= '".$par_ids."'";
    $sql = "SELECT *, appointments.id as appointment_id, consultantappts.rapid as consultantappt_id, consultants.id as consultant_id, locations.id as location_id, locations.name as location_name FROM consultantappts, appointments, consultants, locations WHERE appointments.id ".$cond." AND consultantappts.appointment_id = appointments.id AND consultants.id = consultantappts.consultant_id AND locations.id = appointments.location_id";
    $q = self::$DB->query($sql);
    $ids = array();
    $appts = array();
    $tms = array("Ticket" => array(), "Meeting" => array(), "Meecket" => array());
    $from = application_site_class::config_vals("email_from");
    while($row = self::$DB->fetch($q)){
      $ids[] = $row->consultantappt_id;
      if(!array_key_exists($row->appointment_id, $appts)){$appts[$row->appointment_id] = $row;}
      if(!is_array($appts[$row->appointment_id]->consultantappts)){$appts[$row->appointment_id]->consultantappts = array();}
      $appts[$row->appointment_id]->consultantappts[$row->consultantappt_id] = $row;

      $tms[$row->tm_type][$row->appointment_id] = $row->tm_id;
    }

    foreach($tms as $type => $type_ids){
      if(count($type_ids) > 0){
        $from = strtolower($type);
        $temp = array();
        $sql = "SELECT *, id as tm_id FROM ".$from."s WHERE id IN (".implode(",", $type_ids).")";
        $q = self::$DB->query($sql);
        while($row = self::$DB->fetch($q)){
          $temp[$row->tm_id] = $row;
        }

        foreach($type_ids as $apid => $tmid){
          $appts[$apid]->tm = $temp[$tmid];
        }
      }
    }

    foreach($appts as $row){
      if($row->lockout != "TRUE" && !self::$CRONJOB){
        foreach($row->consultantappts as $rcap){
          Mailer::deliver_delete_appointment($row, $rcap, $from);
        }
      }
    }

    return Consultantappt::destroy($ids);
  }

  /**
   * Destruction callback - delete associated repeat removals
   *
   * @param mixed $par_ids
   * @return boolean
   */
  protected static function destroy_repeat_removals($par_ids){
    $cond = (is_array($par_ids)) ? "IN ('".implode("','", $par_ids)."')" : "= '".$par_ids."'";
    $sql = "SELECT id as appointment_id FROM appointments WHERE special = 'repeat_removal' AND removal_of ".$cond;
    $q = self::$DB->query($sql);
    $ids = array();
    while($row = self::$DB->fetch($q)){
      $ids[] = $row->appointment_id;
    }

    if(count($ids) == 0){
      return true;
    } else {
      return Appointment::destroy($ids);
    }
  }

  /**
   * Determines whether an appointment is within operating hours
   *
   * @param kvframework_db_object $appt
   * @param integer $date
   * @param ophdata_struct $ohdata
   * @return boolean
   */
  public static function isInOphours(kvframework_db_object $appt, $date, ophdata_struct $ohdata){
    if($date > TOOLS::string_to_date($appt->stopdate) || $date < TOOLS::string_to_date($appt->startdate)){
      return false;
    }

    if(MyFunctions::in_intervals($appt, $ohdata->blocks[$date]["intervals"], true)){
      return true;
    }

    return false;
  }

  /**
   * Return a descriptive name for a database record
   *
   * @param kvframework_db_object $self
   * @return string
   */
  public static function select_name(kvframework_db_object $self){
    return TOOLS::escape_quotes($self->appointment_id);
  }

  /**
   * Validate creations and updates
   *
   * @param array $params
   * @param string $type
   */
  protected static function do_validations(array $params, $type = "create"){
    try{
      if($type == "create"){
        self::validate_presence_of(array("starttime","stoptime","startdate","stopdate","timestamp"), $params);
      }
      //kvframework_log::write_log("Appt_Validations_Params: ".serialize($params), KVF_LOG_LDEBUG);
      self::validate_callback_on(array_intersect(array("starttime","stoptime","startdate","stopdate","timestamp"), array_keys($params)), $params, "is_int", "has invalid format");
      if(array_key_exists("lockout", $params)){self::validate_inclusion_of("lockout", $params["lockout"], array("TRUE","FALSE"));}
      if(!array_key_exists("lockout", $params) || $params["lockout"] != "TRUE"){
        //kvframework_log::write_log("Appt_Validations: lockout not true.", KVF_LOG_LDEBUG);
        if($type == "create"){
          self::validate_presence_of(array("tm_id","tm_type","locdetails","location_id","appointment_user"), $params);
        }
        if(array_key_exists("location_id", $params)){
          self::validate_exists_in_db("location_id", array("id" => $params["location_id"]), "locations", "does not reflect an actual location");
        }
        if(array_key_exists("locdetails", $params)){
          self::validate_length_of("locdetails", $params["locdetails"], array("min" => 1), "must have length greater than 0");
        }
        if(array_key_exists("tm_type", $params)){
          self::validate_inclusion_of("tm_type", $params["tm_type"], array("Ticket","Meeting","Meecket"), "is not a valid association");
        }
        if(array_key_exists("special", $params)){self::validate_inclusion_of("special", $params["special"], array("regular", "repeat_removal"));}
        if(array_key_exists("special2", $params)){self::validate_inclusion_of("special2", $params["special2"], array("regular","meeting","meecket"));}
        if(array_key_exists("repeat", $params)){self::validate_inclusion_of("repeat", $params["repeat"], array("TRUE","FALSE"));}
        if(array_key_exists("tm_type", $params)){
          switch($params["tm_type"]){
            case "Ticket":
              self::validate_exists_in_db("tm_id", array("id" => $params["tm_id"]), "tickets", "is invalid.");
              break;
            case "Meeting":
              self::validate_exists_in_db("tm_id", array("id" => $params["tm_id"]), "meetings", "is invalid.");
              break;
            case "Meecket":
              self::validate_exists_in_db("tm_id", array("id" => $params["tm_id"]), "meeckets", "is invalid.");
              break;
          }
        }
      } else {
        //kvframework_log::write_log("Appt_Validations: lockout true.", KVF_LOG_LDEBUG);
        self::validate_presence_of(array("lockout_user"), $params);
      }
      self::validate_resolve();
    } catch(validation_exception $e){
      //kvframework_log::write_log("Appt_Validations: validation error.", KVF_LOG_LDEBUG);
      foreach($e->errors as $err){
        self::throwError($err);
      }
    }
  }

  /**
   * Tell the model that the request is from a cron job
   *
   */
  public static function cron(){
    self::$CRONJOB = true;
  }

}
?>
