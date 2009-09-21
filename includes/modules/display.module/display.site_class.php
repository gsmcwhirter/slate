<?php
/**
 * KvScheduler - Display SiteClass
 * @package KvScheduler
 * @subpackage Modules
 * @author Greg McWhirter <gsmcwhirter@gmail.comgsmcwhirter@gmail.com>
 * @version 1.0
 */

load_files(
  KVF_MODULES."/display.module/lib/mycalendar.class.php",
  KVF_MODULES."/calendar.module/lib/schedulerblock.class.php"
);

/**
 * Consultant Interface to the calendar
 *
 * @package KvScheduler
 * @subpackage Modules
 *
 */
class display_site_class extends application_site_class{

  /**
   * Date to display
   *
   * @var integer
   */
  protected $date;

  /**
   * Grid information
   *
   * @var array
   */
  protected $info = array();

  /**
   * Future appointments info
   *
   * @var array
   */
  protected $fah;

  /**
   * Future appointments count
   *
   * @var integer
   */
  protected $fahc;

  /**
   * Unconfirmed appointments info
   *
   * @var array
   */
  protected $ucaa;

  /**
   * consultant ID for the current user
   *
   * @var mixed
   */
  protected $user_rc_id = null;

  /**
   * Number of weeks to display
   *
   * @var integer
   */
  protected $numweeks = 1;

  /**
   * Week information
   *
   * @var mixed
   */
  protected $weekinfo;

  /**
   * User's consultant record
   *
   * @var mixed
   */
  protected $consultant;

  /**
   * Constructor
   *
   */
  function __construct(){
    parent::__construct();
    $this->auth_level = ACCESS::display;
    $this->before_filter("prep_vars");
  }

  /**
   * Actually display the page
   *
   * @param string $view
   */
  protected function display_date($view){
    try{
      list($this->info, $this->fah, $this->fahc, $this->ucaa, $this->weekinfo) = MyCalendar::newcal($this->user_rc_id, $this->date, $this->numweeks);
    } catch(Exception $e){
      self::throwError($e->getMessage());
    }

    $this->output_page($view, "inline");
  }

  /**
   * Display the full page
   *
   */
  public function display_date_full(){
    $this->display_date("one_week");
  }

  /**
   * Display the grid only
   *
   */
  public function display_date_part(){
    $this->display_date("calendar");
  }

  /**
   * Display the selector
   */
  public function display_select(){
    $this->output_page("selector", "inline");
  }

  /**
   * Display iCal subscription instructions
   *
   */
  public function ical_instructions(){
    $this->output_page("ical_instructions", "inline");
  }

  /**
   * Display RemoteCal instructions
   *
   */
  public function remotecal_instructions(){
    $this->output_page("remotecal_instructions","inline");
  }

