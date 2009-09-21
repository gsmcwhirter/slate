<?php
/**
 * KvScheduler - Operating Hour Admin SiteClass
 * @package KvScheduler
 * @subpackage Modules
 * @author Greg McWhirter <gsmcwhirter@gmail.com>
 * @version 1.0
 */

/**
 * CRUD for ophours
 *
 * @package KvScheduler
 * @subpackage Modules
 */
class ophour_admin_site_class extends admin_site_class{
  /**
   * Selector values for semesters
   *
   * @var array
   */
  protected $all_sems;

  /**
   * Selected semester
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
   * Operating hour something
   *
   * @var mixed
   */
  protected $oh_res;

  /**
   * Flag to view stuff
   *
   * @var boolean
   */
  protected $view_any;

  /**
   * Constructor
   */
  function __construct(){
    parent::__construct();
  }

  /**
   * Change form
   *
   */
  public function form(){
    $this->prep_sem_select();
    $this->output_page("form","inline");
  }

  /**
   * Processing of change form
   *
   */
  public function process_form(){
    if(!array_key_exists("form", self::$PARAMS) || !is_array(self::$PARAMS["form"]) || !array_key_exists("date", self::$PARAMS) || !is_array(self::$PARAMS["date"]) || !array_key_exists("startdate", self::$PARAMS["date"]) || !array_key_exists("stopdate", self::$PARAMS["date"]) || !array_key_exists("starttime", self::$PARAMS) || !array_key_exists("stoptime", self::$PARAMS) || !array_key_exists("repetition", self::$PARAMS)){
      self::throwError("Invalid parameters.");
      $this->form();
    } else {
      $sem = null;
      if(array_key_exists("semester", self::$PARAMS["form"]) && !is_null(self::$PARAMS["form"]["semester"])){
        $sql = "SELECT * FROM semesters WHERE id = '".self::$PARAMS["form"]["semester"]."' LIMIT 1";
        $q = self::$DB->query($sql);
        if(self::$DB->rows($q) == 1){
          $sem = self::$DB->fetch($q);
        }
      }
      unset(self::$PARAMS["form"]["semester"]);
      $id = Ophour::create(array_merge(self::$PARAMS["form"], array("starttime" => TOOLS::string_to_time(implode(":", self::$PARAMS["starttime"])), "stoptime" => TOOLS::string_to_time(implode(":", self::$PARAMS["stoptime"])), "repetition" => implode(",", self::$PARAMS["repetition"]), "timestamp" => time()), (!is_null($sem)) ? array("startdate" => TOOLS::string_to_date($sem->startdate), "stopdate" => TOOLS::string_to_date($sem->stopdate)) : array("startdate" => TOOLS::string_to_date(self::$PARAMS["date"]["startdate"]), "stopdate" => TOOLS::string_to_date(self::$PARAMS["date"]["stopdate"]))));
      if($id){
        self::throwMessage("Operating hours changes made successfully.");
        $this->output_page("index","inline","admin");
      } else {
        self::throwError("Changes failed.");
        $this->form();
      }
    }
  }

  /**
   * View of operating hours
   *
   */
  public function view(){
    $this->prep_sem_select();

    if(array_key_exists("sdate", self::$PARAMS) && array_key_exists("pdate", self::$PARAMS) && self::$PARAMS["sdate"] != "" && self::$PARAMS["pdate"] != ""){
      $this->prep_dates();
    } elseif(array_key_exists("sem_id", self::$PARAMS)){
      $this->prep_sem(false);
    } else {
      $this->prep_sem(true);
    }

    if($this->startd && $this->stopd){
      $this->oh_res = array();
      $this->view_any = true;
      $sql = "SELECT * FROM ophours WHERE startdate <= '".TOOLS::date_to_s($this->stopd)."' AND stopdate >= '".TOOLS::date_to_s($this->startd)."' ORDER BY timestamp DESC";
      $q = self::$DB->query($sql);
      while($row = self::$DB->fetch($q)){
        $this->oh_res[] = $row;
      }
    } else {
      $this->view_any = false;
      $this->oh_res = null;
    }

    $this->output_page("view","inline");
  }

  /**
   * Prepare the semester selector
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

  /**
   * Prepare dates for viewing
   *
   * @return boolean
   */
  protected function prep_dates(){
    $this->sem = null;
    $this->startd = TOOLS::string_to_date(self::$PARAMS["sdate"]);
    $this->stopd = TOOLS::string_to_date(self::$PARAMS["pdate"]);
    if(!is_int($this->startd) || !is_int($this->stopd) || $this->startd == 0 || $this->stopd == 0){
      $this->startd = null;
      $this->stopd = null;
      self::throwError("Date parameters were not valid.");
      return false;
    }

    return true;
  }

  /**
   * Prepare selected semester
   *
   * @param boolean $default
   * @return boolean
   */
  protected function prep_sem($default = true){
    if(!$default){
      $sql = "SELECT *, id as semester_id FROM semesters WHERE id = '".self::$PARAMS["sem_id"]."' LIMIT 1";
      $q = self::$DB->query($sql);
      if(self::$DB->rows($q) == 1){
        $this->sem = self::$DB->fetch($q);
        $this->startd = TOOLS::string_to_date($this->sem->startdate);
        $this->stopd = TOOLS::string_to_date($this->sem->stopdate);
      } else {
        self::throwError("Semester parameters were not valid");
        return false;
      }
    } else {
      $sql = "SELECT *, id as semester_id FROM semesters WHERE id = '".$this->config_val("sem_default")."' LIMIT 1";
      $q = self::$DB->query($sql);
      if(self::$DB->rows($q) == 1){
        $this->sem = self::$DB->fetch($q);
        $this->startd = TOOLS::string_to_date($this->sem->startdate);
        $this->stopd = TOOLS::string_to_date($this->sem->stopdate);
      } else {
        self::throwError("Default Semester not found.");
        $this->show_any = false;
        $this->sem = null;
        $this->startd = null;
        $this->stopd = null;
      }

      return true;
    }
  }
}
