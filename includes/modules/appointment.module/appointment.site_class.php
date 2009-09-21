<?php
/**
 * KvScheduler - Appointment SiteClass
 * @package KvScheduler
 * @subpackage Modules
 * @author Greg McWhirter <gsmcwhirter@gmail.com>
 * @version 1.0
 */

/**
 * Appointment SiteClass - Schedules and edits appointments
 *
 * @package KvScheduler
 * @subpackage Modules
 */
class appointment_site_class extends application_site_class{

  /**
   * Holds appointment types
   *
   * @var array
   */
  protected $ats = array();

  /**
   * Holds a meeting, ticket, or meecket
   *
   * @var mixed
   */
  protected $thing;

  /**
   * Some flag for the process
   *
   * @var boolean
   */
  protected $alter;

  /**
   * Maximum reached step in the process
   *
   * @var integer
   */
  protected $max_step;

  /**
   * (Start) Date of the appointment
   *
   * @var integer
   */
  protected $date;

  /**
   * Start time of the appointment
   *
   * @var integer
   */
  protected $start;

  /**
   * Stop time of the appointment
   *
   * @var integer
   */
  protected $stop;

  /**
   * Stop date of the appointment
   *
   * @var integer
   */
  protected $enddate;

  /**
   * Holds a consultant record
   *
   * @var mixed
   */
  protected $thisguy;

  /**
   * Appointment repetition week
   *
   * @var integer
   */
  protected $rep_week;

  /**
   * Appointment repetition days
   *
   * @var mixed
   */
  protected $rep_day;

  /**
   * Other recons on the appointment
   *
   * @var mixed
   */
  protected $other_rcs;

  /**
   * Flag for rescheduling
   *
   * @var mixed
   */
  protected $reschedule;

  /**
   * Location ID of the appointment
   *
   * @var integer
   */
  protected $loc_id;

  /**
   * Location details of the appointment
   *
   * @var string
   */
  protected $locdetails;

  /**
   * Holds the location record for the appointment
   *
   * @var mixed
   */
  protected $location;

  /**
   * Holds the type of appointment
   *
   * @var mixed
   */
  protected $type;

  /**
   * Flag for overriding automatic name generation from username
   *
   * @var boolean
   */
  protected $foverride;

  /**
   * Inputted username or name of appointment requestor
   *
   * @var string
   */
  protected $withperson;

  /**
   * Primary phone number
   *
   * @var string
   */
  protected $phone;

  /**
   * Altername phone number
   *
   * @var string
   */
  protected $altphone;

  /**
   * Remedy ticket number
   *
   * @var string
   */
  protected $ticket;

  /**
   * Appointment details
   *
   * @var string
   */
  protected $details;

  /**
   * Meeting subject
   *
   * @var string
   */
  protected $subject;

  /**
   * Maximum allowed stop time
   *
   * @var mixed
   */
  protected $stopmax;

  /**
   * An appointment instance
   *
   * @var mixed
   */
  protected $app;

  /**
   * An array of all appointment types for a select generator
   *
   * @var array
   */
  protected $all_ats;

  /**
   * An array of all consultants for a select generator
   *
   * @var array
   */
  protected $all_rcs;

  /**
   * An array of all locations for a select generator
   *
   * @var array
   */
  protected $all_locs;

  /**
   * A flag for whether to use repeating information
   *
   * @var boolean
   */
  protected $do2;

  /**
   * A flag for whether to use multi-consultant information
   *
   * @var boolean
   */
  protected $do3;

  /**
   * Is this appointment repeating
   *
   * @var mixed
   */
  protected $repeat;

  /**
   * Does the appointment have many users
   *
   * @var mixed
   */
  protected $multi_user;

  /**
   * Appointment type ID
   *
   * @var integer
   */
  protected $appttype_id;

  /**
   * A flag for something...
   *
   * @var boolean
   */
  protected $skip;

  /**
   * Consultant names
   *
   * @var mixed
   */
  protected $rc_names;

  /**
   * Holds an appointment instance i would guess
   *
   * @var mixed
   */
  protected $appt;

  /**
   * Got me atm...
   *
   * @var mixed
   */
  protected $aps_data;

  /**
   * Flag for concurrancy override having happened
   *
   * @var array
   */
  protected $concur_override = array();

  /**
   * Flag for time override having happened
   *
   * @var boolean
   */
  protected $time_override;

  /**
   * Flag for finals override having happened
   *
   * @var boolean
   */
  protected $finals_override;

  /**
   * Flag for length warnings already having been overridden
   *
   * @var mixed
   */
  protected $length_override;

  /**
   * Flag for appointment hour warnings already having been overridden
   *
   * @var mixed
   */
  protected $appthour_override;

  /**
   * Flag for location warnings already having been overridden
   *
   * @var mixed
   */
  protected $far_override;

  /**
   * Flag for gender warnings already having been overridden
   *
   * @var mixed
   */
  protected $gender_override;

  /**
   * Flag for whether length needs comfirmation
   *
   * @var boolean
   */
  protected $confirm_length;

  /**
   * Flag for whether appointment hours needs comfirmation
   *
   * @var boolean
   */
  protected $confirm_appthour;

  /**
   * Flag for whether gender needs confirmation
   *
   * @var boolean
   */
  protected $confirm_gender;

  /**
   * Flag for whether time needs confirmation
   *
   * @var boolean
   */
  protected $confirm_time;

  /**
   * Flag for whether finals status needs confirmation
   *
   * @var boolean
   */
  protected $confirm_finals;

  /**
   * Flag for whether location needs confirmation
   *
   * @var boolean
   */
  protected $confirm_location;

  /**
   * Flag for whether concurrancy needs confirmation
   *
   * @var array
   */
  protected $confirm_max_concur;

  /**
   * Contains info on which loctag to confirm
   *
   * @var mixed
   */
  protected $confirm_max_concur_which;

  /**
   * Something...
   *
   * @var mixed
   */
  protected $special2;

  /**
   * Constructor
   */
  function __construct(){
    parent::__construct();
    $this->before_filter("set_ats");
  }

  /**
   * Set applicable appointment type permissions for the current user
   */
  protected function set_ats(){
    $this->ats = array();
    $aph = $this->config_val("appt_sched");
    switch($this->USER->access()){
      case ACCESS::user:
        foreach(array_keys($aph["helpdesk"]) as $k){
          if($aph["helpdesk"][$k]){
            $this->ats[] = self::appttypes($k);
          }
        }
        break;
      case ACCESS::modify:
        foreach(array_keys($aph["supervisor"]) as $k){
          if($aph["supervisor"][$k]){
            $this->ats[] = self::appttypes($k);
          }
        }
        break;
      case ACCESS::sysop:
        foreach(array_keys($aph["sysop"]) as $k){
          if($aph["sysop"][$k]){
            $this->ats[] = self::appttypes($k);
          }
        }
        break;
      default:
        $this->ats = array();
        break;
    }
    return true;
  }

  /**
   * Start rescheduling an appointment
   *
   */
  public function reschedule_appointment(){
    Lockouts::destroy($this->USER->info("username"));
    if(!array_key_exists("aid", self::$PARAMS)){
      self::throwError("Missing appointment ID parameter.");
    } else {
      $ap = null;
      $sql = "SELECT *, appointments.id as appointment_id, consultants.id as consultant_id FROM appointments, consultants, consultantappts WHERE appointments.id = '".self::$PARAMS["aid"]."' AND consultantappts.appointment_id = appointments.id AND consultantappts.consultant_id = consultants.id";
      $q = self::$DB->query($sql);
      while($row = self::$DB->fetch($q)){
        if(is_null($ap)){
          $ap = $row;
        }
        if(!is_array($ap->consultants)){
          $ap->consultants = array();
        }
        $ap->consultants[$row->consultant_id] = $row;
      }

      if(is_null($ap)){
        self::throwError("Requested appointment was not found.");
      } else {
        if(count($ap->consultants) > 1 || $ap->repeat == "TRUE" || $ap->special != "regular"){
          self::throwError("Selected appointment was not valid for rescheduling.");
        }
      }
    }

    $this->output_page("display_date_full", "inline", "calendar", array("reschedule" => (self::is_errors()) ? null : $ap->appointment_id, "date" => self::$PARAMS["adate"]));
  }

  /**
   * Actually process rescheduling an appointment
   */
  public function process_reschedule_select(){
    $ap = null;
    $sql = "SELECT *, id as appointment_id FROM appointments WHERE id = '".self::$PARAMS["reschedule"]."' LIMIT 1";
    $q = self::$DB->query($sql);
    if(self::$DB->rows($q) == 1){
      $ap = self::$DB->fetch($q);
      $this->reschedule = ((int)self::$PARAMS["reschedule"] != 0) ? self::$PARAMS["reschedule"] : null;
      $this->do_step($ap->tm_id, $ap->tm_type, 3);
    } else {
      self::throwError("Requested appointment was not found.");
      Lockouts::destroy($this->USER->info("username"));
      $this->render_close_box();
    }
  }

