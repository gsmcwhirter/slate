<?php
/**
 * KvScheduler - Meeting Admin SiteClass
 * @package KvScheduler
 * @subpackage Modules
 * @author Greg McWhirter <gsmcwhirter@gmail.com>
 * @version 1.0
 */

/**
 * Meeting creation
 *
 * @package KvScheduler
 * @subpackage Modules
 */
class meeting_admin_site_class extends admin_site_class{
  /**
   * Select generator values for locations
   *
   * @var array
   */
  protected $all_locs;
  /**
   * Select generator values for consultants
   *
   * @var array
   */
  protected $all_rcs;

  /**
   * (Start) date of the meeting
   *
   * @var integer
   */
  protected $date;
  /**
   * End date of the meeting
   *
   * @var integer
   */
  protected $enddate;

  /**
   * Start time of the meeting
   *
   * @var integer
   */
  protected $start;

  /**
   * Stop time of the meeting
   *
   * @var integer
   */
  protected $stop;

  /**
   * Location of the meeting
   *
   * @var mixed
   */
  protected $location;

  /**
   * Location details for the meeting
   *
   * @var string
   */
  protected $locdetails;
  /**
   * Description / Details of the meeting
   *
   * @var string
   */
  protected $descrip;

  /**
   * Other consultants for the meeting
   *
   * @var mixed
   */
  protected $other_rcs;

  /**
   * Repetition status of the meeting
   *
   * @var mixed
   */
  protected $repeat;

  /**
   * Repetition weeks
   *
   * @var mixed
   */
  protected $rep_week;

  /**
   * Repetition days
   *
   * @var mixed
   */
  protected $rep_day;

  /**
   * Subject of the meeting
   *
   * @var string
   */
  protected $subject;

  /**
   * Constructor
   *
   */
  function __construct(){
    parent::__construct();
  }

  /**
   * Creation form
   *
   */
  public function form(){
    $this->prep_form();
    $this->output_page("form", "inline");
  }