  /**
   * Display the full information for an appointment
   *
   */
  public function view_appointment(){
    $this->appointment = Consultant::check_appt((int)self::$PARAMS["rid"], (int)self::$PARAMS["id"]);
    if($this->appointment){
      $this->rc =& $this->appointment->consultants[(int)self::$PARAMS["rid"]];
      if(array_key_exists("adate", self::$PARAMS) || $this->appointment->repeat != 'TRUE'){
        if(!array_key_exists("adate", self::$PARAMS)){self::$PARAMS["adate"] = $this->appointment->startdate;}
        $this->output_page("full_details","inline");
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
   * Prep associated variables
   *
   * @return boolean true
   */
  protected function prep_vars(){
    if(!array_key_exists("date", self::$PARAMS) || empty(self::$PARAMS["date"])){
      $this->date = TOOLS::date_today();
    } else {
      $this->date = TOOLS::string_to_date(self::$PARAMS["date"]);
    }

    $obj = $this->USER->allrcinfo();
    if(!is_null($obj)){
      $this->user_rc_id = $obj["id"];
      $sql = "SELECT *, consultants.id as consultant_id, tags.id as tag_id FROM consultants, tags WHERE consultants.id = '".$this->user_rc_id."' AND tags.id = consultants.tag_id AND consultants.status = 'active' LIMIT 1";
      $q = self::$DB->query($sql);
      if(self::$DB->rows($q) == 1){
        $this->consultant = self::$DB->fetch($q);
      } else {
        $this->consultant = null;
      }
    } else {
      self::throwError("You do not have a consultant account.");
    }

    return true;
  }

  /**
   * Display the selector calendar
   *
   * @return string
   */
  protected function selectorOutput(){
    $result = "";

    $mdays = TOOLS::month_days($this->date, 0);
    $pmdays = TOOLS::month_days($this->date, -1);

    $startson = TOOLS::wday_for(TOOLS::new_date(TOOLS::year_for($this->date), TOOLS::month_for($this->date), 1));
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

    $result .= "<table class='cal' style='width: 100%;' border='0' id='tab_cal'>";
    $result .= "<tr>";
    $result .= "<th class='arrows' style='text-align: center;' scope='col' onclick=\"linktoweek('".TOOLS::date_to_s(TOOLS::x_months_since(-1, $this->date))."', '".self::url_for("display", "display_date_part")."')\">&lt;</th>";
    $result .= "<th class='title' style='text-align: center;' scope='col' colspan='5'>".TOOLS::month_name_for(TOOLS::month_for($this->date))."</th>";
    $result .= "<th class='arrows' style='text-align: center;' scope='col' onclick=\"linktoweek('".TOOLS::date_to_s(TOOLS::x_months_since(1, $this->date))."', '".self::url_for("display", "display_date_part")."')\">&gt;</th>";
    $result .= "</tr>";
    $result .= "<tr>";
    for($i = 0; $i < 7; $i++){
      $result .= "<td class='header'>".TOOLS::weekday_transform($i)."</td>";
    }
    $result .= "</tr>";
    $wc = 0;
    foreach($calendar as $week){
      $typetr = "";
      if(in_array(TOOLS::day_for($this->date) + $mstart - 1, TOOLS::int_range(7 *  $wc, 7 * ($wc + 1)))){
        $typetr = "selrow";
      }
      $result .= "<tr class='".$typetr."'>";
      $max_i = count($week);
      for($i = 0; $i < $max_i; $i++){
        $type = "day";
        $check = ($wc * 7 + $i);
        if($check < $mstart || $check >= $mend){
          $type = "header pointer";
        }
        $today = TOOLS::date_today();
        if($week[$i] == TOOLS::day_for($today) && $check >= $mstart && $check <= $mend && TOOLS::month_for($this->date) == TOOLS::month_for($today) && TOOLS::year_for($this->date) == TOOLS::year_for($today)){
          $type == "day today";
        }
        $nd = TOOLS::x_days_since($check - $mstart, TOOLS::new_date(TOOLS::year_for($this->date), TOOLS::month_for($this->date), 1));
        $result .= "<td class='".$type."' id='CD".TOOLS::leftzeropad(TOOLS::month_for($nd), 2).TOOLS::leftzeropad($week[$i], 2)."' onclick=\"linktoweek('".TOOLS::date_to_s($nd)."', '".self::url_for("display", "display_date_part")."')\">".$week[$i]."</td>";
      }
      $result .= "</tr>";
      $wc++;
    }
    $result .= "</table>";

    return $result;
  }

  /**
   * Display the selector framing
   *
   * @return string
   */
  protected function daySelectOutput(){
    $result = "";
    $url = self::url_for('display','display_date_part');
    $result .= "<div id='grid-2a'>";
    $result .= "<div class='button' id='today' style='color:#f68d91;' onclick=\"linktoweek(document.getElementById('tddate').value, '".$url."')\" onmouseover=\"this.style.backgroundColor='#fff87f'\" onmouseout=\"this.style.backgroundColor='#ffffff'\">Today</div>";
    $result .= "<div class='button' id='prev_day' onclick=\"linktoweek(document.getElementById('pwdate').value, '".$url."')\" onmouseover=\"this.style.backgroundColor='#fff87f'\" onmouseout=\"this.style.backgroundColor='#ffffff'\">Prev</div>";
    $result .= "<div class=\"button\" id=\"next_day\" onclick=\"linktoweek(document.getElementById('nwdate').value, '".$url."')\" onmouseover=\"this.style.backgroundColor='#fff87f'\" onmouseout=\"this.style.backgroundColor='#ffffff'\">Next</div>";
    $result .= "<div class=\"button\" id=\"jump_day\" style=\"border-bottom:1px solid #b4b4b4;\" onclick=\"jumptodate('".$url."')\" onmouseover=\"this.style.backgroundColor='#fff87f'\" onmouseout=\"this.style.backgroundColor='#ffffff'\">Jump</div>";
    $result .= "</div>";
    $result .= "<div id='grid-2b'>";
    $result .= "<div id='selector_div'>";
    $result .= $this->selectorOutput();
    $result .= "</div></div>";

    return $result;
  }

  /**
   * Display the grid itself
   *
   * @return string
   */
  protected function myCalendarOutput(){
    $result = "";

    if(!$this->consultant || !$this->info){
      $result .= "<input type='hidden' name='date' id='date' value='".TOOLS::date_to_s($this->date)."' />";
      $result .= "<input type='hidden' name='tddate' id='tddate' value='".TOOLS::date_to_s(TOOLS::date_today())."' />";
      $result .= "<input type='hidden' name='nwdate' id='nwdate' value='".TOOLS::date_to_s(TOOLS::x_days_since(7, $this->date))."' />";
      $result .= "<input type='hidden' name='pwdate' id='pwdate' value='".TOOLS::date_to_s(TOOLS::x_days_since(-7, $this->date))."' />";
      return $result;
    }

    $result .= "<div id=\"dating\" class=\"clearfix\"><div style=\"float:left;\">".TOOLS::day_for($this->weekinfo["start"])." ".TOOLS::month_name_for(TOOLS::month_for($this->weekinfo["start"]))." ".TOOLS::year_for($this->weekinfo["start"])." to ".TOOLS::day_for($this->weekinfo["stop"])." ".TOOLS::month_name_for(TOOLS::month_for($this->weekinfo["stop"]))." ".TOOLS::year_for($this->weekinfo["stop"])."</div></div>";
    $result .= "<div id=\"gridcover\">";
    $result .= "<table id='maintable' style='width: 100%;' border='0'>";
    $row1 = "";
    $row2 = "";
    $row3 = "";
    $row4 = "";

    if(array_key_exists("times", $this->info)){
      $max_j = count($this->info["times"]);
      for($j = 0; $j < $max_j; $j++){
        $extras = 'scope="col"';
        $type = "";
        if(($j % 2) == 1){
          $type = "header2";
          $row1 .= "<th colspan='2' ".$extras.">".TOOLS::hour_s_for($this->info["times"][$j-1], true)."</th>";
          $row4 .= "<th colspan='2' ".$extras." style='border-top:none;'>".TOOLS::hour_s_for($this->info["times"][$j-1], true)."</th>";
        } else {
          $type = "header2a";
        }

        if($j == 0){
          $row1 .= "<th style='width:122Px; border-bottom:none;' ".$extras." class='header'></th>";
          $row2 .= "<td class='header' style='width:122Px;' ".$extras.">Date</td>";
          $row3 .= "<td class='header' style='width: 122Px;' ".$extras." rowspan='2'> </td>";
        } else {
          $extras .= "id='L".$j."' ";
          $row2 .= "<td class='".$type."' ".$extras.">".TOOLS::minutes_s_for($this->info["times"][$j-1])."</td>";
          $row3 .= "<td class='".$type."' style='border-bottom: none;' ".$extras.">".TOOLS::minutes_s_for($this->info["times"][$j-1])."</td>";
        }
      }
      $row2 .= "<td class='header2a' scope='col'>".TOOLS::minutes_s_for($this->info["times"][count($this->info["times"]) - 1]);
      $row3 .= "<td class='header2a' scope='col' style='border-bottom: none;'>".TOOLS::minutes_s_for($this->info["times"][count($this->info["times"]) - 1]);

      $result .= "<tr>".$row1."</tr><tr>".$row2."</tr>";

      $total = (count($this->info["week"]) + 2 > 9) ? count($this->info["week"]) + 2 : 9;

      for($i = 0; $i < (7 * (int)$this->numweeks) + 2; $i++){
        $result .= "<tr>";

        if($i == 0){
          $row =& $this->info["today"];
          $d = TOOLS::date_today();
        } elseif($i == 1){
          $max_j = count($this->info["times"]);
          for($j = 0; $j < $max_j + 1; $j++){
            $result .= "<td>&nbsp;</td>";
          }
          $result .= "</tr>";
          continue;
        } else {
          $row =& $this->info["week"][$i - 2][1];
          $d =& $this->info["week"][$i - 2][0];
        }



        $data = ($i == 0) ? "TODAY" : TOOLS::$dayabbrs[TOOLS::wday_for($d)]." ".TOOLS::day_for($d)." ".TOOLS::month_name_for(TOOLS::month_for($d));
        $type = "name";
        $extras = "id='U$i'";

        $result .= "<td class='$type' ".(($i == 2) ? "style='border-top: 1Px solid #cccccc;'" : "" )." $extras>$data</td>";

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
                break;
              case "C":
                $type = "timeslot oncall";
                $extras .= $this->appt_movmout_code($row[$j]->meta, $this->consultant->consultant_id, $total, $j, 1, true);
                break;
              case "B":
                $type = "timeslot scheduled";
                $extras .= "colspan='".$row[$j]->span."' ";
                $extras .= $this->appt_onclick_code($row[$j]->meta, TOOLS::date_to_s($d))." ";
                $extras .= $this->appt_movmout_code($row[$j]->meta, $this->consultant->consultant_id, $total, $j, $row[$j]->span);
                break;
              case "K":
                $type = "timeslot";
                $data = " ";
                break;
              case "O":
                $type = "timeslot lockout";
                $extras .= "colspan='".$row[$j]->span."' ";
                $extras .= $this->appt_movmout_code($row[$j]->meta, $this->consultant->consultant_id, $total, $j, $row[$j]->span);
                break;
              default:
                self::throwError("Unexpected SchedulerBlock status value.");
                break;
            }

            $result .= "<td $extras id='col_".$j."_".$i."' class='$type' style='".(($color) ? "background-color: #$color;" : "").(($i == 2) ? " border-top: 1Px solid #cccccc;" : "")."'>$data</td>";
          }
        }

        $result .= "</tr>";
      }

      $result .= "<tr>";
      $max_i = count($this->info["times"]);
      for($i = 0; $i < $max_i + 1; $i++){
        $result .= "<td>&nbsp;</td>";
      }
      $result .= "</tr>";
      $cc = count($this->info["times"]) + 1;
    } else {
      $extras = 'scope="col"';
      $row1 .= "<th style='width:122Px; border-bottom:none;' colspan='2' $extras>&nbsp;</th>";
      $row2 .= "<td class='header' style='width: 122Px;'>Date</td>";
      $row2 .= "<td class='header'>&nbsp;</td>";

      $result .= "<tr>";
      $result .= $row1;
      $result .= "</tr>";
      $result .= "<tr>";
      $result .= $row2;
      $result .= "</tr>";

      $cc = 2;
    }

    $result .= $this->unconfirmed_appts($this->ucaa, (array_key_exists("times", $this->info)) ? count($this->info["times"]) + 1 : 1, $this->consultant);

    $result .= "<tr>";
    for($i = 0; $i < $cc; $i++){
      $result .= "<td>&nbsp;</td>";
    }
    $result .= "</tr>";

    $result .= $this->future_appts($this->fah, (array_key_exists("times", $this->info)) ? count($this->info["times"]) + 1 : 1, $this->consultant->consultant_id);

    $ttd = 40 - ((($this->numweeks * 7) + 2) + ($this->fahc + 1));

    if($ttd > 0){
      for($i = 0; $i < $ttd; $i++){
        $result .= "<tr>";
        for($j = 0; $j < $cc; $j++){
          $result .= "<td>&nbsp;</td>";
        }
        $result .= "</tr>";
      }
    }

    $result .= "<tr>";
    $result .= $row3;
    $result .= "</tr><tr>";
    $result .= $row4;
    $result .= "</tr>";

    $result .= "<input type='hidden' name='date' id='date' value='".TOOLS::date_to_s($this->date)."' />";
    $result .= "<input type='hidden' name='tddate' id='tddate' value='".TOOLS::date_to_s(TOOLS::date_today())."' />";
    $result .= "<input type='hidden' name='nwdate' id='nwdate' value='".TOOLS::date_to_s(TOOLS::x_days_since(7, $this->date))."' />";
    $result .= "<input type='hidden' name='pwdate' id='pwdate' value='".TOOLS::date_to_s(TOOLS::x_days_since(-7, $this->date))."' />";
    $result .= "<input type='hidden' name='colcount' id='colcount' value='".$cc."' />";
    $result .= "<input type='hidden' name='startrow' id='startrow' />";

    $result .= "</table>";
    $result .= "</div>";

    return $result;
  }

