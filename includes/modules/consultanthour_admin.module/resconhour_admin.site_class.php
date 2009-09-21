<?php
/**
 * KvScheduler - Consultant Hour Admin SiteClass
 * @package KvScheduler
 * @subpackage Modules
 * @author Greg McWhirter <gsmcwhirter@gmail.com>
 * @version 1.0
 */

load_files( KVF_MODULES."/consultanthour_admin.module/lib/rchadminobject.class.php");

/**
 * Consultant Hours administration
 *
 * @package KvScheduler
 * @subpackage Modules
 */
class consultanthour_admin_site_class extends admin_site_class{
  /**
   * Values for selector of all semesters
   *
   * @var array
   */
  protected $all_sems;
  /**
   * Values for selector of all consultants
   *
   * @var array
   */
  protected $all_rcs;
  /**
   * A semester record
   *
   * @var mixed
   */
  protected $sem;
  /**
   * A flag to display stuff
   *
   * @var boolean
   */
  protected $show_any;
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
   * Flag for changes in the start date
   *
   * @var boolean
   */
  protected $sddelta;
  /**
   * A consultant record
   *
   * @var mixed
   */
  protected $consultant;
  /**
   * Another start date
   *
   * @var integer
   */
  protected $startd2;
  /**
   * A weekday for something
   *
   * @var mixed
   */
  protected $wday;
  /**
   * Start time
   *
   * @var integer
   */
  protected $stime;
  /**
   * Stop time
   *
   * @var integer
   */
  protected $ptime;

  /**
   * Method of change
   *
   * @var mixed
   */
  protected $meth;

  /**
   * It holds info about something... i dont feel like doing a find to determine what.
   *
   * @var mixed
   */
  protected $info;

  /**
   * An array of all consultants for a select generator most likely
   *
   * @var array
   */
  protected $all_consultants;

  /**
   * Constructor
   *
   */
  function __construct(){
    parent::__construct();
  }

  /**
   * The manual modification form
   *
   */
  public function form(){
    $this->prep_form();
    $this->output_page("form","inline");
  }

  /**
   * Processing the manual modification form
   *
   */
  public function process_form(){
    //if(!array_key_exists("form", self::$PARAMS) || !is_array(self::$PARAMS["form"]) || !array_key_exists("date", self::$PARAMS) || !is_array(self::$PARAMS["date"]) || !array_key_exists("startdate", self::$PARAMS["date"]) || !array_key_exists("stopdate", self::$PARAMS["date"]) || !array_key_exists("starttime", self::$PARAMS) || !array_key_exists("stoptime", self::$PARAMS) || !array_key_exists("repeat", self::$PARAMS)){
    if(!array_key_exists("form", self::$PARAMS) || !is_array(self::$PARAMS["form"]) || !array_key_exists("date", self::$PARAMS) || !is_array(self::$PARAMS["date"]) || !array_key_exists("startdate", self::$PARAMS["date"]) || !array_key_exists("stopdate", self::$PARAMS["date"]) || !array_key_exists("starttime", self::$PARAMS) || !array_key_exists("stoptime", self::$PARAMS) || !array_key_exists("repeat", self::$PARAMS)){
      self::throwError("Invalid parameters.");
      $this->form();
    } else {
      $this->sem = null;
      if((array_key_exists("htype", self::$PARAMS["form"]) && (self::$PARAMS["form"]["htype"] == "repeat" || self::$PARAMS["form"]["htype"] == "delete")) && array_key_exists("semester", self::$PARAMS["form"]) && !is_null(self::$PARAMS["form"]["semester"])){
        $sql = "SELECT *, id as semester_id FROM semesters WHERE id = '".self::$PARAMS["form"]["semester"]."' LIMIT 1";
        $q = self::$DB->query($sql);
        if(self::$DB->rows($q) == 1){
          $this->sem = self::$DB->fetch($q);
        }
      }
      unset(self::$PARAMS["form"]["semester"]);
      $sdate = (!is_null($this->sem)) ?
        TOOLS::string_to_date($this->sem->startdate) :
        //TOOLS::string_to_date(implode("-", self::$PARAMS["date"]["startdate"]));
        TOOLS::string_to_date(self::$PARAMS["date"]["startdate"]);
      $pdate = (!is_null($this->sem)) ?
        TOOLS::string_to_date($this->sem->stopdate) :
        ((array_key_exists("htype", self::$PARAMS["form"]) && (self::$PARAMS["form"]["htype"] == "repeat" || self::$PARAMS["form"]["htype"] == "delete")) ?
            //TOOLS::string_to_date(implode("-", self::$PARAMS["date"]["stopdate"])) :
            //TOOLS::string_to_date(implode("-", self::$PARAMS["date"]["startdate"])));
            TOOLS::string_to_date(self::$PARAMS["date"]["stopdate"]) :
            TOOLS::string_to_date(self::$PARAMS["date"]["startdate"]));

      $args = self::$PARAMS["form"];
      $args = array_merge($args, array(
        "starttime" => TOOLS::string_to_time(implode(":", self::$PARAMS["starttime"])),
        "stoptime" => TOOLS::string_to_time(implode(":", self::$PARAMS["stoptime"])),
        "timestamp" => time(),
        "htype2" => "regular",
        "startdate" => $sdate,
        "stopdate" => $pdate,
        "repeat" => (array_key_exists("htype", self::$PARAMS["form"]) && self::$PARAMS["form"]["htype"] == "once") ?
          TOOLS::weekday_transform(TOOLS::wday_for($sdate)) :
          implode(",",self::$PARAMS["repeat"])
      ));
      $id = Consultanthour::create($args);
      if($id){
        self::throwMessage("Consultant hours changes made successfully.");
        $this->output_page("index","inline","admin");
      } else {
        self::throwError("Changes failed.");
        $this->form();
      }
    }
  }

