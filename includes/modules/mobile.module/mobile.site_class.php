<?php
/**
 * KvScheduler - Mobile SiteClass
 * @package KvScheduler
 * @subpackage Modules
 * @author Greg McWhirter <gsmcwhirter@gmail.com>
 * @version 1.0
 */

load_files(
  KVF_MODULES."/display.module/lib/mycalendar.class.php",
  KVF_MODULES."/calendar.module/lib/schedulerblock.class.php"
);

/**
 * Mobile access point
 *
 * @package KvScheduler
 * @subpackage Modules
 *
 */
class mobile_site_class extends application_site_class{
  /**
   * Flag for something or other...
   *
   * @var boolean
   */
  protected $flag = false;

  /**
   * Consultant record
   *
   * @var mixed
   */
  protected $consultant;

  /**
   * Appointment information
   *
   * @var array
   */
  protected $fah;

  /**
   * Appointment count
   *
   * @var integer
   */
  protected $fahc;

  /**
   * Unconfirmed appointments
   *
   * @var array
   */
  protected $ucac;

  /**
   * Values for a select generators
   *
   * @var array
   */
  protected $all_rcs;

  /**
   * Constructor
   *
   */
  function __construct(){
    parent::__construct();
    $this->auth_level = ACCESS::display;
    $this->mobile = true;
    $this->set_layout(CONFIG::tempname_mobile);
  }

  /**
   * The index page - redirects if a lower access
   *
   */
  public function index(){
    if($this->USER->connected() && $this->USER->access() >= ACCESS::user){
      $this->all_rcs = array();
      $sql = "SELECT *, id as consultant_id FROM consultants WHERE status = 'active' ORDER BY SUBSTRING(realname FROM (INSTR(realname, ' ')+1)) ASC";
      $q = self::$DB->query($sql);
      while($row = self::$DB->fetch($q)){
        $this->all_rcs[] = array(Consultant::select_name($row), $row->consultant_id);
      }

      $this->output_page("index");
    } elseif($this->USER->connected()) {
      $this->show_appt_list($this->USER->rcinfo("id"));
    } else {
      $this->output_page("mlogin_form", "full", "user");
    }
  }

  /**
   * Show the page for a certain user
   *
   */
  public function user_appts(){
    if($this->USER->access() >= ACCESS::user){
      $this->flag = true;
      if(!array_key_exists("rcid", self::$PARAMS)){
        self::throwError("Invalid parameters");
        $this->index();
      } else {
        $this->show_appt_list(self::$PARAMS["rcid"], true);
      }
    } else {
      $this->index();
    }
  }

  /**
   * Show the page of all appointments
   *
   */
  public function all_appts(){
    if($this->USER->access() >= ACCESS::user){
      $arcs = array();
      $sql = "SELECT *, id as consultant_id FROM consultants WHERE status = 'active' ORDER BY SUBSTRING(realname FROM (INSTR(realname, ' ')+1)) ASC";
      $q = self::$DB->query($sql);
      while($row = self::$DB->fetch($q)){
        $arcs[$row->consultant_id] = $row;
      }

      $dtds = TOOLS::date_to_s(TOOLS::date_today());
      kvframework_log::write_log("MOBILE: \$dtds = $dtds", KVF_LOG_LDEBUG);
      $dtdwd = TOOLS::weekday_transform(TOOLS::wday_for(TOOLS::date_today()));
      kvframework_log::write_log("MOBILE: \$dtdwd = $dtdwd", KVF_LOG_LDEBUG);
      $sql = "SELECT *, appointments.id as appointment_id, consultantappts.rapid as consultantappt_id, locations.id as location_id, loczones.id as loczone_id, locations.name as location_name, loczones.name as loczone_name, consultants.id as consultant_id FROM appointments, consultantappts, locations, loczones, consultants WHERE consultants.status = 'active' AND consultants.id IN ('".implode("','", array_keys($arcs))."') AND consultantappts.consultant_id = consultants.id AND consultantappts.appointment_id = appointments.id AND locations.id = appointments.location_id AND loczones.id = locations.loczone_id AND (appointments.startdate = '$dtds' OR (appointments.startdate <= '$dtds' AND appointments.stopdate >= '$dtds' AND FIND_IN_SET('$dtdwd', appointments.repetition_day))) ORDER BY appointments.special = 'repeat_removal' DESC, appointments.startdate, appointments.repeat = 'FALSE' DESC";
      $q = self::$DB->query($sql);
      $appointments_all_future = array();
      $tm_ids = array();
      while($row = self::$DB->fetch($q)){
        if(!array_key_exists($row->appointment_id, $appointments_all_future)){$appointments_all_future[$row->appointment_id] = $row;}
        if(!is_array($appointments_all_future[$row->appointment_id]->consultants)){$appointments_all_future[$row->appointment_id]->consultants = array();}
        $appointments_all_future[$row->appointment_id]->consultants[$row->consultant_id] = $row;
        if(!array_key_exists($row->tm_type, $tm_ids) || !is_array($tm_ids[$row->tm_type])){$tm_ids[$row->tm_type] = array();}
        if(!array_key_exists($row->tm_id, $tm_ids[$row->tm_type]) || !is_array($tm_ids[$row->tm_type][$row->tm_id])){$tm_ids[$row->tm_type][$row->tm_id] = array();}
        $tm_ids[$row->tm_type][$row->tm_id][] = $row->appointment_id;
      }

      foreach($tm_ids as $type => $id_array){
        $sql = "SELECT *, '$type' as tm_type, id as tm_id FROM ".strtolower($type)."s WHERE id IN ('".implode("','", array_keys($id_array))."')";
        $q = self::$DB->query($sql);
        while($row = self::$DB->fetch($q)){
          foreach($id_array[$row->tm_id] as $aid){
            $appointments_all_future[$aid]->tm = $row;
          }
        }
      }

      $this->fah = $this->generate_todays_appts_array_all($appointments_all_future);

      $this->output_page("view_all");
    } else {
      $this->index();
    }
  }