  /**
   * Process creation form
   *
   */
  public function process_form(){
    $this->repeat = "FALSE";

    if(array_key_exists("fi", self::$PARAMS) && is_array(self::$PARAMS["fi"])){
      $this->date = (array_key_exists("startdate", self::$PARAMS["fi"]) && is_array(self::$PARAMS["fi"]["startdate"])) ? TOOLS::string_to_date(implode("-", self::$PARAMS["fi"]["startdate"])) : null;
      $this->start =  (array_key_exists("starttime", self::$PARAMS["fi"]) && is_array(self::$PARAMS["fi"]["starttime"])) ? TOOLS::string_to_time(implode(":", self::$PARAMS["fi"]["starttime"])) : null;
      $this->stop =  (array_key_exists("stoptime", self::$PARAMS["fi"]) && is_array(self::$PARAMS["fi"]["stoptime"])) ? TOOLS::string_to_time(implode(":", self::$PARAMS["fi"]["stoptime"])) : null;
      $this->locdetails = (array_key_exists("locdetails", self::$PARAMS["fi"])) ? self::$PARAMS["fi"]["locdetails"] : null;
      $this->subject = (array_key_exists("subject", self::$PARAMS["fi"])) ? self::$PARAMS["fi"]["subject"] : null;
      $this->descrip = (array_key_exists("details", self::$PARAMS["fi"])) ? self::$PARAMS["fi"]["details"] : null;
      $this->other_rcs = (array_key_exists("other_consultants", self::$PARAMS) && is_array(self::$PARAMS["other_consultants"])) ? self::$PARAMS["other_consultants"] : array();
      $this->repeat = (array_key_exists("repeat", self::$PARAMS["fi"])) ? self::$PARAMS["fi"]["repeat"] : "FALSE";
      $this->rep_week = (array_key_exists("repetition_week", self::$PARAMS["fi"])) ? self::$PARAMS["fi"]["repetition_week"] : null;
      $this->rep_day = (array_key_exists("repetition_day", self::$PARAMS) && is_array(self::$PARAMS["repetition_day"])) ? self::$PARAMS["repetition_day"] : array(TOOLS::weekday_transform(TOOLS::wday_for(TOOLS::date_today())));
      $this->enddate = (array_key_exists("enddate", self::$PARAMS["fi"]) && is_array(self::$PARAMS["fi"]["enddate"])) ? TOOLS::string_to_date(implode("-", self::$PARAMS["fi"]["enddate"])) : null;
      $this->location = (array_key_exists("loc_id", self::$PARAMS["fi"])) ? $this->prep_loc(self::$PARAMS["fi"]["loc_id"]) : null;
    }

    $only_rc_errors = true;

    if($this->repeat == "TRUE"){
      //$results = ApptChecks::repeating_checks($this->date, $enddate, $this->start, $this->stop, $this->other_rcs, "meeting", $this->rep_day, $this->rep_week, self::$USER->info("username"));
      $results = ApptChecks::repeating_checks($this->date, $this->enddate, $this->start, $this->stop, $this->other_rcs, "meeting", $this->rep_day, $this->rep_week, $this->USER->info("username"));
      foreach($this->other_rcs as $rc){
        foreach($results[$rc]["checks"] as $key => $value){
          if(!$value){
            self::throwError($results[$rc]["reasons"][$key]);
          }
        }
      }
    } else {
      //$results = ApptChecks::day_check($this->date, $this->other_rcs, $this->start, $this->stop, "meeting", self::$USER->info("username"));
      $results = ApptChecks::day_check($this->date, $this->other_rcs, $this->start, $this->stop, "meeting", $this->USER->info("username"));
      foreach($this->other_rcs as $rc){
        if(!$results[$rc]["check"]){
          self::throwError($results[$rc]["reason"]);
        }
      }
    }

    $thingid = Meeting::create(array("subject" => $this->subject, "description" => $this->descrip));
    if($thingid){
      $args = array(
        "tm_id" => $thingid,
        "tm_type" => "Meeting",
        "starttime" => $this->start,
        "stoptime" => $this->stop,
        "startdate" => $this->date,
        "stopdate" => ($this->enddate) ? $this->enddate : $this->date,
        "location_id" => $this->location->location_id,
        "locdetails" => $this->locdetails,
        "timestamp" => time(),
        "repeat" => $this->repeat,
        "special2" => "meeting",
        //"appointment_user" => self::$USER->info("username")
        "appointment_user" => $this->USER->info("username")
      );
      if($this->repeat == "TRUE"){
        $args = array_merge($args, array("stopdate" => $this->enddate, "repetition_day" => implode(",", $this->rep_day), "repetition_week" => $this->rep_week));
      }


      $all_results = array();
      if($this->repeat == "TRUE"){
        foreach($this->other_rcs as $rc){
          $all_results = array_merge($all_results, array_values($results[$rc]["checks"]));
        }
      } else {
        $all_results = TOOLS::array_collect(array_values($results), '$r','$r["check"]');
      }

      if(in_array(true, $all_results)){
        $apptid = Appointment::create($args);
        if($apptid){
          $gconsultants = array();
          if($this->repeat == "TRUE"){
            foreach($this->other_rcs as $r){if(in_array(true, array_values($results[$r]["checks"]))){$gconsultants[] = $r;}}
          } else {
            foreach($this->other_rcs as $r){if($results[$r]["check"]){$gconsultants[] = $r;}}
          }

          $vals = "";
          foreach($gconsultants as $gr){
            $vals .= "('$apptid','$gr'), ";
          }
          $vals = substr($vals, 0, -2);

          $sql = "INSERT INTO consultantappts (appointment_id, consultant_id) VALUES $vals";
          $q = self::$DB->query($sql);

          self::throwMessage("Meeting added.");

          $appt_record = null;

          $sql = "SELECT *, consultants.id as consultant_id, appointments.id as appointment_id FROM appointments, consultants, consultantappts WHERE appointments.id = '$apptid' AND consultantappts.appointment_id = appointments.id AND consultants.id = consultantappts.consultant_id AND consultants.status = 'active'";
          $q = self::$DB->query($sql);
          while($row = self::$DB->fetch($q)){
            if(is_null($appt_record)){$appt_record = $row;}
            if(!is_array($appt_record->consultants)){$appt_record->consultants = array();}
            $appt_record->consultants[$row->consultant_id] = $row;
          }

          $sql = "SELECT *, id as tm_id, 'Meeting' as tm_type FROM meetings WHERE id = '$thingid' LIMIT 1";
          $q = self::$DB->query($sql);
          if(self::$DB->rows($q) ==1){
            $appt_record->tm = $row;
          } else {
            self::throwError("Unable to find created meeting.");
          }

          BackupFunctions::write_backup($appt_record, $appt_record->consultants, $appt_record->tm);

          foreach($appt_record->consultants as $rc){
            Mailer::deliver_new_appointment($appt_record->appointment_id, $rc, $this->config_val("email_from"), MyFunctions::datetime_in_appt(TOOLS::date_today(), TOOLS::string_to_time($appt_record->starttime), $appt_record), TOOLS::string_to_time($appt_record->starttime));
          }

          $bconsultants = array();
          foreach($gconsultants as $grc){
            if($this->repeat == "TRUE"){
              foreach($results[$grc]["checks"] as $key => $value){
                if(!array_key_exists($key, $bconsultants) || !is_array($bconsultants[$key])){$bconsultants[$key] = array();}
                if(!$value){$bconsultants[$key][] = $grc;}
              }
            } else {
              if(!is_array($bconsultants[$this->date])){$bconsultants[$this->date] = array();}
              if(!$results[$grc]["check"]){$bconsultants[$this->date][] = $grc;}
            }
          }

          if(!count($bconsultants) == 0){self::throwMessage("Attempting removal of certain consultants from certain dates.");}
          foreach($bconsultants as $key => $value){
            if(!count($value) == 0){
              $aid = Appointment::create(array_merge($args, array("startdate" => (int)$key, "stopdate" => (int)$key, "timestamp" => time(), "repeat" => "FALSE", "special" => "repeat_removal", "removal_of" => $appt_record->appointment_id)));
              if($aid){
                foreach($value as $v){
                  Consultantappt::create(array("appointment_id" => $aid, "consultant_id" => $v));
                  self::throwMessage("Consultant ".$appt_record->consultants[$v]->username." removed from meeting on ".TOOLS::date_to_s($key));
                }

                $rappt = null;
                $sql = "SELECT *, consultants.id as consultant_id, appointments.id as appointment_id FROM appointments, consultants, consultantappts WHERE appointments.id = '$aid' AND consultantappts.appointment_id = appointments.id AND consultants.id = consultantappts.consultant_id AND consultants.status = 'active'";
                $q = self::$DB->query($sql);
                while($row = self::$DB->fetch($q)){
                  if(is_null($rappt)){$rappt = $row;}
                  if(!is_array($rappt->consultants)){$rappt->consultants = array();}
                  $rappt->consultants[$row->consultant_id] = $row;
                }

                $sql = "SELECT *, id as tm_id, 'Meeting' as tm_type FROM meetings WHERE id = '$thingid' LIMIT 1";
                $q = self::$DB->query($sql);
                if(self::$DB->rows($q) ==1){
                  $rappt->tm = $row;
                } else {
                  self::throwError("Unable to find created meeting.");
                }

                BackupFunctions::write_backup($rappt, $rappt->consultants, $rappt->tm);
              } else {
                self::throwError("Some consultants were not removed as they should have been.");
              }
            }
          }
          //Lockouts::destroy(self::$USER->info("username"));
          Lockouts::destroy($this->USER->info("username"));
        } else {
          $only_rc_errors = false;
        }

        if(!self::is_errors() || $only_rc_errors){
          $this->output_page("index","inline","admin");
        } else {
          $this->form();
        }
      }
    } else {
      self::throwError("Meeting failed to be created.");
      $this-form();
    }

  }

