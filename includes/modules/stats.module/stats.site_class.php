<?php
/**
 * KvScheduler - Stats SiteClass
 * @package KvScheduler
 * @subpackage Modules
 * @author Greg McWhirter <gsmcwhirter@gmail.com>
 * @version 1.0
 */

load_files(
  KVF_MODULES."/stats.module/lib/statsgenerators.class.php",
  KVF_INCLUDES."/lib/fpdf.php",
  KVF_INCLUDES."/lib/fpdf/report_generator.fpdf.php",
  KVF_MODULES."/consultanthour_admin.module/lib/rchadminobject.class.php"
);

/**
 * Statistics generation interface
 *
 * @package KvScheduler
 * @subpackage Modules
 *
 */
class stats_site_class extends admin_site_class{
  /**
   * Report type options
   *
   * @var array
   */
  protected $options = array("list","number");
  /**
   * Option data (report data)
   *
   * @var array
   */
  protected $op_data = array();
  /**
   * The name of the pdf file i believe
   *
   * @var string
   */
  protected $pdf;

  /**
   * Start date
   *
   * @var integer
   */
  protected $startdate;
  /**
   * Stop date
   *
   * @var integer
   */
  protected $stopdate;
  /**
   * Selected appointment types
   *
   * @var array
   */
  protected $appttypes;
  /**
   * Selected options/types
   *
   * @var array
   */
  protected $thisopts;
  /**
   * Selected consultants
   *
   * @var array
   */
  protected $consultants;
  /**
   * Selected meta-locations
   *
   * @var array
   */
  protected $metalocs;
  /**
   * Holds a canned report record
   *
   * @var mixed
   */
  protected $canned;
  /**
   * Tickets entered
   *
   * @var array
   */
  protected $tickets;

  /**
   * All appointment type for a selector
   *
   * @var array
   */
  protected $all_ats;
  /**
   * All  meta-locations for a selector
   *
   * @var array
   */
  protected $all_mls;
  /**
   * All recons for a selector
   *
   * @var array
   */
  protected $all_rcs;
  /**
   * All canned reports for a selector
   *
   * @var array
   */
  protected $all_canned;

  /**
   * Flag whether to save a report (can a report) or not
   *
   * @var boolean
   */
  protected $save_report = false;
  /**
   * Description of the report to be saved
   *
   * @var string
   */
  protected $save_description;

  /**
   * A semester record
   *
   * @var mixed
   */
  protected $sem;

  /**
   * Start date
   *
   * @var integer
   */
  protected $startd;

  /**
   * Stop date
   *
   * @var integer
   */
  protected $stopd;
  /**
   * Information about something
   *
   * @var mixed
   */
  protected $info;
  /**
   * Array of all consultants for a selector generator
   *
   * @var array
   */
  protected $all_consultants;
  /**
   * Array of all semesters for a selector generator
   *
   * @var array
   */
  protected $all_sems;

  /**
   * Constructor
   *
   */
  function __construct(){
    parent::__construct();
    foreach($this->options as $o){$this->op_data[$o] = array();}
  }

  /**
   * User report form
   *
   */
  public function form_user(){
    $this->output_page("form_user", "inline");
  }

  /**
   * Process user report form
   *
   */
  public function process_user(){

    if(!$this->process_option_prep() || count($this->thisopts) == 0){
      self::throwError("You must select one or more report options.");
    }
    if(!$this->process_date_prep()){
      self::throwError("Requested date boundaries were in-valid.");
    }
    if(!$this->process_user_prep()){
      self::throwError("User list was not found");
    }

    if(self::is_errors()){
      $this->form_user();
    } else {
      $this->run_user_report();
    }
  }

  /**
   * Appointment type form
   *
   */
  public function form_appttype(){
    $this->appttypes_prep();
    $this->output_page("form_appttype", "inline");
  }

