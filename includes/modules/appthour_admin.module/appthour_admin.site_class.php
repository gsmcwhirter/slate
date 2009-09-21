<?php
/**
 * KvScheduler - Appointment Hour Admin SiteClass
 * @package KvScheduler
 * @subpackage Modules
 * @author Greg McWhirter <gsmcwhirter@gmail.com>
 * @version 1.0
 */

/**
 * CRUD for appthours
 *
 * @package KvScheduler
 * @subpackage Modules
 */
class appthour_admin_site_class extends admin_site_class{
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
   * Array of appointment types for a select box generator
   *
   * @var array
   */
  protected $all_apts;

  /**
   * The selected appointment type
   * @var mixed
   */
  protected $sel_appttype;

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
    $this->prep_appttypes();
    $this->output_page("form","inline");
  }

  /**
   * Processing of change form
   *
   */
  public function process_form(){
    if(!array_key_exists("form", self::$PARAMS) || !is_array(self::$PARAMS["form"]) || !array_key_exists("date", self::$PARAMS) || !is_array(self::$PARAMS["date"]) || !array_key_exists("startdate", self::$PARAMS["date"]) || !array_key_exists("stopdate", self::$PARAMS["date"]) || !array_key_exists("starttime", self::$PARAMS) || !array_key_exists("stoptime", self::$PARAMS) || !array_key_exists("repeat", self::$PARAMS)){
      self::throwError("Invalid parameters.");
      $this->form();
    } else {
      $sem = null;
      if(array_key_exists("semester", self::$PARAMS["form"]) && !is_null(self::$PARAMS["form"]["semester"])){
        $sql = "SELECT * FROM semesters WHERE id = '".self::$PARAMS["form"]["semester"]."' LIMIT 1";
        $q = self::$DB->query($sql);
        if(self::$DB->rows($q) == 1){
          $this->sem = self::$DB->fetch($q);
        }
      }
      unset(self::$PARAMS["form"]["semester"]);
      $id = Appthour::create(array_merge(self::$PARAMS["form"], array("starttime" => TOOLS::string_to_time(implode(":", self::$PARAMS["starttime"])), "stoptime" => TOOLS::string_to_time(implode(":", self::$PARAMS["stoptime"])), "repeat" => implode(",", self::$PARAMS["repeat"]), "timestamp" => time()), (!is_null($this->sem)) ? array("startdate" => TOOLS::string_to_date($this->sem->startdate), "stopdate" => TOOLS::string_to_date($this->sem->stopdate)) : array("startdate" => TOOLS::string_to_date(self::$PARAMS["date"]["startdate"]), "stopdate" => TOOLS::string_to_date(self::$PARAMS["date"]["stopdate"]))));
      if($id){
        self::throwMessage("Appointment hours changes made successfully.");
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
    $this->prep_appttypes();
    $this->prep_sem_select();

    if(array_key_exists("sdate", self::$PARAMS) && array_key_exists("pdate", self::$PARAMS) && self::$PARAMS["sdate"] != "" && self::$PARAMS["pdate"] != ""){
      $this->prep_dates();
    } elseif(array_key_exists("sem_id", self::$PARAMS)){
      $this->prep_sem(false);
    } else {
      $this->view_any = false;
      $this->oh_res = null;
    }

    if($this->startd && $this->stopd){
      if(array_key_exists("appttype_id", self::$PARAMS) && $this->prep_sel_appttype(self::$PARAMS["appttype_id"])){
        $this->oh_res = array();
        $this->view_any = true;
        $sql = "SELECT appthours.*, appttypes.* FROM appthours, appttypes WHERE appttypes.id = '".self::$PARAMS["appttype_id"]."' AND appttypes.id = appthours.appttype_id AND startdate <= '".TOOLS::date_to_s($this->stopd)."' AND stopdate >= '".TOOLS::date_to_s($this->startd)."' ORDER BY timestamp DESC";
        $q = self::$DB->query($sql);
        while($row = self::$DB->fetch($q)){
          $this->oh_res[] = $row;
        }
      } else {
        self::throwError("The requested appointment type was not found.");
        $this->view_any = false;
        $this->oh_res = false;
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
   * Prepare appointment types for selecting
   *
   *  @return boolean
   */
  protected function prep_appttypes(){
    $this->all_apts = array();
    $sql = "SELECT *, id as appttype_id FROM appttypes ORDER BY name";
    $q = self::$DB->query($sql);
    while($row = self::$DB->fetch($q)){
      $this->all_apts[] = array(Appttype::select_name($row), $row->appttype_id);
    }

    return true;
  }

  /**
   * Get the record for the requested appointment type
   * @param integer $id
   * @return boolean
   *
   */
  protected function prep_sel_appttype($id){
    $sql = "SELECT *, id as appttype_id FROM appttypes WHERE id = '".$id."' LIMIT 1";
    $q = self::$DB->query($sql);
    if(self::$DB->rows($q) == 1){
      $this->sel_appttype = self::$DB->fetch($q);
      return true;
    } else {
      return false;
    }
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

  /**
   * Displays a calendar of this month and next month
   *
   * @return string
   */
  protected function gen_calendars(){
    $result = "";
    $result .= "<table><tr>";
    $thismonth = (int)date("m", time());
    $y = (int)date("Y", time());
    for($mindex = $thismonth; $mindex < $thismonth + 3; $mindex++){
      $m = $mindex;
      $my = $py = $y;
      if($m > 12){$m -=12; $my += 1;}
      $pm = $m - 1;
      if($pm <= 0){$pm += 12; $py -= 1;}
      $mdays = (int)date("t",mktime(0,0,0,$m,1,$my));
      $pmdays = (int)date("t",mktime(0,0,0,$pm,1,$py));

      $startson = TOOLS::wday_for(TOOLS::new_date($my, $m, 1));
      $weeks = ceil(($mdays + $startson) / 7) + 1;

      $calendar = array();
      for($i = 0; $i < $weeks; $i++){
        $calendar[$i] = array();
        for($j = 0; $j < 7; $j++){
          $calendar[$i][$j] = 0;
        }
      }

      for($i = 0; $i < $weeks * 7; $i++){
        if($i < $startson){
          $calendar[floor($i / 7)][$i % 7] = $pmdays - $startson + $i + 1;
        } elseif($i >= ($startson + $mdays)){
          $calendar[floor($i / 7)][$i % 7] = $i + 1 - $startson - $mdays;
        } else {
          $calendar[floor($i / 7)][$i % 7] = $i + 1 - $startson;
        }
      }

      $mstart = $startson;
      $mend = $startson + $mdays;
      $result .= "<td style='padding: 2Px;'>";
      $result .= "<table class='cal' border='0' id='tab_cal'>";
      $result .= "<tr>";
      $result .= "<th class='title' style='text-align: center;' scope='col' colspan='7'>".TOOLS::month_name_for($m)."</th>";
      $result .= "</tr>";
      $result .= "<tr>";
      for($i = 0; $i < 7; $i++){
        $result .= "<td class='header'>".TOOLS::weekday_transform($i)."</td>";
      }
      $result .= "</tr>";
      $wc = 0;
      foreach($calendar as $week){
        $result .= "<tr>";
        $max_i = count($week);
        for($i = 0; $i < $max_i; $i++){
          $type = "day";
          $type2 = "";
          $check = ($wc * 7 + $i);
          if($check < $mstart || $check >= $mend){
            $type = "header pointer";
          } else {
            $type2 = "background-color: #ffffff;";
          }
          $today = TOOLS::date_today();
          if($week[$i] == TOOLS::day_for($today) && $check >= $mstart && $check <= $mend && $m == TOOLS::month_for($today) && $my == TOOLS::year_for($today)){
            $type == "day today";
          }
          $nd = TOOLS::x_days_since($check - $mstart, TOOLS::new_date($my, $m, 1));
          $result .= "<td class='".$type."' style='$type2 cursor:auto;'>".$week[$i]."</td>";
        }
        $result .= "</tr>";
        $wc++;
      }
      $result .= "</table></td>";
    }
    $result .= "</tr></table>";

    return $result;
  }
}