  /**
   * Prepare a ticket, meeting or meecket passed to an appointment scheduling routine
   *
   * @param integer $tid
   * @param string $ttype
   *
   * @return boolean
   */
  protected function prep_passed_ticket($tid, $ttype){
    $thing_passed = ($tid) ? $tid : null;
    $thing_type_passed = ($ttype) ? $ttype : null;
    $table = null;

    if(!is_null($thing_passed) && !is_null($thing_type_passed)){
      kvframework_log::write_log("STUPIDTHING: prep_passed_ticket 'if';", KVF_LOG_LDEBUG);
      switch($thing_type_passed){
        case "Ticket":
          $table = "tickets";
          break;
        case "Meeting":
          $table = "meetings";
          break;
        case "Meecket":
          $table = "meeckets";
          break;
        default:
          $table = null;
          break;
      }

      if(is_null($table)){
        self::throwError("In-valid thing type value passed.");
        $this->thing = null;
      } else {
        $this->thing = null;
        $sql = "SELECT *, ".$table.".id as tm_id, '$thing_type_passed' as tm_type FROM ".$table." WHERE ".$table.".id = '".$thing_passed."' LIMIT 1";
        $q = self::$DB->query($sql);
        if(self::$DB->rows($q) == 1){
          if(is_null($this->thing)){$this->thing = self::$DB->fetch($q);}
          if(!is_array($this->thing->appointments)){$this->thing->appointments = array();}
          $sql2 = "SELECT *, appointments.id as appointment_id FROM appointments WHERE appointments.tm_id = '".$thing_passed."' AND appointments.tm_type = '".$thing_type_passed."'";
          $q2 = self::$DB->query($sql2);
          while($row = self::$DB->fetch($q2)){
            $this->thing->appointments[$row->appointment_id] = $row;
          }
        } else {
          $this->thing = null;
        }
      }
    }

    return true;
  }


  /**
   * Creation form entry point
   */
  public function create_form(){
    $this->do_step();
  }

  /**
   * Creation form processing entry point
   *
   */
  public function create_form_process(){
    $this->do_step();
  }


  /**
   * Central routine for scheduling an appointment
   *
   * @param mixed $tid
   * @param mixed $ttype
   * @param mixed $starton
   */
  protected function do_step($tid = false, $ttype = false, $starton = false){
    if(!array_key_exists("step", self::$PARAMS) || !in_array(self::$PARAMS["step"], array("next","prev","curr"))){
      $this->passed_step = false;
      $step = "next";
    } else {
      $this->passed_step = true;
      $step = self::$PARAMS["step"];
    }

    $this->reschedule = (array_key_exists("reschedule", self::$PARAMS) && (int)self::$PARAMS["reschedule"] != 0) ? self::$PARAMS["reschedule"] : null;

    if(!$tid){$tid = (array_key_exists("tid", self::$PARAMS)) ? (int)self::$PARAMS["tid"] : null;}
    if(!$ttype){$ttype = (array_key_exists("ttype", self::$PARAMS)) ? self::$PARAMS["ttype"] : null;}

    if($this->reschedule){
      $sql = "SELECT *, appointments.id as appointment_id, locations.id as location_id FROM appointments, locations WHERE appointments.id = '".$this->reschedule."' AND locations.id = appointments.location_id LIMIT 1";
      $q = self::$DB->query($sql);
      if(self::$DB->rows($q) == 1){
        $this->app = self::$DB->fetch($q);
      } else {
        self::throwError("Requested appointment was not found.");
      }

      $this->do2 = false;
    } else {
      $this->do2 = true;
    }

    $this->do3 = true;

    $mod = array("next" => 1, "curr" => 1, "prev" => -1);
    $this->prep_passed_ticket($tid, $ttype);

    $this->onstep = $this->decide_step("curr");
    $this->tostep = $this->decide_step($step);

    if($this->onstep == 0){
      Lockouts::destroy($this->USER->info("username"));
      if($this->setup_create_vars(1)){
        if(Lockouts::create($this->thisguy->consultant_id, $this->start, $this->stop, $this->date, $this->USER->info("username"), $this->reschedule)){
          self::throwMessage("Lockout created successfully.");
        } else {
          self::throwError("Failed to create a lockout. Consultant might not be free at that time.");
        }

      }
      $this->skip = true;
    }

    if($this->onstep < 0){$this->onstep = 0;}
    if($this->tostep < 1){$this->tostep = 1;}
    if($this->passed_step && array_key_exists("max_step", self::$PARAMS) && !empty(self::$PARAMS["max_step"]) && in_array((int)self::$PARAMS["max_step"], array(1,2,3,4,5)) && (int)self::$PARAMS["max_step"] > $this->tostep){
      $this->alter = true;
      $this->max_step = (((int)self::$PARAMS["max_step"] > $this->onstep + 1) ? (int)self::$PARAMS["max_step"] : $this->onstep + 1);
      $this->onstep -= 2;
    } elseif($this->passed_step){
      $this->max_step = $this->onstep + 1;
      $this->onstep -= 2;
    } elseif(array_key_exists("max_step", self::$PARAMS) && !empty(self::$PARAMS["max_step"]) && in_array((int)self::$PARAMS["max_step"], array(1,2,3,4,5)) && (int)self::$PARAMS["max_step"] > $this->tostep) {
      $this->alter = true;
      $this->max_step = (int)self::$PARAMS["max_step"];
    }

    if($this->USER->access() < ACCESS::modify && !in_array($this->tostep, array(0,1,2,4,5,6))){ $this->tostep = 4; }
    if($this->USER->access() < ACCESS::modify && !in_array($this->onstep, array(0,1,2,4,5,6))){ $this->onstep = 4; }

    $bump = 0;
    if($this->onstep != 0){$this->setup_create_vars(2);}
    if(!$this->do2 && $this->tostep == 2){
      $bump += 1;
      $this->tostep += (1 * $mod[$step]);
    }
    if(!$this->do3 && $this->tostep == 3){
      $bump += (1 * $mod[$step]);
      $this->tostep += (1 * $mod[$step]);
    }
    if(!$this->do2 && $this->tostep == 2){
      $bump -= 1;
      $this->tostep += (1 * $mod[$step]);
    }

    if($this->passed_step){
      $this->setup_create_vars((($this->max_step && $this->max_step > $this->tostep) ? $this->max_step : $this->tostep), (($this->max_step == $this->tostep + 2) ? false : true), $this->tostep + 2 + $bump);
    } elseif($this->alter){
      $this->setup_create_vars((($this->max_step && $this->max_step > $this->tostep) ? $this->max_step : $this->tostep), (($this->max_step == $this->tostep + 2) ? false : true), $this->tostep);
    } elseif(!$this->skip){
      $this->setup_create_vars($this->tostep);
    }

    if($this->passed_step){ $this->onstep += 2; }

    if($this->tostep == 1 && self::is_errors() && $step == "next"){
      Lockouts::destroy($this->USER->info("username"));
      $this->render_close_box();
    } else {
      if($this->tostep >= 5){
        $this->process_create_total();
        if((!self::is_errors() || ($this->continue_through_errors && !$this->non_person_errors)) && !$this->confirm_gender && !$this->confirm_location && !$this->confirm_concur(true)){
          foreach($this->appt->consultants as $rc){
            Mailer::deliver_new_appointment($this->appt->appointment_id, $rc, $this->config_val("email_from"), MyFunctions::datetime_in_appt(TOOLS::date_today(), TOOLS::string_to_time($this->appt->starttime), $this->appt), TOOLS::string_to_time($this->appt->starttime));
          }

          if($this->reschedule){
            $app = $this->thing->appointments[$this->reschedule];
            BackupFunctions::unwrite_backup($app);

            kvframework_log::write_log("RESCHEDULE: ".$app->appointment_id, KVF_LOG_LDEBUG);
            if(Appointment::destroy($app->appointment_id)){
              self::throwMessage("Old appointment removed successfully.");
            } else {
              self::throwError("Old appointment was NOT removed successfully.");
            }
          }

          $this->stop_resch = true;
          $this->reschedule = null;
          self::$PARAMS["reschedule"] = null;

          Lockouts::destroy($this->USER->info("username"));
          $this->render_close_box();
        } elseif ((!self::is_errors() || ($this->continue_through_errors && !$this->non_person_errors)) && $this->confirm_location ){
          $this->confirm_far_away();
        } elseif ((!self::is_errors() || ($this->continue_through_errors && !$this->non_person_errors)) && $this->confirm_gender ){
          $this->confirm_gender();
        } elseif ((!self::is_errors() || ($this->continue_through_errors && !$this->non_person_errors)) && $this->confirm_concur(true)){
          $this->confirm_concur();
        } else {
          $this->do_form(4);
        }
      } else {
        if(self::is_errors()){
          $this->do_form($this->onstep);
        } else {
          $this->do_form($this->tostep);
        }
      }
    }
  }