  /**
   * Process appointment type form
   *
   */
  public function process_appttype(){
    $this->process_save_prep();
    if(!$this->process_option_prep() || count($this->thisopts) == 0){
      self::throwError("You must select one or more report options.");
    }
    if(!$this->process_date_prep()){
      self::throwError("Requested date boundaries were in-valid.");
    }
    if(!$this->process_appttype_prep() || count($this->appttypes) == 0){
      self::throwError("You must select one or more type of appointments for which to generate a report.");
    }

    if(self::is_errors()){
      $this->form_appttype();
    } else {
      if($this->save_report){
        $params = array("type" => "appttype", "appttypes" => $this->appttypes, "thisopts" => $this->thisopts, "consultants" => $this->consultants, "metalocs" => $this->metalocs);
        $this->save_canned_report($this->save_description, $params);
      }

      if(self::is_errors()){
        $this->form_appttype();
      } else {
        $this->run_appttype_report();
      }
    }
  }

  /**
   * Consultant form
   *
   */
  public function form_consultant(){
    $this->appttypes_prep();
    $this->consultants_prep();
    $this->output_page("form_consultant", "inline");
  }

  /**
   * Process consultant form
   *
   */
  public function process_consultant(){
    $this->process_save_prep();
    if(!$this->process_option_prep() || count($this->thisopts) == 0){
      self::throwError("You must select one or more report options.");
    }
    if(!$this->process_date_prep()){
      self::throwError("Requested date boundaries were in-valid.");
    }
    if(!$this->process_appttype_prep() || count($this->appttypes) == 0){
      self::throwError("You must select one or more type of appointments for which to generate a report.");
    }
    if(!$this->process_consultant_prep() || count($this->consultants) == 0){
      self::throwError("You must select one or more consultants for whom to generate a report.");
    }

    if(self::is_errors()){
      $this->form_consultant();
    } else {
      if($this->save_report){
        $params = array("type" => "consultant", "appttypes" => $this->appttypes, "thisopts" => $this->thisopts, "consultants" => $this->consultants, "metalocs" => $this->metalocs);
        $this->save_canned_report($this->save_description, $params);
      }

      if(self::is_errors()){
        $this->form_consultant();
      } else {
        $this->run_consultant_report();
      }
    }
  }

  /**
   * Meta-Location form
   *
   */
  public function form_metaloc(){
    $this->appttypes_prep();
    $this->metalocs_prep();
    $this->output_page("form_metaloc", "inline");
  }

  /**
   * Process meta-location form
   *
   */
  public function process_metaloc(){
    $this->process_save_prep();
    if(!$this->process_option_prep() || count($this->thisopts) == 0){
      self::throwError("You must select one or more report options.");
    }
    if(!$this->process_date_prep()){
      self::throwError("Requested date boundaries were in-valid.");
    }
    if(!$this->process_appttype_prep() || count($this->appttypes) == 0){
      self::throwError("You must select one or more type of appointments for which to generate a report.");
    }
    if(!$this->process_metaloc_prep() || count($this->metalocs) == 0){
      self::throwError("You must select one or more locations for which to generate a report.");
    }

    if(self::is_errors()){
      $this->form_metaloc();
    } else {
      if($this->save_report){
        $params = array("type" => "metaloc", "appttypes" => $this->appttypes, "thisopts" => $this->thisopts, "consultants" => $this->consultants, "metalocs" => $this->metalocs);
        $this->save_canned_report($this->save_description, $params);
      }

      if(self::is_errors()){
        $this->form_metaloc();
      } else {
        $this->run_metaloc_report();
      }
    }
  }

  /**
   * Ticket form
   *
   */
  public function form_ticket(){
    $this->output_page("form_ticket", "inline");
  }

  /**
   * Process ticket form
   *
   */
  public function process_ticket(){
    if(!$this->process_ticket_prep()){
      self::throwError("Ticket list was not found");
    }

    if(self::is_errors()){
      $this->form_ticket();
    } else {
      $this->run_ticket_report();
    }
  }

  /**
   * Percents form
   *
   */
  public function form_percents(){
    $this->output_page("form_percents", "inline");
  }

  /**
   * Process percents form
   *
   */
  public function process_percents(){
    if(!$this->process_date_prep()){
      self::throwError("Requested date boundaries were in-valid.");
    }

    if(self::is_errors()){
      $this->form_percents();
    } else {
      $this->run_percent_report();
    }
  }

  /**
   * Canned report form
   *
   */
  public function form_canned(){
    $this->canned_prep();
    $this->output_page("form_canned","inline");
  }