  /**
   * Generate the list of appointments for a certain consultant
   *
   * @param integer $consultant_id
   * @param boolean $showall
   */
  protected function show_appt_list($consultant_id, $showall = false){
    $sql = "SELECT *, id as consultant_id FROM consultants WHERE id = '".$consultant_id."' AND status = 'active' LIMIT 1";
    $q = self::$DB->query($sql);
    if(self::$DB->rows($q) == 1){
      $this->consultant = self::$DB->fetch($q);

      $dtds = TOOLS::date_to_s(TOOLS::date_today());
      $dtdwd = TOOLS::weekday_transform(TOOLS::wday_for(TOOLS::date_today()));
      $sql = "SELECT *, appointments.id as appointment_id, consultantappts.rapid as consultantappt_id, locations.id as location_id, loczones.id as loczone_id, locations.name as location_name, loczones.name as loczone_name FROM appointments, consultantappts, locations, loczones WHERE consultantappts.consultant_id = '$consultant_id' AND consultantappts.appointment_id = appointments.id AND locations.id = appointments.location_id AND loczones.id = locations.loczone_id AND (appointments.startdate = '$dtds' OR (appointments.startdate <= '$dtds' AND appointments.stopdate >= '$dtds' AND FIND_IN_SET('$dtdwd', appointments.repetition_day))) ORDER BY appointments.special = 'repeat_removal' DESC, appointments.startdate, appointments.repeat = 'FALSE' DESC";
      $q = self::$DB->query($sql);
      $appointments_all_future = array();
      $tm_ids = array();
      while($row = self::$DB->fetch($q)){
        if(!array_key_exists($row->appointment_id, $appointments_all_future)){$appointments_all_future[$row->appointment_id] = $row;}
        if(!array_key_exists($row->tm_type, $tm_ids) || !is_array($tm_ids[$row->tm_type])){$tm_ids[$row->tm_type] = array();}
        if(!array_key_exists($row->tm_id, $tm_ids[$row->tm_type]) || !is_array($tm_ids[$row->tm_type][$row->tm_id])){$tm_ids[$row->tm_type][$row->tm_id] = array();}
        $tm_ids[$row->tm_type][$row->tm_id][] = $row->appointment_id;
      }

      foreach($tm_ids as $type => $id_array){
        $sql = "SELECT *, '$type' as tm_type, id as tm_id FROM ".strtolower($type)."s WHERE id IN ('".implode("','", array_keys($id_array))."')";
        $q = self::$DB->query($sql);
        while($row = self::$DB->fetch($q)){
          foreach($id_array[$row->tm_id] as $aid){
            $appointments_all_future[$aid]->tm = $row;
          }
        }
      }

      $hd = MyFunctions::consultantHoursDataFor(array($this->consultant->consultant_id), array(TOOLS::date_today()), false, false);
      $rhd =& $hd["rchours"]->blocks[$this->consultant->consultant_id];
      $ohd =& $hd["ophours"]->blocks;
      $ohdt =& $ohd[TOOLS::date_today()];

      $apptmax = TOOLS::date_today();
      $this->fah = $this->generate_todays_appts_array($appointments_all_future, $apptmax, $ohd, $rhd, $showall);
      $this->ucac = $this->generate_unconfirmed_appts_count($appointments_all_future);

      //getting to output
      $this->output_page("view");

    } else {
      self::throwError("The requested consultant was not found in the system.");
    }
  }