  /**
   * Generate onclick code
   *
   * @param mixed $appt
   * @param integer $date
   * @return string
   */
  protected function appt_onclick_code($appt, $date){
    $result = "";
    $result .= "onclick=\"linkViewFullDetails(".$this->consultant->consultant_id.", ".$appt->appointment_id.",'".$date."')\"";

    return $result;
  }

  /**
   * Generate mouseover and mouseout code
   *
   * @param mixed $appt
   * @param integer $consultant
   * @param integer $total
   * @param integer $row_j
   * @param integer $colspan
   * @param boolean $oncall
   * @return string
   */
  protected function appt_movmout_code($appt, $consultant, $total, $row_j, $colspan, $oncall = false){
    $result = "";
    if($oncall){
      $result .= "onmouseover=\"tooltip_init(findPosX(this), findPosY(this),'down','ON CALL HOURS','');colorCol(".$row_j.",".$total.",this,".$colspan.");\" onmouseout=\"tooltip_kill();resetCol(".$row_j.",".$total.",this,".$colspan.");\"";
    } elseif ($appt && $appt->lockout == 'FALSE'){
      $confirmed = (!is_null($appt->consultantappts[$consultant]) && $appt->consultantappts[$consultant]["confirmed"] == "TRUE") ? true : false;
      if($appt->tm_type == "Ticket"){
        $result .= "onmouseover=\"tooltip_init(findPosX(this),findPosY(this),'down', '".$appt->tm->remedy_ticket."', '".TOOLS::escape_quotes($appt->tm->person)."','".TOOLS::escape_quotes($appt->location_name)."',".(($confirmed) ? "true" : "false").");colorCol(".$row_j.",".$total.",this,".$colspan.");\" onmouseout=\"tooltip_kill();resetCol(".$row_j.",".$total.",this,".$colspan.");\"";
      } elseif($appt->tm_type == "Meeting" || $appt->tm_type == "Meecket"){
        $result .= "onmouseover=\"tooltip_init(findPosX(this),findPosY(this),'down', '<strong>Meeting</strong>', '".TOOLS::escape_quotes($appt->tm->subject)."','".TOOLS::escape_quotes($appt->location_name)."', ".(($confirmed) ? "true" : "false").");colorCol(".$row_j.",".$total.",this,".$colspan.");\" onmouseout=\"tooltip_kill();resetCol(".$row_j.",".$total.",this,".$colspan.");\"";
      }
    } else {
      $result .= "onmouseover=\"tooltip_init(findPosX(this), findPosY(this),'down','LOCKOUT','".TOOLS::escape_quotes($appt->lockout_user)."','');colorCol(".$row_j.",".$total.",this,".$colspan.");\" onmouseout=\"tooltip_kill();resetCol(".$row_j.",".$total.",this,".$colspan.");\"";
    }

    return $result;
  }