  /**
   * Process canned report form
   *
   */
  public function process_canned(){
    if(!$this->process_canned_prep()){
      self::throwError("The requested saved report was not available in the database.");
    }
    if(!$this->process_date_prep()){
      self::throwError("Requested date boundaries were in-valid.");
    }

    if(self::is_errors()){
      $this->form_canned();
    } else {
      $params = unserialize(urldecode($this->canned->parameters));
      $type = $params["type"];
      $this->appttypes = $params["appttypes"];
      $this->thisopts = $params["thisopts"];
      $this->consultants = $params["consultants"];
      $this->metalocs = $params["metalocs"];

      switch($type){
        case "consultant":
          $this->run_consultant_report();
          break;
        case "metaloc":
          $this->run_metaloc_report();
          break;
        case "appttype":
          $this->run_appttype_report();
          break;
        default:
          self::throwError("In-valid type of saved report.");
          $this->form_canned();
      }
    }
  }

  /**
   * Canned report removal form
   *
   */
  public function form_uncanned(){
    $this->canned_prep();
    $this->output_page("form_uncanned","inline");
  }

  /**
   * Canned report removal confirmation
   *
   */
  public function process_uncanned(){
    if(!$this->process_canned_prep()){
      self::throwError("The canned report requested was not found in the database.");
    }

    if(self::is_errors()){
      $this->form_uncanned();
    } else {
      $this->output_page("form_confirm_uncan","inline");
    }
  }

  /**
   * Canned report removal processing
   *
   */
  public function process_uncanned_confirm(){
    if(!$this->process_canned_prep()){
      self::throwError("The canned report requested was not found in the database.");
    }

    if(self::is_errors()){
      $this->form_uncanned();
    } else {
      if(!array_key_exists("rconfirm", self::$PARAMS) || self::$PARAMS["rconfirm"] != "yes"){
        self::throwMessage("Confirmation for removal was denied.");
      } else {
        $sql = "DELETE FROM reports WHERE id = '".$this->canned->canned_id."' LIMIT 1";
        $q = self::$DB->query($sql);
        if(self::$DB->affected() == 1){
          self::throwMessage("Report removed successfully.");
        } else {
          self::throwError("Report failed to be removed successfully.");
        }
      }
      $this->output_page("index","inline","admin");
    }
  }

  /**
   * Prepare appointment types for select
   *
   * @return boolean true
   */
  protected function appttypes_prep(){
    $this->all_ats = array();
    $sql = "SELECT *, id as appttype_id, name as appttype_name FROM appttypes ORDER BY name";
    $q = self::$DB->query($sql);
    while($row = self::$DB->fetch($q)){
      $this->all_ats[] = array(Appttype::select_name($row), $row->appttype_id);
    }
    return true;
  }

  /**
   * Prepare meta-locations for select
   *
   * @return boolean true
   */
  protected function metalocs_prep(){
    $this->all_mls = array();
    $sql = "SELECT *, id as metaloc_id FROM metalocs ORDER BY name";
    $q = self::$DB->query($sql);
    while($row = self::$DB->fetch($q)){
      $this->all_mls[] = array(Metaloc::select_name($row), $row->metaloc_id);
    }
    return true;
  }

  /**
   * Prepare consultants for select
   *
   * @return boolean true
   */
  protected function consultants_prep(){
    $this->all_rcs = array();
    $sql = "SELECT *, id as consultant_id FROM consultants ORDER BY SUBSTRING(realname FROM (INSTR(realname, ' ')+1)) ASC";
    $q = self::$DB->query($sql);
    while($row = self::$DB->fetch($q)){
      $this->all_rcs[] = array(Consultant::select_name($row), $row->consultant_id);
    }
    return true;
  }

  /**
   * Prepare a canned report
   *
   * @return boolean true
   */
  protected function canned_prep(){
    $this->all_canned = array();
    $sql = "SELECT *, id as report_id FROM reports";
    $q = self::$DB->query($sql);
    while($row = self::$DB->fetch($q)){
      $this->all_canned[] = array($row->description, $row->report_id);
    }
    return true;
  }