  /**
   * Generate an array of todays appointments
   *
   * @param array $appts_all_future
   * @param mixed $apptmax
   * @param mixed $ohd
   * @param mixed $rhd
   * @param boolean $showall
   * @return array
   */
  protected function generate_todays_appts_array($appts_all_future, $apptmax, &$ohd, &$rhd, $showall = false){
    $appt_rows = array();
    $removed_ids = array();
    if(!$showall){
      $temp = TOOLS::array_reject($appts_all_future, '$ap', '$ap->confirmed == "FALSE" && $ap->special != "repeat_removal"');
    } else {
      $temp = $appts_all_future;
    }

    $asdate2 = TOOLS::date_today();
    if(!array_key_exists($asdate2, $removed_ids) || !is_array($removed_ids[$asdate2])){
      $removed_ids[$asdate2] = array();
    }
    if(!array_key_exists($asdate2, $appt_rows) || !is_array($appt_rows[$asdate2])){
      $appt_rows[$asdate2] = array();
    }
    foreach($temp as $appt){
      $asdate = TOOLS::string_to_date($appt->startdate);
      $tpd = TOOLS::string_to_date($appt->stopdate);

      if($appt->special == "repeat_removal"){

        if(MyFunctions::datetime_in_appt($asdate2, TOOLS::string_to_time($appt->starttime), $appt)){
          $removed_ids[$asdate2][] = $appt->removal_of;
        }

        continue;
      }

            if((!array_key_exists($asdate2, $removed_ids) || !is_array($removed_ids[$asdate2]) || !in_array($appt->appointment_id, $removed_ids[$asdate2])) && MyFunctions::in_intervals($appt, $ohd[$asdate2]["intervals"], true) && (MyFunctions::in_intervals($appt, TOOLS::array_collect($rhd[$asdate2], '$hd', '(is_array($hd)) ? $hd[0] : $hd'), true) || $appt->special2 == "meeting")){
        $appt_rows[$asdate2][] = $appt;
      }
    }

      usort($appt_rows[$asdate2], array("MyCalendar", "sorter"));

    return $appt_rows;
  }

  /**
   * Count the unconfirmed appointments
   *
   * @param array $appts_all_future
   * @return integer
   */
  protected function generate_unconfirmed_appts_count($appts_all_future){
    return count(TOOLS::array_reject($appts_all_future, '$ap', '$ap->confirmed == "TRUE" || $ap->special == "repeat_removal"'));
  }

  /**
   * Generate output for future appointments
   *
   * @return string
   */
  protected function future_appts_output(){
    $info =& $this->fah;
    $result = "";

    $k = 0;
    ksort($info);
    foreach($info as $day => $appts){
      $max_i = count($appts);
      for($i = 0; $i < $max_i; $i++){
        if($i == 0){
          $result .= "<p>".TOOLS::$daynames[TOOLS::wday_for($day)]." ".TOOLS::day_for($day)." ".TOOLS::month_name_for(TOOLS::month_for($day))."<ul>";
        }
        if($appts[$i]->tm_type == "Ticket"){
          $text = "Appointment: ".TOOLS::time_to_s(TOOLS::string_to_time($appts[$i]->starttime), true)." - ".TOOLS::time_to_s(TOOLS::string_to_time($appts[$i]->stoptime), true)." in ".TOOLS::escape_quotes($appts[$i]->locdetails)." ".$appts[$i]->location_name." with ".$appts[$i]->tm->person." (Ticket: ".$appts[$i]->tm->remedy_ticket.") (Phone: ".$appts[$i]->tm->phone." | ".$appts[$i]->tm->altphone.")";
        } elseif($appts[$i]->tm_type == "Meeting" || $appts[$i]->tm_type == "Meecket"){
          $text = "Meeting: ".$appts[$i]->tm->subject." at ".TOOLS::time_to_s(TOOLS::string_to_time($appts[$i]->starttime), true)." - ".TOOLS::time_to_s(TOOLS::string_to_time($appts[$i]->stoptime), true)." in ".TOOLS::escape_quotes($appts[$i]->locdetails)." ".$appts[$i]->location_name;
        }

        $result .= "<li>$text</li>";

      }
      $result .= "</ul></p>";
      $k++;
    }

    return $result;
  }