  /**
   * Decides which step was requested and should be executed
   *
   * @param string $step
   * @return integer
   */
  protected function decide_step($step){
    $mod = array("next" => 1, "prev" => -1, "curr" => 0);

    $this->fccs = (array_key_exists("thisstep", self::$PARAMS)) ? self::$PARAMS["thisstep"] : null;

    switch((array_key_exists("thisstep", self::$PARAMS)) ? self::$PARAMS["thisstep"] : null){
      case "options":
        return 1 + $mod[$step]; break;
      case "repeat":
        return 2 + $mod[$step]; break;
      case "people":
        return 3 + $mod[$step]; break;
      case "thing":
        return 4 + $mod[$step]; break;
      case "conf":
        return 5 + $mod[$step]; break;
      default:
        $this->fccs = null;
        return 0 + $mod[$step]; break;
    }
  }

  protected function confirm_concur($justtf = false)
  {
    if(!empty($this->confirm_max_concur)){
    	$this->confirm_max_concur_which = null;
    	$keys = array_keys($this->confirm_max_concur);
    	foreach($keys as $cmcw)
    	{
    		if($this->confirm_max_concur[$cmcw])
    		{
			if($justtf){ return true; }
    			$sql = "SELECT *, id as loctag_id FROM loctags WHERE id = '".$cmcw."' LIMIT 1";
    			$q = self::$DB->query($sql);
    			$res = self::$DB->fetch($q);
    			if($res)
    			{
    				$this->confirm_max_concur_which = $res;
    				break;
    			}
    		}
    	}
	if(!$justtf)
	{
    		$this->output_page("form_confirm_concur", "inline");
	}
	else
	{
		return false;
	}
    }
  }

  /**
   * Outputs the appropriate form for a step
   *
   * @param integer $step
   */
  protected function do_form($step){
    if($this->confirm_length){ $this->output_page("form_confirm_length", "inline"); }
    elseif($this->confirm_appthour){ $this->output_page("form_confirm_appthour", "inline"); }
    elseif($this->confirm_time){ $this->confirm_time();}
    elseif($this->confirm_finals){ $this->confirm_finals();}
    else {

      switch($step){
        case 2:
          $this->output_page("repeat_info","inline");
          break;
        case 3:
          $this->all_rcs = array();
          $sql = "SELECT *, id as consultant_id FROM consultants WHERE id != '".$this->thisguy->consultant_id."' AND status = 'active' ORDER BY ".CONFIG::SQL_REALNAME_ORDER_CLAUSE;
          $q = self::$DB->query($sql);
          while($row = self::$DB->fetch($q)){
            $this->all_rcs[] = array(Consultant::select_name($row), $row->consultant_id);
          }

          $this->output_page("multi_user_info","inline");
          break;
        case 4:
        case 5:
          $this->all_locs = array();
          $sql = "SELECT *, locations.id as location_id, locations.name as location_name, loczones.id as loczone_id, loczones.name as loczone_name FROM locations, loczones WHERE locations.appttype_id = '".$this->appttype_id."' AND loczones.id = locations.loczone_id ORDER BY locations.name, loczones.name";
          $q = self::$DB->query($sql);
          while($row = self::$DB->fetch($q)){
            $this->all_locs[] = array(Location::select_name($row), $row->location_id);
          }

          $this->output_page(($this->type == "Meeting" || $this->type == "Meecket") ? "meeting_info" : "ticket_info", "inline");
          break;
        default:
          $temp = Consultant::ats_allowed($this->thisguy);
          $as = array();
          $as2 = array();
          $sql = "SELECT *, id as appttype_id FROM appttypes WHERE id IN ('".implode("','", $this->ats)."') AND FIND_IN_SET('".TOOLS::weekday_transform(TOOLS::wday_for($this->date))."', appttypes.weekdays_allowed)";
          $q = self::$DB->query($sql);
          while($row = self::$DB->fetch($q)){
            $as[] = $row;
          }
          foreach($as as $a){
            if(!is_null($this->thing) && $temp[$this->USER->ats_key][Appttype::ats_key($a)] && in_array($this->thing->tm_type, explode(",", $a->tm_class))){
              $as2[] = $a;
            } elseif (is_null($this->thing) && $temp[$this->USER->ats_key][Appttype::ats_key($a)]) {
              $as2[] = $a;
            }
          }
          $this->all_ats = TOOLS::array_collect($as2, '$a', 'array(Appttype::select_name($a), $a->appttype_id)');
          $this->output_page("form_new","inline");
          break;
      }
    }
  }

  /**
   * First level of variable validation
   *
   * @param boolean $recurse
   * @param mixed $backstep
   * @return boolean true
   */
  protected function setup_create_vars_1($recurse, $backstep){
    $this->confirm_location = false;
    $this->confirm_gender = false;
    $this->confirm_time = false;
    $this->confirm_finals = false;
    $this->confirm_max_concur = array();
    $this->confirm_length = false;
    $this->confirm_appthour = false;
    if(!array_key_exists("fi", self::$PARAMS) || !is_array(self::$PARAMS["fi"])){ self::$PARAMS["fi"] = array();}
    $this->start = TOOLS::string_to_time(self::$PARAMS["starttime"]);
    if(!$recurse and !$backstep == 1){
      $this->stop = (is_array(self::$PARAMS["stoptime"])) ? TOOLS::string_to_time(implode(":", self::$PARAMS["stoptime"])) : ((array_key_exists("corrected_time", self::$PARAMS) && self::$PARAMS["corrected_time"] == "yes") ? TOOLS::string_to_time(self::$PARAMS["stoptime"]) : TOOLS::x_minutes_since(30, TOOLS::string_to_time(self::$PARAMS["stoptime"])));
    } else {
      $this->stop = (is_array(self::$PARAMS["stoptime"])) ? TOOLS::string_to_time(implode(":", self::$PARAMS["stoptime"])) : TOOLS::string_to_time(self::$PARAMS["stoptime"]);
    }
    if(!array_key_exists("stopmax", self::$PARAMS["fi"])){$this->stopmax = $this->stop;}
    else{$this->stopmax = TOOLS::string_to_time(self::$PARAMS["fi"]["stopmax"]);}
    $this->date = TOOLS::string_to_date(self::$PARAMS["date"]);
    $this->time_override = (array_key_exists("time_conf", self::$PARAMS) && self::$PARAMS["time_conf"] == "yes") ? "yes" : null;
    $this->finals_override = (array_key_exists("finals_conf", self::$PARAMS) && self::$PARAMS["finals_conf"] == "yes") ? "yes" : null;

    $sql = "SELECT *, id as consultant_id FROM consultants WHERE id = '".self::$PARAMS["consultant"]."' AND status = 'active' LIMIT 1";
    $q = self::$DB->query($sql);
    if(self::$DB->rows($q) == 1){
      $this->thisguy = self::$DB->fetch($q);
    } else {
      $this->thisguy = null;
    }
    $this->other_rcs = array();
    $this->rep_day = array();

    if(is_null($this->thisguy)){ self::throwError("Consultant was not found."); }
    if($this->start >= $this->stop){ self::throwError("Stop time is not after start time."); }
    if($this->stop > $this->stopmax){ self::throwError("Selected stop time is not valid."); }

    if(($this->date < TOOLS::date_today() || ($this->date == TOOLS::date_today() && $this->start - ((int)$this->config_val("appt_buffer") * 3600) < time() ))){//TOOLS::x_minutes_since((int)$this->config_val("appt_buffer") * -60, $this->start) < TOOLS::time_now()))){
      $this->check_override_time();
    }

    if($this->date <= TOOLS::date_today() && $this->config_val("finals_flag")){
      $this->check_override_finals();
    }


    if(!is_null($this->thing) && $this->USER->access() < ACCESS::modify && ($this->thing->tm_type == "Meeting" || $this->thing->tm_type == "Meecket")){
      self::throwError("You are not allowed to schedule or move Meetings.");
    }

    if($this->reschedule && !$this->loc_id){
      $this->loc_id = $this->app->location_id;
    }

    if($this->reschedule && !$this->locdetails){
      $this->locdetails = $this->app->locdetails;
    }

    return true;
  }