  /**
   * Prepare dates on a processing
   *
   * @return boolean
   */
  protected function process_date_prep(){
    if(!array_key_exists("startdate", self::$PARAMS) || !array_key_exists("stopdate", self::$PARAMS)){
      return false;
    } else {
      $this->startdate = TOOLS::string_to_date(self::$PARAMS["startdate"]);
      $this->stopdate = TOOLS::string_to_date(self::$PARAMS["stopdate"]);
      if($this->startdate <= 0 or $this->stopdate <= 0){return false;}
      if($this->startdate > $this->stopdate){return false;}
      return true;
    }
  }

  /**
   * Prepare tickets on a processing
   *
   * @return boolean
   */
  protected function process_ticket_prep(){
    if(!array_key_exists("ticket", self::$PARAMS)){
      return false;
    } else {
      $this->tickets = array_unique(explode(":", preg_replace("#,\s|;\s|,|;|\s#", ":", self::$PARAMS["ticket"])));
      return true;
    }
  }

  /**
   * Prepare users on a processing
   *
   * @return boolean
   */
  protected function process_user_prep(){
    if(!array_key_exists("user", self::$PARAMS)){
      return false;
    } else {
      $this->users = array_unique(explode(":", preg_replace("#,\s|;\s|,|;|\s#", ":", self::$PARAMS["user"])));
      return true;
    }
  }

  /**
   * Prep appointment types on a processing
   *
   * @return boolean
   */
  protected function process_appttype_prep(){
    if(!array_key_exists("types", self::$PARAMS) || !is_array(self::$PARAMS["types"])){
      return false;
    } else {
      $this->appttypes = array();
      $sql = "SELECT id as appttype_id FROM appttypes WHERE id IN ('".implode("','", self::$PARAMS["types"])."')";
      $q = self::$DB->query($sql);
      while($row = self::$DB->fetch($q)){
        $this->appttypes[] = $row->appttype_id;
      }
      return true;
    }
  }

  /**
   * Prep options on a processing
   *
   * @return boolean
   */
  protected function process_option_prep(){
    if(!array_key_exists("opts", self::$PARAMS) || !is_array(self::$PARAMS["opts"])){
      return false;
    } else {
      $this->thisopts = array_intersect($this->options, self::$PARAMS["opts"]);
      return true;
    }
  }

  /**
   * Prep consultants on a processing
   *
   * @return boolean
   */
  protected function process_consultant_prep(){
    if(!array_key_exists("rcs", self::$PARAMS) || !is_array(self::$PARAMS["rcs"])){
      return false;
    } else {
      $this->consultants = array();
      $sql = "SELECT id as consultant_id FROM consultants WHERE id IN ('".implode("','", self::$PARAMS["rcs"])."')";
      $q = self::$DB->query($sql);
      while($row = self::$DB->fetch($q)){
        $this->consultants[] = $row->consultant_id;
      }
      return true;
    }
  }

  /**
   * Prep meta-locations on a processing
   *
   * @return boolean
   */
  protected function process_metaloc_prep(){
    if(!array_key_exists("locs", self::$PARAMS) || !is_array(self::$PARAMS["locs"])){
      return false;
    } else {
      $this->metalocs = array();
      $sql = "SELECT id as metaloc_id FROM metalocs WHERE id IN ('".implode("','", self::$PARAMS["locs"])."')";
      $q = self::$DB->query($sql);
      while($row = self::$DB->fetch($q)){
        $this->metalocs[] = $row->metaloc_id;
      }
      return true;
    }
  }

  /**
   * Prep saving a report on a processing
   *
   * @return boolean true
   */
  protected function process_save_prep(){
    if(array_key_exists("save", self::$PARAMS) && self::$PARAMS["save"] == "yes"){
      if(!array_key_exists("description", self::$PARAMS) || strlen(self::$PARAMS["description"]) == 0){
        self::throwError("You must provide some description of the report to save it.");
      } else {
        $this->save_report = true;
        $this->save_description = TOOLS::escape_quotes(self::$PARAMS["description"]);
      }
    }

    return true;
  }

  /**
   * Prep a canned report on a processing
   *
   * @return boolean
   */
  protected function process_canned_prep(){
    if(!array_key_exists("canned", self::$PARAMS)){
      return false;
    } else {
      $sql = "SELECT *, id as canned_id FROM reports WHERE id = '". self::$PARAMS["canned"] ."' LIMIT 1";
      $q = self::$DB->query($sql);
      if(self::$DB->rows($q) == 1){
        $this->canned = self::$DB->fetch($q);
        return true;
      } else {
        return false;
      }
    }
  }