  /**
   * Prepare the creation form
   *
   * @return boolean true
   */
  protected function prep_form(){
    $this->all_locs = array();
    $sql = "SELECT *, locations.id as location_id, locations.name as location_name, loczones.id as loczone_id, loczones.name as loczone_name, appttypes.id as appttype_id FROM locations, loczones, appttypes WHERE appttypes.id = locations.appttype_id AND loczones.id = locations.loczone_id AND appttypes.tm_class LIKE '%Meeting%'";
    $q = self::$DB->query($sql);
    while($row = self::$DB->fetch($q)){
      $this->all_locs[] = array(Location::select_name($row)." (".Appttype::select_name($row).")", $row->location_id);
    }

    $this->all_rcs = array();
    $sql = "SELECT *, id as consultant_id FROM consultants WHERE status = 'active' ORDER BY SUBSTRING(realname FROM (INSTR(realname, ' ')+1)) ASC";
    $q = self::$DB->query($sql);
    while($row = self::$DB->fetch($q)){
      $this->all_rcs[] = array(Consultant::select_name($row), $row->consultant_id);
    }

    return true;
  }

  /**
   * Prepare the selected location
   *
   * @param integer $id
   * @return mixed
   */
  protected function prep_loc($id){
    $sql = "SELECT *, locations.id as location_id, locations.name as location_name, loczones.id as loczone_id, loczones.name as loczone_name, appttypes.id as appttype_id, appttypes.name as appttype_name FROM locations, loczones, appttypes WHERE locations.id = '$id' AND loczones.id = locations.loczone_id AND appttypes.id = locations.appttype_id LIMIT 1";
    $q = self::$DB->query($sql);
    if(self::$DB->rows($q) == 1){
      return self::$DB->fetch($q);
    } else {
      return null;
    }
  }
}