  /**
   * Check to see if a time override is necessary
   *
   * @return boolean true
   */
  protected function check_override_time(){
    if($this->USER->access() >= $this->override_val("override_time")){
      if(!$this->time_override){$this->confirm_time = true;}
    } else {
      self::throwError("You are not allowed to schedule an appointment this close to the start time.");
    }

    return true;
  }

  /**
   * Check to see if a finals override is necessary
   *
   * @return boolean true
   */
  protected function check_override_finals(){
      if($this->USER->access() >= $this->override_val("override_finals")){
        if(!$this->finals_override){$this->confirm_finals = true;}
      } else {
        self::throwError("You are not allowed to schedule an appointment on that day.");
      }

      return true;
  }


  /**
   * Second stage input validation
   *
   * @param boolean $recurse
   * @param mixed $backstep
   * @return boolean true
   */
  protected function setup_create_vars_2($recurse, $backstep){
        $this->appttype_id = ((array_key_exists("appttype_id", self::$PARAMS["fi"])) ? (int)self::$PARAMS["fi"]["appttype_id"] : 1);
        $this->multi_user = (array_key_exists("multi_user", self::$PARAMS["fi"])) ? self::$PARAMS["fi"]["multi_user"] : null;
        $this->special2 = (array_key_exists("special2", self::$PARAMS["fi"])) ? self::$PARAMS["fi"]["special2"] : null;
        $this->repeat = (array_key_exists("repeat", self::$PARAMS["fi"])) ? self::$PARAMS["fi"]["repeat"] : null;
        $this->concur_override = array();
        if(array_key_exists("concur_override", self::$PARAMS) && is_array(self::$PARAMS["concur_override"]))
        {
        	foreach(self::$PARAMS["concur_override"] as $loctag => $answ)
        	{
        		$this->concur_override[(int)$loctag] = ($answ == "yes") ? "yes" : null;
        	}
        }
        #$this->concur_override = (self::$PARAMS["concur_override"] == "yes") ? "yes" : null;
        $this->length_override = (self::$PARAMS["length_override"] == "yes") ? "yes" : null;
        $this->appthour_override = (self::$PARAMS["appthour_override"] == "yes") ? "yes" : null;

        if(!is_null($this->thing)){
          $this->special2 = ($this->thing->tm_type == "Meeting") ? "meeting" : (($this->thing->tm_type == "Meecket") ? "meecket" : "regular");
        }

        if(!$this->special2){ $this->special2 = "regular"; }
        if(!$this->multi_user){ $this->multi_user = "FALSE"; }
        if(!$this->repeat){ $this->repeat = "FALSE"; }
        if($this->repeat == "FALSE"){ $this->do2 = false; }
        if($this->multi_user == "FALSE"){ $this->do3 = false; }

        $sql = "SELECT *, appttypes.id as appttype_id, appttypes.name as appttype_name, locations.id as location_id FROM appttypes, locations WHERE appttypes.id = '".$this->appttype_id."' AND appttypes.id = locations.appttype_id";
        $q = self::$DB->query($sql);
        $this->appttype = null;
        if(self::$DB->rows($q) >= 1){
          while($row = self::$DB->fetch($q)){
            if(is_null($this->appttype)){$this->appttype = $row;}
            if(!is_array($this->appttype->locations)){$this->appttype->locations = array();}
            $this->appttype->locations[$row->location_id] = $row;
          }
        } else {
          self::throwError("Appointment type requested was not found.");
        }

        if($this->appttype){
          $ats_allowed = Consultant::ats_allowed($this->thisguy);
          if(!in_array($this->appttype_id, $this->ats) || !$ats_allowed[$this->USER->ats_key][Appttype::ats_key($this->appttype)]){
            self::throwError("You are not allowed to schedule an appointment of that type with that consultant.");
          }
          if($this->special2 == "meeting"){ $this->type = "Meeting";}
          elseif($this->special2 == "meecket"){ $this->type = "Meecket";}
          else{$this->type = "Ticket";}

          $temp = explode(",",$this->appttype->tm_class);
          if(!in_array($this->type, $temp)){
            $this->special2 = ($this->type == "Ticket") ? "regular" : null;
            $this->type = $temp[0];
          }

          #if($this->appttype->max_concurrent_appts > 0 && ApptChecks::max_concurrent_appts($this->appttype, $this->date, $this->start, $this->stop) >= $this->appttype->max_concurrent_appts){
          #  $this->check_override_concurrent();
          #}

          if((int)($this->stop - $this->start) / (30*60) < (int)$this->appttype->min_appt_length){
            $this->check_override_length();
          }

          $t = MyFunctions::apptHoursDataFor(array($this->date), array($this->appttype->appttype_id));
          $this->ahd = $t["aphours"];
          if(!Appthour::valid_schedule($this->ahd, $this->appttype->appttype_id, $this->date, $this->start, $this->stop)){
            $this->check_override_appthour();
          }

          if(!in_array(TOOLS::weekday_transform(TOOLS::wday_for($this->date)), explode(",", $this->appttype->weekdays_allowed))){
            self::throwError(Appttype::select_name($this->appttype)." appointments are not allowed on ".TOOLS::$daynames[TOOLS::wday_for($this->date)]);
          }
        }

        return true;
  }

  /**
   * Check whether a concurrency override is needed
   *
   * @return boolean true
   */
  protected function check_override_concurrent($loctag){
            if($this->USER->access() >= $this->override_val("override_concur")){
              if(!array_key_exists((int)$loctag, $this->concur_override) || $this->concur_override[(int)$loctag] != "yes"){
              	$this->confirm_max_concur[(int)$loctag] = true;
              }
            } else {
              self::throwError("There are already too many appointments at that time and location set.");
            }

            return true;
  }

  /**
   * Check to see if a length override is needed
   *
   * @return boolean true
   */
  protected function check_override_length(){
            if($this->USER->access() >= $this->override_val("override_length")){
              if($this->length_override != "yes"){ $this->confirm_length = true; }
            } else {
              self::throwError("The appointment you are trying to schedule is too short for the selected appointment type.");
            }

            return true;
  }

  /**
   * Check to see if a appthour override is needed
   *
   * @return boolean true
   */
  protected function check_override_appthour(){
            if($this->USER->access() >= $this->override_val("override_appthour")){
              if($this->appthour_override != "yes"){ $this->confirm_appthour = true; }
            } else {
              self::throwError("The appointment you are trying to schedule is outside of acceptable hours for that type of appointment.");
            }

            return true;
  }

  /**
   * Third stage of input validation
   *
   * @param boolean $recurse
   * @param mixed $backstep
   * @return boolean true
   */
  protected function setup_create_vars_3($recurse, $backstep){
        if($this->do2){
          if(is_array(self::$PARAMS["fi"]) && array_key_exists("enddate", self::$PARAMS["fi"])){
            if(is_array(self::$PARAMS["fi"]["enddate"])){
              $this->enddate = TOOLS::string_to_date(implode("-",self::$PARAMS["fi"]["enddate"]));
            } else {
              $this->enddate = TOOLS::string_to_date(self::$PARAMS["fi"]["enddate"]);
            }
          } else {
            $this->enddate = null;
          }

          if(array_key_exists("repetition_day",self::$PARAMS)){
            if(is_array(self::$PARAMS["repetition_day"])){
              $this->rep_day = self::$PARAMS["repetition_day"];
            } else {
              $this->rep_day = explode(",",self::$PARAMS["repetition_day"]);
            }
          } else {
            $this->rep_day = array();
          }

          if(is_array(self::$PARAMS["fi"]) && array_key_exists("repetition_week", self::$PARAMS["fi"])){
            $this->rep_week = (int)self::$PARAMS["fi"]["repetition_week"];
          } else {
            $this->rep_week = 1;
          }

          if(!$this->enddate){ $this->enddate = TOOLS::date_today();}
          if(!$backstep || $backstep > 3){
            if($this->enddate < $this->date){ self::throwError("Ending date was not a valid choice."); }
            if(count($this->rep_day) == 0){ self::throwError("You must pick at least one day of the week for repetition."); }
            elseif($this->enddate == $this->date && !in_array(TOOLS::weekday_transform(TOOLS::wday_for($this->enddate)) , $this->rep_day)){ self::throwError("You must select at least one day of the week between the start and end dates."); }
            if($this->rep_week <= 0){ self::throwError("Repetition weeks must be a positive integer."); }
          }
        }

        return true;
  }