  /**
   * Save a canned report
   *
   * @param string $description
   * @param array $params
   * @return boolean
   */
  protected function save_canned_report($description, array $params){
    $save_str = urlencode(serialize($params));
    $sql = "INSERT INTO reports (description, parameters) VALUES ('$description','$save_str')";
    $id = self::$DB->query($sql);
    if($id){
      self::throwMessage("Report was saved successfully.");
      return true;
    } else {
      self::throwError("Report was not saved successfully.");
      return false;
    }
  }

  /**
   * Run an appointment type report
   *
   */
  protected function run_appttype_report(){
    $this->op_data = array();
    if(in_array("list", $this->thisopts)){
      $this->op_data["list"] = StatsGenerators::appointments_list($this->appttypes, $this->startdate, $this->stopdate);
    }

    if(in_array("number", $this->thisopts)){
      $this->op_data["number"] = StatsGenerators::appointments_count($this->appttypes, $this->startdate, $this->stopdate);
    }

    $this->pdf = PDF_Reports::generate_pdf("appttype", $this->op_data, $this->startdate, $this->stopdate, in_array("number", $this->thisopts), in_array("list", $this->thisopts));
    $this->output_page("output_appttype","inline");
  }

  /**
   * Run a consultant report
   */
  protected function run_consultant_report(){
    $this->op_data = array();
    if(in_array("list", $this->thisopts)){
      $this->op_data["list"] = StatsGenerators::consultants_list($this->consultants, $this->appttypes, $this->startdate, $this->stopdate);
    }

    if(in_array("number", $this->thisopts)){
      $this->op_data["number"] = StatsGenerators::consultants_count($this->consultants, $this->appttypes, $this->startdate, $this->stopdate);
    }

    $this->pdf = PDF_Reports::generate_pdf("consultant", $this->op_data, $this->startdate, $this->stopdate, in_array("number", $this->thisopts), in_array("list", $this->thisopts));
    $this->output_page("output_consultant","inline");
  }

  /**
   * Run a metaloc report
   *
   */
  protected function run_metaloc_report(){
    $this->op_data = array();
    if(in_array("list", $this->thisopts)){
      $this->op_data["list"] = StatsGenerators::metalocs_list($this->metalocs, $this->appttypes, $this->startdate, $this->stopdate);
    }

    if(in_array("number", $this->thisopts)){
      $this->op_data["number"] = StatsGenerators::metalocs_count($this->metalocs, $this->appttypes, $this->startdate, $this->stopdate);
    }

    $this->pdf = PDF_Reports::generate_pdf("metaloc", $this->op_data, $this->startdate, $this->stopdate, in_array("number", $this->thisopts), in_array("list", $this->thisopts));
    $this->output_page("output_metaloc","inline");
  }

  /**
   * Run a ticket report
   *
   */
  protected function run_ticket_report(){
    $this->op_data = StatsGenerators::tickets_list($this->tickets);
    $this->pdf = PDF_Reports::generate_pdf("ticket", $this->op_data, TOOLS::date_today(), TOOLS::date_today(), false, true);
    $this->output_page("output_ticket","inline");
  }

  /**
   * Run a percentages report
   */
  protected function run_percent_report(){
    $this->op_data = StatsGenerators::percents($this->startdate, $this->stopdate);
    $this->pdf = PDF_Reports::generate_pdf("percents", $this->op_data, $this->startdate, $this->stopdate, true, false);
    $this->output_page("output_percents","inline");
  }

  /**
   * Run a user report
   *
   */
  protected function run_user_report(){
    $this->op_data = array();
    if(in_array("list", $this->thisopts)){
      $this->op_data["list"] = StatsGenerators::users_list($this->users, $this->startdate, $this->stopdate);
    }

    if(in_array("number", $this->thisopts)){
      $this->op_data["number"] = StatsGenerators::users_count($this->users, $this->startdate, $this->stopdate);
    }

    $this->pdf = PDF_Reports::generate_pdf("user", $this->op_data, $this->startdate, $this->stopdate, in_array("number", $this->thisopts), in_array("list", $this->thisopts));
    $this->output_page("output_user","inline");
  }