  /**
   * Generate future appointments output
   *
   * @param array $info
   * @param integer $colspan
   * @param integer $consultant
   * @return string
   */
  protected function future_appts($info, $colspan, $consultant){
    $result = "";

    $k = 0;
    ksort($info);
    foreach($info as $day => $appts){
      $rowspan = count($appts);
      if($rowspan == 0){continue;}
      $max_i = count($appts);
      for($i = 0; $i < $max_i; $i++){
        $result .= "<tr>";
        if($i == 0){
          $result .= "<td class='name' rowspan='$rowspan' style='vertical-align: top: width 122Px; ".(($k == 0) ? "border-top: 1Px solid #cccccc;" : "")."'>".TOOLS::$dayabbrs[TOOLS::wday_for($day)]." ".TOOLS::day_for($day)." ".TOOLS::month_name_for(TOOLS::month_for($day))."</td>";
        }
        if($appts[$i]->tm_type == "Ticket"){
          $text = kvframework_markup::link_to_function("[Appointment]", "linkViewFullDetails(".$consultant.", ".$appts[$i]->appointment_id.", '".TOOLS::date_to_s($day)."')")." [".TOOLS::time_to_s(TOOLS::string_to_time($appts[$i]->starttime), true)." - ".TOOLS::time_to_s(TOOLS::string_to_time($appts[$i]->stoptime), true)."] ".TOOLS::escape_quotes($appts[$i]->locdetails)." ".$appts[$i]->location_name." with ".$appts[$i]->tm->person." [Ticket: <a href='http://remedy01.cssd.pitt.edu/arsys/servlet/ViewFormServlet?formalias=PittCallTicket&viewalias=WebManage&mode=QUERY&server=remedy02&eid=".$appts[$i]->tm->remedy_ticket."' target='_new'>".$appts[$i]->tm->remedy_ticket."</a>]";
        } elseif($appts[$i]->tm_type == "Meeting" || $appts[$i]->tm_type == "Meecket"){
          $text = kvframework_markup::link_to_function("[Meeting: ".$appts[$i]->tm->subject."] ", "linkViewFullDetails(".$consultant.", ".$appts[$i]->appointment_id.", '".TOOLS::date_to_s($day)."')")."[".TOOLS::time_to_s(TOOLS::string_to_time($appts[$i]->starttime), true)." - ".TOOLS::time_to_s(TOOLS::string_to_time($appts[$i]->stoptime), true)."] ".TOOLS::escape_quotes($appts[$i]->locdetails)." ".$appts[$i]->location_name;
        }

        $result .= "<td class='timeslot' style='".(($k == 0) ? "border-top: 1Px solid #cccccc;" : "")."' colspan='$colspan'>$text</td>";
        $result .= "</tr>";
      }
      $k++;
    }

    return $result;
  }

  /**
   * Generate unconfirmed appointments output
   *
   * @param array $info
   * @param integer $colspan
   * @param mixed $consultant
   * @return string
   */
  protected function unconfirmed_appts($info, $colspan, $consultant){
    $result = "";

    $k = 0;
    $rowspan = count($info);

    foreach($info as $ar){
      $result .= "<tr>";
      if($k == 0){
        $result .= "<td class='name' rowspan='$rowspan' style='vertical-align: top; width:122px; border-top:1px solid #cccccc;'>Unconfirmed Appointments</td>";
      }
      $result .= "<td class='timeslot' style='".(($k == 0) ? "border-top:1px solid #cccccc;" : "")."' colspan='$colspan'>";
      $result .= "<a href='".self::full_url_for("user","receipt_form",array("a" => $ar->appointment_id, "r" => $consultant->consultant_id, "v" => $ar->confirm_version))."' target='_new'>".self::full_url_for("user","receipt_form",array("a" => $ar->appointment_id, "r" => $consultant->consultant_id, "v" => $ar->confirm_version))."</a>";
      $result .= "</td>";
      $result .= "</tr>";
      $k++;
    }

    return $result;
  }

}

?>