  /**
   * Fouth stage of input validation
   *
   * @param boolean $recurse
   * @param mixed $backstep
   * @return boolean true
   */
  protected function setup_create_vars_4($recurse, $backstep){
        if($this->do3){
          $this->other_rcs = ((array_key_exists("other_consultants", self::$PARAMS)) ? ((!is_array(self::$PARAMS["other_consultants"])) ? explode(",",self::$PARAMS["other_consultants"]) : self::$PARAMS["other_consultants"]) : array());

          if(count($this->other_rcs) > 0){
            $sql = "SELECT *, consultants.id as consultant_id FROM consultants WHERE consultants.id IN ('".implode("','", $this->other_rcs)."') AND status = 'active' ORDER BY ".CONFIG::SQL_REALNAME_ORDER_CLAUSE;
            $this->rc_names = array();
            $q = self::$DB->query($sql);
            if(self::$DB->rows($q) == count($this->other_rcs)){
              while($row = self::$DB->fetch($q)){
                $this->rc_names[] = Consultant::select_name($row);
              }
            }
          }

          if(!$backstep || $backstep > 4){
            if(count($this->other_rcs) == 0){ self::throwError("You must select one or more other consultants or go back and change the initial option."); }
          }
        }

        return true;
  }

  /**
   * Fifth stage of input validation
   *
   * @param boolean $recurse
   * @param mixed $backstep
   * @return boolean true
   */
  protected function setup_create_vars_5($recurse, $backstep){
        if(!$this->special2){
          self::throwError("Something went wrong.  Please start over.");
        }

        $this->locdetails = self::$PARAMS["fi"]["locdetails"];
        $sql = "SELECT *, locations.id as location_id, locations.name as location_name, loczones.id as loczone_id, loczones.name as loczone_name, appttypes.id as appttype_id, appttypes.name as appttype_name FROM locations, loczones, appttypes WHERE locations.id = '".self::$PARAMS["fi"]["loc_id"]."' AND loczones.id = locations.loczone_id AND appttypes.id = locations.appttype_id LIMIT 1";
        $this->location = null;
        $q = self::$DB->query($sql);
        if(self::$DB->rows($q) > 0){
          if(is_null($this->location)){$this->location = self::$DB->fetch($q);}
          $this->loc_id = $this->location->location_id;
        }

        if($this->location){
          /* concurrency checking */
          $ccounts = ApptChecks::max_concurrent_appts($this->loc_id, $this->date, $this->start, $this->stop);

          foreach($ccounts as $conc)
          {
            #print $conc["loctag"][0].":".$conc["loctag"][1].":".reset($conc["counts"]);
            reset($conc["counts"]);
            if($conc["loctag"][1] > 0 && current($conc["counts"]) >= $conc["loctag"][1])
            {
            	/* There are too many appointments at that location */
            	$this->check_override_concurrent($conc["loctag"][2]);
            }
          }

          $ats_allowed = Consultant::ats_allowed($this->thisguy);
          if(!in_array($this->location->appttype_id, $this->ats) || !$ats_allowed[$this->USER->ats_key][Appttype::ats_key($this->appttype)]){
            self::throwError("You are not allowed to schedule that type of appointment.");
          } elseif(!($this->do3 || $this->do2)) {
            $this->aps_data = MyFunctions::appointmentsDataFor(array($this->thisguy->consultant_id), array($this->date));
            $this->pappts = Consultant::previousAppointments($this->thisguy->consultant_id, $this->date, $this->start, $this->aps_data["appts"], $this->reschedule);
            $this->fappts = Consultant::followingAppointments($this->thisguy->consultant_id, $this->date, $this->stop, $this->aps_data["appts"], $this->reschedule);
            if($this->reschedule){
              $this->pappts = TOOLS::array_reject($this->pappts, '$pappt', '$pappt->appointment_id == '.$this->reschedule.'');
              $this->fappts = TOOLS::array_reject($this->fappts, '$fappt', '$fappt->appointment_id == '.$this->reschedule.'');
            }
            $this->pappts = TOOLS::array_select($this->pappts, '$pappt', 'MyFunctions::loczone_far($pappt->potentialh, $pappt->potentialv, '.$this->location->potentialh.', '.$this->location->potentialv.')');
            $this->fappts = TOOLS::array_select($this->fappts, '$fappt','MyFunctions::loczone_far($fappt->potentialh, $fappt->potentialv, '.$this->location->potentialh.','.$this->location->potentialv.')');
            if(!(count($this->pappts) == 0 && count($this->fappts) == 0) && (!array_key_exists("dist_conf", self::$PARAMS) || self::$PARAMS["dist_conf"] != "yes")){
              $this->check_override_far();
            } elseif ($this->location->restrict_gender != "FALSE" && $this->location->restrict_gender == $this->thisguy->gender && (!array_key_exists("gen_conf", self::$PARAMS) || self::$PARAMS["gen_conf"] != "yes")){
              $this->check_override_gender();
            }
          }
        } elseif(!$backstep || $backstep > 5){ //was >=
          self::throwError("The location requested was not found.");
        }

        if($this->type == "Meeting" || $this->type == "Meecket"){
          $this->subject = (array_key_exists("subject", self::$PARAMS["fi"])) ? self::$PARAMS["fi"]["subject"] : null;
          $this->details = (array_key_exists("details", self::$PARAMS["fi"])) ? self::$PARAMS["fi"]["details"] : null;
        } elseif($this->type == "Ticket"){
          $this->withperson = self::$PARAMS["fi"]["withperson"];
          $this->phone = self::$PARAMS["fi"]["phone"];
          $this->altphone = self::$PARAMS["fi"]["altphone"];
          $this->ticket = self::$PARAMS["fi"]["ticket"];
          $this->details = self::$PARAMS["fi"]["details"];

          if(self::$PARAMS["fi"]["foverride"] == "yes" || !is_null($this->thing)){
            $this->name = $this->withperson;
            $this->foverride = true;
          } else {
            $this->foverride = false;
            if(!$backstep || $backstep > 5){
              //$this->name = TOOLS::finger_name(self::$PARAMS["fi"]["withperson"]);
              $this->name = TOOLS::ldap_name(self::$PARAMS["fi"]["withperson"]);
              if(!$this->name){
                self::throwError("No user with that username was found.  Plase enter the user's Full Name instead.");
                $this->withperson = "";
                $this->foverride = true;
              }
            }
          }
        }

        return true;
  }

  /**
   * Determine if a distance override is needed
   *
   * @return boolean true
   */
  protected function check_override_far(){
              if($this->USER->access() >= $this->override_val("override_far")){
                $this->confirm_location = true;
              } else {
                self::throwError("Another appointment exists too far away in too short a time span for this consultant.");
              }

              return true;
  }

  /**
   * Determine if a gender override is needed
   *
   * @return boolean true
   */
  protected function check_override_gender(){
              if($this->USER->access() >= $this->override_val("override_gender")){
                $this->confirm_gender = true;
              } else {
                self::throwError("You are not allowed to schedule an appointment with that consultant at that location.");
              }

              return true;
  }

  /**
   * Input validation dispatcher
   *
   * @param integer $step
   * @param boolean $recurse
   * @param mixed $backstep
   *
   * @return boolean
   */
  protected function setup_create_vars($step, $recurse = false, $backstep = false){
    switch($step){
      case 1:
        $this->setup_create_vars_1($recurse, $backstep);
        break;
      case 2:
        $this->setup_create_vars(1, true, $backstep);
        $this->setup_create_vars_2($recurse, $backstep);
        break;
      case 3:
        $this->setup_create_vars(2, true, $backstep);
        $this->setup_create_vars_3($recurse, $backstep);
        break;
      case 4:
        $this->setup_create_vars(3, true, $backstep);
        $this->setup_create_vars_4($recurse, $backstep);
        break;
      case 5:
        //fall through
      case 6:
        $this->setup_create_vars(4, true, $backstep);
        $this->setup_create_vars_5($recurse, $backstep);
        break;
    }

    if(self::is_errors()){return false;}else{return true;}
  }