  /**
   * Generate appointment output
   *
   * @param array $appt_hash
   * @return string
   */
  protected function page_display_appt_list_out($appt_hash){
    $result = "";
    $result .= "<li>";
    if($appt_hash["appt"]->tm_type == "Meeting" || $appt_hash["appt"]->tm_type == "Meecket"){
      $result .= "Meeting: ".$appt_hash["appt"]->tm->subject ." from ". TOOLS::time_to_s(TOOLS::string_to_time($appt_hash["appt"]->starttime), true) ." to ". TOOLS::time_to_s(TOOLS::string_to_time($appt_hash["appt"]->stoptime), true);
    } else {
      $result .= "Ticket: ". $appt_hash["appt"]->tm->remedy_ticket ." from ". TOOLS::time_to_s(TOOLS::string_to_time($appt_hash["appt"]->starttime), true) ." to ". TOOLS::time_to_s(TOOLS::string_to_time($appt_hash["appt"]->stoptime), true);
    }
    $result .= "<ul>";
    $result .= "Location:  ". $appt_hash["appt"]->locdetails ." ". Location::select_name($appt_hash["appt"])."<br />";
    if($appt_hash["appt"]->repeat == "TRUE"){
      $result .= "Every ". (($appt_hash["appt"]->repetition_week == "1") ? "week" : $appt_hash["appt"]->repetition_week." weeks") ." on ". implode(", ", TOOLS::array_collect(explode(",", $appt_hash["appt"]->repetition_day), '$i','TOOLS::$dayabbrs[TOOLS::weekday_reverse($i)]')) ." from ". $appt_hash["appt"]->startdate ." until ". $appt_hash["appt"]->stopdate;
      if(count($appt_hash["minus"]) > 0) {
        $result .= "<br />";
        $result .= "Except Dates: ". implode(", ", $appt_hash["minus"]);
      }
    } else {
      $result .= "Date: ". $appt_hash["appt"]->startdate;
    }
    $result .= "<br />";
    $result .= "Consultants: ". implode(", ", TOOLS::array_collect($appt_hash["appt"]->consultants, '$r', 'Consultant::select_name($r)'));
    $result .= "</ul></li>";

    return $result;
  }

  /**
   * Consultant hours report form
   *
   */
  public function consultanthours_report_form(){
    $this->prep_sem_select();

    $this->process = false;

    $this->output_page("hours_report","inline");
  }

  /**
   * Processing consultant hours report form
   *
   */
  public function consultanthours_report(){
    $this->prep_sem_select();
    $this->prep_all_consultants();

    $this->process = true;

    if(array_key_exists("sdate", self::$PARAMS) && self::$PARAMS["sdate"] != "" && array_key_exists("pdate", self::$PARAMS) && self::$PARAMS["pdate"] != "" && $this->date_prep(self::$PARAMS["sdate"], self::$PARAMS["pdate"])){
      //do nothing else
    }
    elseif(!array_key_exists("sem_id", self::$PARAMS)){
      $this->default_sem_prep();
    } elseif(self::$PARAMS["sem_id"]) {
      $this->sem_prep(self::$PARAMS["sem_id"]);
    } else {
      self::throwError("Semester parameters where not valid.");
    }

    if(count($this->all_consultants) > 0 && $this->startd && $this->stopd){
      try{
        $info = RCHAdminObject::newobj(TOOLS::array_collect($this->all_consultants, '$r','$r->consultant_id'), $this->startd);
      } catch( Exception $e){
        $info = array();
        self::throwError($e->getMessage());
      }
    }

    //process $info into $this->info
    $this->info = array();
    foreach($this->all_consultants as $rc){
      $this->info[$rc->consultant_id] = array();
      for($i = 0; $i < 7; $i++){
        $this->info[$rc->consultant_id][$i] = array();
        $inblock = false;
        $laststatus = null;
        $bstart = null;
        $max_blocks = count($info[$rc->consultant_id]["week"][$i][1]);
        for($j = 0; $j < $max_blocks; $j++){
          $block = $info[$rc->consultant_id]["week"][$i][1][$j];
          if(!$inblock && $block->status != "A" && $block->status != "C"){
            continue;
          } elseif(!$inblock && ($block->status == "A" || $block->status == "C")){
            $inblock = true;
            $laststatus = $block->status;
            $bstart = $info["times"][$j];
          } elseif($inblock && $laststatus == $block->status){
            continue;
          } elseif($inblock && $laststatus != $block->status){
            if($block->status == "A" || $block->status == "C"){
              //save to array
              $this->info[$rc->consultant_id][$i][] = array($bstart, $info["times"][$j], $laststatus == "C");
              //alter $laststatus and $bstart
              $bstart = $info["times"][$j];
              $laststatus = $block->status;
            } else {
              $inblock = false;
              //save to array
              $this->info[$rc->consultant_id][$i][] = array($bstart, $info["times"][$j], $laststatus == "C");
            }
          }

        }

        if($inblock){
          //save to array
          $this->info[$rc->consultant_id][$i][] = array($bstart, TOOLS::x_minutes_since(30, $info["times"][$max_blocks - 1]), $laststatus == "C");
        }
      }
    }

    $this->pdf = PDF_Reports::generate_pdf_rch(array("info" => $this->info, "consultants" => $this->all_consultants), $this->startd, $this->stopd, $this->sem);

    $this->output_page("hours_report","inline");

  }