  /**
   * Generate output for unconfirmed appointments
   *
   * @return string
   */
  protected function unconfirmed_appts_output(){
    $result = "";

    if($this->ucac > 0 && !$this->flag){
      $result .= "<p>";
      $result .= "<strong>Notice:</strong> You have ".$this->ucac." unconfirmed appointments which are not displayed below.";
      $result .= "</p>";
    }

    return $result;
  }

  /**
   * Generate the array for all appointments today
   *
   * @param mixed $temp
   * @return array
   */
  protected function generate_todays_appts_array_all($temp){
    $appt_rows = array();
    $removed_ids = array();

    foreach($temp as $appt){
      $asdate = TOOLS::string_to_date($appt->startdate);
      $tpd = TOOLS::string_to_date($appt->stopdate);
      $asdate2 = TOOLS::date_today();

      if($appt->special == "repeat_removal"){
        if(!array_key_exists($asdate2, $removed_ids) || !is_array($removed_ids[$asdate2])){
          $removed_ids[$asdate2] = array();
        }

        if(MyFunctions::datetime_in_appt($asdate2, TOOLS::string_to_time($appt->starttime), $appt)){
          $removed_ids[$asdate2][] = $appt->removal_of;
        }
        //}
        continue;
      }

      $actual_dates = array(TOOLS::date_today());

      if(!array_key_exists($asdate2, $appt_rows) || !is_array($appt_rows[$asdate2])){
        $appt_rows[$asdate2] = array();
      }

      if((!array_key_exists($asdate2, $removed_ids) || !is_array($removed_ids[$asdate2]) || !in_array($appt->appointment_id, $removed_ids[$asdate2]))){
        $appt_rows[$asdate2][] = $appt;
      }
      usort($appt_rows[$asdate2], array("MyCalendar", "sorter"));
    }

    

    return $appt_rows;
  }

  /**
   * Generate the output for all appointments today
   *
   * @return string
   */
  protected function future_appts_output2(){
    $info =& $this->fah;
    $result = "";

    $k = 0;
    ksort($info);
    foreach($info as $day => $appts){
      $max_i = count($appts);
      for($i = 0; $i < $max_i; $i++){
        if($i == 0){
          $result .= "<p>".TOOLS::$daynames[TOOLS::wday_for($day)]." ".TOOLS::day_for($day)." ".TOOLS::month_name_for(TOOLS::month_for($day))." ".TOOLS::year_for($day)."<ul>";
        }
        if($appts[$i]->tm_type == "Ticket"){
          $text = "Appointment: ".TOOLS::time_to_s(TOOLS::string_to_time($appts[$i]->starttime), true)." - ".TOOLS::time_to_s(TOOLS::string_to_time($appts[$i]->stoptime), true)." in ".TOOLS::escape_quotes($appts[$i]->locdetails)." ".$appts[$i]->location_name." with ".$appts[$i]->tm->person." (Ticket: ".$appts[$i]->tm->remedy_ticket.") (Phone: ".$appts[$i]->tm->phone." | ".$appts[$i]->tm->altphone.")";
        } elseif($appts[$i]->tm_type == "Meeting" || $appts[$i]->tm_type == "Meecket"){
          $text = "Meeting: ".$appts[$i]->tm->subject." at ".TOOLS::time_to_s(TOOLS::string_to_time($appts[$i]->starttime), true)." - ".TOOLS::time_to_s(TOOLS::string_to_time($appts[$i]->stoptime), true)." in ".TOOLS::escape_quotes($appts[$i]->locdetails)." ".$appts[$i]->location_name;
        }
        $text .= "<br />&nbsp;&nbsp;Consultant(s): ".implode(", ", TOOLS::array_collect($appts[$i]->consultants, '$r','Consultant::select_name($r)'));

        $result .= "<li>$text</li>";
      }
      $result .= "</ul></p>";
      $k++;
    }

    return $result;
  }
}
?>