  /**
   * Finish the appointment creation
   */
  protected function process_create_total(){
    $this->continue_through_errors = false;
    $this->non_person_errors = self::is_errors();
    if(!Lockouts::exist($this->thisguy->consultant_id, $this->start, $this->stop, $this->date, $this->USER->info("username"))){
      self::throwError("You do not own a lockout for this time slot.");
    }

    if(!self::is_errors()){
      if($this->USER->access() >= ACCESS::modify && ($this->do2 || $this->do3)){
        $this->continue_through_errors = true;
        //$consultants = TOOLS::array_collect(array_merge(array($this->thisguy->consultant_id), $this->other_rcs), '$r', '(int) $r');
        $consultants = array_merge(array($this->thisguy->consultant_id), $this->other_rcs);
        if($this->do2){
          $results = ApptChecks::repeating_checks($this->date, $this->enddate, $this->start, $this->stop, $consultants, $this->special2, $this->rep_day,$this->rep_week, $this->USER->info("username"));
          foreach($consultants as $rc){
            foreach($results[$rc]["checks"] as $key => $value){
              if(!$value && $this->special2 != "meeting"){
                self::throwError("Appt Check (multi): ".$results[$rc]["reasons"][$key]);
              }
            }
          }
        } else {
          $results = ApptChecks::day_check($this->date, $consultants, $this->start, $this->stop, $this->special2, $this->USER->info("username"));
          foreach($consultants as $rc){
            if(!$results[$rc]["check"] && $this->special2 != "meeting"){
              self::throwError("Appt Check (single): ".$results[$rc]["reason"]);
            }
          }
        }
      } else {
        if(!Consultant::isFreeOn($this->thisguy->consultant_id, $this->date, $this->start, $this->stop, $this->aps_data["rchours"], $this->aps_data["appts"], $this->USER->info("username"))){
          if($this->USER->access() >= ACCESS::modify && $this->type == "Meeting"){
            $this->continue_through_errors = true;
            if(Consultant::hasConsultantHoursOn($this->thisguy->consultant_id, $this->date, $this->start, $this->stop, $this->aps_data["rchours"] ,true)){
              self::throwError("The consultant is not free on that date at that time.");
            }
          } elseif (($this->USER->access() >= ACCESS::modify && $this->type == "Meecket") || ($this->type == "Ticket")){
            self::throwError("The consultant is not free on that date at that time.");
          }
        }
      }

      if(!self::is_errors() || ($this->continue_through_errors && !$this->non_person_errors)){
        if(!is_null($this->thing)){
          if($this->thing->tm_type == "Meeting" && $this->subject && $this->details){
            if(!Meeting::update_attributes($this->thing->tm_id, array("subject" => $this->subject, "description" => $this->details))){
              $this->non_person_errors = true;
            }
          } elseif($this->thing->tm_type == "Meecket" && $this->subject && $this->details){
            if(!Meecket::update_attributes($this->thing->tm_id, array("subject" => $this->subject, "description" => $this->details))){
              $this->non_person_errors = true;
            }
          } elseif($this->thing->tm_type == "Ticket" && $this->name && ($this->phone || $this->altphone) && $this->ticket && $this->details){
            if(!Ticket::update_attributes($this->thing->tm_id, array("person" => $this->name, "phone" => $this->phone, "altphone" => $this->altphone, "remedy_ticket" => $this->ticket, "description" => $this->details))){
              $this->non_person_errors = true;
            }
          }
        } else {
          $id = null;
          if($this->type == "Meeting"){
            $id = Meeting::create(array("subject" => $this->subject, "description" => $this->details));
            if(!$id){kvframework_log::write_log("Meeting error".var_dump($id));}
            $table = "meetings";
          } elseif($this->type == "Meecket"){
            $id = Meecket::create(array("subject" => $this->subject, "description" => $this->details));
            $table = "meeckets";
          } elseif($this->type == "Ticket"){
            $id = Ticket::create(array("person" => $this->name, "phone" => $this->phone, "altphone" => $this->altphone, "remedy_ticket" => $this->ticket, "description" => $this->details));
            $table = "tickets";
          } else {
            self::throwError("this->type problem.");
            $this->non_person_errors = true;
          }

          if($id){
            $sql = "SELECT *, ".$table.".id as tm_id, '".$this->type."' as tm_type FROM ".$table." WHERE ".$table.".id = '".$id."' LIMIT 1";
            $q = self::$DB->query($sql);
            if(self::$DB->rows($q) == 1){
              $this->thing = self::$DB->fetch($q);
              if(!is_array($this->thing->appointments)){$this->thing->appointments = array();}
            } else {
              $this->thing = null;
              self::throwError("Ticket or Meeting created but not found.");
              $this->non_person_errors = true;
            }
          } else {
            self::throwError("ID wasn't retrieved.");
            $this->non_person_errors = true;
          }
        }
      }

      if((!self::is_errors() || ($this->continue_through_errors && !$this->non_person_errors)) && ($this->confirm_location || $this->confirm_gender || $this->confirm_time || $this->confirm_finals || $this->confirm_concur(true))){
        self::throwMessage("Please confirm this submission.");
      } elseif(!self::is_errors() || ($this->continue_through_errors && !$this->non_person_errors)){
        $ary = array(
          "tm_id" => $this->thing->tm_id,
          "tm_type" => $this->type,
          "starttime" => $this->start,
          "stoptime" => $this->stop,
          "startdate" => $this->date,
          "stopdate" => $this->date,
          "location_id" => $this->location->location_id,
          "locdetails" => $this->locdetails,
          "timestamp" => time(),
          "appointment_user" => $this->USER->info("username")
          );
          if($this->USER->access() >= ACCESS::modify && $this->repeat == "TRUE"){
            $ary["stopdate"] = $this->enddate;
            $ary = array_merge($ary, array("repeat" => $this->repeat, "repetition_day" => implode(",", $this->rep_day), "repetition_week" => $this->rep_week, "special2" => $this->special2));
          } elseif($this->USER->access() >= ACCESS::modify){
            $ary["special2"] = $this->special2;
          }

          $id = Appointment::create($ary);
          if($id){
            $sql = "SELECT *, id as appointment_id FROM appointments WHERE id = '".$id."' LIMIT 1";
            $q = self::$DB->query($sql);
            if(self::$DB->rows($q) == 1){
              $this->appt = self::$DB->fetch($q);
              $this->thing->appointments[] = $this->appt;
            } else {
              $this->appt = null;
              self::throwError("Getting Appointment failed miserably.");
            }
          }

          if($this->USER->access() >= ACCESS::modify && ($this->do2 || $this->do3)){
            $all_results = array();
            if($this->repeat == "TRUE"){
              foreach($consultants as $rc){
                foreach($results[$rc]["checks"] as $k => $v){
                  $all_results[] = $v;
                }
              }
            } else {
              $all_results = TOOLS::array_collect($results, '$r','$r["check"]');
            }

            if(in_array(true, $all_results)){
              if($this->appt){
                $consultant_objs = array();
                $sql = "SELECT *, id as consultant_id FROM consultants WHERE id IN ('".implode("','", $consultants)."') AND status = 'active' ORDER BY ".CONFIG::SQL_REALNAME_ORDER_CLAUSE;
                $q = self::$DB->query($sql);
                while($row = self::$DB->fetch($q)){
                  $consultant_objs[$row->consultant_id] = $row;
                }

                $gconsultants = array();
                if($this->repeat == "TRUE"){
                  foreach($consultants as $r){if(in_array(true,$results[$r]["checks"])){$gconsultants[] = $consultant_objs[$r];}}
                } else {
                  foreach($consultants as $r){if($results[$r]["check"]){$gconsultants[] = $consultant_objs[$r];}}
                }

                foreach($gconsultants as $grc){
                  Consultantappt::create(array("appointment_id" => $this->appt->appointment_id, "consultant_id" => $grc->consultant_id));
                }

                self::throwMessage("Appointment added for available consultants on all dates.");
                BackupFunctions::write_backup($this->appt, $gconsultants, $this->thing);

                $bconsultants = array();
                foreach($gconsultants as $grc){
                  if($this->repeat == "TRUE"){
                    foreach($results[$grc->consultant_id]["checks"] as $key => $value){
                      if(!array_key_exists($key, $bconsultants) || !is_array($bconsultants[$key])){$bconsultants[$key] = array();}
                      if(!$value){$bconsultants[$key][] = $grc;}
                    }
                  } else {
                    if(!array_key_exists($this->date, $bconsultants) || !is_array($bconsultants[$this->date])){$bconsultants[$this->date] = array();}
                    if(!$results[$grc->consultant_id]["check"]){$bconsultants[$this->date][] = $grc;}
                  }
                }

                if(count($bconsultants) != 0){ self::throwMessage("Removing unavailable dates for certain consultants.");}
                $rms_backup = array();
                foreach($bconsultants as $key => $value){
                  if(!count($value) == 0){
                    $aid = Appointment::create(array('tm_id' => $this->appt->tm_id, "tm_type" => $this->appt->tm_type, "starttime" => $this->appt->starttime, "stoptime" => $this->appt->stoptime, "startdate" => $key, "stopdate" => $key, "location_id" => $this->appt->location_id, "locdetails" => $this->appt->locdetails, "timestamp" => time(), "repeat" => "FALSE", "appointment_user" => $this->USER->info("username"), "special2" => $this->appt->special2, "special" => "repeat_removal", "removal_of" => $this->appt->appointment_id));
                    $rms_backup[$aid] = $value;
                    if($aid){
                      foreach($value as $v){
                        Consultantappt::create(array("appointment_id" => $aid, "consultant_id" => $v->consultant_id));
                        self::throwMessage("Consultant ".$v->username." removed from the appointment on ".TOOLS::date_to_s($key).".");
                      }
                    }
                  }
                }

                $sql = "SELECT *, id as appointment_id FROM appointments WHERE id IN ('".implode("','", array_keys($rms_backup))."')";
                $q = self::$DB->query($sql);
                while($row = self::$DB->fetch($q)){
                  BackupFunctions::write_backup($row, $rms_backup[$row->appointment-id], $this->thing);
                }

                Lockouts::destroy($this->USER->info("username"));
              } else {
                self::throwError("this->appt wasn't valid.");
                $this->non_person_errors = true;
              }
            } else {
              self::throwError("The appointment was not valid for any selected consultant.");
            }
          } else {
            if($this->appt){
              if(!Appointment::isInOphours($this->appt, $this->date, $this->aps_data["ophours"])){
                self::throwError("Appointment is not within operating hours.");
              } else {
                Consultantappt::create(array("appointment_id" => $this->appt->appointment_id, "consultant_id" => $this->thisguy->consultant_id));
                Lockouts::destroy($this->USER->info("username"));
                self::throwMessage("Appointment scheduled on ".TOOLS::date_to_s($this->date)." with ".Consultant::select_name($this->thisguy)." from ".TOOLS::time_to_s($this->start, true)." to ".TOOLS::time_to_s($this->stop, true).".");
                BackupFunctions::write_backup($this->appt, array($this->thisguy), $this->thing);
              }
            }
          }

          if(!self::is_errors()){
            $sql = "SELECT *, consultantappts.rapid as consultantappt_id, consultants.id as consultant_id FROM consultantappts, consultants WHERE consultantappts.appointment_id = '".$this->appt->appointment_id."' AND consultants.id = consultantappts.consultant_id AND consultants.status = 'active'";
            $q = self::$DB->query($sql);
            while($row = self::$DB->fetch($q)){
              if(!is_array($this->appt->consultants)){$this->appt->consultants = array();}
              $this->appt->consultants[] = $row;
            }
          }
      }


    }
  }