  /**
   * Prepare consultants for a select
   *
   * @return boolean true
   */
  protected function prep_all_consultants(){
    $sql = "SELECT *, consultants.id as consultant_id FROM consultants ORDER BY SUBSTRING(realname FROM (INSTR(realname, ' ')+1)) ASC";
    $q = self::$DB->query($sql);
    $this->all_consultants = array();
    while($row = self::$DB->fetch($q)){
      $this->all_consultants[$row->consultant_id] = $row;
    }

    return true;
  }

  /**
   * Prep default semester
   *
   * @return boolean true
   */
  protected function default_sem_prep(){
    $sql = "SELECT *, id as semester_id FROM semesters WHERE id = '".$this->config_val("sem_default")."' LIMIT 1";
    $q = self::$DB->query($sql);
    if(self::$DB->rows($q) == 1){
      $this->sem = self::$DB->fetch($q);
      $this->startd = TOOLS::string_to_date($this->sem->startdate);
      $this->stopd = TOOLS::string_to_date($this->sem->stopdate);
    } else {
      self::throwError("Default Semester not found.");
      $this->sem = null;
      $this->startd = null;
      $this->stopd = null;
    }

    return true;
  }

  /**
   * Prep a certain semester
   *
   * @param integer $sem
   * @return boolean
   */
  protected function sem_prep($sem){
    $sql = "SELECT *, id as semester_id FROM semesters WHERE id = '".$sem."' LIMIT 1";
    $q = self::$DB->query($sql);
    if(self::$DB->rows($q) == 1){
      $this->sem = self::$DB->fetch($q);
      $this->startd = TOOLS::string_to_date($this->sem->startdate);
      $this->stopd = TOOLS::string_to_date($this->sem->stopdate);
      return true;
    } else {
      self::throwError("Semester parameters were not valid");
      return false;
    }
  }

  /**
   * Prep dates
   *
   * @param string $sd
   * @param string $pd
   * @return boolean
   */
  protected function date_prep($sd, $pd){
    $this->sem = null;
    $this->startd = TOOLS::string_to_datetime($sd);
    $this->stopd = TOOLS::string_to_datetime($pd);
    if(!is_int($this->startd) || !is_int($this->stopd) || $this->startd == 0 || $this->stopd == 0){
      $this->startd = null;
      $this->stopd = null;
      return false;
    }

    return true;
  }

  /**
   * Prep semesters for selector
   *
   * @return boolean true
   */
  protected function prep_sem_select(){
    $this->all_sems = array();
    $sql = "SELECT *, id as semester_id FROM semesters ORDER BY startdate DESC";
    $q = self::$DB->query($sql);
    while($row = self::$DB->fetch($q)){
      $this->all_sems[] = array(Semester::select_name($row), $row->semester_id);
    }

    return true;
  }

}
?>