  /**
   * Preparing the manual modification form
   *
   * @return boolean true
   */
  protected function prep_form(){
    $this->all_sems = array();
    $sql = "SELECT *, id as semester_id FROM semesters ORDER BY startdate DESC";
    $q = self::$DB->query($sql);
    while($row = self::$DB->fetch($q)){
      $this->all_sems[] = array(Semester::select_name($row), $row->semester_id);
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
   * The better-modify interface
   *
   */
  public function display_week(){
    if(!array_key_exists("rc", self::$PARAMS) || !is_array(self::$PARAMS["rc"]) || !array_key_exists("id",self::$PARAMS["rc"])){
      $this->show_any = false;
    } else {
      $this->prep_consultant(self::$PARAMS["rc"]["id"]);
    }

    if(array_key_exists("sdate", self::$PARAMS) && self::$PARAMS["sdate"] != "" && array_key_exists("pdate", self::$PARAMS) && self::$PARAMS["pdate"] != "" && $this->date_prep(self::$PARAMS["sdate"], self::$PARAMS["pdate"])){
      //do nothing else
    }
    elseif(!array_key_exists("rc", self::$PARAMS) || !is_array(self::$PARAMS["rc"]) || !array_key_exists("sem_id", self::$PARAMS["rc"])){
      $this->default_sem_prep();
    } elseif(self::$PARAMS["rc"]["sem_id"]) {
      $this->sem_prep(self::$PARAMS["rc"]["sem_id"]);
    } else {
      self::throwError("Semester parameters where not valid.");
    }

    $this->sddelta = false;

    if($this->startd && $this->stopd && $this->startd <= TOOLS::date_today() && $this->stopd >= TOOLS::date_today()){
      $this->startd = TOOLS::date_today();
      $this->sddelta = true;
    }

    /*if($this->sem && !$this->sddelta){
      print $this->startd."<br />";
      print TOOLS::wday_for($this->startd)."<br />";
      print date("w", $this->startd)."<br />";
      print ((7 - (int)TOOLS::wday_for($this->startd)) % 7)."<br />";
      $this->startd = (TOOLS::x_days_since(-1 * ((7 - (int)TOOLS::wday_for($this->startd)) % 7), $this->startd));
    }*/

    $this->do_display();
  }

  /**
   * Processing a change from the better-modify interface
   *
   */
  public function process_display_week(){
    if(array_key_exists("rcid", self::$PARAMS)){
      $this->prep_consultant(self::$PARAMS["rcid"]);
    } else {
      $this->show_any = false;
    }

    if(!array_key_exists("sem_id", self::$PARAMS) && (!array_key_exists("sdate2", self::$PARAMS) || !array_key_exists("pdate2", self::$PARAMS))){
      $this->default_sem_prep();
    } elseif(array_key_exists("sdate2", self::$PARAMS) &&  self::$PARAMS["sdate2"] != "" && array_key_exists("pdate2", self::$PARAMS) &&  self::$PARAMS["pdate2"] != "" && $this->date_prep(self::$PARAMS["sdate2"], self::$PARAMS["pdate2"])){
      // do nothing else
    } elseif(array_key_exists("sem_id", self::$PARAMS) && self::$PARAMS["sem_id"]) {
      $this->sem_prep(self::$PARAMS["sem_id"]);
      //, self::$PARAMS["sdate"], self::$PARAMS["pdate"]
    } else {
      self::throwError("Date and Semester parameters were not valid.");
    }

    $this->sddelta = false;

    if($this->startd && $this->stopd && $this->startd <= TOOLS::date_today() && $this->stopd >= TOOLS::date_today()){
      $this->startd = TOOLS::date_today();
      $this->sddelta = true;
    }

    /*if($this->sem && !$this->sddelta){
      $this->startd2 = (TOOLS::x_days_since(((7 - (int)TOOLS::wday_for($this->startd)) % 7), $this->startd));
    } else {
      $this->startd2 = $this->startd;
    }*/

    if(array_key_exists("wday", self::$PARAMS) && in_array((int)self::$PARAMS["wday"], array(0,1,2,3,4,5,6))){
      $this->wday = (int)self::$PARAMS["wday"];
    } else {
      self::throwError("In-valid weekday parameter passed.");
    }

    if(array_key_exists("starttime", self::$PARAMS) && array_key_exists("stoptime", self::$PARAMS)){
      $this->stime = TOOLS::string_to_time(self::$PARAMS["starttime"]);
      $this->ptime = TOOLS::string_to_time(self::$PARAMS["stoptime"]);
      if(!is_int($this->stime) || $this->stime == 0){
        $this->stime = null;
      }

      if(!is_int($this->ptime) || $this->ptime == 0){
        $this->ptime = null;
      } else {
        $this->ptime = TOOLS::x_minutes_since(30, $this->ptime);
      }

      if(!$this->stime || !$this->ptime || $this->ptime <= $this->stime){
        self::throwError("Start time cannot be after stop time");
      }
    } else {
      $this->stime = null;
      $this->ptime = null;
      self::throwError("In-valid parameters passed for start and stop times");
    }

    $this->meth = (array_key_exists("method", self::$PARAMS)) ? self::$PARAMS["method"] : null;
    if(!in_array($this->meth, array("add","del"))){
      self::throwError("Unrecognized method");
    }

    if(!self::is_errors()){
      $pars = array(
        "consultant_id" => $this->consultant->consultant_id,
        "htype2" => "regular",
        "starttime" => $this->stime,
        "stoptime" => $this->ptime,
        "startdate" => $this->startd,
        "stopdate" => $this->stopd,
        "htype" => ($this->meth == "add") ? "repeat" : "delete",
        "repeat" => TOOLS::weekday_transform($this->wday),
        "timestamp" => time(),
        "oncall" => (array_key_exists("changes_oncall", self::$PARAMS) && self::$PARAMS["changes_oncall"] == "yes") ? "TRUE" : "FALSE"
      );

      $id = Consultanthour::create($pars);
      if($id){
        self::throwMessage("Consultant hours changes made successfully.");
      } else {
        self::throwError("Consultant hours changes failed.");
      }
    }

    $this->do_display();

  }

  /**
   * Prepare the better-modify select boxen
   *
   */
  protected function prep_all_rcs_sems(){
    $this->all_rcs = array();
    $sql = "SELECT *, id as consultant_id FROM consultants WHERE status = 'active' ORDER BY SUBSTRING(realname FROM (INSTR(realname, ' ')+1)) ASC";
    $q = self::$DB->query($sql);
    while($row = self::$DB->fetch($q)){
      $this->all_rcs[] = array(Consultant::select_name($row), $row->consultant_id);
    }

    $this->all_sems = array();
    $sql = "SELECT *, id as semester_id FROM semesters ORDER BY startdate DESC";
    $q = self::$DB->query($sql);
    while($row = self::$DB->fetch($q)){
      $this->all_sems[] = array(Semester::select_name($row), $row->semester_id);
    }
  }

  /**
   * Prepare the default semester
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
      $this->show_any = false;
      $this->sem = null;
      $this->startd = null;
      $this->stopd = null;
    }

    return true;
  }

  /**
   * Prep the selected semester
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
    } else {
      self::throwError("Semester parameters were not valid");
      return false;
    }
  }

  /**
   * Prepare the entered dates
   *
   * @param string $sd
   * @param string $pd
   * @return boolean
   */
  protected function date_prep($sd, $pd){
    $this->sem = null;
    $this->startd = TOOLS::string_to_date($sd);
    $this->stopd = TOOLS::string_to_date($pd);
    if(!is_int($this->startd) || !is_int($this->stopd) || $this->startd == 0 || $this->stopd == 0){
      $this->show_any = false;
      $this->startd = null;
      $this->stopd = null;
      return false;
    }

    return true;
  }

  /**
   * Prep the selected consultant
   *
   * @param integer $id
   * @return boolean
   */
  protected function prep_consultant($id){
    $this->show_any = true;
    $sql = "SELECT *, consultants.id as consultant_id, tags.id as tag_id FROM consultants, tags WHERE consultants.status = 'active' AND consultants.id = '".$id."' AND tags.id = consultants.tag_id LIMIT 1";
    $q = self::$DB->query($sql);
    if(self::$DB->rows($q) == 1){
      $this->consultant = self::$DB->fetch($q);
      return true;
    } else {
      $this->consultant = null;
      $this->show_any = false;
      self::throwError("Selected consultant was not found in the system.");
      return false;
    }
  }

  /**
   * Actually output the better-modify page
   *
   */
  protected function do_display(){
    if($this->consultant && $this->show_any){
      try{
        $this->info = RCHAdminObject::newobj($this->consultant->consultant_id, $this->startd);
      } catch( Exception $e){
        self::throwError($e->getMessage());
      }
    }

    $this->prep_all_rcs_sems();

    $this->output_page("one_week","inline");
  }

  /**
   * Generate the grid output
   *
   * @see Consultanthour_admin.site_class::do_display()
   * @return string
   */
  protected function myCalendarOutput(){
    $result = "";
    if(!$this->consultant || !$this->info){
      $result .= "<input type='hidden' name='date' id='date' />";
      $result .= "<input type='hidden' name='tddate' id='tddate' />";
      $result .= "<input type='hidden' name='nwdate' id='nwdate' />";
      $result .= "<input type='hidden' name='pwdate' id='pwdate' />";
      return $result;
    }

    $result .= "<table id='maintable' style='width: 100%;' border='0'>";

    $row1 = "";
    $row2 = "";
    $v = 0;
    if(array_key_exists("times", $this->info)){
      $max_j = count($this->info["times"]);
      for($j = 0; $j < $max_j; $j++){
        $extras = 'scope="col"';
        $type = "";
        if(($j % 2) == 1){
          $type = "header2";
          $row1 .= "<th colspan='2' $extras>".TOOLS::hour_s_for($this->info["times"][$j-1], true)."</th>";
        } else {
          $type = "header2a";
        }

        if($j == 0){
          $row1 .= "<td $extras class='header' style='width: 122Px; border-bottom:none;'> </th>";
          $row2 .= "<td class='header' style='width: 122Px;' $extras>Weekday</td>";
        } else {
          $extras .= "id='L$j' ";
          $row2 .= "<td class='$type' $extras>".TOOLS::minutes_s_for($this->info["times"][$j-1]);
        }
      }
      $row2 .= "<td class='header2a' scope='col'>".TOOLS::minutes_s_for($this->info["times"][count($this->info["times"]) - 1]);

      $result .= "<tr>".$row1."</tr><tr>".$row2."</tr>";

      for($i = 0; $i < 7; $i++){
        $result .= "<tr>";
        $row =& $this->info["week"][$i][1];
        $d =& $this->info["week"][$i][0];
        $hc =& $this->info["week"][$i][2];

        $data = TOOLS::$daynames[TOOLS::wday_for($d)]." (".round($hc / 2, 1)." h)";
        $type = "name";
        $extras = "id='U$i'";

        $result .= "<td class='$type' ".(($i == 0) ? "style='border-top: 1Px solid #cccccc;'" : "" )." $extras>$data</td>";

        $max_j = count($row);
        for($j = 0; $j < $max_j; $j++){
          $extras = "";
          $data = "";
          $type = "";
          $color = false;

          if($row[$j]->status != "I"){
            switch($row[$j]->status){
              case "A":
                $type = "timeslot available";
                $color = $this->consultant->color;
                $extras .= $this->appt_movmout_code($row[$j]->meta, $this->consultant->consultant_id, $j);
                $extras .= " ".$this->block_filled_click_code(TOOLS::time_to_s($this->info["times"][$j]), TOOLS::wday_for($this->info["week"][$i][0]), $i, $j);
                break;
              case "C":
                $type = "timeslot oncall";
                $extras .= $this->appt_movmout_code($row[$j]->meta, $this->consultant->consultant_id, $j, true);
                $extras .= " ".$this->block_filled_click_code(TOOLS::time_to_s($this->info["times"][$j]), TOOLS::wday_for($this->info["week"][$i][0]), $i, $j);
                break;
              case "K":
                $type = "timeslot";
                $extras .= $this->appt_movmout_code($row[$j]->meta, $this->consultantv->consultant_id, $j);
                $extras .= " ".$this->block_free_click_code(TOOLS::time_to_s($this->info["times"][$j]), TOOLS::wday_for($this->info["week"][$i][0]), $i, $j);
                break;
              default:
                self::throwError("Unexpected SchedulerBlock status value.");
                break;
            }

            $result .= "<td $extras id='col_${j}_${i}' class='$type' style='".(($color) ? "background-color: #$color;" : "").(($i == 2) ? " border-top: 1Px solid #cccccc;" : "")."'>$data</td>";
          }
        }

        $result .= "</tr>";
      }
    }

    $result .= "</table>";
    //$result .= "</div>";

    return $result;
  }

  /**
   * Generate mouseover and mouseout code
   *
   * @param mixed $appt
   * @param integer $consultant
   * @param integer $row_j
   * @param boolean $oncall
   * @return string
   */
  protected function appt_movmout_code($appt, $consultant, $row_j, $oncall = false){
    if($oncall){
      return "onmouseover=\"tooltip_init(findPosX(this),findPosY(this),'down','ON CALL HOURS','');colorCol(".$row_j.",7,this,1);\" onmouseout=\"tooltip_kill();resetCol(".$row_j.",7,this,1);\"";
    } else {
      return "onmouseover=\"colorCol(".$row_j.",7,this,1);\" onmouseout=\"resetCol(".$row_j.",7,this,1);\"";
    }
  }

  /**
   * Generate onclick for empty boxen
   *
   * @param string $time
   * @param integer $wday
   * @param integer $i
   * @param integer $j
   * @return string
   */
  protected function block_free_click_code($time, $wday, $i, $j){
    return "onclick=\"blockClicked2('$time','$wday','col_${j}_${i}');\"";
  }

  /**
   * Generate onclick for full boxen
   *
   * @param string $time
   * @param integer $wday
   * @param integer $i
   * @param integer $j
   * @return string
   */
  protected function block_filled_click_code($time, $wday, $i, $j){
    return "onclick=\"blockClicked3('$time','$wday','col_${j}_${i}');\"";
  }

  /**
   * Displays a calendar of this month and next month
   *
   * @return string
   */
  protected function gen_calendars(){
    $result = "";
    $result .= "<table><tr>";
    $thismonth = (int)date("n", time());
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
      $result .= "<td style='padding: 2Px; vertical-align: top;'>";
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