  /**
   * What happens when a confirmation request is denied
   */
  protected function failed_confirm(){
    self::throwMessage("Confirmation denied.");
    Lockouts::destroy($this->USER->info("username"));
    $this->render_close_box();
  }

  /**#@+
   * Wrapper for failed_confirm
   *
   * @see appointment_site_class::failed_confirm()
   */
  public function failed_confirm_far(){$this->failed_confirm();}
  public function failed_confirm_gender(){$this->failed_confirm();}
  public function failed_confirm_time(){$this->failed_confirm();}
  public function failed_confirm_finals(){$this->failed_confirm();}
  public function failed_confirm_concur(){$this->failed_confirm();}
  /**#@-*/

  /**#@+
   * Output confirmation request pages
   */
  public function confirm_far_away(){
    $this->output_page("form_confirm_far","inline");
  }

  public function confirm_gender(){
    $this->output_page("form_confirm_gender","inline");
  }

  public function confirm_time(){
    $this->output_page("form_confirm_time","inline");
  }

  public function confirm_finals(){
    $this->output_page("form_confirm_finals","inline");
  }

  /*public function confirm_concur(){
    $this->output_page("form_confirm_concur","inline");
  }*/
  /**#@-*/

  /**
   * View the details of an appointment
   */
  public function view_appointment(){
    Lockouts::destroy($this->USER->info("username"));
    $this->appointment = Consultant::check_appt((int)self::$PARAMS["rid"], (int)self::$PARAMS["id"]);
    if($this->appointment){
      $this->rc =& $this->appointment->consultants[(int)self::$PARAMS["rid"]];
      if(array_key_exists("adate", self::$PARAMS) || $this->appointment->repeat != 'TRUE'){
        if(!array_key_exists("adate", self::$PARAMS)){self::$PARAMS["adate"] = $this->appointment->startdate;}
        $this->output_page("info_display","inline");
      } else {
        self::throwError("There was no date parameter provided for that repeating appointment.");
        $this->render_close_box();
      }
    } else {
      if(array_key_exists("id", self::$PARAMS)){
        self::throwError("The requested appointment or associated consultant could not be found.");
      }
      $this->render_close_box();
    }
  }

  /**
   * Appointment modify form
   */
  public function modify_appointment(){
    Lockouts::destroy($this->USER->info("username"));

    $sql = "SELECT *, appointments.id as appointment_id, consultants.id as consultant_id, locations.id as location_id FROM appointments, consultants, consultantappts, locations WHERE appointments.id = '".self::$PARAMS["aid"]."' AND locations.id = appointments.location_id AND consultantappts.appointment_id = appointments.id AND consultants.id = consultantappts.consultant_id LIMIT 1";
    $q = self::$DB->query($sql);
    $this->appointment = null;
    if(self::$DB->rows($q) >= 1){
      while($row = self::$DB->fetch($q)){
        if(is_null($this->appointment)){$this->appointment = $row;}
        if(!is_array($this->appointment->consultants)){$this->appointment->consultants = array();}
        $this->appointment->consultants[] = $row;
      }
    }

    $this->all_locs = array();
    $sql = "SELECT *, locations.id as location_id, locations.name as location_name, loczones.id as loczone_id, loczones.name as loczone_name FROM locations, loczones WHERE locations.appttype_id = '".$this->appointment->appttype_id."' AND loczones.id = locations.loczone_id ORDER BY loczones.name, locations.name";
    $q = self::$DB->query($sql);
    while($row = self::$DB->fetch($q)){
      $this->all_locs[] = array(Location::select_name($row), $row->location_id);
    }

    if(!is_null($this->appointment)){
      //if(($this->appointment->tm_type == "Meeting" || $this->appointment->tm_type == "Meecket") && self::$USER->access < ACCESS::modify){
      if(($this->appointment->tm_type == "Meeting" || $this->appointment->tm_type == "Meecket") && $this->USER->access() < ACCESS::modify){
        self::throwError("You do not have permission to edit that appointment.");
        $this->render_close_box();
      } else {
        $this->output_page("form_modify","inline");
      }
    } else {
      if(array_key_exists("aid", self::$PARAMS)){
        self::throwError("The requested appointment or associated consultant could not be found");
      } else {
        self::throwError("An unknown error occurred.");
      }

      $this->render_close_box();
    }
  }

  /**
   * Appointment modify form processing
   */
  public function process_modify_form(){
    Lockouts::destroy($this->USER->info("username"));

    $this->appointment = null;
    $sql = "SELECT *, appointments.id as appointment_id, consultantappts.rapid as consultantappt_id, consultants.id as consultant_id FROM appointments, consultantappts, consultants WHERE appointments.id = '".self::$PARAMS["aid"]."' AND consultantappts.appointment_id = appointments.id AND consultants.id = consultantappts.consultant_id";
    $q = self::$DB->query($sql);
    if(self::$DB->rows($q) >= 1){
      while($row = self::$DB->fetch($q)){
        if(is_null($this->appointment)){ $this->appointment = $row; }
        if(!is_array($this->appointment->consultantappts)){ $this->appointment->consultantappts = array(); }
        $this->appointment->consultantappts[] = $row;
      }
    }

    $sql2 = "SELECT *, id as tm_id, '".$this->appointment->tm_type."' as tm_type FROM ".strtolower($this->appointment->tm_type)."s WHERE id = '".$this->appointment->tm_id."' LIMIT 1";
    $q2 = self::$DB->query($sql);
    if(self::$DB->rows($q2) == 1){
      $this->appointment->tm = self::$DB-> fetch($q2);
    }

    if(!is_null($this->appointment)){
      if(($this->appointment->tm_type == "Meeting" || $this->appointment->tm_type == "Meecket") && $this->USER->access() < ACCESS::modify){
        self::throwError("You do not have permission to edit that appointment.");
        $this->render_close_box();
      } else {
        $sql = "SELECT *, id as location_id FROM locations WHERE id = '".self::$PARAMS["fi"]["loc_id"]."' LIMIT 1";
        $q = self::$DB->query($sql);
        if(self::$DB->rows($q) == 1){
          $this->location = self::$DB->fetch($q);
          if(!in_array($this->location->appttype_id, $this->ats)){
            self::throwError("The location is not valid for this appointment type.");
          }
        } else {
          self::throwError("Location not found.");
        }

        $updates = array(
          "location_id" => $this->location->location_id,
          "locdetails" => self::$PARAMS["fi"]["locdetails"],
          "ical_sequence" => $this->appointment->ical_sequence + 1
          );

          if(!self::is_errors() && Appointment::update_attributes($this->appointment->appointment_id, $updates)){
            self::throwMessage("Appointment modified successfully.");
            BackupFunctions::write_backup($this->appointment, $this->appointment->consultantappts, $this->appointment->tm);

            $sql = "UPDATE consultantappts SET confirmed = 'FALSE', confirm_version = confirm_version + 1 WHERE rapid IN (".implode(",",TOOLS::array_collect($this->appointment->consultantappts, '$r','$r->consultantappt_id')).")";
            $q = self::$DB->query($sql);

            foreach($this->appointment->consultantappts as $rca){
              Mailer::deliver_modify_appointment($this->appointment->appointment_id, $rca, $this->config_val("email_from"), $rca->confirm_version +1, MyFunctions::datetime_in_appt(TOOLS::date_today(), TOOLS::string_to_time($this->appointment->starttime), $this->appointment), TOOLS::string_to_time($this->appointment->starttime));
            }

            $this->render_close_box();
          } else {
            self::throwError("Appointment NOT modified successfully.");
            $this->modify_appointment();
          }
      }
    } else {
      if(array_key_exists("aid", self::$PARAMS)){
        self::throwError("The requested appointment or associated consultant could not be found.");
      } else {
        self::throwError("An unknown error occurred.");
      }
      $this->render_close_box();
    }
  }

  /**
   * Appointment deletion form
   */
  public function delete_appointment(){
    Lockouts::destroy($this->USER->info("username"));

    $this->appointment = Consultant::check_appt((int)self::$PARAMS["rid"], (int)self::$PARAMS["aid"]);

    if($this->appointment && array_key_exists("adate", self::$PARAMS)){
      if(!in_array($this->appointment->appttype_id, $this->ats) || ($this->USER->access() < ACCESS::modify && $this->appointment->repeat == "TRUE")){
        self::throwError("You are not permitted to delete that appointment.");
        $this->render_close_box();
      } else {
        $this->rc =& $this->appointment->consultants[(int)self::$PARAMS["rid"]];
        $this->types = array((count($this->appointment->consultants) > 1) ? true : false, ($this->appointment->repeat == "TRUE") ? true : false);
        $this->output_page("form_delete","inline");
      }
    } else {
      if(array_key_exists("aid", self::$PARAMS) && array_key_exists("adate", self::$PARAMS)){
        self::throwError("The requested appointment or associated consultant could not be found.");
      } else {
        self::throwError("An unknown error occurred.");
      }

      $this->render_close_box();
    }
  }

  /**
   * Appointment deletion form processing
   */
  public function process_delete_form(){
    Lockouts::destroy($this->USER->info("username"));

    $this->appointment = Consultant::check_appt((int)self::$PARAMS["rid"], (int)self::$PARAMS["aid"]);
    if($this->appointment && array_key_exists("confirm_delete", self::$PARAMS) && self::$PARAMS["confirm_delete"] == "yes" && array_key_exists("confirm_type", self::$PARAMS) && array_key_exists("confirm_scale", self::$PARAMS)){
      $this->types = array((count($this->appointment->consultants) > 1) ? true : false, ($this->appointment->repeat == "TRUE") ? true : false);
      if(!in_array($this->appointment->appttype_id, $this->ats) || ($this->USER->access() < ACCESS::modify && ($this->appointment->repeat == "TRUE" || count($this->appointment->consultants) > 1))){
        self::throwError("You are not permitted to delete that appointment.");
        $this->render_close_box();
      }  else {
        $this->rc =& $this->appointment->consultants[(int)self::$PARAMS["rid"]];
        if(self::$PARAMS["confirm_type"] == "general"){
          if(!$this->types[1] || self::$PARAMS["confirm_scale"] == "all"){
            if(Consultantappt::destroy($this->rc->consultantappt_id)){
              self::throwMessage("Consultant removed from the appointment.");
              Mailer::deliver_delete_appointment($this->appointment, $this->rc, $this->config_val("email_from"));
            } else {
              self::throwError("Consultant NOT removed from the appointment.");
            }
          } elseif (self::$PARAMS["confirm_scale"] == "once" && array_key_exists("adate", self::$PARAMS)){
            $pars = array(
              "tm_id" => $this->appointment->tm_id,
              "tm_type" => $this->appointment->tm_type,
              "starttime" => TOOLS::string_to_time($this->appointment->starttime),
              "stoptime" => TOOLS::string_to_time($this->appointment->stoptime),
              "startdate" => TOOLS::string_to_date(self::$PARAMS["adate"]),
              "stopdate" => TOOLS::string_to_date(self::$PARAMS["adate"]),
              "location_id" => $this->appointment->location_id,
              "locdetails" => $this->appointment->locdetails,
              "timestamp" => time(),
              "repeat" => "FALSE",
              "appointment_user" => $this->USER->info("username"),
              "special2" => $this->appointment->special2,
              "special" => "repeat_removal",
              "removal_of" => $this->appointment->appointment_id
              );

              $a = Appointment::create($pars);
              if($a){
                $sql = "SELECT *, id as appointment_id FROM appointments WHERE id = '".$a."' LIMIT 1";
                $q = self::$DB->query($sql);
                $ap = self::$DB->fetch($q);
                $pars = array(
                "consultant_id" => $this->rc->consultant_id,
                "appointment_id" => $a
                );
                if(Consultantappt::create($pars)){
                  self::throwMessage("Consultant removed from the appointment on that date.");
                  Mailer::deliver_delete_appointment($this->appointment, $this->rc, $this->config_val("email_from"), $ap);
                  BackupFunctions::unwrite_backup($ap);
                } else {
                  self::throwError("Consultant was unable to be removed from the appointment on that date.");
                }
              } else {
                self::throwError("Consultant was unable to be removed from the appointment on that date.");
              }
          }
        } elseif(self::$PARAMS["confirm_type"] == "multi"){
          if(!$this->types[1] || self::$PARAMS["confirm_scale"] == "all"){
            if(Appointment::destroy($this->appointment->appointment_id)){
              $this->appointment = null;
              self::throwMessage("All consultants removed from the appointment successfully.");
            } else {
              self::throwError("All consultants NOT successfully removed from the appointment.");
            }
          } elseif(self::$PARAMS["confirm_scale"] == "once" && array_key_exists("adate", self::$PARAMS)){
            $pars = array(
              "tm_id" => $this->appointment->tm_id,
              "tm_type" => $this->appointment->tm_type,
              "starttime" => TOOLS::string_to_time($this->appointment->starttime),
              "stoptime" => TOOLS::string_to_time($this->appointment->stoptime),
              "startdate" => TOOLS::string_to_date(self::$PARAMS["adate"]),
              "stopdate" => TOOLS::string_to_date(self::$PARAMS["adate"]),
              "location_id" => $this->appointment->location_id,
              "locdetails" => $this->appointment->locdetails,
              "timestamp" => time(),
              "repeat" => "FALSE",
              "appointment_user" => $this->USER->info("username"),
              "special2" => $this->appointment->special2,
              "special" => "repeat_removal",
              "removal_of" => $this->appointment->appointment_id
              );

              $a = Appointment::create($pars);
              if($a){
                $sql = "SELECT *, id as appointment_id FROM appointments WHERE id = '".$a."' LIMIT 1";
                $q = self::$DB->query($sql);
                $ap = self::$DB->fetch($q);

                $good = true;
                foreach($this->appointment->consultants as $rc){
                  $pars = array(
                  "consultant_id" => $rc->consultant_id,
                  "appointment_id" => $a
                  );
                  if($good && !Consultantappt::create($pars)){
                    $good = false;
                  }
                }

                if($good){
                  self::throwMessage("All consultants were removed from that appointment on that date.");
                  foreach($this->appointment->consultants as $rc){
                    Mailer::deliver_delete_appointment($this->appointment, $rc, $this->config_val("email_from"), $ap);
                  }
                  BackupFunctions::unwrite_backup($ap);
                } else {
                  self::throwError("Consultants were unable to be removed from the appointment on that date.");
                }
              } else {
                self::throwError("Consultants were unable to be removed from the appointment on that date.");
              }
          }
        }

        $this->appointment2 = Consultant::check_appt((int)self::$PARAMS["rid"], (int)self::$PARAMS["aid"]);
        if($this->appointment2 && count($this->appointment2->consultants) == 0){
          BackupFunctions::unwrite_backup($this->appointment2);
          if(Appointment::destroy($this->appointment2->appointment_id)){
            self::throwMessage("Appointment deleted since there were no consultants attached.");
          } else {
            self::throwError("Appointment NOT deleted despite no consultants attached.");
          }
        } else {

        }
        $this->render_close_box();
      }
    } else {
      self::throwMessage("Appointment not deleted due to bad parameters or request.");
      self::$PARAMS["id"] = self::$PARAMS["aid"];
      $this->render_close_box();
    }
  }

}
?>
